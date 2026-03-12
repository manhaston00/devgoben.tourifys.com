<?php

namespace App\Models;

class KitchenStationModel extends TenantScopedModel
{
    protected $table            = 'kitchen_stations';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;
    protected $useSoftDeletes   = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    protected $allowedFields = [
        'tenant_id',
        'station_name',
        'station_name_th',
        'station_name_en',
        'color_tag',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function findTenantStation(int $id): ?array
    {
        return $this->scoped()
            ->where($this->table . '.deleted_at', null)
            ->where($this->table . '.id', $id)
            ->first();
    }

    public function findVisible(int $id): ?array
    {
        return $this->findTenantStation($id);
    }

    public function getStationsWithBranch(?int $branchId = null, bool $onlyActive = false): array
    {
        $builder = $this->scoped()
            ->where($this->table . '.deleted_at', null);

        if ($onlyActive) {
            $builder->where($this->table . '.status', 1);
        }

        return $builder
            ->orderBy($this->table . '.id', 'ASC')
            ->findAll();
    }

    public function getActiveStationsByBranch(?int $branchId = null): array
    {
        return $this->getVisibleStations(true);
    }

    public function getVisibleStations(bool $onlyActive = false): array
    {
        return $this->getStationsWithBranch(null, $onlyActive);
    }

    public function getDisplayName(array $row): string
    {
        $locale = service('request')->getLocale();

        if ($locale === 'th') {
            return trim((string) ($row['station_name_th'] ?? '')) !== ''
                ? (string) $row['station_name_th']
                : (string) ($row['station_name'] ?? '');
        }

        return trim((string) ($row['station_name_en'] ?? '')) !== ''
            ? (string) $row['station_name_en']
            : (string) ($row['station_name'] ?? '');
    }
}