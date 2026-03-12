<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class HardenTenantUniqueIndexes extends Migration
{
    public function up()
    {
        // -----------------------------
        // branches.branch_code
        // จาก unique global -> unique ต่อ tenant
        // -----------------------------
        if ($this->db->tableExists('branches')) {
            if ($this->indexExists('branches', 'branch_code')) {
                $this->db->query('ALTER TABLE `branches` DROP INDEX `branch_code`');
            }

            if ($this->indexExists('branches', 'uniq_branches_tenant_branch_code')) {
                $this->db->query('ALTER TABLE `branches` DROP INDEX `uniq_branches_tenant_branch_code`');
            }

            if (! $this->indexExists('branches', 'idx_branches_tenant_id')) {
                $this->db->query('ALTER TABLE `branches` ADD INDEX `idx_branches_tenant_id` (`tenant_id`)');
            }

            $this->db->query('ALTER TABLE `branches` ADD UNIQUE KEY `uniq_branches_tenant_branch_code` (`tenant_id`, `branch_code`)');
        }

        // -----------------------------
        // users.username
        // ลบ unique ซ้ำซ้อน ให้เหลืออันเดียว
        // -----------------------------
        if ($this->db->tableExists('users')) {
            $uniqueIndexes = $this->getUniqueIndexesByColumn('users', 'username');

            // เก็บไว้ 1 ตัว ที่ชื่อ uniq_users_username ถ้ายังไม่มีให้สร้าง
            $keepName = 'uniq_users_username';

            if (! in_array($keepName, $uniqueIndexes, true)) {
                $this->db->query('ALTER TABLE `users` ADD UNIQUE KEY `uniq_users_username` (`username`)');
                $uniqueIndexes = $this->getUniqueIndexesByColumn('users', 'username');
            }

            foreach ($uniqueIndexes as $indexName) {
                if ($indexName !== $keepName) {
                    $this->db->query("ALTER TABLE `users` DROP INDEX `{$indexName}`");
                }
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('branches')) {
            if ($this->indexExists('branches', 'uniq_branches_tenant_branch_code')) {
                $this->db->query('ALTER TABLE `branches` DROP INDEX `uniq_branches_tenant_branch_code`');
            }

            if ($this->indexExists('branches', 'idx_branches_tenant_id')) {
                $this->db->query('ALTER TABLE `branches` DROP INDEX `idx_branches_tenant_id`');
            }

            if (! $this->indexExists('branches', 'branch_code')) {
                $this->db->query('ALTER TABLE `branches` ADD UNIQUE KEY `branch_code` (`branch_code`)');
            }
        }

        if ($this->db->tableExists('users')) {
            if (! $this->indexExists('users', 'username')) {
                $this->db->query('ALTER TABLE `users` ADD UNIQUE KEY `username` (`username`)');
            }
        }
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $rows = $this->db->query(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = " . $this->db->escape($indexName)
        )->getResultArray();

        return ! empty($rows);
    }

    protected function getUniqueIndexesByColumn(string $table, string $column): array
    {
        $rows = $this->db->query("SHOW INDEX FROM `{$table}`")->getResultArray();

        $result = [];

        foreach ($rows as $row) {
            $isUnique   = ((int) ($row['Non_unique'] ?? 1) === 0);
            $columnName = (string) ($row['Column_name'] ?? '');
            $keyName    = (string) ($row['Key_name'] ?? '');

            if ($isUnique && $columnName === $column && $keyName !== 'PRIMARY') {
                $result[] = $keyName;
            }
        }

        return array_values(array_unique($result));
    }
}