<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBranchSystem extends Migration
{
    protected function addColumnIfMissing(string $table, string $field, array $definition): void
    {
        if ($this->db->tableExists($table) && ! $this->db->fieldExists($field, $table)) {
            $this->forge->addColumn($table, [$field => $definition]);
        }
    }

    public function up()
    {
        if (! $this->db->tableExists('branches')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'branch_code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'branch_name_th' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],
                'branch_name_en' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'phone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'address_th' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'address_en' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'tax_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'logo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'default_locale' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'default'    => 'th',
                ],
                'timezone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'default'    => 'Asia/Bangkok',
                ],
                'status' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'is_head_office' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'sort_order' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'opened_at' => [
                    'type' => 'DATE',
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
            $this->forge->addUniqueKey('branch_code');
            $this->forge->addKey('status');
            $this->forge->addKey('is_head_office');
            $this->forge->createTable('branches', true);
        }

        $this->addColumnIfMissing('branches', 'branch_name_th', [
            'type'       => 'VARCHAR',
            'constraint' => 150,
            'null'       => true,
            'after'      => 'branch_code',
        ]);
        $this->addColumnIfMissing('branches', 'branch_name_en', [
            'type'       => 'VARCHAR',
            'constraint' => 150,
            'null'       => true,
            'after'      => 'branch_name_th',
        ]);
        $this->addColumnIfMissing('branches', 'address_th', [
            'type'  => 'TEXT',
            'null'  => true,
            'after' => 'email',
        ]);
        $this->addColumnIfMissing('branches', 'address_en', [
            'type'  => 'TEXT',
            'null'  => true,
            'after' => 'address_th',
        ]);
        $this->addColumnIfMissing('branches', 'logo', [
            'type'       => 'VARCHAR',
            'constraint' => 255,
            'null'       => true,
            'after'      => 'tax_id',
        ]);
        $this->addColumnIfMissing('branches', 'default_locale', [
            'type'       => 'VARCHAR',
            'constraint' => 10,
            'default'    => 'th',
            'after'      => 'logo',
        ]);
        $this->addColumnIfMissing('branches', 'timezone', [
            'type'       => 'VARCHAR',
            'constraint' => 100,
            'default'    => 'Asia/Bangkok',
            'after'      => 'default_locale',
        ]);
        $this->addColumnIfMissing('branches', 'opened_at', [
            'type'  => 'DATE',
            'null'  => true,
            'after' => 'sort_order',
        ]);

        if ($this->db->tableExists('branches')) {
            if ($this->db->fieldExists('branch_name', 'branches') && ! $this->db->fieldExists('branch_name_th', 'branches')) {
                $this->db->query('ALTER TABLE branches CHANGE branch_name branch_name_th VARCHAR(150) NOT NULL');
            }

            if ($this->db->fieldExists('address', 'branches') && ! $this->db->fieldExists('address_th', 'branches')) {
                $this->db->query('ALTER TABLE branches CHANGE address address_th TEXT NULL');
            }
        }

        $this->addColumnIfMissing('users', 'branch_id', [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
            'null'       => true,
            'after'      => 'role_id',
        ]);
        $this->addColumnIfMissing('zones', 'branch_id', [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
            'null'       => true,
            'after'      => 'id',
        ]);
        $this->addColumnIfMissing('restaurant_tables', 'branch_id', [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
            'null'       => true,
            'after'      => 'id',
        ]);
        $this->addColumnIfMissing('orders', 'branch_id', [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
            'null'       => true,
            'after'      => 'id',
        ]);
        $this->addColumnIfMissing('reservations', 'branch_id', [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
            'null'       => true,
            'after'      => 'id',
        ]);

        if ($this->db->tableExists('app_settings')) {
            $exists = $this->db->table('app_settings')
                ->where('setting_key', 'branch_mode')
                ->countAllResults();

            if (! $exists) {
                $this->db->table('app_settings')->insert([
                    'setting_key'   => 'branch_mode',
                    'setting_value' => 'multi',
                    'setting_group' => 'general',
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('reservations') && $this->db->fieldExists('branch_id', 'reservations')) {
            $this->forge->dropColumn('reservations', 'branch_id');
        }

        if ($this->db->tableExists('orders') && $this->db->fieldExists('branch_id', 'orders')) {
            $this->forge->dropColumn('orders', 'branch_id');
        }

        if ($this->db->tableExists('restaurant_tables') && $this->db->fieldExists('branch_id', 'restaurant_tables')) {
            $this->forge->dropColumn('restaurant_tables', 'branch_id');
        }

        if ($this->db->tableExists('zones') && $this->db->fieldExists('branch_id', 'zones')) {
            $this->forge->dropColumn('zones', 'branch_id');
        }

        if ($this->db->tableExists('users') && $this->db->fieldExists('branch_id', 'users')) {
            $this->forge->dropColumn('users', 'branch_id');
        }
    }
}
