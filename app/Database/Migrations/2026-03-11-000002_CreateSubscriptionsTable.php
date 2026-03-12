<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubscriptionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([

            'id'=>[
                'type'=>'INT',
                'constraint'=>11,
                'auto_increment'=>true
            ],

            'tenant_id'=>[
                'type'=>'INT',
                'constraint'=>11
            ],

            'plan_id'=>[
                'type'=>'INT',
                'constraint'=>11
            ],

            'billing_type'=>[
                'type'=>'ENUM',
                'constraint'=>['daily','monthly','lifetime']
            ],

            'status'=>[
                'type'=>'ENUM',
                'constraint'=>['active','expired','suspended'],
                'default'=>'active'
            ],

            'start_date DATE',
            'end_date DATE',

            'is_demo'=>[
                'type'=>'TINYINT',
                'constraint'=>1,
                'default'=>0
            ],

            'promo_id'=>[
                'type'=>'INT',
                'constraint'=>11,
                'null'=>true
            ],

            'created_at DATETIME NULL',
            'updated_at DATETIME NULL'

        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');

        $this->forge->createTable('subscriptions');
    }

    public function down()
    {
        $this->forge->dropTable('subscriptions');
    }
}