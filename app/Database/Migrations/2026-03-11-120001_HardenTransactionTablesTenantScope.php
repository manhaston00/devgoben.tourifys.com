<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class HardenTransactionTablesTenantScope extends Migration
{
    public function up()
    {
        $db    = \Config\Database::connect();
        $forge = \Config\Database::forge();

        $tables = [
            'order_items',
            'order_item_options',
            'payments',
            'kitchen_tickets',
            'kitchen_logs',
            'reservation_tables',
            'reservation_logs',
            'promotion_redemptions',
        ];

        foreach ($tables as $table) {
            if ($db->tableExists($table) && ! $db->fieldExists('tenant_id', $table)) {
                $forge->addColumn($table, [
                    'tenant_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'null'       => true,
                        'after'      => 'id',
                    ],
                ]);
            }
        }

        if (
            $db->tableExists('order_items') &&
            $db->tableExists('orders') &&
            $db->fieldExists('tenant_id', 'order_items') &&
            $db->fieldExists('tenant_id', 'orders') &&
            $db->fieldExists('order_id', 'order_items')
        ) {
            $db->query("
                UPDATE order_items oi
                INNER JOIN orders o ON o.id = oi.order_id
                SET oi.tenant_id = o.tenant_id
                WHERE oi.tenant_id IS NULL
            ");
        }

        if (
            $db->tableExists('order_item_options') &&
            $db->tableExists('order_items') &&
            $db->fieldExists('tenant_id', 'order_item_options') &&
            $db->fieldExists('tenant_id', 'order_items') &&
            $db->fieldExists('order_item_id', 'order_item_options')
        ) {
            $db->query("
                UPDATE order_item_options oio
                INNER JOIN order_items oi ON oi.id = oio.order_item_id
                SET oio.tenant_id = oi.tenant_id
                WHERE oio.tenant_id IS NULL
            ");
        }

        if (
            $db->tableExists('payments') &&
            $db->tableExists('orders') &&
            $db->fieldExists('tenant_id', 'payments') &&
            $db->fieldExists('tenant_id', 'orders') &&
            $db->fieldExists('order_id', 'payments')
        ) {
            $db->query("
                UPDATE payments p
                INNER JOIN orders o ON o.id = p.order_id
                SET p.tenant_id = o.tenant_id
                WHERE p.tenant_id IS NULL
            ");
        }

        if (
            $db->tableExists('kitchen_tickets') &&
            $db->tableExists('orders') &&
            $db->fieldExists('tenant_id', 'kitchen_tickets') &&
            $db->fieldExists('tenant_id', 'orders') &&
            $db->fieldExists('order_id', 'kitchen_tickets')
        ) {
            $db->query("
                UPDATE kitchen_tickets kt
                INNER JOIN orders o ON o.id = kt.order_id
                SET kt.tenant_id = o.tenant_id
                WHERE kt.tenant_id IS NULL
            ");
        }

        if (
            $db->tableExists('kitchen_logs') &&
            $db->tableExists('order_items') &&
            $db->fieldExists('tenant_id', 'kitchen_logs') &&
            $db->fieldExists('tenant_id', 'order_items') &&
            $db->fieldExists('order_item_id', 'kitchen_logs')
        ) {
            $db->query("
                UPDATE kitchen_logs kl
                INNER JOIN order_items oi ON oi.id = kl.order_item_id
                SET kl.tenant_id = oi.tenant_id
                WHERE kl.tenant_id IS NULL
            ");
        }

        if (
            $db->tableExists('reservation_tables') &&
            $db->tableExists('reservations') &&
            $db->fieldExists('tenant_id', 'reservation_tables') &&
            $db->fieldExists('tenant_id', 'reservations') &&
            $db->fieldExists('reservation_id', 'reservation_tables')
        ) {
            $db->query("
                UPDATE reservation_tables rt
                INNER JOIN reservations r ON r.id = rt.reservation_id
                SET rt.tenant_id = r.tenant_id
                WHERE rt.tenant_id IS NULL
            ");
        }

        if (
            $db->tableExists('reservation_logs') &&
            $db->tableExists('reservations') &&
            $db->fieldExists('tenant_id', 'reservation_logs') &&
            $db->fieldExists('tenant_id', 'reservations') &&
            $db->fieldExists('reservation_id', 'reservation_logs')
        ) {
            $db->query("
                UPDATE reservation_logs rl
                INNER JOIN reservations r ON r.id = rl.reservation_id
                SET rl.tenant_id = r.tenant_id
                WHERE rl.tenant_id IS NULL
            ");
        }

        if (
            $db->tableExists('promotion_redemptions') &&
            $db->tableExists('promotions') &&
            $db->fieldExists('tenant_id', 'promotion_redemptions') &&
            $db->fieldExists('tenant_id', 'promotions') &&
            $db->fieldExists('promotion_id', 'promotion_redemptions')
        ) {
            $db->query("
                UPDATE promotion_redemptions pr
                INNER JOIN promotions p ON p.id = pr.promotion_id
                SET pr.tenant_id = p.tenant_id
                WHERE pr.tenant_id IS NULL
            ");
        }

        $this->addIndexIfNotExists($db, 'order_items', 'idx_order_items_tenant_id', 'tenant_id');
        $this->addIndexIfNotExists($db, 'order_items', 'idx_order_items_tenant_order', 'tenant_id, order_id');

        $this->addIndexIfNotExists($db, 'order_item_options', 'idx_order_item_options_tenant_id', 'tenant_id');
        $this->addIndexIfNotExists($db, 'order_item_options', 'idx_order_item_options_tenant_order_item', 'tenant_id, order_item_id');

        $this->addIndexIfNotExists($db, 'payments', 'idx_payments_tenant_id', 'tenant_id');
        $this->addIndexIfNotExists($db, 'payments', 'idx_payments_tenant_order', 'tenant_id, order_id');

        $this->addIndexIfNotExists($db, 'kitchen_tickets', 'idx_kitchen_tickets_tenant_id', 'tenant_id');
        $this->addIndexIfNotExists($db, 'kitchen_tickets', 'idx_kitchen_tickets_tenant_order', 'tenant_id, order_id');

        $this->addIndexIfNotExists($db, 'kitchen_logs', 'idx_kitchen_logs_tenant_id', 'tenant_id');
        $this->addIndexIfNotExists($db, 'kitchen_logs', 'idx_kitchen_logs_tenant_order_item', 'tenant_id, order_item_id');

        $this->addIndexIfNotExists($db, 'reservation_tables', 'idx_reservation_tables_tenant_id', 'tenant_id');
        $this->addIndexIfNotExists($db, 'reservation_tables', 'idx_reservation_tables_tenant_reservation', 'tenant_id, reservation_id');
        $this->addIndexIfNotExists($db, 'reservation_tables', 'idx_reservation_tables_tenant_table', 'tenant_id, table_id');

        $this->addIndexIfNotExists($db, 'reservation_logs', 'idx_reservation_logs_tenant_id', 'tenant_id');
        $this->addIndexIfNotExists($db, 'reservation_logs', 'idx_reservation_logs_tenant_reservation', 'tenant_id, reservation_id');

        $this->addIndexIfNotExists($db, 'promotion_redemptions', 'idx_promotion_redemptions_tenant_id', 'tenant_id');
        $this->addIndexIfNotExists($db, 'promotion_redemptions', 'idx_promotion_redemptions_tenant_promotion', 'tenant_id, promotion_id');
    }

    public function down()
    {
    }

    protected function addIndexIfNotExists($db, string $table, string $indexName, string $columns): void
    {
        if (! $db->tableExists($table)) {
            return;
        }

        if (! $this->indexExists($db, $table, $indexName)) {
            try {
                $db->query("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ({$columns})");
            } catch (\Throwable $e) {
            }
        }
    }

    protected function indexExists($db, string $table, string $indexName): bool
    {
        $rows = $db->query("SHOW INDEX FROM `{$table}`")->getResultArray();

        foreach ($rows as $row) {
            if (($row['Key_name'] ?? '') === $indexName) {
                return true;
            }
        }

        return false;
    }
}