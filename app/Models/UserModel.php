<?php

namespace App\Models;

use App\Models\TenantScopedModel;

class UserModel extends TenantScopedModel
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';

    protected $useTimestamps    = true;
    protected $useSoftDeletes   = true;

    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = 'deleted_at';

    protected $allowedFields    = [
        'tenant_id',
        'role_id',
        'branch_id',
        'full_name',
        'username',
        'password_hash',
        'pin_code',
        'phone',
        'email',
        'avatar',
        'status',
        'last_login_at',
        'deleted_at',
    ];

    public function normalizeTenantCode(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = preg_replace('/_+/', '_', (string) $value);
        $value = trim((string) $value, '_');

        return $value;
    }

    public function normalizeBaseUsername(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9._-]+/', '', $value);

        return $value;
    }

    public function buildLoginUsername(?string $loginPrefix, ?string $baseUsername): string
    {
        $loginPrefix  = $this->normalizeTenantCode($loginPrefix);
        $baseUsername = $this->normalizeBaseUsername($baseUsername);

        if ($baseUsername === '') {
            return '';
        }

        if ($loginPrefix === '') {
            return $baseUsername;
        }

        $prefix = $loginPrefix . '_';

        if (strpos($baseUsername, $prefix) === 0) {
            return $baseUsername;
        }

        return $prefix . $baseUsername;
    }

    public function extractBaseUsername(?string $loginUsername, ?string $loginPrefix): string
    {
        $loginUsername = strtolower(trim((string) $loginUsername));
        $loginPrefix   = $this->normalizeTenantCode($loginPrefix);

        if ($loginPrefix === '') {
            return $this->normalizeBaseUsername($loginUsername);
        }

        $prefix = $loginPrefix . '_';

        if (strpos($loginUsername, $prefix) === 0) {
            return substr($loginUsername, strlen($prefix));
        }

        return $this->normalizeBaseUsername($loginUsername);
    }

    public function usernameExists(string $loginUsername, ?int $ignoreUserId = null): bool
    {
        $loginUsername = strtolower(trim($loginUsername));

        if ($loginUsername === '') {
            return false;
        }

        $builder = $this->builder();
        $builder->where('LOWER(username)', $loginUsername);
        $builder->where('deleted_at', null);

        if (! empty($ignoreUserId)) {
            $builder->where('id !=', (int) $ignoreUserId);
        }

        return (bool) $builder->countAllResults();
    }

    public function emailExists(string $email, ?int $ignoreUserId = null): bool
    {
        $email = trim($email);

        if ($email === '') {
            return false;
        }

        $builder = $this->builder();
        $builder->where('email', $email);
        $builder->where('deleted_at', null);

        if (! empty($ignoreUserId)) {
            $builder->where('id !=', (int) $ignoreUserId);
        }

        return (bool) $builder->countAllResults();
    }

    protected function usersWithRoleBuilder(?int $tenantId = null, ?int $branchId = null)
	{
		$builder = $this->builder();

		$builder->select("
			users.*,
			roles.role_name,
			roles.role_name_th,
			roles.role_name_en,
			roles.tenant_id AS role_tenant_id,
			branches.branch_code,
			branches.branch_name,
			branches.branch_name_th,
			branches.branch_name_en,
			branches.tenant_id AS branch_tenant_id,
			tenants.tenant_code,
			COALESCE(tenants.login_prefix, tenants.tenant_code) AS login_prefix
		");

		$builder->join(
			'roles',
			'roles.id = users.role_id
			 AND roles.deleted_at IS NULL
			 AND (roles.tenant_id = users.tenant_id OR roles.tenant_id IS NULL)',
			'left'
		);

		$builder->join(
			'branches',
			'branches.id = users.branch_id
			 AND branches.deleted_at IS NULL
			 AND branches.tenant_id = users.tenant_id',
			'left'
		);

		$builder->join(
			'tenants',
			'tenants.id = users.tenant_id
			 AND tenants.deleted_at IS NULL',
			'left'
		);

		$builder->where('users.deleted_at', null);

		if (! empty($tenantId)) {
			$builder->where('users.tenant_id', (int) $tenantId);
		}

		if (! empty($branchId)) {
			$builder->where('users.branch_id', (int) $branchId);
		}

		if (!(function_exists('is_super_admin') && is_super_admin())) {
			$builder->where('users.tenant_id >', 0);
			$builder->groupStart()
				->where('LOWER(COALESCE(roles.role_name, "")) !=', 'super_admin')
				->where('LOWER(COALESCE(roles.role_name_en, "")) !=', 'super_admin')
				->where('LOWER(COALESCE(roles.role_name_th, "")) !=', 'super_admin')
				->groupEnd();
		}

		return $builder;
	}

    public function getUsersWithRole(?int $tenantId = null, ?int $branchId = null): array
    {
        if (
            empty($tenantId) &&
            function_exists('current_tenant_id') &&
            !(function_exists('is_super_admin') && is_super_admin())
        ) {
            $tenantId = (int) current_tenant_id();
        }

        return $this->usersWithRoleBuilder($tenantId, $branchId)
            ->orderBy('users.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getUsersByBranch(?int $branchId = null, ?int $tenantId = null): array
    {
        return $this->getUsersWithRole($tenantId, $branchId);
    }

    public function findTenantUserWithRole(int $id, int $tenantId, ?int $branchId = null): ?array
    {
        if ($id <= 0 || $tenantId <= 0) {
            return null;
        }

        $row = $this->usersWithRoleBuilder($tenantId, $branchId)
            ->where('users.id', $id)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public function insertTenantUser(array $data): bool
    {
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        if ($tenantId <= 0) {
            return false;
        }

        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);

        return (bool) $this->insert($data, false);
    }

    public function updateTenantUser(int $id, array $data, int $tenantId, ?int $branchId = null): bool
    {
        $row = $this->findTenantUserWithRole($id, $tenantId, $branchId);
        if (! $row) {
            return false;
        }

        unset($data['tenant_id'], $data['id'], $data['created_at'], $data['deleted_at']);

        if ($this->useTimestamps && ! array_key_exists($this->updatedField, $data)) {
            $data[$this->updatedField] = date('Y-m-d H:i:s');
        }

        $builder = $this->builder();
        $builder->where('id', $id);
        $builder->where('tenant_id', $tenantId);
        $builder->where('deleted_at', null);

        if (! empty($branchId)) {
            $builder->where('branch_id', $branchId);
        }

        return (bool) $builder->update($data);
    }

    public function deleteTenantUser(int $id, int $tenantId, ?int $branchId = null): bool
    {
        $row = $this->findTenantUserWithRole($id, $tenantId, $branchId);
        if (! $row) {
            return false;
        }

        if ($this->useSoftDeletes) {
            $data = [
                $this->deletedField => date('Y-m-d H:i:s'),
            ];

            if ($this->useTimestamps) {
                $data[$this->updatedField] = date('Y-m-d H:i:s');
            }

            $builder = $this->builder();
            $builder->where('id', $id);
            $builder->where('tenant_id', $tenantId);
            $builder->where('deleted_at', null);

            if (! empty($branchId)) {
                $builder->where('branch_id', $branchId);
            }

            return (bool) $builder->update($data);
        }

        $builder = $this->builder();
        $builder->where('id', $id);
        $builder->where('tenant_id', $tenantId);

        if (! empty($branchId)) {
            $builder->where('branch_id', $branchId);
        }

        return (bool) $builder->delete();
    }

    public function findActiveLoginUser(string $username): ?array
	{
		$username = strtolower(trim($username));

		if ($username === '') {
			return null;
		}

		$row = $this->select('
				users.*,
				roles.role_name,
				roles.role_name_th,
				roles.role_name_en,
				roles.tenant_id AS role_tenant_id,
				branches.tenant_id AS branch_tenant_id,
				branches.branch_code,
				branches.branch_name,
				branches.branch_name_th,
				branches.branch_name_en,
				branches.default_locale,
				branches.status AS branch_status,
				tenants.tenant_code,
				COALESCE(tenants.login_prefix, tenants.tenant_code) AS login_prefix
			')
			->join(
				'roles',
				'roles.id = users.role_id
				 AND roles.deleted_at IS NULL
				 AND (roles.tenant_id = users.tenant_id OR roles.tenant_id IS NULL)',
				'left'
			)
			->join(
				'branches',
				'branches.id = users.branch_id
				 AND branches.deleted_at IS NULL
				 AND branches.tenant_id = users.tenant_id',
				'left'
			)
			->join(
				'tenants',
				'tenants.id = users.tenant_id
				 AND tenants.deleted_at IS NULL',
				'left'
			)
			->where('LOWER(users.username)', $username)
			->where('users.status', 1)
			->where('users.deleted_at', null)
			->first();

		return $row ?: null;
	}
	
	public function findTenantUser(int $tenantId, int $id): ?array
	{
		if ($tenantId <= 0 || $id <= 0) {
			return null;
		}

		return $this->builder()
			->where('users.tenant_id', $tenantId)
			->where('users.id', $id)
			->where('users.deleted_at IS NULL', null, false)
			->get()
			->getRowArray();
	}

	public function getTenantUsers(int $tenantId): array
	{
		if ($tenantId <= 0) {
			return [];
		}

		return $this->builder()
			->where('users.tenant_id', $tenantId)
			->where('users.deleted_at IS NULL', null, false)
			->orderBy('users.id', 'DESC')
			->get()
			->getResultArray();
	}
}