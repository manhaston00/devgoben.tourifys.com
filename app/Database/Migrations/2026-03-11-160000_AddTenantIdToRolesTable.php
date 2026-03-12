<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTenantIdToRolesTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('roles')) {
            return;
        }

        if (! $this->db->fieldExists('tenant_id', 'roles')) {
            $this->forge->addColumn('roles', [
                'tenant_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'id',
                ],
            ]);
        }

        $this->dropIndexIfExists('roles', 'role_name');
        $this->dropIndexIfExists('roles', 'uniq_roles_tenant_role_name');
        $this->dropIndexIfExists('roles', 'idx_roles_tenant_id');

        $this->db->query('ALTER TABLE `roles` ADD KEY `idx_roles_tenant_id` (`tenant_id`)');
        $this->db->query('ALTER TABLE `roles` ADD UNIQUE KEY `uniq_roles_tenant_role_name` (`tenant_id`, `role_name`)');

        $rolesTable           = $this->db->table('roles');
        $rolePermissionsTable = $this->db->table('role_permissions');
        $tenantsTable         = $this->db->table('tenants');
        $usersTable           = $this->db->table('users');

        $roles = $rolesTable
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($roles)) {
            return;
        }

        $tenants = $tenantsTable
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($tenants)) {
            return;
        }

        $platformRoleIds = [];
        $templateRoles   = [];

        foreach ($roles as $role) {
            $roleId     = (int) ($role['id'] ?? 0);
            $roleName   = strtolower(trim((string) ($role['role_name'] ?? '')));
            $roleNameEn = strtolower(trim((string) ($role['role_name_en'] ?? '')));

            if ($roleId <= 0) {
                continue;
            }

            if ($roleName === 'super_admin' || $roleNameEn === 'super_admin') {
                $platformRoleIds[] = $roleId;

                $rolesTable
                    ->where('id', $roleId)
                    ->update([
                        'tenant_id'  => null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                continue;
            }

            $templateRoles[] = $role;
        }

        $roleMap = [];

        foreach ($tenants as $tenant) {
            $tenantId = (int) ($tenant['id'] ?? 0);
            if ($tenantId <= 0) {
                continue;
            }

            foreach ($templateRoles as $templateRole) {
                $templateRoleId = (int) ($templateRole['id'] ?? 0);
                if ($templateRoleId <= 0) {
                    continue;
                }

                $existing = $rolesTable
                    ->where('tenant_id', $tenantId)
                    ->where('role_name', (string) ($templateRole['role_name'] ?? ''))
                    ->where('deleted_at', null)
                    ->get()
                    ->getRowArray();

                if ($existing) {
                    $newRoleId = (int) $existing['id'];
                } else {
                    $insertData = [
                        'tenant_id'       => $tenantId,
                        'role_name'       => $templateRole['role_name'] ?? null,
                        'role_name_th'    => $templateRole['role_name_th'] ?? null,
                        'role_name_en'    => $templateRole['role_name_en'] ?? null,
                        'description'     => $templateRole['description'] ?? null,
                        'description_th'  => $templateRole['description_th'] ?? null,
                        'description_en'  => $templateRole['description_en'] ?? null,
                        'status'          => (int) ($templateRole['status'] ?? 1),
                        'created_at'      => date('Y-m-d H:i:s'),
                        'updated_at'      => date('Y-m-d H:i:s'),
                    ];

                    $rolesTable->insert($insertData);
                    $newRoleId = (int) $this->db->insertID();
                }

                $roleMap[$tenantId][$templateRoleId] = $newRoleId;

                $permissionRows = $rolePermissionsTable
                    ->where('role_id', $templateRoleId)
                    ->get()
                    ->getResultArray();

                foreach ($permissionRows as $permissionRow) {
                    $permissionId = (int) ($permissionRow['permission_id'] ?? 0);
                    if ($permissionId <= 0) {
                        continue;
                    }

                    $existsPermission = $rolePermissionsTable
                        ->where('role_id', $newRoleId)
                        ->where('permission_id', $permissionId)
                        ->countAllResults();

                    if (! $existsPermission) {
                        $rolePermissionsTable->insert([
                            'role_id'       => $newRoleId,
                            'permission_id' => $permissionId,
                            'created_at'    => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
        }

        $users = $usersTable
            ->where('tenant_id IS NOT NULL', null, false)
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        foreach ($users as $user) {
            $userId    = (int) ($user['id'] ?? 0);
            $tenantId  = (int) ($user['tenant_id'] ?? 0);
            $oldRoleId = (int) ($user['role_id'] ?? 0);

            if ($userId <= 0 || $tenantId <= 0 || $oldRoleId <= 0) {
                continue;
            }

            if (in_array($oldRoleId, $platformRoleIds, true)) {
                continue;
            }

            if (! isset($roleMap[$tenantId][$oldRoleId])) {
                continue;
            }

            $newRoleId = (int) $roleMap[$tenantId][$oldRoleId];

            if ($newRoleId > 0 && $newRoleId !== $oldRoleId) {
                $usersTable
                    ->where('id', $userId)
                    ->update([
                        'role_id'    => $newRoleId,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('roles')) {
            return;
        }

        $this->dropIndexIfExists('roles', 'uniq_roles_tenant_role_name');
        $this->dropIndexIfExists('roles', 'idx_roles_tenant_id');

        if ($this->db->fieldExists('tenant_id', 'roles')) {
            $this->forge->dropColumn('roles', 'tenant_id');
        }

        try {
            $this->db->query('ALTER TABLE `roles` ADD UNIQUE KEY `role_name` (`role_name`)');
        } catch (\Throwable $e) {
        }
    }

    protected function dropIndexIfExists(string $table, string $indexName): void
    {
        $sql = "SHOW INDEX FROM `{$table}` WHERE Key_name = " . $this->db->escape($indexName);
        $exists = $this->db->query($sql)->getResultArray();

        if (! empty($exists)) {
            $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }
    }
}