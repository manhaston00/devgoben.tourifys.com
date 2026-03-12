<?php

namespace App\Libraries;

trait TenantQueryTrait
{
    protected function tenantId(): ?int
    {
        return function_exists('current_tenant_id')
            ? current_tenant_id()
            : null;
    }

    protected function applyTenant($builder, string $alias = null)
    {
        $tenantId = $this->tenantId();

        if (!$tenantId) {
            return $builder;
        }

        if ($alias) {
            $builder->where($alias . '.tenant_id', $tenantId);
        } else {
            $builder->where('tenant_id', $tenantId);
        }

        return $builder;
    }
}