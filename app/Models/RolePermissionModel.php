<?php

namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table            = 'role_permissions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    // ตารางนี้ไม่มี updated_at
    protected $useTimestamps = false;

    protected $allowedFields = [
        'role_id',
        'permission_id',
        'created_at',
    ];

    public function getPermissionIdsByRoleId(int $roleId): array
    {
        if ($roleId <= 0) {
            return [];
        }

        $rows = $this->builder()
            ->select('permission_id')
            ->where('role_id', $roleId)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_filter(array_map(
            static fn ($row) => (int) ($row['permission_id'] ?? 0),
            $rows
        ))));
    }

    public function getPermissionKeysByRoleId(int $roleId): array
    {
        if ($roleId <= 0) {
            return [];
        }

        $rows = $this->builder()
            ->select('permissions.permission_key')
            ->join('permissions', 'permissions.id = role_permissions.permission_id', 'inner')
            ->where('role_permissions.role_id', $roleId)
            ->where('permissions.status', 1)
            ->get()
            ->getResultArray();

        return array_values(array_unique(array_filter(array_map(
            static fn ($row) => trim((string) ($row['permission_key'] ?? '')),
            $rows
        ))));
    }

    public function replaceRolePermissions(int $roleId, array $permissionIds): bool
    {
        if ($roleId <= 0) {
            return false;
        }

        $permissionIds = array_values(array_unique(array_filter(array_map(
            static fn ($id) => (int) $id,
            $permissionIds
        ))));

        $db = \Config\Database::connect();
        $db->transStart();

        $this->builder()
            ->where('role_id', $roleId)
            ->delete();

        foreach ($permissionIds as $permissionId) {
            $this->insert([
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        $db->transComplete();

        return $db->transStatus();
    }

    public function deleteByRoleId(int $roleId): bool
    {
        if ($roleId <= 0) {
            return false;
        }

        $this->builder()
            ->where('role_id', $roleId)
            ->delete();

        return true;
    }
}