<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixProductQuickOptionsSchema extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('product_quick_options')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'product_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'category_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'option_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'option_name_th' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'option_name_en' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'price_adjust' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'sort_order' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'status' => [
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
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('product_id');
            $this->forge->addKey('category_id');
            $this->forge->createTable('product_quick_options');
            return;
        }

        $fields = [];

        if (! $this->db->fieldExists('option_name_th', 'product_quick_options')) {
            $fields['option_name_th'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'option_name',
            ];
        }

        if (! $this->db->fieldExists('option_name_en', 'product_quick_options')) {
            $fields['option_name_en'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => isset($fields['option_name_th']) ? 'option_name_th' : 'option_name',
            ];
        }

        if (! $this->db->fieldExists('deleted_at', 'product_quick_options')) {
            $fields['deleted_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'updated_at',
            ];
        }

        if (! empty($fields)) {
            $this->forge->addColumn('product_quick_options', $fields);
        }

        if ($this->db->fieldExists('option_name', 'product_quick_options')) {
            $this->db->query("UPDATE product_quick_options SET option_name_th = COALESCE(NULLIF(option_name_th, ''), option_name) WHERE option_name IS NOT NULL");
            $this->db->query("UPDATE product_quick_options SET option_name_en = COALESCE(NULLIF(option_name_en, ''), option_name) WHERE option_name IS NOT NULL AND (option_name_en IS NULL OR option_name_en = '')");
        }
    }

    public function down()
    {
        // keep data-safe
    }
}
