<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TenantScopeKitchenStationsAndPromotions extends Migration
{
    public function up()
    {
        $this->scopeKitchenStations();
        $this->scopePromotions();
    }

    public function down()
    {
        // promotions
        if ($this->db->tableExists('promotions')) {
            if ($this->indexExists('promotions', 'uniq_promotions_tenant_code')) {
                $this->db->query('ALTER TABLE `promotions` DROP INDEX `uniq_promotions_tenant_code`');
            }

            if ($this->indexExists('promotions', 'idx_promotions_tenant_id')) {
                $this->db->query('ALTER TABLE `promotions` DROP INDEX `idx_promotions_tenant_id`');
            }

            if ($this->db->fieldExists('tenant_id', 'promotions')) {
                $this->forge->dropColumn('promotions', 'tenant_id');
            }

            if ($this->db->fieldExists('code', 'promotions') && ! $this->indexExists('promotions', 'code')) {
                try {
                    $this->db->query('ALTER TABLE `promotions` ADD UNIQUE KEY `code` (`code`)');
                } catch (\Throwable $e) {
                }
            }
        }

        // kitchen_stations
        if ($this->db->tableExists('kitchen_stations')) {
            if ($this->indexExists('kitchen_stations', 'idx_kitchen_stations_tenant_id')) {
                $this->db->query('ALTER TABLE `kitchen_stations` DROP INDEX `idx_kitchen_stations_tenant_id`');
            }

            if ($this->db->fieldExists('tenant_id', 'kitchen_stations')) {
                $this->forge->dropColumn('kitchen_stations', 'tenant_id');
            }
        }
    }

    protected function scopeKitchenStations(): void
    {
        if (! $this->db->tableExists('kitchen_stations')) {
            return;
        }

        if (! $this->db->fieldExists('tenant_id', 'kitchen_stations')) {
            $this->forge->addColumn('kitchen_stations', [
                'tenant_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'id',
                ],
            ]);
        }

        if (! $this->indexExists('kitchen_stations', 'idx_kitchen_stations_tenant_id')) {
            $this->db->query('ALTER TABLE `kitchen_stations` ADD INDEX `idx_kitchen_stations_tenant_id` (`tenant_id`)');
        }

        // backfill จาก branch_id ถ้ามี
        if ($this->db->fieldExists('branch_id', 'kitchen_stations') && $this->db->tableExists('branches')) {
            $sql = "
                UPDATE `kitchen_stations` ks
                INNER JOIN `branches` b ON b.id = ks.branch_id
                SET ks.tenant_id = b.tenant_id
                WHERE ks.tenant_id IS NULL
            ";
            $this->db->query($sql);
        }

        // ถ้ายัง null แต่ระบบมี tenant เดียว ให้ assign tenant เดียวนั้น
        $tenantCount = 0;
        if ($this->db->tableExists('tenants')) {
            $tenantCount = (int) $this->db->table('tenants')
                ->where('deleted_at', null)
                ->countAllResults();
        }

        if ($tenantCount === 1) {
            $tenant = $this->db->table('tenants')
                ->select('id')
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            $tenantId = (int) ($tenant['id'] ?? 0);

            if ($tenantId > 0) {
                $this->db->table('kitchen_stations')
                    ->where('tenant_id', null)
                    ->update(['tenant_id' => $tenantId]);
            }
        }
    }

    protected function scopePromotions(): void
    {
        if (! $this->db->tableExists('promotions')) {
            return;
        }

        if (! $this->db->fieldExists('tenant_id', 'promotions')) {
            $this->forge->addColumn('promotions', [
                'tenant_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'id',
                ],
            ]);
        }

        if (! $this->indexExists('promotions', 'idx_promotions_tenant_id')) {
            $this->db->query('ALTER TABLE `promotions` ADD INDEX `idx_promotions_tenant_id` (`tenant_id`)');
        }

        // ลบ unique code แบบ global ถ้ามี
        if ($this->db->fieldExists('code', 'promotions') && $this->indexExists('promotions', 'code')) {
            $this->db->query('ALTER TABLE `promotions` DROP INDEX `code`');
        }

        if ($this->indexExists('promotions', 'uniq_promotions_tenant_code')) {
            $this->db->query('ALTER TABLE `promotions` DROP INDEX `uniq_promotions_tenant_code`');
        }

        // backfill tenant_id
        if ($this->db->fieldExists('branch_id', 'promotions') && $this->db->tableExists('branches')) {
            $sql = "
                UPDATE `promotions` p
                INNER JOIN `branches` b ON b.id = p.branch_id
                SET p.tenant_id = b.tenant_id
                WHERE p.tenant_id IS NULL
            ";
            $this->db->query($sql);
        }

        if ($this->db->fieldExists('created_by', 'promotions') && $this->db->tableExists('users')) {
            $sql = "
                UPDATE `promotions` p
                INNER JOIN `users` u ON u.id = p.created_by
                SET p.tenant_id = u.tenant_id
                WHERE p.tenant_id IS NULL
            ";
            $this->db->query($sql);
        }

        // ถ้ายัง null แต่มี tenant เดียว
        $tenantCount = 0;
        if ($this->db->tableExists('tenants')) {
            $tenantCount = (int) $this->db->table('tenants')
                ->where('deleted_at', null)
                ->countAllResults();
        }

        if ($tenantCount === 1) {
            $tenant = $this->db->table('tenants')
                ->select('id')
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            $tenantId = (int) ($tenant['id'] ?? 0);

            if ($tenantId > 0) {
                $this->db->table('promotions')
                    ->where('tenant_id', null)
                    ->update(['tenant_id' => $tenantId]);
            }
        }

        if ($this->db->fieldExists('code', 'promotions')) {
            $this->db->query('ALTER TABLE `promotions` ADD UNIQUE KEY `uniq_promotions_tenant_code` (`tenant_id`, `code`)');
        }
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $rows = $this->db->query(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = " . $this->db->escape($indexName)
        )->getResultArray();

        return ! empty($rows);
    }
}