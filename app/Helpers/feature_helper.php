<?php

if (!function_exists('feature')) {

    function feature(string $key): bool
    {
        if (!function_exists('feature_enabled')) {
            return false;
        }

        return feature_enabled($key);
    }

}

if (!function_exists('feature_limit')) {

    function feature_limit(string $key)
    {
        if (!function_exists('limit_value')) {
            return null;
        }

        return limit_value($key);
    }

}