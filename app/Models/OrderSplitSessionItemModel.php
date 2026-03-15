<?php

namespace App\Models;

class OrderSplitSessionItemModel extends TenantScopedModel
{
    protected $table            = 'order_split_session_items';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    protected $allowedFields = [
        'session_id', 'tenant_id', 'branch_id', 'root_order_id', 'source_order_id', 'target_order_id',
        'source_order_item_id', 'target_order_item_id', 'product_id', 'product_name_snapshot', 'unit_price',
        'source_qty_before', 'moved_qty', 'source_qty_after', 'line_total', 'item_snapshot_json', 'created_at', 'updated_at',
    ];
}
