<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropLegacyPlanFeatures extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('plan_features')) {
            $this->forge->dropTable('plan_features', true);
        }
    }

    public function down()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'auto_increment' => true
            ],
            'plan_id' => [
                'type' => 'INT'
            ],
            'feature_key' => [
                'type' => 'VARCHAR',
                'constraint' => 120
            ],
            'feature_value' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('plan_features');
    }
}