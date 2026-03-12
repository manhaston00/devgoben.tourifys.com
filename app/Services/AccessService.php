<?php

namespace App\Services;

use App\Models\BranchModel;
use App\Models\ProductModel;
use App\Models\SubscriptionModel;
use App\Models\SubscriptionPlanFeatureModel;
use App\Models\TenantModel;
use Config\Database;

class AccessService
{
    protected TenantModel $tenantModel;
    protected SubscriptionModel $subscriptionModel;
    protected SubscriptionPlanFeatureModel $subscriptionPlanFeatureModel;
    protected BranchModel $branchModel;

    protected array $planFeaturesCache = [];
    protected array $tenantCache = [];
    protected array $subscriptionCache = [];

    protected int $cacheTtl = 300;

    public function __construct()
    {
        helper(['tenant', 'branch']);

        $this->tenantModel                  = new TenantModel();
        $this->subscriptionModel            = new SubscriptionModel();
        $this->subscriptionPlanFeatureModel = new SubscriptionPlanFeatureModel();
        $this->branchModel                  = new BranchModel();
    }

    public function isSuperAdmin(): bool
    {
        return function_exists('is_super_admin') && is_super_admin();
    }

    public function normalizeFeatureKey(string $featureKey): string
    {
        return normalize_feature_key($featureKey);
    }

    public function getCurrentTenantId(): ?int
    {
        if ($this->isSuperAdmin()) {
            $tenantId = session('tenant_id');
            return ($tenantId !== null && $tenantId !== '') ? (int) $tenantId : null;
        }

        $tenantId = session('tenant_id');
        if ($tenantId !== null && $tenantId !== '') {
            return (int) $tenantId;
        }

        $branchId = function_exists('current_branch_id')
            ? current_branch_id()
            : session('branch_id');

        if (! $branchId) {
            return null;
        }

        $branch = $this->branchModel
            ->where('id', (int) $branchId)
            ->where('deleted_at', null)
            ->first();

        if (! $branch || empty($branch['tenant_id'])) {
            return null;
        }

        return (int) $branch['tenant_id'];
    }

    public function getCurrentTenant(): ?array
    {
        $tenantId = $this->getCurrentTenantId();

        if (! $tenantId) {
            return null;
        }

        if (isset($this->tenantCache[$tenantId])) {
            return $this->tenantCache[$tenantId];
        }

        $cacheKey = tenant_cache_key('current_tenant', $tenantId);
        $cached   = cache()->get($cacheKey);

        if (is_array($cached)) {
            $this->tenantCache[$tenantId] = $cached;
            return $cached;
        }

        $tenant = $this->tenantModel->find($tenantId) ?: null;

        if ($tenant) {
            cache()->save($cacheKey, $tenant, $this->cacheTtl);
        }

        $this->tenantCache[$tenantId] = $tenant;

        return $tenant;
    }

    public function getCurrentSubscription(): ?array
    {
        $tenantId = $this->getCurrentTenantId();

        if (! $tenantId) {
            return null;
        }

        if (isset($this->subscriptionCache[$tenantId])) {
            return $this->subscriptionCache[$tenantId];
        }

        $cacheKey = tenant_cache_key('current_subscription', $tenantId);
        $cached   = cache()->get($cacheKey);

        if (is_array($cached)) {
            $this->subscriptionCache[$tenantId] = $cached;
            return $cached;
        }

        $subscription = $this->subscriptionModel->getActiveByTenant($tenantId);

        if ($subscription) {
            cache()->save($cacheKey, $subscription, $this->cacheTtl);
        }

        $this->subscriptionCache[$tenantId] = $subscription;

        return $subscription;
    }

    public function tenantIsActive(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $tenant = $this->getCurrentTenant();

        if (! $tenant) {
            return false;
        }

        return in_array((string) ($tenant['status'] ?? ''), ['active', 'demo'], true);
    }

    public function subscriptionIsActive(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $subscription = $this->getCurrentSubscription();

        if (! $subscription) {
            return false;
        }

        return ! $this->subscriptionModel->isExpired($subscription);
    }

