<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class HardenSuperAdminDuplicateGuards extends Migration
{
    public function up()
    {
        // tenants.email unique แบบ soft guard ฝั่งแอปอยู่แล้ว
        // DB ชั้นนี้เพิ่มเฉพาะที่เหมาะกับ unique ชัดเจนจริง

        if ($this->db->tableExists('tenants')) {
            $indexes = $this->db->getIndexData('tenants');

            if (! isset($indexes['uniq_tenants_email']) && $this->db->fieldExists('email', 'tenants')) {
                $this->db->query("CREATE UNIQUE INDEX uniq_tenants_email ON tenants (email)");
            }
        }

        if ($this->db->tableExists('users')) {
            $indexes = $this->db->getIndexData('users');

            if (! isset($indexes['uniq_users_email']) && $this->db->fieldExists('email', 'users')) {
                $this->db->query("CREATE UNIQUE INDEX uniq_users_email ON users (email)");
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tenants')) {
            try {
                $this->db->query("DROP INDEX uniq_tenants_email ON tenants");
            } catch (\Throwable $e) {
            }
        }

        if ($this->db->tableExists('users')) {
            try {
                $this->db->query("DROP INDEX uniq_users_email ON users");
            } catch (\Throwable $e) {
            }
        }
    }
}