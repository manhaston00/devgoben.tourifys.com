<?php

namespace App\Services;

use App\Models\AppSettingModel;
use App\Models\BranchModel;
use App\Models\BranchSettingModel;
use App\Models\SubscriptionModel;
use App\Models\SubscriptionPlanFeatureModel;
use App\Models\TenantModel;
use App\Models\TenantSettingModel;

class SettingsControlService
{
    protected AppSettingModel $appSettingModel;
    protected TenantSettingModel $tenantSettingModel;
    protected BranchSettingModel $branchSettingModel;
    protected BranchModel $branchModel;
    protected TenantModel $tenantModel;
    protected SubscriptionModel $subscriptionModel;
    protected SubscriptionPlanFeatureModel $subscriptionPlanFeatureModel;

    public function __construct()
    {
        helper(['tenant', 'branch', 'super_admin']);

        $this->appSettingModel              = new AppSettingModel();
        $this->tenantSettingModel           = new TenantSettingModel();
        $this->branchSettingModel           = new BranchSettingModel();
        $this->branchModel                  = new BranchModel();
        $this->tenantModel                  = new TenantModel();
        $this->subscriptionModel            = new SubscriptionModel();
        $this->subscriptionPlanFeatureModel = new SubscriptionPlanFeatureModel();
    }

