<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\IncomingRequest;

class TenantModel extends Model
{
    protected $table            = 'tenants';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'tenant_code',
        'login_prefix',
        'tenant_name',
        'owner_name',
        'email',
        'phone',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $datatableOrderable = [
        0 => 't.id',
        1 => 't.tenant_code',
        2 => 't.tenant_name',
        3 => 't.owner_name',
        4 => 't.phone',
        5 => 't.email',
        6 => 'sp.plan_name',
        7 => 's.status',
        8 => 't.status',
    ];

    public function __construct(?\CodeIgniter\Database\ConnectionInterface &$db = null, ?\CodeIgniter\Validation\ValidationInterface $validation = null)
    {
        parent::__construct($db, $validation);
    }

    public function getDatatable(IncomingRequest $request): array
    {
        $draw   = (int) ($request->getGetPost('draw') ?? 1);
        $start  = max(0, (int) ($request->getGetPost('start') ?? 0));
        $length = (int) ($request->getGetPost('length') ?? 10);
        $length = $length > 0 ? $length : 10;

        $searchValue = trim((string) (($request->getGetPost('search')['value'] ?? '') ?: ''));

        $orderColumnIndex = (int) ($request->getGetPost('order')[0]['column'] ?? 0);
        $orderDir         = strtolower((string) ($request->getGetPost('order')[0]['dir'] ?? 'desc'));
        $orderDir         = $orderDir === 'asc' ? 'asc' : 'desc';
        $orderBy          = $this->datatableOrderable[$orderColumnIndex] ?? 't.id';

        $baseBuilder = $this->datatableBaseBuilder();

        $recordsTotal = $this->countBuilderRows(clone $baseBuilder);

        $filteredBuilder = clone $baseBuilder;
        $this->applyDatatableSearch($filteredBuilder, $searchValue);
        $recordsFiltered = $this->countBuilderRows(clone $filteredBuilder);

        $rows = $filteredBuilder
            ->orderBy($orderBy, $orderDir)
            ->limit($length, $start)
            ->get()
            ->getResultArray();

        return [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'rows'            => $rows,
        ];
    }

