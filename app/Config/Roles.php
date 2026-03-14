<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\RoleModel;

class Roles extends BaseController
{
    protected $roleModel;
    protected $auditLogModel;

    public function __construct()
    {
        $this->roleModel     = new RoleModel();
        $this->auditLogModel = new AuditLogModel();
    }

    protected function writeAuditLog(array $payload): void
    {
        try {
            $this->auditLogModel->add($payload);
        } catch (\Throwable $e) {
            log_message('error', 'Roles writeAuditLog error: ' . $e->getMessage());
        }
    }

    protected function denyIfNoPermission(string $permissionKey)
    {
        if (! function_exists('can') || ! can($permissionKey)) {
            return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
        }

        return null;
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
        if ($redirect = $this->denyIfNoPermission('roles.view')) {
            return $redirect;
        }

        if ($redirect = $this->ensureTenantAccess()) {
            return $redirect;
        }

        $rows = $this->roleModel->getTenantRoles($this->currentTenantId(), false);

        $this->writeAuditLog([
            'branch_id'    => (int) (session('branch_id') ?? 0),
            'target_type'  => 'role',
            'target_id'    => 0,
            'action_key'   => 'roles.index',
            'action_label' => lang('app.roles'),
            'meta_json'    => [
                'screen'    => 'roles/index',
                'tenant_id' => $this->currentTenantId(),
                'row_count' => is_array($rows) ? count($rows) : 0,
            ],
        ]);

        return view('roles/index', [
            'title' => lang('app.roles'),
            'rows'  => $rows,
        ]);
    }

    public function create()
    {
        if ($redirect = $this->denyIfNoPermission('roles.create')) {
            return $redirect;
        }

        if ($redirect = $this->ensureTenantAccess()) {
            return $redirect;
        }

        $this->writeAuditLog([
            'branch_id'    => (int) (session('branch_id') ?? 0),
            'target_type'  => 'role',
            'target_id'    => 0,
            'action_key'   => 'roles.create_form',
            'action_label' => lang('app.create_role'),
            'meta_json'    => [
                'screen'    => 'roles/form',
                'form_mode' => 'create',
                'tenant_id' => $this->currentTenantId(),
            ],
        ]);

        return view('roles/form', [
            'title'    => lang('app.create_role'),
            'formMode' => 'create',
            'row'      => null,
        ]);
    }

    public function store()
    {
        if ($redirect = $this->denyIfNoPermission('roles.create')) {
            return $redirect;
        }

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

        $roleId = is_numeric($saved) ? (int) $saved : (int) $this->roleModel->getInsertID();

        $this->writeAuditLog([
            'branch_id'    => (int) (session('branch_id') ?? 0),
            'target_type'  => 'role',
            'target_id'    => $roleId,
            'action_key'   => 'roles.store',
            'action_label' => lang('app.create_role'),
            'meta_json'    => [
                'tenant_id'    => $this->currentTenantId(),
                'role_name'    => $saveData['role_name'],
                'role_name_th' => $roleNameTh,
                'role_name_en' => $roleNameEn,
                'status'       => (int) $this->request->getPost('status'),
            ],
        ]);

        return redirect()->to(site_url('roles'))->with('success', lang('app.create_success'));
    }

    public function edit($id = null)
    {
        if ($redirect = $this->denyIfNoPermission('roles.edit')) {
            return $redirect;
        }

        if ($redirect = $this->ensureTenantAccess()) {
            return $redirect;
        }

        $id  = (int) $id;
        $row = $this->roleModel->findTenantRole($id, $this->currentTenantId());

        if (! $row) {
            return redirect()->to(site_url('roles'))->with('error', lang('app.data_not_found'));
        }

        $this->writeAuditLog([
            'branch_id'    => (int) (session('branch_id') ?? 0),
            'target_type'  => 'role',
            'target_id'    => $id,
            'action_key'   => 'roles.edit_form',
            'action_label' => lang('app.edit_role'),
            'meta_json'    => [
                'screen'    => 'roles/form',
                'form_mode' => 'edit',
                'tenant_id' => $this->currentTenantId(),
            ],
        ]);

        return view('roles/form', [
            'title'    => lang('app.edit_role'),
            'formMode' => 'edit',
            'row'      => $row,
        ]);
    }

    public function update($id = null)
    {
        if ($redirect = $this->denyIfNoPermission('roles.edit')) {
            return $redirect;
        }

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

        $this->writeAuditLog([
            'branch_id'    => (int) (session('branch_id') ?? 0),
            'target_type'  => 'role',
            'target_id'    => $id,
            'action_key'   => 'roles.update',
            'action_label' => lang('app.edit_role'),
            'meta_json'    => [
                'tenant_id' => $this->currentTenantId(),
                'role_name' => $updateData['role_name'],
                'status'    => (int) $this->request->getPost('status'),
            ],
        ]);

        return redirect()->to(site_url('roles'))->with('success', lang('app.update_success'));
    }

    public function delete($id = null)
    {
        if ($redirect = $this->denyIfNoPermission('roles.delete')) {
            return $redirect;
        }

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

        $this->writeAuditLog([
            'branch_id'    => (int) (session('branch_id') ?? 0),
            'target_type'  => 'role',
            'target_id'    => $id,
            'action_key'   => 'roles.delete',
            'action_label' => lang('app.delete'),
            'meta_json'    => [
                'tenant_id' => $this->currentTenantId(),
                'role_name' => (string) ($row['role_name'] ?? ''),
            ],
        ]);

        return redirect()->to(site_url('roles'))->with('success', lang('app.delete_success'));
    }
}
