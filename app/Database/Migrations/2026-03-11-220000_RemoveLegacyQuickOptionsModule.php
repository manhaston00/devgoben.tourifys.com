<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveLegacyQuickOptionsModule extends Migration
{
    public function up()
    {
        // 1) ถ้ายังมี table quick_options อยู่ ให้ย้ายข้อมูลเข้า product_quick_options ก่อน
        if ($this->db->tableExists('quick_options') && $this->db->tableExists('product_quick_options')) {
            $columns = $this->db->getFieldNames('quick_options');

            $hasOptionNameTh = in_array('option_name_th', $columns, true);
            $hasOptionNameEn = in_array('option_name_en', $columns, true);
            $hasPrice        = in_array('price', $columns, true);
            $hasSortOrder    = in_array('sort_order', $columns, true);
            $hasStatus       = in_array('status', $columns, true);
            $hasCreatedAt    = in_array('created_at', $columns, true);
            $hasUpdatedAt    = in_array('updated_at', $columns, true);
            $hasDeletedAt    = in_array('deleted_at', $columns, true);

            $sql = "
                INSERT INTO product_quick_options
                (
                    tenant_id,
                    product_id,
                    category_id,
                    option_name,
                    option_name_th,
                    option_name_en,
                    price_adjust,
                    sort_order,
                    status,
                    created_at,
                    updated_at,
                    deleted_at
                )
                SELECT
                    qo.tenant_id,
                    NULL,
                    NULL,
                    qo.option_name,
                    " . ($hasOptionNameTh ? "qo.option_name_th" : "qo.option_name") . ",
                    " . ($hasOptionNameEn ? "qo.option_name_en" : "qo.option_name") . ",
                    " . ($hasPrice ? "qo.price" : "0") . ",
                    " . ($hasSortOrder ? "qo.sort_order" : "0") . ",
                    " . ($hasStatus ? "qo.status" : "1") . ",
                    " . ($hasCreatedAt ? "qo.created_at" : "NULL") . ",
                    " . ($hasUpdatedAt ? "qo.updated_at" : "NULL") . ",
                    " . ($hasDeletedAt ? "qo.deleted_at" : "NULL") . "
                FROM quick_options qo
                LEFT JOIN product_quick_options pqo
                    ON pqo.tenant_id = qo.tenant_id
                   AND IFNULL(pqo.product_id, 0) = 0
                   AND IFNULL(pqo.category_id, 0) = 0
                   AND IFNULL(pqo.option_name, '') = IFNULL(qo.option_name, '')
                WHERE pqo.id IS NULL
            ";

            $this->db->query($sql);
        }

        // 2) ลบ role_permissions ที่อ้าง permission quick_options.*
        if ($this->db->tableExists('role_permissions') && $this->db->tableExists('permissions')) {
            $this->db->query("
                DELETE rp
                FROM role_permissions rp
                INNER JOIN permissions p ON p.id = rp.permission_id
                WHERE p.permission_key IN (
                    'quick_options.view',
                    'quick_options.create',
                    'quick_options.edit',
                    'quick_options.delete'
                )
            ");
        }

        // 3) ลบ permission quick_options.*
        if ($this->db->tableExists('permissions')) {
            $this->db->query("
                DELETE FROM permissions
                WHERE permission_key IN (
                    'quick_options.view',
                    'quick_options.create',
                    'quick_options.edit',
                    'quick_options.delete'
                )
            ");
        }

        // 4) ลบ table quick_options ถ้ายังมีอยู่
        if ($this->db->tableExists('quick_options')) {
            $this->forge->dropTable('quick_options', true);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('permissions')) {
            return;
        }

        $rows = [
            [
                'module_key'     => 'quick_options',
                'action_key'     => 'view',
                'permission_key' => 'quick_options.view',
                'label'          => 'ดูรายละเอียดอาหาร',
                'label_th'       => 'ดูรายละเอียดอาหาร',
                'label_en'       => 'View Quick Options',
                'status'         => 1,
            ],
            [
                'module_key'     => 'quick_options',
                'action_key'     => 'create',
                'permission_key' => 'quick_options.create',
                'label'          => 'เพิ่มรายละเอียดอาหาร',
                'label_th'       => 'เพิ่มรายละเอียดอาหาร',
                'label_en'       => 'Create Quick Options',
                'status'         => 1,
            ],
            [
                'module_key'     => 'quick_options',
                'action_key'     => 'edit',
                'permission_key' => 'quick_options.edit',
                'label'          => 'แก้ไขรายละเอียดอาหาร',
                'label_th'       => 'แก้ไขรายละเอียดอาหาร',
                'label_en'       => 'Edit Quick Options',
                'status'         => 1,
            ],
            [
                'module_key'     => 'quick_options',
                'action_key'     => 'delete',
                'permission_key' => 'quick_options.delete',
                'label'          => 'ลบรายละเอียดอาหาร',
                'label_th'       => 'ลบรายละเอียดอาหาร',
                'label_en'       => 'Delete Quick Options',
                'status'         => 1,
            ],
        ];

        foreach ($rows as $row) {
            $exists = $this->db->table('permissions')
                ->where('permission_key', $row['permission_key'])
                ->countAllResults();

            if (! $exists) {
                $this->db->table('permissions')->insert($row);
            }
        }
    }
}