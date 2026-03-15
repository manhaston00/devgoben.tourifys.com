<?php

namespace App\Controllers;

use App\Models\KitchenLogModel;
use App\Models\KitchenStationModel;
use App\Models\KitchenTicketModel;
use App\Models\OrderItemModel;
use App\Models\OrderMergeModel;
use App\Models\TableModel;
use App\Models\OrderModel;
use App\Models\AuditLogModel;

class KitchenMonitorController extends BaseController
{
    protected $kitchenTicketModel;
    protected $kitchenStationModel;
    protected $orderItemModel;
    protected $kitchenLogModel;
    protected $orderMergeModel;
    protected $tableModel;
    protected $orderModel;
    protected $auditLogModel;
    protected $db;

    public function __construct()
    {
        $this->kitchenTicketModel  = new KitchenTicketModel();
        $this->kitchenStationModel = new KitchenStationModel();
        $this->orderItemModel      = new OrderItemModel();
        $this->kitchenLogModel     = new KitchenLogModel();
        $this->orderMergeModel     = new OrderMergeModel();
        $this->tableModel          = new TableModel();
        $this->orderModel          = new OrderModel();
        $this->auditLogModel       = new AuditLogModel();
        $this->db                  = \Config\Database::connect();
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


    protected function currentUserPermissions(): array
    {
        $permissions = session('permissions') ?? [];

        if (! is_array($permissions)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($item) => trim((string) $item),
            $permissions
        ))));
    }

    protected function userHasPermissionKey(string $permissionKey): bool
    {
        if ($permissionKey === '') {
            return false;
        }

        return in_array($permissionKey, $this->currentUserPermissions(), true);
    }

    protected function canServeItems(): bool
    {
        return $this->userHasPermissionKey('kitchen.update_status')
            || $this->userHasPermissionKey('kitchen.serve_item');
    }

    protected function isServeItemFeatureEnabled(): bool
    {
        helper('app');

        return setting_bool('feature.serve_item.enabled', true);
    }

    protected function writeAuditLog(array $payload, ?string $dedupeKey = null, int $dedupeSeconds = 0): void
    {
        try {
            if (! isset($this->auditLogModel) || ! $this->auditLogModel) {
                return;
            }

            if ($dedupeKey !== null && $dedupeSeconds > 0 && ! $this->shouldWriteAuditLog($dedupeKey, $dedupeSeconds)) {
                return;
            }

            $this->auditLogModel->add($payload);
        } catch (\Throwable $e) {
            log_message('error', 'Kitchen writeAuditLog error: ' . $e->getMessage());
        }
    }

    protected function shouldWriteAuditLog(string $key, int $seconds = 5): bool
    {
        $sessionKey = '_audit_dedupe.' . md5($key);
        $lastAt     = (int) (session($sessionKey) ?? 0);
        $now        = time();

        if ($lastAt > 0 && ($now - $lastAt) < $seconds) {
            return false;
        }

        session()->set($sessionKey, $now);

        return true;
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

            case 'cancelled':
                return 'cancel';

            default:
                return '';
        }
    }

    protected function normalizeBillableStatus(?string $status): string
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

    protected function isNonBillableStatus(?string $status): bool
    {
        $status = $this->normalizeBillableStatus($status);

        return in_array($status, ['pending', 'cancelled'], true);
    }

    protected function recalculateOrderTotal(int $orderId): void
    {
        if ($orderId <= 0) {
            return;
        }

        $builder = $this->orderItemModel->where('order_id', $orderId);

        if (method_exists($this->orderItemModel, 'scopeTenant')) {
            $builder = $this->orderItemModel->scopeTenant()->where('order_id', $orderId);
        }

        $items = $builder->findAll();
        $total = 0.0;

        foreach ($items as $row) {
            $qty       = (int) ($row['qty'] ?? 0);
            $unitPrice = (float) ($row['price'] ?? 0);
            $status    = (string) ($row['status'] ?? 'pending');
            $lineTotal = $this->isNonBillableStatus($status) ? 0.0 : ($unitPrice * $qty);

            $currentLineTotal = (float) ($row['line_total'] ?? 0);
            if (abs($currentLineTotal - $lineTotal) > 0.00001) {
                $this->directUpdateOrderItem((int) ($row['id'] ?? 0), [
                    'line_total' => $lineTotal,
                ]);
            }

            $total += $lineTotal;
        }

        $this->orderModel->update($orderId, [
            'total_price' => $total,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    protected function orderItemsFieldExists(string $field): bool
    {
        return $this->db->fieldExists($field, 'order_items');
    }

    protected function isCancelRequestFlowEnabled(): bool
    {
        return $this->orderItemsFieldExists('cancel_request_status');
    }

    protected function flowText(string $th, string $en): string
    {
        return $this->currentLocale() === 'th' ? $th : $en;
    }

    protected function currentUserId(): int
    {
        return (int) (session('user_id') ?? 0);
    }

    protected function currentActorName(): string
    {
        return trim((string) (session('full_name') ?? session('username') ?? ''));
    }

    protected function currentActorRoleName(): string
    {
        return trim((string) (session('role_name') ?? session('role_code') ?? session('role') ?? ''));
    }


    protected function resolveServePermissionKey(): string
    {
        return $this->userHasPermissionKey('kitchen.serve_item')
            ? 'kitchen.serve_item'
            : 'kitchen.update_status';
    }

    protected function getScopedOrder(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $builder = $this->db->table('orders')->where('id', $orderId);

        $tenantId = $this->currentTenantId();
        if ($tenantId > 0 && $this->db->fieldExists('tenant_id', 'orders')) {
            $builder->where('tenant_id', $tenantId);
        }

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'orders')) {
            $builder->where('branch_id', $branchId);
        }

        return $builder->get()->getRowArray() ?: null;
    }

    protected function getScopedTable(int $tableId): ?array
    {
        if ($tableId <= 0) {
            return null;
        }

        $builder = $this->db->table('restaurant_tables')->where('id', $tableId);

        $tenantId = $this->currentTenantId();
        if ($tenantId > 0 && $this->db->fieldExists('tenant_id', 'restaurant_tables')) {
            $builder->where('tenant_id', $tenantId);
        }

        $branchId = $this->currentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'restaurant_tables')) {
            $builder->where('branch_id', $branchId);
        }

        return $builder->get()->getRowArray() ?: null;
    }

    protected function getKitchenTicketAuditContext(int $ticketId): array
    {
        if ($ticketId <= 0) {
            return [
                'ticket_no'    => '',
                'batch_no'     => null,
                'station_id'   => null,
                'station_name' => '',
            ];
        }

        $tenantId = $this->currentTenantId();
        $branchId = $this->currentBranchId();

        $builder = $this->db->table('kitchen_tickets kt')
            ->select([
                'kt.ticket_no',
                'kt.dispatch_batch_no',
                'p.kitchen_station_id',
                'ks.station_name',
                'ks.station_name_th',
                'ks.station_name_en',
            ])
            ->join('order_items oi', 'oi.kitchen_ticket_id = kt.id AND oi.tenant_id = kt.tenant_id', 'left')
            ->join('products p', 'p.id = oi.product_id AND p.tenant_id = oi.tenant_id', 'left')
            ->join('kitchen_stations ks', 'ks.id = p.kitchen_station_id AND ks.tenant_id = p.tenant_id', 'left')
            ->where('kt.id', $ticketId);

        if ($tenantId > 0 && $this->db->fieldExists('tenant_id', 'kitchen_tickets')) {
            $builder->where('kt.tenant_id', $tenantId);
        }

        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'kitchen_tickets')) {
            $builder->where('kt.branch_id', $branchId);
        }

        $row = $builder->orderBy('oi.id', 'ASC')->get()->getRowArray() ?: [];

        $locale = $this->currentLocale();
        $stationName = '';
        if ($locale === 'th') {
            $stationName = trim((string) ($row['station_name_th'] ?? $row['station_name'] ?? $row['station_name_en'] ?? ''));
        } else {
            $stationName = trim((string) ($row['station_name_en'] ?? $row['station_name'] ?? $row['station_name_th'] ?? ''));
        }

        return [
            'ticket_no'    => trim((string) ($row['ticket_no'] ?? '')),
            'batch_no'     => isset($row['dispatch_batch_no']) && $row['dispatch_batch_no'] !== '' ? (int) $row['dispatch_batch_no'] : null,
            'station_id'   => isset($row['kitchen_station_id']) && $row['kitchen_station_id'] !== '' ? (int) $row['kitchen_station_id'] : null,
            'station_name' => $stationName,
        ];
    }

    protected function buildServedAuditMeta(array $item, ?array $order, string $fromStatus, string $servedAt, string $actionSource, string $sourceScreen): array
    {
        $tableId = isset($order['table_id']) ? (int) ($order['table_id'] ?? 0) : 0;
        $table = $tableId > 0 ? $this->getScopedTable($tableId) : null;
        $ticketId = (int) ($item['kitchen_ticket_id'] ?? 0);
        $ticketContext = $this->getKitchenTicketAuditContext($ticketId);

        $meta = [
            'item_id'         => (int) ($item['id'] ?? 0),
            'product_name'    => trim((string) ($item['product_name'] ?? '')),
            'qty'             => (int) ($item['qty'] ?? 0),
            'from_status'     => $fromStatus,
            'to_status'       => 'served',
            'served_at'       => $servedAt,
            'action_source'   => $actionSource,
            'source_screen'   => $sourceScreen,
            'tenant_id'       => $this->currentTenantId(),
            'branch_id'       => $this->currentBranchId(),
            'order_id'        => (int) ($item['order_id'] ?? ($order['id'] ?? 0)),
            'order_number'    => trim((string) ($order['order_number'] ?? '')),
            'table_id'        => $tableId > 0 ? $tableId : null,
            'table_name'      => trim((string) ($table['table_name'] ?? '')),
            'ticket_id'       => $ticketId > 0 ? $ticketId : null,
            'ticket_no'       => $ticketContext['ticket_no'] ?? '',
            'batch_no'        => $ticketContext['batch_no'] ?? null,
            'station_id'      => $ticketContext['station_id'] ?? null,
            'station_name'    => trim((string) ($ticketContext['station_name'] ?? '')),
            'actor_user_id'   => $this->currentUserId(),
            'actor_name'      => $this->currentActorName(),
            'actor_role_name' => $this->currentActorRoleName(),
            'permission_key'  => $this->resolveServePermissionKey(),
        ];

        foreach ($meta as $key => $value) {
            if ($value === null || $value === '') {
                unset($meta[$key]);
            }
        }

        return $meta;
    }

    protected function canShowCancelDecisionActions(array $row): bool
    {
        $cancelRequestStatus = strtolower((string) ($row['cancel_request_status'] ?? ''));

        return $cancelRequestStatus === 'pending';
    }

    protected function getScopedItemFull(int $itemId): ?array
    {
        if ($itemId <= 0) {
            return null;
        }

        return method_exists($this->orderItemModel, 'findScoped')
            ? $this->orderItemModel->findScoped($itemId)
            : $this->orderItemModel->find($itemId);
    }

    protected function directUpdateOrderItem(int $itemId, array $data): bool
    {
        if ($itemId <= 0 || empty($data)) {
            return false;
        }

        $builder = $this->db->table('order_items')->where('id', $itemId);

        $tenantId = $this->currentTenantId();
        if ($tenantId > 0 && $this->orderItemsFieldExists('tenant_id')) {
            $builder->where('tenant_id', $tenantId);
        }

        return (bool) $builder->update($data);
    }

    protected function attachCancelRequestInfoToRow(array $row): array
    {
        if (! $this->isCancelRequestFlowEnabled()) {
            $row['cancel_request_status'] = $row['cancel_request_status'] ?? null;
            return $row;
        }

        $itemId = (int) ($row['order_item_id'] ?? $row['item_id'] ?? 0);
        if ($itemId <= 0) {
            $row['cancel_request_status'] = $row['cancel_request_status'] ?? null;
            return $row;
        }

        $item = $this->getScopedItemFull($itemId);
        if (! $item) {
            $row['cancel_request_status'] = $row['cancel_request_status'] ?? null;
            return $row;
        }

        foreach ([
            'cancel_request_status',
            'cancel_request_note',
            'cancel_request_reason',
            'cancel_requested_at',
            'cancel_requested_by',
            'cancel_request_prev_status',
            'cancel_decided_at',
            'cancel_decided_by',
            'cancel_rejected_note',
            'cancel_rejected_reason',
        ] as $field) {
            if (array_key_exists($field, $item)) {
                $row[$field] = $item[$field];
            }
        }

        return $row;
    }

    protected function resolveBoardStatus(array $row): string
    {
        $cancelRequestStatus = strtolower((string) ($row['cancel_request_status'] ?? ''));
        $cancelPrevStatus    = strtolower((string) ($row['cancel_request_prev_status'] ?? ''));
        $displayStatus       = strtolower((string) ($row['display_status'] ?? ''));
        $itemStatus          = strtolower((string) ($row['item_status'] ?? $row['status'] ?? ''));
        $ticketStatus        = strtolower((string) ($row['ticket_status'] ?? ''));

        if ($cancelRequestStatus === 'pending') {
            return 'cancel_request';
        }

        if ($cancelRequestStatus === 'rejected' && in_array($cancelPrevStatus, ['new', 'preparing', 'ready', 'served'], true)) {
            return $cancelPrevStatus;
        }

        if (in_array($displayStatus, ['new', 'preparing', 'ready', 'served', 'cancel_request'], true)) {
            return $displayStatus;
        }

        if (in_array($itemStatus, ['cancel', 'cancelled', 'canceled'], true)) {
            return 'served';
        }

        if ($itemStatus === 'served') {
            return 'served';
        }

        if ($itemStatus === 'ready') {
            return 'ready';
        }

        if (in_array($itemStatus, ['preparing', 'cooking', 'doing'], true)) {
            return 'preparing';
        }

        if (in_array($itemStatus, ['new', 'pending', 'sent'], true)) {
            return 'new';
        }

        if (in_array($ticketStatus, ['cancel', 'cancelled', 'canceled'], true)) {
            return 'served';
        }

        if ($ticketStatus === 'served') {
            return 'served';
        }

        if ($ticketStatus === 'ready') {
            return 'ready';
        }

        if (in_array($ticketStatus, ['preparing', 'cooking', 'doing'], true)) {
            return 'preparing';
        }

        return 'new';
    }

    protected function statusLabels(): array
    {
        return [
            'new'            => lang('app.status_new'),
            'preparing'      => lang('app.status_preparing'),
            'ready'          => lang('app.status_ready'),
            'cancel_request' => lang('app.cancel_request_pending') ?: 'คำขอยกเลิก',
            'served'         => lang('app.status_served'),
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

        $merge = $mergeBuilder->orderBy('id', 'DESC')->first();

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


    protected function servedBoardLimit(): int
    {
        return 20;
    }

    protected function servedBoardMinutes(): int
    {
        return 20;
    }

    protected function isRecentServedRow(array $row): bool
    {
        $servedAt = trim((string) ($row['served_at'] ?? $row['updated_at'] ?? ''));
        if ($servedAt === '') {
            return false;
        }

        $servedTs = strtotime($servedAt);
        if ($servedTs === false) {
            return false;
        }

        return $servedTs >= strtotime('-' . $this->servedBoardMinutes() . ' minutes');
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
            'new'            => [],
            'preparing'      => [],
            'ready'          => [],
            'cancel_request' => [],
            'served'         => [],
        ];

        foreach ($rows as $row) {
            $row = $this->attachMergeInfoToRow($row);
            $row = $this->attachCancelRequestInfoToRow($row);

            $boardStatus = $this->resolveBoardStatus($row);
            $row['board_status'] = $boardStatus;
            $row['show_cancel_decision_actions'] = $this->canShowCancelDecisionActions($row);
            $row['cancel_request_is_pending']    = strtolower((string) ($row['cancel_request_status'] ?? '')) === 'pending';
            $row['cancel_request_is_rejected']   = strtolower((string) ($row['cancel_request_status'] ?? '')) === 'rejected';
            $row['cancel_request_is_approved']   = strtolower((string) ($row['cancel_request_status'] ?? '')) === 'approved';
            $row['status_label'] = $this->statusLabels()[$boardStatus] ?? ucfirst($boardStatus);

            if ($boardStatus === 'served' && ! $this->isRecentServedRow($row)) {
                continue;
            }

            if (! array_key_exists($boardStatus, $grouped)) {
                $grouped[$boardStatus] = [];
            }

            $grouped[$boardStatus][] = $row;
        }

        if (! empty($grouped['served'])) {
            usort($grouped['served'], static function (array $a, array $b): int {
                $aTs = strtotime((string) ($a['served_at'] ?? $a['updated_at'] ?? '')) ?: 0;
                $bTs = strtotime((string) ($b['served_at'] ?? $b['updated_at'] ?? '')) ?: 0;
                return $bTs <=> $aTs;
            });

            $grouped['served'] = array_slice($grouped['served'], 0, $this->servedBoardLimit());
        }

        $historyRows = $this->kitchenTicketModel->getMonitorHistoryRows(
            $this->currentTenantId(),
            $this->currentBranchId(),
            $stationId > 0 ? $stationId : null,
            $mode === 'station',
            $locale,
            300,
            7
        );

        $history = [
            'served'    => [],
            'cancelled' => [],
        ];

        foreach ($historyRows as $historyRow) {
            $historyRow = $this->attachMergeInfoToRow($historyRow);
            $historyRow = $this->attachCancelRequestInfoToRow($historyRow);

            $historyStatus = strtolower((string) ($historyRow['history_status'] ?? 'served'));
            if ($historyStatus === 'cancelled') {
                $history['cancelled'][] = $historyRow;
            } else {
                $history['served'][] = $historyRow;
            }
        }

        $summary = [
            'new'                     => count($grouped['new'] ?? []),
            'preparing'               => count($grouped['preparing'] ?? []),
            'ready'                   => count($grouped['ready'] ?? []),
            'cancel_request'          => count($grouped['cancel_request'] ?? []),
            'served'                  => count($grouped['served'] ?? []),
            'cancelled'               => count($history['cancelled'] ?? []),
            'active_total'            => count($grouped['new'] ?? []) + count($grouped['preparing'] ?? []) + count($grouped['ready'] ?? []) + count($grouped['cancel_request'] ?? []),
            'served_history_count'    => count($history['served'] ?? []),
            'cancelled_history_count' => count($history['cancelled'] ?? []),
        ];

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $grouped,
            'meta'   => [
                'labels'   => $this->statusLabels(),
                'locale'   => $locale,
                'settings' => [
                    'served_board_limit'   => $this->servedBoardLimit(),
                    'served_board_minutes' => $this->servedBoardMinutes(),
                ],
                'history' => $history,
                'summary' => $summary,
            ],
        ]);
    }

    public function updateStatus()
    {
        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        $requestedStatus = strtolower(trim((string) ($this->request->getPost('status') ?? '')));

        if ($itemId <= 0 || $requestedStatus === '') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.invalid_request'),
            ]);
        }

        if ($requestedStatus === 'served') {
            if (! $this->isServeItemFeatureEnabled()) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.feature_not_available_for_plan'),
                    'token'   => csrf_hash(),
                ]);
            }

            if (! $this->canServeItems()) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.no_permission'),
                    'token'   => csrf_hash(),
                ]);
            }
        }

        $item = $this->getScopedItemFull($itemId);

        if (! $item) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.item_not_found'),
            ]);
        }

        $fromStatus = (string) ($item['status'] ?? '');
        $now = date('Y-m-d H:i:s');
        $userId = $this->currentUserId();

        if ($requestedStatus === 'cancel_approved' && in_array(strtolower($fromStatus), ['cancel', 'cancelled', 'canceled'], true)) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $this->flowText('รายการนี้ถูกยกเลิกแล้ว', 'This item is already cancelled.'),
                'token'   => csrf_hash(),
            ]);
        }

        if ($requestedStatus === 'cancel_rejected' && strtolower((string) ($item['cancel_request_status'] ?? '')) === 'rejected') {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $this->flowText('รายการนี้ถูกปฏิเสธคำขอยกเลิกแล้ว', 'This cancel request has already been rejected.'),
                'token'   => csrf_hash(),
            ]);
        }

        if ($requestedStatus === 'cancel_approved') {
            $data = [
                'status'     => 'cancel',
                'line_total' => 0,
            ];

            if ($this->orderItemsFieldExists('updated_at')) {
                $data['updated_at'] = $now;
            }
            if ($this->orderItemsFieldExists('cancelled_at')) {
                $data['cancelled_at'] = $now;
            }
            if ($this->orderItemsFieldExists('cancelled_by')) {
                $data['cancelled_by'] = $userId;
            }

            if ($this->isCancelRequestFlowEnabled()) {
                foreach ([
                    'cancel_request_status' => 'approved',
                    'cancel_decided_at'     => $now,
                    'cancel_decided_by'     => $userId,
                ] as $field => $value) {
                    if ($this->orderItemsFieldExists($field)) {
                        $data[$field] = $value;
                    }
                }
            }

            if (! $this->directUpdateOrderItem($itemId, $data)) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.save_failed'),
                ]);
            }

            try {
                $this->kitchenLogModel->addLog(
                    $itemId,
                    'cancel',
                    $this->flowText('ครัวอนุมัติการยกเลิกรายการ', 'Kitchen approved the cancel request'),
                    [
                        'branch_id'     => $this->currentBranchId(),
                        'order_id'      => (int) ($item['order_id'] ?? 0),
                        'ticket_id'     => (int) ($item['kitchen_ticket_id'] ?? 0),
                        'from_status'   => $fromStatus,
                        'to_status'     => 'cancel',
                        'action_by'     => $userId,
                        'action_source' => 'kitchen.monitor.cancel_approved',
                    ]
                );
            } catch (\Throwable $e) {
                log_message('error', 'Kitchen cancel approve log error: ' . $e->getMessage());
            }

            $ticketId = (int) ($item['kitchen_ticket_id'] ?? 0);
            if ($ticketId > 0) {
                $this->kitchenTicketModel->refreshStatusByTicketId($this->currentTenantId(), $ticketId);
            }

            $orderId = (int) ($item['order_id'] ?? 0);
            if ($orderId > 0) {
                $this->recalculateOrderTotal($orderId);
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $this->flowText('อนุมัติยกเลิกรายการแล้ว', 'Cancel request approved.'),
                'token'   => csrf_hash(),
            ]);
        }

        if ($requestedStatus === 'cancel_rejected') {
            if (! $this->isCancelRequestFlowEnabled()) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.invalid_request'),
                ]);
            }

            $data = [];
            if ($this->orderItemsFieldExists('updated_at')) {
                $data['updated_at'] = $now;
            }

            foreach ([
                'cancel_request_status' => 'rejected',
                'cancel_decided_at'     => $now,
                'cancel_decided_by'     => $userId,
            ] as $field => $value) {
                if ($this->orderItemsFieldExists($field)) {
                    $data[$field] = $value;
                }
            }

            if (empty($data)) {
                $data['status'] = $fromStatus;
            }

            if (! $this->directUpdateOrderItem($itemId, $data)) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.save_failed'),
                ]);
            }

            try {
                $this->kitchenLogModel->addLog(
                    $itemId,
                    'cancel',
                    $this->flowText('ครัวปฏิเสธการยกเลิกรายการ', 'Kitchen rejected the cancel request'),
                    [
                        'branch_id'     => $this->currentBranchId(),
                        'order_id'      => (int) ($item['order_id'] ?? 0),
                        'ticket_id'     => (int) ($item['kitchen_ticket_id'] ?? 0),
                        'from_status'   => $fromStatus,
                        'to_status'     => $fromStatus,
                        'action_by'     => $userId,
                        'action_source' => 'kitchen.monitor.cancel_rejected',
                    ]
                );
            } catch (\Throwable $e) {
                log_message('error', 'Kitchen cancel reject log error: ' . $e->getMessage());
            }

            $orderId = (int) ($item['order_id'] ?? 0);
            if ($orderId > 0) {
                $this->recalculateOrderTotal($orderId);
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $this->flowText('ปฏิเสธการยกเลิกรายการแล้ว', 'Cancel request rejected.'),
                'token'   => csrf_hash(),
            ]);
        }

        $status = $this->normalizeRequestedStatus($requestedStatus);

        if ($status === '') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.invalid_request'),
            ]);
        }

        $data = [
            'status' => $status,
        ];

        if ($this->orderItemsFieldExists('updated_at')) {
            $data['updated_at'] = $now;
        }
        if ($status === 'sent' && empty($item['sent_at']) && $this->orderItemsFieldExists('sent_at')) {
            $data['sent_at'] = $now;
        }
        if ($status === 'served' && $this->orderItemsFieldExists('served_at')) {
            $data['served_at'] = $now;
        }
        if (in_array($status, ['cancel', 'cancelled', 'canceled'], true)) {
            if ($this->orderItemsFieldExists('cancelled_at')) {
                $data['cancelled_at'] = $now;
            }
            if ($this->orderItemsFieldExists('cancelled_by')) {
                $data['cancelled_by'] = $userId;
            }
            if ($this->isCancelRequestFlowEnabled()) {
                foreach ([
                    'cancel_request_status' => (($item['cancel_request_status'] ?? '') === 'pending') ? 'approved' : ($item['cancel_request_status'] ?? null),
                    'cancel_decided_at'     => (($item['cancel_request_status'] ?? '') === 'pending') ? $now : ($item['cancel_decided_at'] ?? null),
                    'cancel_decided_by'     => (($item['cancel_request_status'] ?? '') === 'pending') ? $userId : ($item['cancel_decided_by'] ?? null),
                ] as $field => $value) {
                    if ($value !== null && $this->orderItemsFieldExists($field)) {
                        $data[$field] = $value;
                    }
                }
            }
        }

        $data['line_total'] = $this->isNonBillableStatus($status)
            ? 0
            : ((float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 0));

        if (! $this->directUpdateOrderItem($itemId, $data)) {
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
                        'action_by'     => $userId,
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

        $orderId = (int) ($item['order_id'] ?? 0);
        if ($status === 'served' && $orderId > 0) {
            $order = $this->getScopedOrder($orderId);
            $this->writeAuditLog([
                'target_type'  => 'order_item',
                'target_id'    => $itemId,
                'order_id'     => $orderId,
                'table_id'     => isset($order['table_id']) ? (int) ($order['table_id'] ?? 0) : null,
                'action_key'   => 'pos.item_served',
                'action_label' => lang('app.audit_log_item_served'),
                'ref_code'     => $order['order_number'] ?? null,
                'meta_json'    => $this->buildServedAuditMeta(
                    array_merge($item, ['id' => $itemId]),
                    $order,
                    $fromStatus,
                    (string) ($data['served_at'] ?? $now),
                    'kitchen.monitor',
                    'kitchen_monitor'
                ),
            ], 'kitchen.item_served.' . $itemId . '.' . ($data['served_at'] ?? $now), 3);
        }

        $orderId = (int) ($item['order_id'] ?? 0);
        if ($orderId > 0) {
            $this->recalculateOrderTotal($orderId);
        }

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