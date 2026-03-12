<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionModel extends Model
{
    protected $table            = 'subscriptions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'tenant_id',
        'plan_id',
        'billing_type',
        'status',
        'start_date',
        'end_date',
        'started_at',
        'expires_at',
        'is_demo',
        'demo_readonly',
        'promo_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function baseQuery()
    {
        return $this->select('
                subscriptions.*,
                subscription_plans.plan_code,
                subscription_plans.plan_name,
                subscription_plans.plan_name_th,
                subscription_plans.plan_name_en,
                subscription_plans.plan_type,
                subscription_plans.price,
                subscription_plans.duration_days,
                subscription_plans.is_lifetime
            ')
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left');
    }

    protected function endOfDay(?string $date): ?int
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        $ts = strtotime($date . ' 23:59:59');
        return $ts === false ? null : $ts;
    }

    protected function timestampOf(?string $datetime): ?int
    {
        $datetime = trim((string) $datetime);
        if ($datetime === '') {
            return null;
        }

        $ts = strtotime($datetime);
        return $ts === false ? null : $ts;
    }

    public function getLatestByTenant(int $tenantId): ?array
    {
        if ($tenantId <= 0) {
            return null;
        }

        $query = $this->baseQuery()->where('subscriptions.tenant_id', $tenantId);

        if ($this->db->fieldExists('deleted_at', 'subscriptions')) {
            $query->where('subscriptions.deleted_at', null);
        }

        $row = $query
            ->orderBy('subscriptions.id', 'DESC')
            ->first();

        return $row ?: null;
    }

    public function getActiveByTenant(int $tenantId): ?array
    {
        if ($tenantId <= 0) {
            return null;
        }

        $rows = $this->baseQuery()
            ->where('subscriptions.tenant_id', $tenantId)
            ->whereIn('subscriptions.status', ['active', 'demo', 'trial'])
            ->where('subscriptions.deleted_at', null)
            ->orderBy('subscriptions.id', 'DESC')
            ->findAll();

        foreach ($rows as $row) {
            if (! $this->isExpired($row)) {
                return $row;
            }
        }

        return null;
    }

    public function isTenantActive(int $tenantId): bool
    {
        return $this->getActiveByTenant($tenantId) !== null;
    }

    public function isExpired(?array $subscription, ?int $referenceTs = null): bool
    {
        if (empty($subscription)) {
            return false;
        }

        $referenceTs ??= time();

        $status = strtolower((string) ($subscription['status'] ?? ''));

        if (in_array($status, ['expired', 'cancelled', 'inactive', 'suspended'], true)) {
            return true;
        }

        $isLifetime = (int) ($subscription['is_lifetime'] ?? 0) === 1;
        if ($isLifetime) {
            return false;
        }

        $expiresAtTs = $this->timestampOf($subscription['expires_at'] ?? null);
        if ($expiresAtTs !== null) {
            return $expiresAtTs < $referenceTs;
        }

        $endDateTs = $this->endOfDay($subscription['end_date'] ?? null);
        if ($endDateTs !== null) {
            return $endDateTs < $referenceTs;
        }

        return false;
    }

    public function isDemo(?array $subscription): bool
    {
        if (empty($subscription)) {
            return false;
        }

        if ((int) ($subscription['is_demo'] ?? 0) === 1) {
            return true;
        }

        return strtolower((string) ($subscription['status'] ?? '')) === 'demo';
    }

    public function isDemoReadonly(?array $subscription): bool
    {
        if (! $this->isDemo($subscription)) {
            return false;
        }

        return (int) ($subscription['demo_readonly'] ?? 0) === 1;
    }

    public function getStatusLabel(?array $subscription): string
    {
        if (empty($subscription)) {
            return 'inactive';
        }

        if ($this->isExpired($subscription)) {
            return 'expired';
        }

        if ($this->isDemo($subscription)) {
            return 'demo';
        }

        return strtolower((string) ($subscription['status'] ?? 'active'));
    }
}