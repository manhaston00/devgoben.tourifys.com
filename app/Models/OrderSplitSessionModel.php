<?php

namespace App\Models;

class OrderSplitSessionModel extends TenantScopedModel
{
    protected $table            = 'order_split_sessions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    protected $allowedFields = [
        'tenant_id', 'branch_id', 'session_code', 'root_order_id', 'source_order_id', 'target_order_id',
        'split_no', 'status', 'reason', 'requested_by', 'confirmed_by', 'cancelled_by',
        'requested_at', 'confirmed_at', 'cancelled_at', 'created_at', 'updated_at',
    ];
}
