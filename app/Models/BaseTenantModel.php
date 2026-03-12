<?php

namespace App\Models;

use CodeIgniter\Model;

class BaseTenantModel extends Model
{
    protected $useTenant = true;

    protected function applyTenantFilter($builder)
    {
        if (! $this->useTenant) {
            return $builder;
        }

        $tenantId = session('tenant_id');

        if ($tenantId) {
            $builder->where($this->table . '.tenant_id', $tenantId);
        }

        return $builder;
    }

    public function builder(?string $table = null)
    {
        $builder = parent::builder($table);

        return $this->applyTenantFilter($builder);
    }

    public function find($id = null)
    {
        if ($id === null) {
            return null;
        }

        $builder = $this->builder();
        $builder->where($this->primaryKey, $id);

        return $builder->get()->getRowArray();
    }

    public function findAll(int $limit = 0, int $offset = 0)
    {
        $builder = $this->builder();

        if ($limit) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResultArray();
    }
}