    public function getPlanOptions(): array
    {
        $builder = $this->db->table('subscription_plans')
            ->select('id, plan_name, plan_name_th, plan_name_en, code, plan_code, plan_type, price, status, is_active, sort_order')
            ->where('deleted_at', null);

        if ($this->db->fieldExists('is_active', 'subscription_plans')) {
            $builder->where('is_active', 1);
        } elseif ($this->db->fieldExists('status', 'subscription_plans')) {
            $builder->where('status', 1);
        }

        return $builder
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getFormRow(int $tenantId): ?array
    {
        if ($tenantId <= 0) {
            return null;
        }

        $tenant = $this->db->table('tenants t')
            ->select('
                t.id,
                t.tenant_code,
                t.login_prefix,
                t.tenant_name,
                t.owner_name,
                t.email,
                t.phone,
                t.status
            ')
            ->where('t.id', $tenantId)
            ->where('t.deleted_at', null)
            ->get()
            ->getRowArray();

        if (! $tenant) {
            return null;
        }

        $headBranch = $this->db->table('branches b')
            ->select('
                b.id,
                b.branch_name,
                b.branch_name_th,
                b.branch_name_en,
                b.branch_code
            ')
            ->where('b.tenant_id', $tenantId)
            ->where('b.deleted_at', null)
            ->groupStart()
                ->where('b.is_head_office', 1)
                ->orWhere('b.is_head_office', '1')
            ->groupEnd()
            ->orderBy('b.id', 'ASC')
            ->get()
            ->getRowArray();

        if (! $headBranch) {
            $headBranch = $this->db->table('branches b')
                ->select('
                    b.id,
                    b.branch_name,
                    b.branch_name_th,
                    b.branch_name_en,
                    b.branch_code
                ')
                ->where('b.tenant_id', $tenantId)
                ->where('b.deleted_at', null)
                ->orderBy('b.id', 'ASC')
                ->get()
                ->getRowArray();
        }

        $adminBuilder = $this->db->table('users u')
            ->select('
                u.id,
                u.full_name,
                u.username,
                u.pin_code
            ')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->where('u.tenant_id', $tenantId)
            ->where('u.deleted_at', null);

        $hasRoleName = $this->db->fieldExists('role_name', 'roles');
        $hasRoleKey  = $this->db->fieldExists('role_key', 'roles');

        if ($hasRoleName || $hasRoleKey) {
            $adminBuilder->groupStart();

            if ($hasRoleName) {
                $adminBuilder
                    ->where('LOWER(r.role_name)', 'admin')
                    ->orWhere('LOWER(r.role_name)', 'administrator')
                    ->orWhere('LOWER(r.role_name)', 'tenant admin')
                    ->orWhere('LOWER(r.role_name)', 'tenant_admin');
            }

            if ($hasRoleKey) {
                $adminBuilder
                    ->orWhere('LOWER(r.role_key)', 'admin')
                    ->orWhere('LOWER(r.role_key)', 'tenant_admin');
            }

            $adminBuilder->groupEnd();
        }

        $adminUser = $adminBuilder
            ->orderBy('u.id', 'ASC')
            ->get()
            ->getRowArray();

        if (! $adminUser) {
            $adminUser = $this->db->table('users u')
                ->select('
                    u.id,
                    u.full_name,
                    u.username,
                    u.pin_code
                ')
                ->where('u.tenant_id', $tenantId)
                ->where('u.deleted_at', null)
                ->orderBy('u.id', 'ASC')
                ->get()
                ->getRowArray();
        }

        $subscription = $this->db->table('subscriptions s')
            ->select('
                s.id,
                s.plan_id,
                s.billing_type,
                s.status,
                s.start_date,
                s.end_date
            ')
            ->where('s.tenant_id', $tenantId)
            ->where('s.deleted_at', null)
            ->orderBy('s.id', 'DESC')
            ->get()
            ->getRowArray();

        return [
            'id'                  => (int) ($tenant['id'] ?? 0),
            'tenant_name'         => (string) ($tenant['tenant_name'] ?? ''),
            'tenant_code'         => (string) ($tenant['tenant_code'] ?? ''),
            'login_prefix'        => (string) ($tenant['login_prefix'] ?? ''),
            'owner_name'          => (string) ($tenant['owner_name'] ?? ''),
            'email'               => (string) ($tenant['email'] ?? ''),
            'phone'               => (string) ($tenant['phone'] ?? ''),
            'status'              => (string) ($tenant['status'] ?? 'active'),
            'head_branch_id'      => (int) ($headBranch['id'] ?? 0),
            'branch_name'         => (string) ($headBranch['branch_name'] ?? ($headBranch['branch_name_th'] ?? '')),
            'branch_code'         => (string) ($headBranch['branch_code'] ?? ''),
            'admin_user_id'       => (int) ($adminUser['id'] ?? 0),
            'admin_full_name'     => (string) ($adminUser['full_name'] ?? ''),
            'admin_username'      => (string) ($adminUser['username'] ?? ''),
            'admin_password'      => '',
            'admin_pin_code'      => (string) ($adminUser['pin_code'] ?? '9999'),
            'subscription_id'     => (int) ($subscription['id'] ?? 0),
            'plan_id'             => (string) ($subscription['plan_id'] ?? ''),
            'billing_type'        => (string) ($subscription['billing_type'] ?? 'monthly'),
            'subscription_status' => (string) ($subscription['status'] ?? 'active'),
            'start_date'          => (string) ($subscription['start_date'] ?? date('Y-m-d')),
            'end_date'            => (string) ($subscription['end_date'] ?? date('Y-m-d', strtotime('+1 month'))),
        ];
    }

    public function findTenantCreationConflicts(
        array $tenantData,
        array $branchData,
        array $adminData,
        ?int $ignoreTenantId = null,
        ?int $ignoreBranchId = null,
        ?int $ignoreUserId = null
    ): array {
        $conflicts = [];

        $tenantCode  = trim((string) ($tenantData['tenant_code'] ?? ''));
        $loginPrefix = trim((string) ($tenantData['login_prefix'] ?? ''));
        $email       = trim((string) ($tenantData['email'] ?? ''));
        $branchCode  = trim((string) ($branchData['branch_code'] ?? ''));
        $username    = trim((string) ($adminData['username'] ?? ''));

        if ($tenantCode !== '') {
            $builder = $this->db->table('tenants')
                ->where('tenant_code', $tenantCode)
                ->where('deleted_at', null);

            if ($ignoreTenantId) {
                $builder->where('id !=', $ignoreTenantId);
            }

            if ($builder->countAllResults() > 0) {
                $conflicts[] = [
                    'label' => 'Tenant Code',
                    'value' => $tenantCode,
                ];
            }
        }

        if ($loginPrefix !== '') {
            $builder = $this->db->table('tenants')
                ->where('login_prefix', $loginPrefix)
                ->where('deleted_at', null);

            if ($ignoreTenantId) {
                $builder->where('id !=', $ignoreTenantId);
            }

            if ($builder->countAllResults() > 0) {
                $conflicts[] = [
                    'label' => 'Login Prefix',
                    'value' => $loginPrefix,
                ];
            }
        }

        if ($email !== '') {
            $builder = $this->db->table('tenants')
                ->where('email', $email)
                ->where('deleted_at', null);

            if ($ignoreTenantId) {
                $builder->where('id !=', $ignoreTenantId);
            }

            if ($builder->countAllResults() > 0) {
                $conflicts[] = [
                    'label' => 'Tenant Email',
                    'value' => $email,
                ];
            }
        }

        if ($branchCode !== '') {
            $builder = $this->db->table('branches')
                ->where('branch_code', $branchCode)
                ->where('deleted_at', null);

            if ($ignoreBranchId) {
                $builder->where('id !=', $ignoreBranchId);
            }

            if ($builder->countAllResults() > 0) {
                $conflicts[] = [
                    'label' => 'Branch Code',
                    'value' => $branchCode,
                ];
            }
        }

        if ($username !== '') {
            $builder = $this->db->table('users')
                ->where('username', $username)
                ->where('deleted_at', null);

            if ($ignoreUserId) {
                $builder->where('id !=', $ignoreUserId);
            }

            if ($builder->countAllResults() > 0) {
                $conflicts[] = [
                    'label' => 'Admin Username',
                    'value' => $username,
                ];
            }
        }

        return $conflicts;
    }

    protected function datatableBaseBuilder()
    {
        $subscriptionSubquery = $this->db->table('subscriptions s1')
            ->select('MAX(s1.id)', false)
            ->where('s1.tenant_id = t.id', null, false)
            ->where('s1.deleted_at', null)
            ->getCompiledSelect();

        return $this->db->table('tenants t')
            ->select('
                t.id,
                t.tenant_code,
                t.login_prefix,
                t.tenant_name,
                t.owner_name,
                t.phone,
                t.email,
                t.status AS tenant_status,
                COALESCE(sp.plan_name, sp.name_th, sp.name_en, "-") AS plan_name,
                COALESCE(s.status, "") AS subscription_status
            ', false)
            ->join('subscriptions s', 's.id = (' . $subscriptionSubquery . ')', 'left', false)
            ->join('subscription_plans sp', 'sp.id = s.plan_id', 'left')
            ->where('t.deleted_at', null);
    }

    protected function applyDatatableSearch($builder, string $searchValue): void
    {
        if ($searchValue === '') {
            return;
        }

        $builder->groupStart()
            ->like('t.tenant_code', $searchValue)
            ->orLike('t.tenant_name', $searchValue)
            ->orLike('t.owner_name', $searchValue)
            ->orLike('t.email', $searchValue)
            ->orLike('t.phone', $searchValue)
            ->groupEnd();
    }

    protected function countBuilderRows($builder): int
    {
        $row = $builder
            ->select('COUNT(DISTINCT t.id) AS aggregate_total', false)
            ->get()
            ->getRowArray();

        return (int) ($row['aggregate_total'] ?? 0);
    }
}