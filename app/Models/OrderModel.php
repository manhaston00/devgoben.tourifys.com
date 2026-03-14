<?php

namespace App\Models;

class OrderModel extends TenantScopedModel
{
    protected $table            = 'orders';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'table_id',
        'reservation_id',
        'order_number',
        'order_type',
        'status',
        'merged_into_order_id',
        'subtotal',
        'discount_amount',
        'service_charge',
        'vat_amount',
        'total_price',
        'remark',
        'opened_by',
        'closed_by',
        'paid_by',
        'opened_at',
        'closed_at',
        'paid_at',
        'created_by',
    ];

    protected function currentBranchId(): int
    {
        if (function_exists('current_branch_id')) {
            return (int) (current_branch_id() ?: 0);
        }

        return (int) (session('branch_id') ?? 0);
    }

    public function scoped()
    {
        $query = parent::scoped();
        $branchId = $this->currentBranchId();

        if ($branchId > 0 && $this->db->fieldExists('branch_id', $this->table)) {
            $query->where($this->table . '.branch_id', $branchId);
        }

        return $query;
    }

    public function scopedBuilder()
    {
        $builder = parent::scopedBuilder();
        $branchId = $this->currentBranchId();

        if ($branchId > 0 && $this->db->fieldExists('branch_id', $this->table)) {
            $builder->where($this->table . '.branch_id', $branchId);
        }

        return $builder;
    }

    public function findActiveByTable(int $tableId, ?array $statuses = null): ?array
    {
        if ($tableId <= 0) {
            return null;
        }

        $statuses = $statuses ?: ['open', 'billing'];

        return $this->scoped()
            ->where('table_id', $tableId)
            ->whereIn('status', $statuses)
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function getOneById(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        return $this->findScoped($orderId);
    }
}
