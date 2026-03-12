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

    public function getByOrder(int $orderId): array
    {
        return $this->scoped()
            ->where('order_id', $orderId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}