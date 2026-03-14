<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSettingsControlCenter extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tenant_settings')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'tenant_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => false,
                ],
                'setting_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'setting_value' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['tenant_id', 'setting_key'], 'uq_tenant_settings_tenant_key');

            // tenants.id ใน DB จริงเป็น INT signed
            $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');

            $this->forge->createTable('tenant_settings', true, [
                'ENGINE'  => 'InnoDB',
                'DEFAULT CHARACTER SET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ]);
        }

        if ($this->db->tableExists('permissions')) {
            $permissions = [
                ['module_key' => 'settings', 'action_key' => 'view', 'permission_key' => 'settings.control_center.view', 'label' => 'ดูศูนย์ควบคุมการตั้งค่า'],
                ['module_key' => 'settings', 'action_key' => 'edit', 'permission_key' => 'settings.control_center.edit', 'label' => 'แก้ไขศูนย์ควบคุมการตั้งค่า'],
                ['module_key' => 'settings', 'action_key' => 'view', 'permission_key' => 'settings.features.view', 'label' => 'ดูการตั้งค่าฟีเจอร์'],
                ['module_key' => 'settings', 'action_key' => 'edit', 'permission_key' => 'settings.features.edit', 'label' => 'แก้ไขการตั้งค่าฟีเจอร์'],
                ['module_key' => 'settings', 'action_key' => 'view', 'permission_key' => 'settings.menu_visibility.view', 'label' => 'ดูการแสดงเมนู'],
                ['module_key' => 'settings', 'action_key' => 'edit', 'permission_key' => 'settings.menu_visibility.edit', 'label' => 'แก้ไขการแสดงเมนู'],
                ['module_key' => 'settings', 'action_key' => 'view', 'permission_key' => 'settings.media.view', 'label' => 'ดูการตั้งค่ารูปภาพและแบรนด์'],
                ['module_key' => 'settings', 'action_key' => 'edit', 'permission_key' => 'settings.media.edit', 'label' => 'แก้ไขการตั้งค่ารูปภาพและแบรนด์'],
                ['module_key' => 'settings', 'action_key' => 'view', 'permission_key' => 'settings.billing.view', 'label' => 'ดูการตั้งค่าภาษีและค่าบริการ'],
                ['module_key' => 'settings', 'action_key' => 'edit', 'permission_key' => 'settings.billing.edit', 'label' => 'แก้ไขการตั้งค่าภาษีและค่าบริการ'],
                ['module_key' => 'settings', 'action_key' => 'view', 'permission_key' => 'settings.payments.view', 'label' => 'ดูการตั้งค่าช่องทางชำระเงิน'],
                ['module_key' => 'settings', 'action_key' => 'edit', 'permission_key' => 'settings.payments.edit', 'label' => 'แก้ไขการตั้งค่าช่องทางชำระเงิน'],
                ['module_key' => 'settings', 'action_key' => 'view', 'permission_key' => 'settings.printing.view', 'label' => 'ดูการตั้งค่าการพิมพ์'],
                ['module_key' => 'settings', 'action_key' => 'edit', 'permission_key' => 'settings.printing.edit', 'label' => 'แก้ไขการตั้งค่าการพิมพ์'],
                ['module_key' => 'settings', 'action_key' => 'view', 'permission_key' => 'settings.platform.view', 'label' => 'ดูค่าเริ่มต้นระดับแพลตฟอร์ม'],
                ['module_key' => 'settings', 'action_key' => 'edit', 'permission_key' => 'settings.platform.edit', 'label' => 'แก้ไขค่าเริ่มต้นระดับแพลตฟอร์ม'],
            ];

            $now = date('Y-m-d H:i:s');

            foreach ($permissions as $row) {
                $exists = $this->db->table('permissions')
                    ->where('permission_key', $row['permission_key'])
                    ->countAllResults();

                if ($exists) {
                    continue;
                }

                $insert = [
                    'module_key'     => $row['module_key'],
                    'action_key'     => $row['action_key'],
                    'permission_key' => $row['permission_key'],
                    'label'          => $row['label'],
                    'status'         => 1,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];

                if ($this->db->fieldExists('label_th', 'permissions')) {
                    $insert['label_th'] = $row['label'];
                }

                if ($this->db->fieldExists('label_en', 'permissions')) {
                    $insert['label_en'] = $row['label'];
                }

                $this->db->table('permissions')->insert($insert);
            }
        }

        $this->grantDerivedPermissionsByExistingRolePermissions();
    }

    public function down()
    {
        if ($this->db->tableExists('tenant_settings')) {
            $this->forge->dropTable('tenant_settings', true);
        }

        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $permissionKeys = [
            'settings.control_center.view',
            'settings.control_center.edit',
            'settings.features.view',
            'settings.features.edit',
            'settings.menu_visibility.view',
            'settings.menu_visibility.edit',
            'settings.media.view',
            'settings.media.edit',
            'settings.billing.view',
            'settings.billing.edit',
            'settings.payments.view',
            'settings.payments.edit',
            'settings.printing.view',
            'settings.printing.edit',
            'settings.platform.view',
            'settings.platform.edit',
        ];

        $permissionRows = $this->db->table('permissions')
            ->select('id, permission_key')
            ->whereIn('permission_key', $permissionKeys)
            ->get()
            ->getResultArray();

        $permissionIds = array_values(array_filter(array_map(static function (array $row): int {
            return (int) ($row['id'] ?? 0);
        }, $permissionRows)));

        if (! empty($permissionIds) && $this->db->tableExists('role_permissions')) {
            $this->db->table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        }

        $this->db->table('permissions')->whereIn('permission_key', $permissionKeys)->delete();
    }

    protected function grantDerivedPermissionsByExistingRolePermissions(): void
    {
        if (! $this->db->tableExists('permissions') || ! $this->db->tableExists('roles') || ! $this->db->tableExists('role_permissions')) {
            return;
        }

        $permissionRows = $this->db->table('permissions')
            ->select('id, permission_key')
            ->whereIn('permission_key', [
                'settings.view',
                'settings.edit',
                'branches.settings',
                'branches.edit',
                'settings.control_center.view',
                'settings.control_center.edit',
                'settings.features.view',
                'settings.features.edit',
                'settings.menu_visibility.view',
                'settings.menu_visibility.edit',
                'settings.media.view',
                'settings.media.edit',
                'settings.billing.view',
                'settings.billing.edit',
                'settings.payments.view',
                'settings.payments.edit',
                'settings.printing.view',
                'settings.printing.edit',
                'settings.platform.view',
                'settings.platform.edit',
            ])
            ->get()
            ->getResultArray();

        $permissionMap = [];
        foreach ($permissionRows as $row) {
            $permissionMap[(string) $row['permission_key']] = (int) $row['id'];
        }

        $rolesBuilder = $this->db->table('roles')->select('id, role_name');
        if ($this->db->fieldExists('deleted_at', 'roles')) {
            $rolesBuilder->where('deleted_at', null);
        }
        $roleRows = $rolesBuilder->get()->getResultArray();

        foreach ($roleRows as $role) {
            $roleId   = (int) ($role['id'] ?? 0);
            $roleName = strtolower(trim((string) ($role['role_name'] ?? '')));

            if ($roleId <= 0) {
                continue;
            }

            $existingPermissionIds = array_map(
                static fn (array $row): int => (int) ($row['permission_id'] ?? 0),
                $this->db->table('role_permissions')
                    ->select('permission_id')
                    ->where('role_id', $roleId)
                    ->get()
                    ->getResultArray()
            );

            $hasSettingsView = in_array((int) ($permissionMap['settings.view'] ?? 0), $existingPermissionIds, true)
                || in_array((int) ($permissionMap['branches.settings'] ?? 0), $existingPermissionIds, true)
                || in_array((int) ($permissionMap['branches.edit'] ?? 0), $existingPermissionIds, true);

            $hasSettingsEdit = in_array((int) ($permissionMap['settings.edit'] ?? 0), $existingPermissionIds, true)
                || in_array((int) ($permissionMap['branches.settings'] ?? 0), $existingPermissionIds, true)
                || in_array((int) ($permissionMap['branches.edit'] ?? 0), $existingPermissionIds, true);

            $toGrant = [];

            if ($hasSettingsView) {
                $toGrant = array_merge($toGrant, [
                    'settings.control_center.view',
                    'settings.features.view',
                    'settings.menu_visibility.view',
                    'settings.media.view',
                    'settings.billing.view',
                    'settings.payments.view',
                    'settings.printing.view',
                ]);
            }

            if ($hasSettingsEdit) {
                $toGrant = array_merge($toGrant, [
                    'settings.control_center.edit',
                    'settings.features.edit',
                    'settings.menu_visibility.edit',
                    'settings.media.edit',
                    'settings.billing.edit',
                    'settings.payments.edit',
                    'settings.printing.edit',
                ]);
            }

            if (in_array($roleName, ['super_admin', 'super admin', 'super-admin', 'platform_admin'], true)) {
                $toGrant = array_merge($toGrant, [
                    'settings.platform.view',
                    'settings.platform.edit',
                ]);
            }

            foreach (array_unique($toGrant) as $permissionKey) {
                $permissionId = (int) ($permissionMap[$permissionKey] ?? 0);

                if ($permissionId <= 0) {
                    continue;
                }

                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->countAllResults();

                if (! $exists) {
                    $insert = [
                        'role_id'       => $roleId,
                        'permission_id' => $permissionId,
                    ];

                    if ($this->db->fieldExists('created_at', 'role_permissions')) {
                        $insert['created_at'] = date('Y-m-d H:i:s');
                    }

                    $this->db->table('role_permissions')->insert($insert);
                }
            }
        }
    }
}