<?php

namespace App\Models;

class OrderMergeModel extends TenantScopedModel
{
    protected $table = 'order_merges';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'source_order_id',
        'target_order_id',
        'source_table_id',
        'target_table_id',
        'merged_by',
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

    public function getLatestBySourceOrder(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $query = $this->scoped()
            ->where('source_order_id', $orderId);

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', $this->table)) {
            $query->where($this->table . '.branch_id', $branchId);
        }

        return $query->orderBy('id', 'DESC')->first();
    }

    public function getLatestBySourceTable(int $tableId): ?array
    {
        if ($tableId <= 0) {
            return null;
        }

        $query = $this->scoped()
            ->where('source_table_id', $tableId);

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', $this->table)) {
            $query->where($this->table . '.branch_id', $branchId);
        }

        return $query->orderBy('id', 'DESC')->first();
    }
}
