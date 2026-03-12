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

    public function getByOrderItem(int $orderItemId): array
    {
        return $this->scoped()
            ->where('order_item_id', $orderItemId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function addLog(int $orderItemId, string $logType, ?string $remark = null, array $extra = []): bool
    {
        $logType = $this->normalizeLogType($logType);

        if ($orderItemId <= 0 || $logType === '') {
            return false;
        }

        $payload = array_merge([
            'order_item_id' => $orderItemId,
            'log_type'      => $logType,
            'remark'        => $remark,
        ], $extra);

        if (isset($payload['meta_json']) && is_array($payload['meta_json'])) {
            $payload['meta_json'] = json_encode($payload['meta_json'], JSON_UNESCAPED_UNICODE);
        }

        return (bool) $this->insert($payload);
    }

    protected function normalizeLogType(string $logType): string
    {
        $logType = strtolower(trim($logType));

        $map = [
            'pending'   => 'new',
            'new'       => 'new',
            'sent'      => 'new',
            'printed'   => 'printed',
            'cooking'   => 'cooking',
            'preparing' => 'cooking',
            'ready'     => 'ready',
            'served'    => 'served',
            'cancel'    => 'cancel',
            'cancelled' => 'cancel',
            'canceled'  => 'cancel',
        ];

        return $map[$logType] ?? '';
    }
}