<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        /*
        |--------------------------------------------------------------------------
        | map สิทธิ์ตาม role
        |--------------------------------------------------------------------------
        | ใช้ permission_key จากตาราง permissions
        */
        $rolePermissions = [
            'admin' => [
                'dashboard.view',

                'roles.view',
                'roles.create',
                'roles.edit',
                'roles.delete',

                'users.view',
                'users.create',
                'users.edit',
                'users.delete',

                'settings.view',
                'settings.edit',

                'branches.view',
                'branches.create',
                'branches.edit',
                'branches.delete',
                'branches.switch',
                'branches.settings',

                'zones.view',
                'zones.create',
                'zones.edit',
                'zones.delete',

                'tables.view',
                'tables.create',
                'tables.edit',
                'tables.delete',
                'tables.change_status',

                'categories.view',
                'categories.create',
                'categories.edit',
                'categories.delete',

                'products.view',
                'products.create',
                'products.edit',
                'products.delete',

                'customers.view',
                'customers.create',
                'customers.edit',
                'customers.delete',

                'orders.view',
                'orders.create',
                'orders.edit',
                'orders.delete',
                'orders.payment',

                'kitchen.view',
                'kitchen.update_status',

                'reports.view',
                'reports.sales',
                'reports.stock',

                'reservations.view',
                'reservations.create',
                'reservations.edit',
                'reservations.delete',
                'reservations.checkin',
                'reservations.seat',
                'reservations.cancel',
                'reservations.no_show',
                'reservations.complete',

                'cashier.reopen_bill',
                'cashier.undo_payment',
                'cashier.void_item',
            ],

            'manager' => [
                'dashboard.view',

                'users.view',
                'users.create',
                'users.edit',

                'settings.view',

                'branches.view',
                'branches.switch',
                'branches.settings',

                'zones.view',
                'zones.create',
                'zones.edit',

                'tables.view',
                'tables.create',
                'tables.edit',
                'tables.change_status',

                'categories.view',
                'categories.create',
                'categories.edit',

                'products.view',
                'products.create',
                'products.edit',

                'customers.view',
                'customers.create',
                'customers.edit',

                'orders.view',
                'orders.create',
                'orders.edit',
                'orders.payment',

                'kitchen.view',
                'kitchen.update_status',

                'reports.view',
                'reports.sales',
                'reports.stock',

                'reservations.view',
                'reservations.create',
                'reservations.edit',
                'reservations.delete',
                'reservations.checkin',
                'reservations.seat',
                'reservations.cancel',
                'reservations.no_show',
                'reservations.complete',

                'cashier.reopen_bill',
                'cashier.undo_payment',
                'cashier.void_item',
            ],

            'staff' => [
                'dashboard.view',

                'tables.view',
                'tables.change_status',

                'categories.view',
                'products.view',

                'customers.view',
                'customers.create',
                'customers.edit',

                'orders.view',
                'orders.create',
                'orders.edit',
                'orders.payment',

                'kitchen.view',
                'kitchen.update_status',

                'reservations.view',
                'reservations.create',
                'reservations.edit',
                'reservations.checkin',
                'reservations.seat',
                'reservations.cancel',
                'reservations.no_show',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | โหลด roles ทั้งหมด โดยใช้ role_name
        |--------------------------------------------------------------------------
        */
        $roles = $db->table('roles')->get()->getResultArray();
        $roleMap = [];
        foreach ($roles as $role) {
            $key = strtolower(trim((string) ($role['role_name'] ?? '')));
            if ($key !== '') {
                $roleMap[$key] = $role;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | โหลด permissions ทั้งหมด
        |--------------------------------------------------------------------------
        */
        $permissions = $db->table('permissions')->get()->getResultArray();
        $permissionMap = [];
        foreach ($permissions as $permission) {
            $key = trim((string) ($permission['permission_key'] ?? ''));
            if ($key !== '') {
                $permissionMap[$key] = $permission;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | insert role_permissions
        |--------------------------------------------------------------------------
        */
        foreach ($rolePermissions as $roleName => $permissionKeys) {
            $roleKey = strtolower(trim($roleName));

            if (! isset($roleMap[$roleKey])) {
                echo "Skip role: {$roleName}" . PHP_EOL;
                continue;
            }

            $roleId = (int) $roleMap[$roleKey]['id'];

            foreach ($permissionKeys as $permissionKey) {
                if (! isset($permissionMap[$permissionKey])) {
                    echo "Skip permission: {$permissionKey}" . PHP_EOL;
                    continue;
                }

                $permissionId = (int) $permissionMap[$permissionKey]['id'];

                $exists = $db->table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->get()
                    ->getRowArray();

                if (! $exists) {
                    $db->table('role_permissions')->insert([
                        'role_id'       => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }
}