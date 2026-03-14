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

    protected function currentBranchId(): int
    {
        if (function_exists('current_branch_id')) {
            return (int) (current_branch_id() ?: 0);
        }

        return (int) (session('branch_id') ?? 0);
    }

    protected function applyBranchScope($builder, string $alias = '')
    {
        $branchId = $this->currentBranchId();
        $column = $alias !== '' ? $alias . '.branch_id' : $this->table . '.branch_id';

        if ($branchId > 0 && $this->db->fieldExists('branch_id', $this->table)) {
            $builder->where($column, $branchId);
        }

        return $builder;
    }

    protected function resolveTicketContext(int $ticketId): ?array
    {
        if ($ticketId <= 0) {
            return null;
        }

        $builder = $this->db->table($this->table . ' kt')
            ->select('kt.id, kt.order_id, kt.branch_id')
            ->where('kt.tenant_id', $this->currentTenantId())
            ->where('kt.id', $ticketId);

        $this->applyBranchScope($builder, 'kt');

        return $builder->get()->getRowArray() ?: null;
    }

    public function getByOrder(int $orderId): array
    {
        if ($orderId <= 0) {
            return [];
        }

        $builder = $this->scoped()
            ->where('order_id', $orderId)
            ->orderBy('id', 'ASC');

        $this->applyBranchScope($builder);

        return $builder->findAll();
    }

    public function getLatestByOrder(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $builder = $this->scoped()
            ->where('order_id', $orderId)
            ->orderBy('id', 'DESC');

        $this->applyBranchScope($builder);

        return $builder->first();
    }

    public function findByRequestUuid(int $tenantId, int $orderId, string $requestUuid): ?array
    {
        $requestUuid = trim($requestUuid);

        if ($tenantId <= 0 || $orderId <= 0 || $requestUuid === '') {
            return null;
        }

        if ($tenantId !== $this->currentTenantId()) {
            return null;
        }

        $builder = $this->builder()
            ->where('tenant_id', $tenantId)
            ->where('order_id', $orderId)
            ->where('source_request_uuid', $requestUuid)
            ->orderBy('id', 'DESC');

        $this->applyBranchScope($builder);

        return $builder->get()->getRowArray() ?: null;
    }

    public function getNextBatchNo(int $tenantId, int $orderId): int
    {
        if ($tenantId <= 0 || $orderId <= 0) {
            return 1;
        }

        if ($tenantId !== $this->currentTenantId()) {
            return 1;
        }

        $builder = $this->builder()
            ->selectMax('dispatch_batch_no', 'max_batch')
            ->where('tenant_id', $tenantId)
            ->where('order_id', $orderId);

        $this->applyBranchScope($builder);

        $row = $builder->get()->getRowArray();

        return (int) (($row['max_batch'] ?? 0) + 1);
    }

    public function refreshStatusByTicketId(int $tenantId, int $ticketId): void
    {
        if ($tenantId <= 0 || $ticketId <= 0) {
            return;
        }

        if ($tenantId !== $this->currentTenantId()) {
            return;
        }

        $context = $this->resolveTicketContext($ticketId);
        if (! $context) {
            return;
        }

        $db = \Config\Database::connect();

        $builder = $db->table('order_items oi')
            ->select('COUNT(oi.id) AS active_count', false)
            ->join('orders o', 'o.id = oi.order_id AND o.tenant_id = oi.tenant_id', 'inner')
            ->where('oi.tenant_id', $tenantId)
            ->where('oi.kitchen_ticket_id', $ticketId)
            ->groupStart()
                ->whereNotIn('oi.status', ['served', 'cancel', 'cancelled', 'canceled'])
                ->groupStart()
                    ->where('oi.cancel_request_status IS NULL', null, false)
                    ->orWhere('oi.cancel_request_status !=', 'approved')
                ->groupEnd()
            ->groupEnd();

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'orders')) {
            $builder->where('o.branch_id', $branchId);
        }

        $row = $builder->get()->getRowArray();
        $activeCount = (int) ($row['active_count'] ?? 0);

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

        $effectiveBranchId = $branchId > 0 ? $branchId : $this->currentBranchId();

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
                WHEN oi.status IN ('cancel', 'cancelled', 'canceled') OR COALESCE(oi.cancel_request_status, '') = 'approved' THEN 'cancelled'
                ELSE 'new'
            END AS display_status
        ");

        $builder->join('orders o', 'o.id = kt.order_id AND o.tenant_id = kt.tenant_id', 'inner');
        $builder->join('order_items oi', 'oi.kitchen_ticket_id = kt.id AND oi.tenant_id = kt.tenant_id AND oi.order_id = o.id', 'inner');
        $builder->join('products p', 'p.id = oi.product_id AND p.tenant_id = oi.tenant_id', 'left');
        $builder->join('restaurant_tables rt', 'rt.id = o.table_id AND rt.tenant_id = o.tenant_id', 'left');
        $builder->join('kitchen_stations ks', 'ks.id = p.kitchen_station_id AND ks.tenant_id = kt.tenant_id', 'left');

        $latestMoveSubquery = "
        (
            SELECT x.*
            FROM order_table_moves x
            INNER JOIN (
                SELECT order_id, MAX(id) AS max_id
                FROM order_table_moves
                WHERE tenant_id = {$tenantId}"
                . ($effectiveBranchId > 0 ? " AND branch_id = {$effectiveBranchId}" : '') . "
                GROUP BY order_id
            ) lm ON lm.max_id = x.id
        ) otm
        ";

        $builder->join($latestMoveSubquery, 'otm.order_id = o.id', 'left', false);
        $builder->join('restaurant_tables rtf', 'rtf.id = otm.from_table_id AND rtf.tenant_id = kt.tenant_id', 'left');
        $builder->join('restaurant_tables rtt', 'rtt.id = otm.to_table_id AND rtt.tenant_id = kt.tenant_id', 'left');

        $builder->where('kt.tenant_id', $tenantId);
        $builder->where('o.tenant_id', $tenantId);
        $builder->where('oi.tenant_id', $tenantId);

        if ($effectiveBranchId > 0) {
            $builder->where('kt.branch_id', $effectiveBranchId);
            $builder->where('o.branch_id', $effectiveBranchId);
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
                ->groupStart()
                    ->whereIn('oi.status', ['cancel', 'cancelled', 'canceled'])
                    ->orWhere('oi.cancel_request_status', 'approved')
                ->groupEnd()
                ->groupStart()
                    ->where('oi.cancelled_at >=', date('Y-m-d H:i:s', strtotime('-20 minutes')))
                    ->orGroupStart()
                        ->where('oi.cancelled_at', null)
                        ->where('oi.updated_at >=', date('Y-m-d H:i:s', strtotime('-20 minutes')))
                    ->groupEnd()
                ->groupEnd()
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

    public function getMonitorHistoryRows(
        int $tenantId,
        int $branchId = 0,
        ?int $stationId = null,
        bool $stationOnly = false,
        string $locale = 'th',
        int $limit = 300,
        int $daysBack = 7
    ): array {
        if ($tenantId <= 0) {
            return [];
        }

        $locale = strtolower(trim($locale));
        if (! in_array($locale, ['th', 'en'], true)) {
            $locale = 'th';
        }

        $limit = max(20, min(1000, (int) $limit));
        $daysBack = max(1, min(30, (int) $daysBack));
        $fromDate = date('Y-m-d H:i:s', strtotime('-' . $daysBack . ' days'));

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

        $effectiveBranchId = $branchId > 0 ? $branchId : $this->currentBranchId();

        $builder = $this->db->table('order_items oi');
        $builder->select(" 
            oi.id AS order_item_id,
            oi.id AS item_id,
            o.id AS order_id,
            o.order_number,
            o.table_id,
            rt.table_name,
            oi.product_id,
            oi.product_name,
            oi.item_detail,
            oi.qty,
            oi.note,
            oi.status AS item_status,
            oi.sent_at,
            oi.served_at,
            oi.cancelled_at,
            oi.updated_at,
            oi.cancel_request_status,
            oi.cancel_request_note,
            oi.cancel_request_reason,
            oi.cancel_requested_at,
            oi.cancel_request_prev_status,
            oi.cancel_decided_at,
            oi.cancel_decided_by,
            oi.cancel_rejected_note,
            oi.cancel_rejected_reason,
            p.kitchen_station_id,
            ks.station_name,
            ks.station_name_th,
            ks.station_name_en,
            {$stationDisplaySql} AS station_display_name,
            CASE
                WHEN oi.status = 'served' THEN 'served'
                WHEN oi.status IN ('cancel', 'cancelled', 'canceled') OR COALESCE(oi.cancel_request_status, '') = 'approved' THEN 'cancelled'
                ELSE 'served'
            END AS history_status
        ");

        $builder->join('orders o', 'o.id = oi.order_id AND o.tenant_id = oi.tenant_id', 'inner');
        $builder->join('products p', 'p.id = oi.product_id AND p.tenant_id = oi.tenant_id', 'left');
        $builder->join('restaurant_tables rt', 'rt.id = o.table_id AND rt.tenant_id = o.tenant_id', 'left');
        $builder->join('kitchen_stations ks', 'ks.id = p.kitchen_station_id AND ks.tenant_id = oi.tenant_id', 'left');

        $builder->where('oi.tenant_id', $tenantId);
        $builder->where('o.tenant_id', $tenantId);

        if ($effectiveBranchId > 0) {
            $builder->where('o.branch_id', $effectiveBranchId);
        }

        $builder->groupStart()
            ->groupStart()
                ->where('oi.status', 'served')
                ->where('oi.served_at IS NOT NULL', null, false)
                ->where('oi.served_at >=', $fromDate)
            ->groupEnd()
            ->orGroupStart()
                ->groupStart()
                    ->whereIn('oi.status', ['cancel', 'cancelled', 'canceled'])
                    ->orWhere('oi.cancel_request_status', 'approved')
                ->groupEnd()
                ->groupStart()
                    ->where('oi.cancelled_at >=', $fromDate)
                    ->orGroupStart()
                        ->where('oi.cancelled_at', null)
                        ->where('oi.updated_at >=', $fromDate)
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd()
        ->groupEnd();

        if ($stationId) {
            $builder->where('p.kitchen_station_id', $stationId);
        }

        if ($stationOnly) {
            $builder->where('p.kitchen_station_id IS NOT NULL', null, false);
        }

        $builder->orderBy("COALESCE(oi.served_at, oi.cancelled_at, oi.cancel_decided_at, oi.updated_at)", 'DESC', false);
        $builder->limit($limit);

        return $builder->get()->getResultArray();
    }
}
