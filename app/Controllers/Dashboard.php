<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use Config\Database;

class Dashboard extends BaseController
{
    public function index()
    {
        if (function_exists('is_super_admin') && is_super_admin()) {
            return redirect()->to(site_url('super-admin'));
        }

        $tenantId = $this->currentTenantIdOrFail();

        $db = Database::connect();

        $usersCount = $db->table('users')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->countAllResults();

        $categoriesCount = $db->table('categories')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->countAllResults();

        $productsCount = $db->table('products')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->countAllResults();

        $zonesCount = $db->table('zones')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->countAllResults();

        $tablesCount = $db->table('restaurant_tables')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->countAllResults();

        return view('dashboard/index', [
            'usersCount'      => $usersCount,
            'categoriesCount' => $categoriesCount,
            'productsCount'   => $productsCount,
            'zonesCount'      => $zonesCount,
            'tablesCount'     => $tablesCount,
        ]);
    }
}