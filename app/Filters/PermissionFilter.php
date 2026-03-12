<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(site_url('login'))->with('error', lang('app.please_login'));
        }

        if (function_exists('is_super_admin') && is_super_admin()) {
            return null;
        }

        if (empty($arguments)) {
            return null;
        }

        $permissions = session('permissions') ?? [];

        foreach ($arguments as $requiredPermission) {
            $requiredPermission = trim((string) $requiredPermission);

            if ($requiredPermission !== '' && in_array($requiredPermission, $permissions, true)) {
                return null;
            }
        }

        return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}