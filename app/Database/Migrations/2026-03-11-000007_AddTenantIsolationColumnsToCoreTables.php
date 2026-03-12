<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTenantIsolationColumnsToCoreTables extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        $targets = [
            'categories'        => 'id',
            'products'          => 'id',
            'zones'             => 'branch_id',
            'restaurant_tables' => 'branch_id',
            'orders'            => 'branch_id',
            'reservations'      => 'branch_id',
        ];

        foreach ($targets as $table => $after) {
            if (! $db->fieldExists('tenant_id', $table)) {
                $this->forge->addColumn($table, [
                    'tenant_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => $after,
                    ],
                ]);
            }
        }

        // -----------------------------
        // Backfill from branches
        // -----------------------------
        if ($db->fieldExists('tenant_id', 'zones') && $db->tableExists('branches')) {
            $db->query("
                UPDATE zones z
                INNER JOIN branches b ON b.id = z.branch_id
                SET z.tenant_id = b.tenant_id
                WHERE z.tenant_id IS NULL
            ");
        }

        if ($db->fieldExists('tenant_id', 'restaurant_tables') && $db->tableExists('branches')) {
            $db->query("
                UPDATE restaurant_tables t
                INNER JOIN branches b ON b.id = t.branch_id
                SET t.tenant_id = b.tenant_id
                WHERE t.tenant_id IS NULL
            ");
        }

        if ($db->fieldExists('tenant_id', 'orders') && $db->tableExists('branches')) {
            $db->query("
                UPDATE orders o
                INNER JOIN branches b ON b.id = o.branch_id
                SET o.tenant_id = b.tenant_id
                WHERE o.tenant_id IS NULL
            ");
        }

        if ($db->fieldExists('tenant_id', 'reservations') && $db->tableExists('branches')) {
            $db->query("
                UPDATE reservations r
                INNER JOIN branches b ON b.id = r.branch_id
                SET r.tenant_id = b.tenant_id
                WHERE r.tenant_id IS NULL
            ");
        }

        // -----------------------------
        // categories / products
        // ตอนนี้ระบบเดิมมี tenant เดียว จึงย้าย global data ไป tenant 1
        // -----------------------------
        if ($db->fieldExists('tenant_id', 'categories')) {
            $db->query("
                UPDATE categories
                SET tenant_id = 1
                WHERE tenant_id IS NULL
            ");
        }

        if ($db->fieldExists('tenant_id', 'products')) {
            if ($db->fieldExists('tenant_id', 'categories')) {
                $db->query("
                    UPDATE products p
                    INNER JOIN categories c ON c.id = p.category_id
                    SET p.tenant_id = c.tenant_id
                    WHERE p.tenant_id IS NULL
                ");
            }

            $db->query("
                UPDATE products
                SET tenant_id = 1
                WHERE tenant_id IS NULL
            ");
        }

        // -----------------------------
        // Indexes
        // -----------------------------
        $indexMap = [
            'categories'        => 'idx_categories_tenant',
            'products'          => 'idx_products_tenant',
            'zones'             => 'idx_zones_tenant',
            'restaurant_tables' => 'idx_restaurant_tables_tenant',
            'orders'            => 'idx_orders_tenant',
            'reservations'      => 'idx_reservations_tenant',
        ];

        foreach ($indexMap as $table => $indexName) {
            try {
                $db->query("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`tenant_id`)");
            } catch (\Throwable $e) {
                // ignore if exists
            }
        }

        // -----------------------------
        // Foreign keys
        // -----------------------------
        $fkMap = [
            'categories'        => 'fk_categories_tenant',
            'products'          => 'fk_products_tenant',
            'zones'             => 'fk_zones_tenant',
            'restaurant_tables' => 'fk_restaurant_tables_tenant',
            'orders'            => 'fk_orders_tenant',
            'reservations'      => 'fk_reservations_tenant',
        ];

        foreach ($fkMap as $table => $fkName) {
            try {
                $db->query("
                    ALTER TABLE `{$table}`
                    ADD CONSTRAINT `{$fkName}`
                    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                ");
            } catch (\Throwable $e) {
                // ignore if exists
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        $fkMap = [
            'categories'        => 'fk_categories_tenant',
            'products'          => 'fk_products_tenant',
            'zones'             => 'fk_zones_tenant',
            'restaurant_tables' => 'fk_restaurant_tables_tenant',
            'orders'            => 'fk_orders_tenant',
            'reservations'      => 'fk_reservations_tenant',
        ];

        foreach ($fkMap as $table => $fkName) {
            try {
                $db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $tables = [
            'categories',
            'products',
            'zones',
            'restaurant_tables',
            'orders',
            'reservations',
        ];

        foreach ($tables as $table) {
            if ($db->fieldExists('tenant_id', $table)) {
                $this->forge->dropColumn($table, 'tenant_id');
            }
        }
    }
}