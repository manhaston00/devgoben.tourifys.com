<?php

namespace App\Models;

use CodeIgniter\Model;

class AppFeatureModel extends Model
{
    protected $table            = 'app_features';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'feature_key',
        'feature_name',
        'description',
        'module_name',
        'is_active',
    ];
}