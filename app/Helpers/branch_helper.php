<?php

use App\Models\BranchModel;

if (! function_exists('current_branch_id')) {
    function current_branch_id(): ?int
    {
        $branchId = session('branch_id');

        if ($branchId !== null && $branchId !== '') {
            return (int) $branchId;
        }

        if (session('user_branch_id')) {
            return (int) session('user_branch_id');
        }

        return null;
    }
}

if (! function_exists('current_branch_name')) {
    function current_branch_name(): string
    {
        $locale = function_exists('current_locale') ? current_locale() : 'th';

        $nameTh = trim((string) (session('branch_name_th') ?? ''));
        $nameEn = trim((string) (session('branch_name_en') ?? ''));
        $name   = trim((string) (session('branch_name') ?? ''));

        if ($locale === 'en') {
            if ($nameEn !== '') {
                return $nameEn;
            }
            if ($name !== '') {
                return $name;
            }
            if ($nameTh !== '') {
                return $nameTh;
            }

            return '-';
        }

        if ($nameTh !== '') {
            return $nameTh;
        }
        if ($name !== '') {
            return $name;
        }
        if ($nameEn !== '') {
            return $nameEn;
        }

        return '-';
    }
}

if (! function_exists('current_branch_code')) {
    function current_branch_code(): string
    {
        return (string) (session('branch_code') ?? '');
    }
}

if (! function_exists('can_manage_all_branches')) {
    function can_manage_all_branches(): bool
    {
        return (function_exists('is_super_admin') && is_super_admin())
            || (function_exists('can') && can('branches.switch'));
    }
}

if (! function_exists('allowed_branch_ids')) {
    function allowed_branch_ids(): array
    {
        $model    = new BranchModel();
        $tenantId = function_exists('current_tenant_id') ? current_tenant_id() : null;

        if (can_manage_all_branches()) {
            $builder = $model->select('id')->where('status', 1);

            if (! empty($tenantId)) {
                $builder->where('tenant_id', $tenantId);
            }

            $rows = $builder->findAll();

            return array_values(array_map(static fn ($row) => (int) ($row['id'] ?? 0), $rows));
        }

        $branchId = current_branch_id();

        return $branchId ? [$branchId] : [];
    }
}

if (! function_exists('can_access_branch')) {
    function can_access_branch(int $branchId): bool
    {
        return in_array($branchId, allowed_branch_ids(), true);
    }
}

if (! function_exists('branch_scope_filter')) {
    function branch_scope_filter($builder, string $tableAlias = '')
    {
        $column = $tableAlias !== '' ? $tableAlias . '.branch_id' : 'branch_id';

        $activeBranchId = current_branch_id();
        if ($activeBranchId) {
            return $builder->where($column, (int) $activeBranchId);
        }

        return $builder;
    }
}

if (! function_exists('branch_options')) {
    function branch_options(): array
    {
        $model    = new BranchModel();
        $tenantId = function_exists('current_tenant_id') ? current_tenant_id() : null;
        $rows     = $model->getActiveBranches($tenantId);

        if (can_manage_all_branches()) {
            return $rows;
        }

        $branchId = current_branch_id();

        return array_values(array_filter($rows, static function ($row) use ($branchId) {
            return (int) ($row['id'] ?? 0) === (int) $branchId;
        }));
    }
}