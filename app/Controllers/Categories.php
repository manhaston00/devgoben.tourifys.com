<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CategoryModel;

class Categories extends BaseController
{
    protected $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
    }

    protected function denyIfNoPermission(string $permissionKey)
    {
        if (! function_exists('can') || ! can($permissionKey)) {
            return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
        }

        return null;
    }

    protected function buildPayload(): array
    {
        $nameTh = trim((string) $this->request->getPost('category_name_th'));
        $nameEn = trim((string) $this->request->getPost('category_name_en'));
        $name   = trim((string) $this->request->getPost('category_name'));

        $categoryName = $nameTh !== ''
            ? $nameTh
            : ($nameEn !== '' ? $nameEn : $name);

        return [
            'tenant_id'        => $this->currentTenantIdOrFail(),
            'category_name'    => $categoryName,
            'category_name_th' => $nameTh !== '' ? $nameTh : $categoryName,
            'category_name_en' => $nameEn !== '' ? $nameEn : $categoryName,
            'sort_order'       => (int) ($this->request->getPost('sort_order') ?? 0),
            'status'           => ((int) ($this->request->getPost('status') ?? 1)) === 1 ? 1 : 0,
        ];
    }

    protected function validatePayload(array $data): ?string
    {
        if (trim((string) ($data['category_name'] ?? '')) === '') {
            return lang('app.category_name_required');
        }

        return null;
    }

    protected function findOwnedCategoryOrRedirect(int $id)
    {
        $row = $this->categoryModel->findTenantCategory($id);

        if (! $row) {
            return redirect()->to(site_url('categories'))
                ->with('error', lang('app.data_not_found'));
        }

        return $row;
    }

    public function index()
    {
        if ($response = $this->denyIfNoPermission('categories.view')) {
            return $response;
        }

        $rows = $this->categoryModel->getTenantCategories();

        return view('categories/index', [
            'title' => lang('app.categories'),
            'rows'  => $rows,
        ]);
    }

    public function create()
    {
        if ($response = $this->denyIfNoPermission('categories.create')) {
            return $response;
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            if ($response = $this->denyIfDemoReadonly()) {
                return $response;
            }

            $data  = $this->buildPayload();
            $error = $this->validatePayload($data);

            if ($error !== null) {
                return redirect()->back()->withInput()->with('error', $error);
            }

            $insertResult = $this->categoryModel->insert($data);

            if ($insertResult === false) {
                $modelErrors = $this->categoryModel->errors();
                $errorText   = ! empty($modelErrors)
                    ? implode(' / ', $modelErrors)
                    : lang('app.created_fail');

                return redirect()->back()->withInput()->with('error', $errorText);
            }

            return redirect()->to(site_url('categories'))
                ->with('success', lang('app.created_success'));
        }

        return view('categories/form', [
            'title' => lang('app.add_category'),
            'row'   => [],
        ]);
    }

    public function edit($id = null)
    {
        if ($response = $this->denyIfNoPermission('categories.edit')) {
            return $response;
        }

        $id = (int) $id;
        $row = $this->findOwnedCategoryOrRedirect($id);

        if (! is_array($row)) {
            return $row;
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            if ($response = $this->denyIfDemoReadonly()) {
                return $response;
            }

            $data = $this->buildPayload();
            unset($data['tenant_id']);

            $error = $this->validatePayload($data);
            if ($error !== null) {
                return redirect()->back()->withInput()->with('error', $error);
            }

            $updateResult = $this->categoryModel->update($id, $data);

            if ($updateResult === false) {
                $modelErrors = $this->categoryModel->errors();
                $errorText   = ! empty($modelErrors)
                    ? implode(' / ', $modelErrors)
                    : lang('app.updated_fail');

                return redirect()->back()->withInput()->with('error', $errorText);
            }

            return redirect()->to(site_url('categories'))
                ->with('success', lang('app.updated_success'));
        }

        return view('categories/form', [
            'title' => lang('app.edit_category'),
            'row'   => $row,
        ]);
    }

    public function delete($id = null)
    {
        if ($response = $this->denyIfNoPermission('categories.delete')) {
            return $response;
        }

        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to(site_url('categories'))
                ->with('error', lang('app.invalid_method'));
        }

        $id = (int) $id;
        $row = $this->findOwnedCategoryOrRedirect($id);

        if (! is_array($row)) {
            return $row;
        }

        $deleteResult = $this->categoryModel->delete($id);

        if ($deleteResult === false) {
            $modelErrors = $this->categoryModel->errors();
            $errorText   = ! empty($modelErrors)
                ? implode(' / ', $modelErrors)
                : lang('app.deleted_fail');

            return redirect()->to(site_url('categories'))->with('error', $errorText);
        }

        return redirect()->to(site_url('categories'))
            ->with('success', lang('app.deleted_success'));
    }
}