<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table         = 'permissions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'module_key',
        'action_key',
        'permission_key',
        'label',
        'label_th',
        'label_en',
        'status',
    ];

    public function getAssignablePermissions(): array
    {
        $builder = $this->where('status', 1);

        // ฝั่ง super admin เห็นได้ทั้งหมด
        if (function_exists('is_super_admin') && is_super_admin()) {
            return $builder
                ->orderBy('module_key', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll();
        }

        // ฝั่ง tenant ซ่อน permission ของ platform
        return $builder
            ->whereNotIn('module_key', [
                'super_admin',
                'tenants',
                'plans',
                'subscriptions',
                'subscription_logs',
            ])
            ->orderBy('module_key', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}