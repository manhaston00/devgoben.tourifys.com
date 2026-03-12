<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDemoAccessTable extends Migration
{
    public function up()
    {
        $this->forge->addField([

            'id'=>[
                'type'=>'INT',
                'constraint'=>11,
                'auto_increment'=>true
            ],

            'email'=>[
                'type'=>'VARCHAR',
                'constraint'=>150
            ],

            'phone'=>[
                'type'=>'VARCHAR',
                'constraint'=>50,
                'null'=>true
            ],

            'ip'=>[
                'type'=>'VARCHAR',
                'constraint'=>50,
                'null'=>true
            ],

            'start_date DATE',
            'end_date DATE',

            'created_at DATETIME NULL'

        ]);

        $this->forge->addKey('id', true);

        $this->forge->createTable('demo_access');
    }

    public function down()
    {
        $this->forge->dropTable('demo_access');
    }
}