<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SaasCoreSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        /*
        |--------------------------------------------------------------------------
        | app_settings
        |--------------------------------------------------------------------------
        */
        $settingsTable = 'app_settings';
        if ($db->tableExists($settingsTable)) {
            $settings = [
                [
                    'setting_key'   => 'app_name',
                    'setting_value' => 'GoBen POS',
                    'setting_group' => 'general',
                ],
                [
                    'setting_key'   => 'default_locale',
                    'setting_value' => 'th',
                    'setting_group' => 'general',
                ],
                [
                    'setting_key'   => 'company_name',
                    'setting_value' => 'GoBen Demo',
                    'setting_group' => 'company',
                ],
                [
                    'setting_key'   => 'currency',
                    'setting_value' => 'THB',
                    'setting_group' => 'general',
                ],
                [
                    'setting_key'   => 'demo_enabled',
                    'setting_value' => '1',
                    'setting_group' => 'demo',
                ],
                [
                    'setting_key'   => 'auto_expire_license',
                    'setting_value' => '1',
                    'setting_group' => 'automation',
                ],
            ];

            foreach ($settings as $row) {
                $exists = $db->table($settingsTable)
                    ->where('setting_key', $row['setting_key'])
                    ->countAllResults();

                if (!$exists) {
                    $db->table($settingsTable)->insert($row);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | subscription_plans
        |--------------------------------------------------------------------------
        */
        $plansTable = 'subscription_plans';
        if ($db->tableExists($plansTable)) {
            $plans = [
                [
                    'plan_code'      => 'DAILY',
                    'plan_name'      => 'รายวัน',
                    'plan_type'      => 'daily',
                    'price'          => 99.00,
                    'duration_days'  => 1,
                    'is_lifetime'    => 0,
                    'status'         => 1,
                ],
                [
                    'plan_code'      => 'MONTHLY',
                    'plan_name'      => 'รายเดือน',
                    'plan_type'      => 'monthly',
                    'price'          => 1990.00,
                    'duration_days'  => 30,
                    'is_lifetime'    => 0,
                    'status'         => 1,
                ],
                [
                    'plan_code'      => 'LIFETIME',
                    'plan_name'      => 'ตลอดอายุ',
                    'plan_type'      => 'lifetime',
                    'price'          => 19990.00,
                    'duration_days'  => null,
                    'is_lifetime'    => 1,
                    'status'         => 1,
                ],
            ];

            foreach ($plans as $row) {
                $exists = $db->table($plansTable)
                    ->where('plan_code', $row['plan_code'])
                    ->countAllResults();

                if (!$exists) {
                    $db->table($plansTable)->insert($row);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | promotions
        |--------------------------------------------------------------------------
        */
        $promotionsTable = 'promotions';
        if ($db->tableExists($promotionsTable)) {
            $promotions = [
                [
                    'code'              => 'DEMO100',
                    'name'              => 'ส่วนลดเดโม 100 บาท',
                    'discount_type'     => 'fixed',
                    'discount_value'    => 100,
                    'min_order_amount'  => 500,
                    'usage_limit'       => 100,
                    'used_count'        => 0,
                    'start_date'        => date('Y-m-d'),
                    'end_date'          => date('Y-m-d', strtotime('+90 days')),
                    'status'            => 1,
                ],
                [
                    'code'              => 'WELCOME10',
                    'name'              => 'ส่วนลด 10%',
                    'discount_type'     => 'percent',
                    'discount_value'    => 10,
                    'min_order_amount'  => 300,
                    'usage_limit'       => 500,
                    'used_count'        => 0,
                    'start_date'        => date('Y-m-d'),
                    'end_date'          => date('Y-m-d', strtotime('+180 days')),
                    'status'            => 1,
                ],
            ];

            foreach ($promotions as $row) {
                $exists = $db->table($promotionsTable)
                    ->where('code', $row['code'])
                    ->countAllResults();

                if (!$exists) {
                    $db->table($promotionsTable)->insert($row);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | licenses (optional demo license)
        |--------------------------------------------------------------------------
        */
        $licensesTable = 'licenses';
        if ($db->tableExists($licensesTable) && $db->tableExists('subscription_plans')) {
            $dailyPlan = $db->table('subscription_plans')
                ->where('plan_code', 'DAILY')
                ->get()
                ->getRowArray();

            if ($dailyPlan) {
                $licenseKey = 'DEMO-' . strtoupper(substr(md5('goben-demo-license'), 0, 12));

                $exists = $db->table($licensesTable)
                    ->where('license_key', $licenseKey)
                    ->countAllResults();

                if (!$exists) {
                    $db->table($licensesTable)->insert([
                        'license_key'   => $licenseKey,
                        'customer_name' => 'Demo Customer',
                        'customer_phone'=> '0000000000',
                        'plan_id'       => $dailyPlan['id'],
                        'start_date'    => date('Y-m-d'),
                        'end_date'      => date('Y-m-d', strtotime('+1 day')),
                        'status'        => 'active',
                        'note'          => 'Seed demo license',
                    ]);
                }
            }
        }

        echo "SaasCoreSeeder completed.\n";
    }
}