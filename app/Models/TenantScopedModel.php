<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

class TenantScopedModel extends Model
{
    protected function currentTenantIdOrNull(): ?int
    {
        $tenantId = 0;

        if (function_exists('current_tenant_id')) {
            $tenantId = (int) (current_tenant_id() ?: 0);
        }

        if ($tenantId <= 0) {
            $tenantId = (int) (session('tenant_id') ?? 0);
        }

        if ($tenantId > 0) {
            return $tenantId;
        }

        if ((bool) (session('is_super_admin') ?? false) === true) {
            return null;
        }

        $roleName = strtolower((string) (session('role_name') ?? ''));
        if ($roleName === 'super_admin' || $roleName === 'superadmin') {
            return null;
        }

        return null;
    }

    protected function currentTenantId(): int
    {
        $tenantId = $this->currentTenantIdOrNull();

        if ($tenantId === null || $tenantId <= 0) {
            throw new RuntimeException('Tenant context is missing.');
        }

        return $tenantId;
    }

    protected function tenantColumn(): string
    {
        return $this->table . '.tenant_id';
    }

    public function scopedBuilder()
    {
        $builder = $this->builder();
        $tenantId = $this->currentTenantIdOrNull();

        if ($tenantId === null || $tenantId <= 0) {
            return $builder->where('1 = 0', null, false);
        }

        return $builder->where($this->tenantColumn(), $tenantId);
    }

    /**
     * คืนค่าเป็น Model query builder chain
     * เพื่อให้สามารถใช้ ->where()->findAll(), ->first(), ->countAllResults() ได้
     */
    public function scoped()
    {
        $tenantId = $this->currentTenantIdOrNull();

        if ($tenantId === null || $tenantId <= 0) {
            return $this->where('1 = 0', null, false);
        }

        return $this->where($this->tenantColumn(), $tenantId);
    }

    public function scopeTenant()
    {
        return $this->scoped();
    }

    protected function beforeInsertTenant(array $data): array
    {
        if (! isset($data['data']) || ! is_array($data['data'])) {
            $data['data'] = [];
        }

        $data['data']['tenant_id'] = $this->currentTenantId();

        return $data;
    }

    protected function beforeUpdateTenant(array $data): array
    {
        if (! isset($data['data']) || ! is_array($data['data'])) {
            $data['data'] = [];
        }

        unset($data['data']['tenant_id']);

        return $data;
    }

    public function find($id = null)
    {
        if ($id === null) {
            return $this->findAll();
        }

        return $this->scopedBuilder()
            ->where($this->table . '.' . $this->primaryKey, $id)
            ->get()
            ->getRowArray();
    }

    public function findScoped($id)
    {
        if ((int) $id <= 0) {
            return null;
        }

        return $this->scopedBuilder()
            ->where($this->table . '.' . $this->primaryKey, $id)
            ->get()
            ->getRowArray();
    }

    public function first()
    {
        return $this->scopedBuilder()
            ->limit(1)
            ->get()
            ->getRowArray();
    }

    public function firstScoped()
    {
        return $this->first();
    }

    public function findAll(int $limit = 0, int $offset = 0)
    {
        $builder = $this->scopedBuilder();

        if ($limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResultArray();
    }

    public function findAllScoped(int $limit = 0, int $offset = 0)
    {
        return $this->findAll($limit, $offset);
    }

    public function insert($data = null, bool $returnID = true)
    {
        if (is_array($data)) {
            $data['tenant_id'] = $this->currentTenantId();
        }

        return parent::insert($data, $returnID);
    }

    public function update($id = null, $data = null): bool
    {
        if ($id === null || ! is_array($data)) {
            return false;
        }

        $row = $this->findScoped($id);
        if (! $row) {
            return false;
        }

        unset($data['tenant_id']);

        return parent::update($id, $data);
    }

    public function updateScoped($id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function delete($id = null, bool $purge = false)
    {
        if ($id === null) {
            return false;
        }

        $row = $this->findScoped($id);
        if (! $row) {
            return false;
        }

        return parent::delete($id, $purge);
    }

    public function deleteScoped($id, bool $purge = false): bool
    {
        return $this->delete($id, $purge);
    }
}