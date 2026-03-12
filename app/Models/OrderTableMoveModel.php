<?php

namespace App\Models;

class OrderTableMoveModel extends TenantScopedModel
{
    protected $table = 'order_table_moves';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'order_id',
        'from_table_id',
        'to_table_id',
        'moved_by',
        'reason',
    ];

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];
	
	public function getLatestByOrder(int $orderId): ?array
	{
		return $this->scoped()
			->where('order_id', $orderId)
			->orderBy('id', 'DESC')
			->first();
	}
}