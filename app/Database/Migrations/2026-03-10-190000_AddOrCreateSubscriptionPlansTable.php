<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrCreateSubscriptionPlansTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('subscription_plans')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false,
                ],
                'name_th' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'name_en' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'description_th' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'description_en' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'plan_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'monthly',
                    'null'       => false,
                ],
                'duration_days' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'price' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0,
                    'null'       => false,
                ],
                'currency' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'default'    => 'THB',
                    'null'       => false,
                ],
                'max_branches' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                    'null'       => false,
                ],
                'max_users' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 5,
                    'null'       => false,
                ],
                'features_json' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
                'sort_order' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                    'null'       => false,
                ],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'null'       => false,
                ],
                'is_public' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'null'       => false,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->createTable('subscription_plans', true);

            $this->createIndexIfNotExists('subscription_plans', 'idx_subscription_plans_code', 'code');
            $this->createIndexIfNotExists('subscription_plans', 'idx_subscription_plans_plan_type', 'plan_type');
            $this->createIndexIfNotExists('subscription_plans', 'idx_subscription_plans_is_active', 'is_active');
            $this->createIndexIfNotExists('subscription_plans', 'idx_subscription_plans_deleted_at', 'deleted_at');
            $this->createIndexIfNotExists('subscription_plans', 'idx_subscription_plans_sort_order', 'sort_order');
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('code', 'subscription_plans')) {
            $fields['code'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
                'after'      => 'id',
            ];
        }

        if (! $this->db->fieldExists('name_th', 'subscription_plans')) {
            $fields['name_th'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
                'after'      => 'code',
            ];
        }

        if (! $this->db->fieldExists('name_en', 'subscription_plans')) {
            $fields['name_en'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'name_th',
            ];
        }

        if (! $this->db->fieldExists('description_th', 'subscription_plans')) {
            $fields['description_th'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'name_en',
            ];
        }

        if (! $this->db->fieldExists('description_en', 'subscription_plans')) {
            $fields['description_en'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'description_th',
            ];
        }

        if (! $this->db->fieldExists('plan_type', 'subscription_plans')) {
            $fields['plan_type'] = [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'monthly',
                'null'       => false,
                'after'      => 'description_en',
            ];
        }

        if (! $this->db->fieldExists('duration_days', 'subscription_plans')) {
            $fields['duration_days'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'plan_type',
            ];
        }

        if (! $this->db->fieldExists('price', 'subscription_plans')) {
            $fields['price'] = [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
                'null'       => false,
                'after'      => 'duration_days',
            ];
        }

        if (! $this->db->fieldExists('currency', 'subscription_plans')) {
            $fields['currency'] = [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => 'THB',
                'null'       => false,
                'after'      => 'price',
            ];
        }

        if (! $this->db->fieldExists('max_branches', 'subscription_plans')) {
            $fields['max_branches'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
                'null'       => false,
                'after'      => 'currency',
            ];
        }

        if (! $this->db->fieldExists('max_users', 'subscription_plans')) {
            $fields['max_users'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 5,
                'null'       => false,
                'after'      => 'max_branches',
            ];
        }

        if (! $this->db->fieldExists('features_json', 'subscription_plans')) {
            $fields['features_json'] = [
                'type' => 'LONGTEXT',
                'null' => true,
                'after' => 'max_users',
            ];
        }

        if (! $this->db->fieldExists('sort_order', 'subscription_plans')) {
            $fields['sort_order'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'null'       => false,
                'after'      => 'features_json',
            ];
        }

        if (! $this->db->fieldExists('is_active', 'subscription_plans')) {
            $fields['is_active'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'after'      => 'sort_order',
            ];
        }

        if (! $this->db->fieldExists('is_public', 'subscription_plans')) {
            $fields['is_public'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'after'      => 'is_active',
            ];
        }

        if (! $this->db->fieldExists('created_at', 'subscription_plans')) {
            $fields['created_at'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }

        if (! $this->db->fieldExists('updated_at', 'subscription_plans')) {
            $fields['updated_at'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }

        if (! $this->db->fieldExists('deleted_at', 'subscription_plans')) {
            $fields['deleted_at'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }

        if (! empty($fields)) {
            $this->forge->addColumn('subscription_plans', $fields);
        }

        $this->createIndexIfNotExists('subscription_plans', 'idx_subscription_plans_code', 'code');
        $this->createIndexIfNotExists('subscription_plans', 'idx_subscription_plans_plan_type', 'plan_type');
        $this->createIndexIfNotExists('subscription_plans', 'idx_subscription_plans_is_active', 'is_active');
        $this->createIndexIfNotExists('subscription_plans', 'idx_subscription_plans_deleted_at', 'deleted_at');
        $this->createIndexIfNotExists('subscription_plans', 'idx_subscription_plans_sort_order', 'sort_order');
    }

    public function down()
    {
        // กันพลาด: step นี้ไม่ drop table เพราะเป็น migration เสริมบน DB ล่าสุด
    }

    protected function createIndexIfNotExists(string $table, string $indexName, string $column): void
    {
        if (! $this->indexExists($table, $indexName)) {
            $this->db->query("CREATE INDEX `{$indexName}` ON `{$table}` (`{$column}`)");
        }
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $database = $this->db->getDatabase();

        $sql = "SELECT COUNT(1) AS total
                FROM information_schema.statistics
                WHERE table_schema = ?
                  AND table_name = ?
                  AND index_name = ?";

        $row = $this->db->query($sql, [$database, $table, $indexName])->getRowArray();

        return (int) ($row['total'] ?? 0) > 0;
    }
}