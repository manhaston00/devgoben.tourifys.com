<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrderMergesTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('order_merges')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'tenant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'branch_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'source_order_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'target_order_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'source_table_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'target_table_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'merged_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('branch_id');
        $this->forge->addKey('source_order_id');
        $this->forge->addKey('target_order_id');
        $this->forge->addKey('source_table_id');
        $this->forge->addKey('target_table_id');
        $this->forge->addKey('merged_by');

        $this->forge->createTable('order_merges', true);
    }

    public function down()
    {
        $this->forge->dropTable('order_merges', true);
    }
}