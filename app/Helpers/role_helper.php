<?php

if (!function_exists('hasRole')) {
    function hasRole($roles = [])
    {
        $userRole = session()->get('role');

        if (!$userRole) {
            return false;
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        return in_array($userRole, $roles, true);
    }
}