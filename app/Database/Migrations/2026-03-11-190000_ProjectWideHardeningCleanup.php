<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ProjectWideHardeningCleanup extends Migration
{
    public function up()
    {
        $this->normalizeAppFeatures();
        $this->normalizeSubscriptionPlanFeatures();
        $this->cleanupLegacyPlanFeaturesTable();
    }

    public function down()
    {
        // intentionally no-op
    }

    protected function normalizeAppFeatures(): void
    {
        if (! $this->db->tableExists('app_features')) {
            return;
        }

        $builder = $this->db->table('app_features');
        $now     = date('Y-m-d H:i:s');

        $canonicalRows = [
            [
                'feature_key'  => 'pos.access',
                'feature_name' => 'POS Access',
                'description'  => 'เข้าใช้งานหน้าขาย POS',
                'module_name'  => 'pos',
                'is_active'    => 1,
            ],
            [
                'feature_key'  => 'pos.sell',
                'feature_name' => 'POS Sell',
                'description'  => 'สร้างออเดอร์ / เปิดบิลขาย',
                'module_name'  => 'pos',
                'is_active'    => 1,
            ],
            [
                'feature_key'  => 'reservations.manage',
                'feature_name' => 'Reservation Management',
                'description'  => 'จัดการการจองโต๊ะ',
                'module_name'  => 'reservations',
                'is_active'    => 1,
            ],
            [
                'feature_key'  => 'tables.manage',
                'feature_name' => 'Table Management',
                'description'  => 'จัดการโต๊ะ',
                'module_name'  => 'tables',
                'is_active'    => 1,
            ],
            [
                'feature_key'  => 'zones.manage',
                'feature_name' => 'Zone Management',
                'description'  => 'จัดการโซน',
                'module_name'  => 'zones',
                'is_active'    => 1,
            ],
            [
                'feature_key'  => 'reports.basic',
                'feature_name' => 'Basic Reports',
                'description'  => 'รายงานพื้นฐาน',
                'module_name'  => 'reports',
                'is_active'    => 1,
            ],
            [
                'feature_key'  => 'multi.branch',
                'feature_name' => 'Multi Branch',
                'description'  => 'ใช้งานหลายสาขา',
                'module_name'  => 'branches',
                'is_active'    => 1,
            ],
            [
                'feature_key'  => 'branches_limit',
                'feature_name' => 'Branches Limit',
                'description'  => 'จำนวนสาขาสูงสุด',
                'module_name'  => 'branches',
                'is_active'    => 1,
            ],
            [
                'feature_key'  => 'users_limit',
                'feature_name' => 'Users Limit',
                'description'  => 'จำนวนผู้ใช้สูงสุด',
                'module_name'  => 'users',
                'is_active'    => 1,
            ],
            [
                'feature_key'  => 'products_limit',
                'feature_name' => 'Products Limit',
                'description'  => 'จำนวนสินค้าสูงสุด',
                'module_name'  => 'products',
                'is_active'    => 1,
            ],
        ];

        foreach ($canonicalRows as $row) {
            $exists = $builder->where('feature_key', $row['feature_key'])->get()->getRowArray();

            if ($exists) {
                $builder->where('id', (int) $exists['id'])->update([
                    'feature_name' => $row['feature_name'],
                    'description'  => $row['description'],
                    'module_name'  => $row['module_name'],
                    'is_active'    => $row['is_active'],
                    'updated_at'   => $now,
                ]);
            } else {
                $builder->insert([
                    'feature_key'  => $row['feature_key'],
                    'feature_name' => $row['feature_name'],
                    'description'  => $row['description'],
                    'module_name'  => $row['module_name'],
                    'is_active'    => $row['is_active'],
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        }

        $legacyAliases = [
            'pos'            => 'pos.access',
            'pos.view'       => 'pos.access',
            'kitchen'        => 'pos.access',
            'kitchen.view'   => 'pos.access',
            'sell'           => 'pos.sell',
            'reservation'    => 'reservations.manage',
            'reservations'   => 'reservations.manage',
            'table'          => 'tables.manage',
            'tables'         => 'tables.manage',
            'zone'           => 'zones.manage',
            'zones'          => 'zones.manage',
            'report'         => 'reports.basic',
            'reports'        => 'reports.basic',
            'multi_branch'   => 'multi.branch',
            'branch.multi'   => 'multi.branch',
            'branches.limit' => 'branches_limit',
            'branch_limit'   => 'branches_limit',
            'users.limit'    => 'users_limit',
            'user_limit'     => 'users_limit',
            'products.limit' => 'products_limit',
            'product_limit'  => 'products_limit',
        ];

        foreach ($legacyAliases as $oldKey => $newKey) {
            $oldRows = $this->db->table('app_features')
                ->where('feature_key', $oldKey)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            if (empty($oldRows)) {
                continue;
            }

            $canonical = $this->db->table('app_features')
                ->where('feature_key', $newKey)
                ->orderBy('id', 'ASC')
                ->get()
                ->getRowArray();

            if ($canonical) {
                $oldIds = array_map(static fn($r) => (int) $r['id'], $oldRows);
                $this->db->table('app_features')->whereIn('id', $oldIds)->delete();
                continue;
            }

            $keep = array_shift($oldRows);

            $this->db->table('app_features')
                ->where('id', (int) $keep['id'])
                ->update([
                    'feature_key' => $newKey,
                    'updated_at'  => $now,
                ]);

            if (! empty($oldRows)) {
                $deleteIds = array_map(static fn($r) => (int) $r['id'], $oldRows);
                $this->db->table('app_features')->whereIn('id', $deleteIds)->delete();
            }
        }

        $this->deleteDuplicateFeatureRows('app_features');
    }

    protected function normalizeSubscriptionPlanFeatures(): void
    {
        if (! $this->db->tableExists('subscription_plan_features')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $legacyAliases = [
            'pos'            => 'pos.access',
            'pos.view'       => 'pos.access',
            'kitchen'        => 'pos.access',
            'kitchen.view'   => 'pos.access',
            'sell'           => 'pos.sell',
            'reservation'    => 'reservations.manage',
            'reservations'   => 'reservations.manage',
            'table'          => 'tables.manage',
            'tables'         => 'tables.manage',
            'zone'           => 'zones.manage',
            'zones'          => 'zones.manage',
            'report'         => 'reports.basic',
            'reports'        => 'reports.basic',
            'multi_branch'   => 'multi.branch',
            'branch.multi'   => 'multi.branch',
            'branches.limit' => 'branches_limit',
            'branch_limit'   => 'branches_limit',
            'users.limit'    => 'users_limit',
            'user_limit'     => 'users_limit',
            'products.limit' => 'products_limit',
            'product_limit'  => 'products_limit',
        ];

        foreach ($legacyAliases as $oldKey => $newKey) {
            $oldRows = $this->db->table('subscription_plan_features')
                ->where('feature_key', $oldKey)
                ->orderBy('plan_id', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            if (empty($oldRows)) {
                continue;
            }

            foreach ($oldRows as $oldRow) {
                $planId = (int) $oldRow['plan_id'];
                $oldId  = (int) $oldRow['id'];

                $canonical = $this->db->table('subscription_plan_features')
                    ->where('plan_id', $planId)
                    ->where('feature_key', $newKey)
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getRowArray();

                if ($canonical) {
                    $canonicalId = (int) $canonical['id'];

                    $mergedEnabled = max(
                        (int) ($canonical['is_enabled'] ?? 0),
                        (int) ($oldRow['is_enabled'] ?? 0)
                    );

                    $canonicalLimit = $canonical['limit_value'] ?? null;
                    $oldLimit       = $oldRow['limit_value'] ?? null;

                    $mergedLimit = $canonicalLimit;
                    if (($mergedLimit === null || $mergedLimit === '') && $oldLimit !== null && $oldLimit !== '') {
                        $mergedLimit = $oldLimit;
                    }

                    $this->db->table('subscription_plan_features')
                        ->where('id', $canonicalId)
                        ->update([
                            'is_enabled'  => $mergedEnabled,
                            'limit_value' => $mergedLimit,
                            'updated_at'  => $now,
                            'deleted_at'  => null,
                        ]);

                    $this->db->table('subscription_plan_features')
                        ->where('id', $oldId)
                        ->delete();
                } else {
                    $this->db->table('subscription_plan_features')
                        ->where('id', $oldId)
                        ->update([
                            'feature_key' => $newKey,
                            'updated_at'  => $now,
                        ]);
                }
            }
        }

        $this->deleteDuplicatePlanFeatureRows();
    }

    protected function cleanupLegacyPlanFeaturesTable(): void
    {
        if (! $this->db->tableExists('plan_features')) {
            return;
        }

        $this->db->table('plan_features')
            ->whereIn('feature_key', [
                'pos',
                'kitchen',
                'pos.view',
                'kitchen.view',
                'branches.limit',
                'users.limit',
                'products.limit',
            ])
            ->delete();
    }

    protected function deleteDuplicateFeatureRows(string $table): void
    {
        $rows = $this->db->table($table)
            ->select('feature_key, MIN(id) AS keep_id, COUNT(*) AS total_rows')
            ->groupBy('feature_key')
            ->having('COUNT(*) >', 1)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $keepId = (int) $row['keep_id'];

            $this->db->table($table)
                ->where('feature_key', (string) $row['feature_key'])
                ->where('id !=', $keepId)
                ->delete();
        }
    }

    protected function deleteDuplicatePlanFeatureRows(): void
    {
        $rows = $this->db->table('subscription_plan_features')
            ->select('plan_id, feature_key, MIN(id) AS keep_id, COUNT(*) AS total_rows')
            ->groupBy('plan_id, feature_key')
            ->having('COUNT(*) >', 1)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $planId     = (int) $row['plan_id'];
            $featureKey = (string) $row['feature_key'];
            $keepId     = (int) $row['keep_id'];

            $duplicates = $this->db->table('subscription_plan_features')
                ->where('plan_id', $planId)
                ->where('feature_key', $featureKey)
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            if (count($duplicates) <= 1) {
                continue;
            }

            $mergedEnabled = 0;
            $mergedLimit   = null;

            foreach ($duplicates as $dup) {
                $mergedEnabled = max($mergedEnabled, (int) ($dup['is_enabled'] ?? 0));

                $limitValue = $dup['limit_value'] ?? null;
                if (($mergedLimit === null || $mergedLimit === '') && $limitValue !== null && $limitValue !== '') {
                    $mergedLimit = $limitValue;
                }
            }

            $this->db->table('subscription_plan_features')
                ->where('id', $keepId)
                ->update([
                    'is_enabled'  => $mergedEnabled,
                    'limit_value' => $mergedLimit,
                    'updated_at'  => date('Y-m-d H:i:s'),
                    'deleted_at'  => null,
                ]);

            $this->db->table('subscription_plan_features')
                ->where('plan_id', $planId)
                ->where('feature_key', $featureKey)
                ->where('id !=', $keepId)
                ->delete();
        }
    }
}