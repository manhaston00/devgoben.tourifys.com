<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSoftDeletesToBranchesTablesQuickOptions extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('branches') && ! $this->db->fieldExists('deleted_at', 'branches')) {
            $this->forge->addColumn('branches', [
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'updated_at',
                ],
            ]);
        }

        if ($this->db->tableExists('restaurant_tables') && ! $this->db->fieldExists('deleted_at', 'restaurant_tables')) {
            $this->forge->addColumn('restaurant_tables', [
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'updated_at',
                ],
            ]);
        }

        if ($this->db->tableExists('quick_options') && ! $this->db->fieldExists('deleted_at', 'quick_options')) {
            $this->forge->addColumn('quick_options', [
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'updated_at',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('branches') && $this->db->fieldExists('deleted_at', 'branches')) {
            $this->forge->dropColumn('branches', 'deleted_at');
        }

        if ($this->db->tableExists('restaurant_tables') && $this->db->fieldExists('deleted_at', 'restaurant_tables')) {
            $this->forge->dropColumn('restaurant_tables', 'deleted_at');
        }

        if ($this->db->tableExists('quick_options') && $this->db->fieldExists('deleted_at', 'quick_options')) {
            $this->forge->dropColumn('quick_options', 'deleted_at');
        }
    }
}