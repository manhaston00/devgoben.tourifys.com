<?php

namespace App\Filters;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class AuthSubscriptionFilter implements FilterInterface
{
    protected function db(): BaseConnection
    {
        return Database::connect();
    }

    protected function fail(string $message)
    {
        session()->destroy();

        return redirect()
            ->to(site_url('login'))
            ->with('error', $message);
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        helper('tenant');

        if (! session('isLoggedIn')) {
            return redirect()
                ->to(site_url('login'))
                ->with('error', lang('app.please_login'));
        }

        if (function_exists('is_super_admin') && is_super_admin()) {
            return null;
        }

        $tenantId = (int) (session('tenant_id') ?? 0);
        $branchId = (int) (session('branch_id') ?: session('user_branch_id') ?: 0);

        if ($tenantId <= 0) {
            return $this->fail(lang('app.please_login'));
        }

        $db = $this->db();

        $tenant = $db->table('tenants')
            ->select('id, status, deleted_at')
            ->where('id', $tenantId)
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        if (! $tenant) {
            return $this->fail(lang('app.tenant_inactive'));
        }

        $tenantStatus = strtolower((string) ($tenant['status'] ?? ''));

        if (! in_array($tenantStatus, ['active', 'demo'], true)) {
            return redirect()
                ->to(site_url('/'))
                ->with('error', lang('app.tenant_inactive'));
        }

        if ($branchId > 0) {
            $branch = $db->table('branches')
                ->select('id, tenant_id, status, deleted_at')
                ->where('id', $branchId)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (! $branch) {
                return $this->fail(lang('app.branch_not_found'));
            }

            if ((int) ($branch['tenant_id'] ?? 0) !== $tenantId) {
                return $this->fail(lang('app.no_permission'));
            }

            if ((int) ($branch['status'] ?? 0) !== 1) {
                return $this->fail(lang('app.user_branch_disabled'));
            }
        }

        if (! function_exists('tenant_active') || ! tenant_active()) {
            return redirect()
                ->to(site_url('/'))
                ->with('error', lang('app.tenant_inactive'));
        }

        if (! function_exists('subscription_active') || ! subscription_active()) {
            return redirect()
                ->to(site_url('subscription/expired'))
                ->with('error', lang('app.subscription_expired'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}