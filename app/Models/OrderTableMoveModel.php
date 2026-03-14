<?php

namespace App\Models;

class OrderTableMoveModel extends TenantScopedModel
{
    protected $table = 'order_table_moves';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'order_id',
        'from_table_id',
        'to_table_id',
        'moved_by',
        'reason',
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

    public function getLatestByOrder(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $query = $this->scoped()
            ->where('order_id', $orderId);

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', $this->table)) {
            $query->where($this->table . '.branch_id', $branchId);
        }

        return $query
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function getLatestByTargetTable(int $tableId): ?array
    {
        if ($tableId <= 0) {
            return null;
        }

        $query = $this->scoped()
            ->where('to_table_id', $tableId);

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', $this->table)) {
            $query->where($this->table . '.branch_id', $branchId);
        }

        return $query
            ->orderBy('id', 'DESC')
            ->first();
    }
}
