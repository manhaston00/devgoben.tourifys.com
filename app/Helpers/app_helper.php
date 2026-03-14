<?php

if (!function_exists('app_setting')) {
    function app_setting(string $key, $default = null)
    {
        static $settings = [];

        if (empty($settings)) {
            $db = \Config\Database::connect();
            $table = $db->tableExists('app_settings') ? 'app_settings' : 'settings';

            $rows = $db->table($table)->get()->getResultArray();

            foreach ($rows as $row) {
                $value = $row['setting_value'] ?? null;

                if (is_string($value)) {
                    $trimmed = ltrim($value);

                    if ($trimmed !== '' && (strpos($trimmed, '{') === 0 || strpos($trimmed, '[') === 0)) {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $value = $decoded;
                        }
                    }
                }

                $settings[$row['setting_key']] = $value;
            }
        }

        return $settings[$key] ?? $default;
    }
}

if (!function_exists('lfield')) {
    function lfield(array $row, string $baseField, string $fallback = ''): string
    {
        $locale = service('request')->getLocale();
        $locale = in_array($locale, ['th', 'en'], true) ? $locale : 'th';

        $localeField = $baseField . '_' . $locale;
        $thField     = $baseField . '_th';
        $enField     = $baseField . '_en';

        if (!empty($row[$localeField]) && !is_array($row[$localeField])) {
            return (string) $row[$localeField];
        }

        if (!empty($row[$thField]) && !is_array($row[$thField])) {
            return (string) $row[$thField];
        }

        if (!empty($row[$enField]) && !is_array($row[$enField])) {
            return (string) $row[$enField];
        }

        if (isset($row[$baseField]) && $row[$baseField] !== '' && !is_array($row[$baseField])) {
            return (string) $row[$baseField];
        }

        return $fallback;
    }
}

if (! function_exists('normalize_setting_scalar')) {
    function normalize_setting_scalar($value)
    {
        if (is_array($value) || is_object($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        if ($trimmed === '1' || strtolower($trimmed) === 'true') {
            return true;
        }

        if ($trimmed === '0' || strtolower($trimmed) === 'false') {
            return false;
        }

        if (($trimmed[0] ?? '') === '{' || ($trimmed[0] ?? '') === '[') {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }
}

if (! function_exists('tenant_setting_map')) {
    function tenant_setting_map(?int $tenantId = null): array
    {
        static $cache = [];

        $tenantId = (int) ($tenantId ?: (function_exists('current_tenant_id') ? current_tenant_id() : (session('tenant_id') ?? 0)));
        if ($tenantId <= 0) {
            return [];
        }

        if (array_key_exists($tenantId, $cache)) {
            return $cache[$tenantId];
        }

        $db = \Config\Database::connect();
        if (! $db->tableExists('tenant_settings')) {
            return $cache[$tenantId] = [];
        }

        $rows = $db->table('tenant_settings')->where('tenant_id', $tenantId)->get()->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['setting_key']] = normalize_setting_scalar($row['setting_value'] ?? null);
        }

        return $cache[$tenantId] = $map;
    }
}

if (! function_exists('branch_setting_map')) {
    function branch_setting_map(?int $branchId = null): array
    {
        static $cache = [];

        $branchId = (int) ($branchId ?: (function_exists('current_branch_id') ? current_branch_id() : (session('branch_id') ?? 0)));
        if ($branchId <= 0) {
            return [];
        }

        if (array_key_exists($branchId, $cache)) {
            return $cache[$branchId];
        }

        $db = \Config\Database::connect();
        if (! $db->tableExists('branch_settings')) {
            return $cache[$branchId] = [];
        }

        $rows = $db->table('branch_settings')->where('branch_id', $branchId)->get()->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['setting_key']] = normalize_setting_scalar($row['setting_value'] ?? null);
        }

        return $cache[$branchId] = $map;
    }
}

if (! function_exists('effective_setting')) {
    function effective_setting(string $key, $default = null, ?int $tenantId = null, ?int $branchId = null)
    {
        $branchMap = branch_setting_map($branchId);
        if (array_key_exists($key, $branchMap)) {
            return $branchMap[$key];
        }

        $tenantMap = tenant_setting_map($tenantId);
        if (array_key_exists($key, $tenantMap)) {
            return $tenantMap[$key];
        }

        return normalize_setting_scalar(app_setting($key, $default));
    }
}

