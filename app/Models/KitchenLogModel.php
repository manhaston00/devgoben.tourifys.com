<?php

namespace App\Models;

class KitchenLogModel extends TenantScopedModel
{
    protected $table            = 'kitchen_logs';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'tenant_id',
        'order_item_id',
        'log_type',
        'remark',
    ];

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    public function getByOrderItem(int $orderItemId): array
    {
        return $this->scoped()
            ->where('order_item_id', $orderItemId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function addLog(int $orderItemId, string $logType, ?string $remark = null): bool
    {
        return (bool) $this->insert([
            'order_item_id' => $orderItemId,
            'log_type'      => $logType,
            'remark'        => $remark,
        ]);
    }
}