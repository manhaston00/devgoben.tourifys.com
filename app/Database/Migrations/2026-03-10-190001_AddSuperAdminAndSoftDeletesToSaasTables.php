<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSuperAdminAndSoftDeletesToSaasTables extends Migration
{
    protected function tableExists(string $table): bool
    {
        return in_array($table, $this->db->listTables(), true);
    }

    protected function columnExists(string $table, string $column): bool
    {
        if (! $this->tableExists($table)) {
            return false;
        }

        return in_array($column, $this->db->getFieldNames($table), true);
    }

    protected function addDeletedAtIfMissing(string $table): void
    {
        if (! $this->tableExists($table)) {
            return;
        }

        if ($this->columnExists($table, 'deleted_at')) {
            return;
        }

        $sql = "ALTER TABLE `{$table}` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL";
        $this->db->query($sql);
    }

    public function up()
    {
        // 1) add super_admin role
        $role = $this->db->table('roles')
            ->where('role_name', 'super_admin')
            ->get()
            ->getRowArray();

        if (! $role) {
            $this->db->table('roles')->insert([
                'role_name'       => 'super_admin',
                'role_name_th'    => 'ผู้ดูแลระบบส่วนกลาง',
                'role_name_en'    => 'Super Admin',
                'description'     => 'Platform Super Administrator',
                'description_th'  => 'ผู้ดูแลระบบ SaaS ส่วนกลาง',
                'description_en'  => 'Platform-level SaaS administrator',
                'status'          => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        // 2) permissions for super admin module
        $permissions = [
            ['super_admin',       'view',   'super_admin.view',        'Super Admin Access',      'เข้าใช้งานระบบส่วนกลาง', 'Access Super Admin'],
            ['tenants',           'view',   'tenants.view',            'View Tenants',            'ดู Tenants',              'View Tenants'],
            ['tenants',           'create', 'tenants.create',          'Create Tenant',           'เพิ่ม Tenant',            'Create Tenant'],
            ['tenants',           'edit',   'tenants.edit',            'Edit Tenant',             'แก้ไข Tenant',            'Edit Tenant'],
            ['tenants',           'delete', 'tenants.delete',          'Delete Tenant',           'ลบ Tenant',               'Delete Tenant'],
            ['plans',             'view',   'plans.view',              'View Plans',              'ดูแพ็กเกจ',               'View Plans'],
            ['plans',             'create', 'plans.create',            'Create Plan',             'เพิ่มแพ็กเกจ',            'Create Plan'],
            ['plans',             'edit',   'plans.edit',              'Edit Plan',               'แก้ไขแพ็กเกจ',            'Edit Plan'],
            ['plans',             'delete', 'plans.delete',            'Delete Plan',             'ลบแพ็กเกจ',               'Delete Plan'],
            ['subscriptions',     'view',   'subscriptions.view',      'View Subscriptions',      'ดู subscriptions',         'View Subscriptions'],
            ['subscriptions',     'create', 'subscriptions.create',    'Create Subscription',     'เพิ่ม subscription',       'Create Subscription'],
            ['subscriptions',     'edit',   'subscriptions.edit',      'Edit Subscription',       'แก้ไข subscription',       'Edit Subscription'],
            ['subscriptions',     'delete', 'subscriptions.delete',    'Delete Subscription',     'ลบ subscription',          'Delete Subscription'],
            ['subscription_logs', 'view',   'subscription_logs.view',  'View Subscription Logs',  'ดู logs/invoices',         'View Logs/Invoices'],
        ];

        foreach ($permissions as $perm) {
            $exists = $this->db->table('permissions')
                ->where('permission_key', $perm[2])
                ->get()
                ->getRowArray();

            if (! $exists) {
                $this->db->table('permissions')->insert([
                    'module_key'     => $perm[0],
                    'action_key'     => $perm[1],
                    'permission_key' => $perm[2],
                    'label'          => $perm[3],
                    'label_th'       => $perm[4],
                    'label_en'       => $perm[5],
                    'status'         => 1,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $superAdminRole = $this->db->table('roles')
            ->where('role_name', 'super_admin')
            ->get()
            ->getRowArray();

        if ($superAdminRole) {
            $roleId = (int) $superAdminRole['id'];

            $permRows = $this->db->table('permissions')
                ->whereIn('permission_key', [
                    'super_admin.view',
                    'tenants.view', 'tenants.create', 'tenants.edit', 'tenants.delete',
                    'plans.view', 'plans.create', 'plans.edit', 'plans.delete',
                    'subscriptions.view', 'subscriptions.create', 'subscriptions.edit', 'subscriptions.delete',
                    'subscription_logs.view',
                ])
                ->get()
                ->getResultArray();

            foreach ($permRows as $permRow) {
                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', (int) $permRow['id'])
                    ->get()
                    ->getRowArray();

                if (! $exists) {
                    $this->db->table('role_permissions')->insert([
                        'role_id'       => $roleId,
                        'permission_id' => (int) $permRow['id'],
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        // 3) add deleted_at to SaaS tables safely
        $tables = [
            'tenants',
            'subscription_plans',
            'subscriptions',
            'subscription_logs',
            'subscription_invoices',
            'app_features',
            'subscription_plan_features',
        ];

        foreach ($tables as $table) {
            $this->addDeletedAtIfMissing($table);
        }
    }

    public function down()
    {
        $tables = [
            'tenants',
            'subscription_plans',
            'subscriptions',
            'subscription_logs',
            'subscription_invoices',
            'app_features',
            'subscription_plan_features',
        ];

        foreach ($tables as $table) {
            if ($this->tableExists($table) && $this->columnExists($table, 'deleted_at')) {
                $this->db->query("ALTER TABLE `{$table}` DROP COLUMN `deleted_at`");
            }
        }

        $permKeys = [
            'super_admin.view',
            'tenants.view', 'tenants.create', 'tenants.edit', 'tenants.delete',
            'plans.view', 'plans.create', 'plans.edit', 'plans.delete',
            'subscriptions.view', 'subscriptions.create', 'subscriptions.edit', 'subscriptions.delete',
            'subscription_logs.view',
        ];

        $permRows = $this->db->table('permissions')
            ->whereIn('permission_key', $permKeys)
            ->get()
            ->getResultArray();

        $permIds = array_map(static function ($row) {
            return (int) $row['id'];
        }, $permRows);

        if (! empty($permIds)) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permIds)->delete();
            $this->db->table('permissions')->whereIn('id', $permIds)->delete();
        }

        $this->db->table('roles')->where('role_name', 'super_admin')->delete();
    }
}