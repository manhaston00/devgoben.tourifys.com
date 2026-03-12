<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTenantScopeToQuickData extends Migration
{
    public function up()
    {
        $tables = ['quick_options', 'quick_notes', 'product_quick_options'];

        foreach ($tables as $table) {
            if (! $this->db->fieldExists('tenant_id', $table)) {
                $this->forge->addColumn($table, [
                    'tenant_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => 'id',
                    ],
                ]);
            }
        }

        if ($this->db->tableExists('products')) {
            $productRows = $this->db->table('products')
                ->select('id, tenant_id')
                ->where('tenant_id IS NOT NULL', null, false)
                ->get()
                ->getResultArray();

            foreach ($productRows as $row) {
                $productId = (int) ($row['id'] ?? 0);
                $tenantId  = (int) ($row['tenant_id'] ?? 0);

                if ($productId > 0 && $tenantId > 0) {
                    $this->db->table('product_quick_options')
                        ->where('product_id', $productId)
                        ->where('tenant_id', null)
                        ->update(['tenant_id' => $tenantId]);
                }
            }
        }

        if ($this->db->tableExists('categories')) {
            $categoryRows = $this->db->table('categories')
                ->select('id, tenant_id')
                ->where('tenant_id IS NOT NULL', null, false)
                ->get()
                ->getResultArray();

            foreach ($categoryRows as $row) {
                $categoryId = (int) ($row['id'] ?? 0);
                $tenantId   = (int) ($row['tenant_id'] ?? 0);

                if ($categoryId > 0 && $tenantId > 0) {
                    $this->db->table('product_quick_options')
                        ->where('category_id', $categoryId)
                        ->where('tenant_id', null)
                        ->update(['tenant_id' => $tenantId]);
                }
            }
        }
    }

    public function down()
    {
        foreach (['quick_options', 'quick_notes', 'product_quick_options'] as $table) {
            if ($this->db->fieldExists('tenant_id', $table)) {
                $this->forge->dropColumn($table, 'tenant_id');
            }
        }
    }
}