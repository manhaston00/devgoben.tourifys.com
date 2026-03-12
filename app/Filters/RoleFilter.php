<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $role = session()->get('role');

        if (!$role) {
            return redirect()->to('/login')->with('error', 'กรุณาเข้าสู่ระบบ');
        }

        if (empty($arguments)) {
            return;
        }

        $allowedRoles = array_map('trim', $arguments);

        if (!in_array($role, $allowedRoles, true)) {
            return redirect()->to('/dashboard')->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}