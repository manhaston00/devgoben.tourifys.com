<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SaasFeatureSeeder extends Seeder
{
    public function run()
    {
        $features = [
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
        ];

        $builder = $this->db->table('app_features');

        foreach ($features as $row) {
            $exists = $builder->where('feature_key', $row['feature_key'])->get()->getRowArray();
            if (! $exists) {
                $builder->insert($row);
            }
        }
    }
}