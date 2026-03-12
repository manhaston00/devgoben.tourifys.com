<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class TenantAccessFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('tenant');

        if (function_exists('is_super_admin') && is_super_admin()) {
            return null;
        }

        if (function_exists('tenant_active') && ! tenant_active()) {
            return redirect()->to(site_url('/'))->with('error', lang('app.tenant_inactive'));
        }

        if (function_exists('subscription_active') && ! subscription_active()) {
            return redirect()->to(site_url('/'))->with('error', lang('app.subscription_inactive'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}