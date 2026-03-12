<?php

if (!function_exists('user_permissions')) {
    function user_permissions(): array
    {
        return session('permissions') ?? [];
    }
}

if (!function_exists('can')) {
    function can(string $permissionKey): bool
    {
        $permissions = user_permissions();
        return in_array($permissionKey, $permissions, true);
    }
}
if (!function_exists('module_label')) {
    function module_label(string $moduleKey): string
    {
        $map = [
			'dashboard'        => 'Dashboard',
			'roles'            => 'Roles',
			'users'            => 'Users',
			'categories'       => 'Categories',
			'kitchen_stations' => 'Kitchen Stations',
			'products'         => 'Products',
			'zones'            => 'Zones',
			'tables'           => 'Tables',
			'pos'              => 'POS',
			'cashier'          => 'Cashier',
			'kitchen'          => 'Kitchen',
			'reservations'     => 'Reservations',
			'settings'         => 'Settings',
			'licenses'         => 'Licenses',
			'promotions'       => 'Promotions',
			'quick_notes'      => 'Quick Notes',
			'product_quick_options' => 'Food Details',
			'branches' => 'Branches',
		];

        return $map[$moduleKey] ?? ucwords(str_replace('_', ' ', $moduleKey));
    }
}