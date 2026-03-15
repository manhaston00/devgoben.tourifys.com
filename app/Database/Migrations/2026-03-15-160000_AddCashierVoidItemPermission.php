<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCashierVoidItemPermission extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $permission = $this->db->table('permissions')->where('permission_key', 'cashier.void_item')->get()->getRowArray();
        if (! $permission) {
            $this->db->table('permissions')->insert([
                'module_key' => 'cashier',
                'action_key' => 'void_item',
                'permission_key' => 'cashier.void_item',
                'label' => 'Void Item',
                'label_th' => 'Void Item',
                'label_en' => 'Void Item',
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $permission = $this->db->table('permissions')->where('permission_key', 'cashier.void_item')->get()->getRowArray();
        }
        if (! $permission) {
            return;
        }
        $permissionId = (int) ($permission['id'] ?? 0);
        foreach ($this->db->table('roles')->whereIn('role_name', ['admin', 'manager'])->get()->getResultArray() as $role) {
            $roleId = (int) ($role['id'] ?? 0);
            if ($roleId <= 0) { continue; }
            $exists = $this->db->table('role_permissions')->where('role_id', $roleId)->where('permission_id', $permissionId)->countAllResults();
            if (! $exists) {
                $this->db->table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
    }

    public function down()
    {
        $permission = $this->db->table('permissions')->where('permission_key', 'cashier.void_item')->get()->getRowArray();
        if (! $permission) { return; }
        $permissionId = (int) ($permission['id'] ?? 0);
        if ($permissionId > 0) {
            $this->db->table('role_permissions')->where('permission_id', $permissionId)->delete();
        }
        $this->db->table('permissions')->where('permission_key', 'cashier.void_item')->delete();
    }
}
