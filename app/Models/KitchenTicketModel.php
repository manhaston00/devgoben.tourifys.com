<?php

namespace App\Models;

class KitchenTicketModel extends TenantScopedModel
{
    protected $table = 'kitchen_tickets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'order_id',
        'ticket_no',
        'status',
        'source_request_uuid',
        'dispatch_batch_no',
        'item_count',
        'created_by',
        'printed_at',
        'done_at',
        'cancelled_at',
    ];

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    public function getByOrder(int $orderId): array
    {
        return $this->scoped()
            ->where('order_id', $orderId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function getLatestByOrder(int $orderId): ?array
    {
        return $this->scoped()
            ->where('order_id', $orderId)
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function findByRequestUuid(int $tenantId, int $orderId, string $requestUuid): ?array
    {
        $requestUuid = trim($requestUuid);

        if ($tenantId <= 0 || $orderId <= 0 || $requestUuid === '') {
            return null;
        }

        return $this->where('tenant_id', $tenantId)
            ->where('order_id', $orderId)
            ->where('source_request_uuid', $requestUuid)
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function getNextBatchNo(int $tenantId, int $orderId): int
    {
        $row = $this->selectMax('dispatch_batch_no', 'max_batch')
            ->where('tenant_id', $tenantId)
            ->where('order_id', $orderId)
            ->first();

        return (int) (($row['max_batch'] ?? 0) + 1);
    }

    public function refreshStatusByTicketId(int $tenantId, int $ticketId): void
    {
        if ($tenantId <= 0 || $ticketId <= 0) {
            return;
        }

        $db = \Config\Database::connect();

        $activeCount = (int) $db->table('order_items')
            ->where('tenant_id', $tenantId)
            ->where('kitchen_ticket_id', $ticketId)
            ->whereNotIn('status', ['served', 'cancel', 'cancelled', 'canceled'])
            ->countAllResults();

        if ($activeCount === 0) {
            $this->update($ticketId, [
                'status'     => 'done',
                'done_at'    => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function getMonitorBoardRows(
        int $tenantId,
        int $branchId = 0,
        ?int $stationId = null,
        bool $stationOnly = false,
        string $locale = 'th'
    ): array {
        if ($tenantId <= 0) {
            return [];
        }

        $locale = strtolower(trim($locale));
        if (! in_array($locale, ['th', 'en'], true)) {
            $locale = 'th';
        }

        $stationDisplaySql = $locale === 'th'
            ? "CASE
                    WHEN COALESCE(ks.station_name_th, '') <> '' THEN ks.station_name_th
                    WHEN COALESCE(ks.station_name, '') <> '' THEN ks.station_name
                    ELSE COALESCE(ks.station_name_en, '')
               END"
            : "CASE
                    WHEN COALESCE(ks.station_name_en, '') <> '' THEN ks.station_name_en
                    WHEN COALESCE(ks.station_name, '') <> '' THEN ks.station_name
                    ELSE COALESCE(ks.station_name_th, '')
               END";

        $builder = $this->db->table('kitchen_tickets kt');
        $builder->select("
            kt.id AS ticket_id,
            kt.ticket_no,
            kt.status AS ticket_status,
            kt.order_id,
            kt.branch_id,
            kt.dispatch_batch_no,
            kt.item_count,
            o.order_number,
            o.table_id,
            rt.table_name,
            oi.id AS order_item_id,
            oi.product_id,
            oi.product_name,
            oi.item_detail,
            oi.qty,
            oi.note,
            oi.status AS item_status,
            oi.sent_at,
            oi.served_at,
            oi.updated_at,
            p.kitchen_station_id,
            ks.station_name,
            ks.station_name_th,
            ks.station_name_en,
            otm.from_table_id AS moved_from_table_id,
            otm.to_table_id AS moved_to_table_id,
            otm.reason AS moved_reason,
            rtf.table_name AS moved_from_table_name,
            rtt.table_name AS moved_to_table_name,
            {$stationDisplaySql} AS station_display_name,
            CASE
                WHEN oi.status = 'served' THEN 'served'
                WHEN oi.status = 'ready' THEN 'ready'
                WHEN oi.status = 'cooking' THEN 'preparing'
                WHEN oi.status IN ('pending', 'sent') THEN 'new'
                WHEN oi.status IN ('cancel', 'cancelled', 'canceled') THEN 'cancelled'
                ELSE 'new'
            END AS display_status
        ");

        $builder->join('orders o', 'o.id = kt.order_id', 'inner');
        $builder->join('order_items oi', 'oi.kitchen_ticket_id = kt.id AND oi.tenant_id = kt.tenant_id', 'inner');
        $builder->join('products p', 'p.id = oi.product_id', 'left');
        $builder->join('restaurant_tables rt', 'rt.id = o.table_id', 'left');
        $builder->join('kitchen_stations ks', 'ks.id = p.kitchen_station_id', 'left');

        $latestMoveSubquery = "
        (
            SELECT x.*
            FROM order_table_moves x
            INNER JOIN (
                SELECT order_id, MAX(id) AS max_id
                FROM order_table_moves
                WHERE tenant_id = {$tenantId}
                GROUP BY order_id
            ) lm ON lm.max_id = x.id
        ) otm
        ";

        $builder->join($latestMoveSubquery, 'otm.order_id = o.id', 'left', false);
        $builder->join('restaurant_tables rtf', 'rtf.id = otm.from_table_id', 'left');
        $builder->join('restaurant_tables rtt', 'rtt.id = otm.to_table_id', 'left');

        $builder->where('kt.tenant_id', $tenantId);
        $builder->where('o.tenant_id', $tenantId);
        $builder->where('oi.tenant_id', $tenantId);

        if ($branchId > 0) {
            $builder->where('kt.branch_id', $branchId);
        }

        $builder->groupStart()
            ->whereIn('oi.status', [
                'pending',
                'sent',
                'cooking',
                'ready'
            ])
            ->orGroupStart()
                ->where('oi.status', 'served')
                ->where('oi.served_at >=', date('Y-m-d H:i:s', strtotime('-20 minutes')))
            ->groupEnd()
            ->orGroupStart()
                ->whereIn('oi.status', ['cancel', 'cancelled', 'canceled'])
                ->where('oi.updated_at >=', date('Y-m-d H:i:s', strtotime('-20 minutes')))
            ->groupEnd()
        ->groupEnd();

        if ($stationId) {
            $builder->where('p.kitchen_station_id', $stationId);
        }

        if ($stationOnly) {
            $builder->where('p.kitchen_station_id IS NOT NULL', null, false);
        }

        $builder->orderBy('kt.created_at', 'ASC');
        $builder->orderBy('kt.id', 'ASC');
        $builder->orderBy('oi.id', 'ASC');

        return $builder->get()->getResultArray();
    }
}