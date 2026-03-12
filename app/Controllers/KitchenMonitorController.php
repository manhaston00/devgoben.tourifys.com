<?php

namespace App\Controllers;

use App\Models\KitchenLogModel;
use App\Models\KitchenStationModel;
use App\Models\KitchenTicketModel;
use App\Models\OrderItemModel;
use App\Models\OrderMergeModel;
use App\Models\TableModel;
use App\Models\OrderModel;

class KitchenMonitorController extends BaseController
{
    protected $kitchenTicketModel;
    protected $kitchenStationModel;
    protected $orderItemModel;
    protected $kitchenLogModel;
    protected $orderMergeModel;
    protected $tableModel;
    protected $orderModel;

    public function __construct()
    {
        $this->kitchenTicketModel  = new KitchenTicketModel();
        $this->kitchenStationModel = new KitchenStationModel();
        $this->orderItemModel      = new OrderItemModel();
        $this->kitchenLogModel     = new KitchenLogModel();
        $this->orderMergeModel     = new OrderMergeModel();
        $this->tableModel          = new TableModel();
        $this->orderModel          = new OrderModel();
    }

    protected function currentTenantId(): int
    {
        if (function_exists('current_tenant_id')) {
            return (int) (current_tenant_id() ?: 0);
        }

        return (int) (session('tenant_id') ?? 0);
    }

    protected function currentBranchId(): int
    {
        if (function_exists('current_branch_id')) {
            return (int) (current_branch_id() ?: 0);
        }

        return (int) (session('branch_id') ?? 0);
    }

    protected function currentLocale(): string
    {
        return (string) (service('request')->getLocale() ?: 'th');
    }

    protected function normalizeRequestedStatus(string $status): string
    {
        $status = strtolower(trim($status));

        $map = [
            'new'       => 'pending',
            'pending'   => 'pending',
            'sent'      => 'sent',
            'preparing' => 'cooking',
            'cooking'   => 'cooking',
            'ready'     => 'ready',
            'served'    => 'served',
            'cancelled' => 'cancel',
            'canceled'  => 'cancel',
            'cancel'    => 'cancel',
        ];

        return $map[$status] ?? '';
    }

    protected function mapStatusToLogType(string $status): string
    {
        $status = strtolower(trim($status));

        switch ($status) {
            case 'pending':
            case 'sent':
                return 'new';

            case 'cooking':
                return 'cooking';

            case 'ready':
                return 'ready';

            case 'served':
                return 'served';

            case 'cancel':
                return 'cancel';

            default:
                return '';
        }
    }

    protected function resolveBoardStatus(array $row): string
    {
        $displayStatus = strtolower((string) ($row['display_status'] ?? ''));
        if (in_array($displayStatus, ['new', 'preparing', 'ready', 'served'], true)) {
            return $displayStatus;
        }

        $itemStatus = strtolower((string) ($row['item_status'] ?? ''));
        $ticketStatus = strtolower((string) ($row['ticket_status'] ?? ''));

        if ($itemStatus === 'served') {
            return 'served';
        }

        if ($itemStatus === 'ready') {
            return 'ready';
        }

        if ($itemStatus === 'cooking') {
            return 'preparing';
        }

        if (in_array($itemStatus, ['pending', 'sent'], true)) {
            if ($itemStatus === 'sent' && $ticketStatus === 'done') {
                return 'ready';
            }

            return 'new';
        }

        return 'new';
    }

    protected function statusLabels(): array
    {
        return [
            'new'       => lang('app.status_new'),
            'preparing' => lang('app.status_preparing'),
            'ready'     => lang('app.status_ready'),
            'served'    => lang('app.status_served'),
        ];
    }

