<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TenantModel;
use App\Models\RoleModel;
use Config\Database;

class SystemTenantsController extends BaseController
{
    protected TenantModel $tenantModel;
    protected $db;

    protected array $tableFieldsCache = [];

    public function __construct()
    {
        helper(['form', 'url', 'tenant']);
        $this->tenantModel = new TenantModel();
        $this->db = Database::connect();
    }

    public function index()
    {
        return view('super_admin/tenants/index', [
            'title'        => lang('app.tenants'),
            'datatableUrl' => site_url('super-admin/tenants/data'),
            'createUrl'    => site_url('super-admin/tenants/create'),
        ]);
    }

    public function data()
    {
        $result = $this->tenantModel->getDatatable($this->request);

        $rows = [];
        foreach ($result['rows'] as $row) {
            $tenantId     = (int) ($row['id'] ?? 0);
            $tenantStatus = strtolower((string) ($row['tenant_status'] ?? 'active'));
            $subStatus    = strtolower((string) ($row['subscription_status'] ?? ''));

            $statusBadge = '<span class="badge bg-secondary">-</span>';
            if ($tenantStatus === 'active') {
                $statusBadge = '<span class="badge bg-success">' . esc(lang('app.active')) . '</span>';
            } elseif ($tenantStatus === 'demo') {
                $statusBadge = '<span class="badge bg-info text-dark">' . esc(lang('app.demo')) . '</span>';
            } elseif ($tenantStatus === 'suspended') {
                $statusBadge = '<span class="badge bg-warning text-dark">' . esc(lang('app.suspended')) . '</span>';
            }

            $subBadge = '-';
            if ($subStatus !== '') {
                if ($subStatus === 'active') {
                    $subBadge = '<span class="badge bg-success">' . esc(lang('app.active')) . '</span>';
                } elseif ($subStatus === 'expired') {
                    $subBadge = '<span class="badge bg-danger">' . esc(lang('app.expired')) . '</span>';
                } elseif ($subStatus === 'suspended') {
                    $subBadge = '<span class="badge bg-warning text-dark">' . esc(lang('app.suspended')) . '</span>';
                } else {
                    $subBadge = '<span class="badge bg-secondary">' . esc(ucfirst($subStatus)) . '</span>';
                }
            }

            $actions = '<div class="d-flex gap-1 justify-content-center flex-wrap">';

            if (function_exists('can') && can('tenants.edit')) {
                $actions .= '<a href="' . site_url('super-admin/tenants/edit/' . $tenantId) . '" class="btn btn-sm btn-warning">' . esc(lang('app.edit')) . '</a>';
            }

            if (function_exists('can') && can('tenants.delete')) {
                $actions .= '
                    <form method="post"
                          action="' . site_url('super-admin/tenants/delete/' . $tenantId) . '"
                          class="d-inline"
                          onsubmit="return confirm(' . json_encode(lang('app.confirm_delete')) . ')">
                        ' . csrf_field() . '
                        <button type="submit" class="btn btn-sm btn-outline-danger">' . esc(lang('app.delete')) . '</button>
                    </form>
                ';
            }

            $actions .= '</div>';

            $rows[] = [
                'id'                  => $tenantId,
                'tenant_code'         => esc($row['tenant_code'] ?? '-'),
                'tenant_name'         => esc($row['tenant_name'] ?? '-'),
                'owner_name'          => esc($row['owner_name'] ?? '-'),
                'phone'               => esc($row['phone'] ?? '-'),
                'email'               => esc($row['email'] ?? '-'),
                'plan_name'           => esc($row['plan_name'] ?? '-'),
                'subscription_status' => $subBadge,
                'tenant_status'       => $statusBadge,
                'actions'             => $actions,
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) ($this->request->getGetPost('draw') ?? 1),
            'recordsTotal'    => (int) ($result['recordsTotal'] ?? 0),
            'recordsFiltered' => (int) ($result['recordsFiltered'] ?? 0),
            'data'            => $rows,
        ]);
    }

    public function create()
    {
        return view('super_admin/tenants/form', [
            'title'    => lang('app.add_tenant'),
            'formMode' => 'create',
            'row'      => $this->getDefaultFormRow(),
            'plans'    => $this->tenantModel->getPlanOptions(),
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();

        $rules = [
            'tenant_name'     => 'required|min_length[2]|max_length[150]',
            'tenant_code'     => 'permit_empty|max_length[50]',
            'login_prefix'    => 'required|min_length[2]|max_length[50]',
            'owner_name'      => 'permit_empty|max_length[150]',
            'phone'           => 'permit_empty|max_length[50]',
            'email'           => 'permit_empty|valid_email|max_length[150]',
            'status'          => 'required|in_list[active,demo,suspended]',
            'plan_id'         => 'required|integer',
            'branch_name'     => 'required|min_length[2]|max_length[150]',
            'branch_code'     => 'required|min_length[2]|max_length[50]',
            'admin_full_name' => 'required|min_length[2]|max_length[150]',
            'admin_username'  => 'required|min_length[3]|max_length[100]',
            'admin_password'  => 'required|min_length[4]|max_length[100]',
        ];

        if (! $this->validateData($post, $rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        try {
            $this->db->transBegin();

            $tenantData       = $this->prepareTenantData($post);
            $branchData       = $this->prepareBranchData($post);
            $adminData        = $this->prepareAdminData($post, 0, 0, 0);
            $subscriptionData = $this->prepareSubscriptionData($post);

            $this->throwIfTenantCreateConflict(
                $tenantData,
                $branchData,
                $adminData,
                null,
                null,
                null
            );

            $tenantInsert = $this->filterTableData('tenants', $tenantData);
            $this->db->table('tenants')->insert($tenantInsert);
            $tenantId = (int) $this->db->insertID();

            if ($tenantId <= 0) {
                throw new \RuntimeException(lang('app.create_tenant_failed'));
            }

            $branchData['tenant_id'] = $tenantId;
            $branchInsert = $this->filterTableData('branches', $branchData);
            $this->db->table('branches')->insert($branchInsert);
            $branchId = (int) $this->db->insertID();

            if ($branchId <= 0) {
                throw new \RuntimeException(lang('app.create_branch_failed'));
            }

            $this->seedTenantRoles($tenantId);

			$adminData = $this->prepareAdminData($post, 0, $tenantId, $branchId);
			$adminData['role_id'] = $this->resolveTenantAdminRoleId($tenantId);
			$adminInsert = $this->filterTableData('users', $adminData);
			$this->db->table('users')->insert($adminInsert);
            $adminUserId = (int) $this->db->insertID();

            if ($adminUserId <= 0) {
                throw new \RuntimeException(lang('app.create_tenant_admin_failed'));
            }

            $subscriptionData['tenant_id'] = $tenantId;
            $subscriptionInsert = $this->filterTableData('subscriptions', $subscriptionData);
            $this->db->table('subscriptions')->insert($subscriptionInsert);
            $subscriptionId = (int) $this->db->insertID();

            if ($subscriptionId > 0) {
                $this->writeSubscriptionLogIfPossible($subscriptionId, 'create', [
                    'tenant_id'     => $tenantId,
                    'plan_id'       => $subscriptionData['plan_id'] ?? null,
                    'billing_type'  => $subscriptionData['billing_type'] ?? null,
                    'start_date'    => $subscriptionData['start_date'] ?? null,
                    'end_date'      => $subscriptionData['end_date'] ?? null,
                    'status'        => $subscriptionData['status'] ?? null,
                    'admin_user_id' => $adminUserId,
                    'branch_id'     => $branchId,
                ]);
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(lang('app.database_transaction_failed'));
            }

			$this->createStarterDataForTenant((int) $tenantId, (int) $branchId);
			
            $this->db->transCommit();
			
			clear_tenant_access_cache($tenantId, (int) ($subscriptionData['plan_id'] ?? 0));

            return redirect()->to(site_url('super-admin/tenants'))
                ->with('success', lang('app.tenant_created_successfully'));
        } catch (\Throwable $e) {
            $this->db->transRollback();

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(int $id)
    {
        $row = $this->tenantModel->getFormRow($id);

        if (! $row) {
            return redirect()->to(site_url('super-admin/tenants'))
                ->with('error', lang('app.tenant_not_found'));
        }

        return view('super_admin/tenants/form', [
            'title'    => lang('app.edit_tenant'),
            'formMode' => 'edit',
            'row'      => $row,
            'plans'    => $this->tenantModel->getPlanOptions(),
        ]);
    }

    public function update(int $id)
    {
        $row = $this->tenantModel->getFormRow($id);

        if (! $row) {
            return redirect()->to(site_url('super-admin/tenants'))
                ->with('error', lang('app.tenant_not_found'));
        }

        $post = $this->request->getPost();

        $rules = [
            'tenant_name'     => 'required|min_length[2]|max_length[150]',
            'tenant_code'     => 'permit_empty|max_length[50]',
            'login_prefix'    => 'required|min_length[2]|max_length[50]',
            'owner_name'      => 'permit_empty|max_length[150]',
            'phone'           => 'permit_empty|max_length[50]',
            'email'           => 'permit_empty|valid_email|max_length[150]',
            'status'          => 'required|in_list[active,demo,suspended]',
            'plan_id'         => 'required|integer',
            'branch_name'     => 'required|min_length[2]|max_length[150]',
            'branch_code'     => 'required|min_length[2]|max_length[50]',
            'admin_full_name' => 'required|min_length[2]|max_length[150]',
            'admin_username'  => 'required|min_length[3]|max_length[100]',
            'admin_password'  => 'permit_empty|min_length[4]|max_length[100]',
        ];

        if (! $this->validateData($post, $rules)) {
            return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
        }

        try {
            $this->db->transBegin();
			
			$oldLoginPrefix = normalize_tenant_code_value((string) ($row['login_prefix'] ?? ''));

            $headBranchId = (int) ($row['head_branch_id'] ?? 0);
            $adminUserId  = (int) ($row['admin_user_id'] ?? 0);
            $currentSubId = (int) ($row['subscription_id'] ?? 0);

            $tenantData       = $this->prepareTenantData($post);
            $branchData       = $this->prepareBranchData($post);
            $adminData        = $this->prepareAdminData($post, $adminUserId, $id, $headBranchId);
            $subscriptionData = $this->prepareSubscriptionData($post);

            $this->throwIfTenantCreateConflict(
                $tenantData,
                $branchData,
                $adminData,
                $id,
                $headBranchId > 0 ? $headBranchId : null,
                $adminUserId > 0 ? $adminUserId : null
            );

            $tenantUpdate = $this->filterTableData('tenants', $tenantData);
			unset($tenantUpdate['created_at']);
			$this->db->table('tenants')->where('id', $id)->update($tenantUpdate);

			$newLoginPrefix = normalize_tenant_code_value((string) ($tenantData['login_prefix'] ?? ''));
			if ($newLoginPrefix !== '' && $oldLoginPrefix !== '' && $newLoginPrefix !== $oldLoginPrefix) {
				$this->bulkUpdateTenantUsernamesByPrefix($id, $oldLoginPrefix, $newLoginPrefix);
			}

            if ($headBranchId > 0) {
                $branchUpdate = $this->filterTableData('branches', $branchData);
                unset($branchUpdate['created_at']);
                $this->db->table('branches')->where('id', $headBranchId)->update($branchUpdate);
            }

            $this->seedTenantRoles($id);

			if ($adminUserId > 0) {
				$adminData['role_id'] = $this->resolveTenantAdminRoleId($id);

				$adminUpdate = $this->filterTableData('users', $adminData);
				unset($adminUpdate['created_at']);
				$this->db->table('users')->where('id', $adminUserId)->update($adminUpdate);
			}

            if ($currentSubId > 0) {
                $subscriptionUpdate = $this->filterTableData('subscriptions', $subscriptionData);
                unset($subscriptionUpdate['created_at']);
                $this->db->table('subscriptions')->where('id', $currentSubId)->update($subscriptionUpdate);

                $this->writeSubscriptionLogIfPossible($currentSubId, 'update', [
                    'tenant_id'    => $id,
                    'plan_id'      => $subscriptionData['plan_id'] ?? null,
                    'billing_type' => $subscriptionData['billing_type'] ?? null,
                    'start_date'   => $subscriptionData['start_date'] ?? null,
                    'end_date'     => $subscriptionData['end_date'] ?? null,
                    'status'       => $subscriptionData['status'] ?? null,
                ]);
            } else {
                $subscriptionData['tenant_id'] = $id;
                $subscriptionInsert = $this->filterTableData('subscriptions', $subscriptionData);
                $this->db->table('subscriptions')->insert($subscriptionInsert);
                $newSubscriptionId = (int) $this->db->insertID();

                if ($newSubscriptionId > 0) {
                    $this->writeSubscriptionLogIfPossible($newSubscriptionId, 'create', [
                        'tenant_id'    => $id,
                        'plan_id'      => $subscriptionData['plan_id'] ?? null,
                        'billing_type' => $subscriptionData['billing_type'] ?? null,
                        'start_date'   => $subscriptionData['start_date'] ?? null,
                        'end_date'     => $subscriptionData['end_date'] ?? null,
                        'status'       => $subscriptionData['status'] ?? null,
                    ]);
                }
            }

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException(lang('app.database_transaction_failed'));
            }

            $this->db->transCommit();
			
			clear_tenant_access_cache($id, (int) ($subscriptionData['plan_id'] ?? 0));

            return redirect()->to(site_url('super-admin/tenants'))
                ->with('success', lang('app.tenant_updated_successfully'));
        } catch (\Throwable $e) {
            $this->db->transRollback();

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function delete(int $id)
	{
		$row = $this->tenantModel->find($id);
		if (! $row) {
			return redirect()->to(site_url('super-admin/tenants'))
				->with('error', lang('app.tenant_not_found'));
		}

		try {
			if ($this->hasField('tenants', 'deleted_at')) {
				$this->db->table('tenants')->where('id', $id)->update([
					'deleted_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s'),
				]);
			} else {
				$this->db->table('tenants')->where('id', $id)->delete();
			}

			clear_tenant_access_cache($id, null);

			return redirect()->to(site_url('super-admin/tenants'))
				->with('success', lang('app.tenant_deleted_successfully'));
		} catch (\Throwable $e) {
			return redirect()->to(site_url('super-admin/tenants'))
				->with('error', $e->getMessage());
		}
	}
	
	protected function throwIfTenantCreateConflict(
        array $tenantData,
        array $branchData,
        array $adminData,
        ?int $ignoreTenantId = null,
        ?int $ignoreBranchId = null,
        ?int $ignoreUserId = null
    ): void {
        $conflicts = $this->tenantModel->findTenantCreationConflicts(
            $tenantData,
            $branchData,
            $adminData,
            $ignoreTenantId,
            $ignoreBranchId,
            $ignoreUserId
        );

        if ($conflicts === []) {
            return;
        }

        throw new \RuntimeException($this->buildTenantConflictMessage($conflicts));
    }

    protected function buildTenantConflictMessage(array $conflicts): string
	{
		$lines = [lang('app.tenant_save_conflict_header')];

		foreach ($conflicts as $conflict) {
			$label = trim((string) ($conflict['label'] ?? lang('app.data')));
			$value = trim((string) ($conflict['value'] ?? ''));

			$lines[] = lang('app.tenant_save_conflict_item', [
				'label' => $label,
				'value' => $value,
			]);
		}

		return implode("\n", $lines);
	}

    protected function getDefaultFormRow(): array
    {
        $today = date('Y-m-d');

        return [
            'id'                  => 0,
            'tenant_name'         => '',
            'tenant_code'         => '',
            'login_prefix'        => '',
            'owner_name'          => '',
            'email'               => '',
            'phone'               => '',
            'status'              => 'active',
            'head_branch_id'      => 0,
            'branch_name'         => lang('app.head_office_default_name'),
            'branch_code'         => 'HO',
            'admin_user_id'       => 0,
            'admin_full_name'     => 'Administrator',
            'admin_username'      => '',
            'admin_password'      => '',
            'admin_pin_code'      => '9999',
            'subscription_id'     => 0,
            'plan_id'             => '',
            'billing_type'        => 'monthly',
            'subscription_status' => 'active',
            'start_date'          => $today,
            'end_date'            => date('Y-m-d', strtotime('+1 month', strtotime($today))),
        ];
    }

    protected function prepareTenantData(array $post): array
    {
        $tenantName  = trim((string) ($post['tenant_name'] ?? ''));
        $tenantCode  = normalize_tenant_code_value((string) ($post['tenant_code'] ?? ''));
        $loginPrefix = normalize_tenant_code_value((string) ($post['login_prefix'] ?? ''));

        if ($tenantCode === '') {
            $tenantCode = normalize_tenant_code_value($this->generateCode($tenantName, 'TENANT'));
        }

        if ($loginPrefix === '') {
            $loginPrefix = $tenantCode;
        }

        return [
            'tenant_name'  => $tenantName,
            'tenant_code'  => $tenantCode,
            'login_prefix' => $loginPrefix,
            'owner_name'   => trim((string) ($post['owner_name'] ?? '')),
            'email'        => trim((string) ($post['email'] ?? '')),
            'phone'        => trim((string) ($post['phone'] ?? '')),
            'status'       => trim((string) ($post['status'] ?? 'active')),
            'updated_at'   => date('Y-m-d H:i:s'),
            'created_at'   => date('Y-m-d H:i:s'),
        ];
    }

    protected function prepareBranchData(array $post): array
    {
        return [
            'branch_name'    => trim((string) ($post['branch_name'] ?? '')),
            'branch_name_th' => trim((string) ($post['branch_name'] ?? '')),
            'branch_name_en' => trim((string) ($post['branch_name_en'] ?? $post['branch_name'] ?? '')),
            'branch_code'    => trim((string) ($post['branch_code'] ?? '')),
            'phone'          => trim((string) ($post['phone'] ?? '')),
            'email'          => trim((string) ($post['email'] ?? '')),
            'status'         => 1,
            'is_head_office' => 1,
            'updated_at'     => date('Y-m-d H:i:s'),
            'created_at'     => date('Y-m-d H:i:s'),
        ];
    }

    protected function prepareAdminData(array $post, int $userId = 0, int $tenantId = 0, int $branchId = 0): array
    {
        $loginPrefix = normalize_tenant_code_value((string) ($post['login_prefix'] ?? ''));
        if ($loginPrefix === '') {
            $loginPrefix = normalize_tenant_code_value((string) ($post['tenant_code'] ?? ''));
        }

        $baseUsername = trim((string) ($post['admin_username'] ?? ''));
        $username     = make_tenant_login_username($loginPrefix, $baseUsername);
        $password     = trim((string) ($post['admin_password'] ?? ''));

        $data = [
            'tenant_id'  => $tenantId > 0 ? $tenantId : null,
            'branch_id'  => $branchId > 0 ? $branchId : null,
            'full_name'  => trim((string) ($post['admin_full_name'] ?? '')),
            'username'   => $username,
            'pin_code'   => trim((string) ($post['admin_pin_code'] ?? '9999')),
            'phone'      => trim((string) ($post['phone'] ?? '')),
            'email'      => trim((string) ($post['email'] ?? '')),
            'status'     => 1,
            'updated_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($userId <= 0) {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        } elseif ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        return $data;
    }

    protected function prepareSubscriptionData(array $post): array
    {
        $planId      = (int) ($post['plan_id'] ?? 0);
        $startDate   = trim((string) ($post['start_date'] ?? date('Y-m-d')));
        $billingType = trim((string) ($post['billing_type'] ?? 'monthly'));
        $endDate     = trim((string) ($post['end_date'] ?? ''));

        if ($endDate === '') {
            $endDate = $this->calculateEndDateByPlan($planId, $startDate, $billingType);
        }

        $status = trim((string) ($post['subscription_status'] ?? 'active'));
        if (! in_array($status, ['active', 'expired', 'suspended'], true)) {
            $status = 'active';
        }

        return [
            'plan_id'      => $planId,
            'billing_type' => in_array($billingType, ['daily', 'monthly', 'lifetime'], true) ? $billingType : 'monthly',
            'status'       => $status,
            'start_date'   => $startDate,
            'end_date'     => $endDate ?: null,
            'started_at'   => $startDate ? ($startDate . ' 00:00:00') : null,
            'expires_at'   => $endDate ? ($endDate . ' 23:59:59') : null,
            'is_demo'      => (($post['status'] ?? 'active') === 'demo') ? 1 : 0,
            'updated_at'   => date('Y-m-d H:i:s'),
            'created_at'   => date('Y-m-d H:i:s'),
        ];
    }

    protected function calculateEndDateByPlan(int $planId, string $startDate, string $billingType): ?string
    {
        $base = strtotime($startDate ?: date('Y-m-d'));
        if (! $base) {
            $base = time();
        }

        if ($planId > 0 && $this->db->tableExists('subscription_plans')) {
            $plan = $this->db->table('subscription_plans')
                ->select('duration_days, is_lifetime, plan_type')
                ->where('id', $planId)
                ->get()
                ->getRowArray();

            if ($plan) {
                if ((int) ($plan['is_lifetime'] ?? 0) === 1 || ($plan['plan_type'] ?? '') === 'lifetime') {
                    return null;
                }

                $durationDays = (int) ($plan['duration_days'] ?? 0);
                if ($durationDays > 0) {
                    return date('Y-m-d', strtotime('+' . $durationDays . ' days', $base));
                }
            }
        }

        if ($billingType === 'daily') {
            return date('Y-m-d', strtotime('+1 day', $base));
        }

        if ($billingType === 'lifetime') {
            return null;
        }

        return date('Y-m-d', strtotime('+1 month', $base));
    }

    protected function writeSubscriptionLogIfPossible(int $subscriptionId, string $action, array $payload = []): void
    {
        if (! $this->db->tableExists('subscription_logs')) {
            return;
        }

        $insert = [
            'subscription_id' => $subscriptionId,
            'action'          => $action,
            'created_at'      => date('Y-m-d H:i:s'),
        ];

        if ($this->hasField('subscription_logs', 'payload')) {
            $insert['payload'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        $this->db->table('subscription_logs')->insert($this->filterTableData('subscription_logs', $insert));
    }

    protected function filterTableData(string $table, array $data): array
    {
        if (! isset($this->tableFieldsCache[$table])) {
            $this->tableFieldsCache[$table] = $this->db->tableExists($table)
                ? $this->db->getFieldNames($table)
                : [];
        }

        $fields = $this->tableFieldsCache[$table];

        return array_intersect_key($data, array_flip($fields));
    }

    protected function hasField(string $table, string $field): bool
    {
        if (! isset($this->tableFieldsCache[$table])) {
            $this->tableFieldsCache[$table] = $this->db->tableExists($table)
                ? $this->db->getFieldNames($table)
                : [];
        }

        return in_array($field, $this->tableFieldsCache[$table], true);
    }

    protected function resolveTenantAdminRoleId(int $tenantId): int
	{
		$roleModel = new RoleModel();
		$roleId = $roleModel->getTenantAdminRoleId($tenantId);

		if ($roleId > 0) {
			return $roleId;
		}

		throw new \RuntimeException(lang('app.role_admin_not_found'));
	}
	
	protected function seedTenantRoles(int $tenantId): void
	{
		if ($tenantId <= 0) {
			return;
		}

		$rolesTable           = $this->db->table('roles');
		$rolePermissionsTable = $this->db->table('role_permissions');

		$templateRoles = $rolesTable
			->where('tenant_id', null)
			->where('deleted_at', null)
			->groupStart()
				->where('LOWER(COALESCE(role_name, "")) !=', 'super_admin')
				->where('LOWER(COALESCE(role_name_en, "")) !=', 'super_admin')
			->groupEnd()
			->orderBy('id', 'ASC')
			->get()
			->getResultArray();

		foreach ($templateRoles as $templateRole) {
			$existing = $rolesTable
				->where('tenant_id', $tenantId)
				->groupStart()
					->where('LOWER(COALESCE(role_name_en, ""))', strtolower(trim((string) ($templateRole['role_name_en'] ?? ''))))
					->orWhere('LOWER(COALESCE(role_name, ""))', strtolower(trim((string) ($templateRole['role_name'] ?? ''))))
				->groupEnd()
				->where('deleted_at', null)
				->get()
				->getRowArray();

			if ($existing) {
				continue;
			}

			$rolesTable->insert([
				'tenant_id'       => $tenantId,
				'role_name'       => $templateRole['role_name'] ?? null,
				'role_name_th'    => $templateRole['role_name_th'] ?? null,
				'role_name_en'    => $templateRole['role_name_en'] ?? null,
				'description'     => $templateRole['description'] ?? null,
				'description_th'  => $templateRole['description_th'] ?? null,
				'description_en'  => $templateRole['description_en'] ?? null,
				'status'          => (int) ($templateRole['status'] ?? 1),
				'created_at'      => date('Y-m-d H:i:s'),
				'updated_at'      => date('Y-m-d H:i:s'),
			]);

			$newRoleId      = (int) $this->db->insertID();
			$templateRoleId = (int) ($templateRole['id'] ?? 0);

			if ($newRoleId <= 0 || $templateRoleId <= 0) {
				continue;
			}

			$permissions = $rolePermissionsTable
				->where('role_id', $templateRoleId)
				->get()
				->getResultArray();

			foreach ($permissions as $permission) {
				$permissionId = (int) ($permission['permission_id'] ?? 0);
				if ($permissionId <= 0) {
					continue;
				}

				$rolePermissionsTable->insert([
					'role_id'       => $newRoleId,
					'permission_id' => $permissionId,
					'created_at'    => date('Y-m-d H:i:s'),
				]);
			}
		}
	}

	protected function bulkUpdateTenantUsernamesByPrefix(int $tenantId, string $oldPrefix, string $newPrefix): void
	{
		if ($tenantId <= 0) {
			return;
		}

		$oldPrefix = normalize_tenant_code_value($oldPrefix);
		$newPrefix = normalize_tenant_code_value($newPrefix);

		if ($oldPrefix === '' || $newPrefix === '' || $oldPrefix === $newPrefix) {
			return;
		}

		$users = $this->db->table('users')
			->select('id, username')
			->where('tenant_id', $tenantId)
			->where('deleted_at', null)
			->get()
			->getResultArray();

		foreach ($users as $user) {
			$userId    = (int) ($user['id'] ?? 0);
			$username  = strtolower(trim((string) ($user['username'] ?? '')));

			if ($userId <= 0 || $username === '') {
				continue;
			}

			$baseUsername = $username;
			$oldPrefixKey = $oldPrefix . '_';

			if (strpos($username, $oldPrefixKey) === 0) {
				$baseUsername = substr($username, strlen($oldPrefixKey));
			} elseif (strpos($username, '_') !== false) {
				$parts = explode('_', $username, 2);
				$baseUsername = $parts[1] ?? $username;
			}

			$baseUsername = strtolower(trim((string) $baseUsername));
			$baseUsername = preg_replace('/[^a-z0-9._-]+/', '', $baseUsername);

			if ($baseUsername === '') {
				continue;
			}

			$newUsername = $newPrefix . '_' . $baseUsername;

			$this->db->table('users')
				->where('id', $userId)
				->update([
					'username'   => $newUsername,
					'updated_at' => date('Y-m-d H:i:s'),
				]);
		}
	}

    protected function generateCode(string $source, string $fallback = 'CODE'): string
    {
        $source = trim($source);
        if ($source === '') {
            $source = $fallback;
        }

        $source = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $source));

        if ($source === '') {
            $source = strtoupper($fallback);
        }

        return substr($source, 0, 12);
    }
	
	protected function createStarterDataForTenant(int $tenantId, int $branchId): void
	{
		$db = \Config\Database::connect();
		$now = date('Y-m-d H:i:s');

		$db->table('categories')->insert([
			'tenant_id'        => $tenantId,
			'category_name'    => 'หมวดหลัก',
			'category_name_th' => 'หมวดหลัก',
			'category_name_en' => 'Main Category',
			'sort_order'       => 1,
			'status'           => 1,
			'created_at'       => $now,
			'updated_at'       => $now,
		]);

		$categoryId = (int) $db->insertID();

		$db->table('zones')->insert([
			'tenant_id'       => $tenantId,
			'branch_id'       => $branchId,
			'zone_name_th'    => 'โซนหลัก',
			'zone_name_en'    => 'Main Zone',
			'description_th'  => 'โซนเริ่มต้น',
			'description_en'  => 'Starter zone',
			'sort_order'      => 1,
			'status'          => 1,
			'created_at'      => $now,
			'updated_at'      => $now,
		]);

		$zoneId = (int) $db->insertID();

		$starterTables = [
			['table_name' => 'A1', 'sort_order' => 1],
			['table_name' => 'A2', 'sort_order' => 2],
			['table_name' => 'A3', 'sort_order' => 3],
		];

		foreach ($starterTables as $row) {
			$db->table('restaurant_tables')->insert([
				'tenant_id'   => $tenantId,
				'branch_id'   => $branchId,
				'zone_id'     => $zoneId,
				'table_name'  => $row['table_name'],
				'seat_count'  => 4,
				'min_guest'   => 1,
				'max_guest'   => 4,
				'status'      => 'available',
				'sort_order'  => $row['sort_order'],
				'is_active'   => 1,
				'note'        => 'Starter table',
				'created_at'  => $now,
				'updated_at'  => $now,
			]);
		}

		$db->table('quick_notes')->insertBatch([
			[
				'tenant_id'    => $tenantId,
				'note_name'    => 'ไม่เอาผัก',
				'note_name_th' => 'ไม่เอาผัก',
				'note_name_en' => 'No vegetables',
				'sort_order'   => 1,
				'status'       => 1,
				'created_at'   => $now,
				'updated_at'   => $now,
			],
			[
				'tenant_id'    => $tenantId,
				'note_name'    => 'ไม่เอากระเทียม',
				'note_name_th' => 'ไม่เอากระเทียม',
				'note_name_en' => 'No garlic',
				'sort_order'   => 2,
				'status'       => 1,
				'created_at'   => $now,
				'updated_at'   => $now,
			],
		]);

		$db->table('product_quick_options')->insertBatch([
			[
				'tenant_id'      => $tenantId,
				'product_id'     => null,
				'category_id'    => null,
				'option_name'    => 'ไม่เผ็ด',
				'option_name_th' => 'ไม่เผ็ด',
				'option_name_en' => 'Not spicy',
				'price_adjust'   => 0,
				'sort_order'     => 1,
				'status'         => 1,
				'created_at'     => $now,
				'updated_at'     => $now,
			],
			[
				'tenant_id'      => $tenantId,
				'product_id'     => null,
				'category_id'    => null,
				'option_name'    => 'เพิ่มไข่',
				'option_name_th' => 'เพิ่มไข่',
				'option_name_en' => 'Add egg',
				'price_adjust'   => 10,
				'sort_order'     => 2,
				'status'         => 1,
				'created_at'     => $now,
				'updated_at'     => $now,
			],
		]);
	}
}