<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductQuickOptionModel;
use App\Models\ProductModel;
use App\Models\CategoryModel;

class ProductQuickOptionController extends BaseController
{
    protected ProductQuickOptionModel $productQuickOptionModel;
    protected ProductModel $productModel;
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->productQuickOptionModel = new ProductQuickOptionModel();
        $this->productModel            = new ProductModel();
        $this->categoryModel           = new CategoryModel();
    }

    protected function denyIfProductQuickOptionsDisabled()
    {
        if (function_exists('is_super_admin') && is_super_admin()) {
            return null;
        }

        if (function_exists('module_runtime_enabled') && ! module_runtime_enabled('product_quick_options')) {
            return redirect()->to(site_url('/'))->with('error', lang('app.feature_not_available_for_plan'));
        }

        return null;
    }

    protected function currentTenantId(): int
    {
        return (int) (session('tenant_id') ?? 0);
    }

    protected function findTenantProduct(int $productId): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        return $this->productModel->builder()
            ->select('products.*')
            ->where('products.tenant_id', $this->currentTenantId())
            ->where('products.id', $productId)
            ->where('products.deleted_at IS NULL', null, false)
            ->get()
            ->getRowArray();
    }

    protected function findTenantCategory(int $categoryId): ?array
    {
        if ($categoryId <= 0) {
            return null;
        }

        return $this->categoryModel->builder()
            ->select('categories.*')
            ->where('categories.tenant_id', $this->currentTenantId())
            ->where('categories.id', $categoryId)
            ->where('categories.deleted_at IS NULL', null, false)
            ->get()
            ->getRowArray();
    }

    protected function findTenantOption(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return $this->productQuickOptionModel->builder()
            ->select('product_quick_options.*')
            ->where('product_quick_options.tenant_id', $this->currentTenantId())
            ->where('product_quick_options.id', $id)
            ->where('product_quick_options.deleted_at IS NULL', null, false)
            ->get()
            ->getRowArray();
    }

    public function index()
    {
        if ($response = $this->denyIfProductQuickOptionsDisabled()) {
            return $response;
        }

        $tenantId = $this->currentTenantId();

        $options = $this->productQuickOptionModel->builder()
            ->select('
                product_quick_options.*,
                products.product_name,
                products.product_name_th,
                products.product_name_en,
                categories.category_name,
                categories.category_name_th,
                categories.category_name_en
            ')
            ->join('products', 'products.id = product_quick_options.product_id AND products.deleted_at IS NULL', 'left')
            ->join('categories', 'categories.id = product_quick_options.category_id AND categories.deleted_at IS NULL', 'left')
            ->where('product_quick_options.tenant_id', $tenantId)
            ->where('product_quick_options.deleted_at IS NULL', null, false)
            ->orderBy('product_quick_options.sort_order', 'ASC')
            ->orderBy('product_quick_options.id', 'DESC')
            ->get()
            ->getResultArray();

        $products = $this->productModel->builder()
            ->select('products.*')
            ->where('products.tenant_id', $tenantId)
            ->where('products.deleted_at IS NULL', null, false)
            ->orderBy('products.id', 'DESC')
            ->get()
            ->getResultArray();

        $categories = $this->categoryModel->builder()
            ->select('categories.*')
            ->where('categories.tenant_id', $tenantId)
            ->where('categories.deleted_at IS NULL', null, false)
            ->orderBy('categories.id', 'DESC')
            ->get()
            ->getResultArray();

        return view('product_quick_options/index', [
            'title'      => lang('app.product_quick_options'),
            'options'    => $options,
            'products'   => $products,
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        if ($response = $this->denyIfProductQuickOptionsDisabled()) {
            return $response;
        }

        return redirect()->to(site_url('product-quick-options'));
    }

    public function store()
    {
        if ($response = $this->denyIfProductQuickOptionsDisabled()) {
            return $response;
        }

        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $tenantId = $this->currentTenantId();

        $productId  = (int) ($this->request->getPost('product_id') ?? 0);
        $categoryId = (int) ($this->request->getPost('category_id') ?? 0);

        if ($productId > 0 && ! $this->findTenantProduct($productId)) {
            return redirect()->back()->withInput()
                ->with('error', lang('app.data_not_found'));
        }

        if ($categoryId > 0 && ! $this->findTenantCategory($categoryId)) {
            return redirect()->back()->withInput()
                ->with('error', lang('app.data_not_found'));
        }

        $optionNameTh = trim((string) $this->request->getPost('option_name_th'));
        $optionNameEn = trim((string) $this->request->getPost('option_name_en'));
        $optionName   = trim((string) $this->request->getPost('option_name'));

        if ($optionName === '') {
            $optionName = $optionNameTh !== '' ? $optionNameTh : $optionNameEn;
        }

        if ($optionName === '') {
            return redirect()->back()->withInput()
                ->with('error', lang('app.data_not_found'));
        }

        $data = [
            'tenant_id'      => $tenantId,
            'product_id'     => $productId > 0 ? $productId : null,
            'category_id'    => $categoryId > 0 ? $categoryId : null,
            'option_name'    => $optionName,
            'option_name_th' => $optionNameTh,
            'option_name_en' => $optionNameEn,
            'price_adjust'   => (float) ($this->request->getPost('price_adjust') ?? 0),
            'sort_order'     => (int) ($this->request->getPost('sort_order') ?? 0),
            'status'         => (int) ($this->request->getPost('status') ?? 1),
        ];

        $this->productQuickOptionModel->insert($data);

        return redirect()->to(site_url('product-quick-options'))
            ->with('success', lang('app.saved_successfully'));
    }

    public function edit(int $id)
    {
        if ($response = $this->denyIfProductQuickOptionsDisabled()) {
            return $response;
        }

        return redirect()->to(site_url('product-quick-options'));
    }

    public function update(int $id)
    {
        if ($response = $this->denyIfProductQuickOptionsDisabled()) {
            return $response;
        }

        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $row = $this->findTenantOption($id);

        if (! $row) {
            return redirect()->to(site_url('product-quick-options'))
                ->with('error', lang('app.data_not_found'));
        }

        $productId  = (int) ($this->request->getPost('product_id') ?? 0);
        $categoryId = (int) ($this->request->getPost('category_id') ?? 0);

        if ($productId > 0 && ! $this->findTenantProduct($productId)) {
            return redirect()->back()->withInput()
                ->with('error', lang('app.data_not_found'));
        }

        if ($categoryId > 0 && ! $this->findTenantCategory($categoryId)) {
            return redirect()->back()->withInput()
                ->with('error', lang('app.data_not_found'));
        }

        $optionNameTh = trim((string) $this->request->getPost('option_name_th'));
        $optionNameEn = trim((string) $this->request->getPost('option_name_en'));
        $optionName   = trim((string) $this->request->getPost('option_name'));

        if ($optionName === '') {
            $optionName = $optionNameTh !== '' ? $optionNameTh : $optionNameEn;
        }

        if ($optionName === '') {
            return redirect()->back()->withInput()
                ->with('error', lang('app.data_not_found'));
        }

        $data = [
            'product_id'     => $productId > 0 ? $productId : null,
            'category_id'    => $categoryId > 0 ? $categoryId : null,
            'option_name'    => $optionName,
            'option_name_th' => $optionNameTh,
            'option_name_en' => $optionNameEn,
            'price_adjust'   => (float) ($this->request->getPost('price_adjust') ?? 0),
            'sort_order'     => (int) ($this->request->getPost('sort_order') ?? 0),
            'status'         => (int) ($this->request->getPost('status') ?? 1),
        ];

        $this->productQuickOptionModel->update($id, $data);

        return redirect()->to(site_url('product-quick-options'))
            ->with('success', lang('app.updated_successfully'));
    }

    public function delete(int $id)
    {
        if ($response = $this->denyIfProductQuickOptionsDisabled()) {
            return $response;
        }

        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $row = $this->findTenantOption($id);

        if (! $row) {
            return redirect()->to(site_url('product-quick-options'))
                ->with('error', lang('app.data_not_found'));
        }

        $this->productQuickOptionModel->delete($id);

        return redirect()->to(site_url('product-quick-options'))
            ->with('success', lang('app.deleted_successfully'));
    }
}
