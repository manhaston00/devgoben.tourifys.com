<?php

namespace App\Models;

class CategoryModel extends TenantScopedModel
{
    protected $table            = 'categories';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';
    protected $useTimestamps    = true;

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    protected $allowedFields = [
        'tenant_id',
        'category_name',
        'category_name_th',
        'category_name_en',
        'sort_order',
        'status',
    ];

    public function getTenantCategories(bool $onlyActive = false): array
    {
        $builder = $this->scopedBuilder()
            ->where('categories.deleted_at IS NULL', null, false);

        if ($onlyActive) {
            $builder->where('categories.status', 1);
        }

        return $builder
            ->orderBy('categories.sort_order', 'ASC')
            ->orderBy('categories.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function findTenantCategory(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->scopedBuilder()
            ->where('categories.deleted_at IS NULL', null, false)
            ->where('categories.id', $id)
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }
}