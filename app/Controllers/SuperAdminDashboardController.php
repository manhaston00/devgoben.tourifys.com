<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class SuperAdminDashboardController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        $tenantCount = (int) $db->table('tenants')
            ->where('deleted_at', null)
            ->countAllResults();

        $activeSubscriptions = (int) $db->table('subscriptions')
            ->where('deleted_at', null)
            ->where('status', 'active')
            ->countAllResults();

        $expiredSubscriptions = (int) $db->table('subscriptions')
            ->where('deleted_at', null)
            ->where('status', 'expired')
            ->countAllResults();

        $suspendedSubscriptions = (int) $db->table('subscriptions')
            ->where('deleted_at', null)
            ->where('status', 'suspended')
            ->countAllResults();

        $demoSubscriptions = (int) $db->table('subscriptions')
            ->where('deleted_at', null)
            ->where('is_demo', 1)
            ->countAllResults();

        $plans = (int) $db->table('subscription_plans')
            ->where('deleted_at', null)
            ->countAllResults();

        return view('super_admin/dashboard', [
            'title'                  => lang('app.central_dashboard'),
            'tenantCount'            => $tenantCount,
            'activeSubscriptions'    => $activeSubscriptions,
            'expiredSubscriptions'   => $expiredSubscriptions,
            'suspendedSubscriptions' => $suspendedSubscriptions,
            'demoSubscriptions'      => $demoSubscriptions,
            'plans'                  => $plans,
        ]);
    }
}