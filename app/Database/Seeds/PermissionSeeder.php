<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $permissions = [
            ['module_key' => 'dashboard', 'action_key' => 'view', 'permission_key' => 'dashboard.view', 'label' => 'ดูแดชบอร์ด'],

            ['module_key' => 'roles', 'action_key' => 'view', 'permission_key' => 'roles.view', 'label' => 'ดู Roles'],
            ['module_key' => 'roles', 'action_key' => 'create', 'permission_key' => 'roles.create', 'label' => 'เพิ่ม Roles'],
            ['module_key' => 'roles', 'action_key' => 'edit', 'permission_key' => 'roles.edit', 'label' => 'แก้ไข Roles'],
            ['module_key' => 'roles', 'action_key' => 'delete', 'permission_key' => 'roles.delete', 'label' => 'ลบ Roles'],

            ['module_key' => 'users', 'action_key' => 'view', 'permission_key' => 'users.view', 'label' => 'ดูผู้ใช้งาน'],
            ['module_key' => 'users', 'action_key' => 'create', 'permission_key' => 'users.create', 'label' => 'เพิ่มผู้ใช้งาน'],
            ['module_key' => 'users', 'action_key' => 'edit', 'permission_key' => 'users.edit', 'label' => 'แก้ไขผู้ใช้งาน'],
            ['module_key' => 'users', 'action_key' => 'delete', 'permission_key' => 'users.delete', 'label' => 'ลบผู้ใช้งาน'],

            ['module_key' => 'categories', 'action_key' => 'view', 'permission_key' => 'categories.view', 'label' => 'ดูหมวดหมู่'],
            ['module_key' => 'categories', 'action_key' => 'create', 'permission_key' => 'categories.create', 'label' => 'เพิ่มหมวดหมู่'],
            ['module_key' => 'categories', 'action_key' => 'edit', 'permission_key' => 'categories.edit', 'label' => 'แก้ไขหมวดหมู่'],
            ['module_key' => 'categories', 'action_key' => 'delete', 'permission_key' => 'categories.delete', 'label' => 'ลบหมวดหมู่'],

            ['module_key' => 'products', 'action_key' => 'view', 'permission_key' => 'products.view', 'label' => 'ดูสินค้า'],
            ['module_key' => 'products', 'action_key' => 'create', 'permission_key' => 'products.create', 'label' => 'เพิ่มสินค้า'],
            ['module_key' => 'products', 'action_key' => 'edit', 'permission_key' => 'products.edit', 'label' => 'แก้ไขสินค้า'],
            ['module_key' => 'products', 'action_key' => 'delete', 'permission_key' => 'products.delete', 'label' => 'ลบสินค้า'],

            ['module_key' => 'zones', 'action_key' => 'view', 'permission_key' => 'zones.view', 'label' => 'ดูโซน'],
            ['module_key' => 'zones', 'action_key' => 'create', 'permission_key' => 'zones.create', 'label' => 'เพิ่มโซน'],
            ['module_key' => 'zones', 'action_key' => 'edit', 'permission_key' => 'zones.edit', 'label' => 'แก้ไขโซน'],
            ['module_key' => 'zones', 'action_key' => 'delete', 'permission_key' => 'zones.delete', 'label' => 'ลบโซน'],

            ['module_key' => 'tables', 'action_key' => 'view', 'permission_key' => 'tables.view', 'label' => 'ดูโต๊ะ'],
            ['module_key' => 'tables', 'action_key' => 'create', 'permission_key' => 'tables.create', 'label' => 'เพิ่มโต๊ะ'],
            ['module_key' => 'tables', 'action_key' => 'edit', 'permission_key' => 'tables.edit', 'label' => 'แก้ไขโต๊ะ'],
            ['module_key' => 'tables', 'action_key' => 'delete', 'permission_key' => 'tables.delete', 'label' => 'ลบโต๊ะ'],

            ['module_key' => 'kitchen_stations', 'action_key' => 'view', 'permission_key' => 'kitchen_stations.view', 'label' => 'ดูสถานีครัว'],
            ['module_key' => 'kitchen_stations', 'action_key' => 'create', 'permission_key' => 'kitchen_stations.create', 'label' => 'เพิ่มสถานีครัว'],
            ['module_key' => 'kitchen_stations', 'action_key' => 'edit', 'permission_key' => 'kitchen_stations.edit', 'label' => 'แก้ไขสถานีครัว'],
            ['module_key' => 'kitchen_stations', 'action_key' => 'delete', 'permission_key' => 'kitchen_stations.delete', 'label' => 'ลบสถานีครัว'],

            ['module_key' => 'quick_notes', 'action_key' => 'view', 'permission_key' => 'quick_notes.view', 'label' => 'ดูหมายเหตุเพิ่มเติม'],
            ['module_key' => 'quick_notes', 'action_key' => 'create', 'permission_key' => 'quick_notes.create', 'label' => 'เพิ่มหมายเหตุเพิ่มเติม'],
            ['module_key' => 'quick_notes', 'action_key' => 'edit', 'permission_key' => 'quick_notes.edit', 'label' => 'แก้ไขหมายเหตุเพิ่มเติม'],
            ['module_key' => 'quick_notes', 'action_key' => 'delete', 'permission_key' => 'quick_notes.delete', 'label' => 'ลบหมายเหตุเพิ่มเติม'],

            ['module_key' => 'product_quick_options', 'action_key' => 'view', 'permission_key' => 'product_quick_options.view', 'label' => 'ดูการผูกรายละเอียดอาหารกับสินค้า'],
            ['module_key' => 'product_quick_options', 'action_key' => 'create', 'permission_key' => 'product_quick_options.create', 'label' => 'เพิ่มการผูกรายละเอียดอาหารกับสินค้า'],
            ['module_key' => 'product_quick_options', 'action_key' => 'edit', 'permission_key' => 'product_quick_options.edit', 'label' => 'แก้ไขการผูกรายละเอียดอาหารกับสินค้า'],
            ['module_key' => 'product_quick_options', 'action_key' => 'delete', 'permission_key' => 'product_quick_options.delete', 'label' => 'ลบการผูกรายละเอียดอาหารกับสินค้า'],

            ['module_key' => 'reservations', 'action_key' => 'view', 'permission_key' => 'reservations.view', 'label' => 'ดูรายการจองโต๊ะ'],
            ['module_key' => 'reservations', 'action_key' => 'create', 'permission_key' => 'reservations.create', 'label' => 'เพิ่มการจองโต๊ะ'],
            ['module_key' => 'reservations', 'action_key' => 'edit', 'permission_key' => 'reservations.edit', 'label' => 'แก้ไขการจองโต๊ะ'],
            ['module_key' => 'reservations', 'action_key' => 'delete', 'permission_key' => 'reservations.delete', 'label' => 'ลบการจองโต๊ะ'],

            ['module_key' => 'settings', 'action_key' => 'view', 'permission_key' => 'settings.view', 'label' => 'ดูตั้งค่าระบบ'],
            ['module_key' => 'settings', 'action_key' => 'edit', 'permission_key' => 'settings.edit', 'label' => 'แก้ไขตั้งค่าระบบ'],

            ['module_key' => 'licenses', 'action_key' => 'view', 'permission_key' => 'licenses.view', 'label' => 'ดูแพ็กเกจและไลเซนส์'],
            ['module_key' => 'licenses', 'action_key' => 'create', 'permission_key' => 'licenses.create', 'label' => 'สร้างไลเซนส์'],
            ['module_key' => 'licenses', 'action_key' => 'edit', 'permission_key' => 'licenses.edit', 'label' => 'แก้ไขไลเซนส์'],

            ['module_key' => 'promotions', 'action_key' => 'view', 'permission_key' => 'promotions.view', 'label' => 'ดูโปรโมชั่น'],
            ['module_key' => 'promotions', 'action_key' => 'create', 'permission_key' => 'promotions.create', 'label' => 'เพิ่มโปรโมชั่น'],
            ['module_key' => 'promotions', 'action_key' => 'edit', 'permission_key' => 'promotions.edit', 'label' => 'แก้ไขโปรโมชั่น'],
            ['module_key' => 'promotions', 'action_key' => 'delete', 'permission_key' => 'promotions.delete', 'label' => 'ลบโปรโมชั่น'],

            ['module_key' => 'branches', 'action_key' => 'view', 'permission_key' => 'branches.view', 'label' => 'ดูสาขา'],
            ['module_key' => 'branches', 'action_key' => 'create', 'permission_key' => 'branches.create', 'label' => 'เพิ่มสาขา'],
            ['module_key' => 'branches', 'action_key' => 'edit', 'permission_key' => 'branches.edit', 'label' => 'แก้ไขสาขา'],
            ['module_key' => 'branches', 'action_key' => 'delete', 'permission_key' => 'branches.delete', 'label' => 'ลบสาขา'],

            ['module_key' => 'cashier', 'action_key' => 'reopen_bill', 'permission_key' => 'cashier.reopen_bill', 'label' => 'Reopen Bill'],
            ['module_key' => 'cashier', 'action_key' => 'undo_payment', 'permission_key' => 'cashier.undo_payment', 'label' => 'Undo Payment / Void Payment'],
            ['module_key' => 'cashier', 'action_key' => 'void_item', 'permission_key' => 'cashier.void_item', 'label' => 'Void Item'],
        ];

        foreach ($permissions as $row) {
            $exists = $this->db->table('permissions')
                ->where('permission_key', $row['permission_key'])
                ->countAllResults();

            if (! $exists) {
                $this->db->table('permissions')->insert([
                    'module_key'     => $row['module_key'],
                    'action_key'     => $row['action_key'],
                    'permission_key' => $row['permission_key'],
                    'label'          => $row['label'],
                    'label_th'       => $row['label'],
                    'label_en'       => $row['label'],
                    'status'         => 1,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }
        }

        $adminPermissionKeys = [
            'dashboard.view',

            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'zones.view', 'zones.create', 'zones.edit', 'zones.delete',
            'tables.view', 'tables.create', 'tables.edit', 'tables.delete',
            'kitchen_stations.view', 'kitchen_stations.create', 'kitchen_stations.edit', 'kitchen_stations.delete',
            'quick_notes.view', 'quick_notes.create', 'quick_notes.edit', 'quick_notes.delete',
            'product_quick_options.view', 'product_quick_options.create', 'product_quick_options.edit', 'product_quick_options.delete',
            'reservations.view', 'reservations.create', 'reservations.edit', 'reservations.delete',
            'settings.view', 'settings.edit',
            'licenses.view', 'licenses.create', 'licenses.edit',
            'promotions.view', 'promotions.create', 'promotions.edit', 'promotions.delete',
            'branches.view', 'branches.create', 'branches.edit', 'branches.delete',
            'cashier.reopen_bill',
            'cashier.undo_payment',
            'cashier.void_item',
        ];

        $adminRole = $this->db->table('roles')->where('role_name', 'admin')->get()->getRowArray();

        if ($adminRole) {
            foreach ($adminPermissionKeys as $permissionKey) {
                $permission = $this->db->table('permissions')
                    ->where('permission_key', $permissionKey)
                    ->get()
                    ->getRowArray();

                if (! $permission) {
                    continue;
                }

                $exists = $this->db->table('role_permissions')
                    ->where('role_id', (int) $adminRole['id'])
                    ->where('permission_id', (int) $permission['id'])
                    ->countAllResults();

                if (! $exists) {
                    $this->db->table('role_permissions')->insert([
                        'role_id'       => (int) $adminRole['id'],
                        'permission_id' => (int) $permission['id'],
                    ]);
                }
            }
        }
    }
}