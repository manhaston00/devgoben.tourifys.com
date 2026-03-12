<?php

namespace App\Models;

class RoleModel extends TenantScopedModel
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';
    protected $useTimestamps    = true;

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    protected $allowedFields = [
        'tenant_id',
        'role_name',
        'role_name_th',
        'role_name_en',
        'description',
        'description_th',
        'description_en',
        'status',
    ];

    protected function tenantRoleBuilder(?int $tenantId = null)
    {
        $builder = $this->builder()
            ->where('roles.deleted_at', null);

        if ($tenantId !== null && $tenantId > 0) {
            $builder->where('roles.tenant_id', $tenantId);
        } else {
            $builder = $this->scopedBuilder()
                ->where('roles.deleted_at', null);
        }

        return $builder;
    }

    public function getTenantRoles($tenantIdOrOnlyActive = null, ?bool $onlyActive = null): array
    {
        $tenantId = null;

        if (is_int($tenantIdOrOnlyActive)) {
            $tenantId = $tenantIdOrOnlyActive;
            $onlyActive = $onlyActive ?? false;
        } elseif (is_bool($tenantIdOrOnlyActive)) {
            $onlyActive = $tenantIdOrOnlyActive;
        } else {
            $onlyActive = $onlyActive ?? false;
        }

        $builder = $this->tenantRoleBuilder($tenantId);

        if ($onlyActive) {
            $builder->where('roles.status', 1);
        }

        return $builder
            ->orderBy('roles.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getAssignableRoles(bool $onlyActive = true): array
    {
        return $this->getTenantRoles($onlyActive);
    }

    public function getRoles(bool $onlyActive = false): array
    {
        return $this->getTenantRoles($onlyActive);
    }

    public function getActiveRoles(): array
    {
        return $this->getTenantRoles(true);
    }

    public function findTenantRole(int $id, ?int $tenantId = null): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->tenantRoleBuilder($tenantId)
            ->where('roles.id', $id)
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    public function findVisibleRole(int $id): ?array
    {
        return $this->findTenantRole($id);
    }

    public function getRole(int $id): ?array
    {
        return $this->findTenantRole($id);
    }

    public function getTenantAdminRole(?int $tenantId = null): ?array
    {
        $builder = $this->tenantRoleBuilder($tenantId)
            ->where('roles.status', 1)
            ->groupStart()
                ->where('LOWER(COALESCE(roles.role_name, ""))', 'admin')
                ->orWhere('LOWER(COALESCE(roles.role_name_en, ""))', 'admin')
                ->orWhere('LOWER(COALESCE(roles.role_name, ""))', 'administrator')
                ->orWhere('LOWER(COALESCE(roles.role_name_en, ""))', 'administrator')
                ->orWhere('LOWER(COALESCE(roles.role_name, ""))', 'ผู้ดูแลระบบ')
                ->orWhere('LOWER(COALESCE(roles.role_name_th, ""))', 'ผู้ดูแลระบบ')
            ->groupEnd();

        $row = $builder
            ->orderBy('roles.id', 'ASC')
            ->get()
            ->getRowArray();

        if (is_array($row)) {
            return $row;
        }

        $fallback = $this->tenantRoleBuilder($tenantId)
            ->where('roles.status', 1)
            ->orderBy('roles.id', 'ASC')
            ->get()
            ->getRowArray();

        return is_array($fallback) ? $fallback : null;
    }

    public function getTenantAdminRoleId(?int $tenantId = null): ?int
    {
        $row = $this->getTenantAdminRole($tenantId);

        return ! empty($row['id']) ? (int) $row['id'] : null;
    }

    public function updateTenantRole(int $id, array $data, ?int $tenantId = null): bool
    {
        $row = $this->findTenantRole($id, $tenantId);
        if (! $row) {
            return false;
        }

        unset($data['tenant_id'], $data['id'], $data['created_at'], $data['deleted_at']);

        return parent::update($id, $data);
    }

    public function deleteTenantRole(int $id, ?int $tenantId = null): bool
    {
        $row = $this->findTenantRole($id, $tenantId);
        if (! $row) {
            return false;
        }

        return (bool) parent::delete($id);
    }
}