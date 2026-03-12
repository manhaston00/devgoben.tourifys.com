<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTenantIdToBranchesTable extends Migration
{
    public function up()
    {
        $fields = [
            'tenant_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'id'
            ],
        ];

        $this->forge->addColumn('branches', $fields);

        $this->db->query("ALTER TABLE branches ADD INDEX idx_branches_tenant (tenant_id)");
    }

    public function down()
    {
        $this->forge->dropColumn('branches', 'tenant_id');
    }
}