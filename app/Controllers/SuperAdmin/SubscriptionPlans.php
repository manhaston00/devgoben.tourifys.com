<?php

namespace App\Controllers\SuperAdmin;

use App\Controllers\BaseController;
use App\Models\SubscriptionPlanFeatureModel;
use App\Models\SubscriptionPlanModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Database;

class SubscriptionPlans extends BaseController
{
    protected SubscriptionPlanModel $planModel;
    protected SubscriptionPlanFeatureModel $planFeatureModel;
    protected $db;

    public function __construct()
    {
        $this->planModel        = new SubscriptionPlanModel();
        $this->planFeatureModel = new SubscriptionPlanFeatureModel();
        $this->db               = Database::connect();
    }

    protected function ensureSuperAdmin()
    {
        if (function_exists('is_super_admin') && is_super_admin()) {
            return null;
        }

        $sessionRole = strtolower((string) session('role_name'));
        $sessionCode = strtolower((string) session('role_code'));
        $isSuper     = (int) session('is_super_admin') === 1
            || $sessionRole === 'super admin'
            || $sessionRole === 'super-admin'
            || $sessionCode === 'super_admin';

        if (! $isSuper) {
            return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
        }

        return null;
    }

    public function index()
    {
        if ($guard = $this->ensureSuperAdmin()) {
            return $guard;
        }

        $q      = trim((string) $this->request->getGet('q'));
        $status = trim((string) $this->request->getGet('status'));

        $builder = $this->planModel->withDeleted();

        if ($q !== '') {
            $builder = $builder->groupStart()
                ->like('code', $q)
                ->orLike('name_th', $q)
                ->orLike('name_en', $q)
                ->orLike('plan_code', $q)
                ->orLike('plan_name', $q)
                ->orLike('plan_name_th', $q)
                ->orLike('plan_name_en', $q)
                ->groupEnd();
        }

        if ($status === 'active') {
            $builder = $builder->where('deleted_at', null)->where('is_active', 1);
        } elseif ($status === 'inactive') {
            $builder = $builder->where('deleted_at', null)->where('is_active', 0);
        } elseif ($status === 'trashed') {
            $builder = $builder->where('deleted_at IS NOT NULL', null, false);
        }

        $rows = $builder->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('super_admin/subscription_plans/index', [
            'title'  => lang('app.subscription_plans'),
            'rows'   => $rows,
            'q'      => $q,
            'status' => $status,
        ]);
    }

    public function create()
    {
        if ($guard = $this->ensureSuperAdmin()) {
            return $guard;
        }

        return view('super_admin/subscription_plans/form', [
            'title'    => lang('app.create_subscription_plan'),
            'row'      => null,
            'features' => [],
        ]);
    }

