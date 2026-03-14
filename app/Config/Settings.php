<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\BranchModel;

class Settings extends BaseController
{
    protected $auditLogModel;
    protected $branchModel;

    public function __construct()
    {
        $this->auditLogModel = new AuditLogModel();
        $this->branchModel   = new BranchModel();
    }

    protected function writeAuditLog(array $payload): void
    {
        try {
            $this->auditLogModel->add($payload);
        } catch (\Throwable $e) {
            log_message('error', 'Settings writeAuditLog error: ' . $e->getMessage());
        }
    }

    protected function hasAnyPermission(array $permissionKeys): bool
    {
        if (! function_exists('can')) {
            return false;
        }

        foreach ($permissionKeys as $permissionKey) {
            if ($permissionKey !== '' && can($permissionKey)) {
                return true;
            }
        }

        return false;
    }

    protected function denyIfNoSettingsAccess()
    {
        if ($this->hasAnyPermission([
            'settings.view',
            'branches.settings',
            'branches.edit',
            'branches.view',
            'users.view',
            'roles.view',
        ])) {
            return null;
        }

        return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
    }

    public function index()
    {
        if ($redirect = $this->denyIfNoSettingsAccess()) {
            return $redirect;
        }

        $currentBranchId = (int) (session('branch_id') ?? 0);

        $this->writeAuditLog([
            'branch_id'    => $currentBranchId,
            'target_type'  => 'settings',
            'target_id'    => $currentBranchId,
            'action_key'   => 'settings.index',
            'action_label' => lang('app.settings'),
            'meta_json'    => [
                'screen' => 'settings/index',
            ],
        ]);

        return view('settings/index', [
            'title'              => lang('app.settings'),
            'currentBranchId'    => $currentBranchId,
            'canBranchSettings'  => $this->hasAnyPermission(['branches.settings', 'branches.edit', 'branches.view']),
            'canBranches'        => $this->hasAnyPermission(['branches.view', 'branches.settings']),
            'canUsers'           => $this->hasAnyPermission(['users.view']),
            'canRoles'           => $this->hasAnyPermission(['roles.view']),
            'canAuditLogs'       => $this->hasAnyPermission(['audit_logs.view']),
        ]);
    }

    public function branch()
    {
        if (! $this->hasAnyPermission(['branches.settings', 'branches.edit', 'branches.view'])) {
            return redirect()->to(site_url('settings'))->with('error', lang('app.no_permission'));
        }

        $branchId = (int) (session('branch_id') ?? 0);
        $tenantId = function_exists('current_tenant_id') ? current_tenant_id() : (session('tenant_id') ? (int) session('tenant_id') : null);

        if ($branchId <= 0 || ! $tenantId) {
            return redirect()->to(site_url('settings'))->with('error', lang('app.branch_not_found'));
        }

        $branch = $this->branchModel
            ->where('id', $branchId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->first();

        if (! $branch) {
            return redirect()->to(site_url('settings'))->with('error', lang('app.branch_not_found'));
        }

        $this->writeAuditLog([
            'branch_id'    => $branchId,
            'target_type'  => 'branch',
            'target_id'    => $branchId,
            'action_key'   => 'settings.branch',
            'action_label' => lang('app.branch_settings'),
            'meta_json'    => [
                'screen'       => 'settings/branch',
                'redirect_to'  => 'branches/edit/' . $branchId,
                'branch_code'  => (string) ($branch['branch_code'] ?? ''),
            ],
        ]);

        if ($this->hasAnyPermission(['branches.edit', 'branches.settings'])) {
            return redirect()->to(site_url('branches/edit/' . $branchId));
        }

        return redirect()->to(site_url('branches'));
    }
}
