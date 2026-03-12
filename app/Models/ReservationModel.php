<?php

namespace App\Models;

class ReservationModel extends TenantScopedModel
{
    protected $table            = 'reservations';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'reservation_code',
        'customer_name',
        'customer_phone',
        'reservation_date',
        'reservation_time',
        'guest_count',
        'duration_minutes',
        'zone_id',
        'table_id',
        'order_id',
        'status',
        'source',
        'special_note',
        'internal_note',
        'checkin_at',
        'seated_at',
        'completed_at',
        'cancelled_at',
        'no_show_at',
        'created_by',
        'updated_by',
        'deleted_at',
    ];

    protected function resolveBranchId(?int $branchId = null): ?int
    {
        if ($branchId !== null && $branchId > 0) {
            return $branchId;
        }

        if (function_exists('current_branch_id')) {
            $current = current_branch_id();
            return $current ?: null;
        }

        return null;
    }

    protected function applyBranchScope($builder, string $alias = 'r', ?int $branchId = null)
    {
        $resolved = $this->resolveBranchId($branchId);

        if ($resolved !== null && $resolved > 0) {
            $builder->where($alias . '.branch_id', $resolved);
        }

        return $builder;
    }

    protected function getZoneNameSelect(string $alias = 'z'): string
    {
        $fields = [];

        try {
            $fields = $this->db->getFieldNames('zones');
        } catch (\Throwable $e) {
            $fields = [];
        }

        $hasZoneNameTh = in_array('zone_name_th', $fields, true);
        $hasZoneNameEn = in_array('zone_name_en', $fields, true);
        $hasZoneName   = in_array('zone_name', $fields, true);

        if ($hasZoneNameTh && $hasZoneNameEn && $hasZoneName) {
            return "COALESCE(NULLIF({$alias}.zone_name_th, ''), NULLIF({$alias}.zone_name_en, ''), {$alias}.zone_name) AS zone_name, {$alias}.zone_name_th, {$alias}.zone_name_en";
        }

        if ($hasZoneNameTh && $hasZoneNameEn) {
            return "COALESCE(NULLIF({$alias}.zone_name_th, ''), {$alias}.zone_name_en) AS zone_name, {$alias}.zone_name_th, {$alias}.zone_name_en";
        }

        if ($hasZoneNameTh) {
            return "{$alias}.zone_name_th AS zone_name, {$alias}.zone_name_th, NULL AS zone_name_en";
        }

        if ($hasZoneName) {
            return "{$alias}.zone_name AS zone_name, {$alias}.zone_name AS zone_name_th, NULL AS zone_name_en";
        }

        return "'' AS zone_name, NULL AS zone_name_th, NULL AS zone_name_en";
    }

    public function generateReservationCode(): string
    {
        do {
            $code = 'RSV' . date('YmdHis') . rand(100, 999);

            $builder = $this->where('reservation_code', $code);

            $tenantId = $this->currentTenantId();
            if ($tenantId !== null && $tenantId > 0) {
                $builder->where('tenant_id', $tenantId);
            }

            $exists = $builder->first();
        } while ($exists);

        return $code;
    }

    public function getList(array $filters = [], ?int $branchId = null)
    {
        $builder = $this->db->table('reservations r');
        $zoneNameSelect = $this->getZoneNameSelect('z');

        $builder->select("
            r.*,
            {$zoneNameSelect},
            GROUP_CONCAT(DISTINCT t.table_name ORDER BY t.table_name SEPARATOR ', ') AS table_name
        ");

        $builder->join(
            'zones z',
            'z.id = r.zone_id
             AND z.tenant_id = r.tenant_id
             AND z.deleted_at IS NULL',
            'left'
        );

        $builder->join(
            'reservation_tables rt',
            'rt.reservation_id = r.id
             AND rt.tenant_id = r.tenant_id
             AND rt.deleted_at IS NULL',
            'left',
            false
        );

        $builder->join(
            'restaurant_tables t',
            't.id = rt.table_id
             AND t.tenant_id = r.tenant_id
             AND t.deleted_at IS NULL',
            'left'
        );

        $builder->where('r.deleted_at IS NULL', null, false);
        $builder->where('r.tenant_id', $this->currentTenantId());
        $this->applyBranchScope($builder, 'r', $branchId);

        if (! empty($filters['date'])) {
            $builder->where('r.reservation_date', $filters['date']);
        }

        if (! empty($filters['status'])) {
            $builder->where('r.status', $filters['status']);
        }

        if (! empty($filters['zone_id'])) {
            $builder->where('r.zone_id', (int) $filters['zone_id']);
        }

        if (! empty($filters['keyword'])) {
            $keyword = trim((string) $filters['keyword']);
            $builder->groupStart()
                ->like('r.customer_name', $keyword)
                ->orLike('r.customer_phone', $keyword)
                ->orLike('r.reservation_code', $keyword)
                ->groupEnd();
        }

        $builder->groupBy('r.id');
        $builder->orderBy('r.reservation_date', 'DESC');
        $builder->orderBy('r.reservation_time', 'DESC');

        return $builder->get()->getResultArray();
    }

    public function getFull(int $id, ?int $branchId = null)
    {
        $builder = $this->db->table('reservations r');
        $zoneNameSelect = $this->getZoneNameSelect('z');

        $builder->select("
            r.*,
            {$zoneNameSelect},
            GROUP_CONCAT(DISTINCT t.table_name ORDER BY t.table_name SEPARATOR ', ') AS table_name
        ");

        $builder->join(
            'zones z',
            'z.id = r.zone_id
             AND z.tenant_id = r.tenant_id
             AND z.deleted_at IS NULL',
            'left'
        );

        $builder->join(
            'reservation_tables rt',
            'rt.reservation_id = r.id
             AND rt.tenant_id = r.tenant_id
             AND rt.deleted_at IS NULL',
            'left',
            false
        );

        $builder->join(
            'restaurant_tables t',
            't.id = rt.table_id
             AND t.tenant_id = r.tenant_id
             AND t.deleted_at IS NULL',
            'left'
        );

        $builder->where('r.id', $id);
        $builder->where('r.deleted_at IS NULL', null, false);
        $builder->where('r.tenant_id', $this->currentTenantId());
        $this->applyBranchScope($builder, 'r', $branchId);
        $builder->groupBy('r.id');

        return $builder->get()->getRowArray();
    }

    public function getFullWithDeleted(int $id, ?int $branchId = null)
    {
        $builder = $this->db->table('reservations r');
        $zoneNameSelect = $this->getZoneNameSelect('z');

        $builder->select("
            r.*,
            {$zoneNameSelect},
            GROUP_CONCAT(DISTINCT t.table_name ORDER BY t.table_name SEPARATOR ', ') AS table_name
        ");

        $builder->join(
            'zones z',
            'z.id = r.zone_id
             AND z.tenant_id = r.tenant_id',
            'left'
        );

        $builder->join(
            'reservation_tables rt',
            'rt.reservation_id = r.id
             AND rt.tenant_id = r.tenant_id',
            'left'
        );

        $builder->join(
            'restaurant_tables t',
            't.id = rt.table_id
             AND t.tenant_id = r.tenant_id',
            'left'
        );

        $builder->where('r.id', $id);
        $builder->where('r.tenant_id', $this->currentTenantId());
        $this->applyBranchScope($builder, 'r', $branchId);
        $builder->groupBy('r.id');

        return $builder->get()->getRowArray();
    }

    public function softDeleteReservation(int $id): bool
    {
        return (bool) $this->deleteScoped($id);
    }

    public function restoreReservation(int $id): bool
    {
        return (bool) $this->withDeleted()
            ->where('id', $id)
            ->where('tenant_id', $this->currentTenantId())
            ->set(['deleted_at' => null])
            ->update();
    }

    public function getReservationDateTimeStart(string $reservationDate, string $reservationTime): string
    {
        return date('Y-m-d H:i:s', strtotime($reservationDate . ' ' . $reservationTime));
    }

    public function getReservationDateTimeEnd(string $reservationDate, string $reservationTime, int $durationMinutes = 60): string
    {
        $start = strtotime($reservationDate . ' ' . $reservationTime);
        $durationMinutes = $durationMinutes > 0 ? $durationMinutes : 60;

        return date('Y-m-d H:i:s', strtotime('+' . $durationMinutes . ' minutes', $start));
    }

    public function getConflictingReservationTableIds(
        string $reservationDate,
        string $reservationTime,
        int $durationMinutes = 60,
        ?int $excludeReservationId = null,
        ?int $branchId = null
    ): array {
        if ($reservationDate === '' || $reservationTime === '') {
            return [];
        }

        $startAt = $this->getReservationDateTimeStart($reservationDate, $reservationTime);
        $endAt   = $this->getReservationDateTimeEnd($reservationDate, $reservationTime, $durationMinutes);

        $builder = $this->db->table('reservation_tables rt');
        $builder->select("
            rt.table_id,
            r.id,
            r.reservation_date,
            r.reservation_time,
            r.duration_minutes,
            r.status,
            r.order_id,
            o.status AS order_status
        ");

        $builder->join(
            'reservations r',
            'r.id = rt.reservation_id
             AND r.tenant_id = rt.tenant_id',
            'inner'
        );

        $builder->join(
            'orders o',
            'o.id = r.order_id
             AND o.tenant_id = r.tenant_id',
            'left'
        );

        $builder->where('rt.deleted_at IS NULL', null, false);
        $builder->where('r.deleted_at IS NULL', null, false);
        $builder->where('r.tenant_id', $this->currentTenantId());
        $this->applyBranchScope($builder, 'r', $branchId);

        $builder->groupStart();
            $builder->whereIn('r.status', ['pending', 'confirmed', 'seated']);
            $builder->groupStart();
                $builder->where('r.order_id IS NULL', null, false);
                $builder->orWhere('o.status IS NULL', null, false);
                $builder->orWhereNotIn('o.status', ['paid', 'closed', 'completed']);
            $builder->groupEnd();
        $builder->groupEnd();

        if (! empty($excludeReservationId)) {
            $builder->where('r.id !=', $excludeReservationId);
        }

        $builder->where("
            TIMESTAMP(r.reservation_date, r.reservation_time) < " . $this->db->escape($endAt) . "
            AND
            DATE_ADD(
                TIMESTAMP(r.reservation_date, r.reservation_time),
                INTERVAL COALESCE(NULLIF(r.duration_minutes, 0), 60) MINUTE
            ) > " . $this->db->escape($startAt),
            null,
            false
        );

        $rows = $builder->get()->getResultArray();

        if (empty($rows)) {
            return [];
        }

        $tableIds = [];
        foreach ($rows as $row) {
            $tableId = (int) ($row['table_id'] ?? 0);
            if ($tableId > 0) {
                $tableIds[] = $tableId;
            }
        }

        return array_values(array_unique($tableIds));
    }

    public function getConflictingReservationsByTable(
        string $reservationDate,
        string $reservationTime,
        int $durationMinutes = 60,
        ?int $excludeReservationId = null,
        ?int $branchId = null
    ): array {
        if ($reservationDate === '' || $reservationTime === '') {
            return [];
        }

        $startAt = $this->getReservationDateTimeStart($reservationDate, $reservationTime);
        $endAt   = $this->getReservationDateTimeEnd($reservationDate, $reservationTime, $durationMinutes);

        $builder = $this->db->table('reservation_tables rt');
        $builder->select("
            rt.table_id,
            r.id AS reservation_id,
            r.customer_name,
            r.reservation_date,
            r.reservation_time,
            COALESCE(NULLIF(r.duration_minutes, 0), 60) AS duration_minutes,
            r.status,
            r.order_id,
            o.status AS order_status,
            DATE_ADD(
                TIMESTAMP(r.reservation_date, r.reservation_time),
                INTERVAL COALESCE(NULLIF(r.duration_minutes, 0), 60) MINUTE
            ) AS busy_until
        ");

        $builder->join(
            'reservations r',
            'r.id = rt.reservation_id
             AND r.tenant_id = rt.tenant_id',
            'inner'
        );

        $builder->join(
            'orders o',
            'o.id = r.order_id
             AND o.tenant_id = r.tenant_id',
            'left'
        );

        $builder->where('rt.deleted_at IS NULL', null, false);
        $builder->where('r.deleted_at IS NULL', null, false);
        $builder->where('r.tenant_id', $this->currentTenantId());
        $this->applyBranchScope($builder, 'r', $branchId);

        $builder->groupStart();
            $builder->whereIn('r.status', ['pending', 'confirmed', 'seated']);
            $builder->groupStart();
                $builder->where('r.order_id IS NULL', null, false);
                $builder->orWhere('o.status IS NULL', null, false);
                $builder->orWhereNotIn('o.status', ['paid', 'closed', 'completed']);
            $builder->groupEnd();
        $builder->groupEnd();

        if (! empty($excludeReservationId)) {
            $builder->where('r.id !=', $excludeReservationId);
        }

        $builder->where("
            TIMESTAMP(r.reservation_date, r.reservation_time) < " . $this->db->escape($endAt) . "
            AND
            DATE_ADD(
                TIMESTAMP(r.reservation_date, r.reservation_time),
                INTERVAL COALESCE(NULLIF(r.duration_minutes, 0), 60) MINUTE
            ) > " . $this->db->escape($startAt),
            null,
            false
        );

        $rows = $builder->get()->getResultArray();
        $map  = [];

        foreach ($rows as $row) {
            $tableId = (int) ($row['table_id'] ?? 0);
            if ($tableId <= 0) {
                continue;
            }

            $map[$tableId] = $row;
        }

        return $map;
    }

    public function isTimeConflict(
        array $tableIds,
        string $reservationDate,
        string $reservationTime,
        int $durationMinutes = 60,
        ?int $excludeReservationId = null,
        ?int $branchId = null
    ): bool {
        if (empty($tableIds) || $reservationDate === '' || $reservationTime === '') {
            return false;
        }

        $conflictTableIds = $this->getConflictingReservationTableIds(
            $reservationDate,
            $reservationTime,
            $durationMinutes,
            $excludeReservationId,
            $branchId
        );

        foreach ($tableIds as $tableId) {
            if (in_array((int) $tableId, $conflictTableIds, true)) {
                return true;
            }
        }

        return false;
    }
}