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
    ];
}