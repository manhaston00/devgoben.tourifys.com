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

    protected function denyIfNoSettingsAccess()
    {
        $allowed = function_exists('can') && (
            can('settings.view')
            || can('settings.edit')
            || can('settings.control_center.view')
            || can('branches.settings')
            || can('branches.edit')
            || can('branches.view')
            || can('users.view')
            || can('roles.view')
            || can('audit_logs.view')
        );

        if (! $allowed) {
            return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
        }

        return null;
    }

    protected function writeAuditLog(array $payload, ?string $dedupeKey = null, int $dedupeSeconds = 0): void
    {
        try {
            if ($dedupeKey !== null && $dedupeSeconds > 0 && ! $this->shouldWriteAuditLog($dedupeKey, $dedupeSeconds)) {
                return;
            }

            $this->auditLogModel->add($payload);
        } catch (\Throwable $e) {
            log_message('error', 'Settings writeAuditLog error: ' . $e->getMessage());
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

    public function index()
    {
        if ($response = $this->denyIfNoSettingsAccess()) {
            return $response;
        }

        $currentBranchId = function_exists('current_branch_id')
            ? (int) current_branch_id()
            : (int) (session('branch_id') ?? 0);

        $this->writeAuditLog([
            'branch_id'    => $currentBranchId,
            'target_type'  => 'settings',
            'target_id'    => 0,
            'action_key'   => 'settings.view',
            'action_label' => lang('app.settings'),
            'meta_json'    => [
                'screen' => 'settings_index',
            ],
        ], 'settings.index.' . $currentBranchId, 5);

        return view('settings/index', [
            'title'              => lang('app.settings'),
            'canBranchSettings'  => function_exists('can') && (can('branches.settings') || can('branches.edit')),
            'canBranches'        => function_exists('can') && can('branches.view'),
            'canUsers'           => function_exists('can') && can('users.view'),
            'canRoles'           => function_exists('can') && can('roles.view'),
            'canAuditLogs'       => function_exists('can') && can('audit_logs.view'),
            'canControlCenter'   => function_exists('can') && (can('settings.view') || can('settings.edit') || can('settings.control_center.view') || can('branches.settings') || can('branches.edit')),
            'currentBranchId'    => $currentBranchId,
        ]);
    }

    public function branch()
    {
        $allowed = function_exists('can') && (can('branches.settings') || can('branches.edit'));

        if (! $allowed) {
            return redirect()->to(site_url('settings'))->with('error', lang('app.no_permission'));
        }

        $branchId = function_exists('current_branch_id')
            ? (int) current_branch_id()
            : (int) (session('branch_id') ?? 0);

        if ($branchId <= 0) {
            return redirect()->to(site_url('settings'))->with('error', lang('app.branch_not_found'));
        }

        $branch = $this->branchModel->find($branchId);

        if (! $branch) {
            return redirect()->to(site_url('settings'))->with('error', lang('app.branch_not_found'));
        }

        $tenantId = function_exists('current_tenant_id') ? current_tenant_id() : (session('tenant_id') ?? null);

        if ($tenantId !== null && isset($branch['tenant_id']) && (int) $branch['tenant_id'] !== (int) $tenantId) {
            return redirect()->to(site_url('settings'))->with('error', lang('app.no_permission'));
        }

        $this->writeAuditLog([
            'branch_id'    => $branchId,
            'target_type'  => 'branch',
            'target_id'    => $branchId,
            'action_key'   => 'branches.settings',
            'action_label' => lang('app.branch_settings'),
            'meta_json'    => [
                'screen'    => 'settings_branch_entry',
                'branch_id' => $branchId,
            ],
        ], 'settings.branch.' . $branchId, 5);

        return redirect()->to(site_url('branches/edit/' . $branchId));
    }

    public function switchLocale($locale = null)
    {
        $locale = in_array($locale, ['th', 'en'], true) ? $locale : 'th';

        session()->set('site_locale', $locale);

        return redirect()->back();
    }
}
