<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RoleModel;

class Roles extends BaseController
{
    protected $roleModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
    }

    protected function currentTenantId(): ?int
    {
        $tenantId = session('tenant_id');
        return $tenantId !== null ? (int) $tenantId : null;
    }

    protected function ensureTenantAccess()
    {
        if (function_exists('is_super_admin') && is_super_admin()) {
            return redirect()->to(site_url('super-admin'))->with('error', lang('app.access_denied'));
        }

        if (! session('tenant_id')) {
            return redirect()->to(site_url('login'))->with('error', lang('app.access_denied'));
        }

        return null;
    }

    public function index()
    {
        if ($redirect = $this->ensureTenantAccess()) {
            return $redirect;
        }

        return view('roles/index', [
            'title' => lang('app.roles'),
            'rows'  => $this->roleModel->getTenantRoles($this->currentTenantId(), false),
        ]);
    }

    public function create()
    {
        if ($redirect = $this->ensureTenantAccess()) {
            return $redirect;
        }

        return view('roles/form', [
            'title'    => lang('app.create_role'),
            'formMode' => 'create',
            'row'      => null,
        ]);
    }

    public function store()
    {
        if ($redirect = $this->ensureTenantAccess()) {
            return $redirect;
        }

        $rules = [
            'role_name'      => 'permit_empty|max_length[100]',
            'role_name_th'   => 'permit_empty|max_length[100]',
            'role_name_en'   => 'permit_empty|max_length[100]',
            'description'    => 'permit_empty|max_length[255]',
            'description_th' => 'permit_empty|max_length[255]',
            'description_en' => 'permit_empty|max_length[255]',
            'status'         => 'required|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $roleName   = trim((string) $this->request->getPost('role_name'));
        $roleNameTh = trim((string) $this->request->getPost('role_name_th'));
        $roleNameEn = trim((string) $this->request->getPost('role_name_en'));

        if ($roleName === '' && $roleNameTh === '' && $roleNameEn === '') {
            return redirect()->back()->withInput()->with('error', lang('app.role_name_required'));
        }

        $saveData = [
            'tenant_id'       => $this->currentTenantId(),
            'role_name'       => $roleName !== '' ? $roleName : ($roleNameEn !== '' ? $roleNameEn : $roleNameTh),
            'role_name_th'    => $roleNameTh,
            'role_name_en'    => $roleNameEn,
            'description'     => trim((string) $this->request->getPost('description')),
            'description_th'  => trim((string) $this->request->getPost('description_th')),
            'description_en'  => trim((string) $this->request->getPost('description_en')),
            'status'          => (int) $this->request->getPost('status'),
        ];

        $saved = $this->roleModel->insert($saveData);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', lang('app.save_failed'));
        }

        return redirect()->to(site_url('roles'))->with('success', lang('app.create_success'));
    }

    public function edit($id = null)
    {
        if ($redirect = $this->ensureTenantAccess()) {
            return $redirect;
        }

        $id  = (int) $id;
        $row = $this->roleModel->findTenantRole($id, $this->currentTenantId());

        if (! $row) {
            return redirect()->to(site_url('roles'))->with('error', lang('app.data_not_found'));
        }

        return view('roles/form', [
            'title'    => lang('app.edit_role'),
            'formMode' => 'edit',
            'row'      => $row,
        ]);
    }

    public function update($id = null)
    {
        if ($redirect = $this->ensureTenantAccess()) {
            return $redirect;
        }

        $id = (int) $id;
        $row = $this->roleModel->findTenantRole($id, $this->currentTenantId());

        if (! $row) {
            return redirect()->to(site_url('roles'))->with('error', lang('app.data_not_found'));
        }

        $rules = [
            'role_name'      => 'permit_empty|max_length[100]',
            'role_name_th'   => 'permit_empty|max_length[100]',
            'role_name_en'   => 'permit_empty|max_length[100]',
            'description'    => 'permit_empty|max_length[255]',
            'description_th' => 'permit_empty|max_length[255]',
            'description_en' => 'permit_empty|max_length[255]',
            'status'         => 'required|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $roleName   = trim((string) $this->request->getPost('role_name'));
        $roleNameTh = trim((string) $this->request->getPost('role_name_th'));
        $roleNameEn = trim((string) $this->request->getPost('role_name_en'));

        if ($roleName === '' && $roleNameTh === '' && $roleNameEn === '') {
            return redirect()->back()->withInput()->with('error', lang('app.role_name_required'));
        }

        $updateData = [
            'role_name'      => $roleName !== '' ? $roleName : ($roleNameEn !== '' ? $roleNameEn : $roleNameTh),
            'role_name_th'   => $roleNameTh,
            'role_name_en'   => $roleNameEn,
            'description'    => trim((string) $this->request->getPost('description')),
            'description_th' => trim((string) $this->request->getPost('description_th')),
            'description_en' => trim((string) $this->request->getPost('description_en')),
            'status'         => (int) $this->request->getPost('status'),
        ];

        $saved = $this->roleModel->updateTenantRole($id, $updateData, $this->currentTenantId());

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', lang('app.save_failed'));
        }

        return redirect()->to(site_url('roles'))->with('success', lang('app.update_success'));
    }

    public function delete($id = null)
    {
        if ($redirect = $this->ensureTenantAccess()) {
            return $redirect;
        }

        $id  = (int) $id;
        $row = $this->roleModel->findTenantRole($id, $this->currentTenantId());

        if (! $row) {
            return redirect()->to(site_url('roles'))->with('error', lang('app.data_not_found'));
        }

        $name   = strtolower((string) ($row['role_name'] ?? ''));
        $nameEn = strtolower((string) ($row['role_name_en'] ?? ''));
        $nameTh = trim((string) ($row['role_name_th'] ?? ''));

        if (in_array($name, ['admin', 'administrator'], true)
            || in_array($nameEn, ['admin', 'administrator'], true)
            || $nameTh === 'ผู้ดูแลระบบ') {
            return redirect()->to(site_url('roles'))->with('error', lang('app.cannot_delete_admin_role'));
        }

        $deleted = $this->roleModel->deleteTenantRole($id, $this->currentTenantId());

        if (! $deleted) {
            return redirect()->to(site_url('roles'))->with('error', lang('app.delete_failed'));
        }

        return redirect()->to(site_url('roles'))->with('success', lang('app.delete_success'));
    }
}