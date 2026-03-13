<?php

namespace App\Models;

class AuditLogModel extends BaseTenantModel
{
    protected $table         = 'audit_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'user_id',
        'actor_name',
        'target_type',
        'target_id',
        'action_key',
        'action_label',
        'ref_code',
        'order_id',
        'table_id',
        'payment_id',
        'meta_json',
        'old_values_json',
        'new_values_json',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    public function add(array $data): bool
    {
        if (empty($data['tenant_id'])) {
            $data['tenant_id'] = (int) (session('tenant_id') ?? 0);
        }

        if (! array_key_exists('branch_id', $data)) {
            $branchId = (int) (session('branch_id') ?? 0);
            $data['branch_id'] = $branchId > 0 ? $branchId : null;
        }

        if (! array_key_exists('user_id', $data)) {
            $userId = (int) (session('user_id') ?? 0);
            $data['user_id'] = $userId > 0 ? $userId : null;
        }

        if (! array_key_exists('actor_name', $data) || trim((string) $data['actor_name']) === '') {
            $actorName = trim((string) (session('full_name') ?? session('username') ?? ''));
            $data['actor_name'] = $actorName !== '' ? $actorName : null;
        }

        $data['ip_address'] = $data['ip_address'] ?? service('request')->getIPAddress();
        $data['user_agent'] = $data['user_agent'] ?? substr((string) service('request')->getUserAgent(), 0, 500);
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');

        foreach (['meta_json', 'old_values_json', 'new_values_json'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
            }
        }

        return (bool) $this->insert($data);
    }

    public function search(array $filters = [], int $limit = 300): array
    {
        $builder = $this->builder();
        $builder->select('audit_logs.*, users.username, users.full_name, branches.branch_name, branches.branch_name_th, branches.branch_name_en, orders.order_number, restaurant_tables.table_name');
        $builder->join('users', 'users.id = audit_logs.user_id AND users.deleted_at IS NULL', 'left');
        $builder->join('branches', 'branches.id = audit_logs.branch_id AND branches.deleted_at IS NULL', 'left');
        $builder->join('orders', 'orders.id = audit_logs.order_id', 'left');
        $builder->join('restaurant_tables', 'restaurant_tables.id = audit_logs.table_id AND restaurant_tables.deleted_at IS NULL', 'left');

        $branchId = (int) ($filters['branch_id'] ?? 0);
        if ($branchId > 0) {
            $builder->where('audit_logs.branch_id', $branchId);
        }

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $builder->where('audit_logs.user_id', $userId);
        }

        $actionKey = trim((string) ($filters['action_key'] ?? ''));
        if ($actionKey !== '') {
            $builder->where('audit_logs.action_key', $actionKey);
        }

        $orderId = (int) ($filters['order_id'] ?? 0);
        if ($orderId > 0) {
            $builder->where('audit_logs.order_id', $orderId);
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $builder->where('DATE(audit_logs.created_at) >=', $dateFrom);
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $builder->where('DATE(audit_logs.created_at) <=', $dateTo);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $builder->groupStart()
                ->like('audit_logs.actor_name', $search)
                ->orLike('audit_logs.action_label', $search)
                ->orLike('audit_logs.action_key', $search)
                ->orLike('audit_logs.ref_code', $search)
                ->orLike('orders.order_number', $search)
                ->orLike('restaurant_tables.table_name', $search)
            ->groupEnd();
        }

        return $builder
            ->orderBy('audit_logs.created_at', 'DESC')
            ->limit(max(1, min(1000, $limit)))
            ->get()
            ->getResultArray();
    }

    public function getActionOptions(): array
    {
        return $this->builder()
            ->select('action_key, MAX(action_label) AS action_label')
            ->groupBy('action_key')
            ->orderBy('action_key', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getTimelineByOrderId(int $orderId): array
    {
        if ($orderId <= 0) {
            return [];
        }

        return $this->builder()
            ->select('audit_logs.*, users.username, users.full_name')
            ->join('users', 'users.id = audit_logs.user_id AND users.deleted_at IS NULL', 'left')
            ->where('audit_logs.order_id', $orderId)
            ->orderBy('audit_logs.created_at', 'ASC')
            ->orderBy('audit_logs.id', 'ASC')
            ->get()
            ->getResultArray();
    }
}
