<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionLogModel extends Model
{
    protected $table            = 'subscription_logs';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'subscription_id',
        'action',
        'meta',
        'created_at',
    ];

    public function addLog(int $subscriptionId, string $action, array $meta = []): bool
    {
        return (bool) $this->insert([
            'subscription_id' => $subscriptionId,
            'action'          => $action,
            'meta'            => empty($meta) ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}