    public function store()
    {
        if ($guard = $this->ensureSuperAdmin()) {
            return $guard;
        }

        $data = $this->collectPostData();

        if ($this->planModel->codeExists($data['code'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', lang('app.plan_code_exists'));
        }

        $this->db->transBegin();

        try {
            $id = $this->planModel->insert($data, true);

            if (! $id) {
                throw new \RuntimeException(lang('app.save_failed'));
            }

            $this->planFeatureModel->syncPlanFeatures((int) $id, $this->collectFeatureRows());
			clear_tenant_access_cache(null, (int) $id);

            $this->db->transCommit();

            return redirect()->to(site_url('super-admin/subscription-plans'))
                ->with('success', lang('app.plan_created_successfully'));
        } catch (\Throwable $e) {
            $this->db->transRollback();

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function edit(int $id)
    {
        if ($guard = $this->ensureSuperAdmin()) {
            return $guard;
        }

        $row = $this->planModel->withDeleted()->find($id);

        if (! $row) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('super_admin/subscription_plans/form', [
            'title'    => lang('app.edit_subscription_plan'),
            'row'      => $row,
            'features' => $this->planFeatureModel->getPlanFeatures($id),
        ]);
    }

    public function update(int $id)
    {
        if ($guard = $this->ensureSuperAdmin()) {
            return $guard;
        }

        $row = $this->planModel->withDeleted()->find($id);

        if (! $row) {
            throw PageNotFoundException::forPageNotFound();
        }

        $data = $this->collectPostData();

        if ($this->planModel->codeExists($data['code'], $id)) {
            return redirect()->back()
                ->withInput()
                ->with('error', lang('app.plan_code_exists'));
        }

        $this->db->transBegin();

        try {
            if (! $this->planModel->update($id, $data)) {
                throw new \RuntimeException(lang('app.save_failed'));
            }

            $this->planFeatureModel->syncPlanFeatures($id, $this->collectFeatureRows());
			clear_tenant_access_cache(null, (int) $id);

            $this->db->transCommit();

            return redirect()->to(site_url('super-admin/subscription-plans'))
                ->with('success', lang('app.plan_updated_successfully'));
        } catch (\Throwable $e) {
            $this->db->transRollback();

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function delete(int $id)
    {
        if ($guard = $this->ensureSuperAdmin()) {
            return $guard;
        }

        $row = $this->planModel->find($id);

        if (! $row) {
            return redirect()->to(site_url('super-admin/subscription-plans'))
                ->with('error', lang('app.plan_not_found'));
        }

        $this->planModel->delete($id);

        return redirect()->to(site_url('super-admin/subscription-plans'))
            ->with('success', lang('app.plan_deleted_successfully'));
    }

    public function restore(int $id)
    {
        if ($guard = $this->ensureSuperAdmin()) {
            return $guard;
        }

        $row = $this->planModel->withDeleted()->find($id);

        if (! $row) {
            return redirect()->to(site_url('super-admin/subscription-plans'))
                ->with('error', lang('app.plan_not_found'));
        }

        if (empty($row['deleted_at'])) {
            return redirect()->to(site_url('super-admin/subscription-plans'))
                ->with('info', lang('app.plan_not_deleted'));
        }

        $this->planModel->update($id, ['deleted_at' => null]);

        return redirect()->to(site_url('super-admin/subscription-plans?status=trashed'))
            ->with('success', lang('app.plan_restored_successfully'));
    }

    protected function collectPostData(): array
    {
        $planType = trim((string) $this->request->getPost('plan_type'));
        $duration = $this->request->getPost('duration_days');
        $duration = ($duration === '' || $duration === null) ? null : (int) $duration;

        if ($planType === 'daily' && ($duration === null || $duration <= 0)) {
            $duration = 1;
        }

        if ($planType === 'monthly' && ($duration === null || $duration <= 0)) {
            $duration = 30;
        }

        if ($planType === 'demo' && ($duration === null || $duration <= 0)) {
            $duration = 7;
        }

        if ($planType === 'lifetime') {
            $duration = null;
        }

        $code   = trim((string) $this->request->getPost('code'));
        $nameTh = trim((string) $this->request->getPost('name_th'));
        $nameEn = trim((string) $this->request->getPost('name_en'));

        return [
            'code'            => $code,
            'plan_code'       => $code,

            'name_th'         => $nameTh,
            'name_en'         => $nameEn,
            'plan_name'       => $nameTh !== '' ? $nameTh : $nameEn,
            'plan_name_th'    => $nameTh,
            'plan_name_en'    => $nameEn,

            'description_th'  => trim((string) $this->request->getPost('description_th')),
            'description_en'  => trim((string) $this->request->getPost('description_en')),
            'description'     => trim((string) $this->request->getPost('description_th')),

            'plan_type'       => $planType,
            'duration_days'   => $duration,
            'price'           => (float) $this->request->getPost('price'),
            'currency'        => strtoupper(trim((string) $this->request->getPost('currency')) ?: 'THB'),
            'max_branches'    => max(1, (int) $this->request->getPost('max_branches')),
            'max_users'       => max(1, (int) $this->request->getPost('max_users')),
            'features_json'   => null,
            'sort_order'      => (int) $this->request->getPost('sort_order'),
            'is_active'       => (int) $this->request->getPost('is_active') === 1 ? 1 : 0,
            'is_public'       => (int) $this->request->getPost('is_public') === 1 ? 1 : 0,
            'status'          => (int) $this->request->getPost('is_active') === 1 ? 1 : 0,
        ];
    }

    protected function collectFeatureRows(): array
    {
        return [
            'pos.access' => [
                'enabled' => (int) $this->request->getPost('feature_pos_access') === 1,
            ],
            'pos.sell' => [
                'enabled' => (int) $this->request->getPost('feature_pos_sell') === 1,
            ],
            'reservations.manage' => [
                'enabled' => (int) $this->request->getPost('feature_reservations_manage') === 1,
            ],
            'tables.manage' => [
                'enabled' => (int) $this->request->getPost('feature_tables_manage') === 1,
            ],
            'zones.manage' => [
                'enabled' => (int) $this->request->getPost('feature_zones_manage') === 1,
            ],
            'reports.basic' => [
                'enabled' => (int) $this->request->getPost('feature_reports_basic') === 1,
            ],
            'multi.branch' => [
                'enabled' => (int) $this->request->getPost('feature_multi_branch') === 1,
            ],
            'branches_limit' => [
                'enabled' => 1,
                'limit'   => trim((string) $this->request->getPost('limit_branches')),
            ],
            'users_limit' => [
                'enabled' => 1,
                'limit'   => trim((string) $this->request->getPost('limit_users')),
            ],
            'products_limit' => [
                'enabled' => 1,
                'limit'   => trim((string) $this->request->getPost('limit_products')),
            ],
        ];
    }
}