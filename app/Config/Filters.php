<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    public $aliases = [
        'csrf'                => CSRF::class,
        'toolbar'             => DebugToolbar::class,
        'honeypot'            => Honeypot::class,
        'invalidchars'        => InvalidChars::class,
        'secureheaders'       => SecureHeaders::class,

        // Primary filters
        'auth'                => \App\Filters\AuthFilter::class,
        'auth_subscription'   => \App\Filters\AuthSubscriptionFilter::class,
        'permission'          => \App\Filters\PermissionFilter::class,
        'feature_gate'        => \App\Filters\FeatureGateFilter::class,
        'demo_write'          => \App\Filters\DemoWriteGuard::class,
        'super_admin'         => \App\Filters\SuperAdminFilter::class,

        // Legacy / backward compatibility
        'subscription_active' => \App\Filters\SubscriptionActiveFilter::class,
        'subscription_guard'  => \App\Filters\SubscriptionGuard::class,
        'tenantAccess'        => \App\Filters\TenantAccessFilter::class,
        'role'                => \App\Filters\RoleFilter::class,
    ];

    public $globals = [
        'before' => [
            'demo_write',
        ],
        'after' => [
            'toolbar',
        ],
    ];

    public $methods = [];

    public $filters = [];
}