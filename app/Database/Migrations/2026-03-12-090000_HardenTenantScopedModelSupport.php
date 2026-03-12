<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class HardenTenantScopedModelSupport extends Migration
{
    public function up()
    {
        $this->hardenBranches();
        $this->hardenKitchenStations();
        $this->hardenProductQuickOptions();
        $this->hardenPromotions();
        $this->hardenQuickNotes();
    }

    public function down()
    {
        // intentionally no-op
    }

    protected function hardenBranches(): void
    {
        $table = 'branches';

        if (! $this->db->tableExists($table) || ! $this->db->fieldExists('tenant_id', $table)) {
            return;
        }

        $this->backfillSingleTenantTable($table);
        $this->ensureIndex($table, 'idx_branches_tenant_id', 'tenant_id');
        $this->tryAlterTenantIdNotNull($table);
    }

    protected function hardenKitchenStations(): void
    {
        $table = 'kitchen_stations';

        if (! $this->db->tableExists($table) || ! $this->db->fieldExists('tenant_id', $table)) {
            return;
        }

        if ($this->db->fieldExists('branch_id', $table) && $this->db->tableExists('branches')) {
            $this->db->query("
                UPDATE kitchen_stations ks
                INNER JOIN branches b ON b.id = ks.branch_id
                SET ks.tenant_id = b.tenant_id
                WHERE ks.tenant_id IS NULL
                  AND b.tenant_id IS NOT NULL
            ");
        }

        $this->backfillSingleTenantTable($table);
        $this->ensureIndex($table, 'idx_kitchen_stations_tenant_id', 'tenant_id');
        $this->tryAlterTenantIdNotNull($table);
    }

    protected function hardenProductQuickOptions(): void
    {
        $table = 'product_quick_options';

        if (! $this->db->tableExists($table) || ! $this->db->fieldExists('tenant_id', $table)) {
            return;
        }

        if ($this->db->tableExists('products')) {
            $this->db->query("
                UPDATE product_quick_options pqo
                INNER JOIN products p ON p.id = pqo.product_id
                SET pqo.tenant_id = p.tenant_id
                WHERE pqo.tenant_id IS NULL
                  AND p.tenant_id IS NOT NULL
            ");
        }

        if ($this->db->tableExists('categories')) {
            $this->db->query("
                UPDATE product_quick_options pqo
                INNER JOIN categories c ON c.id = pqo.category_id
                SET pqo.tenant_id = c.tenant_id
                WHERE pqo.tenant_id IS NULL
                  AND c.tenant_id IS NOT NULL
            ");
        }

        $this->backfillSingleTenantTable($table);
        $this->ensureIndex($table, 'idx_product_quick_options_tenant_id', 'tenant_id');
        $this->tryAlterTenantIdNotNull($table);
    }

    protected function hardenPromotions(): void
    {
        $table = 'promotions';

        if (! $this->db->tableExists($table) || ! $this->db->fieldExists('tenant_id', $table)) {
            return;
        }

        if ($this->db->fieldExists('branch_id', $table) && $this->db->tableExists('branches')) {
            $this->db->query("
                UPDATE promotions p
                INNER JOIN branches b ON b.id = p.branch_id
                SET p.tenant_id = b.tenant_id
                WHERE p.tenant_id IS NULL
                  AND b.tenant_id IS NOT NULL
            ");
        }

        $this->backfillSingleTenantTable($table);
        $this->ensureIndex($table, 'idx_promotions_tenant_id', 'tenant_id');
        $this->tryAlterTenantIdNotNull($table);
    }

    protected function hardenQuickNotes(): void
    {
        $table = 'quick_notes';

        if (! $this->db->tableExists($table) || ! $this->db->fieldExists('tenant_id', $table)) {
            return;
        }

        $this->backfillSingleTenantTable($table);
        $this->ensureIndex($table, 'idx_quick_notes_tenant_id', 'tenant_id');
        $this->tryAlterTenantIdNotNull($table);
    }

    protected function backfillSingleTenantTable(string $table): void
    {
        if (! $this->db->tableExists('tenants') || ! $this->db->fieldExists('tenant_id', $table)) {
            return;
        }

        $tenants = $this->db->table('tenants')
            ->select('id')
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        if (count($tenants) !== 1) {
            return;
        }

        $tenantId = (int) $tenants[0]['id'];

        $this->db->table($table)
            ->where('tenant_id', null)
            ->update(['tenant_id' => $tenantId]);
    }

    protected function tryAlterTenantIdNotNull(string $table): void
    {
        if (! $this->db->fieldExists('tenant_id', $table)) {
            return;
        }

        $nullCount = (int) $this->db->table($table)
            ->where('tenant_id', null)
            ->countAllResults();

        if ($nullCount > 0) {
            return;
        }

        if ($this->columnHasForeignKeyConstraint($table, 'tenant_id')) {
            return;
        }

        $column = $this->getColumnDefinition($table, 'tenant_id');

        if ($column === null) {
            return;
        }

        if (strtoupper($column['Null']) === 'NO') {
            return;
        }

        $type = strtoupper($column['Type']);
        $extra = strtoupper($column['Extra']);

        $sql = "ALTER TABLE `{$table}` MODIFY `tenant_id` {$type} NOT NULL";

        if (strpos($extra, 'AUTO_INCREMENT') !== false) {
            $sql .= ' AUTO_INCREMENT';
        }

        $this->db->query($sql);
    }

    protected function ensureIndex(string $table, string $indexName, string $column): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$column}`)");
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $rows = $this->db->query(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'"
        )->getResultArray();

        return ! empty($rows);
    }

    protected function columnHasForeignKeyConstraint(string $table, string $column): bool
    {
        $database = $this->db->database;

        $sql = "
            SELECT COUNT(*) AS total
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ";

        $row = $this->db->query($sql, [$database, $table, $column])->getRowArray();

        return (int) ($row['total'] ?? 0) > 0;
    }

    protected function getColumnDefinition(string $table, string $column): ?array
    {
        $rows = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'")
            ->getResultArray();

        return $rows[0] ?? null;
    }
}