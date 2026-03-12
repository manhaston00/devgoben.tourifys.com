<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTenantIdToUsersTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // ถ้ายังไม่มี column
        if (!$db->fieldExists('tenant_id', 'users')) {

            $this->forge->addColumn('users', [
                'tenant_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'after' => 'id'
                ],
            ]);

            // backfill tenant จาก branch
            $db->query("
                UPDATE users u
                JOIN branches b ON b.id = u.branch_id
                SET u.tenant_id = b.tenant_id
            ");

            // เพิ่ม index
            $this->forge->addKey('tenant_id');

            // FK
            $db->query("
                ALTER TABLE users
                ADD CONSTRAINT fk_users_tenant
                FOREIGN KEY (tenant_id) REFERENCES tenants(id)
                ON DELETE CASCADE
            ");
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->fieldExists('tenant_id', 'users')) {

            $db->query("
                ALTER TABLE users
                DROP FOREIGN KEY fk_users_tenant
            ");

            $this->forge->dropColumn('users', 'tenant_id');
        }
    }
}