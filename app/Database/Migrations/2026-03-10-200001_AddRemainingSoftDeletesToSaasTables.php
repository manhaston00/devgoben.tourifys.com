<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRemainingSoftDeletesToSaasTables extends Migration
{
    protected function tableExists(string $table): bool
    {
        return in_array($table, $this->db->listTables(), true);
    }

    protected function columnExists(string $table, string $column): bool
    {
        if (! $this->tableExists($table)) {
            return false;
        }

        $dbName = $this->db->database;

        $sql = "
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ";

        $row = $this->db->query($sql, [$dbName, $table, $column])->getRowArray();

        return (int) ($row['total'] ?? 0) > 0;
    }

    protected function addDeletedAtIfMissing(string $table): void
    {
        if (! $this->tableExists($table)) {
            return;
        }

        if ($this->columnExists($table, 'deleted_at')) {
            return;
        }

        $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL");
    }

    public function up()
    {
        $tables = [
            'app_features',
            'subscription_logs',
            'subscription_invoices',
            'subscription_plan_features',
        ];

        foreach ($tables as $table) {
            $this->addDeletedAtIfMissing($table);
        }
    }

    public function down()
    {
        // กันข้อมูลหาย ไม่ rollback column ออก
    }
}