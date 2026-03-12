<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class FeatureGateFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('tenant');

        if (function_exists('is_super_admin') && is_super_admin()) {
            return null;
        }

        if (! function_exists('feature_enabled')) {
            return redirect()->to(site_url('/'))->with('error', lang('app.feature_not_available_for_plan'));
        }

        $featureKey = normalize_feature_key((string) ($arguments[0] ?? ''));

        if ($featureKey === '') {
            return null;
        }

        if (! feature_enabled($featureKey)) {
            return redirect()->to(site_url('/'))->with('error', lang('app.feature_not_available_for_plan'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}