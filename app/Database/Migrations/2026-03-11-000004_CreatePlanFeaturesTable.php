<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlanFeaturesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([

            'id'=>[
                'type'=>'INT',
                'constraint'=>11,
                'auto_increment'=>true
            ],

            'plan_id'=>[
                'type'=>'INT',
                'constraint'=>11
            ],

            'feature_key'=>[
                'type'=>'VARCHAR',
                'constraint'=>100
            ],

            'feature_value'=>[
                'type'=>'VARCHAR',
                'constraint'=>100
            ]

        ]);

        $this->forge->addKey('id', true);

        $this->forge->createTable('plan_features');
    }

    public function down()
    {
        $this->forge->dropTable('plan_features');
    }
}