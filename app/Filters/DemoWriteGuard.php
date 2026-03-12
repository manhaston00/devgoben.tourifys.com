<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class DemoWriteGuard implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (is_cli()) {
            return;
        }

        helper(['saas', 'url']);

        $method = strtoupper($request->getMethod());

        // allow read requests
        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        if (! function_exists('current_tenant_id') || ! function_exists('tenant_demo_readonly')) {
            return;
        }

        $tenantId = current_tenant_id();

        if ($tenantId <= 0) {
            return;
        }

        if (tenant_demo_readonly()) {

            $message = lang('app.demo_readonly_denied');

            $referrer = previous_url();

            if (! empty($referrer)) {
                return redirect()->back()->with('error', $message);
            }

            return redirect()->to(site_url('/'))->with('error', $message);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }
}