<?php

namespace App\Models;

class OrderItemOptionModel extends TenantScopedModel
{
    protected $table            = 'order_item_options';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'tenant_id',
        'order_item_id',
        'option_group',
        'option_name',
        'price_adjust',
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

    public function deleteByOrderItem(int $orderItemId): bool
    {
        return (bool) $this->where('tenant_id', $this->currentTenantId())
            ->where('order_item_id', $orderItemId)
            ->delete();
    }
}