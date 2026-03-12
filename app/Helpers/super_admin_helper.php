<?php

if (! function_exists('is_super_admin')) {
    function is_super_admin(): bool
    {
        if ((int) (session('is_super_admin') ?? 0) === 1) {
            return true;
        }

        $candidates = [
            session('role_code'),
            session('role_name'),
            session('role'),
        ];

        foreach ($candidates as $value) {
            $role = strtolower(trim((string) $value));

            if (in_array($role, [
                'super_admin',
                'super admin',
                'super-admin',
                'superadmin',
                'platform_admin',
                'platform admin',
            ], true)) {
                return true;
            }
        }

        return false;
    }
}