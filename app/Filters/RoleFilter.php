<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session('isLoggedIn')) {
            return redirect()
                ->to(site_url('login'))
                ->with('error', lang('app.please_login'));
        }

        if (function_exists('is_super_admin') && is_super_admin()) {
            return null;
        }

        if (empty($arguments) || ! is_array($arguments)) {
            return null;
        }

        $currentRoles = array_filter([
            strtolower(trim((string) session('role_code'))),
            strtolower(trim((string) session('role_name'))),
            strtolower(trim((string) session('role'))),
        ]);

        $allowedRoles = array_map(
            static fn ($role) => strtolower(trim((string) $role)),
            $arguments
        );

        foreach ($currentRoles as $role) {
            if (in_array($role, $allowedRoles, true)) {
                return null;
            }
        }

        return redirect()
            ->to(site_url('dashboard'))
            ->with('error', lang('app.no_permission'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}