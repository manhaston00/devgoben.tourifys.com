<?php

if (! function_exists('user_permissions')) {
    function user_permissions(): array
    {
        return session('permissions') ?? [];
    }
}

if (! function_exists('can')) {
    function can(string $permissionKey): bool
    {
        $permissions = user_permissions();
        return in_array($permissionKey, $permissions, true);
    }
}