if (! function_exists('setting_bool')) {
    function setting_bool(string $key, bool $default = false, ?int $tenantId = null, ?int $branchId = null): bool
    {
        $value = effective_setting($key, $default ? '1' : '0', $tenantId, $branchId);

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}


if (! function_exists('runtime_setting_plan_feature_key')) {
    function runtime_setting_plan_feature_key(?string $settingKey): string
    {
        $settingKey = strtolower(trim((string) $settingKey));

        $map = [
            'feature.pos.enabled'                  => 'pos.access',
            'feature.kds.enabled'                  => 'pos.access',
            'feature.cashier.enabled'              => 'pos.access',
            'feature.reservations.enabled'         => 'reservations.manage',
            'feature.quick_notes.enabled'          => 'pos.access',
            'feature.product_quick_options.enabled'=> 'pos.access',
            'feature.tv_mode.enabled'              => 'pos.access',
            'feature.served_history.enabled'       => 'pos.access',
            'feature.cancel_request_flow.enabled'  => 'pos.access',
            'feature.split_bill.enabled'           => 'pos.sell',
            'feature.merge_bill.enabled'           => 'pos.sell',
            'feature.move_table.enabled'           => 'pos.sell',
            'feature.branch_switching.enabled'     => 'multi.branch',
        ];

        return $map[$settingKey] ?? '';
    }
}

if (! function_exists('runtime_feature_key_for_module')) {
    function runtime_feature_key_for_module(?string $module): string
    {
        $module = strtolower(trim((string) $module));

        $map = [
            'pos' => 'feature.pos.enabled',
            'cashier' => 'feature.cashier.enabled',
            'kds' => 'feature.kds.enabled',
            'kitchen' => 'feature.kds.enabled',
            'kitchen-monitor' => 'feature.kds.enabled',
            'reservations' => 'feature.reservations.enabled',
            'quick_notes' => 'feature.quick_notes.enabled',
            'quick-notes' => 'feature.quick_notes.enabled',
            'product_quick_options' => 'feature.product_quick_options.enabled',
            'product-quick-options' => 'feature.product_quick_options.enabled',
            'audit_logs' => 'feature.audit_logs.enabled',
            'audit-logs' => 'feature.audit_logs.enabled',
            'qr_menu' => 'feature.qr_menu.enabled',
            'qr-menu' => 'feature.qr_menu.enabled',
            'tv_mode' => 'feature.tv_mode.enabled',
        ];

        return $map[$module] ?? '';
    }
}

if (! function_exists('runtime_menu_key_for_module')) {
    function runtime_menu_key_for_module(?string $module): string
    {
        $module = strtolower(trim((string) $module));

        $map = [
            'pos' => 'menu.pos.visible',
            'cashier' => 'menu.cashier.visible',
            'kds' => 'menu.kds.visible',
            'kitchen' => 'menu.kds.visible',
            'kitchen-monitor' => 'menu.kds.visible',
            'reservations' => 'menu.reservations.visible',
            'quick_notes' => 'menu.quick_notes.visible',
            'quick-notes' => 'menu.quick_notes.visible',
            'product_quick_options' => 'menu.product_quick_options.visible',
            'product-quick-options' => 'menu.product_quick_options.visible',
            'audit_logs' => 'menu.audit_logs.visible',
            'audit-logs' => 'menu.audit_logs.visible',
            'branch_settings' => 'menu.branch_settings.visible',
            'settings' => 'menu.branch_settings.visible',
        ];

        return $map[$module] ?? '';
    }
}

if (! function_exists('module_runtime_enabled')) {
    function module_runtime_enabled(string $module, bool $default = true): bool
    {
        $featureKey = runtime_feature_key_for_module($module);

        if ($featureKey === '') {
            return $default;
        }

        return setting_bool($featureKey, $default);
    }
}

if (! function_exists('module_menu_visible')) {
    function module_menu_visible(string $module, bool $default = true): bool
    {
        $menuKey = runtime_menu_key_for_module($module);
        $menuVisible = $menuKey === '' ? $default : setting_bool($menuKey, $default);

        if (! $menuVisible) {
            return false;
        }

        return module_runtime_enabled($module, $default);
    }
}

if (! function_exists('runtime_feature_key_from_request')) {
    function runtime_feature_key_from_request(?string $path, ?string $planFeatureKey = null): string
    {
        $path = strtolower(trim((string) $path), '/');
        $planFeatureKey = strtolower(trim((string) $planFeatureKey));

        if (strpos($path, 'pos/cashier') === 0) {
            return 'feature.cashier.enabled';
        }

        if (strpos($path, 'kitchen-monitor') === 0) {
            return 'feature.kds.enabled';
        }

        if (strpos($path, 'reservations') === 0) {
            return 'feature.reservations.enabled';
        }

        if (strpos($path, 'quick-notes') === 0) {
            return 'feature.quick_notes.enabled';
        }

        if (strpos($path, 'product-quick-options') === 0 || strpos($path, 'quick-options') === 0) {
            return 'feature.product_quick_options.enabled';
        }

        if (strpos($path, 'audit-logs') === 0) {
            return 'feature.audit_logs.enabled';
        }

        if (strpos($path, 'pos') === 0) {
            return 'feature.pos.enabled';
        }

        $fallbackMap = [
            'pos.access' => 'feature.pos.enabled',
            'pos.sell' => 'feature.pos.enabled',
            'reservations.manage' => 'feature.reservations.enabled',
            'reports.basic' => 'feature.audit_logs.enabled',
        ];

        return $fallbackMap[$planFeatureKey] ?? '';
    }
}
