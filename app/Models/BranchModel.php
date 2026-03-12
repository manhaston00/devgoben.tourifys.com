<?php

namespace App\Models;

class BranchModel extends TenantScopedModel
{
    protected $table            = 'branches';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';
    protected $useTimestamps    = true;

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    protected $allowedFields = [
        'tenant_id',
        'branch_code',
        'branch_name',
        'branch_name_th',
        'branch_name_en',
        'phone',
        'email',
        'address_th',
        'address_en',
        'address',
        'tax_id',
        'logo',
        'default_locale',
        'timezone',
        'status',
        'is_head_office',
        'sort_order',
        'opened_at',
    ];

    public function getTenantBranches(bool $onlyActive = false): array
    {
        $builder = $this->scopedBuilder()
            ->where('branches.deleted_at IS NULL', null, false);

        if ($onlyActive) {
            $builder->where('branches.status', 1);
        }

        return $builder
            ->orderBy('branches.sort_order', 'ASC')
            ->orderBy('branches.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getActiveBranches(): array
    {
        return $this->getTenantBranches(true);
    }

    public function getBranches(): array
    {
        return $this->getTenantBranches(false);
    }

    public function findTenantBranch(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->scopedBuilder()
            ->where('branches.deleted_at IS NULL', null, false)
            ->where('branches.id', $id)
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    public function getBranch(int $id): ?array
    {
        return $this->findTenantBranch($id);
    }

    public function getHeadquartersBranch(): ?array
    {
        $row = $this->scopedBuilder()
            ->where('branches.deleted_at IS NULL', null, false)
            ->orderBy('branches.is_head_office', 'DESC')
            ->orderBy('branches.sort_order', 'ASC')
            ->orderBy('branches.id', 'ASC')
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }
}