<?php

namespace App\Models;

class KitchenTicketModel extends TenantScopedModel
{
    protected $table            = 'kitchen_tickets';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'tenant_id',
        'order_id',
        'ticket_no',
        'status',
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

    public function getLatestByOrder(int $orderId): ?array
    {
        return $this->scoped()
            ->where('order_id', $orderId)
            ->orderBy('id', 'DESC')
            ->first();
    }
}