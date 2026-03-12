<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubscriptionLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([

            'id'=>[
                'type'=>'INT',
                'constraint'=>11,
                'auto_increment'=>true
            ],

            'subscription_id'=>[
                'type'=>'INT',
                'constraint'=>11
            ],

            'action'=>[
                'type'=>'VARCHAR',
                'constraint'=>50
            ],

            'meta'=>[
                'type'=>'TEXT',
                'null'=>true
            ],

            'created_at DATETIME NULL'

        ]);

        $this->forge->addKey('id', true);

        $this->forge->createTable('subscription_logs');
    }

    public function down()
    {
        $this->forge->dropTable('subscription_logs');
    }
}