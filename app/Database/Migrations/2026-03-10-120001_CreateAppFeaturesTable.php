<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppFeaturesTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('app_features')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'feature_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'feature_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'module_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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
        $this->forge->addUniqueKey('feature_key');
        $this->forge->createTable('app_features', true);
    }

    public function down()
    {
        $this->forge->dropTable('app_features', true);
    }
}