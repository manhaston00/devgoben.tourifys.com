<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';

    protected $allowedFields = [
        'tenant_id',
        'category_id',
        'kitchen_station_id',
        'sku',
        'product_name',
        'product_name_th',
        'product_name_en',
        'description',
        'description_th',
        'description_en',
        'cost',
        'price',
        'image',
        'is_available',
        'is_recommended',
        'sort_order',
        'status',
    ];

    public function findTenantProduct(int $tenantId, int $id): ?array
    {
        if ($tenantId <= 0 || $id <= 0) {
            return null;
        }

        $row = $this->builder()
            ->select('products.*')
            ->where('products.tenant_id', $tenantId)
            ->where('products.id', $id)
            ->where('products.deleted_at IS NULL', null, false)
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    public function getTenantProducts(int $tenantId): array
    {
        if ($tenantId <= 0) {
            return [];
        }

        return $this->builder()
            ->select('products.*')
            ->where('products.tenant_id', $tenantId)
            ->where('products.deleted_at IS NULL', null, false)
            ->orderBy('products.sort_order', 'ASC')
            ->orderBy('products.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getProductsByCategory(int $tenantId, int $categoryId): array
    {
        if ($tenantId <= 0 || $categoryId <= 0) {
            return [];
        }

        return $this->builder()
            ->select('products.*')
            ->where('products.tenant_id', $tenantId)
            ->where('products.category_id', $categoryId)
            ->where('products.deleted_at IS NULL', null, false)
            ->orderBy('products.sort_order', 'ASC')
            ->orderBy('products.id', 'DESC')
            ->get()
            ->getResultArray();
    }
}