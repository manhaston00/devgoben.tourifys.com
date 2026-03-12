<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SubscriptionActiveFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session('isLoggedIn')) {
            return redirect()->to('/login')->with('error', lang('app.please_login'));
        }

        if (! function_exists('tenant_active') || ! tenant_active()) {
            return redirect()->to('/dashboard')->with('error', lang('app.tenant_inactive'));
        }

        if (! function_exists('subscription_active') || ! subscription_active()) {
            return redirect()->to('/dashboard')->with('error', lang('app.subscription_expired'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
