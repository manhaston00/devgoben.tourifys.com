<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\TenantModel;

class Branches extends BaseController
{
    protected $branchModel;

    public function __construct()
    {
        $this->branchModel = new BranchModel();
    }

    protected function denyIfNoPermission(string $permissionKey)
    {
        if (! function_exists('can') || ! can($permissionKey)) {
            return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
        }

        return null;
    }

    protected function getBranchDeleteDependencies(int $branchId): array
    {
        $db = \Config\Database::connect();

        $checks = [
            'users' => [
                'table' => 'users',
                'field' => 'branch_id',
                'label' => lang('app.branch_dependency_users'),
            ],
            'zones' => [
                'table' => 'zones',
                'field' => 'branch_id',
                'label' => lang('app.branch_dependency_zones'),
            ],
            'restaurant_tables' => [
                'table' => 'restaurant_tables',
                'field' => 'branch_id',
                'label' => lang('app.branch_dependency_tables'),
            ],
            'reservations' => [
                'table' => 'reservations',
                'field' => 'branch_id',
                'label' => lang('app.branch_dependency_reservations'),
            ],
            'orders' => [
                'table' => 'orders',
                'field' => 'branch_id',
                'label' => lang('app.branch_dependency_orders'),
            ],
        ];

        $result = [];

        foreach ($checks as $check) {
            if (! $db->tableExists($check['table']) || ! $db->fieldExists($check['field'], $check['table'])) {
                continue;
            }

            $builder = $db->table($check['table'])->where($check['field'], $branchId);

            if ($db->fieldExists('deleted_at', $check['table'])) {
                $builder->where('deleted_at', null);
            }

            $count = (int) $builder->countAllResults();

            if ($count > 0) {
                $result[] = [
                    'label' => $check['label'],
                    'count' => $count,
                ];
            }
        }

        return $result;
    }

    protected function branchPayload(): array
    {
        $nameTh = trim((string) $this->request->getPost('branch_name_th'));
        $nameEn = trim((string) $this->request->getPost('branch_name_en'));
        $name   = $nameTh !== '' ? $nameTh : ($nameEn !== '' ? $nameEn : trim((string) $this->request->getPost('branch_name')));

        $addressTh = trim((string) $this->request->getPost('address_th'));
        $addressEn = trim((string) $this->request->getPost('address_en'));
        $address   = $addressTh !== '' ? $addressTh : ($addressEn !== '' ? $addressEn : trim((string) $this->request->getPost('address')));

        return [
            'tenant_id'      => function_exists('current_tenant_id') ? current_tenant_id() : null,
            'branch_code'    => trim((string) $this->request->getPost('branch_code')),
            'branch_name'    => $name,
            'branch_name_th' => $nameTh !== '' ? $nameTh : $name,
            'branch_name_en' => $nameEn !== '' ? $nameEn : $name,
            'phone'          => trim((string) $this->request->getPost('phone')),
            'email'          => trim((string) $this->request->getPost('email')),
            'address'        => $address,
            'address_th'     => $addressTh !== '' ? $addressTh : $address,
            'address_en'     => $addressEn !== '' ? $addressEn : $address,
            'tax_id'         => trim((string) $this->request->getPost('tax_id')),
            'logo'           => trim((string) $this->request->getPost('logo')),
            'default_locale' => trim((string) $this->request->getPost('default_locale')),
            'timezone'       => trim((string) $this->request->getPost('timezone')),
            'status'         => (int) ($this->request->getPost('status') ?? 1),
            'is_head_office' => (int) ($this->request->getPost('is_head_office') ?? 0),
            'sort_order'     => (int) ($this->request->getPost('sort_order') ?? 0),
            'opened_at'      => $this->request->getPost('opened_at') ?: null,
        ];
    }

    public function index()
	{
		$guard = $this->denyIfNoPermission('branches.view');
		if ($guard) {
			return $guard;
		}

		$tenantId = function_exists('current_tenant_id') ? current_tenant_id() : null;

		$rows = $this->branchModel
			->where('tenant_id', $tenantId)
			->where('deleted_at', null)
			->orderBy('sort_order', 'ASC')
			->findAll();

		return view('branches/index', [
			'title' => lang('app.branches'),
			'rows'  => $rows,
		]);
	}

    public function create()
    {
        if ($response = $this->denyIfNoPermission('branches.create')) {
            return $response;
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'branch_code'    => 'required|min_length[2]|max_length[50]|is_unique[branches.branch_code]',
                'branch_name_th' => 'required|min_length[2]|max_length[150]',
                'branch_name_en' => 'permit_empty|max_length[150]',
                'phone'          => 'permit_empty|max_length[30]',
                'email'          => 'permit_empty|valid_email|max_length[150]',
                'tax_id'         => 'permit_empty|max_length[50]',
                'default_locale' => 'required|in_list[th,en]',
                'timezone'       => 'required|max_length[100]',
                'opened_at'      => 'permit_empty|valid_date[Y-m-d]',
            ];

            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
            }

            $this->branchModel->insert($this->branchPayload());

