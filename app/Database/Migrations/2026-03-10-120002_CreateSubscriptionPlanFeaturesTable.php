<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubscriptionPlanFeaturesTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('subscription_plan_features')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'plan_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'feature_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'is_enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'limit_value' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
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
        $this->forge->addKey(['plan_id']);
        $this->forge->addKey(['feature_key']);
        $this->forge->addUniqueKey(['plan_id', 'feature_key'], 'uniq_plan_feature');
        $this->forge->createTable('subscription_plan_features', true);

        if ($this->db->tableExists('subscription_plans')) {
            $this->db->query("
                ALTER TABLE subscription_plan_features
                ADD CONSTRAINT fk_spf_plan_id
                FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
                ON DELETE CASCADE ON UPDATE CASCADE
            ");
        }
    }

    public function down()
    {
        if ($this->db->tableExists('subscription_plan_features')) {
            try {
                $this->db->query("ALTER TABLE subscription_plan_features DROP FOREIGN KEY fk_spf_plan_id");
            } catch (\Throwable $e) {
            }
        }

        $this->forge->dropTable('subscription_plan_features', true);
    }
}