<?php

namespace App\Models;

class OrderItemModel extends TenantScopedModel
{
    protected $table            = 'order_items';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

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
        'sent_at',
        'served_at',
        'cancelled_at',
        'cancelled_by',
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

    public function getOneById(int $id): ?array
    {
        return $this->findScoped($id);
    }

    public function getPendingByOrder(int $orderId): array
    {
        return $this->scoped()
            ->where('order_id', $orderId)
            ->where('status', 'pending')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function countPendingByOrder(int $orderId): int
    {
        return (int) $this->scoped()
            ->where('order_id', $orderId)
            ->where('status', 'pending')
            ->countAllResults();
    }
}