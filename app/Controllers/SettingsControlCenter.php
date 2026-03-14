<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Services\SettingsControlService;

class SettingsControlCenter extends BaseController
{
    protected SettingsControlService $service;
    protected AuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->service       = new SettingsControlService();
        $this->auditLogModel = new AuditLogModel();
    }

    public function index()
    {
        $isSuperAdmin = function_exists('is_super_admin') && is_super_admin();
        $scope        = (string) ($this->request->getGet('scope') ?: ($isSuperAdmin ? 'platform' : 'tenant'));
        $scope        = in_array($scope, ['platform', 'tenant', 'branch'], true) ? $scope : 'tenant';

        if ($guard = $this->denyInvalidScope($scope, $isSuperAdmin)) {
            return $guard;
        }

        $tenantId = $this->resolveTenantId($scope, $isSuperAdmin);
        $branchId = $this->resolveBranchId($scope, $isSuperAdmin);

        if ($scope === 'branch' && $branchId <= 0) {
            return redirect()->back()->with('error', lang('settings_control.settings_control_branch_required'));
        }

        if ($scope === 'tenant' && $tenantId <= 0) {
            return redirect()->back()->with('error', lang('settings_control.settings_control_tenant_required'));
        }

        $tenant = $tenantId > 0 ? $this->service->getTenantById($tenantId) : null;
        $branch = $branchId > 0 ? $this->service->getBranchById($branchId) : null;

        if (! $isSuperAdmin && $branchId > 0 && function_exists('can_access_branch') && ! can_access_branch($branchId)) {
            return redirect()->to(site_url('settings'))->with('error', lang('app.no_permission'));
        }

        if (! $isSuperAdmin && $tenantId > 0 && $tenantId !== $this->currentTenantIdOrFail()) {
            return redirect()->to(site_url('settings'))->with('error', lang('app.no_permission'));
        }

        $sections = $this->service->getPageData($scope, $tenantId, $branchId, $isSuperAdmin);

        $this->writeAudit([
            'branch_id'    => $branchId > 0 ? $branchId : null,
            'target_type'  => 'settings_control_center',
            'target_id'    => $scope === 'platform' ? 0 : ($scope === 'tenant' ? $tenantId : $branchId),
            'action_key'   => 'settings.control_center.view',
            'action_label' => lang('settings_control.settings_control_title'),
            'meta_json'    => [
                'scope'     => $scope,
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'screen'    => 'settings_control_center',
            ],
        ], 'settings.control_center.view.' . $scope . '.' . $tenantId . '.' . $branchId, 5);

        return view('settings/control_center', [
            'title'         => lang('settings_control.settings_control_title'),
            'scope'         => $scope,
            'tenantId'      => $tenantId,
            'branchId'      => $branchId,
            'tenant'        => $tenant,
            'branch'        => $branch,
            'sections'      => $sections,
            'tenantOptions' => $isSuperAdmin ? $this->service->getTenantOptions() : [],
            'branchOptions' => $isSuperAdmin
                ? $this->service->getBranchOptions($tenantId > 0 ? $tenantId : null)
                : $this->service->getBranchOptions($this->currentTenantIdOrFail()),
            'isSuperAdmin'  => $isSuperAdmin,
            'saveUrl'       => $isSuperAdmin ? site_url('super-admin/settings/control-center/save') : site_url('settings/control-center/save'),
            'baseUrl'       => $isSuperAdmin ? site_url('super-admin/settings/control-center') : site_url('settings/control-center'),
            'canEdit'       => $this->canEditScope($scope, $isSuperAdmin),
        ]);
    }

    public function save()
    {
        $isSuperAdmin = function_exists('is_super_admin') && is_super_admin();
        $scope        = (string) $this->request->getPost('scope');
        $sectionKey   = trim((string) $this->request->getPost('section_key'));
        $scope        = in_array($scope, ['platform', 'tenant', 'branch'], true) ? $scope : 'tenant';

        if ($guard = $this->denyInvalidScope($scope, $isSuperAdmin)) {
            return $guard;
        }

        if (! $this->canEditScope($scope, $isSuperAdmin)) {
            return redirect()->back()->with('error', lang('app.no_permission'));
        }

        if ($sectionKey === '') {
            return redirect()->back()->with('error', lang('app.invalid_request'));
        }

        $tenantId = $this->resolveTenantId($scope, $isSuperAdmin, true);
        $branchId = $this->resolveBranchId($scope, $isSuperAdmin, true);

        try {
            $postData = (array) $this->request->getPost();
            $result   = $this->service->saveSection($sectionKey, $scope, $tenantId, $branchId, $postData);
        } catch (\Throwable $e) {
            log_message('error', 'SettingsControlCenter save failed: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('error', lang('app.save_failed'));
        }

        if (! empty($result['changed'])) {
            $this->writeAudit([
                'branch_id'       => $branchId > 0 ? $branchId : null,
                'target_type'     => 'settings_control_center',
                'target_id'       => $scope === 'platform' ? 0 : ($scope === 'tenant' ? $tenantId : $branchId),
                'action_key'      => 'settings.control_center.update',
                'action_label'    => lang('settings_control.settings_control_title'),
                'meta_json'       => [
                    'scope'        => $scope,
                    'tenant_id'    => $tenantId,
                    'branch_id'    => $branchId,
                    'section_key'  => $sectionKey,
                    'changed_keys' => $result['changed'] ?? [],
                ],
                'old_values_json' => $result['old'] ?? [],
                'new_values_json' => $result['new'] ?? [],
            ]);
        }

        $redirectUrl = $isSuperAdmin ? site_url('super-admin/settings/control-center') : site_url('settings/control-center');
        $query       = ['scope' => $scope];

        if ($tenantId > 0) {
            $query['tenant_id'] = $tenantId;
        }

        if ($branchId > 0) {
            $query['branch_id'] = $branchId;
        }

        $flashType = ! empty($result['changed']) ? 'success' : 'info';
        $flashText = ! empty($result['changed'])
            ? lang('settings_control.settings_control_saved')
            : lang('settings_control.settings_control_no_changes');

        return redirect()->to($redirectUrl . '?' . http_build_query($query))
            ->with($flashType, $flashText);
    }

    protected function denyInvalidScope(string $scope, bool $isSuperAdmin)
    {
        if ($scope === 'platform' && ! $isSuperAdmin) {
            return redirect()->to(site_url('settings'))->with('error', lang('app.no_permission'));
        }

        if (! $this->canViewScope($scope, $isSuperAdmin)) {
            return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
        }

        return null;
    }

    protected function canViewScope(string $scope, bool $isSuperAdmin): bool
    {
        if ($isSuperAdmin) {
            if ($scope === 'platform') {
                return function_exists('can') && can('settings.platform.view');
            }

            if ($scope === 'branch') {
                return function_exists('can') && (
                    can('settings.control_center.view')
                    || can('settings.view')
                    || can('branches.settings')
                );
            }

            return function_exists('can') && (
                can('settings.control_center.view')
                || can('settings.view')
            );
        }

        if ($scope === 'branch') {
            return function_exists('can') && (
                can('branches.settings')
                || can('branches.edit')
                || can('settings.view')
                || can('settings.control_center.view')
            );
        }

        return function_exists('can') && (
            can('settings.view')
            || can('settings.control_center.view')
            || can('settings.edit')
        );
    }

    protected function canEditScope(string $scope, bool $isSuperAdmin): bool
    {
        if ($isSuperAdmin) {
            if ($scope === 'platform') {
                return function_exists('can') && can('settings.platform.edit');
            }

            if ($scope === 'branch') {
                return function_exists('can') && (
                    can('settings.control_center.edit')
                    || can('settings.edit')
                    || can('branches.edit')
                    || can('branches.settings')
                );
            }

            return function_exists('can') && (
                can('settings.control_center.edit')
                || can('settings.edit')
            );
        }

        if ($scope === 'branch') {
            return function_exists('can') && (
                can('branches.settings')
                || can('branches.edit')
                || can('settings.edit')
                || can('settings.control_center.edit')
            );
        }

        return function_exists('can') && (
            can('settings.edit')
            || can('settings.control_center.edit')
        );
    }

    protected function resolveTenantId(string $scope, bool $isSuperAdmin, bool $fromPost = false): int
    {
        $methodValue = $fromPost ? $this->request->getPost('tenant_id') : $this->request->getGet('tenant_id');

        if ($scope === 'platform') {
            return 0;
        }

        if ($scope === 'branch') {
            $branchId = $this->resolveBranchId($scope, $isSuperAdmin, $fromPost);
            $branch   = $branchId > 0 ? $this->service->getBranchById($branchId) : null;

            return (int) ($branch['tenant_id'] ?? 0);
        }

        if ($isSuperAdmin && (int) $methodValue > 0) {
            return (int) $methodValue;
        }

        return (int) (function_exists('current_tenant_id') ? current_tenant_id() : (session('tenant_id') ?? 0));
    }

    protected function resolveBranchId(string $scope, bool $isSuperAdmin, bool $fromPost = false): int
    {
        $methodValue = $fromPost ? $this->request->getPost('branch_id') : $this->request->getGet('branch_id');

        if ($scope !== 'branch') {
            return 0;
        }

        if ($isSuperAdmin && (int) $methodValue > 0) {
            return (int) $methodValue;
        }

        return (int) (function_exists('current_branch_id') ? current_branch_id() : (session('branch_id') ?? 0));
    }

    protected function writeAudit(array $payload, ?string $dedupeKey = null, int $dedupeSeconds = 0): void
    {
        try {
            if ($dedupeKey !== null && $dedupeSeconds > 0 && ! $this->shouldWriteAuditLog($dedupeKey, $dedupeSeconds)) {
                return;
            }

            $this->auditLogModel->add($payload);
        } catch (\Throwable $e) {
            log_message('error', 'SettingsControlCenter writeAudit failed: ' . $e->getMessage());
        }
    }

    protected function shouldWriteAuditLog(string $key, int $seconds = 5): bool
    {
        $sessionKey = '_audit_dedupe.' . md5($key);
        $lastAt     = (int) (session($sessionKey) ?? 0);
        $now        = time();

        if ($lastAt > 0 && ($now - $lastAt) < $seconds) {
            return false;
        }

        session()->set($sessionKey, $now);

        return true;
    }
}