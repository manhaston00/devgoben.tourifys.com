<?php

if (! function_exists('is_super_admin')) {
    function is_super_admin(): bool
    {
        $roleName = strtolower(trim((string) session('role_name')));
        $roleCode = strtolower(trim((string) session('role_code')));

        return (int) session('is_super_admin') === 1
            || $roleName === 'super_admin'
            || $roleName === 'super admin'
            || $roleName === 'super-admin'
            || $roleCode === 'super_admin';
    }
}