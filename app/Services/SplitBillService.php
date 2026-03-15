<?php

namespace App\Services;

use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\OrderSplitSessionItemModel;
use App\Models\OrderSplitSessionModel;

class SplitBillService
{
    protected $db;
    protected $orderModel;
    protected $orderItemModel;
    protected $sessionModel;
    protected $sessionItemModel;

    public function __construct()
    {
        $this->db               = \Config\Database::connect();
        $this->orderModel       = new OrderModel();
        $this->orderItemModel   = new OrderItemModel();
        $this->sessionModel     = new OrderSplitSessionModel();
        $this->sessionItemModel = new OrderSplitSessionItemModel();
    }


    protected function normalizeOrderItemStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        $map = [
            ''           => 'pending',
            'new'        => 'pending',
            'open'       => 'pending',
            'pending'    => 'pending',
            'sent'       => 'sent',
            'submitted'  => 'sent',
            'sent_to_kitchen' => 'sent',
            'preparing'  => 'preparing',
            'cooking'    => 'preparing',
            'ready'      => 'ready',
            'served'     => 'served',
            'cancel'     => 'cancelled',
            'cancelled'  => 'cancelled',
            'canceled'   => 'cancelled',
        ];

        return $map[$status] ?? $status;
    }

    protected function isNonBillableItem(array $item): bool
    {
        $status = $this->normalizeOrderItemStatus($item['status'] ?? 'pending');
        if (in_array($status, ['pending', 'cancelled'], true)) {
            return true;
        }

        $requestStatus = strtolower(trim((string) ($item['cancel_request_status'] ?? '')));
        if (in_array($requestStatus, ['approved', 'accepted'], true)) {
            return true;
        }

        if (! empty($item['cancelled_at'])) {
            return true;
        }

        return false;
    }

    public function preview(int $orderId, array $items, int $tenantId, int $branchId): array
    {
        $order = $this->findScopedOrder($orderId, $tenantId, $branchId);
        if (! $order) {
            throw new \RuntimeException(lang('app.order_not_found'));
        }

        $this->assertOrderSplittable($order);

        $selected = $this->normalizeAndValidateItems($order, $items, $tenantId, $branchId);
        if (empty($selected)) {
            throw new \RuntimeException(lang('app.split_bill_no_items_selected'));
        }

        $sourceSubtotal = $this->sumBillableSubtotal($orderId, $tenantId, $branchId);
        $childSubtotal  = 0.0;
        $childItems     = [];

        foreach ($selected as $row) {
            $lineTotal = $this->isNonBillableItem($row) ? 0.0 : round(((float) ($row['price'] ?? 0)) * ((int) ($row['split_qty'] ?? 0)), 2);
            $childSubtotal += $lineTotal;
            $childItems[] = [
                'order_item_id'     => (int) ($row['id'] ?? 0),
                'product_id'        => isset($row['product_id']) ? (int) ($row['product_id'] ?? 0) : null,
                'product_name'      => (string) ($row['product_name'] ?? ''),
                'item_detail'       => (string) ($row['item_detail'] ?? ''),
                'option_summary'    => (string) ($row['option_summary'] ?? ''),
                'note'              => (string) ($row['note'] ?? ''),
                'status'            => (string) ($row['status'] ?? 'pending'),
                'source_qty'        => (int) ($row['qty'] ?? 0),
                'split_qty'         => (int) ($row['split_qty'] ?? 0),
                'remaining_qty'     => max(0, (int) ($row['qty'] ?? 0) - (int) ($row['split_qty'] ?? 0)),
                'unit_price'        => round((float) ($row['price'] ?? 0), 2),
                'line_total'        => $lineTotal,
            ];
        }

        $sourceSubtotal = round($sourceSubtotal, 2);
        $childSubtotal  = round($childSubtotal, 2);
        $parentSubtotal = round(max(0, $sourceSubtotal - $childSubtotal), 2);

        [$childDiscount, $childService, $childVat] = $this->allocateOrderAmounts($order, $sourceSubtotal, $childSubtotal);
        $parentDiscount = round((float) ($order['discount_amount'] ?? 0) - $childDiscount, 2);
        $parentService  = round((float) ($order['service_charge'] ?? 0) - $childService, 2);
        $parentVat      = round((float) ($order['vat_amount'] ?? 0) - $childVat, 2);

        return [
            'root_order_id'    => $this->resolveRootOrderId($order),
            'source_order_id'  => (int) ($order['id'] ?? 0),
            'source_order_no'  => (string) ($order['order_number'] ?? ''),
            'bill_type'        => (string) ($order['bill_type'] ?? 'normal'),
            'child_items'      => $childItems,
            'preview_parent'   => [
                'subtotal'        => $parentSubtotal,
                'discount_amount' => $parentDiscount,
                'service_charge'  => $parentService,
                'vat_amount'      => $parentVat,
                'total_price'     => round(max(0, $parentSubtotal - $parentDiscount + $parentService + $parentVat), 2),
            ],
            'preview_child'    => [
                'subtotal'        => $childSubtotal,
                'discount_amount' => $childDiscount,
                'service_charge'  => $childService,
                'vat_amount'      => $childVat,
                'total_price'     => round(max(0, $childSubtotal - $childDiscount + $childService + $childVat), 2),
            ],
            'warnings'         => [],
            'rules'            => [
                'paid_order_cannot_split' => true,
                'cancelled_items_cannot_split' => true,
                'merge_group_blocked' => true,
            ],
        ];
    }

    public function confirm(int $orderId, array $items, int $actorId, int $tenantId, int $branchId, ?string $reason = null): array
    {
        $order = $this->findScopedOrder($orderId, $tenantId, $branchId);
        if (! $order) {
            throw new \RuntimeException(lang('app.order_not_found'));
        }

        $this->assertOrderSplittable($order);

        $preview = $this->preview($orderId, $items, $tenantId, $branchId);
        $selected = $this->normalizeAndValidateItems($order, $items, $tenantId, $branchId);
        $now = date('Y-m-d H:i:s');

        $this->db->transBegin();

        try {
            $lockedOrder = $this->lockOrderForUpdate($orderId, $tenantId, $branchId);
            if (! $lockedOrder) {
                throw new \RuntimeException(lang('app.order_not_found'));
            }

            $this->assertOrderSplittable($lockedOrder);

            $rootOrderId    = $this->resolveRootOrderId($lockedOrder);
            $splitGroupCode = trim((string) ($lockedOrder['split_group_code'] ?? ''));
            if ($splitGroupCode === '') {
                $splitGroupCode = $this->buildSplitGroupCode((int) ($rootOrderId ?: $orderId));
            }

            $splitNo = $this->nextSplitNo($rootOrderId > 0 ? $rootOrderId : $orderId, $tenantId, $branchId);
            $sourceOrderNo = trim((string) ($lockedOrder['order_number'] ?? ''));
            $childOrderNo = $sourceOrderNo !== '' ? ($sourceOrderNo . '-S' . $splitNo) : ('SPLIT-' . $orderId . '-' . $splitNo);

            if ((string) ($lockedOrder['bill_type'] ?? 'normal') === 'normal') {
                $this->orderModel->update($orderId, [
                    'bill_type'         => 'split_parent',
                    'split_root_order_id'=> $orderId,
                    'split_group_code'  => $splitGroupCode,
                    'is_split_locked'   => 1,
                    'updated_at'        => $now,
                ]);
                $rootOrderId = $orderId;
            } else {
                $this->orderModel->update($orderId, [
                    'is_split_locked' => 1,
                    'updated_at'      => $now,
                ]);
                if ($rootOrderId <= 0) {
                    $rootOrderId = $orderId;
                }
            }

            $sessionId = $this->sessionModel->insert([
                'tenant_id'       => $tenantId,
                'branch_id'       => $branchId,
                'session_code'    => $this->buildSessionCode($rootOrderId, $splitNo),
                'root_order_id'   => $rootOrderId,
                'source_order_id' => $orderId,
                'target_order_id' => null,
                'split_no'        => $splitNo,
                'status'          => 'draft',
                'reason'          => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
                'requested_by'    => $actorId,
                'requested_at'    => $now,
                'created_at'      => $now,
                'updated_at'      => $now,
            ], true);

            if (! $sessionId) {
                throw new \RuntimeException(lang('app.split_bill_failed'));
            }

            $childOrderId = $this->orderModel->insert([
                'tenant_id'          => $tenantId,
                'branch_id'          => $branchId,
                'table_id'           => (int) ($lockedOrder['table_id'] ?? 0) ?: null,
                'reservation_id'     => (int) ($lockedOrder['reservation_id'] ?? 0) ?: null,
                'order_number'       => $childOrderNo,
                'order_type'         => (string) ($lockedOrder['order_type'] ?? 'dine_in'),
                'status'             => (string) ($lockedOrder['status'] ?? 'open'),
                'bill_type'          => 'split_child',
                'parent_order_id'    => $orderId,
                'split_root_order_id'=> $rootOrderId,
                'split_from_order_id'=> $orderId,
                'split_no'           => $splitNo,
                'split_group_code'   => $splitGroupCode,
                'is_split_locked'    => 0,
                'subtotal'           => (float) ($preview['preview_child']['subtotal'] ?? 0),
                'discount_amount'    => (float) ($preview['preview_child']['discount_amount'] ?? 0),
                'service_charge'     => (float) ($preview['preview_child']['service_charge'] ?? 0),
                'vat_amount'         => (float) ($preview['preview_child']['vat_amount'] ?? 0),
                'total_price'        => (float) ($preview['preview_child']['total_price'] ?? 0),
                'remark'             => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
                'opened_by'          => $actorId > 0 ? $actorId : null,
                'opened_at'          => $now,
                'created_by'         => $actorId > 0 ? $actorId : null,
                'created_at'         => $now,
                'updated_at'         => $now,
            ], true);

            if (! $childOrderId) {
                throw new \RuntimeException(lang('app.split_bill_failed'));
            }

            $this->sessionModel->update($sessionId, [
                'target_order_id' => $childOrderId,
                'status'          => 'confirmed',
                'confirmed_by'    => $actorId,
                'confirmed_at'    => $now,
                'updated_at'      => $now,
            ]);

            foreach ($selected as $sourceItem) {
                $sourceItemId = (int) ($sourceItem['id'] ?? 0);
                $splitQty     = (int) ($sourceItem['split_qty'] ?? 0);
                $sourceQty    = (int) ($sourceItem['qty'] ?? 0);
                $remainingQty = max(0, $sourceQty - $splitQty);
                $unitPrice    = (float) ($sourceItem['price'] ?? 0);
                $sourceRootId = (int) ($sourceItem['split_root_item_id'] ?? 0);
                if ($sourceRootId <= 0) {
                    $sourceRootId = $sourceItemId;
                }

                $targetItemId = 0;

                if ($splitQty >= $sourceQty) {
                    $updateData = [
                        'order_id'              => $childOrderId,
                        'parent_order_item_id'  => $sourceItemId,
                        'split_root_item_id'    => $sourceRootId,
                        'split_session_id'      => $sessionId,
                        'split_from_order_id'   => $orderId,
                        'split_to_order_id'     => $childOrderId,
                        'source_qty_before_split'=> $sourceQty,
                        'moved_qty'             => $splitQty,
                        'line_total'            => $this->isNonBillableItem($sourceItem) ? 0.0 : round($unitPrice * $splitQty, 2),
                        'updated_at'            => $now,
                    ];

                    $this->orderItemModel->update($sourceItemId, $updateData);
                    $targetItemId = $sourceItemId;
                } else {
                    $cloneData = [
                        'tenant_id'             => $tenantId,
                        'order_id'              => $childOrderId,
                        'product_id'            => isset($sourceItem['product_id']) ? (int) ($sourceItem['product_id'] ?? 0) : null,
                        'product_name'          => (string) ($sourceItem['product_name'] ?? ''),
                        'item_detail'           => (string) ($sourceItem['item_detail'] ?? ''),
                        'option_price'          => (float) ($sourceItem['option_price'] ?? 0),
                        'option_summary'        => (string) ($sourceItem['option_summary'] ?? ''),
                        'price'                 => $unitPrice,
                        'qty'                   => $splitQty,
                        'line_total'            => $this->isNonBillableItem($sourceItem) ? 0.0 : round($unitPrice * $splitQty, 2),
                        'note'                  => (string) ($sourceItem['note'] ?? ''),
                        'status'                => (string) ($sourceItem['status'] ?? 'pending'),
                        'kitchen_ticket_id'     => (int) ($sourceItem['kitchen_ticket_id'] ?? 0) ?: null,
                        'sent_at'               => $sourceItem['sent_at'] ?? null,
                        'served_at'             => $sourceItem['served_at'] ?? null,
                        'cancelled_at'          => $sourceItem['cancelled_at'] ?? null,
                        'cancelled_by'          => $sourceItem['cancelled_by'] ?? null,
                        'cancel_request_status' => $sourceItem['cancel_request_status'] ?? null,
                        'cancel_request_note'   => $sourceItem['cancel_request_note'] ?? null,
                        'cancel_request_reason' => $sourceItem['cancel_request_reason'] ?? null,
                        'cancel_requested_at'   => $sourceItem['cancel_requested_at'] ?? null,
                        'cancel_requested_by'   => $sourceItem['cancel_requested_by'] ?? null,
                        'cancel_request_prev_status' => $sourceItem['cancel_request_prev_status'] ?? null,
                        'cancel_decided_at'     => $sourceItem['cancel_decided_at'] ?? null,
                        'cancel_decided_by'     => $sourceItem['cancel_decided_by'] ?? null,
                        'cancel_rejected_reason'=> $sourceItem['cancel_rejected_reason'] ?? null,
                        'cancel_rejected_note'  => $sourceItem['cancel_rejected_note'] ?? null,
                        'parent_order_item_id'  => $sourceItemId,
                        'split_root_item_id'    => $sourceRootId,
                        'split_session_id'      => $sessionId,
                        'split_from_order_id'   => $orderId,
                        'split_to_order_id'     => $childOrderId,
                        'source_qty_before_split'=> $sourceQty,
                        'moved_qty'             => $splitQty,
                        'created_at'            => $now,
                        'updated_at'            => $now,
                    ];

                    $targetItemId = (int) $this->orderItemModel->insert($cloneData, true);
                    if ($targetItemId <= 0) {
                        throw new \RuntimeException(lang('app.split_bill_failed'));
                    }

                    $this->orderItemModel->update($sourceItemId, [
                        'qty'                   => $remainingQty,
                        'line_total'            => $this->isNonBillableItem($sourceItem) ? 0.0 : round($unitPrice * $remainingQty, 2),
                        'split_root_item_id'    => $sourceRootId,
                        'split_session_id'      => $sessionId,
                        'split_from_order_id'   => $orderId,
                        'split_to_order_id'     => $childOrderId,
                        'source_qty_before_split'=> $sourceQty,
                        'moved_qty'             => $splitQty,
                        'updated_at'            => $now,
                    ]);
                }

                $itemSnapshot = json_encode([
                    'product_name'   => (string) ($sourceItem['product_name'] ?? ''),
                    'item_detail'    => (string) ($sourceItem['item_detail'] ?? ''),
                    'option_summary' => (string) ($sourceItem['option_summary'] ?? ''),
                    'note'           => (string) ($sourceItem['note'] ?? ''),
                    'status'         => (string) ($sourceItem['status'] ?? 'pending'),
                ], JSON_UNESCAPED_UNICODE);

                $this->sessionItemModel->insert([
                    'session_id'           => $sessionId,
                    'tenant_id'            => $tenantId,
                    'branch_id'            => $branchId,
                    'root_order_id'        => $rootOrderId,
                    'source_order_id'      => $orderId,
                    'target_order_id'      => $childOrderId,
                    'source_order_item_id' => $sourceItemId,
                    'target_order_item_id' => $targetItemId > 0 ? $targetItemId : null,
                    'product_id'           => isset($sourceItem['product_id']) ? (int) ($sourceItem['product_id'] ?? 0) : null,
                    'product_name_snapshot'=> (string) ($sourceItem['product_name'] ?? ''),
                    'unit_price'           => $unitPrice,
                    'source_qty_before'    => $sourceQty,
                    'moved_qty'            => $splitQty,
                    'source_qty_after'     => $remainingQty,
                    'line_total'           => $this->isNonBillableItem($sourceItem) ? 0.0 : round($unitPrice * $splitQty, 2),
                    'item_snapshot_json'   => $itemSnapshot,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]);
            }

            $this->orderModel->update($orderId, [
                'bill_type'           => 'split_parent',
                'split_root_order_id' => $rootOrderId,
                'split_group_code'    => $splitGroupCode,
                'subtotal'            => (float) ($preview['preview_parent']['subtotal'] ?? 0),
                'discount_amount'     => (float) ($preview['preview_parent']['discount_amount'] ?? 0),
                'service_charge'      => (float) ($preview['preview_parent']['service_charge'] ?? 0),
                'vat_amount'          => (float) ($preview['preview_parent']['vat_amount'] ?? 0),
                'total_price'         => (float) ($preview['preview_parent']['total_price'] ?? 0),
                'is_split_locked'     => 0,
                'updated_at'          => $now,
            ]);

            $this->db->transCommit();

            return [
                'session_id'        => (int) $sessionId,
                'root_order_id'     => $rootOrderId,
                'parent_order_id'   => $orderId,
                'child_order_id'    => (int) $childOrderId,
                'child_order_number'=> $childOrderNo,
                'split_no'          => $splitNo,
                'split_group_code'  => $splitGroupCode,
                'preview_parent'    => $preview['preview_parent'] ?? [],
                'preview_child'     => $preview['preview_child'] ?? [],
                'items'             => $preview['child_items'] ?? [],
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            try {
                $this->orderModel->update($orderId, [
                    'is_split_locked' => 0,
                    'updated_at'      => $now,
                ]);
            } catch (\Throwable $inner) {
            }
            throw $e;
        }
    }

    public function getSplitGroup(int $orderId, int $tenantId, int $branchId): array
    {
        $order = $this->findScopedOrder($orderId, $tenantId, $branchId);
        if (! $order) {
            throw new \RuntimeException(lang('app.order_not_found'));
        }

        $rootOrderId = $this->resolveRootOrderId($order);
        if ($rootOrderId <= 0) {
            $rootOrderId = (int) ($order['id'] ?? 0);
        }

        $builder = $this->orderModel
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->groupStart()
                ->where('id', $rootOrderId)
                ->orWhere('split_root_order_id', $rootOrderId)
            ->groupEnd()
            ->orderBy('split_no', 'ASC')
            ->orderBy('id', 'ASC');

        $rows = $builder->findAll();
        $root = null;
        $children = [];

        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $rootOrderId) {
                $root = $row;
                continue;
            }
            $children[] = $row;
        }

        return [
            'root_order'     => $root ?: $order,
            'current_order'  => $order,
            'children'       => $children,
            'group_summary'  => [
                'root_order_id'    => $rootOrderId,
                'current_order_id' => (int) ($order['id'] ?? 0),
                'children_count'   => count($children),
                'split_group_code' => (string) ($order['split_group_code'] ?? ($root['split_group_code'] ?? '')),
            ],
        ];
    }

    public function getSplitHistory(int $orderId, int $tenantId, int $branchId): array
    {
        $order = $this->findScopedOrder($orderId, $tenantId, $branchId);
        if (! $order) {
            throw new \RuntimeException(lang('app.order_not_found'));
        }

        $rootOrderId = $this->resolveRootOrderId($order);
        if ($rootOrderId <= 0) {
            $rootOrderId = (int) ($order['id'] ?? 0);
        }

        $sessions = $this->sessionModel
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('root_order_id', $rootOrderId)
            ->orderBy('id', 'DESC')
            ->findAll();

        $sessionIds = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $sessions);
        $items = [];
        if (! empty($sessionIds)) {
            $items = $this->sessionItemModel
                ->whereIn('session_id', $sessionIds)
                ->orderBy('id', 'ASC')
                ->findAll();
        }

        return [
            'sessions' => $sessions,
            'items'    => $items,
        ];
    }

    protected function findScopedOrder(int $orderId, int $tenantId, int $branchId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $builder = $this->orderModel->where('id', $orderId);
        if ($tenantId > 0) {
            $builder->where('tenant_id', $tenantId);
        }
        if ($branchId > 0) {
            $builder->where('branch_id', $branchId);
        }

        return $builder->first();
    }

    protected function lockOrderForUpdate(int $orderId, int $tenantId, int $branchId): ?array
    {
        $sql = 'SELECT * FROM orders WHERE id = ? AND tenant_id = ?';
        $params = [$orderId, $tenantId];
        if ($branchId > 0) {
            $sql .= ' AND branch_id = ?';
            $params[] = $branchId;
        }
        $sql .= ' LIMIT 1 FOR UPDATE';

        return $this->db->query($sql, $params)->getRowArray() ?: null;
    }

    protected function resolveRootOrderId(array $order): int
    {
        $rootOrderId = (int) ($order['split_root_order_id'] ?? 0);
        if ($rootOrderId > 0) {
            return $rootOrderId;
        }

        if ((string) ($order['bill_type'] ?? 'normal') === 'split_child') {
            return (int) ($order['parent_order_id'] ?? 0);
        }

        return (int) ($order['id'] ?? 0);
    }

    protected function assertOrderSplittable(array $order): void
    {
        $status = (string) ($order['status'] ?? 'open');
        if (in_array($status, ['paid', 'cancel', 'merged'], true)) {
            throw new \RuntimeException(lang('app.split_bill_not_allowed_for_order_status'));
        }

        if ((int) ($order['is_split_locked'] ?? 0) === 1) {
            throw new \RuntimeException(lang('app.split_bill_order_locked'));
        }

        if ((string) ($order['bill_type'] ?? 'normal') === 'split_child') {
            throw new \RuntimeException(lang('app.split_bill_child_not_supported'));
        }

        if ((int) ($order['merged_into_order_id'] ?? 0) > 0) {
            throw new \RuntimeException(lang('app.split_bill_not_allowed_for_order_status'));
        }
    }

    protected function normalizeAndValidateItems(array $order, array $items, int $tenantId, int $branchId): array
    {
        $raw = [];
        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            $itemId = (int) ($row['order_item_id'] ?? 0);
            $qty    = (int) ($row['split_qty'] ?? 0);
            if ($itemId <= 0 || $qty <= 0) {
                continue;
            }
            $raw[$itemId] = $qty;
        }

        if (empty($raw)) {
            return [];
        }

        $orderItems = $this->orderItemModel->getByOrder((int) ($order['id'] ?? 0));
        $itemMap = [];
        foreach ($orderItems as $item) {
            $itemMap[(int) ($item['id'] ?? 0)] = $item;
        }

        $selected = [];
        foreach ($raw as $itemId => $qty) {
            if (! isset($itemMap[$itemId])) {
                throw new \RuntimeException(lang('app.item_not_found'));
            }

            $item = $itemMap[$itemId];
            $status = strtolower(trim((string) ($item['status'] ?? 'pending')));
            if (in_array($status, ['cancel', 'cancelled'], true)) {
                throw new \RuntimeException(lang('app.split_bill_item_cancelled'));
            }

            $availableQty = (int) ($item['qty'] ?? 0);
            if ($availableQty <= 0 || $qty > $availableQty) {
                throw new \RuntimeException(lang('app.invalid_qty'));
            }

            $item['split_qty'] = $qty;
            $selected[] = $item;
        }

        return $selected;
    }

    protected function sumBillableSubtotal(int $orderId, int $tenantId, int $branchId): float
    {
        $items = $this->orderItemModel->getByOrder($orderId);
        $subtotal = 0.0;
        foreach ($items as $item) {
            if ($this->isNonBillableItem($item)) {
                continue;
            }
            $subtotal += (float) ($item['line_total'] ?? (((float) ($item['price'] ?? 0)) * ((int) ($item['qty'] ?? 0))));
        }

        return round($subtotal, 2);
    }

    protected function allocateOrderAmounts(array $order, float $sourceSubtotal, float $childSubtotal): array
    {
        if ($sourceSubtotal <= 0 || $childSubtotal <= 0) {
            return [0.0, 0.0, 0.0];
        }

        $ratio = max(0, min(1, $childSubtotal / $sourceSubtotal));
        $discount = round((float) ($order['discount_amount'] ?? 0) * $ratio, 2);
        $service  = round((float) ($order['service_charge'] ?? 0) * $ratio, 2);
        $vat      = round((float) ($order['vat_amount'] ?? 0) * $ratio, 2);

        return [$discount, $service, $vat];
    }

    protected function nextSplitNo(int $rootOrderId, int $tenantId, int $branchId): int
    {
        $builder = $this->orderModel
            ->selectMax('split_no', 'max_split_no')
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('split_root_order_id', $rootOrderId);

        $row = $builder->first();
        $max = (int) ($row['max_split_no'] ?? 0);

        return $max + 1;
    }

    protected function buildSplitGroupCode(int $rootOrderId): string
    {
        return 'SBG-' . $rootOrderId . '-' . date('YmdHis');
    }

    protected function buildSessionCode(int $rootOrderId, int $splitNo): string
    {
        return 'SBS-' . $rootOrderId . '-' . $splitNo . '-' . date('YmdHis');
    }
}
