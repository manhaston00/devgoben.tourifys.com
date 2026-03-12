<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\CategoryModel;
use App\Models\KitchenStationModel;

class Products extends BaseController
{
    protected $productModel;
    protected $categoryModel;
    protected $kitchenStationModel;

    public function __construct()
    {
        $this->productModel        = new ProductModel();
        $this->categoryModel       = new CategoryModel();
        $this->kitchenStationModel = new KitchenStationModel();
    }

    protected function denyIfNoPermission(string $permissionKey)
    {
        if (! function_exists('can') || ! can($permissionKey)) {
            return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
        }

        return null;
    }

    protected function getTenantCategories(): array
    {
        return $this->categoryModel
            ->where('tenant_id', $this->currentTenantIdOrFail())
            ->where('deleted_at', null)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    protected function getTenantKitchenStations(): array
    {
        return $this->kitchenStationModel
            ->where('tenant_id', $this->currentTenantIdOrFail())
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    protected function findTenantProduct(int $id): ?array
    {
        return $this->productModel->findTenantProduct($this->currentTenantIdOrFail(), $id);
    }

    protected function buildPayload(): array
    {
        $nameTh = trim((string) $this->request->getPost('product_name_th'));
        $nameEn = trim((string) $this->request->getPost('product_name_en'));
        $name   = trim((string) $this->request->getPost('product_name'));

        $productName = $nameTh !== ''
            ? $nameTh
            : ($nameEn !== '' ? $nameEn : $name);

        $categoryId       = (int) ($this->request->getPost('category_id') ?? 0);
        $kitchenStationId = (int) ($this->request->getPost('kitchen_station_id') ?? 0);

        return [
            'tenant_id'          => $this->currentTenantIdOrFail(),
            'category_id'        => $categoryId,
            'kitchen_station_id' => $kitchenStationId > 0 ? $kitchenStationId : null,
            'sku'                => trim((string) $this->request->getPost('sku')),
            'product_name'       => $productName,
            'product_name_th'    => $nameTh !== '' ? $nameTh : $productName,
            'product_name_en'    => $nameEn !== '' ? $nameEn : $productName,
            'description'        => trim((string) $this->request->getPost('description')),
            'description_th'     => trim((string) $this->request->getPost('description_th')),
            'description_en'     => trim((string) $this->request->getPost('description_en')),
            'cost'               => (float) ($this->request->getPost('cost') ?? 0),
            'price'              => (float) ($this->request->getPost('price') ?? 0),
            'is_available'       => ((int) ($this->request->getPost('is_available') ?? 1)) === 1 ? 1 : 0,
            'is_recommended'     => ((int) ($this->request->getPost('is_recommended') ?? 0)) === 1 ? 1 : 0,
            'sort_order'         => (int) ($this->request->getPost('sort_order') ?? 0),
            'status'             => ((int) ($this->request->getPost('status') ?? 1)) === 1 ? 1 : 0,
        ];
    }

    protected function validatePayload(array $data): ?string
    {
        if (trim((string) ($data['product_name'] ?? '')) === '') {
            return lang('app.product_name_required');
        }

        if ((int) ($data['category_id'] ?? 0) <= 0) {
            return lang('app.category_required');
        }

        $category = $this->categoryModel
            ->where('tenant_id', $this->currentTenantIdOrFail())
            ->where('deleted_at', null)
            ->where('id', (int) $data['category_id'])
            ->first();

        if (! $category) {
            return lang('app.data_not_found');
        }

        if (! empty($data['kitchen_station_id'])) {
            $station = $this->kitchenStationModel
                ->where('tenant_id', $this->currentTenantIdOrFail())
                ->where('deleted_at', null)
                ->where('id', (int) $data['kitchen_station_id'])
                ->first();

            if (! $station) {
                return lang('app.data_not_found');
            }
        }

        return null;
    }

    public function index()
    {
        if ($response = $this->denyIfNoPermission('products.view')) {
            return $response;
        }

        $tenantId = $this->currentTenantIdOrFail();

        $products = $this->productModel
            ->select('
                products.*,
                categories.category_name,
                categories.category_name_th,
                categories.category_name_en,
                kitchen_stations.station_name,
                kitchen_stations.station_name_th,
                kitchen_stations.station_name_en
            ')
            ->join(
                'categories',
                'categories.id = products.category_id
                 AND categories.tenant_id = products.tenant_id
                 AND categories.deleted_at IS NULL',
                'left'
            )
            ->join(
                'kitchen_stations',
                'kitchen_stations.id = products.kitchen_station_id
                 AND kitchen_stations.tenant_id = products.tenant_id
                 AND kitchen_stations.deleted_at IS NULL',
                'left'
            )
            ->where('products.tenant_id', $tenantId)
            ->where('products.deleted_at', null)
            ->orderBy('products.sort_order', 'ASC')
            ->orderBy('products.id', 'DESC')
            ->findAll();

        return view('products/index', [
            'title'    => lang('app.products'),
            'products' => $products,
        ]);
    }

    public function create()
    {
        if ($response = $this->denyIfNoPermission('products.create')) {
            return $response;
        }

        return view('products/form', [
            'title'           => lang('app.add_product'),
            'row'             => null,
            'categories'      => $this->getTenantCategories(),
            'kitchenStations' => $this->getTenantKitchenStations(),
        ]);
    }

    public function store()
    {
        if ($response = $this->denyIfNoPermission('products.create')) {
            return $response;
        }

        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $data  = $this->buildPayload();
        $error = $this->validatePayload($data);

        if ($error !== null) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $this->productModel->insert($data);

        return redirect()->to(site_url('products'))
            ->with('success', lang('app.created_success'));
    }

    public function edit($id = null)
    {
        if ($response = $this->denyIfNoPermission('products.edit')) {
            return $response;
        }

        $row = $this->findTenantProduct((int) $id);
        if (! $row) {
            return redirect()->to(site_url('products'))
                ->with('error', lang('app.data_not_found'));
        }

        return view('products/form', [
            'title'           => lang('app.edit_product'),
            'row'             => $row,
            'categories'      => $this->getTenantCategories(),
            'kitchenStations' => $this->getTenantKitchenStations(),
        ]);
    }

    public function update($id = null)
    {
        if ($response = $this->denyIfNoPermission('products.edit')) {
            return $response;
        }

        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $row = $this->findTenantProduct((int) $id);
        if (! $row) {
            return redirect()->to(site_url('products'))
                ->with('error', lang('app.data_not_found'));
        }

        $data = $this->buildPayload();
        unset($data['tenant_id']);

        $error = $this->validatePayload($data);
        if ($error !== null) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $this->productModel->update((int) $row['id'], $data);

        return redirect()->to(site_url('products'))
            ->with('success', lang('app.updated_success'));
    }

    public function delete($id = null)
    {
        if ($response = $this->denyIfNoPermission('products.delete')) {
            return $response;
        }

        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to(site_url('products'))
                ->with('error', lang('app.invalid_method'));
        }

        $row = $this->findTenantProduct((int) $id);
        if (! $row) {
            return redirect()->to(site_url('products'))
                ->with('error', lang('app.data_not_found'));
        }

        $this->productModel->delete((int) $row['id']);

        return redirect()->to(site_url('products'))
            ->with('success', lang('app.deleted_success'));
    }
}