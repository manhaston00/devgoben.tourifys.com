<?php

if (! function_exists('tenant_subscription')) {
    function tenant_subscription(?int $tenantId = null): ?array
    {
        $currentTenantId = $tenantId;

        if (! $currentTenantId && function_exists('current_tenant_id')) {
            $currentTenantId = (int) current_tenant_id();
        }

        if ((int) $currentTenantId <= 0) {
            return null;
        }

        $model = new \App\Models\SubscriptionModel();

        $subscription = $model->getLatestByTenant((int) $currentTenantId);

        return is_array($subscription) ? $subscription : null;
    }
}

if (! function_exists('tenant_has_active_subscription')) {
    function tenant_has_active_subscription(?int $tenantId = null): bool
    {
        $subscription = tenant_subscription($tenantId);

        if (! $subscription) {
            return false;
        }

        $status = strtolower((string) ($subscription['status'] ?? 'active'));

        if (in_array($status, ['expired', 'suspended', 'cancelled', 'inactive'], true)) {
            return false;
        }

        $expiresAt = $subscription['expires_at'] ?? null;

        if (empty($expiresAt)) {
            return true;
        }

        $expiresTs = strtotime((string) $expiresAt);
        if ($expiresTs === false) {
            return true;
        }

        return $expiresTs >= time();
    }
}

if (! function_exists('tenant_subscription_expired')) {
    function tenant_subscription_expired(?int $tenantId = null): bool
    {
        $subscription = tenant_subscription($tenantId);

        if (! $subscription) {
            return false;
        }

        $status = strtolower((string) ($subscription['status'] ?? 'active'));

        if ($status === 'expired') {
            return true;
        }

        if ($status === 'suspended' || $status === 'cancelled' || $status === 'inactive') {
            return true;
        }

        $expiresAt = $subscription['expires_at'] ?? null;

        if (empty($expiresAt)) {
            return false;
        }

        $expiresTs = strtotime((string) $expiresAt);
        if ($expiresTs === false) {
            return false;
        }

        return $expiresTs < time();
    }
}

if (! function_exists('tenant_is_demo')) {
    function tenant_is_demo(?int $tenantId = null): bool
    {
        $subscription = tenant_subscription($tenantId);

        return ! empty($subscription) && (int) ($subscription['is_demo'] ?? 0) === 1;
    }
}

if (! function_exists('tenant_demo_readonly')) {
    function tenant_demo_readonly(?int $tenantId = null): bool
    {
        $subscription = tenant_subscription($tenantId);

        return ! empty($subscription)
            && (int) ($subscription['is_demo'] ?? 0) === 1
            && (int) ($subscription['demo_readonly'] ?? 0) === 1;
    }
}

if (! function_exists('tenant_plan_id')) {
    function tenant_plan_id(?int $tenantId = null): int
    {
        $subscription = tenant_subscription($tenantId);

        return (int) ($subscription['plan_id'] ?? 0);
    }
}