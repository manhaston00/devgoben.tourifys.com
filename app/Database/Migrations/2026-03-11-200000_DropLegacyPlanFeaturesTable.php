<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropLegacyPlanFeaturesTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('plan_features')) {
            $this->forge->dropTable('plan_features', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('plan_features')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => false,
                'auto_increment' => true,
            ],
            'plan_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'feature_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'feature_value' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('plan_id');
        $this->forge->createTable('plan_features', true);
    }
}