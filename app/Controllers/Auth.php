<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RolePermissionModel;
use App\Models\SubscriptionModel;
use App\Models\TenantModel;
use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('isLoggedIn')) {
            if (function_exists('is_super_admin') && is_super_admin()) {
                return redirect()->to('/super-admin');
            }

            return redirect()->to('/dashboard');
        }

        if ($this->request->getMethod() === 'post') {
            $username = strtolower(trim((string) $this->request->getPost('username')));
            $password = (string) $this->request->getPost('password');

            if ($username === '' || $password === '') {
                return redirect()->back()->withInput()->with('error', lang('app.username_password_required'));
            }

            $userModel = new UserModel();
            $user = $userModel->findActiveLoginUser($username);

            if (! $user || ! password_verify($password, $user['password_hash'])) {
                return redirect()->back()->withInput()->with('error', lang('app.invalid_username_or_password'));
            }

            $roleName = strtolower((string) ($user['role_name'] ?? ''));

            $rolePermissionModel = new RolePermissionModel();
            $permissionKeys = $rolePermissionModel->getPermissionKeysByRoleId((int) $user['role_id']);

            if ($roleName === 'super_admin') {
                session()->set([
                    'user_id'        => (int) $user['id'],
                    'role_id'        => (int) $user['role_id'],
                    'role_name'      => (string) ($user['role_name'] ?? ''),
                    'role_code'      => 'super_admin',
                    'is_super_admin' => 1,
                    'full_name'      => (string) ($user['full_name'] ?? ''),
                    'username'       => (string) ($user['username'] ?? ''),
                    'permissions'    => $permissionKeys,
                    'isLoggedIn'     => true,
                    'tenant_id'      => null,
                    'tenant_name'    => 'Platform',
                    'user_branch_id' => null,
                    'branch_id'      => null,
                    'branch_code'    => '',
                    'branch_name'    => 'Platform',
                    'branch_name_th' => 'ระบบส่วนกลาง',
                    'branch_name_en' => 'Platform',
                    'site_locale'    => 'th',
                ]);

                $userModel->update((int) $user['id'], [
                    'last_login_at' => date('Y-m-d H:i:s'),
                ]);

                return redirect()->to('/super-admin')->with('success', lang('app.login_success'));
            }

            $tenantId = ! empty($user['tenant_id'])
                ? (int) $user['tenant_id']
                : (! empty($user['branch_tenant_id']) ? (int) $user['branch_tenant_id'] : null);

            if (! $tenantId) {
                return redirect()->back()->withInput()->with('error', lang('app.user_not_linked_tenant'));
            }

            if (! empty($user['branch_id']) && (int) ($user['branch_status'] ?? 0) !== 1) {
                return redirect()->back()->withInput()->with('error', lang('app.user_branch_disabled'));
            }

            $tenant = (new TenantModel())->find($tenantId);
            if (! $tenant || ! in_array((string) ($tenant['status'] ?? ''), ['active', 'demo'], true)) {
                return redirect()->back()->withInput()->with('error', lang('app.tenant_inactive'));
            }

            $subscription = (new SubscriptionModel())->getActiveByTenant($tenantId);
            if (! $subscription) {
                return redirect()->back()->withInput()->with('error', lang('app.subscription_expired'));
            }

            $userModel->update((int) $user['id'], [
                'last_login_at' => date('Y-m-d H:i:s'),
                'tenant_id'     => $tenantId,
            ]);

            $userBranchId = ! empty($user['branch_id']) ? (int) $user['branch_id'] : null;
            $branchId     = $userBranchId;
            $branchCode   = (string) ($user['branch_code'] ?? '');
            $branchNameTh = trim((string) ($user['branch_name_th'] ?? ''));
            $branchNameEn = trim((string) ($user['branch_name_en'] ?? ''));

            $siteLocale = (string) (
                $user['default_locale']
                ?? session('site_locale')
                ?? 'th'
            );

            $branchName = $siteLocale === 'en'
                ? ($branchNameEn !== '' ? $branchNameEn : ($branchNameTh !== '' ? $branchNameTh : '-'))
                : ($branchNameTh !== '' ? $branchNameTh : ($branchNameEn !== '' ? $branchNameEn : '-'));

            session()->set([
                'user_id'        => (int) $user['id'],
                'role_id'        => (int) $user['role_id'],
                'role_name'      => (string) ($user['role_name'] ?? ''),
                'role_code'      => strtolower((string) ($user['role_name'] ?? '')),
                'is_super_admin' => 0,
                'full_name'      => (string) ($user['full_name'] ?? ''),
                'username'       => (string) ($user['username'] ?? ''),
                'permissions'    => $permissionKeys,
                'isLoggedIn'     => true,
                'tenant_id'      => $tenantId,
                'tenant_name'    => trim((string) ($tenant['tenant_name'] ?? '')),
                'user_branch_id' => $userBranchId,
                'branch_id'      => $branchId ?: null,
                'branch_code'    => $branchCode,
                'branch_name'    => $branchName,
                'branch_name_th' => $branchNameTh,
                'branch_name_en' => $branchNameEn,
                'site_locale'    => $siteLocale,
            ]);

            return redirect()->to('/dashboard')->with('success', lang('app.login_success'));
        }

        return view('auth/login');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login')->with('success', lang('app.logout_success'));
    }
}