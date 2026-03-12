<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuItemModel extends Model
{
    protected $table            = 'menu_items';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'category_id',
        'code',
        'name_th',
        'name_en',
        'price',
        'image',
        'kitchen_station',
        'is_recommended',
        'status',
    ];
}