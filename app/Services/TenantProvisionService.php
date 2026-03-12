<?php

namespace App\Services;

use App\Models\BranchModel;
use App\Models\RoleModel;
use App\Models\SubscriptionModel;
use App\Models\SubscriptionPlanModel;
use App\Models\TenantModel;
use App\Models\UserModel;
use Config\Database;
use RuntimeException;

class TenantProvisionService
{
    protected TenantModel $tenantModel;
    protected BranchModel $branchModel;
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected SubscriptionModel $subscriptionModel;
    protected SubscriptionPlanModel $subscriptionPlanModel;

    public function __construct()
    {
        $this->tenantModel            = new TenantModel();
        $this->branchModel            = new BranchModel();
        $this->userModel              = new UserModel();
        $this->roleModel              = new RoleModel();
        $this->subscriptionModel      = new SubscriptionModel();
        $this->subscriptionPlanModel  = new SubscriptionPlanModel();
    }

    protected function resolveAdminRoleId(): int
    {
        $role = $this->roleModel
            ->where('deleted_at', null)
            ->groupStart()
                ->where('role_name', 'admin')
                ->orWhere('role_name_en', 'admin')
            ->groupEnd()
            ->first();

        if (! $role) {
            throw new RuntimeException('Admin role not found');
        }

        return (int) $role['id'];
    }

    protected function buildSubscriptionDates(array $plan): array
    {
        $today = date('Y-m-d');

        if ((int) ($plan['is_lifetime'] ?? 0) === 1) {
            return [
                'start_date' => $today,
                'end_date'   => null,
            ];
        }

        $durationDays = (int) ($plan['duration_days'] ?? 0);
        if ($durationDays <= 0) {
            $durationDays = 30;
        }

        return [
            'start_date' => $today,
            'end_date'   => date('Y-m-d', strtotime('+' . ($durationDays - 1) . ' days')),
        ];
    }

    public function createTenantWithDefaults(array $input): int
    {
        $db = Database::connect();
        $db->transBegin();

        try {
            $planId = (int) ($input['plan_id'] ?? 0);
            $plan = $this->subscriptionPlanModel->findActivePlan($planId);

            if (! $plan) {
                throw new RuntimeException('Subscription plan not found');
            }

            $tenantId = $this->tenantModel->insert([
                'tenant_code' => trim((string) ($input['tenant_code'] ?? '')),
                'tenant_name' => trim((string) ($input['tenant_name'] ?? '')),
                'owner_name'  => trim((string) ($input['owner_name'] ?? '')),
                'email'       => trim((string) ($input['email'] ?? '')),
                'phone'       => trim((string) ($input['phone'] ?? '')),
                'status'      => trim((string) ($input['status'] ?? 'active')),
            ], true);

            if (! $tenantId) {
                throw new RuntimeException('Create tenant failed');
            }

            $branchName = trim((string) ($input['branch_name'] ?? 'สำนักงานใหญ่'));
            $branchCode = trim((string) ($input['branch_code'] ?? 'HO'));

            $branchId = $this->branchModel->insert([
                'tenant_id'       => $tenantId,
                'branch_code'     => $branchCode,
                'branch_name'     => $branchName,
                'branch_name_th'  => $branchName,
                'branch_name_en'  => 'Head Office',
                'status'          => 1,
                'is_head_office'  => 1,
                'sort_order'      => 0,
                'default_locale'  => 'th',
                'timezone'        => 'Asia/Bangkok',
                'phone'           => trim((string) ($input['phone'] ?? '')),
                'email'           => trim((string) ($input['email'] ?? '')),
            ], true);

            if (! $branchId) {
                throw new RuntimeException('Create head office branch failed');
            }

            $adminRoleId = $this->resolveAdminRoleId();

            $adminUsername = trim((string) ($input['admin_username'] ?? ''));
            $adminPassword = trim((string) ($input['admin_password'] ?? ''));
            $adminFullName = trim((string) ($input['admin_full_name'] ?? 'Administrator'));

            if ($adminUsername === '' || $adminPassword === '') {
                throw new RuntimeException('Admin username/password required');
            }

            $dup = $this->userModel
                ->where('username', $adminUsername)
                ->where('deleted_at', null)
                ->first();

            if ($dup) {
                throw new RuntimeException('Admin username already exists');
            }

            $userId = $this->userModel->insert([
                'tenant_id'     => $tenantId,
                'branch_id'     => $branchId,
                'role_id'       => $adminRoleId,
                'full_name'     => $adminFullName,
                'username'      => $adminUsername,
                'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
                'pin_code'      => trim((string) ($input['admin_pin_code'] ?? '9999')),
                'phone'         => trim((string) ($input['phone'] ?? '')),
                'email'         => trim((string) ($input['email'] ?? '')),
                'status'        => 1,
            ], true);

            if (! $userId) {
                throw new RuntimeException('Create tenant admin failed');
            }

            $dates = $this->buildSubscriptionDates($plan);

            $subscriptionId = $this->subscriptionModel->insert([
                'tenant_id'      => $tenantId,
                'plan_id'        => (int) $plan['id'],
                'billing_type'   => (string) ($plan['plan_type'] ?? 'monthly'),
                'status'         => 'active',
                'start_date'     => $dates['start_date'],
                'end_date'       => $dates['end_date'],
                'started_at'     => date('Y-m-d H:i:s'),
                'expires_at'     => $dates['end_date'] ? ($dates['end_date'] . ' 23:59:59') : null,
                'is_demo'        => 0,
                'demo_readonly'  => 0,
            ], true);

            if (! $subscriptionId) {
                throw new RuntimeException('Create subscription failed');
            }

            $db->transCommit();

            return (int) $tenantId;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
}