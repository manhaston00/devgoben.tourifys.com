<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSaasCoreTables extends Migration
{
    public function up()
    {
        /*
        |--------------------------------------------------------------------------
        | app_settings
        |--------------------------------------------------------------------------
        */
        if (! $this->db->tableExists('app_settings')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'setting_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'setting_value' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'setting_group' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'general',
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
            $this->forge->addUniqueKey('setting_key');
            $this->forge->addKey('setting_group');
            $this->forge->createTable('app_settings', true);
        }

        /*
        |--------------------------------------------------------------------------
        | subscription_plans
        |--------------------------------------------------------------------------
        */
        if (! $this->db->tableExists('subscription_plans')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'plan_code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'plan_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],
                'plan_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'comment'    => 'daily, monthly, lifetime',
                ],
                'price' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'duration_days' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'is_lifetime' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'status' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'description' => [
                    'type' => 'TEXT',
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
            $this->forge->addUniqueKey('plan_code');
            $this->forge->addKey('plan_type');
            $this->forge->addKey('status');
            $this->forge->createTable('subscription_plans', true);
        }

        /*
        |--------------------------------------------------------------------------
        | licenses
        |--------------------------------------------------------------------------
        */
        if (! $this->db->tableExists('licenses')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'license_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'customer_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],
                'customer_phone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'customer_email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
                'plan_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'start_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'end_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'active',
                    'comment'    => 'active, expired, suspended',
                ],
                'note' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'last_checked_at' => [
                    'type' => 'DATETIME',
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
            $this->forge->addUniqueKey('license_key');
            $this->forge->addKey('plan_id');
            $this->forge->addKey('status');
            $this->forge->addKey('start_date');
            $this->forge->addKey('end_date');
            $this->forge->createTable('licenses', true);
        }

        /*
        |--------------------------------------------------------------------------
        | subscription_invoices
        |--------------------------------------------------------------------------
        */
        if (! $this->db->tableExists('subscription_invoices')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'license_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'invoice_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'paid_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'payment_status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'unpaid',
                ],
                'due_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'paid_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'note' => [
                    'type' => 'TEXT',
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
            $this->forge->addUniqueKey('invoice_no');
            $this->forge->addKey('license_id');
            $this->forge->addKey('payment_status');
            $this->forge->createTable('subscription_invoices', true);
        }

        /*
        |--------------------------------------------------------------------------
        | promotions
        |--------------------------------------------------------------------------
        */
        if (! $this->db->tableExists('promotions')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],
                'discount_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'comment'    => 'fixed, percent',
                ],
                'discount_value' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'min_order_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'usage_limit' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'used_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'start_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'end_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'status' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'note' => [
                    'type' => 'TEXT',
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
            $this->forge->addUniqueKey('code');
            $this->forge->addKey('discount_type');
            $this->forge->addKey('status');
            $this->forge->createTable('promotions', true);
        }

        /*
        |--------------------------------------------------------------------------
        | promotion_redemptions
        |--------------------------------------------------------------------------
        */
        if (! $this->db->tableExists('promotion_redemptions')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'promotion_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'order_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'customer_phone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                ],
                'discount_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'redeemed_at' => [
                    'type' => 'DATETIME',
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
            $this->forge->addKey('promotion_id');
            $this->forge->addKey('order_id');
            $this->forge->addKey('customer_phone');
            $this->forge->createTable('promotion_redemptions', true);
        }

        /*
        |--------------------------------------------------------------------------
        | automation_logs
        |--------------------------------------------------------------------------
        */
        if (! $this->db->tableExists('automation_logs')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'job_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'job_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                ],
                'run_status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'success',
                ],
                'message' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'ran_at' => [
                    'type' => 'DATETIME',
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
            $this->forge->addKey('job_key');
            $this->forge->addKey('run_status');
            $this->forge->addKey('ran_at');
            $this->forge->createTable('automation_logs', true);
        }
    }

    public function down()
    {
        $tables = [
            'automation_logs',
            'promotion_redemptions',
            'promotions',
            'subscription_invoices',
            'licenses',
            'subscription_plans',
            'app_settings',
        ];

        foreach ($tables as $table) {
            if ($this->db->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }
    }
}