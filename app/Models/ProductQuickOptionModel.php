<?php

namespace App\Models;

class ProductQuickOptionModel extends BaseTenantModel
{
    protected $table            = 'product_quick_options';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'tenant_id',
        'product_id',
        'category_id',
        'option_name',
        'option_name_th',
        'option_name_en',
        'price_adjust',
        'sort_order',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getOptionsForPos(int $productId, int $categoryId = 0): array
	{
		if ($productId <= 0) {
			return [];
		}

		$tenantId = (int) (session('tenant_id') ?? 0);
		if ($tenantId <= 0) {
			return [];
		}

		$builder = $this->builder()
			->select('
				product_quick_options.id,
				product_quick_options.product_id,
				product_quick_options.category_id,
				product_quick_options.option_name,
				product_quick_options.option_name_th,
				product_quick_options.option_name_en,
				product_quick_options.price_adjust,
				product_quick_options.sort_order,
				product_quick_options.status
			')
			->where('product_quick_options.tenant_id', $tenantId)
			->where('product_quick_options.deleted_at IS NULL', null, false)
			->where('product_quick_options.status', 1)
			->groupStart()
				->where('product_quick_options.product_id', $productId);

		if ($categoryId > 0) {
			$builder->orGroupStart()
				->where('product_quick_options.product_id IS NULL', null, false)
				->where('product_quick_options.category_id', $categoryId)
			->groupEnd();
		}

		$builder->orGroupStart()
			->where('product_quick_options.product_id IS NULL', null, false)
			->where('product_quick_options.category_id IS NULL', null, false)
		->groupEnd();

		$builder->groupEnd();

		return $builder
			->orderBy('product_quick_options.sort_order', 'ASC')
			->orderBy('product_quick_options.id', 'ASC')
			->get()
			->getResultArray();
	}

    public function getTenantRows(): array
    {
        return $this->scoped()
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function findTenantRow(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->findScoped($id);

        return is_array($row) ? $row : null;
    }

    public function getByProduct(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }

        return $this->scoped()
            ->where($this->table . '.product_id', $productId)
            ->orderBy($this->table . '.sort_order', 'ASC')
            ->orderBy($this->table . '.id', 'ASC')
            ->findAll();
    }

    public function getByCategory(int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        return $this->scoped()
            ->where($this->table . '.category_id', $categoryId)
            ->orderBy($this->table . '.sort_order', 'ASC')
            ->orderBy($this->table . '.id', 'ASC')
            ->findAll();
    }
}