    protected function getPlanIdFromCurrentSubscription(): int
    {
        $subscription = $this->getCurrentSubscription();

        return (int) ($subscription['plan_id'] ?? 0);
    }

    protected function getPlanFeatures(int $planId): array
    {
        if ($planId <= 0) {
            return [];
        }

        if (isset($this->planFeaturesCache[$planId])) {
            return $this->planFeaturesCache[$planId];
        }

        $cacheKey = tenant_cache_key('plan_features', $planId);
        $cached   = cache()->get($cacheKey);

        if (is_array($cached)) {
            $this->planFeaturesCache[$planId] = $cached;
            return $cached;
        }

        $rows = $this->subscriptionPlanFeatureModel->getPlanFeatures($planId);

        $normalized = [];

        foreach ($rows as $key => $row) {
            $normalized[$this->normalizeFeatureKey((string) $key)] = $row;
        }

        cache()->save($cacheKey, $normalized, $this->cacheTtl);
        $this->planFeaturesCache[$planId] = $normalized;

        return $normalized;
    }

    public function featureValue(string $featureKey, $default = null)
    {
        $featureKey = $this->normalizeFeatureKey($featureKey);
        $planId     = $this->getPlanIdFromCurrentSubscription();

        if ($planId <= 0 || $featureKey === '') {
            return $default;
        }

        $rows = $this->getPlanFeatures($planId);

        if (! isset($rows[$featureKey])) {
            return $default;
        }

        $row = $rows[$featureKey];

        if (($row['limit'] ?? null) !== null && $row['limit'] !== '') {
            return $row['limit'];
        }

        return ((int) ($row['enabled'] ?? 0) === 1) ? '1' : '0';
    }

    public function featureEnabled(string $featureKey, bool $default = false): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $value = $this->featureValue($featureKey, $default ? '1' : '0');

        if (is_bool($value)) {
            return $value;
        }

        return in_array(
            strtolower(trim((string) $value)),
            ['1', 'true', 'yes', 'on', 'enabled'],
            true
        );
    }

    public function limitValue(string $featureKey, ?int $default = null): ?int
    {
        if ($this->isSuperAdmin()) {
            return null;
        }

        $value = $this->featureValue($featureKey, $default);

        if ($value === null || $value === '') {
            return $default;
        }

        if (strtolower((string) $value) === 'unlimited') {
            return null;
        }

        return (int) $value;
    }

    public function canCreateBranch(?int $tenantId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $tenantId = $tenantId ?: $this->getCurrentTenantId();

        if (! $tenantId) {
            return false;
        }

        $limit = $this->limitValue('branches_limit', null);

        if ($limit === null || $limit <= 0) {
            return true;
        }

        $count = (int) $this->branchModel
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->countAllResults();

        return $count < $limit;
    }

    public function canCreateUser(?int $tenantId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $tenantId = $tenantId ?: $this->getCurrentTenantId();

        if (! $tenantId) {
            return false;
        }

        $limit = $this->limitValue('users_limit', null);

        if ($limit === null || $limit <= 0) {
            return true;
        }

        $count = (int) Database::connect()
            ->table('users')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->countAllResults();

        return $count < $limit;
    }

    public function canCreateProduct(?int $tenantId = null): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $tenantId = $tenantId ?: $this->getCurrentTenantId();

        if (! $tenantId) {
            return false;
        }

        $limit = $this->limitValue('products_limit', null);

        if ($limit === null || $limit <= 0) {
            return true;
        }

        $count = (int) (new ProductModel())
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->countAllResults();

        return $count < $limit;
    }

    public function clearTenantCache(?int $tenantId = null, ?int $planId = null): void
    {
        $tenantId = $tenantId ?: $this->getCurrentTenantId();

        if ($tenantId) {
            cache()->delete(tenant_cache_key('current_tenant', $tenantId));
            cache()->delete(tenant_cache_key('current_subscription', $tenantId));
            unset($this->tenantCache[$tenantId], $this->subscriptionCache[$tenantId]);
        }

        if ($planId) {
            cache()->delete(tenant_cache_key('plan_features', $planId));
            unset($this->planFeaturesCache[$planId]);
        }
    }
}