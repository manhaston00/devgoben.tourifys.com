<?php

namespace App\Models;

class OrderMergeModel extends TenantScopedModel
{
    protected $table = 'order_merges';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = true;

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

    public function getLatestBySourceOrder(int $orderId): ?array
    {
        return $this->scoped()
            ->where('source_order_id', $orderId)
            ->orderBy('id', 'DESC')
            ->first();
    }
}