    public function registry(): array
    {
        return [
            'features' => [
                'title' => lang('settings_control.settings_control_section_features'),
                'settings' => [
                    $this->boolSetting('feature.pos.enabled', 'settings_control_label_feature_pos', 'tenant', 'pos.access'),
                    $this->boolSetting('feature.kds.enabled', 'settings_control_label_feature_kds', 'tenant', 'pos.access'),
                    $this->boolSetting('feature.cashier.enabled', 'settings_control_label_feature_cashier', 'tenant', 'pos.access'),
                    $this->boolSetting('feature.reservations.enabled', 'settings_control_label_feature_reservations', 'tenant', 'reservations.manage'),
                    $this->boolSetting('feature.qr_menu.enabled', 'settings_control_label_feature_qr_menu', 'tenant'),
                    $this->boolSetting('feature.quick_notes.enabled', 'settings_control_label_feature_quick_notes', 'tenant', 'pos.access'),
                    $this->boolSetting('feature.product_quick_options.enabled', 'settings_control_label_feature_product_quick_options', 'tenant', 'pos.access'),
                    $this->boolSetting('feature.tv_mode.enabled', 'settings_control_label_feature_tv_mode', 'branch', 'pos.access'),
                    $this->boolSetting('feature.served_history.enabled', 'settings_control_label_feature_served_history', 'branch', 'pos.access'),
                    $this->boolSetting('feature.cancel_request_flow.enabled', 'settings_control_label_feature_cancel_request_flow', 'branch', 'pos.access'),
                    $this->boolSetting('feature.discount.enabled', 'settings_control_label_feature_discount', 'tenant'),
                    $this->boolSetting('feature.service_charge.enabled', 'settings_control_label_feature_service_charge', 'tenant'),
                    $this->boolSetting('feature.vat.enabled', 'settings_control_label_feature_vat', 'tenant'),
                    $this->boolSetting('feature.split_bill.enabled', 'settings_control_label_feature_split_bill', 'branch', 'pos.sell'),
                    $this->boolSetting('feature.merge_bill.enabled', 'settings_control_label_feature_merge_bill', 'branch', 'pos.sell'),
                    $this->boolSetting('feature.move_table.enabled', 'settings_control_label_feature_move_table', 'branch', 'pos.sell'),
                    $this->boolSetting('feature.manager_override.enabled', 'settings_control_label_feature_manager_override', 'tenant', 'pos.access'),
                    $this->boolSetting('feature.receipt_print.enabled', 'settings_control_label_feature_receipt_print', 'branch'),
                    $this->boolSetting('feature.kitchen_ticket_print.enabled', 'settings_control_label_feature_kitchen_ticket_print', 'branch'),
                    $this->boolSetting('feature.print_queue.enabled', 'settings_control_label_feature_print_queue', 'tenant'),
                    $this->boolSetting('feature.audit_logs.enabled', 'settings_control_label_feature_audit_logs', 'tenant'),
                    $this->boolSetting('feature.branch_switching.enabled', 'settings_control_label_feature_branch_switching', 'tenant', 'multi.branch'),
                    $this->boolSetting('feature.multilanguage_switch.enabled', 'settings_control_label_feature_multilanguage_switch', 'tenant'),
                    $this->boolSetting('feature.reopen_bill.enabled', 'settings_control_label_feature_reopen_bill', 'branch'),
                    $this->boolSetting('feature.refund_void.enabled', 'settings_control_label_feature_refund_void', 'branch'),
                    $this->boolSetting('feature.payment_lock.enabled', 'settings_control_label_feature_payment_lock', 'branch'),
                ],
            ],
            'menu' => [
                'title' => lang('settings_control.settings_control_section_menu'),
                'settings' => [
                    $this->boolSetting('menu.pos.visible', 'settings_control_label_menu_pos', 'tenant', 'pos.access'),
                    $this->boolSetting('menu.kds.visible', 'settings_control_label_menu_kds', 'tenant', 'pos.access'),
                    $this->boolSetting('menu.cashier.visible', 'settings_control_label_menu_cashier', 'tenant', 'pos.access'),
                    $this->boolSetting('menu.reservations.visible', 'settings_control_label_menu_reservations', 'tenant', 'reservations.manage'),
                    $this->boolSetting('menu.quick_notes.visible', 'settings_control_label_menu_quick_notes', 'tenant', 'pos.access'),
                    $this->boolSetting('menu.product_quick_options.visible', 'settings_control_label_menu_product_quick_options', 'tenant', 'pos.access'),
                    $this->boolSetting('menu.audit_logs.visible', 'settings_control_label_menu_audit_logs', 'tenant'),
                    $this->boolSetting('menu.branch_settings.visible', 'settings_control_label_menu_branch_settings', 'tenant', 'multi.branch'),
                ],
            ],
            'media' => [
                'title' => lang('settings_control.settings_control_section_media'),
                'settings' => [
                    $this->boolSetting('media.pos.product_images.show', 'settings_control_label_media_pos_product_images', 'branch', 'pos.access'),
                    $this->boolSetting('media.cashier.product_images.show', 'settings_control_label_media_cashier_product_images', 'branch', 'pos.access'),
                    $this->boolSetting('media.qr_menu.product_images.show', 'settings_control_label_media_qr_menu_product_images', 'branch'),
                    $this->boolSetting('media.kds.product_images.show', 'settings_control_label_media_kds_product_images', 'branch', 'pos.access'),
                    $this->boolSetting('media.category_images.show', 'settings_control_label_media_category_images', 'tenant'),
                    $this->boolSetting('media.placeholder_image.enabled', 'settings_control_label_media_placeholder_image', 'tenant'),
                    $this->boolSetting('media.lazyload.enabled', 'settings_control_label_media_lazyload', 'tenant'),
                    $this->boolSetting('media.store_logo.show', 'settings_control_label_media_store_logo', 'tenant'),
                    $this->boolSetting('media.branch_image.show', 'settings_control_label_media_branch_image', 'branch'),
                ],
            ],
            'billing' => [
                'title' => lang('settings_control.settings_control_section_billing'),
                'settings' => [
                    $this->boolSetting('billing.vat_enabled', 'settings_control_label_billing_vat_enabled', 'tenant'),
                    $this->numberSetting('billing.vat_rate', 'settings_control_label_billing_vat_rate', 'tenant', 7),
                    $this->selectSetting('billing.vat_mode', 'settings_control_label_billing_vat_mode', 'tenant', 'included', [
                        'included' => lang('settings_control.settings_control_options_included'),
                        'excluded' => lang('settings_control.settings_control_options_excluded'),
                    ]),
                    $this->boolSetting('billing.service_charge_enabled', 'settings_control_label_billing_service_charge_enabled', 'tenant'),
                    $this->numberSetting('billing.service_charge_rate', 'settings_control_label_billing_service_charge_rate', 'tenant', 10),
                    $this->selectSetting('billing.service_charge_mode', 'settings_control_label_billing_service_charge_mode', 'tenant', 'before_vat', [
                        'before_vat' => lang('settings_control.settings_control_options_before_vat'),
                        'after_vat'  => lang('settings_control.settings_control_options_after_vat'),
                    ]),
                    $this->boolSetting('billing.service_charge_apply_before_vat', 'settings_control_label_billing_service_charge_apply_before_vat', 'tenant', null, true),
                    $this->boolSetting('billing.rounding_enabled', 'settings_control_label_billing_rounding_enabled', 'tenant'),
                    $this->selectSetting('billing.rounding_mode', 'settings_control_label_billing_rounding_mode', 'tenant', 'half_up', [
                        'half_up'     => lang('settings_control.settings_control_options_half_up'),
                        'floor'       => lang('settings_control.settings_control_options_floor'),
                        'ceil'        => lang('settings_control.settings_control_options_ceil'),
                        'nearest_025' => lang('settings_control.settings_control_options_nearest_025'),
                        'nearest_050' => lang('settings_control.settings_control_options_nearest_050'),
                    ]),
                    $this->boolSetting('billing.show_tax_breakdown', 'settings_control_label_billing_show_tax_breakdown', 'tenant'),
                    $this->boolSetting('billing.prices_include_tax', 'settings_control_label_billing_prices_include_tax', 'tenant'),
                ],
            ],
            'payments' => [
                'title' => lang('settings_control.settings_control_section_payments'),
                'settings' => [
                    $this->boolSetting('payment.cash.enabled', 'settings_control_label_payment_cash_enabled', 'branch'),
                    $this->boolSetting('payment.transfer.enabled', 'settings_control_label_payment_transfer_enabled', 'branch'),
                    $this->boolSetting('payment.card.enabled', 'settings_control_label_payment_card_enabled', 'branch'),
                    $this->boolSetting('payment.qr.enabled', 'settings_control_label_payment_qr_enabled', 'branch'),
                    $this->boolSetting('payment.require_manager_override_for_manual_discount', 'settings_control_label_payment_require_manager_override_for_manual_discount', 'tenant'),
                    $this->boolSetting('payment.require_manager_override_for_void', 'settings_control_label_payment_require_manager_override_for_void', 'tenant'),
                    $this->boolSetting('payment.payment_lock_enabled', 'settings_control_label_payment_payment_lock_enabled', 'branch'),
                ],
            ],
            'printing' => [
                'title' => lang('settings_control.settings_control_section_printing'),
                'settings' => [
                    $this->boolSetting('printing.receipt_print.enabled', 'settings_control_label_printing_receipt_print', 'branch'),
                    $this->boolSetting('printing.kitchen_ticket_print.enabled', 'settings_control_label_printing_kitchen_ticket_print', 'branch'),
                    $this->boolSetting('printing.auto_print_kitchen.enabled', 'settings_control_label_printing_auto_print_kitchen', 'branch'),
                    $this->boolSetting('printing.auto_print_receipt_after_payment.enabled', 'settings_control_label_printing_auto_print_receipt_after_payment', 'branch'),
                    $this->boolSetting('printing.thermal_printer.enabled', 'settings_control_label_printing_thermal_printer', 'branch'),
                    $this->boolSetting('printing.print_queue.enabled', 'settings_control_label_printing_print_queue', 'tenant'),
                ],
            ],
        ];
    }

