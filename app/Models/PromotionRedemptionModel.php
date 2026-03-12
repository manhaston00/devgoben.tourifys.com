<?php

namespace App\Models;

class PromotionRedemptionModel extends TenantScopedModel
{
    protected $table            = 'promotion_redemptions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'tenant_id',
        'promotion_id',
        'order_id',
        'customer_phone',
        'discount_amount',
        'redeemed_at',
    ];

    protected $beforeInsert = ['beforeInsertTenant'];
    protected $beforeUpdate = ['beforeUpdateTenant'];

    public function getByOrder(int $orderId): array
    {
        return $this->scoped()
            ->where('order_id', $orderId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function getByPromotion(int $promotionId): array
    {
        return $this->scoped()
            ->where('promotion_id', $promotionId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}