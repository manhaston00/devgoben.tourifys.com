<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCashierUndoPaymentSupport extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('payment_status', 'payments')) {
            $this->forge->addColumn('payments', [
                'payment_status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['paid', 'voided'],
                    'default'    => 'paid',
                    'after'      => 'paid_at',
                ],
                'voided_by' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'payment_status',
                ],
                'voided_at' => [
                    'type'       => 'DATETIME',
                    'null'       => true,
                    'after'      => 'voided_by',
                ],
                'void_reason' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'voided_at',
                ],
            ]);
        }

        if ($this->db->fieldExists('payment_status', 'payments')) {
            $this->db->query("UPDATE payments SET payment_status = 'paid' WHERE payment_status IS NULL");
        }

        if (! $this->indexExists('payments', 'idx_payments_status')) {
            $this->db->query('ALTER TABLE `payments` ADD KEY `idx_payments_status` (`payment_status`)');
        }

        $permission = $this->db->table('permissions')
            ->where('permission_key', 'cashier.undo_payment')
            ->get()
            ->getRowArray();

        $permissionId = (int) ($permission['id'] ?? 0);

        if ($permissionId <= 0) {
            $this->db->table('permissions')->insert([
                'module_key'     => 'cashier',
                'action_key'     => 'undo_payment',
                'permission_key' => 'cashier.undo_payment',
                'label'          => 'Undo Payment / Void Payment',
                'label_th'       => 'Undo Payment / Void Payment',
                'label_en'       => 'Undo Payment / Void Payment',
                'status'         => 1,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            $permissionId = (int) $this->db->insertID();
        }

        if ($permissionId > 0) {
            $roleRows = $this->db->table('role_permissions')
                ->distinct()
                ->select('role_permissions.role_id')
                ->join('permissions', 'permissions.id = role_permissions.permission_id', 'inner')
                ->whereIn('permissions.permission_key', ['cashier.pay', 'cashier.reopen_bill'])
                ->get()
                ->getResultArray();

            foreach ($roleRows as $row) {
                $roleId = (int) ($row['role_id'] ?? 0);

                if ($roleId <= 0) {
                    continue;
                }

                $exists = $this->db->table('role_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->countAllResults();

                if (! $exists) {
                    $this->db->table('role_permissions')->insert([
                        'role_id'       => $roleId,
                        'permission_id' => $permissionId,
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }

    public function down()
    {
        $permission = $this->db->table('permissions')
            ->where('permission_key', 'cashier.undo_payment')
            ->get()
            ->getRowArray();

        $permissionId = (int) ($permission['id'] ?? 0);

        if ($permissionId > 0) {
            $this->db->table('role_permissions')
                ->where('permission_id', $permissionId)
                ->delete();

            $this->db->table('permissions')
                ->where('id', $permissionId)
                ->delete();
        }

        if ($this->indexExists('payments', 'idx_payments_status')) {
            $this->db->query('ALTER TABLE `payments` DROP INDEX `idx_payments_status`');
        }

        $columns = [];

        if ($this->db->fieldExists('payment_status', 'payments')) {
            $columns[] = 'payment_status';
        }
        if ($this->db->fieldExists('voided_by', 'payments')) {
            $columns[] = 'voided_by';
        }
        if ($this->db->fieldExists('voided_at', 'payments')) {
            $columns[] = 'voided_at';
        }
        if ($this->db->fieldExists('void_reason', 'payments')) {
            $columns[] = 'void_reason';
        }

        if (! empty($columns)) {
            $this->forge->dropColumn('payments', $columns);
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = $this->db->query(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        )->getResultArray();

        return ! empty($result);
    }
}