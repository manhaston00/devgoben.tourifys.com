<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SubscriptionGuard implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $path = trim($request->uri->getPath(), '/');

        // Allow paths
        $allowPaths = [
            '',
            '/',
            'login',
            'logout',
            'subscription/expired',
            'lang',
            'demo-login',
            'cron',
        ];

        foreach ($allowPaths as $allow) {

            $allow = trim($allow, '/');

            if ($allow === '') {
                if ($path === '') {
                    return;
                }
                continue;
            }

            if ($path === $allow || strpos($path, $allow . '/') === 0) {
                return;
            }
        }

        // POS bypass (allow billing even if expired)
        if (strpos($path, 'pos') === 0) {
            return;
        }

        if (! function_exists('current_tenant_id') || ! function_exists('tenant_subscription_expired')) {
            return;
        }

        $tenantId = current_tenant_id();

        if (empty($tenantId)) {
            return;
        }

        if (tenant_subscription_expired((int) $tenantId)) {

            return redirect()
                ->to(site_url('subscription/expired'))
                ->with('error', lang('app.subscription_expired'));
        }

        return;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}