            return redirect()->to(site_url('branches'))->with('success', lang('app.branch_created_successfully'));
        }

        return view('branches/create', [
            'title' => lang('app.add_branch'),
        ]);
    }

    public function edit($id = null)
    {
        if ($response = $this->denyIfNoPermission('branches.edit')) {
            return $response;
        }

        $branch = $this->branchModel->find((int) $id);

        if (! $branch) {
            return redirect()->to(site_url('branches'))->with('error', lang('app.branch_not_found'));
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'branch_code'    => 'required|min_length[2]|max_length[50]',
                'branch_name_th' => 'required|min_length[2]|max_length[150]',
                'branch_name_en' => 'permit_empty|max_length[150]',
                'phone'          => 'permit_empty|max_length[30]',
                'email'          => 'permit_empty|valid_email|max_length[150]',
                'tax_id'         => 'permit_empty|max_length[50]',
                'default_locale' => 'required|in_list[th,en]',
                'timezone'       => 'required|max_length[100]',
                'opened_at'      => 'permit_empty|valid_date[Y-m-d]',
            ];

            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
            }

            $branchCode = trim((string) $this->request->getPost('branch_code'));

            $dup = $this->branchModel
                ->where('branch_code', $branchCode)
                ->where('id !=', (int) $id)
                ->first();

            if ($dup) {
                return redirect()->back()->withInput()->with('error', lang('app.branch_code_already_exists'));
            }

            $payload = $this->branchPayload();
            unset($payload['tenant_id']);

            $this->branchModel->update((int) $id, $payload);

            return redirect()->to(site_url('branches'))->with('success', lang('app.branch_updated_successfully'));
        }

        return view('branches/edit', [
            'title'  => lang('app.edit_branch'),
            'branch' => $branch,
        ]);
    }

    public function delete($id = null)
    {
        if ($response = $this->denyIfNoPermission('branches.delete')) {
            return $response;
        }

        $branch = $this->branchModel->find((int) $id);

        if (! $branch) {
            return redirect()->to(site_url('branches'))->with('error', lang('app.branch_not_found'));
        }

        if ((int) ($branch['is_head_office'] ?? 0) === 1) {
            return redirect()->to(site_url('branches'))->with('error', lang('app.cannot_delete_head_office'));
        }

        $dependencies = $this->getBranchDeleteDependencies((int) $id);

        if (! empty($dependencies)) {
            $parts = array_map(static function ($row) {
                return sprintf(lang('app.branch_dependency_count_format'), $row['label'], number_format((int) $row['count']));
            }, $dependencies);

            return redirect()->to(site_url('branches'))
                ->with('error', lang('app.cannot_delete_branch_has_dependencies') . ': ' . implode(', ', $parts));
        }

        $this->branchModel->delete((int) $id);

        return redirect()->to(site_url('branches'))->with('success', lang('app.branch_deleted_successfully'));
    }

    public function switch($id = null)
    {
        if (! session('isLoggedIn')) {
            return redirect()->to(site_url('login'))->with('error', lang('app.please_login_first'));
        }

        $branchId = (int) $id;

        if ($branchId <= 0) {
            return redirect()->back()->with('error', lang('app.invalid_branch'));
        }

        if (! function_exists('can_access_branch') || ! can_access_branch($branchId)) {
            return redirect()->back()->with('error', lang('app.cannot_access_branch'));
        }

        $branch = $this->branchModel
            ->where('id', $branchId)
            ->where('status', 1)
            ->first();

        if (! $branch) {
            return redirect()->back()->with('error', lang('app.branch_not_found_or_inactive'));
        }

        $siteLocale   = (string) ($branch['default_locale'] ?? session('site_locale') ?? 'th');
        $branchNameTh = trim((string) ($branch['branch_name_th'] ?? $branch['branch_name'] ?? ''));
        $branchNameEn = trim((string) ($branch['branch_name_en'] ?? ''));
        $branchName   = $siteLocale === 'en'
            ? ($branchNameEn !== '' ? $branchNameEn : ($branchNameTh !== '' ? $branchNameTh : '-'))
            : ($branchNameTh !== '' ? $branchNameTh : ($branchNameEn !== '' ? $branchNameEn : '-'));

        $tenantId = ! empty($branch['tenant_id']) ? (int) $branch['tenant_id'] : null;
        $tenantName = '';

        if ($tenantId) {
            $tenant = (new TenantModel())->find($tenantId);
            $tenantName = trim((string) ($tenant['tenant_name'] ?? ''));
        }

        session()->set([
            'tenant_id'      => $tenantId,
            'tenant_name'    => $tenantName,
            'branch_id'      => (int) $branch['id'],
            'branch_code'    => (string) ($branch['branch_code'] ?? ''),
            'branch_name'    => $branchName,
            'branch_name_th' => $branchNameTh,
            'branch_name_en' => $branchNameEn,
            'site_locale'    => $siteLocale,
        ]);

        return redirect()->back()->with('success', lang('app.branch_switched_successfully'));
    }
}