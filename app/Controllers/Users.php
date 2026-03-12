<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\BranchModel;

class Users extends BaseController
{
    protected $userModel;
    protected $roleModel;
    protected $branchModel;

    public function __construct()
    {
        $this->userModel   = new UserModel();
        $this->roleModel   = new RoleModel();
        $this->branchModel = new BranchModel();
    }

    public function index()
    {
        $rows = $this->userModel->getUsersWithRole($this->currentTenantIdOrFail());

        return view('users/index', [
            'title' => lang('app.users'),
            'rows'  => $rows,
        ]);
    }

    public function create()
    {
        return view('users/form', [
            'title'    => lang('app.create_user'),
            'formMode' => 'create',
            'row'      => null,
            'roles'    => $this->roleModel->getTenantRoles(true),
            'branches' => $this->branchModel->getTenantBranches(true),
        ]);
    }

    public function store()
    {
        if ($deny = $this->denyIfDemoReadonly()) {
            return $deny;
        }

        $tenantId = $this->currentTenantIdOrFail();

        $rules = [
            'full_name' => 'required|min_length[2]|max_length[150]',
            'username'  => 'required|min_length[3]|max_length[100]',
            'role_id'   => 'required|integer',
            'branch_id' => 'permit_empty|integer',
            'password'  => 'required|min_length[4]',
            'pin_code'  => 'permit_empty|max_length[20]',
            'email'     => 'permit_empty|valid_email|max_length[150]',
            'phone'     => 'permit_empty|max_length[30]',
            'status'    => 'required|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $roleId   = (int) $this->request->getPost('role_id');
        $branchId = $this->request->getPost('branch_id') !== '' ? (int) $this->request->getPost('branch_id') : null;

        $role = $this->roleModel->findTenantRole($roleId, $tenantId);
        if (! $role || (int) ($role['status'] ?? 0) !== 1) {
            return redirect()->back()->withInput()->with('error', lang('app.data_not_found'));
        }

        if ($branchId !== null) {
            $branch = $this->branchModel->findTenantBranch($branchId);
            if (! $branch) {
                return redirect()->back()->withInput()->with('error', lang('app.invalid_branch'));
            }
        }

        $username = strtolower(trim((string) $this->request->getPost('username')));
        $email    = trim((string) $this->request->getPost('email'));

        if ($this->userModel->usernameExists($username)) {
            return redirect()->back()->withInput()->with('error', lang('app.admin_username_exists'));
        }

        if ($email !== '' && $this->userModel->emailExists($email)) {
            return redirect()->back()->withInput()->with('error', lang('app.save_failed'));
        }

        $saved = $this->userModel->insertTenantUser([
            'tenant_id'     => $tenantId,
            'role_id'       => $roleId,
            'branch_id'     => $branchId,
            'full_name'     => trim((string) $this->request->getPost('full_name')),
            'username'      => $username,
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'pin_code'      => trim((string) $this->request->getPost('pin_code')),
            'phone'         => trim((string) $this->request->getPost('phone')),
            'email'         => $email !== '' ? $email : null,
            'status'        => (int) $this->request->getPost('status'),
        ]);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', lang('app.save_failed'));
        }

        return redirect()->to(site_url('users'))->with('success', lang('app.create_success'));
    }

    public function edit($id = null)
    {
        $id       = (int) $id;
        $tenantId = $this->currentTenantIdOrFail();

        $row = $this->userModel->findTenantUserWithRole($id, $tenantId);

        if (! $row) {
            return redirect()->to(site_url('users'))->with('error', lang('app.data_not_found'));
        }

        return view('users/form', [
            'title'    => lang('app.edit_user'),
            'formMode' => 'edit',
            'row'      => $row,
            'roles'    => $this->roleModel->getTenantRoles(true),
            'branches' => $this->branchModel->getTenantBranches(true),
        ]);
    }

    public function update($id = null)
    {
        if ($deny = $this->denyIfDemoReadonly()) {
            return $deny;
        }

        $id       = (int) $id;
        $tenantId = $this->currentTenantIdOrFail();

        $row = $this->userModel->findTenantUserWithRole($id, $tenantId);

        if (! $row) {
            return redirect()->to(site_url('users'))->with('error', lang('app.data_not_found'));
        }

        $rules = [
            'full_name' => 'required|min_length[2]|max_length[150]',
            'username'  => 'required|min_length[3]|max_length[100]',
            'role_id'   => 'required|integer',
            'branch_id' => 'permit_empty|integer',
            'pin_code'  => 'permit_empty|max_length[20]',
            'email'     => 'permit_empty|valid_email|max_length[150]',
            'phone'     => 'permit_empty|max_length[30]',
            'status'    => 'required|in_list[0,1]',
        ];

        $password = (string) $this->request->getPost('password');
        if ($password !== '') {
            $rules['password'] = 'min_length[4]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $roleId   = (int) $this->request->getPost('role_id');
        $branchId = $this->request->getPost('branch_id') !== '' ? (int) $this->request->getPost('branch_id') : null;

        $role = $this->roleModel->findTenantRole($roleId, $tenantId);
        if (! $role || (int) ($role['status'] ?? 0) !== 1) {
            return redirect()->back()->withInput()->with('error', lang('app.data_not_found'));
        }

        if ($branchId !== null) {
            $branch = $this->branchModel->findTenantBranch($branchId);
            if (! $branch) {
                return redirect()->back()->withInput()->with('error', lang('app.invalid_branch'));
            }
        }

        $username = strtolower(trim((string) $this->request->getPost('username')));
        $email    = trim((string) $this->request->getPost('email'));

        if ($this->userModel->usernameExists($username, $id)) {
            return redirect()->back()->withInput()->with('error', lang('app.admin_username_exists'));
        }

        if ($email !== '' && $this->userModel->emailExists($email, $id)) {
            return redirect()->back()->withInput()->with('error', lang('app.save_failed'));
        }

        $data = [
            'role_id'   => $roleId,
            'branch_id' => $branchId,
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'username'  => $username,
            'pin_code'  => trim((string) $this->request->getPost('pin_code')),
            'phone'     => trim((string) $this->request->getPost('phone')),
            'email'     => $email !== '' ? $email : null,
            'status'    => (int) $this->request->getPost('status'),
        ];

        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $saved = $this->userModel->updateTenantUser($id, $data, $tenantId);

        if (! $saved) {
            return redirect()->back()->withInput()->with('error', lang('app.save_failed'));
        }

        return redirect()->to(site_url('users'))->with('success', lang('app.update_success'));
    }

    public function delete($id = null)
    {
        if ($deny = $this->denyIfDemoReadonly()) {
            return $deny;
        }

        $id       = (int) $id;
        $tenantId = $this->currentTenantIdOrFail();

        $row = $this->userModel->findTenantUserWithRole($id, $tenantId);

        if (! $row) {
            return redirect()->to(site_url('users'))->with('error', lang('app.data_not_found'));
        }

        if ((int) $id === (int) session('user_id')) {
            return redirect()->to(site_url('users'))->with('error', lang('app.no_permission'));
        }

        $deleted = $this->userModel->deleteTenantUser($id, $tenantId);

        if (! $deleted) {
            return redirect()->to(site_url('users'))->with('error', lang('app.delete_failed'));
        }

        return redirect()->to(site_url('users'))->with('success', lang('app.delete_success'));
    }
}