<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductPriceModel extends Model
{
    protected $table         = 'product_prices';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'product_id',
        'price_name',
        'price_value',
        'is_default',
        'sort_order',
        'status',
    ];
}