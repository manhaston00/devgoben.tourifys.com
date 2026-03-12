<?php

namespace App\Models;

class ZoneModel extends TenantScopedModel
{
    protected $table            = 'zones';
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
        'zone_name_th',
        'zone_name_en',
        'description_th',
        'description_en',
        'sort_order',
        'status',
    ];

    public function getTenantZones(?int $branchId = null, bool $onlyActive = false): array
    {
        $builder = $this->scopedBuilder()
            ->select('zones.*, branches.branch_code, branches.branch_name_th, branches.branch_name_en')
            ->join('branches', 'branches.id = zones.branch_id AND branches.deleted_at IS NULL', 'left')
            ->where('zones.deleted_at IS NULL', null, false);

        if ($branchId !== null && $branchId > 0) {
            $builder->where('zones.branch_id', $branchId);
        }

        if ($onlyActive) {
            $builder->where('zones.status', 1);
        }

        return $builder
            ->orderBy('zones.sort_order', 'ASC')
            ->orderBy('zones.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getZonesWithBranch(?int $branchId = null, bool $onlyActive = false): array
    {
        return $this->getTenantZones($branchId, $onlyActive);
    }

    public function findTenantZone(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->scopedBuilder()
            ->select('zones.*, branches.branch_code, branches.branch_name_th, branches.branch_name_en')
            ->join('branches', 'branches.id = zones.branch_id AND branches.deleted_at IS NULL', 'left')
            ->where('zones.deleted_at IS NULL', null, false)
            ->where('zones.id', $id)
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    public function getZonesByBranch(int $branchId, bool $onlyActive = false): array
    {
        if ($branchId <= 0) {
            return [];
        }

        return $this->getTenantZones($branchId, $onlyActive);
    }
}