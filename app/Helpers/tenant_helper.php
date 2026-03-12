<?php

use App\Services\AccessService;

if (! function_exists('tenant_access_service')) {
    function tenant_access_service(): AccessService
    {
        static $service = null;

        if (! $service instanceof AccessService) {
            $service = new AccessService();
        }

        return $service;
    }
}

if (! function_exists('normalize_feature_key')) {
    function normalize_feature_key(?string $featureKey): string
    {
        $featureKey = strtolower(trim((string) $featureKey));

        if ($featureKey === '') {
            return '';
        }

        $map = [
            'pos'             => 'pos.access',
            'pos.view'        => 'pos.access',
            'kitchen'         => 'pos.access',
            'kitchen.view'    => 'pos.access',

            'sell'            => 'pos.sell',

            'reservation'     => 'reservations.manage',
            'reservations'    => 'reservations.manage',

            'table'           => 'tables.manage',
            'tables'          => 'tables.manage',

            'zone'            => 'zones.manage',
            'zones'           => 'zones.manage',

            'report'          => 'reports.basic',
            'reports'         => 'reports.basic',

            'multi_branch'    => 'multi.branch',
            'branch.multi'    => 'multi.branch',

            'branches.limit'  => 'branches_limit',
            'branch_limit'    => 'branches_limit',
            'branches_limit'  => 'branches_limit',

            'users.limit'     => 'users_limit',
            'user_limit'      => 'users_limit',
            'users_limit'     => 'users_limit',

            'products.limit'  => 'products_limit',
            'product_limit'   => 'products_limit',
            'products_limit'  => 'products_limit',
        ];

        return $map[$featureKey] ?? $featureKey;
    }
}

if (! function_exists('current_tenant_id')) {
    function current_tenant_id(): ?int
    {
        return tenant_access_service()->getCurrentTenantId();
    }
}

if (! function_exists('current_tenant')) {
    function current_tenant(): ?array
    {
        return tenant_access_service()->getCurrentTenant();
    }
}

if (! function_exists('current_subscription')) {
    function current_subscription(): ?array
    {
        return tenant_access_service()->getCurrentSubscription();
    }
}

if (! function_exists('tenant_active')) {
    function tenant_active(): bool
    {
        return tenant_access_service()->tenantIsActive();
    }
}

if (! function_exists('subscription_active')) {
    function subscription_active(): bool
    {
        return tenant_access_service()->subscriptionIsActive();
    }
}

if (! function_exists('feature_value')) {
    function feature_value(string $featureKey, $default = null)
    {
        return tenant_access_service()->featureValue($featureKey, $default);
    }
}

if (! function_exists('feature_enabled')) {
    function feature_enabled(string $featureKey, bool $default = false): bool
    {
        return tenant_access_service()->featureEnabled($featureKey, $default);
    }
}

if (! function_exists('limit_value')) {
    function limit_value(string $featureKey, ?int $default = null): ?int
    {
        return tenant_access_service()->limitValue($featureKey, $default);
    }
}

if (! function_exists('can_create_branch_by_plan')) {
    function can_create_branch_by_plan(?int $tenantId = null): bool
    {
        return tenant_access_service()->canCreateBranch($tenantId);
    }
}

if (! function_exists('can_create_user_by_plan')) {
    function can_create_user_by_plan(?int $tenantId = null): bool
    {
        return tenant_access_service()->canCreateUser($tenantId);
    }
}

if (! function_exists('can_create_product_by_plan')) {
    function can_create_product_by_plan(?int $tenantId = null): bool
    {
        return tenant_access_service()->canCreateProduct($tenantId);
    }
}

if (! function_exists('normalize_tenant_code_value')) {
    function normalize_tenant_code_value(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = preg_replace('/_+/', '_', (string) $value);
        $value = trim((string) $value, '_');

        return $value;
    }
}

if (! function_exists('current_tenant_login_prefix')) {
    function current_tenant_login_prefix(): string
    {
        $tenant = current_tenant();

        $prefix = (string) ($tenant['login_prefix'] ?? '');
        if ($prefix === '') {
            $prefix = (string) ($tenant['tenant_code'] ?? '');
        }

        return normalize_tenant_code_value($prefix);
    }
}

if (! function_exists('make_tenant_login_username')) {
    function make_tenant_login_username(?string $loginPrefix, ?string $baseUsername): string
    {
        $loginPrefix  = normalize_tenant_code_value($loginPrefix);
        $baseUsername = strtolower(trim((string) $baseUsername));
        $baseUsername = preg_replace('/[^a-z0-9._-]+/', '', $baseUsername);

        if ($baseUsername === '') {
            return '';
        }

        if ($loginPrefix === '') {
            return $baseUsername;
        }

        $prefix = $loginPrefix . '_';

        if (strpos($baseUsername, $prefix) === 0) {
            return $baseUsername;
        }

        return $prefix . $baseUsername;
    }
}

if (! function_exists('strip_tenant_login_prefix')) {
    function strip_tenant_login_prefix(?string $loginUsername, ?string $loginPrefix = null): string
    {
        $loginUsername = strtolower(trim((string) $loginUsername));
        $loginPrefix   = normalize_tenant_code_value($loginPrefix);

        if ($loginPrefix === '') {
            return $loginUsername;
        }

        $prefix = $loginPrefix . '_';

        if (strpos($loginUsername, $prefix) === 0) {
            return substr($loginUsername, strlen($prefix));
        }

        return $loginUsername;
    }
}

if (! function_exists('tenant_cache_key')) {
    function tenant_cache_key(string $prefix, ...$parts): string
    {
        $items = [$prefix];

        foreach ($parts as $part) {
            if (is_array($part)) {
                $items[] = md5(json_encode($part));
            } else {
                $items[] = (string) $part;
            }
        }

        return 'tenant_' . implode('_', array_map(static function ($v) {
            $v = trim((string) $v);
            $v = preg_replace('/[^A-Za-z0-9_-]+/', '_', $v);
            $v = preg_replace('/_+/', '_', (string) $v);
            return trim((string) $v, '_');
        }, $items));
    }
}

if (! function_exists('tenant_cache_delete')) {
    function tenant_cache_delete(string $prefix, ...$parts): void
    {
        cache()->delete(tenant_cache_key($prefix, ...$parts));
    }
}

if (! function_exists('clear_tenant_access_cache')) {
    function clear_tenant_access_cache(?int $tenantId = null, ?int $planId = null): void
    {
        $tenantId = $tenantId ?: (function_exists('current_tenant_id') ? current_tenant_id() : null);

        if ($tenantId) {
            cache()->delete(tenant_cache_key('current_tenant', $tenantId));
            cache()->delete(tenant_cache_key('current_subscription', $tenantId));
        }

        if ($planId) {
            cache()->delete(tenant_cache_key('plan_features', $planId));
        }
    }
}