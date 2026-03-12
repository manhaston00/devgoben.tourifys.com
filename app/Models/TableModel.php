<?php

namespace App\Models;

class TableModel extends TenantScopedModel
{
    protected $table            = 'restaurant_tables';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';
    protected $useTimestamps    = true;

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    protected $allowedFields = [
        'tenant_id',
        'branch_id',
        'zone_id',
        'table_name',
        'seat_count',
        'min_guest',
        'max_guest',
        'qr_code',
        'status',
        'is_active',
        'sort_order',
        'note',
    ];

    protected function getZoneNameSelect(string $alias = 'zones'): string
    {
        return "COALESCE(NULLIF({$alias}.zone_name_th, ''), NULLIF({$alias}.zone_name_en, '')) as zone_name, {$alias}.zone_name_th, {$alias}.zone_name_en";
    }

    protected function applyTenantFilters($builder): void
    {
        $tenantId = $this->currentTenantId();

        $builder->where('restaurant_tables.tenant_id', $tenantId);
        $builder->where('restaurant_tables.deleted_at IS NULL', null, false);
        $builder->join(
            'zones',
            'zones.id = restaurant_tables.zone_id
             AND zones.deleted_at IS NULL
             AND zones.tenant_id = restaurant_tables.tenant_id',
            'left'
        );
        $builder->join(
            'branches',
            'branches.id = restaurant_tables.branch_id
             AND branches.deleted_at IS NULL
             AND branches.tenant_id = restaurant_tables.tenant_id',
            'left'
        );
    }

    public function getTablesFull(?int $branchId = null, ?int $zoneId = null, ?int $guestCount = null, bool $onlyActive = false): array
    {
        $builder = $this->db->table('restaurant_tables');
        $zoneNameSelect = $this->getZoneNameSelect('zones');

        $builder->select("
            restaurant_tables.*,
            {$zoneNameSelect},
            branches.branch_code,
            branches.branch_name_th,
            branches.branch_name_en
        ");

        $this->applyTenantFilters($builder);

        if ($branchId !== null && $branchId > 0) {
            $builder->where('restaurant_tables.branch_id', $branchId);
        }

        if ($zoneId !== null && $zoneId > 0) {
            $builder->where('restaurant_tables.zone_id', $zoneId);
        }

        if ($onlyActive) {
            $builder->where('restaurant_tables.is_active', 1);
        }

        if ($guestCount !== null && $guestCount > 0) {
            $builder->groupStart()
                ->where('restaurant_tables.max_guest >=', $guestCount)
                ->orGroupStart()
                    ->where('restaurant_tables.max_guest', 0)
                    ->where('restaurant_tables.seat_count >=', $guestCount)
                ->groupEnd()
            ->groupEnd();
        }

        return $builder
            ->orderBy('restaurant_tables.zone_id', 'ASC')
            ->orderBy('restaurant_tables.sort_order', 'ASC')
            ->orderBy('restaurant_tables.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getUsableTables(?int $branchId = null, ?int $zoneId = null, ?int $guestCount = null): array
    {
        $builder = $this->db->table('restaurant_tables');
        $zoneNameSelect = $this->getZoneNameSelect('zones');

        $builder->select("
            restaurant_tables.*,
            {$zoneNameSelect},
            branches.branch_code,
            branches.branch_name_th,
            branches.branch_name_en
        ");

        $this->applyTenantFilters($builder);
        $builder->where('restaurant_tables.is_active', 1);
        $builder->where('restaurant_tables.status !=', 'disabled');

        if ($branchId !== null && $branchId > 0) {
            $builder->where('restaurant_tables.branch_id', $branchId);
        }

        if ($zoneId !== null && $zoneId > 0) {
            $builder->where('restaurant_tables.zone_id', $zoneId);
        }

        if ($guestCount !== null && $guestCount > 0) {
            $builder->groupStart()
                ->where('restaurant_tables.max_guest >=', $guestCount)
                ->orGroupStart()
                    ->where('restaurant_tables.max_guest', 0)
                    ->where('restaurant_tables.seat_count >=', $guestCount)
                ->groupEnd()
            ->groupEnd();
        }

        return $builder
            ->orderBy('restaurant_tables.zone_id', 'ASC')
            ->orderBy('restaurant_tables.sort_order', 'ASC')
            ->orderBy('restaurant_tables.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getActiveTable($tableId, ?int $branchId = null): ?array
    {
        $builder = $this->scopedBuilder()
            ->where('restaurant_tables.deleted_at IS NULL', null, false)
            ->where('restaurant_tables.id', (int) $tableId)
            ->where('restaurant_tables.is_active', 1);

        if ($branchId !== null && $branchId > 0) {
            $builder->where('restaurant_tables.branch_id', $branchId);
        }

        $row = $builder->get()->getRowArray();

        return is_array($row) ? $row : null;
    }

    public function getUsableTable($tableId, ?int $branchId = null): ?array
    {
        $builder = $this->scopedBuilder()
            ->where('restaurant_tables.deleted_at IS NULL', null, false)
            ->where('restaurant_tables.id', (int) $tableId)
            ->where('restaurant_tables.is_active', 1)
            ->where('restaurant_tables.status !=', 'disabled');

        if ($branchId !== null && $branchId > 0) {
            $builder->where('restaurant_tables.branch_id', $branchId);
        }

        $row = $builder->get()->getRowArray();

        return is_array($row) ? $row : null;
    }

    public function getTableMapByIds(array $tableIds, ?int $branchId = null): array
    {
        $tableIds = array_values(array_unique(array_filter(array_map('intval', $tableIds), static fn ($id) => $id > 0)));

        if ($tableIds === []) {
            return [];
        }

        $rows = $this->getTablesFull($branchId);
        $map  = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0 && in_array($id, $tableIds, true)) {
                $map[$id] = $row;
            }
        }

        return $map;
    }

    public function getActiveTablesByBranch(int $branchId): array
    {
        return $this->scopedBuilder()
            ->where('restaurant_tables.deleted_at IS NULL', null, false)
            ->where('restaurant_tables.branch_id', $branchId)
            ->where('restaurant_tables.is_active', 1)
            ->orderBy('restaurant_tables.zone_id', 'ASC')
            ->orderBy('restaurant_tables.sort_order', 'ASC')
            ->orderBy('restaurant_tables.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getUsableTablesByBranch(int $branchId, ?int $zoneId = null, ?int $guestCount = null): array
    {
        return $this->getUsableTables($branchId, $zoneId, $guestCount);
    }

    public function getCapacityValue(array $tableRow): int
    {
        $maxGuest  = (int) ($tableRow['max_guest'] ?? 0);
        $seatCount = (int) ($tableRow['seat_count'] ?? 0);

        return $maxGuest > 0 ? $maxGuest : $seatCount;
    }
}