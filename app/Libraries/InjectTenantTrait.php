<?php

namespace App\Libraries;

trait InjectTenantTrait
{
    protected function injectTenantId(array $data): array
    {
        if (
            (!isset($data['data']['tenant_id']) || empty($data['data']['tenant_id'])) &&
            function_exists('current_tenant_id')
        ) {
            $tenantId = current_tenant_id();

            if (!empty($tenantId)) {
                $data['data']['tenant_id'] = (int) $tenantId;
            }
        }

        return $data;
    }
}