    protected function attachMergeInfoToRow(array $row): array
    {
        $tenantId = $this->currentTenantId();
        $branchId = $this->currentBranchId();
        $orderId  = (int) ($row['order_id'] ?? 0);

        $row['is_merged'] = 0;
        $row['merged_from_table_name'] = null;
        $row['merged_to_table_name'] = null;
        $row['merged_target_order_number'] = null;
        $row['merged_reason'] = null;

        if ($orderId <= 0) {
            return $row;
        }

        $mergeBuilder = $this->orderMergeModel
            ->where('tenant_id', $tenantId)
            ->where('source_order_id', $orderId);

        if ($branchId > 0) {
            $mergeBuilder->groupStart()
                ->where('branch_id', $branchId)
                ->orWhere('branch_id', null)
                ->orWhere('branch_id', 0)
                ->groupEnd();
        }

        $merge = $mergeBuilder
            ->orderBy('id', 'DESC')
            ->first();

        if (! $merge) {
            return $row;
        }

        $row['is_merged'] = 1;
        $row['merged_reason'] = $merge['reason'] ?? null;

        $sourceTableId = (int) ($merge['source_table_id'] ?? 0);
        $targetTableId = (int) ($merge['target_table_id'] ?? 0);
        $targetOrderId = (int) ($merge['target_order_id'] ?? 0);

        if ($sourceTableId > 0) {
            $sourceTable = $this->tableModel->find($sourceTableId);
            if ($sourceTable) {
                $row['merged_from_table_name'] = $sourceTable['table_name'] ?? null;
            }
        }

        if ($targetTableId > 0) {
            $targetTable = $this->tableModel->find($targetTableId);
            if ($targetTable) {
                $row['merged_to_table_name'] = $targetTable['table_name'] ?? null;
            }
        }

        if ($targetOrderId > 0) {
            $targetOrder = $this->orderModel->find($targetOrderId);
            if ($targetOrder) {
                $row['merged_target_order_number'] = $targetOrder['order_number'] ?? null;
            }
        }

        return $row;
    }


    protected function normalizeBillableItemStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        $map = [
            ''           => 'pending',
            'new'        => 'pending',
            'open'       => 'pending',
            'pending'    => 'pending',
            'sent'       => 'sent',
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

    protected function isNonBillableItemStatus(?string $status): bool
    {
        $status = $this->normalizeBillableItemStatus($status);

        return in_array($status, ['pending', 'cancelled'], true);
    }

    protected function recalculateOrderTotals(int $orderId): void
    {
        if ($orderId <= 0) {
            return;
        }

        $tenantId = $this->currentTenantId();
        $branchId = $this->currentBranchId();
        $db       = \Config\Database::connect();

        $orderBuilder = $this->orderModel
            ->where('id', $orderId)
            ->where('tenant_id', $tenantId);

        if ($branchId > 0 && $db->fieldExists('branch_id', 'orders')) {
            $orderBuilder->where('branch_id', $branchId);
        }

        $order = $orderBuilder->first();

        if (! $order) {
            return;
        }

        $itemBuilder = $this->orderItemModel->where('order_id', $orderId);

        if ($db->fieldExists('tenant_id', 'order_items')) {
            $itemBuilder->groupStart()
                ->where('tenant_id', $tenantId)
                ->orWhere('tenant_id', null)
                ->orWhere('tenant_id', 0)
                ->groupEnd();
        }

        if ($branchId > 0 && $db->fieldExists('branch_id', 'order_items')) {
            $itemBuilder->groupStart()
                ->where('branch_id', $branchId)
                ->orWhere('branch_id', null)
                ->orWhere('branch_id', 0)
                ->groupEnd();
        }

        $items = $itemBuilder->findAll();
        $subtotal = 0.0;
        $now = date('Y-m-d H:i:s');

        foreach ($items as $row) {
            $qty       = (int) ($row['qty'] ?? 0);
            $unitPrice = (float) ($row['price'] ?? 0);
            $lineTotal = $this->isNonBillableItemStatus($row['status'] ?? '')
                ? 0.0
                : ($qty * $unitPrice);

            $this->orderItemModel->update((int) ($row['id'] ?? 0), [
                'line_total' => $lineTotal,
                'updated_at' => $now,
            ]);

            $subtotal += $lineTotal;
        }

        $discount = (float) ($order['discount_amount'] ?? 0);
        $service  = (float) ($order['service_charge'] ?? 0);
        $vat      = (float) ($order['vat_amount'] ?? 0);
        $total    = $subtotal - $discount + $service + $vat;

        if ($total < 0) {
            $total = 0;
        }

        $this->orderModel->update($orderId, [
            'subtotal'        => $subtotal,
            'discount_amount' => $discount,
            'service_charge'  => $service,
            'vat_amount'      => $vat,
            'total_price'     => $total,
            'updated_at'      => $now,
        ]);
    }

    public function index()
    {
        $stationId = (int) ($this->request->getGet('station_id') ?? 0);
        $stations = method_exists($this->kitchenStationModel, 'getVisibleStations')
            ? $this->kitchenStationModel->getVisibleStations(true)
            : $this->kitchenStationModel->findAll();

        return view('kitchen_monitor/index', [
            'title'          => lang('app.kitchen_monitor'),
            'stationId'      => $stationId,
            'stations'       => $stations,
            'defaultMode'    => $stationId > 0 ? 'station' : 'all',
            'pollingSeconds' => 5,
        ]);
    }

    public function feed()
    {
        $stationId = (int) ($this->request->getGet('station_id') ?? 0);
        $mode = trim((string) ($this->request->getGet('mode') ?? 'all'));
        $locale = $this->currentLocale();

        $rows = $this->kitchenTicketModel->getMonitorBoardRows(
            $this->currentTenantId(),
            $this->currentBranchId(),
            $stationId > 0 ? $stationId : null,
            $mode === 'station',
            $locale
        );

        $grouped = [
            'new'       => [],
            'preparing' => [],
            'ready'     => [],
            'served'    => [],
        ];

        foreach ($rows as $row) {
            $row = $this->attachMergeInfoToRow($row);

            $boardStatus = $this->resolveBoardStatus($row);
            $row['board_status'] = $boardStatus;
            $row['status_label'] = $this->statusLabels()[$boardStatus] ?? ucfirst($boardStatus);
            $grouped[$boardStatus][] = $row;
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $grouped,
            'meta'   => [
                'labels' => $this->statusLabels(),
                'locale' => $locale,
            ],
        ]);
    }

