<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('branches')) {
            echo "branches table not found.\n";
            return;
        }

        $rows = [
            [
                'branch_code'     => 'HO',
                'branch_name'     => 'สำนักงานใหญ่',
                'branch_name_en'  => 'Head Office',
                'phone'           => '',
                'email'           => '',
                'address'         => '',
                'tax_id'          => '',
                'status'          => 1,
                'is_head_office'  => 1,
                'sort_order'      => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($rows as $row) {
            $exists = $db->table('branches')
                ->where('branch_code', $row['branch_code'])
                ->countAllResults();

            if (! $exists) {
                $db->table('branches')->insert($row);
            }
        }

        $headOffice = $db->table('branches')
            ->where('branch_code', 'HO')
            ->get()
            ->getRowArray();

        if ($headOffice) {
            if ($db->tableExists('users') && $db->fieldExists('branch_id', 'users')) {
                $db->table('users')
                    ->where('branch_id IS NULL', null, false)
                    ->update(['branch_id' => $headOffice['id']]);
            }

            if ($db->tableExists('zones') && $db->fieldExists('branch_id', 'zones')) {
                $db->table('zones')
                    ->where('branch_id IS NULL', null, false)
                    ->update(['branch_id' => $headOffice['id']]);
            }

            if ($db->tableExists('tables') && $db->fieldExists('branch_id', 'tables')) {
                $db->table('tables')
                    ->where('branch_id IS NULL', null, false)
                    ->update(['branch_id' => $headOffice['id']]);
            }

            if ($db->tableExists('orders') && $db->fieldExists('branch_id', 'orders')) {
                $db->table('orders')
                    ->where('branch_id IS NULL', null, false)
                    ->update(['branch_id' => $headOffice['id']]);
            }

            if ($db->tableExists('reservations') && $db->fieldExists('branch_id', 'reservations')) {
                $db->table('reservations')
                    ->where('branch_id IS NULL', null, false)
                    ->update(['branch_id' => $headOffice['id']]);
            }
        }

        echo "BranchSeeder completed.\n";
    }
}