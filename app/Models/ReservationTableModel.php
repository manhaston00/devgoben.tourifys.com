<?php

namespace App\Models;

class ReservationTableModel extends TenantScopedModel
{
    protected $table            = 'reservation_tables';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';
    protected $useTimestamps    = true;

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    protected $allowedFields = [
        'tenant_id',
        'reservation_id',
        'table_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getTableIdsByReservation(int $reservationId): array
    {
        if ($reservationId <= 0) {
            return [];
        }

        $rows = $this->scopedBuilder()
            ->select('reservation_tables.table_id')
            ->where('reservation_tables.deleted_at IS NULL', null, false)
            ->where('reservation_tables.reservation_id', $reservationId)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return [];
        }

        $tableIds = array_map(static function ($row) {
            return (int) ($row['table_id'] ?? 0);
        }, $rows);

        return array_values(array_unique(array_filter($tableIds, static fn ($id) => $id > 0)));
    }

    public function getRowsByReservation(int $reservationId): array
    {
        if ($reservationId <= 0) {
            return [];
        }

        return $this->scopedBuilder()
            ->where('reservation_tables.deleted_at IS NULL', null, false)
            ->where('reservation_tables.reservation_id', $reservationId)
            ->orderBy('reservation_tables.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function deleteByReservation(int $reservationId): bool
    {
        if ($reservationId <= 0) {
            return false;
        }

        $rows = $this->getRowsByReservation($reservationId);

        if (empty($rows)) {
            return true;
        }

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $this->delete($id);
            }
        }

        return true;
    }

    public function replaceReservationTables(int $reservationId, array $tableIds): bool
    {
        if ($reservationId <= 0) {
            return false;
        }

        $tableIds = array_values(array_unique(array_filter(array_map('intval', $tableIds), static fn ($id) => $id > 0)));

        // ลบของเดิมทั้งหมดก่อน
        $this->deleteByReservation($reservationId);

        // ถ้าไม่มีโต๊ะใหม่ ก็ถือว่า replace สำเร็จ
        if ($tableIds === []) {
            return true;
        }

        foreach ($tableIds as $tableId) {
            $this->insert([
                'reservation_id' => $reservationId,
                'table_id'       => $tableId,
            ]);
        }

        return true;
    }
}