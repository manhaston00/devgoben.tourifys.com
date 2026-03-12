<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SuperAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session('isLoggedIn')) {
            return redirect()->to(site_url('login'))->with('error', lang('app.please_login'));
        }

        if (! function_exists('is_super_admin') || ! is_super_admin()) {
            return redirect()->to(site_url('/'))->with('error', lang('app.super_admin_only'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}