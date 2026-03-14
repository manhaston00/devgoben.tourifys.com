<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCashierReopenBillPermission extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $permissionRow = $this->db->table('permissions')
            ->where('permission_key', 'cashier.reopen_bill')
            ->get()
            ->getRowArray();

        if (! $permissionRow) {
            $insert = [
                'module_key'     => 'cashier',
                'action_key'     => 'reopen_bill',
                'permission_key' => 'cashier.reopen_bill',
                'label'          => 'Reopen Bill',
                'status'         => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            if ($this->db->fieldExists('label_th', 'permissions')) {
                $insert['label_th'] = 'Reopen Bill';
            }

            if ($this->db->fieldExists('label_en', 'permissions')) {
                $insert['label_en'] = 'Reopen Bill';
            }

            $this->db->table('permissions')->insert($insert);

            $permissionRow = $this->db->table('permissions')
                ->where('permission_key', 'cashier.reopen_bill')
                ->get()
                ->getRowArray();
        }

        $permissionId = (int) ($permissionRow['id'] ?? 0);
        if ($permissionId <= 0 || ! $this->db->tableExists('role_permissions')) {
            return;
        }

        $closeBillPermission = $this->db->table('permissions')
            ->select('id')
            ->where('permission_key', 'cashier.close_bill')
            ->get()
            ->getRowArray();

        $closeBillPermissionId = (int) ($closeBillPermission['id'] ?? 0);
        if ($closeBillPermissionId <= 0) {
            return;
        }

        $roleIds = array_map(static fn(array $row): int => (int) ($row['role_id'] ?? 0),
            $this->db->table('role_permissions')
                ->select('role_id')
                ->where('permission_id', $closeBillPermissionId)
                ->get()
                ->getResultArray()
        );

        foreach (array_unique(array_filter($roleIds)) as $roleId) {
            $exists = $this->db->table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->countAllResults();

            if (! $exists) {
                $this->db->table('role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                    'created_at'    => $now,
                ]);
            }
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $permissionRow = $this->db->table('permissions')
            ->select('id')
            ->where('permission_key', 'cashier.reopen_bill')
            ->get()
            ->getRowArray();

        $permissionId = (int) ($permissionRow['id'] ?? 0);

        if ($permissionId > 0 && $this->db->tableExists('role_permissions')) {
            $this->db->table('role_permissions')->where('permission_id', $permissionId)->delete();
        }

        $this->db->table('permissions')->where('permission_key', 'cashier.reopen_bill')->delete();
    }
}
