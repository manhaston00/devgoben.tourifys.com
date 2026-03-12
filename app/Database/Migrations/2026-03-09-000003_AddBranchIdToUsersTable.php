<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBranchIdToUsersTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('users') && ! $this->db->fieldExists('branch_id', 'users')) {
            $this->forge->addColumn('users', [
                'branch_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'role_id',
                ],
            ]);
        }

        if ($this->db->tableExists('users') && $this->db->fieldExists('branch_id', 'users')) {
            $this->db->query('ALTER TABLE `users` ADD INDEX `idx_users_branch_id` (`branch_id`)');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('users') && $this->db->fieldExists('branch_id', 'users')) {
            $this->forge->dropColumn('users', 'branch_id');
        }
    }
}