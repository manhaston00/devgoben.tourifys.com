<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class HardenKitchenFlow extends Migration
{
    public function up()
    {
        // kitchen_tickets
        if (! $this->db->fieldExists('branch_id', 'kitchen_tickets')) {
            $this->forge->addColumn('kitchen_tickets', [
                'branch_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'tenant_id',
                ],
                'source_request_uuid' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'after'      => 'status',
                ],
                'dispatch_batch_no' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                    'after'      => 'source_request_uuid',
                ],
                'item_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                    'after'      => 'dispatch_batch_no',
                ],
                'created_by' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'item_count',
                ],
                'printed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'created_by',
                ],
                'done_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'printed_at',
                ],
                'cancelled_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'done_at',
                ],
            ]);
        }

        // order_items
        if (! $this->db->fieldExists('kitchen_ticket_id', 'order_items')) {
            $this->forge->addColumn('order_items', [
                'kitchen_ticket_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'status',
                ],
            ]);
        }

        // kitchen_logs
        if (! $this->db->fieldExists('branch_id', 'kitchen_logs')) {
            $this->forge->addColumn('kitchen_logs', [
                'branch_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'tenant_id',
                ],
                'order_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'branch_id',
                ],
                'ticket_id' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'order_id',
                ],
                'from_status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                    'after'      => 'log_type',
                ],
                'to_status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'null'       => true,
                    'after'      => 'from_status',
                ],
                'action_by' => [
                    'type'       => 'INT',
                    'constraint' => 10,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'to_status',
                ],
                'action_source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'action_by',
                ],
                'request_uuid' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'after'      => 'action_source',
                ],
                'meta_json' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'request_uuid',
                ],
            ]);
        }

        // Fix enum: kitchen_logs must support ready
        $this->db->query("
            ALTER TABLE `kitchen_logs`
            MODIFY `log_type` ENUM('new','printed','cooking','ready','served','cancel')
            NOT NULL DEFAULT 'new'
        ");

        // Backfill branch_id in kitchen_tickets from orders
        $this->db->query("
            UPDATE kitchen_tickets kt
            INNER JOIN orders o ON o.id = kt.order_id
            SET kt.branch_id = o.branch_id
            WHERE kt.branch_id IS NULL
        ");

        // Backfill kitchen_ticket_id for old rows: match the latest ticket of the same order
        if ($this->db->fieldExists('kitchen_ticket_id', 'order_items')) {
            $tickets = $this->db->query("
                SELECT kt.id, kt.order_id, kt.created_at
                FROM kitchen_tickets kt
                ORDER BY kt.id ASC
            ")->getResultArray();

            foreach ($tickets as $ticket) {
                $orderId = (int) ($ticket['order_id'] ?? 0);
                $ticketId = (int) ($ticket['id'] ?? 0);
                $createdAt = (string) ($ticket['created_at'] ?? '');

                if ($orderId <= 0 || $ticketId <= 0 || $createdAt === '') {
                    continue;
                }

                $this->db->query("
                    UPDATE order_items
                    SET kitchen_ticket_id = ?
                    WHERE order_id = ?
                      AND kitchen_ticket_id IS NULL
                      AND sent_at IS NOT NULL
                      AND sent_at <= ?
                ", [$ticketId, $orderId, $createdAt]);
            }
        }

        // Backfill branch/order/ticket on logs
        $this->db->query("
            UPDATE kitchen_logs kl
            INNER JOIN order_items oi ON oi.id = kl.order_item_id
            INNER JOIN orders o ON o.id = oi.order_id
            SET
                kl.order_id = oi.order_id,
                kl.branch_id = o.branch_id,
                kl.ticket_id = oi.kitchen_ticket_id
            WHERE kl.order_id IS NULL OR kl.branch_id IS NULL OR kl.ticket_id IS NULL
        ");

        // Fix blank log_type rows from old bad enum mapping
        $this->db->query("
            UPDATE kitchen_logs kl
            INNER JOIN order_items oi ON oi.id = kl.order_item_id
            SET kl.log_type = CASE
                WHEN oi.status = 'ready' THEN 'ready'
                WHEN oi.status = 'served' THEN 'served'
                WHEN oi.status = 'cancel' THEN 'cancel'
                ELSE 'cooking'
            END
            WHERE kl.log_type = '' OR kl.log_type IS NULL
        ");

        // Useful indexes
        $this->db->query("CREATE INDEX idx_kitchen_tickets_tenant_branch_status_created ON kitchen_tickets (tenant_id, branch_id, status, created_at)");
        $this->db->query("CREATE INDEX idx_kitchen_tickets_request_uuid ON kitchen_tickets (tenant_id, order_id, source_request_uuid)");
        $this->db->query("CREATE INDEX idx_order_items_ticket_status ON order_items (tenant_id, kitchen_ticket_id, status)");
        $this->db->query("CREATE INDEX idx_kitchen_logs_ticket_created ON kitchen_logs (tenant_id, ticket_id, created_at)");
    }

    public function down()
    {
        // Intentionally minimal rollback for production safety.
    }
}