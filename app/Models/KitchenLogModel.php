<?php

namespace App\Models;

class KitchenLogModel extends TenantScopedModel
{
    protected $table = 'kitchen_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'order_id',
        'ticket_id',
        'order_item_id',
        'log_type',
        'from_status',
        'to_status',
        'remark',
        'action_by',
        'action_source',
        'request_uuid',
        'meta_json',
    ];

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    protected function currentBranchId(): int
    {
        if (function_exists('current_branch_id')) {
            return (int) (current_branch_id() ?: 0);
        }

        return (int) (session('branch_id') ?? 0);
    }

    protected function getScopedLogBuilder()
    {
        $builder = $this->db->table($this->table)
            ->select($this->table . '.*')
            ->where($this->table . '.tenant_id', $this->currentTenantId());

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', $this->table)) {
            $builder->groupStart()
                ->where($this->table . '.branch_id', $branchId)
                ->orWhere($this->table . '.branch_id IS NULL', null, false)
            ->groupEnd();
        }

        return $builder;
    }

    protected function resolveOrderItemContext(int $orderItemId): ?array
    {
        if ($orderItemId <= 0) {
            return null;
        }

        $builder = $this->db->table('order_items oi')
            ->select('oi.id, oi.order_id, oi.kitchen_ticket_id, o.branch_id')
            ->join('orders o', 'o.id = oi.order_id AND o.tenant_id = oi.tenant_id', 'inner')
            ->where('oi.tenant_id', $this->currentTenantId())
            ->where('oi.id', $orderItemId);

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'orders')) {
            $builder->where('o.branch_id', $branchId);
        }

        return $builder->get()->getRowArray() ?: null;
    }

    public function getByOrderItem(int $orderItemId): array
    {
        if ($orderItemId <= 0) {
            return [];
        }

        return $this->getScopedLogBuilder()
            ->where($this->table . '.order_item_id', $orderItemId)
            ->orderBy($this->table . '.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getByTicket(int $ticketId): array
    {
        if ($ticketId <= 0) {
            return [];
        }

        return $this->getScopedLogBuilder()
            ->where($this->table . '.ticket_id', $ticketId)
            ->orderBy($this->table . '.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function addLog(int $orderItemId, string $logType, ?string $remark = null, array $extra = []): bool
    {
        $logType = $this->normalizeLogType($logType);

        if ($orderItemId <= 0 || $logType === '') {
            return false;
        }

        $context = $this->resolveOrderItemContext($orderItemId);
        if (! $context) {
            return false;
        }

        $payload = array_merge([
            'order_item_id' => $orderItemId,
            'order_id'      => (int) ($context['order_id'] ?? 0),
            'ticket_id'     => (int) ($context['kitchen_ticket_id'] ?? 0) ?: null,
            'branch_id'     => isset($context['branch_id']) ? (int) $context['branch_id'] : null,
            'log_type'      => $logType,
            'remark'        => $remark,
        ], $extra);

        if (isset($payload['meta_json']) && is_array($payload['meta_json'])) {
            $payload['meta_json'] = json_encode($payload['meta_json'], JSON_UNESCAPED_UNICODE);
        }

        if (array_key_exists('ticket_id', $payload) && (int) $payload['ticket_id'] <= 0) {
            $payload['ticket_id'] = null;
        }

        if (array_key_exists('order_id', $payload) && (int) $payload['order_id'] <= 0) {
            $payload['order_id'] = null;
        }

        if (array_key_exists('branch_id', $payload) && (int) $payload['branch_id'] <= 0) {
            $payload['branch_id'] = null;
        }

        return (bool) $this->insert($payload);
    }

    protected function normalizeLogType(string $logType): string
    {
        $logType = strtolower(trim($logType));

        $map = [
            'pending'        => 'new',
            'new'            => 'new',
            'sent'           => 'new',
            'printed'        => 'printed',
            'cooking'        => 'cooking',
            'preparing'      => 'cooking',
            'ready'          => 'ready',
            'served'         => 'served',
            'cancel'         => 'cancel',
            'cancelled'      => 'cancel',
            'canceled'       => 'cancel',
            'cancel_request' => 'cancel',
        ];

        return $map[$logType] ?? '';
    }
}
