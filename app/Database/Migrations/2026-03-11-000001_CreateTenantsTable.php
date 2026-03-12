<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTenantsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([

            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'auto_increment' => true,
            ],

            'tenant_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],

            'tenant_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],

            'owner_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],

            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],

            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],

            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active','suspended','demo'],
                'default'    => 'active',
            ],

            'created_at DATETIME NULL',
            'updated_at DATETIME NULL',

        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('tenant_code');

        $this->forge->createTable('tenants');
    }

    public function down()
    {
        $this->forge->dropTable('tenants');
    }
}