    public function updateStatus()
    {
        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        $requestedStatus = (string) ($this->request->getPost('status') ?? '');
        $status = $this->normalizeRequestedStatus($requestedStatus);

        if ($itemId <= 0 || $status === '') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.invalid_request'),
            ]);
        }

        $item = method_exists($this->orderItemModel, 'findScoped')
            ? $this->orderItemModel->findScoped($itemId)
            : $this->orderItemModel->find($itemId);

        if (! $item) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.item_not_found'),
            ]);
        }

        $fromStatus = (string) ($item['status'] ?? '');
        $now = date('Y-m-d H:i:s');

        $data = [
            'status'     => $status,
            'updated_at' => $now,
        ];

        if ($this->isNonBillableItemStatus($status)) {
            $data['line_total'] = 0;
        } else {
            $data['line_total'] = ((float) ($item['price'] ?? 0)) * (int) ($item['qty'] ?? 0);
        }

        if ($status === 'sent' && empty($item['sent_at'])) {
            $data['sent_at'] = $now;
        }

        if ($status === 'served') {
            $data['served_at'] = $now;
        }

        if ($status === 'cancel') {
            $data['cancelled_at'] = $now;
            $data['cancelled_by'] = (int) (session('user_id') ?? 0);
        }

        if (! $this->orderItemModel->update($itemId, $data)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.save_failed'),
            ]);
        }

        $logType = $this->mapStatusToLogType($status);

        if ($logType !== '') {
            try {
                $this->kitchenLogModel->addLog(
                    $itemId,
                    $logType,
                    lang('app.kitchen_status_updated'),
                    [
                        'branch_id'     => $this->currentBranchId(),
                        'order_id'      => (int) ($item['order_id'] ?? 0),
                        'ticket_id'     => (int) ($item['kitchen_ticket_id'] ?? 0),
                        'from_status'   => $fromStatus,
                        'to_status'     => $status,
                        'action_by'     => (int) (session('user_id') ?? 0),
                        'action_source' => 'kitchen.monitor',
                    ]
                );
            } catch (\Throwable $e) {
                log_message('error', 'Kitchen monitor addLog error: ' . $e->getMessage());
            }
        }

        $ticketId = (int) ($item['kitchen_ticket_id'] ?? 0);
        if ($ticketId > 0) {
            $this->kitchenTicketModel->refreshStatusByTicketId($this->currentTenantId(), $ticketId);
        }

        $this->recalculateOrderTotals((int) ($item['order_id'] ?? 0));

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => lang('app.save_success'),
            'token'   => csrf_hash(),
            'data'    => [
                'item_id'        => $itemId,
                'requested'      => strtolower(trim($requestedStatus)),
                'stored_status'  => $status,
                'display_status' => $this->resolveBoardStatus([
                    'item_status'    => $status,
                    'ticket_status'  => '',
                    'display_status' => '',
                ]),
            ],
        ]);
    }
}