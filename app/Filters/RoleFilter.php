<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('isLoggedIn')) {
            return redirect()->to(site_url('login'))
                ->with('error', lang('app.please_login'));
        }

        if (function_exists('is_super_admin') && is_super_admin()) {
            return null;
        }

        $roleCandidates = [
            strtolower(trim((string) session('role_code'))),
            strtolower(trim((string) session('role_name'))),
            strtolower(trim((string) session('role'))),
        ];

        $roleCandidates = array_values(array_filter(array_unique($roleCandidates)));

        if (empty($roleCandidates)) {
            return redirect()->to(site_url('login'))
                ->with('error', lang('app.please_login'));
        }

        if (empty($arguments)) {
            return null;
        }

        $allowedRoles = array_map(
            static fn ($role) => strtolower(trim((string) $role)),
            (array) $arguments
        );

        foreach ($roleCandidates as $currentRole) {
            if (in_array($currentRole, $allowedRoles, true)) {
                return null;
            }
        }

        return redirect()->to(site_url('/'))
            ->with('error', lang('app.no_permission'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
	
}