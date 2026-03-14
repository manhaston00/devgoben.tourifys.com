<?php

namespace App\Models;

class PaymentModel extends TenantScopedModel
{
    protected $table            = 'payments';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'tenant_id',
        'order_id',
        'payment_method',
        'amount',
        'received_by',
        'change_amount',
        'remark',
        'paid_at',
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

    protected function getScopedOrderPaymentBuilder(int $orderId)
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

    public function getByOrder(int $orderId): array
    {
        if ($orderId <= 0) {
            return [];
        }

        return $this->getScopedOrderPaymentBuilder($orderId)
            ->orderBy($this->table . '.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getOneById(int $paymentId): ?array
    {
        if ($paymentId <= 0) {
            return null;
        }

        $builder = $this->db->table($this->table)
            ->select($this->table . '.*')
            ->join('orders', 'orders.id = ' . $this->table . '.order_id AND orders.tenant_id = ' . $this->table . '.tenant_id', 'inner')
            ->where($this->table . '.tenant_id', $this->currentTenantId())
            ->where($this->table . '.id', $paymentId);

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'orders')) {
            $builder->where('orders.branch_id', $branchId);
        }

        return $builder->get()->getRowArray() ?: null;
    }

    public function sumPaidByOrder(int $orderId): float
    {
        if ($orderId <= 0) {
            return 0.0;
        }

        $row = $this->getScopedOrderPaymentBuilder($orderId)
            ->select('COALESCE(SUM(' . $this->table . '.amount), 0) AS paid_total', false)
            ->get()
            ->getRowArray();

        return (float) ($row['paid_total'] ?? 0);
    }
}