    public function getPageData(string $scope, ?int $tenantId, ?int $branchId, bool $isSuperAdmin = false): array
    {
        $registry       = $this->registry();
        $appMap         = $this->appSettingModel->getMap();
        $tenantMap      = $tenantId > 0 ? $this->tenantSettingModel->getMap((int) $tenantId) : [];
        $branchMap      = $branchId > 0 ? $this->branchSettingModel->getMap((int) $branchId) : [];
        $planFeatureMap = $this->getPlanFeatureMap($tenantId, $isSuperAdmin);

        foreach ($registry as $sectionKey => &$section) {
            foreach ($section['settings'] as &$setting) {
                $resolved                  = $this->resolveSetting($setting, $appMap, $tenantMap, $branchMap);
                $setting['effective_value'] = $resolved['value'];
                $setting['value_source']    = $resolved['source'];
                $setting['plan_allowed']     = $this->isPlanAllowed($setting, $planFeatureMap, $isSuperAdmin);
                $setting['is_visible']       = $this->isSettingVisibleForScope($setting, $scope);
                $setting['editable_in_scope'] = $this->canPersistSettingForScope($setting, $scope, $tenantId, $branchId);
                $setting['input_name']       = $this->inputName((string) ($setting['key'] ?? ''));
            }
            unset($setting);

            $section['settings'] = array_values(array_filter(
                $section['settings'],
                static fn (array $row): bool => ! empty($row['is_visible'])
            ));
        }
        unset($section);

        return $registry;
    }

