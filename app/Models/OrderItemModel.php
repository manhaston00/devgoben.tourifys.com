<?php

namespace App\Models;

class OrderItemModel extends TenantScopedModel
{
    protected $table = 'order_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'tenant_id',
        'order_id',
        'product_id',
        'product_name',
        'item_detail',
        'option_price',
        'option_summary',
        'price',
        'qty',
        'line_total',
        'note',
        'status',
        'kitchen_ticket_id',
        'sent_at',
        'served_at',
        'cancelled_at',
        'cancelled_by',
        'cancel_request_status',
        'cancel_request_note',
        'cancel_request_reason',
        'cancel_requested_at',
        'cancel_requested_by',
        'cancel_request_prev_status',
        'cancel_decided_at',
        'cancel_decided_by',
        'cancel_rejected_reason',
        'cancel_rejected_note',
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

    protected function getScopedOrderBuilder(int $orderId)
    {
        $builder = $this->db->table($this->table)
            ->select($this->table . '.*')
            ->join('orders', 'orders.id = ' . $this->table . '.order_id AND orders.tenant_id = ' . $this->table . '.tenant_id', 'inner')
            ->where($this->table . '.tenant_id', $this->currentTenantId())
            ->where($this->table . '.order_id', $orderId);

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'orders')) {
            $builder->where('orders.branch_id', $branchId);
        }

        return $builder;
    }

    protected function applyPendingFilter($builder)
    {
        return $builder->where($this->table . '.status', 'pending');
    }

    public function getByOrder(int $orderId): array
    {
        if ($orderId <= 0) {
            return [];
        }

        return $this->getScopedOrderBuilder($orderId)
            ->orderBy($this->table . '.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getOneById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $builder = $this->db->table($this->table)
            ->select($this->table . '.*')
            ->join('orders', 'orders.id = ' . $this->table . '.order_id AND orders.tenant_id = ' . $this->table . '.tenant_id', 'inner')
            ->where($this->table . '.tenant_id', $this->currentTenantId())
            ->where($this->table . '.id', $id);

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'orders')) {
            $builder->where('orders.branch_id', $branchId);
        }

        return $builder->get()->getRowArray() ?: null;
    }

    public function getPendingByOrder(int $orderId): array
    {
        if ($orderId <= 0) {
            return [];
        }

        return $this->applyPendingFilter($this->getScopedOrderBuilder($orderId))
            ->orderBy($this->table . '.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function countPendingByOrder(int $orderId): int
    {
        if ($orderId <= 0) {
            return 0;
        }

        return (int) $this->applyPendingFilter($this->getScopedOrderBuilder($orderId))
            ->countAllResults();
    }

    public function lockPendingByOrder(int $tenantId, int $orderId): array
    {
        if ($tenantId <= 0 || $orderId <= 0) {
            return [];
        }

        $currentTenantId = $this->currentTenantId();
        if ($tenantId !== $currentTenantId) {
            return [];
        }

        $params = [$tenantId, $orderId];
        $sql = "SELECT oi.*
                FROM order_items oi
                INNER JOIN orders o
                    ON o.id = oi.order_id
                   AND o.tenant_id = oi.tenant_id
                WHERE oi.tenant_id = ?
                  AND oi.order_id = ?
                  AND oi.status = 'pending'";

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'orders')) {
            $sql .= " AND o.branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY oi.id ASC FOR UPDATE";

        return $this->db->query($sql, $params)->getResultArray();
    }

    public function countActiveByTicket(int $tenantId, int $ticketId): int
    {
        if ($tenantId <= 0 || $ticketId <= 0) {
            return 0;
        }

        if ($tenantId !== $this->currentTenantId()) {
            return 0;
        }

        $builder = $this->db->table($this->table)
            ->select('COUNT(' . $this->table . '.id) AS aggregate_count', false)
            ->join('orders', 'orders.id = ' . $this->table . '.order_id AND orders.tenant_id = ' . $this->table . '.tenant_id', 'inner')
            ->where($this->table . '.tenant_id', $tenantId)
            ->where($this->table . '.kitchen_ticket_id', $ticketId)
            ->whereNotIn($this->table . '.status', ['served', 'cancel']);

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'orders')) {
            $builder->where('orders.branch_id', $branchId);
        }

        $row = $builder->get()->getRowArray();

        return (int) ($row['aggregate_count'] ?? 0);
    }

    public function getBillableByOrder(int $orderId): array
    {
        if ($orderId <= 0) {
            return [];
        }

        $builder = $this->getScopedOrderBuilder($orderId)
            ->groupStart()
                ->whereNotIn($this->table . '.status', ['pending', 'cancel'])
                ->groupStart()
                    ->where($this->table . '.cancelled_at', null)
                    ->orWhere($this->table . '.cancel_request_status !=', 'approved')
                    ->orWhere($this->table . '.cancel_request_status', null)
                ->groupEnd()
            ->groupEnd()
            ->orderBy($this->table . '.id', 'ASC');

        return $builder->get()->getResultArray();
    }

    public function getNonBillableByOrder(int $orderId): array
    {
        if ($orderId <= 0) {
            return [];
        }

        $builder = $this->getScopedOrderBuilder($orderId)
            ->groupStart()
                ->where($this->table . '.status', 'pending')
                ->orWhere($this->table . '.status', 'cancel')
                ->orWhere($this->table . '.cancel_request_status', 'approved')
                ->where($this->table . '.cancelled_at IS NOT NULL', null, false)
            ->groupEnd()
            ->orderBy($this->table . '.id', 'ASC');

        return $builder->get()->getResultArray();
    }
}
