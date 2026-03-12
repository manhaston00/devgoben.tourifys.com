<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterSubscriptionsAddLifecycleFields extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('subscriptions')) {
            return;
        }

        $fields = $this->db->getFieldNames('subscriptions');
        $addFields = [];

        if (! in_array('started_at', $fields, true)) {
            $addFields['started_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'plan_id',
            ];
        }

        if (! in_array('expires_at', $fields, true)) {
            $addFields['expires_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'started_at',
            ];
        }

        if (! in_array('status', $fields, true)) {
            $addFields['status'] = [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'active',
                'after'      => 'expires_at',
            ];
        }

        if (! in_array('is_demo', $fields, true)) {
            $addFields['is_demo'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'status',
            ];
        }

        if (! in_array('demo_readonly', $fields, true)) {
            $addFields['demo_readonly'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'is_demo',
            ];
        }

        if (! empty($addFields)) {
            $this->forge->addColumn('subscriptions', $addFields);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('subscriptions')) {
            return;
        }

        $fields = $this->db->getFieldNames('subscriptions');

        foreach (['started_at', 'expires_at', 'status', 'is_demo', 'demo_readonly'] as $field) {
            if (in_array($field, $fields, true)) {
                $this->forge->dropColumn('subscriptions', $field);
            }
        }
    }
}