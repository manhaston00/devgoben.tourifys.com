<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionPlanModel extends Model
{
    protected $table            = 'subscription_plans';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';

    protected $allowedFields = [
        'code',
        'name_th',
        'name_en',

        'plan_code',
        'plan_name',
        'plan_name_th',
        'plan_name_en',

        'plan_type',
        'price',
        'currency',
        'max_branches',
        'max_users',
        'features_json',
        'sort_order',
        'is_active',
        'is_public',
        'duration_days',
        'is_lifetime',
        'status',
        'description',
        'description_th',
        'description_en',
        'deleted_at',
    ];

    public function normalizePayload(array $payload): array
    {
        $planCode   = trim((string) ($payload['plan_code'] ?? $payload['code'] ?? ''));
        $planNameTh = trim((string) ($payload['plan_name_th'] ?? $payload['name_th'] ?? ''));
        $planNameEn = trim((string) ($payload['plan_name_en'] ?? $payload['name_en'] ?? ''));
        $planName   = trim((string) ($payload['plan_name'] ?? ''));

        if ($planName === '') {
            $planName = $planNameTh !== '' ? $planNameTh : $planNameEn;
        }

        if ($planNameTh === '') {
            $planNameTh = $planName;
        }

        if ($planNameEn === '') {
            $planNameEn = $planName;
        }

        $payload['plan_code']    = $planCode;
        $payload['plan_name']    = $planName;
        $payload['plan_name_th'] = $planNameTh;
        $payload['plan_name_en'] = $planNameEn;

        // mirror ฟิลด์เก่าไว้ compatibility
        $payload['code']    = $planCode;
        $payload['name_th'] = $planNameTh;
        $payload['name_en'] = $planNameEn;

        $payload['plan_type']    = trim((string) ($payload['plan_type'] ?? 'monthly'));
        $payload['currency']     = strtoupper(trim((string) ($payload['currency'] ?? 'THB')));
        $payload['price']        = (float) ($payload['price'] ?? 0);
        $payload['sort_order']   = (int) ($payload['sort_order'] ?? 0);
        $payload['is_active']    = (int) ($payload['is_active'] ?? 1) ? 1 : 0;
        $payload['is_public']    = (int) ($payload['is_public'] ?? 1) ? 1 : 0;
        $payload['is_lifetime']  = (int) ($payload['is_lifetime'] ?? 0) ? 1 : 0;
        $payload['status']       = (int) ($payload['status'] ?? $payload['is_active'] ?? 1) ? 1 : 0;
        $payload['max_branches'] = max(1, (int) ($payload['max_branches'] ?? 1));
        $payload['max_users']    = max(1, (int) ($payload['max_users'] ?? 5));

        if ($payload['is_lifetime'] === 1 || $payload['plan_type'] === 'lifetime') {
            $payload['duration_days'] = null;
            $payload['is_lifetime']   = 1;
        } else {
            $payload['duration_days'] = ($payload['duration_days'] ?? '') !== ''
                ? (int) $payload['duration_days']
                : null;
        }

        return $payload;
    }

    public function getAdminRows(): array
    {
        return $this->withDeleted()
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function getActivePlans(bool $publicOnly = false): array
    {
        $builder = $this->where('deleted_at', null)
            ->where('status', 1)
            ->where('is_active', 1);

        if ($publicOnly) {
            $builder->where('is_public', 1);
        }

        return $builder
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function findActivePlan(int $id): ?array
    {
        $row = $this->where('id', $id)
            ->where('deleted_at', null)
            ->where('status', 1)
            ->where('is_active', 1)
            ->first();

        return $row ?: null;
    }

    public function codeExists(string $code, ?int $ignoreId = null): bool
    {
        $code = trim($code);

        if ($code === '') {
            return false;
        }

        $builder = $this->withDeleted()
            ->groupStart()
                ->where('code', $code)
                ->orWhere('plan_code', $code)
            ->groupEnd();

        if ($ignoreId !== null && $ignoreId > 0) {
            $builder->where('id !=', $ignoreId);
        }

        $row = $builder->first();

        return ! empty($row);
    }
}