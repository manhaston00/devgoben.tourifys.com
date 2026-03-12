<?php

namespace App\Models;

class PromotionModel extends TenantScopedModel
{
    protected $table            = 'promotions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    protected $allowedFields = [
        'tenant_id',
        'code',
        'name',
        'name_th',
        'name_en',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'usage_limit',
        'used_count',
        'start_date',
        'end_date',
        'status',
        'note',
        'note_th',
        'note_en',
    ];

    public function getVisiblePromotions(bool $activeOnly = false): array
    {
        $builder = $this->builder();

        if (! (function_exists('is_super_admin') && is_super_admin())) {
            $tenantId = $this->currentTenantId();

            if (empty($tenantId)) {
                return [];
            }

            $builder->where($this->table . '.tenant_id', (int) $tenantId);
        }

        if ($activeOnly) {
            $builder->where($this->table . '.status', 1);
        }

        return $builder
            ->orderBy($this->table . '.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function findVisible(int $id): ?array
    {
        $builder = $this->builder();
        $builder->where($this->table . '.id', $id);

        if (! (function_exists('is_super_admin') && is_super_admin())) {
            $tenantId = $this->currentTenantId();

            if (empty($tenantId)) {
                return null;
            }

            $builder->where($this->table . '.tenant_id', (int) $tenantId);
        }

        $row = $builder->get()->getRowArray();

        return $row ?: null;
    }

    public function findTenantPromotion(int $id): ?array
    {
        return $this->scoped()
            ->where($this->table . '.id', $id)
            ->first();
    }

    public function codeExistsInTenant(string $code, ?int $ignoreId = null, ?int $tenantId = null): bool
    {
        $code = trim($code);

        if ($code === '') {
            return false;
        }

        if ($tenantId === null) {
            $tenantId = $this->currentTenantId();
        }

        if (empty($tenantId)) {
            return false;
        }

        $builder = $this->builder();
        $builder->where($this->table . '.tenant_id', (int) $tenantId);
        $builder->where($this->table . '.code', $code);

        if (! empty($ignoreId)) {
            $builder->where($this->table . '.id !=', (int) $ignoreId);
        }

        return (bool) $builder->countAllResults();
    }
}