<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CleanupDuplicateKitchenTicketForeignKeys extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('kitchen_tickets')) {
            return;
        }

        try {
            $this->db->query('ALTER TABLE `kitchen_tickets` DROP FOREIGN KEY `fk_kitchen_tickets_order_id`');
        } catch (\Throwable $e) {
            // ignore if not exists
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('kitchen_tickets') || ! $this->db->tableExists('orders')) {
            return;
        }

        try {
            $this->db->query('
                ALTER TABLE `kitchen_tickets`
                ADD CONSTRAINT `fk_kitchen_tickets_order_id`
                FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`)
                ON DELETE CASCADE ON UPDATE CASCADE
            ');
        } catch (\Throwable $e) {
            // ignore if already exists
        }
    }
}