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
	
	public function getMonitorBoardRows(
		int $tenantId,
		int $branchId = 0,
		?int $stationId = null,
		bool $stationOnly = false
	): array {
		if ($tenantId <= 0) {
			return [];
		}

		$builder = $this->db->table('kitchen_tickets kt');

		$builder->select("
			kt.id AS ticket_id,
			kt.ticket_no,
			kt.status AS ticket_status,
			kt.order_id,
			o.order_number,
			o.branch_id,
			o.table_id,
			rt.table_name,
			oi.id AS order_item_id,
			oi.product_id,
			oi.product_name,
			oi.item_detail,
			oi.qty,
			oi.note,
			oi.status AS item_status,
			oi.sent_at,
			oi.served_at,
			p.kitchen_station_id,
			ks.station_name,
			ks.station_name_th,
			ks.station_name_en
		");

		$builder->join('orders o', 'o.id = kt.order_id', 'inner');
		$builder->join('order_items oi', 'oi.order_id = o.id', 'inner');
		$builder->join('products p', 'p.id = oi.product_id', 'left');
		$builder->join('restaurant_tables rt', 'rt.id = o.table_id', 'left');
		$builder->join('kitchen_stations ks', 'ks.id = p.kitchen_station_id', 'left');

		$builder->where('kt.tenant_id', $tenantId);
		$builder->where('o.tenant_id', $tenantId);
		$builder->where('oi.tenant_id', $tenantId);

		if ($branchId > 0) {
			$builder->where('o.branch_id', $branchId);
		}

		$builder->whereIn('oi.status', ['sent', 'cooking', 'served']);

		if ($stationOnly && $stationId) {
			$builder->where('p.kitchen_station_id', $stationId);
		}

		if (! $stationOnly && $stationId) {
			$builder->where('p.kitchen_station_id', $stationId);
		}

		$builder->orderBy('kt.id', 'DESC');
		$builder->orderBy('oi.id', 'ASC');

		return $builder->get()->getResultArray();
	}
}