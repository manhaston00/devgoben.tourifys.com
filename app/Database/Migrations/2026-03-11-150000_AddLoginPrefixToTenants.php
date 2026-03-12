<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLoginPrefixToTenants extends Migration
{
    protected function hasIndex(string $table, string $indexName): bool
    {
        $rows = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName])->getResultArray();
        return ! empty($rows);
    }

    public function up()
    {
        if (! $this->db->tableExists('tenants')) {
            return;
        }

        if (! $this->db->fieldExists('login_prefix', 'tenants')) {
            $this->forge->addColumn('tenants', [
                'login_prefix' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'tenant_code',
                ],
            ]);
        }

        // backfill จาก tenant_code เดิม
        $this->db->query("
            UPDATE tenants
            SET login_prefix = tenant_code
            WHERE (login_prefix IS NULL OR login_prefix = '')
        ");

        if (! $this->hasIndex('tenants', 'uniq_tenants_login_prefix')) {
            try {
                $this->db->query("ALTER TABLE `tenants` ADD UNIQUE KEY `uniq_tenants_login_prefix` (`login_prefix`)");
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('tenants')) {
            return;
        }

        if ($this->hasIndex('tenants', 'uniq_tenants_login_prefix')) {
            try {
                $this->db->query("ALTER TABLE `tenants` DROP INDEX `uniq_tenants_login_prefix`");
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if ($this->db->fieldExists('login_prefix', 'tenants')) {
            $this->forge->dropColumn('tenants', 'login_prefix');
        }
    }
}