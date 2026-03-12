<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanFeatureModel extends Model
{
    protected $table            = 'subscription_plan_features';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'plan_id',
        'feature_key',
        'is_enabled',
        'limit_value',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function normalizeFeatureKey(string $featureKey): string
    {
        helper('tenant');

        return function_exists('normalize_feature_key')
            ? normalize_feature_key($featureKey)
            : strtolower(trim($featureKey));
    }

    public function getFeatureMapByPlanId(int $planId): array
    {
        if ($planId <= 0) {
            return [];
        }

        $rows = $this->select('feature_key, is_enabled, limit_value')
            ->where('plan_id', $planId)
            ->where('deleted_at', null)
            ->findAll();

        if (empty($rows)) {
            return [];
        }

        $map = [];

        foreach ($rows as $row) {
            $key = $this->normalizeFeatureKey((string) ($row['feature_key'] ?? ''));

            if ($key === '') {
                continue;
            }

            $limitValue = $row['limit_value'] ?? null;

            if ($limitValue !== null && $limitValue !== '') {
                $map[$key] = (string) $limitValue;
            } else {
                $map[$key] = ((int) ($row['is_enabled'] ?? 0) === 1) ? '1' : '0';
            }
        }

        return $map;
    }

    public function getFeatureValue(int $planId, string $featureKey, $default = null)
    {
        $featureKey = $this->normalizeFeatureKey($featureKey);

        if ($planId <= 0 || $featureKey === '') {
            return $default;
        }

        $row = $this->where('plan_id', $planId)
            ->where('feature_key', $featureKey)
            ->where('deleted_at', null)
            ->first();

        if (! $row) {
            return $default;
        }

        $limitValue = $row['limit_value'] ?? null;

        if ($limitValue !== null && $limitValue !== '') {
            return $limitValue;
        }

        return ((int) ($row['is_enabled'] ?? 0) === 1) ? '1' : '0';
    }
}