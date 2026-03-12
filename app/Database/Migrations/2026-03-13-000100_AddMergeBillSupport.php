<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMergeBillSupport extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('merged_into_order_id', 'orders')) {
            $this->forge->addColumn('orders', [
                'merged_into_order_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'status',
                ],
            ]);
        }

        if (! $this->db->tableExists('order_merges')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 10,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'tenant_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'branch_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'source_order_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'target_order_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'source_table_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'target_table_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'merged_by' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
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
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'branch_id', 'source_order_id']);
            $this->forge->addKey(['tenant_id', 'branch_id', 'target_order_id']);
            $this->forge->createTable('order_merges', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('order_merges')) {
            $this->forge->dropTable('order_merges', true);
        }
    }
}