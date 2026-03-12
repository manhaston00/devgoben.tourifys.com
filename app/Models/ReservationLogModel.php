<?php

namespace App\Models;

class ReservationLogModel extends TenantScopedModel
{
    protected $table            = 'reservation_logs';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'tenant_id',
        'reservation_id',
        'action',
        'old_status',
        'new_status',
        'message',
        'created_by',
        'created_at',
    ];

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    public function getByReservation(int $reservationId): array
    {
        return $this->scoped()
            ->where('reservation_id', $reservationId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function addLog(
        int $reservationId,
        string $action,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $message = null,
        ?int $createdBy = null
    ): bool {
        return (bool) $this->insert([
            'reservation_id' => $reservationId,
            'action'         => $action,
            'old_status'     => $oldStatus,
            'new_status'     => $newStatus,
            'message'        => $message,
            'created_by'     => $createdBy ?: (session()->get('user_id') ?: null),
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }
}