    public function saveSection(string $sectionKey, string $scope, ?int $tenantId, ?int $branchId, array $input): array
    {
        $registry = $this->registry();

        if (! isset($registry[$sectionKey])) {
            return ['changed' => [], 'old' => [], 'new' => []];
        }

        $appMap         = $this->appSettingModel->getMap();
        $tenantMap      = $tenantId > 0 ? $this->tenantSettingModel->getMap((int) $tenantId) : [];
        $branchMap      = $branchId > 0 ? $this->branchSettingModel->getMap((int) $branchId) : [];
        $planFeatureMap = $this->getPlanFeatureMap($tenantId, function_exists('is_super_admin') && is_super_admin());

        $changed = [];
        $old     = [];
        $new     = [];

        foreach ($registry[$sectionKey]['settings'] as $setting) {
            if (! $this->isSettingVisibleForScope($setting, $scope)) {
                continue;
            }

            if (! $this->canPersistSettingForScope($setting, $scope, $tenantId, $branchId)) {
                continue;
            }

            if (! $this->isPlanAllowed($setting, $planFeatureMap, function_exists('is_super_admin') && is_super_admin())) {
                continue;
            }

            [$present, $rawValue] = $this->extractPostedValue($setting, $input);

            if (! $present) {
                continue;
            }

            $key      = (string) ($setting['key'] ?? '');
            $posted   = $this->normalizePostedValue($setting, $rawValue);
            $resolved = $this->resolveSetting($setting, $appMap, $tenantMap, $branchMap);
            $oldValue = $resolved['value'];

            if ($this->valuesEqual($oldValue, $posted)) {
                continue;
            }

            $saved = false;

            if ($scope === 'platform') {
                $this->appSettingModel->setValue($key, $posted, (string) $sectionKey);
                $appMap[$key] = $posted;
                $saved = true;
            } elseif ($scope === 'tenant') {
                $this->tenantSettingModel->setValue((int) $tenantId, $key, $posted);
                $tenantMap[$key] = $posted;
                $saved = true;
            } elseif ($scope === 'branch') {
                $this->branchSettingModel->setValue((int) $branchId, $key, $posted);
                $branchMap[$key] = $posted;
                $saved = true;
            }

            if (! $saved) {
                continue;
            }

            $changed[] = $key;
            $old[$key] = $oldValue;
            $new[$key] = $posted;
        }

        return [
            'changed' => $changed,
            'old'     => $old,
            'new'     => $new,
        ];
    }

    public function getTenantOptions(): array
    {
        return $this->tenantModel->where('deleted_at', null)->orderBy('tenant_name', 'ASC')->findAll();
    }

    public function getTenantById(int $tenantId): ?array
    {
        if ($tenantId <= 0) {
            return null;
        }

        return $this->tenantModel->find($tenantId) ?: null;
    }

    public function getBranchOptions(?int $tenantId = null): array
    {
        $builder = $this->branchModel->where('deleted_at', null)->orderBy('branch_name', 'ASC');

        if ($tenantId !== null && $tenantId > 0) {
            $builder->where('tenant_id', $tenantId);
        }

        return $builder->findAll();
    }

    public function getBranchById(int $branchId): ?array
    {
        if ($branchId <= 0) {
            return null;
        }

        return $this->branchModel->find($branchId) ?: null;
    }

    protected function getPlanFeatureMap(?int $tenantId, bool $isSuperAdmin): array
    {
        if ($isSuperAdmin && (! $tenantId || $tenantId <= 0)) {
            return [];
        }

        $tenantId = $tenantId ?: (function_exists('current_tenant_id') ? current_tenant_id() : 0);
        if (! $tenantId) {
            return [];
        }

        $subscription = $this->subscriptionModel->getActiveByTenant((int) $tenantId);
        $planId       = (int) ($subscription['plan_id'] ?? 0);

        if ($planId <= 0) {
            return [];
        }

        return $this->subscriptionPlanFeatureModel->getPlanFeatures($planId);
    }

    protected function resolveSetting(array $setting, array $appMap, array $tenantMap, array $branchMap): array
    {
        $key = (string) $setting['key'];

        if (array_key_exists($key, $branchMap)) {
            return ['value' => $this->castValue($setting, $branchMap[$key]), 'source' => 'branch'];
        }

        if (array_key_exists($key, $tenantMap)) {
            return ['value' => $this->castValue($setting, $tenantMap[$key]), 'source' => 'tenant'];
        }

        if (array_key_exists($key, $appMap)) {
            return ['value' => $this->castValue($setting, $appMap[$key]), 'source' => 'platform'];
        }

        return ['value' => $this->castValue($setting, $setting['default'] ?? null), 'source' => 'default'];
    }

