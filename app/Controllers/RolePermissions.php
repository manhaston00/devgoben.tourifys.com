<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Models\RolePermissionModel;

class RolePermissions extends BaseController
{
    protected array $platformModules = [
        'super_admin',
        'tenants',
        'plans',
        'subscriptions',
        'subscription_logs',
    ];

    protected function isPlatformRole(array $role): bool
    {
        $roleName   = strtolower(trim((string) ($role['role_name'] ?? '')));
        $roleNameEn = strtolower(trim((string) ($role['role_name_en'] ?? '')));

        return $roleName === 'super_admin' || $roleNameEn === 'super_admin';
    }

    protected function isAdminRole(array $role): bool
    {
        $roleName   = strtolower(trim((string) ($role['role_name'] ?? '')));
        $roleNameEn = strtolower(trim((string) ($role['role_name_en'] ?? '')));
        $roleNameTh = trim((string) ($role['role_name_th'] ?? ''));

        return in_array($roleName, ['admin', 'administrator'], true)
            || in_array($roleNameEn, ['admin', 'administrator'], true)
            || $roleNameTh === 'ผู้ดูแลระบบ';
    }

    protected function getAssignablePermissions(PermissionModel $permissionModel): array
    {
        $builder = $permissionModel->where('status', 1);

        if (!(function_exists('is_super_admin') && is_super_admin())) {
            $builder->whereNotIn('module_key', $this->platformModules);
        }

        return $builder
            ->orderBy('module_key', 'ASC')
            ->orderBy('action_key', 'ASC')
            ->findAll();
    }

    public function edit($roleId)
    {
        if (! can('roles.edit')) {
            return redirect()->to('/dashboard')
                ->with('error', lang('app.no_permission_manage_role_permissions'));
        }

        $roleId = (int) $roleId;

        $roleModel           = new RoleModel();
        $permissionModel     = new PermissionModel();
        $rolePermissionModel = new RolePermissionModel();

        $role = $roleModel->findVisibleRole($roleId);

        if (! $role) {
            return redirect()->to('/roles')
                ->with('error', lang('app.role_not_found'));
        }

        // tenant ห้ามแก้ role super_admin
        if ($this->isPlatformRole($role) && !(function_exists('is_super_admin') && is_super_admin())) {
            return redirect()->to('/roles')
                ->with('error', lang('app.no_permission'));
        }

        $assignablePermissions = $this->getAssignablePermissions($permissionModel);
        $assignablePermissionIds = array_map(
            static fn ($row) => (int) ($row['id'] ?? 0),
            $assignablePermissions
        );

        if (strtolower($this->request->getMethod()) === 'post') {
            $permissionIds = $this->request->getPost('permission_ids');

            if (! is_array($permissionIds)) {
                $permissionIds = [];
            }

            $permissionIds = array_map('intval', $permissionIds);
            $permissionIds = array_values(array_unique($permissionIds));

            // กรองไม่ให้ tenant ใส่ permission platform ผ่าน POST ตรง
            if (!(function_exists('is_super_admin') && is_super_admin())) {
                $permissionIds = array_values(array_intersect($permissionIds, $assignablePermissionIds));
            }

            if ($this->isAdminRole($role) && empty($permissionIds)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', lang('app.admin_must_have_at_least_one_permission'));
            }

            $db = \Config\Database::connect();
            $db->transStart();

            $rolePermissionModel
                ->where('role_id', $roleId)
                ->delete();

            foreach ($permissionIds as $permissionId) {
                $rolePermissionModel->insert([
                    'role_id'       => $roleId,
                    'permission_id' => (int) $permissionId,
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', lang('app.save_permissions_failed'));
            }

            return redirect()->to('/roles')
                ->with('success', lang('app.save_role_permissions_success'));
        }

        $groupedPermissions = [];
        foreach ($assignablePermissions as $permission) {
            $module = $permission['module_key'] ?? '';
            if ($module === '') {
                $module = 'general';
            }

            if (! isset($groupedPermissions[$module])) {
                $groupedPermissions[$module] = [];
            }

            $groupedPermissions[$module][] = $permission;
        }

        $currentPermissionIds = $rolePermissionModel
            ->where('role_id', $roleId)
            ->findColumn('permission_id');

        if (! $currentPermissionIds) {
            $currentPermissionIds = [];
        }

        // tenant จะเห็นเฉพาะ permission ที่ assign ได้จริง
        if (!(function_exists('is_super_admin') && is_super_admin())) {
            $currentPermissionIds = array_values(array_intersect(
                array_map('intval', $currentPermissionIds),
                $assignablePermissionIds
            ));
        } else {
            $currentPermissionIds = array_map('intval', $currentPermissionIds);
        }

        $data = [
            'title'                => lang('app.set_permissions') . ': ' . ($role['role_name'] ?? '-'),
            'role'                 => $role,
            'groupedPermissions'   => $groupedPermissions,
            'currentPermissionIds' => $currentPermissionIds,
        ];

        return view('roles/permissions', $data);
    }
}