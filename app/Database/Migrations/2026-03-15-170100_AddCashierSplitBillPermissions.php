<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCashierSplitBillPermissions extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $permissionDefinitions = [
            'cashier.split_bill' => [
                'module_key' => 'cashier',
                'action_key' => 'split_bill',
                'label' => 'Split Bill',
                'label_th' => 'Split Bill',
                'label_en' => 'Split Bill',
            ],
            'cashier.split_bill_view' => [
                'module_key' => 'cashier',
                'action_key' => 'split_bill_view',
                'label' => 'View Split Bill History',
                'label_th' => 'View Split Bill History',
                'label_en' => 'View Split Bill History',
            ],
            'cashier.split_bill_cancel' => [
                'module_key' => 'cashier',
                'action_key' => 'split_bill_cancel',
                'label' => 'Cancel Split Bill',
                'label_th' => 'Cancel Split Bill',
                'label_en' => 'Cancel Split Bill',
            ],
        ];

        $permissionIds = [];
        foreach ($permissionDefinitions as $permissionKey => $def) {
            $row = $this->db->table('permissions')->where('permission_key', $permissionKey)->get()->getRowArray();
            if (! $row) {
                $insert = [
                    'module_key'     => $def['module_key'],
                    'action_key'     => $def['action_key'],
                    'permission_key' => $permissionKey,
                    'label'          => $def['label'],
                    'status'         => 1,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];

                if ($this->db->fieldExists('label_th', 'permissions')) {
                    $insert['label_th'] = $def['label_th'];
                }

                if ($this->db->fieldExists('label_en', 'permissions')) {
                    $insert['label_en'] = $def['label_en'];
                }

                $this->db->table('permissions')->insert($insert);
                $row = $this->db->table('permissions')->where('permission_key', $permissionKey)->get()->getRowArray();
            }

            if ($row) {
                $permissionIds[$permissionKey] = (int) ($row['id'] ?? 0);
            }
        }

        if (! $this->db->tableExists('role_permissions')) {
            return;
        }

        $splitBillRoleIds = $this->getRoleIdsByPermissionKey('cashier.close_bill');
        $splitBillViewRoleIds = array_unique(array_merge(
            $splitBillRoleIds,
            $this->getRoleIdsByPermissionKey('cashier.view')
        ));
        $splitBillCancelRoleIds = array_unique(array_merge(
            $this->getRoleIdsByPermissionKey('cashier.reopen_bill'),
            $this->getRoleIdsByPermissionKey('cashier.manager_override')
        ));

        $this->assignPermissionToRoles($permissionIds['cashier.split_bill'] ?? 0, $splitBillRoleIds, $now);
        $this->assignPermissionToRoles($permissionIds['cashier.split_bill_view'] ?? 0, $splitBillViewRoleIds, $now);
        $this->assignPermissionToRoles($permissionIds['cashier.split_bill_cancel'] ?? 0, $splitBillCancelRoleIds, $now);
    }

    public function down()
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $permissionKeys = [
            'cashier.split_bill',
            'cashier.split_bill_view',
            'cashier.split_bill_cancel',
        ];

        foreach ($permissionKeys as $permissionKey) {
            $row = $this->db->table('permissions')->select('id')->where('permission_key', $permissionKey)->get()->getRowArray();
            $permissionId = (int) ($row['id'] ?? 0);

            if ($permissionId > 0 && $this->db->tableExists('role_permissions')) {
                $this->db->table('role_permissions')->where('permission_id', $permissionId)->delete();
            }

            $this->db->table('permissions')->where('permission_key', $permissionKey)->delete();
        }
    }

    protected function getRoleIdsByPermissionKey(string $permissionKey): array
    {
        if (! $this->db->tableExists('permissions') || ! $this->db->tableExists('role_permissions')) {
            return [];
        }

        $permission = $this->db->table('permissions')->select('id')->where('permission_key', $permissionKey)->get()->getRowArray();
        $permissionId = (int) ($permission['id'] ?? 0);

        if ($permissionId <= 0) {
            return [];
        }

        $rows = $this->db->table('role_permissions')
            ->select('role_id')
            ->where('permission_id', $permissionId)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_filter(array_map(static function (array $row): int {
            return (int) ($row['role_id'] ?? 0);
        }, $rows))));
    }

    protected function assignPermissionToRoles(int $permissionId, array $roleIds, string $now): void
    {
        if ($permissionId <= 0 || $roleIds === []) {
            return;
        }

        foreach (array_unique(array_filter($roleIds)) as $roleId) {
            $exists = $this->db->table('role_permissions')
                ->where('role_id', (int) $roleId)
                ->where('permission_id', $permissionId)
                ->countAllResults();

            if (! $exists) {
                $insert = [
                    'role_id'       => (int) $roleId,
                    'permission_id' => $permissionId,
                ];

                if ($this->db->fieldExists('created_at', 'role_permissions')) {
                    $insert['created_at'] = $now;
                }

                $this->db->table('role_permissions')->insert($insert);
            }
        }
    }
}