    protected function castValue(array $setting, $value)
    {
        $type = (string) ($setting['type'] ?? 'text');

        if ($type === 'boolean') {
            if (is_bool($value)) {
                return $value;
            }

            return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on', 'enabled'], true);
        }

        if ($type === 'number') {
            return is_numeric($value) ? (float) $value : (float) ($setting['default'] ?? 0);
        }

        return (string) $value;
    }


    protected function canPersistSettingForScope(array $setting, string $scope, ?int $tenantId, ?int $branchId): bool
    {
        if ($scope === 'platform') {
            return true;
        }

        $settingScope = (string) ($setting['scope'] ?? 'tenant');

        if ($scope === 'tenant') {
            return $settingScope === 'tenant' && (int) $tenantId > 0;
        }

        return $settingScope === 'branch' && (int) $branchId > 0;
    }

    protected function extractPostedValue(array $setting, array $input): array
    {
        $key       = (string) ($setting['key'] ?? '');
        $postedKey = $this->inputName($key);

        if (array_key_exists($postedKey, $input)) {
            return [true, $input[$postedKey]];
        }

        if (array_key_exists($key, $input)) {
            return [true, $input[$key]];
        }

        return [false, null];
    }

    protected function normalizePostedValue(array $setting, $value)
    {
        $type = (string) ($setting['type'] ?? 'text');

        if ($type === 'boolean') {
            return ! empty($value) && in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
        }

        if ($type === 'number') {
            return is_numeric($value) ? (string) $value : (string) ($setting['default'] ?? '0');
        }

        $allowed = $setting['options'] ?? [];
        $value   = trim((string) $value);

        if ($type === 'select' && ! array_key_exists($value, $allowed)) {
            return (string) ($setting['default'] ?? '');
        }

        return $value;
    }

    protected function isPlanAllowed(array $setting, array $planFeatureMap, bool $isSuperAdmin): bool
    {
        if ($isSuperAdmin) {
            return true;
        }

        $planFeatureKey = trim((string) ($setting['plan_feature_key'] ?? ''));
        if ($planFeatureKey === '') {
            return true;
        }

        $normalized = normalize_feature_key($planFeatureKey);

        if (! isset($planFeatureMap[$normalized])) {
            return false;
        }

        return (int) ($planFeatureMap[$normalized]['enabled'] ?? 0) === 1;
    }

    protected function isSettingVisibleForScope(array $setting, string $scope): bool
    {
        $settingScope = (string) ($setting['scope'] ?? 'tenant');

        if ($scope === 'platform') {
            return true;
        }

        if ($scope === 'tenant') {
            return $settingScope === 'tenant';
        }

        return $settingScope === 'branch';
    }

    protected function valuesEqual($left, $right): bool
    {
        if (is_bool($left) || is_bool($right)) {
            return (bool) $left === (bool) $right;
        }

        return (string) $left === (string) $right;
    }

    protected function inputName(string $key): string
    {
        return str_replace(['.', '[', ']'], '_', $key);
    }

    protected function boolSetting(string $key, string $labelKey, string $scope = 'tenant', ?string $planFeatureKey = null, bool $default = true): array
    {
        return [
            'key'              => $key,
            'type'             => 'boolean',
            'label'            => lang('settings_control.' . $labelKey),
            'scope'            => $scope,
            'default'          => $default,
            'plan_feature_key' => $planFeatureKey,
        ];
    }

    protected function numberSetting(string $key, string $labelKey, string $scope = 'tenant', $default = 0, ?string $planFeatureKey = null): array
    {
        return [
            'key'              => $key,
            'type'             => 'number',
            'label'            => lang('settings_control.' . $labelKey),
            'scope'            => $scope,
            'default'          => $default,
            'plan_feature_key' => $planFeatureKey,
        ];
    }

    protected function selectSetting(string $key, string $labelKey, string $scope = 'tenant', string $default = '', array $options = [], ?string $planFeatureKey = null): array
    {
        return [
            'key'              => $key,
            'type'             => 'select',
            'label'            => lang('settings_control.' . $labelKey),
            'scope'            => $scope,
            'default'          => $default,
            'options'          => $options,
            'plan_feature_key' => $planFeatureKey,
        ];
    }
}
