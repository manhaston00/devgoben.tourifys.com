<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSplitBillFoundation extends Migration
{
    public function up()
    {
        $this->ensureOrdersStatusSupportsMerged();
        $this->addOrdersSplitColumns();
        $this->addOrderItemsSplitColumns();
        $this->createOrderSplitSessionsTable();
        $this->createOrderSplitSessionItemsTable();
        $this->addSplitIndexes();
        $this->addSplitForeignKeys();
    }

    public function down()
    {
        $this->dropSplitForeignKeys();

        if ($this->db->tableExists('order_split_session_items')) {
            $this->forge->dropTable('order_split_session_items', true);
        }

        if ($this->db->tableExists('order_split_sessions')) {
            $this->forge->dropTable('order_split_sessions', true);
        }

        if ($this->db->tableExists('order_items')) {
            $this->dropColumnIfExists('order_items', 'moved_qty');
            $this->dropColumnIfExists('order_items', 'source_qty_before_split');
            $this->dropColumnIfExists('order_items', 'split_to_order_id');
            $this->dropColumnIfExists('order_items', 'split_from_order_id');
            $this->dropColumnIfExists('order_items', 'split_session_id');
            $this->dropColumnIfExists('order_items', 'split_root_item_id');
            $this->dropColumnIfExists('order_items', 'parent_order_item_id');
        }

        if ($this->db->tableExists('orders')) {
            $this->dropColumnIfExists('orders', 'is_split_locked');
            $this->dropColumnIfExists('orders', 'split_group_code');
            $this->dropColumnIfExists('orders', 'split_no');
            $this->dropColumnIfExists('orders', 'bill_type');
            $this->dropColumnIfExists('orders', 'split_from_order_id');
            $this->dropColumnIfExists('orders', 'split_root_order_id');
            $this->dropColumnIfExists('orders', 'parent_order_id');
        }

        $this->revertOrdersStatusWithoutMergedWhenSafe();
    }

    protected function ensureOrdersStatusSupportsMerged(): void
    {
        if (! $this->db->tableExists('orders') || ! $this->db->fieldExists('status', 'orders')) {
            return;
        }

        $driver = strtolower((string) $this->db->DBDriver);
        if (! in_array($driver, ['mysqli', 'mysqli', 'pdo', 'pdo_mysql'], true)) {
            return;
        }

        $row = $this->db->query("SHOW COLUMNS FROM `orders` LIKE 'status'")->getRowArray();
        $type = strtolower((string) ($row['Type'] ?? ''));

        if ($type !== '' && strpos($type, "'merged'") !== false) {
            return;
        }

        $this->db->query("ALTER TABLE `orders` MODIFY `status` ENUM('open','billing','paid','cancel','merged') NOT NULL DEFAULT 'open'");
    }

    protected function revertOrdersStatusWithoutMergedWhenSafe(): void
    {
        if (! $this->db->tableExists('orders') || ! $this->db->fieldExists('status', 'orders')) {
            return;
        }

        $driver = strtolower((string) $this->db->DBDriver);
        if (! in_array($driver, ['mysqli', 'mysqli', 'pdo', 'pdo_mysql'], true)) {
            return;
        }

        $mergedCount = 0;
        try {
            $mergedCount = (int) ($this->db->table('orders')->where('status', 'merged')->countAllResults() ?? 0);
        } catch (\Throwable $e) {
            $mergedCount = 0;
        }

        if ($mergedCount > 0) {
            return;
        }

        $row = $this->db->query("SHOW COLUMNS FROM `orders` LIKE 'status'")->getRowArray();
        $type = strtolower((string) ($row['Type'] ?? ''));

        if ($type === '' || strpos($type, "'merged'") === false) {
            return;
        }

        $this->db->query("ALTER TABLE `orders` MODIFY `status` ENUM('open','billing','paid','cancel') NOT NULL DEFAULT 'open'");
    }

    protected function addOrdersSplitColumns(): void
    {
        if (! $this->db->tableExists('orders')) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('parent_order_id', 'orders')) {
            $fields['parent_order_id'] = [
                'type'     => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null'     => true,
                'after'    => 'merged_into_order_id',
            ];
        }

        if (! $this->db->fieldExists('split_root_order_id', 'orders')) {
            $fields['split_root_order_id'] = [
                'type'     => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null'     => true,
                'after'    => 'parent_order_id',
            ];
        }

        if (! $this->db->fieldExists('split_from_order_id', 'orders')) {
            $fields['split_from_order_id'] = [
                'type'     => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null'     => true,
                'after'    => 'split_root_order_id',
            ];
        }

        if (! $this->db->fieldExists('bill_type', 'orders')) {
            $fields['bill_type'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'normal',
                'after'      => 'status',
            ];
        }

        if (! $this->db->fieldExists('split_no', 'orders')) {
            $fields['split_no'] = [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'bill_type',
            ];
        }

        if (! $this->db->fieldExists('split_group_code', 'orders')) {
            $fields['split_group_code'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'split_no',
            ];
        }

        if (! $this->db->fieldExists('is_split_locked', 'orders')) {
            $fields['is_split_locked'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'split_group_code',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('orders', $fields);
        }
    }

    protected function addOrderItemsSplitColumns(): void
    {
        if (! $this->db->tableExists('order_items')) {
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('parent_order_item_id', 'order_items')) {
            $fields['parent_order_item_id'] = [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'order_id',
            ];
        }

        if (! $this->db->fieldExists('split_root_item_id', 'order_items')) {
            $fields['split_root_item_id'] = [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'parent_order_item_id',
            ];
        }

        if (! $this->db->fieldExists('split_session_id', 'order_items')) {
            $fields['split_session_id'] = [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'split_root_item_id',
            ];
        }

        if (! $this->db->fieldExists('split_from_order_id', 'order_items')) {
            $fields['split_from_order_id'] = [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'split_session_id',
            ];
        }

        if (! $this->db->fieldExists('split_to_order_id', 'order_items')) {
            $fields['split_to_order_id'] = [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'split_from_order_id',
            ];
        }

        if (! $this->db->fieldExists('source_qty_before_split', 'order_items')) {
            $fields['source_qty_before_split'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'qty',
            ];
        }

        if (! $this->db->fieldExists('moved_qty', 'order_items')) {
            $fields['moved_qty'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'source_qty_before_split',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('order_items', $fields);
        }
    }

    protected function createOrderSplitSessionsTable(): void
    {
        if ($this->db->tableExists('order_split_sessions')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'session_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'root_order_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'source_order_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'target_order_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'split_no' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'draft',
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'requested_by' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'confirmed_by' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'cancelled_by' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'requested_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'confirmed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'cancelled_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addUniqueKey('session_code', 'uniq_order_split_sessions_session_code');
        $this->forge->addKey(['tenant_id', 'branch_id', 'root_order_id'], false, false, 'idx_oss_tenant_branch_root');
        $this->forge->addKey(['tenant_id', 'branch_id', 'source_order_id'], false, false, 'idx_oss_tenant_branch_source');
        $this->forge->addKey(['tenant_id', 'branch_id', 'target_order_id'], false, false, 'idx_oss_tenant_branch_target');
        $this->forge->addKey(['tenant_id', 'branch_id', 'status'], false, false, 'idx_oss_tenant_branch_status');
        $this->forge->createTable('order_split_sessions', true);
    }

    protected function createOrderSplitSessionItemsTable(): void
    {
        if ($this->db->tableExists('order_split_session_items')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'session_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'tenant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'root_order_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'source_order_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'target_order_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'source_order_item_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'target_order_item_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'product_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'product_name_snapshot' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'unit_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'source_qty_before' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'moved_qty' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'source_qty_after' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'line_total' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'item_snapshot_json' => [
                'type' => 'LONGTEXT',
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
        $this->forge->addKey('session_id', false, false, 'idx_ossi_session_id');
        $this->forge->addKey(['tenant_id', 'branch_id', 'root_order_id'], false, false, 'idx_ossi_tenant_branch_root');
        $this->forge->addKey(['tenant_id', 'branch_id', 'source_order_id'], false, false, 'idx_ossi_tenant_branch_source');
        $this->forge->addKey(['tenant_id', 'branch_id', 'target_order_id'], false, false, 'idx_ossi_tenant_branch_target');
        $this->forge->addKey('source_order_item_id', false, false, 'idx_ossi_source_order_item_id');
        $this->forge->addKey('target_order_item_id', false, false, 'idx_ossi_target_order_item_id');
        $this->forge->createTable('order_split_session_items', true);
    }

    protected function addSplitIndexes(): void
    {
        if ($this->db->tableExists('orders')) {
            $this->addIndexIfMissing('orders', 'idx_orders_tenant_branch_split_root', 'ALTER TABLE `orders` ADD KEY `idx_orders_tenant_branch_split_root` (`tenant_id`,`branch_id`,`split_root_order_id`)');
            $this->addIndexIfMissing('orders', 'idx_orders_tenant_branch_parent_order', 'ALTER TABLE `orders` ADD KEY `idx_orders_tenant_branch_parent_order` (`tenant_id`,`branch_id`,`parent_order_id`)');
            $this->addIndexIfMissing('orders', 'idx_orders_tenant_branch_split_group_code', 'ALTER TABLE `orders` ADD KEY `idx_orders_tenant_branch_split_group_code` (`tenant_id`,`branch_id`,`split_group_code`)');
            $this->addIndexIfMissing('orders', 'idx_orders_tenant_branch_bill_type', 'ALTER TABLE `orders` ADD KEY `idx_orders_tenant_branch_bill_type` (`tenant_id`,`branch_id`,`bill_type`)');
            $this->addIndexIfMissing('orders', 'idx_orders_tenant_branch_status_bill_type', 'ALTER TABLE `orders` ADD KEY `idx_orders_tenant_branch_status_bill_type` (`tenant_id`,`branch_id`,`status`,`bill_type`)');
        }

        if ($this->db->tableExists('order_items')) {
            $this->addIndexIfMissing('order_items', 'idx_order_items_tenant_order_split_session', 'ALTER TABLE `order_items` ADD KEY `idx_order_items_tenant_order_split_session` (`tenant_id`,`order_id`,`split_session_id`)');
            $this->addIndexIfMissing('order_items', 'idx_order_items_tenant_order_parent_item', 'ALTER TABLE `order_items` ADD KEY `idx_order_items_tenant_order_parent_item` (`tenant_id`,`order_id`,`parent_order_item_id`)');
            $this->addIndexIfMissing('order_items', 'idx_order_items_tenant_order_split_root_item', 'ALTER TABLE `order_items` ADD KEY `idx_order_items_tenant_order_split_root_item` (`tenant_id`,`order_id`,`split_root_item_id`)');
        }
    }

    protected function addSplitForeignKeys(): void
    {
        if ($this->db->tableExists('orders')) {
            $this->addForeignKeyIfMissing('orders', 'fk_orders_parent_order_id', 'ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_parent_order_id` FOREIGN KEY (`parent_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
            $this->addForeignKeyIfMissing('orders', 'fk_orders_split_root_order_id', 'ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_split_root_order_id` FOREIGN KEY (`split_root_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
            $this->addForeignKeyIfMissing('orders', 'fk_orders_split_from_order_id', 'ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_split_from_order_id` FOREIGN KEY (`split_from_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        }

        if ($this->db->tableExists('order_items')) {
            $this->addForeignKeyIfMissing('order_items', 'fk_order_items_parent_order_item_id', 'ALTER TABLE `order_items` ADD CONSTRAINT `fk_order_items_parent_order_item_id` FOREIGN KEY (`parent_order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
            $this->addForeignKeyIfMissing('order_items', 'fk_order_items_split_session_id', 'ALTER TABLE `order_items` ADD CONSTRAINT `fk_order_items_split_session_id` FOREIGN KEY (`split_session_id`) REFERENCES `order_split_sessions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
            $this->addForeignKeyIfMissing('order_items', 'fk_order_items_split_from_order_id', 'ALTER TABLE `order_items` ADD CONSTRAINT `fk_order_items_split_from_order_id` FOREIGN KEY (`split_from_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
            $this->addForeignKeyIfMissing('order_items', 'fk_order_items_split_to_order_id', 'ALTER TABLE `order_items` ADD CONSTRAINT `fk_order_items_split_to_order_id` FOREIGN KEY (`split_to_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        }

        if ($this->db->tableExists('order_split_sessions')) {
            $this->addForeignKeyIfMissing('order_split_sessions', 'fk_order_split_sessions_root_order_id', 'ALTER TABLE `order_split_sessions` ADD CONSTRAINT `fk_order_split_sessions_root_order_id` FOREIGN KEY (`root_order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE');
            $this->addForeignKeyIfMissing('order_split_sessions', 'fk_order_split_sessions_source_order_id', 'ALTER TABLE `order_split_sessions` ADD CONSTRAINT `fk_order_split_sessions_source_order_id` FOREIGN KEY (`source_order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE');
            $this->addForeignKeyIfMissing('order_split_sessions', 'fk_order_split_sessions_target_order_id', 'ALTER TABLE `order_split_sessions` ADD CONSTRAINT `fk_order_split_sessions_target_order_id` FOREIGN KEY (`target_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        }

        if ($this->db->tableExists('order_split_session_items')) {
            $this->addForeignKeyIfMissing('order_split_session_items', 'fk_order_split_session_items_session_id', 'ALTER TABLE `order_split_session_items` ADD CONSTRAINT `fk_order_split_session_items_session_id` FOREIGN KEY (`session_id`) REFERENCES `order_split_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE');
            $this->addForeignKeyIfMissing('order_split_session_items', 'fk_order_split_session_items_source_order_id', 'ALTER TABLE `order_split_session_items` ADD CONSTRAINT `fk_order_split_session_items_source_order_id` FOREIGN KEY (`source_order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE');
            $this->addForeignKeyIfMissing('order_split_session_items', 'fk_order_split_session_items_target_order_id', 'ALTER TABLE `order_split_session_items` ADD CONSTRAINT `fk_order_split_session_items_target_order_id` FOREIGN KEY (`target_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
            $this->addForeignKeyIfMissing('order_split_session_items', 'fk_order_split_session_items_source_order_item_id', 'ALTER TABLE `order_split_session_items` ADD CONSTRAINT `fk_order_split_session_items_source_order_item_id` FOREIGN KEY (`source_order_item_id`) REFERENCES `order_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE');
            $this->addForeignKeyIfMissing('order_split_session_items', 'fk_order_split_session_items_target_order_item_id', 'ALTER TABLE `order_split_session_items` ADD CONSTRAINT `fk_order_split_session_items_target_order_item_id` FOREIGN KEY (`target_order_item_id`) REFERENCES `order_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        }
    }

    protected function dropSplitForeignKeys(): void
    {
        $this->dropForeignKeyIfExists('order_split_session_items', 'fk_order_split_session_items_target_order_item_id');
        $this->dropForeignKeyIfExists('order_split_session_items', 'fk_order_split_session_items_source_order_item_id');
        $this->dropForeignKeyIfExists('order_split_session_items', 'fk_order_split_session_items_target_order_id');
        $this->dropForeignKeyIfExists('order_split_session_items', 'fk_order_split_session_items_source_order_id');
        $this->dropForeignKeyIfExists('order_split_session_items', 'fk_order_split_session_items_session_id');

        $this->dropForeignKeyIfExists('order_split_sessions', 'fk_order_split_sessions_target_order_id');
        $this->dropForeignKeyIfExists('order_split_sessions', 'fk_order_split_sessions_source_order_id');
        $this->dropForeignKeyIfExists('order_split_sessions', 'fk_order_split_sessions_root_order_id');

        $this->dropForeignKeyIfExists('order_items', 'fk_order_items_split_to_order_id');
        $this->dropForeignKeyIfExists('order_items', 'fk_order_items_split_from_order_id');
        $this->dropForeignKeyIfExists('order_items', 'fk_order_items_split_session_id');
        $this->dropForeignKeyIfExists('order_items', 'fk_order_items_parent_order_item_id');

        $this->dropForeignKeyIfExists('orders', 'fk_orders_split_from_order_id');
        $this->dropForeignKeyIfExists('orders', 'fk_orders_split_root_order_id');
        $this->dropForeignKeyIfExists('orders', 'fk_orders_parent_order_id');
    }

    protected function dropColumnIfExists(string $table, string $column): void
    {
        if ($this->db->tableExists($table) && $this->db->fieldExists($column, $table)) {
            $this->forge->dropColumn($table, $column);
        }
    }

    protected function addIndexIfMissing(string $table, string $indexName, string $sql): void
    {
        if (! $this->db->tableExists($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        try {
            $this->db->query($sql);
        } catch (\Throwable $e) {
        }
    }

    protected function addForeignKeyIfMissing(string $table, string $constraintName, string $sql): void
    {
        if (! $this->db->tableExists($table) || $this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        try {
            $this->db->query($sql);
        } catch (\Throwable $e) {
        }
    }

    protected function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        if (! $this->db->tableExists($table) || ! $this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        try {
            $this->db->query(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $constraintName));
        } catch (\Throwable $e) {
        }
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $database = $this->db->getDatabase();
        $sql = 'SELECT COUNT(1) AS aggregate_count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?';
        $row = $this->db->query($sql, [$database, $table, $indexName])->getRowArray();

        return ((int) ($row['aggregate_count'] ?? 0)) > 0;
    }

    protected function foreignKeyExists(string $table, string $constraintName): bool
    {
        $database = $this->db->getDatabase();
        $sql = 'SELECT COUNT(1) AS aggregate_count FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_type = ? AND constraint_name = ?';
        $row = $this->db->query($sql, [$database, $table, 'FOREIGN KEY', $constraintName])->getRowArray();

        return ((int) ($row['aggregate_count'] ?? 0)) > 0;
    }
}
