<?php

namespace App\Models;

use CodeIgniter\Model;

class DemoAccessModel extends Model
{
    protected $table            = 'demo_access';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'email',
        'phone',
        'ip',
        'start_date',
        'end_date',
        'created_at',
    ];

    public function hasActiveDemo(?string $email = null, ?string $phone = null, ?string $ip = null): bool
    {
        $builder = $this->groupStart();
        $hasCondition = false;

        if ($email !== null && trim($email) !== '') {
            $builder->orWhere('email', trim($email));
            $hasCondition = true;
        }
        if ($phone !== null && trim($phone) !== '') {
            $builder->orWhere('phone', trim($phone));
            $hasCondition = true;
        }
        if ($ip !== null && trim($ip) !== '') {
            $builder->orWhere('ip', trim($ip));
            $hasCondition = true;
        }

        $builder->groupEnd();

        if (! $hasCondition) {
            return false;
        }

        $row = $builder->groupStart()
                ->where('end_date IS NULL', null, false)
                ->orWhere('end_date >=', date('Y-m-d'))
            ->groupEnd()
            ->first();

        return $row !== null;
    }
}
