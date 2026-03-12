<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Roles
        $roles = [
            ['role_name' => 'admin', 'description' => 'System Administrator', 'status' => 1],
            ['role_name' => 'manager', 'description' => 'Store Manager', 'status' => 1],
            ['role_name' => 'cashier', 'description' => 'Cashier', 'status' => 1],
            ['role_name' => 'staff', 'description' => 'Staff / Waiter', 'status' => 1],
            ['role_name' => 'kitchen', 'description' => 'Kitchen', 'status' => 1],
        ];

        foreach ($roles as $role) {
            $exists = $db->table('roles')->where('role_name', $role['role_name'])->get()->getRowArray();
            if (!$exists) {
                $db->table('roles')->insert($role);
            }
        }

        // Admin user
        $adminRole = $db->table('roles')->where('role_name', 'admin')->get()->getRowArray();

        if ($adminRole) {
            $admin = $db->table('users')->where('username', 'admin')->get()->getRowArray();

            if (!$admin) {
                $db->table('users')->insert([
                    'role_id'       => $adminRole['id'],
                    'full_name'     => 'Administrator',
                    'username'      => 'admin',
                    'password_hash' => password_hash('123456', PASSWORD_DEFAULT),
                    'pin_code'      => '9999',
                    'phone'         => '0000000000',
                    'email'         => 'admin@example.com',
                    'status'        => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Categories
        $defaultCategories = [
            ['category_name' => 'ต้ม', 'sort_order' => 1, 'status' => 1],
            ['category_name' => 'ผัด', 'sort_order' => 2, 'status' => 1],
            ['category_name' => 'ทอด', 'sort_order' => 3, 'status' => 1],
            ['category_name' => 'ยำ', 'sort_order' => 4, 'status' => 1],
            ['category_name' => 'เครื่องดื่ม', 'sort_order' => 5, 'status' => 1],
        ];

        foreach ($defaultCategories as $row) {
            $exists = $db->table('categories')->where('category_name', $row['category_name'])->get()->getRowArray();
            if (!$exists) {
                $row['created_at'] = date('Y-m-d H:i:s');
                $row['updated_at'] = date('Y-m-d H:i:s');
                $db->table('categories')->insert($row);
            }
        }

        // Zones
        $defaultZones = [
            ['zone_name' => 'หน้า店', 'sort_order' => 1, 'status' => 1],
            ['zone_name' => 'ห้องแอร์', 'sort_order' => 2, 'status' => 1],
            ['zone_name' => 'ด้านนอก', 'sort_order' => 3, 'status' => 1],
        ];

        foreach ($defaultZones as $row) {
            $exists = $db->table('zones')->where('zone_name', $row['zone_name'])->get()->getRowArray();
            if (!$exists) {
                $row['created_at'] = date('Y-m-d H:i:s');
                $row['updated_at'] = date('Y-m-d H:i:s');
                $db->table('zones')->insert($row);
            }
        }

        // Kitchen stations
        $defaultStations = [
            ['station_name' => 'ครัวร้อน', 'color_tag' => '#dc3545', 'status' => 1],
            ['station_name' => 'บาร์น้ำ', 'color_tag' => '#0d6efd', 'status' => 1],
        ];

        foreach ($defaultStations as $row) {
            $exists = $db->table('kitchen_stations')->where('station_name', $row['station_name'])->get()->getRowArray();
            if (!$exists) {
                $row['created_at'] = date('Y-m-d H:i:s');
                $row['updated_at'] = date('Y-m-d H:i:s');
                $db->table('kitchen_stations')->insert($row);
            }
        }

        echo "InitialSeeder completed.\n";
    }
}