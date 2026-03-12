<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrderTableMoves extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('order_table_moves')) {
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
                'order_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'from_table_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'to_table_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                ],
                'moved_by' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'reason' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
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
            $this->forge->addKey(['tenant_id', 'branch_id', 'order_id']);
            $this->forge->addKey(['tenant_id', 'branch_id', 'from_table_id']);
            $this->forge->addKey(['tenant_id', 'branch_id', 'to_table_id']);
            $this->forge->createTable('order_table_moves', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('order_table_moves')) {
            $this->forge->dropTable('order_table_moves', true);
        }
    }
}