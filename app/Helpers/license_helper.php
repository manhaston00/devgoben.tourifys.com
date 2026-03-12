<?php

if (! function_exists('license_enabled')) {
    function license_enabled(): bool
    {
        return function_exists('subscription_active') ? subscription_active() : false;
    }
}

if (! function_exists('license_plan')) {
    function license_plan(): string
    {
        if (function_exists('current_subscription')) {
            $subscription = current_subscription();
            if (! empty($subscription['plan_code'])) {
                return (string) $subscription['plan_code'];
            }
        }

        return (string) app_setting('license_plan', 'standard');
    }
}

if (! function_exists('license_expired')) {
    function license_expired(): bool
    {
        if (function_exists('subscription_active')) {
            return ! subscription_active();
        }

        $expiredAt = app_setting('license_expired_at');

        if (empty($expiredAt)) {
            return false;
        }

        $timestamp = strtotime((string) $expiredAt);

        if ($timestamp === false) {
            return false;
        }

        return $timestamp < time();
    }
}
