<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class IndexCleanupAfterTenantHardening extends Migration
{
    public function up()
    {
        $dropMap = [
            'branches' => [
                'idx_branches_tenant',
            ],
            'kitchen_logs' => [
                'idx_kitchen_logs_tenant',
                'idx_kitchen_logs_tenant_id',
            ],
            'kitchen_tickets' => [
                'idx_kitchen_tickets_tenant',
                'idx_kitchen_tickets_tenant_id',
            ],
            'order_items' => [
                'idx_order_items_tenant',
                'idx_order_items_tenant_id',
            ],
            'order_item_options' => [
                'idx_order_item_options_tenant',
                'idx_order_item_options_tenant_id',
                'idx_order_item_options_tenant_item',
            ],
            'payments' => [
                'idx_payments_tenant',
                'idx_payments_tenant_id',
            ],
            'promotion_redemptions' => [
                'idx_promotion_redemptions_tenant',
                'idx_promotion_redemptions_tenant_id',
            ],
            'reservation_logs' => [
                'idx_reservation_logs_tenant',
                'idx_reservation_logs_tenant_id',
            ],
            'reservation_tables' => [
                'idx_reservation_tables_tenant',
                'idx_reservation_tables_tenant_id',
            ],
        ];

        foreach ($dropMap as $table => $indexes) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            foreach ($indexes as $indexName) {
                if ($this->indexExists($table, $indexName)) {
                    $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
                }
            }
        }
    }

    public function down()
    {
        // no-op
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $rows = $this->db->query(
            'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ' . $this->db->escape($indexName)
        )->getResultArray();

        return ! empty($rows);
    }
}