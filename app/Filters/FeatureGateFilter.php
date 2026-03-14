<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class FeatureGateFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper(['tenant', 'app']);

        if (function_exists('is_super_admin') && is_super_admin()) {
            return null;
        }

        if (! function_exists('feature_enabled')) {
            return redirect()->to(site_url('/'))->with('error', lang('app.feature_not_available_for_plan'));
        }

        $featureKey = normalize_feature_key((string) ($arguments[0] ?? ''));

        if ($featureKey !== '' && ! feature_enabled($featureKey)) {
            return redirect()->to(site_url('/'))->with('error', lang('app.feature_not_available_for_plan'));
        }

        $path = method_exists($request, 'getPath') ? (string) $request->getPath() : trim((string) $request->getUri()->getPath(), '/');
        $runtimeSettingKey = function_exists('runtime_feature_key_from_request')
            ? runtime_feature_key_from_request($path, $featureKey)
            : '';

        if ($runtimeSettingKey !== '' && function_exists('setting_bool') && ! setting_bool($runtimeSettingKey, true)) {
            return redirect()->to(site_url('/'))->with('error', lang('app.feature_not_available_for_plan'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
