<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionPlanFeatureModel extends Model
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

        return normalize_feature_key($featureKey);
    }

    public function getPlanFeatures(int $planId): array
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

        $features = [];

        foreach ($rows as $row) {
            $featureKey = $this->normalizeFeatureKey((string) ($row['feature_key'] ?? ''));

            if ($featureKey === '') {
                continue;
            }

            $features[$featureKey] = [
                'enabled' => (int) ($row['is_enabled'] ?? 0),
                'limit'   => $row['limit_value'] ?? null,
            ];
        }

        return $features;
    }

    public function getOne(int $planId, string $featureKey): ?array
    {
        $featureKey = $this->normalizeFeatureKey($featureKey);

        if ($planId <= 0 || $featureKey === '') {
            return null;
        }

        $row = $this->where('plan_id', $planId)
            ->where('feature_key', $featureKey)
            ->where('deleted_at', null)
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'plan_id'     => (int) ($row['plan_id'] ?? 0),
            'feature_key' => $featureKey,
            'enabled'     => (int) ($row['is_enabled'] ?? 0),
            'limit'       => $row['limit_value'] ?? null,
            'raw'         => $row,
        ];
    }

    public function syncPlanFeatures(int $planId, array $rows): void
    {
        if ($planId <= 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        // normalize + dedupe ก่อน
        $normalizedRows = [];

        foreach ($rows as $featureKey => $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalizedFeatureKey = $this->normalizeFeatureKey(
                (string) ($row['feature_key'] ?? $featureKey)
            );

            if ($normalizedFeatureKey === '') {
                continue;
            }

            $isEnabled  = (! empty($row['enabled']) || ! empty($row['is_enabled'])) ? 1 : 0;
            $limitValue = $row['limit'] ?? $row['limit_value'] ?? null;

            if ($limitValue !== null) {
                $limitValue = trim((string) $limitValue);
                if ($limitValue === '') {
                    $limitValue = null;
                }
            }

            if (! isset($normalizedRows[$normalizedFeatureKey])) {
                $normalizedRows[$normalizedFeatureKey] = [
                    'plan_id'     => $planId,
                    'feature_key' => $normalizedFeatureKey,
                    'is_enabled'  => $isEnabled,
                    'limit_value' => $limitValue,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                    'deleted_at'  => null,
                ];
            } else {
                // merge กันเผื่อ alias หลายตัว normalize มา key เดียวกัน
                $normalizedRows[$normalizedFeatureKey]['is_enabled'] = max(
                    (int) $normalizedRows[$normalizedFeatureKey]['is_enabled'],
                    $isEnabled
                );

                if (
                    ($normalizedRows[$normalizedFeatureKey]['limit_value'] === null ||
                     $normalizedRows[$normalizedFeatureKey]['limit_value'] === '') &&
                    $limitValue !== null &&
                    $limitValue !== ''
                ) {
                    $normalizedRows[$normalizedFeatureKey]['limit_value'] = $limitValue;
                }

                $normalizedRows[$normalizedFeatureKey]['updated_at'] = $now;
            }
        }

        // สำคัญ: ลบจริง ไม่ใช่ soft delete
        $this->db->table($this->table)
            ->where('plan_id', $planId)
            ->delete();

        foreach ($normalizedRows as $insertRow) {
            $this->db->table($this->table)->insert($insertRow);
        }
    }

    public function replacePlanFeatures(int $planId, array $rows): void
    {
        $this->syncPlanFeatures($planId, $rows);
    }
}