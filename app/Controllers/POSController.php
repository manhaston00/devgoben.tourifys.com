<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\OrderItemModel;
use App\Models\ProductModel;
use App\Models\TableModel;
use App\Models\CategoryModel;
use App\Models\PaymentModel;
use App\Models\KitchenTicketModel;
use App\Models\OrderItemOptionModel;
use App\Models\ProductQuickOptionModel;
use App\Models\QuickNoteModel;
use App\Models\ReservationModel;
use App\Models\ReservationLogModel;
use App\Models\ReservationTableModel;
use App\Models\KitchenLogModel;
use App\Models\OrderTableMoveModel;
use App\Models\OrderMergeModel;
use App\Models\UserModel;
use App\Models\RolePermissionModel;
use App\Models\AuditLogModel;

class POSController extends BaseController
{
    protected $orderModel;
    protected $orderItemModel;
    protected $productModel;
    protected $tableModel;
    protected $categoryModel;
    protected $paymentModel;
    protected $kitchenTicketModel;
    protected $kitchenLogModel;
    protected $orderItemOptionModel;
    protected $productQuickOptionModel;
    protected $quickNoteModel;
    protected $reservationModel;
    protected $reservationLogModel;
    protected $reservationTableModel;
    protected $orderTableMoveModel;
    protected $orderMergeModel;
    protected $userModel;
    protected $rolePermissionModel;
    protected $auditLogModel;
    protected $db;

    public function __construct()
    {
        $this->orderModel              = new OrderModel();
        $this->orderItemModel          = new OrderItemModel();
        $this->productModel            = new ProductModel();
        $this->tableModel              = new TableModel();
        $this->categoryModel           = new CategoryModel();
        $this->paymentModel            = new PaymentModel();
        $this->kitchenTicketModel      = new KitchenTicketModel();
        $this->kitchenLogModel         = new KitchenLogModel();
        $this->orderItemOptionModel    = new OrderItemOptionModel();
        $this->productQuickOptionModel = new ProductQuickOptionModel();
        $this->quickNoteModel          = new QuickNoteModel();
        $this->reservationModel        = new ReservationModel();
        $this->reservationLogModel     = new ReservationLogModel();
        $this->reservationTableModel   = new ReservationTableModel();
        $this->db                      = \Config\Database::connect();
        $this->orderTableMoveModel     = new OrderTableMoveModel();
        $this->orderMergeModel         = new OrderMergeModel();
        $this->userModel               = new UserModel();
        $this->rolePermissionModel     = new RolePermissionModel();
        $this->auditLogModel            = new AuditLogModel();
    }


    protected function writeAuditLog(array $payload): void
    {
        try {
            if (! isset($this->auditLogModel) || ! $this->auditLogModel) {
                return;
            }

            $this->auditLogModel->add($payload);
        } catch (\Throwable $e) {
            log_message('error', 'writeAuditLog error: ' . $e->getMessage());
        }
    }

    protected function currentActorName(): string
    {
        return trim((string) (session('full_name') ?? session('username') ?? ''));
    }

    protected function getActiveOrderStatuses(): array
    {
        return ['open', 'billing'];
    }

    protected function denyCashierViewAccess()
    {
        if (! $this->userHasPermissionKey('cashier.view')) {
            return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
        }

        return null;
    }

    protected function jsonDenyCashierViewAccess()
    {
        if ($this->userHasPermissionKey('cashier.view')) {
            return null;
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => lang('app.no_permission'),
            'code'    => 'NO_PERMISSION',
        ]);
    }

    protected function jsonFeatureDenied(string $featureKey, ?string $messageKey = null)
    {
        if (function_exists('tenant_subscription_expired') && tenant_subscription_expired()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.subscription_expired'),
                'code'    => 'SUBSCRIPTION_EXPIRED',
            ]);
        }

        if (function_exists('feature_enabled') && ! feature_enabled($featureKey)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $messageKey ? lang($messageKey) : lang('app.feature_not_available_for_plan'),
                'code'    => 'FEATURE_NOT_ENABLED',
            ]);
        }

        return null;
    }

    protected function jsonDemoReadonlyDenied(?string $messageKey = null)
    {
        if (function_exists('tenant_demo_readonly') && tenant_demo_readonly()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $messageKey ? lang($messageKey) : lang('app.demo_readonly_denied'),
                'code'    => 'DEMO_READONLY',
            ]);
        }

        return null;
    }

    protected function jsonPosWriteDenied()
    {
        if ($response = $this->jsonFeatureDenied('pos.sell', 'app.plan_cannot_sell_pos')) {
            return $response;
        }

        if ($response = $this->jsonDemoReadonlyDenied('app.demo_cannot_modify_pos_data')) {
            return $response;
        }

        return null;
    }


    protected function jsonManagerOverrideRequired(string $permissionKey, string $actionKey, int $orderId = 0)
    {
        return $this->response->setJSON([
            'status'              => 'error',
            'message'             => lang('app.manager_override_required'),
            'code'                => 'MANAGER_OVERRIDE_REQUIRED',
            'required_permission' => $permissionKey,
            'override_action'     => $actionKey,
            'order_id'            => $orderId,
        ]);
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

    protected function managerOverridePermissionMap(): array
    {
        return [
            'request_bill' => 'cashier.request_bill',
            'close_bill'   => 'cashier.close_bill',
            'pay'          => 'cashier.pay',
        ];
    }

    protected function normalizeOverrideAction(string $actionKey): string
    {
        $actionKey = strtolower(trim($actionKey));
        $map = $this->managerOverridePermissionMap();

        return array_key_exists($actionKey, $map) ? $actionKey : '';
    }

    protected function getOverridePermissionKey(string $actionKey): string
    {
        $map = $this->managerOverridePermissionMap();

        return $map[$actionKey] ?? '';
    }

    protected function getManagerOverridesFromSession(): array
    {
        $rows = session('manager_overrides');

        return is_array($rows) ? $rows : [];
    }

    protected function cleanupManagerOverrides(): array
    {
        $now = time();
        $rows = $this->getManagerOverridesFromSession();
        $clean = [];

        foreach ($rows as $row) {
            $expiresAt = (int) ($row['expires_at'] ?? 0);
            if ($expiresAt > $now) {
                $clean[] = $row;
            }
        }

        session()->set('manager_overrides', $clean);

        return $clean;
    }

    protected function storeManagerOverrideApproval(string $actionKey, int $orderId, array $approver): void
    {
        $rows = $this->cleanupManagerOverrides();
        $rows[] = [
            'action_key'        => $actionKey,
            'permission_key'    => $this->getOverridePermissionKey($actionKey),
            'order_id'          => $orderId,
            'approved_for_user' => (int) (session('user_id') ?? 0),
            'approved_by'       => (int) ($approver['id'] ?? 0),
            'approved_by_name'  => (string) ($approver['full_name'] ?? $approver['username'] ?? '-'),
            'approved_at'       => date('Y-m-d H:i:s'),
            'expires_at'        => time() + 300,
        ];

        session()->set('manager_overrides', $rows);
    }

    protected function consumeManagerOverrideApproval(string $actionKey, int $orderId): ?array
    {
        $rows = $this->cleanupManagerOverrides();
        $currentUserId = (int) (session('user_id') ?? 0);
        $consumed = null;
        $remaining = [];

        foreach ($rows as $row) {
            $matches = (string) ($row['action_key'] ?? '') === $actionKey
                && (int) ($row['order_id'] ?? 0) === $orderId
                && (int) ($row['approved_for_user'] ?? 0) === $currentUserId;

            if ($matches && $consumed === null) {
                $consumed = $row;
                continue;
            }

            $remaining[] = $row;
        }

        session()->set('manager_overrides', $remaining);

        return $consumed;
    }

    protected function ensurePermissionOrManagerOverride(string $permissionKey, string $actionKey, int $orderId = 0)
    {
        if ($this->userHasPermissionKey($permissionKey)) {
            return null;
        }

        $approval = $this->consumeManagerOverrideApproval($actionKey, $orderId);
        if ($approval !== null) {
            return null;
        }

        return $this->jsonManagerOverrideRequired($permissionKey, $actionKey, $orderId);
    }

    protected function findManagerOverrideApprover(string $username, string $pinCode): ?array
    {
        $username = strtolower(trim($username));
        $pinCode = trim($pinCode);

        if ($username === '' || $pinCode === '') {
            return null;
        }

        $user = $this->userModel->findActiveLoginUser($username);
        if (! $user) {
            return null;
        }

        if ((int) ($user['tenant_id'] ?? 0) !== $this->currentTenantId()) {
            return null;
        }

        $userBranchId = (int) ($user['branch_id'] ?? 0);
        $currentBranchId = $this->getCurrentBranchId();
        if ($userBranchId > 0 && $currentBranchId > 0 && $userBranchId !== $currentBranchId) {
            return null;
        }

        if (trim((string) ($user['pin_code'] ?? '')) === '' || trim((string) ($user['pin_code'] ?? '')) !== $pinCode) {
            return null;
        }

        return $user;
    }

    protected function approverCanOverride(array $approver, string $permissionKey): bool
    {
        $roleId = (int) ($approver['role_id'] ?? 0);
        if ($roleId <= 0) {
            return false;
        }

        $permissionKeys = $this->rolePermissionModel->getPermissionKeysByRoleId($roleId);

        return in_array('cashier.manager_override', $permissionKeys, true)
            && in_array($permissionKey, $permissionKeys, true);
    }

    protected function getCurrentBranchId(): int
    {
        if (function_exists('current_branch_id')) {
            return (int) (current_branch_id() ?: 0);
        }

        return (int) (session()->get('branch_id') ?: 0);
    }

    protected function getScopedTable(int $tableId): ?array
    {
        $branchId = $this->getCurrentBranchId();

        if ($tableId <= 0) {
            return null;
        }

        return $this->tableModel->getUsableTable($tableId, $branchId);
    }

    protected function getScopedOrder(int $orderId, array $statuses = []): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        $branchId = $this->getCurrentBranchId();
        $tenantId = function_exists('current_tenant_id') ? current_tenant_id() : null;

        $builder = $this->orderModel->where('id', $orderId);

        if ($tenantId) {
            $builder->where('tenant_id', $tenantId);
        }

        if ($branchId > 0) {
            $builder->where('branch_id', $branchId);
        }

        if (! empty($statuses)) {
            $builder->whereIn('status', $statuses);
        }

        return $builder->first();
    }

    protected function currentTenantId(): int
    {
        $tenantId = 0;

        if (function_exists('current_tenant_id')) {
            $tenantId = (int) (current_tenant_id() ?: 0);
        }

        if ($tenantId <= 0) {
            $tenantId = (int) (session('tenant_id') ?? 0);
        }

        return $tenantId;
    }

    protected function getScopedProduct(int $productId): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        return $this->productModel->findTenantProduct($this->currentTenantId(), $productId);
    }

    protected function scopedOrderQuery(?array $statuses = null)
    {
        $builder = $this->orderModel
            ->where('tenant_id', $this->currentTenantId())
            ->where('branch_id', $this->getCurrentBranchId());

        if (is_array($statuses) && ! empty($statuses)) {
            $builder->whereIn('status', $statuses);
        }

        return $builder;
    }

    protected function findCurrentOrderByTable(int $tableId, ?array $statuses = null): ?array
    {
        if ($tableId <= 0) {
            return null;
        }

        $statuses = $statuses ?? $this->getActiveOrderStatuses();

        return $this->scopedOrderQuery($statuses)
            ->where('table_id', $tableId)
            ->orderBy('id', 'DESC')
            ->first();
    }

    protected function getTenantCategoriesForPos(): array
    {
        if (method_exists($this->categoryModel, 'getTenantCategories')) {
            return $this->categoryModel->getTenantCategories(true);
        }

        if (method_exists($this->categoryModel, 'scopeTenant')) {
            return $this->categoryModel
                ->scopeTenant()
                ->where('status', 1)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll();
        }

        return $this->categoryModel
            ->where('tenant_id', $this->currentTenantId())
            ->where('status', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    protected function getTenantProductsForPos(): array
    {
        if (method_exists($this->productModel, 'scopeTenant')) {
            return $this->productModel
                ->scopeTenant()
                ->where('products.status', 1)
                ->orderBy('products.sort_order', 'ASC')
                ->orderBy('products.product_name', 'ASC')
                ->findAll();
        }

        return $this->productModel
            ->where('tenant_id', $this->currentTenantId())
            ->where('status', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('product_name', 'ASC')
            ->findAll();
    }

    protected function getTenantQuickNotesForPos(): array
    {
        if (method_exists($this->quickNoteModel, 'scopedBuilder')) {
            return $this->quickNoteModel
                ->scopedBuilder()
                ->where('quick_notes.deleted_at IS NULL', null, false)
                ->where('quick_notes.status', 1)
                ->orderBy('quick_notes.sort_order', 'ASC')
                ->orderBy('quick_notes.id', 'ASC')
                ->get()
                ->getResultArray();
        }

        if (method_exists($this->quickNoteModel, 'scopeTenant')) {
            return $this->quickNoteModel
                ->scopeTenant()
                ->where('quick_notes.deleted_at IS NULL', null, false)
                ->where('quick_notes.status', 1)
                ->orderBy('quick_notes.sort_order', 'ASC')
                ->orderBy('quick_notes.id', 'ASC')
                ->get()
                ->getResultArray();
        }

        return $this->quickNoteModel
            ->where('quick_notes.tenant_id', $this->currentTenantId())
            ->where('quick_notes.deleted_at IS NULL', null, false)
            ->where('quick_notes.status', 1)
            ->orderBy('quick_notes.sort_order', 'ASC')
            ->orderBy('quick_notes.id', 'ASC')
            ->findAll();
    }

    protected function decodePostedOptions($optionsRaw): array
    {
        $options = [];

        if (is_string($optionsRaw) && $optionsRaw !== '') {
            $decoded = json_decode($optionsRaw, true);
            if (is_array($decoded)) {
                $options = $decoded;
            }
        } elseif (is_array($optionsRaw)) {
            $options = $optionsRaw;
        }

        return $options;
    }

    protected function buildOrderItemOptionPayload(array $options, string $itemDetail = ''): array
    {
        $optionRows        = [];
        $optionSummaryList = [];
        $optionTotal       = 0.0;

        foreach ($options as $opt) {
            $group       = trim((string) ($opt['group'] ?? ''));
            $name        = trim((string) ($opt['name'] ?? ''));
            $priceAdjust = (float) ($opt['price_adjust'] ?? 0);

            if ($name === '') {
                continue;
            }

            $optionRows[] = [
                'option_group' => $group !== '' ? $group : lang('app.options'),
                'option_name'  => $name,
                'price_adjust' => $priceAdjust,
            ];

            $optionSummaryList[] = $priceAdjust > 0
                ? ($name . ' (+' . number_format($priceAdjust, 2) . ')')
                : $name;

            $optionTotal += $priceAdjust;
        }

        if ($itemDetail !== '') {
            $detailParts = array_filter(array_map('trim', explode(',', $itemDetail)));

            foreach ($detailParts as $detailText) {
                $exists = false;

                foreach ($optionRows as $optRow) {
                    if (trim((string) ($optRow['option_name'] ?? '')) === $detailText) {
                        $exists = true;
                        break;
                    }
                }

                if (! $exists) {
                    $optionRows[] = [
                        'option_group' => lang('app.food_detail_label'),
                        'option_name'  => $detailText,
                        'price_adjust' => 0,
                    ];

                    $optionSummaryList[] = $detailText;
                }
            }
        }

        return [
            'rows'         => $optionRows,
            'summary'      => implode(', ', $optionSummaryList),
            'total_adjust' => $optionTotal,
        ];
    }

    protected function updateScopedTableStatus(int $tableId, string $status): bool
    {
        if ($tableId <= 0) {
            return false;
        }

        $table = $this->tableModel->getTableMapByIds([$tableId], $this->getCurrentBranchId())[$tableId] ?? null;

        if (! $table) {
            return false;
        }

        $tenantId = (int) ($table['tenant_id'] ?? 0);
        if ($tenantId > 0 && $tenantId !== $this->currentTenantId()) {
            return false;
        }

        return (bool) $this->tableModel->update($tableId, [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function findScopedReservationByOrder(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        return $this->reservationModel
            ->where('tenant_id', $this->currentTenantId())
            ->where('branch_id', $this->getCurrentBranchId())
            ->where('order_id', $orderId)
            ->orderBy('id', 'DESC')
            ->first();
    }

    protected function getScopedOrderItem(int $itemId): ?array
    {
        if ($itemId <= 0) {
            return null;
        }

        return $this->orderItemModel->findScoped($itemId);
    }

    protected function deleteScopedOrderItemOptions(int $itemId): void
    {
        if ($itemId <= 0) {
            return;
        }

        $this->orderItemOptionModel->deleteByOrderItem($itemId);
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

    protected function normalizeOrderItemRowForResponse(array $item): array
    {
        $rawStatus = strtolower(trim((string) ($item['status'] ?? '')));
        $requestStatus = strtolower(trim((string) ($item['cancel_request_status'] ?? '')));
        $cancelledAt = trim((string) ($item['cancelled_at'] ?? ''));
        $servedAt = trim((string) ($item['served_at'] ?? ''));

        if ($cancelledAt !== '' || in_array($requestStatus, ['approved', 'accepted'], true)) {
            $item['status'] = 'cancel';
            if ($item['cancel_request_status'] ?? null) {
                $item['cancel_request_status'] = 'approved';
            }
            if (($item['line_total'] ?? null) !== null) {
                $item['line_total'] = 0;
            }
            return $item;
        }

        if ($servedAt !== '' && $rawStatus === '') {
            $item['status'] = 'served';
            return $item;
        }

        $normalized = $this->normalizeOrderItemStatus($rawStatus);
        if ($normalized !== '') {
            $item['status'] = $normalized;
        }

        return $item;
    }

    protected function isNonBillableOrderItemStatus(?string $status): bool
    {
        $status = $this->normalizeOrderItemStatus($status);

        return in_array($status, ['pending', 'cancelled'], true);
    }

    protected function getEffectiveOrderTotal(int $orderId): float
    {
        $this->recalculateOrderTotal($orderId);

        $freshOrder = $this->getScopedOrder($orderId);

        return (float) ($freshOrder['total_price'] ?? 0);
    }

    public function index()
    {
        if ($response = $this->denyIfFeatureNotEnabled('pos.access', lang('app.plan_cannot_access_pos'))) {
            return $response;
        }

        $branchId = $this->getCurrentBranchId();
        $tables   = $this->tableModel->getTablesFull($branchId);

        foreach ($tables as &$table) {
            $tableId  = (int) ($table['id'] ?? 0);
            $isActive = (int) ($table['is_active'] ?? 0) === 1;
            $status   = strtolower(trim((string) ($table['status'] ?? 'available')));

            $currentOrder = $this->findCurrentOrderByTable($tableId);
            $hasOpenOrder = ! empty($currentOrder);

            $lockInfo = $this->getUpcomingReservationLockInfo($tableId);

            $table['reservation_minutes_left'] = $lockInfo['minutes_left'];
            $table['reservation_message']      = $lockInfo['message'];
            $table['reservation_data']         = $lockInfo['reservation'];

            if (! $isActive) {
                $table['status'] = 'disabled';
            } else {
                if ($hasOpenOrder) {
                    $table['status'] = 'occupied';
                } else {
                    if (! $lockInfo['locked'] && in_array($status, ['reserved', 'occupied'], true)) {
                        $table['status'] = 'available';
                    }

                    if (trim((string) ($table['status'] ?? '')) === '') {
                        $table['status'] = 'available';
                    }
                }
            }

            $table['has_open_order']       = $hasOpenOrder ? 1 : 0;
            $table['current_order_id']     = ! empty($currentOrder['id']) ? (int) $currentOrder['id'] : null;
            $table['current_order_status'] = $currentOrder['status'] ?? null;
            $table['reservation_locked']   = $lockInfo['locked'];
            $table['reservation_message']  = $lockInfo['message'];
            $table['reservation_data']     = $lockInfo['reservation'];
        }
        unset($table);

        return view('pos/index', [
            'tables' => $tables,
        ]);
    }

    public function table($tableId)
    {
        if ($response = $this->denyIfFeatureNotEnabled('pos.access', lang('app.plan_cannot_access_pos'))) {
            return $response;
        }

        $tableId = (int) $tableId;

        $table = $this->getScopedTable($tableId);
        if (! $table) {
            return redirect()->to(site_url('pos'))
                ->with('error', lang('app.table_not_found'));
        }

        if ((int) ($table['is_active'] ?? 0) !== 1 || ($table['status'] ?? '') === 'disabled') {
            return redirect()->to(site_url('pos'))
                ->with('error', lang('app.table_disabled'));
        }

        if (($table['status'] ?? '') === 'cleaning') {
            return redirect()->to(site_url('pos'))
                ->with('error', lang('app.table_cleaning'));
        }

        $categories   = $this->getTenantCategoriesForPos();
        $products     = $this->getTenantProductsForPos();
        $currentOrder = $this->findCurrentOrderByTable($tableId);
        $quickNotes   = $this->getTenantQuickNotesForPos();

        return view('pos/table', [
            'table'        => $table,
            'categories'   => $categories,
            'products'     => $products,
            'currentOrder' => $currentOrder,
            'quickNotes'   => $quickNotes,
        ]);
    }


    protected function getCashierOrdersForBranch(): array
    {
        $tenantId = $this->currentTenantId();
        $branchId = $this->getCurrentBranchId();

        $builder = $this->db->table('orders');

        $builder->select("
            orders.*,
            restaurant_tables.table_name,
            restaurant_tables.zone_id,
            COALESCE(NULLIF(zones.zone_name_th, ''), NULLIF(zones.zone_name_en, ''), '-') AS zone_name
        ");

        $builder->join(
            'restaurant_tables',
            'restaurant_tables.id = orders.table_id
             AND restaurant_tables.tenant_id = orders.tenant_id
             AND restaurant_tables.deleted_at IS NULL',
            'left'
        );

        $builder->join(
            'zones',
            'zones.id = restaurant_tables.zone_id
             AND zones.tenant_id = restaurant_tables.tenant_id
             AND zones.deleted_at IS NULL',
            'left'
        );

        $builder->where('orders.tenant_id', $tenantId);

        if ($branchId > 0) {
            $builder->where('orders.branch_id', $branchId);
        }

        $builder->whereIn('orders.status', ['open', 'billing']);
        $builder->orderBy("CASE WHEN orders.status = 'billing' THEN 0 ELSE 1 END", '', false);
        $builder->orderBy('orders.updated_at', 'DESC');
        $builder->orderBy('orders.id', 'DESC');

        $orders = $builder->get()->getResultArray();

        foreach ($orders as &$order) {
            $orderId = (int) ($order['id'] ?? 0);

            $this->recalculateOrderTotal($orderId);

            $items = $this->orderItemModel->getByOrder($orderId);
            $items = array_map(fn (array $item): array => $this->normalizeOrderItemRowForResponse($item), $items);

            $counts = [
                'all'       => count($items),
                'pending'   => 0,
                'sent'      => 0,
                'preparing' => 0,
                'ready'     => 0,
                'served'    => 0,
                'cancelled' => 0,
            ];

            foreach ($items as $item) {
                $normalizedStatus = $this->normalizeOrderItemStatus($item['status'] ?? 'pending');
                if (isset($counts[$normalizedStatus])) {
                    $counts[$normalizedStatus]++;
                }
            }

            $effectiveTotal = $this->getEffectiveOrderTotal($orderId);

            $order['item_counts'] = $counts;
            $order['display_total'] = $effectiveTotal;
        }
        unset($order);

        return $orders;
    }

    protected function getCashierOrderPayload(int $orderId): ?array
    {
        $order = $this->getScopedOrder($orderId, ['open', 'billing']);

        if (! $order) {
            return null;
        }

        $this->recalculateOrderTotal($orderId);
        $order = $this->getScopedOrder($orderId, ['open', 'billing']);

        if (! $order) {
            return null;
        }

        $items = $this->orderItemModel->getByOrder($orderId);
        $items = array_map(fn (array $item): array => $this->normalizeOrderItemRowForResponse($item), $items);

        $table = null;
        if (! empty($order['table_id'])) {
            $tableMap = $this->tableModel->getTableMapByIds([(int) $order['table_id']], $this->getCurrentBranchId());
            $table = $tableMap[(int) $order['table_id']] ?? null;
        }

        $counts = [
            'all'       => count($items),
            'pending'   => 0,
            'sent'      => 0,
            'preparing' => 0,
            'ready'     => 0,
            'served'    => 0,
            'cancelled' => 0,
        ];

        foreach ($items as $item) {
            $normalizedStatus = $this->normalizeOrderItemStatus($item['status'] ?? 'pending');
            if (isset($counts[$normalizedStatus])) {
                $counts[$normalizedStatus]++;
            }
        }

        $summary = [
            'subtotal'        => (float) ($order['subtotal'] ?? 0),
            'discount_amount' => (float) ($order['discount_amount'] ?? 0),
            'service_charge'  => (float) ($order['service_charge'] ?? 0),
            'vat_amount'      => (float) ($order['vat_amount'] ?? 0),
            'total_price'     => (float) ($order['total_price'] ?? 0),
            'display_total'   => $this->getEffectiveOrderTotal($orderId),
        ];

        return [
            'order' => $order,
            'items' => $items,
            'table' => $table,
            'counts' => $counts,
            'summary' => $summary,
        ];
    }

    public function cashier()
    {
        if ($response = $this->denyIfFeatureNotEnabled('pos.access', lang('app.plan_cannot_access_pos'))) {
            return $response;
        }

        if ($response = $this->denyCashierViewAccess()) {
            return $response;
        }

        $this->writeAuditLog([
            'target_type'  => 'cashier_screen',
            'action_key'   => 'cashier.view',
            'action_label' => lang('app.audit_log_cashier_access'),
            'meta_json'    => [
                'screen'    => 'cashier',
                'branch_id' => $this->getCurrentBranchId(),
            ],
        ]);

        $orders = $this->getCashierOrdersForBranch();

        $summary = [
            'orders' => count($orders),
            'billing' => 0,
            'open' => 0,
            'sales_total' => 0.0,
        ];

        foreach ($orders as $order) {
            if (($order['status'] ?? '') === 'billing') {
                $summary['billing']++;
            } else {
                $summary['open']++;
            }

            $summary['sales_total'] += (float) ($order['display_total'] ?? 0);
        }

        return view('pos/cashier', [
            'cashierOrders' => $orders,
            'cashierSummary' => $summary,
            'cashierPermissions' => [
                'view'             => $this->userHasPermissionKey('cashier.view'),
                'request_bill'     => $this->userHasPermissionKey('cashier.request_bill'),
                'close_bill'       => $this->userHasPermissionKey('cashier.close_bill'),
                'pay'              => $this->userHasPermissionKey('cashier.pay'),
                'manager_override' => $this->userHasPermissionKey('cashier.manager_override'),
            ],
        ]);
    }

    public function cashierOrder($orderId = null)
    {
        if ($response = $this->jsonFeatureDenied('pos.access', 'app.plan_cannot_access_pos')) {
            return $response;
        }

        if ($response = $this->jsonDenyCashierViewAccess()) {
            return $response;
        }

        $orderId = (int) $orderId;

        if ($orderId <= 0) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        $payload = $this->getCashierOrderPayload($orderId);

        if (! $payload) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        $this->writeAuditLog([
            'target_type'  => 'order',
            'target_id'    => $orderId,
            'order_id'     => $orderId,
            'action_key'   => 'cashier.view',
            'action_label' => lang('app.audit_log_cashier_order_access'),
            'meta_json'    => [
                'screen'    => 'cashier_order',
                'branch_id' => $this->getCurrentBranchId(),
            ],
        ]);

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $payload,
        ]);
    }

    public function getProductQuickOptions($productId = null)
    {
        if ($response = $this->jsonFeatureDenied('pos.access', 'app.plan_cannot_access_pos')) {
            return $response;
        }

        $productId = (int) $productId;

        if ($productId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.product_not_found'),
            ]);
        }

        $product = $this->getScopedProduct($productId);
        if (! $product) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.product_not_found'),
            ]);
        }

        $categoryId = (int) ($product['category_id'] ?? 0);
        $locale     = service('request')->getLocale();

        $rows = $this->productQuickOptionModel->getOptionsForPos($productId, $categoryId);

        $options = [];
        foreach ($rows as $row) {
            $optionName = '';

            if ($locale === 'en') {
                $optionName = trim((string) ($row['option_name_en'] ?? ''));
                if ($optionName === '') {
                    $optionName = trim((string) ($row['option_name_th'] ?? ''));
                }
            } else {
                $optionName = trim((string) ($row['option_name_th'] ?? ''));
                if ($optionName === '') {
                    $optionName = trim((string) ($row['option_name_en'] ?? ''));
                }
            }

            if ($optionName === '') {
                $optionName = trim((string) ($row['option_name'] ?? ''));
            }

            if ($optionName === '') {
                continue;
            }

            $options[] = [
                'id'           => (int) ($row['id'] ?? 0),
                'option_name'  => $optionName,
                'price_adjust' => (float) ($row['price_adjust'] ?? 0),
                'sort_order'   => (int) ($row['sort_order'] ?? 0),
                'status'       => (int) ($row['status'] ?? 1),
            ];
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'type'    => 'product',
            'options' => $options,
        ]);
    }

    public function openOrder()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $tableId = (int) $this->request->getPost('table_id');

        if ($tableId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.table_not_found'),
            ]);
        }

        $check = $this->validateUsableTable($tableId);

        if (! $check['ok']) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $check['message'],
            ]);
        }

        $exists = $this->findCurrentOrderByTable($tableId);

        if ($exists) {
            return $this->response->setJSON([
                'status'   => 'success',
                'message'  => lang('app.order_already_open'),
                'order_id' => (int) $exists['id'],
            ]);
        }

        $orderNumber = 'ORD' . date('YmdHis') . rand(10, 99);

        $this->orderModel->insert([
            'tenant_id'       => $this->currentTenantId(),
            'branch_id'       => $this->getCurrentBranchId(),
            'table_id'        => $tableId,
            'order_number'    => $orderNumber,
            'order_type'      => 'dine_in',
            'status'          => 'open',
            'subtotal'        => 0,
            'discount_amount' => 0,
            'service_charge'  => 0,
            'vat_amount'      => 0,
            'total_price'     => 0,
            'opened_by'       => session()->get('user_id') ?: null,
            'opened_at'       => date('Y-m-d H:i:s'),
        ]);

        $orderId = (int) $this->orderModel->getInsertID();

        $this->updateScopedTableStatus($tableId, 'occupied');

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => lang('app.open_order_success'),
            'order_id' => $orderId,
        ]);
    }

    protected function buildMergeNoticeFromRow(array $merge): ?array
    {
        $branchId = $this->getCurrentBranchId();

        $sourceTableId    = (int) ($merge['source_table_id'] ?? 0);
        $targetTableId    = (int) ($merge['target_table_id'] ?? 0);
        $targetOrderId    = (int) ($merge['target_order_id'] ?? 0);
        $sourceTableName  = null;
        $targetTableName  = null;
        $targetOrderNo    = null;

        if ($sourceTableId > 0 && method_exists($this->tableModel, 'getTableMapByIds')) {
            $sourceMap       = $this->tableModel->getTableMapByIds([$sourceTableId], $branchId);
            $sourceTableName = $sourceMap[$sourceTableId]['table_name'] ?? null;
        }

        if ($targetTableId > 0 && method_exists($this->tableModel, 'getTableMapByIds')) {
            $targetMap      = $this->tableModel->getTableMapByIds([$targetTableId], $branchId);
            $targetTableName = $targetMap[$targetTableId]['table_name'] ?? null;
        }

        if ($targetOrderId > 0) {
            $targetOrder = $this->orderModel
                ->where('tenant_id', $this->currentTenantId())
                ->where('branch_id', $branchId)
                ->where('id', $targetOrderId)
                ->first();

            if ($targetOrder) {
                $targetOrderNo = $targetOrder['order_number'] ?? null;
            }
        }

        return [
            'source_order_id'     => (int) ($merge['source_order_id'] ?? 0),
            'target_order_id'     => $targetOrderId,
            'source_table_id'     => $sourceTableId,
            'target_table_id'     => $targetTableId,
            'source_table_name'   => $sourceTableName,
            'target_table_name'   => $targetTableName,
            'target_order_number' => $targetOrderNo,
            'reason'              => $merge['reason'] ?? null,
            'can_open_new_order'  => true,
        ];
    }

    protected function getLatestMergedNoticeByTable(int $tableId): ?array
    {
        if ($tableId <= 0 || ! $this->db->tableExists('order_merges')) {
            return null;
        }

        $builder = $this->orderMergeModel
            ->where('tenant_id', $this->currentTenantId())
            ->where('source_table_id', $tableId);

        $branchId = $this->getCurrentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'order_merges')) {
            $builder->groupStart()
                ->where('branch_id', $branchId)
                ->orWhere('branch_id', null)
                ->orWhere('branch_id', 0)
                ->groupEnd();
        }

        $merge = $builder->orderBy('id', 'DESC')->first();

        if (! $merge) {
            return null;
        }

        return $this->buildMergeNoticeFromRow($merge);
    }

    public function currentOrder($tableId)
    {
        if ($response = $this->jsonFeatureDenied('pos.access', 'app.plan_cannot_access_pos')) {
            return $response;
        }

        $tableId = (int) $tableId;

        $table = $this->getScopedTable($tableId);
        if (! $table) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.table_not_found'),
            ]);
        }

        $order = $this->findCurrentOrderByTable($tableId);

        if (! $order) {
            return $this->response->setJSON([
                'status'        => 'empty',
                'merged_notice' => $this->getLatestMergedNoticeByTable($tableId),
            ]);
        }

        $this->recalculateOrderTotal((int) $order['id']);
        $order = $this->getScopedOrder((int) $order['id']);

        $items = $this->orderItemModel->getByOrder((int) $order['id']);
        $items = array_map(fn (array $item): array => $this->normalizeOrderItemRowForResponse($item), $items);

        return $this->response->setJSON([
            'status'        => 'success',
            'order'         => $order,
            'items'         => $items,
            'merged_notice' => null,
        ]);
    }

    public function addItem()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $orderId    = (int) $this->request->getPost('order_id');
        $productId  = (int) $this->request->getPost('product_id');
        $note       = trim((string) $this->request->getPost('note'));
        $itemDetail = trim((string) $this->request->getPost('item_detail'));
        $options    = $this->decodePostedOptions($this->request->getPost('options'));

        $order = $this->getScopedOrder($orderId, ['open', 'billing']);
        if (! $order || ! in_array(($order['status'] ?? ''), ['open', 'billing'], true)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.open_order_not_found'),
            ]);
        }

        $product = $this->getScopedProduct($productId);
        if (! $product) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.product_not_found'),
            ]);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $productName = $product['product_name'] ?? $product['name'] ?? '';
            $basePrice   = (float) ($product['price'] ?? 0);

            $optionData    = $this->buildOrderItemOptionPayload($options, $itemDetail);
            $optionRows    = $optionData['rows'];
            $optionSummary = (string) $optionData['summary'];
            $optionTotal   = (float) $optionData['total_adjust'];

            $finalPrice = $basePrice + $optionTotal;

            $this->orderItemModel->insert([
                'tenant_id'      => $this->currentTenantId(),
                'branch_id'      => $this->getCurrentBranchId() ?: null,
                'order_id'       => $orderId,
                'product_id'     => $productId,
                'product_name'   => $productName,
                'item_detail'    => $itemDetail !== '' ? $itemDetail : null,
                'option_price'   => $optionTotal,
                'option_summary' => $optionSummary !== '' ? $optionSummary : null,
                'price'          => $finalPrice,
                'qty'            => 1,
                'line_total'     => 0,
                'note'           => $note !== '' ? $note : null,
                'status'         => 'pending',
            ]);

            $orderItemId = (int) $this->orderItemModel->getInsertID();

            foreach ($optionRows as $optRow) {
                $this->orderItemOptionModel->insert([
                    'order_item_id' => $orderItemId,
                    'option_group'  => $optRow['option_group'],
                    'option_name'   => $optRow['option_name'],
                    'price_adjust'  => $optRow['price_adjust'],
                ]);
            }

            $this->recalculateOrderTotal($orderId);

            if ($db->transStatus() === false) {
                $db->transRollback();

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.add_item_failed'),
                ]);
            }

            $db->transCommit();

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => lang('app.add_item_success'),
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'addItem error: ' . $e->getMessage());

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.add_item_error'),
            ]);
        }
    }

    public function updateItemQty()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        $type   = trim((string) ($this->request->getPost('type') ?? ''));

        if ($itemId <= 0 || ! in_array($type, ['plus', 'minus'], true)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.invalid_data'),
            ]);
        }

        $check = $this->validateEditableItem($itemId);

        if (! $check['ok']) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $check['message'],
            ]);
        }

        $item = $check['item'];

        $qty = (int) ($item['qty'] ?? 0);
        $qty = ($type === 'plus') ? ($qty + 1) : ($qty - 1);

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            if ($qty <= 0) {
                $this->deleteScopedOrderItemOptions($itemId);
                $this->orderItemModel->deleteScoped($itemId);
            } else {
                $status = $this->normalizeOrderItemStatus($item['status'] ?? 'pending');

                $lineTotal = $this->isNonBillableOrderItemStatus($status)
                    ? 0
                    : ((float) ($item['price'] ?? 0) * $qty);

                $this->orderItemModel->update($itemId, [
                    'qty'        => $qty,
                    'line_total' => $lineTotal,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->recalculateOrderTotal((int) $item['order_id']);

            if ($db->transStatus() === false) {
                $db->transRollback();

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.save_failed'),
                ]);
            }

            $db->transCommit();

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => lang('app.update_item_success'),
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'updateItemQty error: ' . $e->getMessage());

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.save_error'),
            ]);
        }
    }

    public function removeItem()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $itemId = (int) ($this->request->getPost('item_id') ?? 0);

        if ($itemId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.invalid_data'),
            ]);
        }

        $check = $this->validateEditableItem($itemId);

        if (! $check['ok']) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $check['message'],
            ]);
        }

        $item    = $check['item'];
        $orderId = (int) $item['order_id'];

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $this->deleteScopedOrderItemOptions($itemId);
            $this->orderItemModel->deleteScoped($itemId);

            $this->recalculateOrderTotal($orderId);

            if ($db->transStatus() === false) {
                $db->transRollback();

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.remove_item_failed'),
                ]);
            }

            $db->transCommit();

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => lang('app.remove_item_success'),
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'removeItem error: ' . $e->getMessage());

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.remove_item_error'),
            ]);
        }
    }

    public function sendKitchen()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $orderId     = (int) $this->request->getPost('order_id');
        $tenantId    = $this->currentTenantId();
        $branchId    = $this->getCurrentBranchId();
        $requestUuid = trim((string) ($this->request->getPost('request_uuid') ?: $this->request->getHeaderLine('X-Idempotency-Key')));

        $order = $this->getScopedOrder($orderId);

        if (! $order) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        if (($order['status'] ?? '') !== 'open') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_cannot_send_kitchen'),
            ]);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $params = [$orderId, $tenantId];
            $sql = "SELECT id
                    FROM orders
                    WHERE id = ?
                      AND tenant_id = ?";

            if ($branchId > 0) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $sql .= " AND status = 'open' FOR UPDATE";

            $lockedOrder = $db->query($sql, $params)->getRowArray();

            if (! $lockedOrder) {
                $db->transRollback();

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.order_not_found'),
                ]);
            }

            if ($requestUuid !== '') {
                $existingTicket = $this->kitchenTicketModel->findByRequestUuid($tenantId, $orderId, $requestUuid);

                if ($existingTicket) {
                    $db->transCommit();

                    return $this->response->setJSON([
                        'status'    => 'success',
                        'message'   => lang('app.send_kitchen_success'),
                        'ticket_no' => $existingTicket['ticket_no'] ?? '',
                        'duplicate' => true,
                    ]);
                }
            }

            $pendingItems = $this->orderItemModel->lockPendingByOrder($tenantId, $orderId);

            if (empty($pendingItems)) {
                $db->transRollback();

                return $this->response->setJSON([
                    'status'  => 'warning',
                    'message' => lang('app.no_pending_items_to_kitchen'),
                ]);
            }

            $batchNo  = $this->kitchenTicketModel->getNextBatchNo($tenantId, $orderId);
            $ticketNo = 'KT' . date('YmdHis') . str_pad((string) $batchNo, 2, '0', STR_PAD_LEFT);

            $this->kitchenTicketModel->insert([
                'tenant_id'           => $tenantId,
                'branch_id'           => $branchId > 0 ? $branchId : null,
                'order_id'            => $orderId,
                'ticket_no'           => $ticketNo,
                'status'              => 'new',
                'source_request_uuid' => $requestUuid !== '' ? $requestUuid : null,
                'dispatch_batch_no'   => $batchNo,
                'item_count'          => count($pendingItems),
                'created_by'          => $this->currentUserId(),
            ]);

            $ticketId = (int) $this->kitchenTicketModel->getInsertID();
            $now      = date('Y-m-d H:i:s');

            foreach ($pendingItems as $item) {
                $itemId = (int) ($item['id'] ?? 0);

                if ($itemId <= 0) {
                    continue;
                }

                $this->orderItemModel->update($itemId, [
                    'status'            => 'sent',
                    'kitchen_ticket_id' => $ticketId,
                    'sent_at'           => $now,
                    'line_total'        => ((float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 0)),
                    'updated_at'        => $now,
                ]);

                $this->kitchenLogModel->addLog(
                    $itemId,
                    'new',
                    lang('app.sent_to_kitchen_log'),
                    [
                        'tenant_id'     => $tenantId,
                        'branch_id'     => $branchId > 0 ? $branchId : null,
                        'order_id'      => $orderId,
                        'ticket_id'     => $ticketId,
                        'from_status'   => (string) ($item['status'] ?? 'pending'),
                        'to_status'     => 'sent',
                        'action_by'     => $this->currentUserId(),
                        'action_source' => 'pos.send_kitchen',
                        'request_uuid'  => $requestUuid !== '' ? $requestUuid : null,
                        'meta_json'     => [
                            'product_id'   => (int) ($item['product_id'] ?? 0),
                            'product_name' => (string) ($item['product_name'] ?? ''),
                            'qty'          => (int) ($item['qty'] ?? 0),
                        ],
                    ]
                );
            }

            $this->recalculateOrderTotal($orderId);

            if ($db->transStatus() === false) {
                $db->transRollback();

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.send_kitchen_failed'),
                ]);
            }

            $db->transCommit();

            $this->writeAuditLog([
                'branch_id'    => $branchId > 0 ? $branchId : null,
                'target_type'  => 'order',
                'target_id'    => $orderId,
                'action_key'   => 'pos.send_kitchen',
                'action_label' => lang('app.audit_log_sent_to_kitchen'),
                'ref_code'     => (string) $ticketNo,
                'order_id'     => $orderId,
                'table_id'     => (int) ($order['table_id'] ?? 0) ?: null,
                'meta_json'    => [
                    'ticket_no'   => (string) $ticketNo,
                    'ticket_id'   => (int) $ticketId,
                    'batch_no'    => (int) $batchNo,
                    'item_count'  => count($pendingItems),
                ],
            ]);

            return $this->response->setJSON([
                'status'    => 'success',
                'message'   => lang('app.send_kitchen_success'),
                'ticket_no' => $ticketNo,
                'ticket_id' => $ticketId,
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'sendKitchen error: ' . $e->getMessage());

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.send_kitchen_error'),
            ]);
        }
    }

    public function pay()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $orderId       = (int) $this->request->getPost('order_id');

        if ($response = $this->ensurePermissionOrManagerOverride('cashier.pay', 'pay', $orderId)) {
            return $response;
        }
        $paymentMethod = trim((string) $this->request->getPost('payment_method'));
        $amount        = (float) $this->request->getPost('amount');

        $order = $this->getScopedOrder($orderId);
        if (! $order) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        if (! in_array(($order['status'] ?? ''), ['open', 'billing'], true)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_cannot_pay'),
            ]);
        }

        $total = $this->getEffectiveOrderTotal($orderId);

        if ($amount <= 0) {
            $amount = $total;
        }

        if ($amount < $total) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.payment_amount_not_enough'),
            ]);
        }

        $change = $amount - $total;

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $now    = date('Y-m-d H:i:s');
            $userId = session()->get('user_id') ?: null;

            $this->paymentModel->insert([
                'order_id'       => $orderId,
                'payment_method' => $paymentMethod ?: 'cash',
                'amount'         => $amount,
                'received_by'    => $userId,
                'change_amount'  => $change,
                'paid_at'        => $now,
            ]);

            $this->orderModel->update($orderId, [
                'status'     => 'paid',
                'subtotal'   => $total,
                'total_price' => $total,
                'paid_by'    => $userId,
                'paid_at'    => $now,
                'closed_by'  => ! empty($order['closed_by']) ? $order['closed_by'] : $userId,
                'closed_at'  => ! empty($order['closed_at']) ? $order['closed_at'] : $now,
                'updated_at' => $now,
            ]);

            $reservation = null;

            if (! empty($order['reservation_id'])) {
                $reservation = $this->reservationModel->getFull((int) $order['reservation_id'], $this->getCurrentBranchId());

                if ($reservation && (int) ($reservation['tenant_id'] ?? 0) !== $this->currentTenantId()) {
                    $reservation = null;
                }
            }

            if (! $reservation) {
                $reservation = $this->findScopedReservationByOrder($orderId);
            }

            if ($reservation && ! in_array(($reservation['status'] ?? ''), ['completed', 'cancelled', 'no_show'], true)) {
                $oldStatus = $reservation['status'] ?? null;

                $updateData = [
                    'status'     => 'completed',
                    'updated_by' => $userId,
                ];

                if ($this->reservationFieldExists('completed_at')) {
                    $updateData['completed_at'] = $now;
                }

                $this->reservationModel->update((int) $reservation['id'], $updateData);

                if (method_exists($this->reservationLogModel, 'addLog')) {
                    $this->reservationLogModel->addLog(
                        (int) $reservation['id'],
                        'complete',
                        $oldStatus,
                        'completed',
                        lang('app.reservation_completed_after_payment')
                    );
                }
            }

            if (! empty($order['table_id'])) {
                $tableId = (int) $order['table_id'];
                $table   = $this->tableModel->getTableMapByIds([$tableId], $this->getCurrentBranchId())[$tableId] ?? null;

                if ($table) {
                    $tableTenantId = (int) ($table['tenant_id'] ?? 0);

                    if ($tableTenantId === 0 || $tableTenantId === $this->currentTenantId()) {
                        $newStatus = ((int) ($table['is_active'] ?? 0) === 1) ? 'available' : 'disabled';
                        $this->updateScopedTableStatus($tableId, $newStatus);
                    }
                }
            }

            if ($db->transStatus() === false) {
                $db->transRollback();

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.payment_failed'),
                ]);
            }

            $db->transCommit();

            $paymentId = (int) $this->paymentModel->getInsertID();

            $this->writeAuditLog([
                'branch_id'    => (int) ($order['branch_id'] ?? 0) ?: null,
                'target_type'  => 'payment',
                'target_id'    => $paymentId > 0 ? $paymentId : $orderId,
                'action_key'   => 'cashier.pay',
                'action_label' => lang('app.audit_log_payment_received'),
                'ref_code'     => (string) ($order['order_number'] ?? ''),
                'order_id'     => $orderId,
                'table_id'     => (int) ($order['table_id'] ?? 0) ?: null,
                'payment_id'   => $paymentId > 0 ? $paymentId : null,
                'meta_json'    => [
                    'payment_method' => (string) ($paymentMethod ?: 'cash'),
                    'amount'         => (float) $amount,
                    'total'          => (float) $total,
                    'change'         => (float) $change,
                    'override_by'    => session('override_approved_by_name') ?? null,
                ],
            ]);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => lang('app.payment_success'),
                'change'  => $change,
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'pay error: ' . $e->getMessage());

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.payment_error'),
            ]);
        }
    }

    protected function recalculateOrder($orderId)
    {
        $this->recalculateOrderTotal((int) $orderId);
    }

    protected function orderItemsFieldExists(string $field): bool
    {
        try {
            return $this->db->fieldExists($field, 'order_items');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function isCancelRequestFlowEnabled(): bool
    {
        return $this->orderItemsFieldExists('cancel_request_status');
    }

    protected function buildCancelRequestPayload(string $requestStatus, ?string $note = null, ?string $prevStatus = null): array
    {
        $now    = date('Y-m-d H:i:s');
        $userId = $this->currentUserId() ?: null;
        $data   = [];

        if ($this->orderItemsFieldExists('cancel_request_status')) {
            $data['cancel_request_status'] = $requestStatus;
        }

        if ($this->orderItemsFieldExists('updated_at')) {
            $data['updated_at'] = $now;
        }

        if ($requestStatus === 'pending') {
            if ($this->orderItemsFieldExists('cancel_requested_at')) {
                $data['cancel_requested_at'] = $now;
            }
            if ($this->orderItemsFieldExists('cancel_requested_by')) {
                $data['cancel_requested_by'] = $userId;
            }
            if ($this->orderItemsFieldExists('cancel_request_prev_status')) {
                $data['cancel_request_prev_status'] = $prevStatus ?: 'sent';
            }
            if ($note !== null && $note !== '') {
                if ($this->orderItemsFieldExists('cancel_request_note')) {
                    $data['cancel_request_note'] = $note;
                } elseif ($this->orderItemsFieldExists('cancel_request_reason')) {
                    $data['cancel_request_reason'] = $note;
                }
            }
            foreach ([
                'cancel_decided_at',
                'cancel_decided_by',
                'cancel_rejected_at',
                'cancel_rejected_by',
                'cancel_rejected_note',
                'cancel_rejected_reason',
            ] as $field) {
                if ($this->orderItemsFieldExists($field)) {
                    $data[$field] = null;
                }
            }
        } elseif ($requestStatus === 'rejected') {
            if ($this->orderItemsFieldExists('cancel_decided_at')) {
                $data['cancel_decided_at'] = $now;
            }
            if ($this->orderItemsFieldExists('cancel_decided_by')) {
                $data['cancel_decided_by'] = $userId;
            }
            if ($note !== null && $note !== '') {
                if ($this->orderItemsFieldExists('cancel_rejected_note')) {
                    $data['cancel_rejected_note'] = $note;
                } elseif ($this->orderItemsFieldExists('cancel_rejected_reason')) {
                    $data['cancel_rejected_reason'] = $note;
                }
            }
        } elseif ($requestStatus === 'approved') {
            if ($this->orderItemsFieldExists('cancel_decided_at')) {
                $data['cancel_decided_at'] = $now;
            }
            if ($this->orderItemsFieldExists('cancel_decided_by')) {
                $data['cancel_decided_by'] = $userId;
            }
        }

        return $data;
    }

    protected function buildCancelRequestClearPayload(): array
    {
        $data = [];
        foreach ([
            'cancel_request_status',
            'cancel_requested_at',
            'cancel_requested_by',
            'cancel_request_note',
            'cancel_request_reason',
            'cancel_request_prev_status',
            'cancel_decided_at',
            'cancel_decided_by',
            'cancel_rejected_at',
            'cancel_rejected_by',
            'cancel_rejected_note',
            'cancel_rejected_reason',
        ] as $field) {
            if ($this->orderItemsFieldExists($field)) {
                $data[$field] = null;
            }
        }

        if ($this->orderItemsFieldExists('updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    public function updateItemStatus()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        $status = strtolower(trim((string) ($this->request->getPost('status') ?? '')));
        $note   = trim((string) ($this->request->getPost('note') ?? ''));

        $allowedStatuses = ['pending', 'sent', 'preparing', 'cooking', 'ready', 'served', 'cancelled', 'cancel', 'cancel_requested'];

        if ($itemId <= 0 || ! in_array($status, $allowedStatuses, true)) {
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

        $currentStatus = $this->normalizeOrderItemStatus($item['status'] ?? 'pending');

        $directCancelFromPending = false;

        if ($status === 'cancel_requested') {
            if ($currentStatus === 'pending') {
                $status = 'cancel';
                $directCancelFromPending = true;
            } elseif ($this->isCancelRequestFlowEnabled()) {
                $requestData = $this->buildCancelRequestPayload('pending', $note, (string) ($item['status'] ?? 'sent'));

                if (empty($requestData)) {
                    return $this->response->setJSON([
                        'status'  => 'error',
                        'message' => lang('app.save_failed'),
                    ]);
                }

                $builder = $this->db->table('order_items')->where('id', $itemId);

                if ($this->orderItemsFieldExists('tenant_id')) {
                    $builder->where('tenant_id', (int) ($item['tenant_id'] ?? $this->currentTenantId()));
                }

                if ($this->orderItemsFieldExists('branch_id') && array_key_exists('branch_id', $item)) {
                    $branchId = (int) ($item['branch_id'] ?? 0);
                    if ($branchId > 0) {
                        $builder->where('branch_id', $branchId);
                    }
                }

                if (! $builder->update($requestData)) {
                    return $this->response->setJSON([
                        'status'  => 'error',
                        'message' => lang('app.save_failed'),
                    ]);
                }

                try {
                    if (method_exists($this->kitchenLogModel, 'addLog')) {
                        $this->kitchenLogModel->addLog(
                            $itemId,
                            'cancel',
                            lang('app.kitchen_status_updated'),
                            [
                                'branch_id'     => $this->getCurrentBranchId(),
                                'order_id'      => (int) ($item['order_id'] ?? 0),
                                'ticket_id'     => (int) ($item['kitchen_ticket_id'] ?? 0),
                                'from_status'   => (string) ($item['status'] ?? ''),
                                'to_status'     => 'cancel_requested',
                                'action_by'     => (int) (session('user_id') ?? 0),
                                'action_source' => 'pos.cancel_request',
                            ]
                        );
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'updateItemStatus cancel request log error: ' . $e->getMessage());
                }

                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => lang('app.save_success'),
                    'mode'    => 'cancel_requested',
                ]);
            } else {
                $status = 'cancel';
            }
        }

        $storedStatus = $status;
        if ($storedStatus === 'cooking') {
            $storedStatus = 'preparing';
        }
        if (in_array($storedStatus, ['cancel', 'cancelled'], true)) {
            $storedStatus = 'cancel';
        }

        $data = [
            'status'     => $storedStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($storedStatus === 'preparing' && $this->db->fieldExists('started_at', 'order_items')) {
            $data['started_at'] = date('Y-m-d H:i:s');
        }

        if ($storedStatus === 'ready' && $this->db->fieldExists('ready_at', 'order_items')) {
            $data['ready_at'] = date('Y-m-d H:i:s');
        }

        if ($storedStatus === 'served' && $this->db->fieldExists('served_at', 'order_items')) {
            $data['served_at'] = date('Y-m-d H:i:s');
        }

        if ($storedStatus === 'cancel') {
            if ($this->db->fieldExists('cancelled_at', 'order_items')) {
                $data['cancelled_at'] = date('Y-m-d H:i:s');
            }
            if ($this->db->fieldExists('cancelled_by', 'order_items')) {
                $data['cancelled_by'] = $this->currentUserId();
            }
        }

        if ($this->isNonBillableOrderItemStatus($storedStatus)) {
            $data['line_total'] = 0;
        } else {
            $data['line_total'] = ((float) ($item['price'] ?? 0)) * (int) ($item['qty'] ?? 0);
        }

        if ($this->isCancelRequestFlowEnabled()) {
            $data = array_merge($data, $this->buildCancelRequestClearPayload());
        }

        if (! $this->orderItemModel->update($itemId, $data)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.save_failed'),
            ]);
        }

        $logType = $storedStatus;
        if ($logType === 'preparing') {
            $logType = 'cooking';
        }
        if ($logType === 'cancelled') {
            $logType = 'cancel';
        }

        if (isset($this->kitchenLogModel) && $this->kitchenLogModel) {
            try {
                if (method_exists($this->kitchenLogModel, 'addLog')) {
                    $this->kitchenLogModel->addLog(
                        $itemId,
                        $logType,
                        lang('app.kitchen_status_updated')
                    );
                } else {
                    $this->kitchenLogModel->insert([
                        'tenant_id'     => (int) ($item['tenant_id'] ?? current_tenant_id()),
                        'order_id'      => (int) ($item['order_id'] ?? 0),
                        'order_item_id' => $itemId,
                        'status'        => $logType,
                        'note'          => lang('app.kitchen_status_updated'),
                        'created_by'    => (int) (session('user_id') ?? 0),
                    ]);
                }
            } catch (\Throwable $e) {
                log_message('error', 'updateItemStatus kitchen log error: ' . $e->getMessage());
            }
        }

        $this->recalculateOrderTotal((int) ($item['order_id'] ?? 0));

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => $directCancelFromPending
                ? (lang('app.cancelled') ?: lang('app.canceled'))
                : lang('app.save_success'),
            'mode'    => $directCancelFromPending ? 'cancelled_direct' : 'updated',
        ]);
    }


    public function requestBill()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $orderId = (int) $this->request->getPost('order_id');

        if ($response = $this->ensurePermissionOrManagerOverride('cashier.request_bill', 'request_bill', $orderId)) {
            return $response;
        }

        $order = $this->getScopedOrder($orderId);
        if (! $order) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        if (($order['status'] ?? '') !== 'open') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_cannot_request_bill'),
            ]);
        }

        $this->recalculateOrderTotal($orderId);

        $this->orderModel->update($orderId, [
            'status'     => 'billing',
            'closed_by'  => session()->get('user_id') ?: null,
            'closed_at'  => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->writeAuditLog([
            'branch_id'    => (int) ($order['branch_id'] ?? 0) ?: null,
            'target_type'  => 'order',
            'target_id'    => $orderId,
            'action_key'   => 'cashier.request_bill',
            'action_label' => lang('app.audit_log_request_bill'),
            'ref_code'     => (string) ($order['order_number'] ?? ''),
            'order_id'     => $orderId,
            'table_id'     => (int) ($order['table_id'] ?? 0) ?: null,
            'meta_json'    => [
                'from_status' => 'open',
                'to_status'   => 'billing',
                'override_by' => session('override_approved_by_name') ?? null,
            ],
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => lang('app.enter_billing_status'),
        ]);
    }

    public function closeBill()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $orderId = (int) $this->request->getPost('order_id');

        if ($response = $this->ensurePermissionOrManagerOverride('cashier.close_bill', 'close_bill', $orderId)) {
            return $response;
        }

        $order = $this->getScopedOrder($orderId);
        if (! $order) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        if (($order['status'] ?? '') === 'paid') {
            return $this->response->setJSON([
                'status'  => 'warning',
                'message' => lang('app.order_already_paid'),
            ]);
        }

        if (($order['status'] ?? '') !== 'open') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_cannot_close_bill'),
            ]);
        }

        $this->recalculateOrderTotal($orderId);

        $this->orderModel->update($orderId, [
            'status'     => 'billing',
            'closed_by'  => session()->get('user_id') ?: null,
            'closed_at'  => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->writeAuditLog([
            'branch_id'    => (int) ($order['branch_id'] ?? 0) ?: null,
            'target_type'  => 'order',
            'target_id'    => $orderId,
            'action_key'   => 'cashier.close_bill',
            'action_label' => lang('app.audit_log_close_bill'),
            'ref_code'     => (string) ($order['order_number'] ?? ''),
            'order_id'     => $orderId,
            'table_id'     => (int) ($order['table_id'] ?? 0) ?: null,
            'meta_json'    => [
                'from_status' => 'open',
                'to_status'   => 'billing',
                'override_by' => session('override_approved_by_name') ?? null,
            ],
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => lang('app.close_bill_success_billing'),
        ]);
    }


    public function managerOverride()
    {
        if ($response = $this->jsonFeatureDenied('pos.access', 'app.plan_cannot_access_pos')) {
            return $response;
        }

        $actionKey = $this->normalizeOverrideAction((string) $this->request->getPost('action_key'));
        $orderId = (int) $this->request->getPost('order_id');
        $managerUsername = (string) $this->request->getPost('manager_username');
        $managerPinCode = (string) $this->request->getPost('manager_pin_code');

        if ($actionKey === '' || $orderId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.invalid_request'),
            ]);
        }

        $order = $this->getScopedOrder($orderId);
        if (! $order) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        $permissionKey = $this->getOverridePermissionKey($actionKey);
        if ($permissionKey === '') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.invalid_request'),
            ]);
        }

        $approver = $this->findManagerOverrideApprover($managerUsername, $managerPinCode);
        if (! $approver) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.manager_override_invalid_credentials'),
            ]);
        }

        if ((int) ($approver['id'] ?? 0) === (int) (session('user_id') ?? 0)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.manager_override_self_not_allowed'),
            ]);
        }

        if (! $this->approverCanOverride($approver, $permissionKey)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.manager_override_not_allowed'),
            ]);
        }

        $this->storeManagerOverrideApproval($actionKey, $orderId, $approver);

        $this->writeAuditLog([
            'branch_id'    => (int) ($order['branch_id'] ?? 0) ?: null,
            'user_id'      => (int) ($approver['id'] ?? 0) ?: null,
            'actor_name'   => (string) ($approver['full_name'] ?? $approver['username'] ?? $this->currentActorName()),
            'target_type'  => 'manager_override',
            'target_id'    => $orderId,
            'action_key'   => 'cashier.manager_override',
            'action_label' => lang('app.audit_log_manager_override'),
            'ref_code'     => (string) ($order['order_number'] ?? ''),
            'order_id'     => $orderId,
            'table_id'     => (int) ($order['table_id'] ?? 0) ?: null,
            'meta_json'    => [
                'override_action' => $actionKey,
                'approved_for_user_id' => (int) (session('user_id') ?? 0),
                'approved_for_user_name' => $this->currentActorName(),
            ],
        ]);

        return $this->response->setJSON([
            'status'           => 'success',
            'message'          => lang('app.manager_override_approved'),
            'approved_by'      => (string) ($approver['full_name'] ?? $approver['username'] ?? '-'),
            'action_key'       => $actionKey,
            'permission_key'   => $permissionKey,
            'approved_order_id'=> $orderId,
        ]);
    }

    protected function validateEditableItem(int $itemId): array
    {
        $item = $this->getScopedOrderItem($itemId);
        if (! $item) {
            return [
                'ok'      => false,
                'message' => lang('app.item_not_found'),
            ];
        }

        $order = $this->getScopedOrder((int) $item['order_id']);
        if (! $order) {
            return [
                'ok'      => false,
                'message' => lang('app.order_not_found'),
            ];
        }

        if (($order['status'] ?? '') !== 'open') {
            return [
                'ok'      => false,
                'message' => lang('app.order_cannot_edit_items'),
            ];
        }

        if (($item['status'] ?? '') !== 'pending') {
            return [
                'ok'      => false,
                'message' => lang('app.item_not_pending_cannot_edit'),
            ];
        }

        return [
            'ok'    => true,
            'item'  => $item,
            'order' => $order,
        ];
    }

    protected function validateUsableTable(int $tableId): array
    {
        $table = $this->getScopedTable($tableId);

        if (! $table) {
            return [
                'ok'      => false,
                'message' => lang('app.table_not_found'),
            ];
        }

        if ((int) ($table['is_active'] ?? 0) !== 1) {
            return [
                'ok'      => false,
                'message' => lang('app.table_disabled'),
                'table'   => $table,
            ];
        }

        if (($table['status'] ?? '') === 'disabled') {
            return [
                'ok'      => false,
                'message' => lang('app.table_disabled'),
                'table'   => $table,
            ];
        }

        if (($table['status'] ?? '') === 'cleaning') {
            return [
                'ok'      => false,
                'message' => lang('app.table_cleaning'),
                'table'   => $table,
            ];
        }

        return [
            'ok'    => true,
            'table' => $table,
        ];
    }

    protected function recalculateOrderTotal(int $orderId): void
    {
        $order = $this->getScopedOrder($orderId);

        if (! $order) {
            return;
        }

        $tenantId = $this->currentTenantId();
        $branchId = $this->getCurrentBranchId();

        $builder = $this->orderItemModel
            ->where('order_id', $orderId);

        if ($this->db->fieldExists('tenant_id', 'order_items')) {
            $builder->groupStart()
                ->where('tenant_id', $tenantId)
                ->orWhere('tenant_id', null)
                ->orWhere('tenant_id', 0)
                ->groupEnd();
        }

        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'order_items')) {
            $builder->groupStart()
                ->where('branch_id', $branchId)
                ->orWhere('branch_id', null)
                ->orWhere('branch_id', 0)
                ->groupEnd();
        }

        $items = $builder->findAll();

        $subtotal = 0.0;
        $now      = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $qty          = (int) ($item['qty'] ?? 0);
            $unitPrice    = (float) ($item['price'] ?? 0);
            $status       = $this->normalizeOrderItemStatus($item['status'] ?? '');
            $lineTotal    = $this->isNonBillableOrderItemStatus($status) ? 0.0 : ($unitPrice * $qty);

            $this->orderItemModel->update((int) $item['id'], [
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

    public function updateItemNote()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        $note   = trim((string) ($this->request->getPost('note') ?? ''));

        if ($itemId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.invalid_data'),
            ]);
        }

        $check = $this->validateEditableItem($itemId);

        if (! $check['ok']) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $check['message'],
            ]);
        }

        $this->orderItemModel->update($itemId, [
            'note'       => $note,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => lang('app.update_note_success'),
        ]);
    }

    public function updateItemDetail()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $itemId     = (int) ($this->request->getPost('item_id') ?? 0);
        $itemDetail = trim((string) ($this->request->getPost('item_detail') ?? ''));

        if ($itemId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.invalid_data'),
            ]);
        }

        $check = $this->validateEditableItem($itemId);

        if (! $check['ok']) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $check['message'],
            ]);
        }

        $this->orderItemModel->update($itemId, [
            'item_detail' => $itemDetail ?: null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => lang('app.update_detail_success'),
        ]);
    }

    public function updateItem()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $itemId     = (int) $this->request->getPost('item_id');
        $itemDetail = trim((string) $this->request->getPost('item_detail'));
        $note       = trim((string) $this->request->getPost('note'));
        $options    = $this->decodePostedOptions($this->request->getPost('options'));

        if ($itemId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.item_not_found'),
            ]);
        }

        $check = $this->validateEditableItem($itemId);
        if (! $check['ok']) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $check['message'],
            ]);
        }

        $item = $check['item'];

        $product = $this->getScopedProduct((int) $item['product_id']);
        if (! $product) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.product_not_found'),
            ]);
        }

        $qty       = (int) ($item['qty'] ?? 1);
        $basePrice = (float) ($product['price'] ?? 0);

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $optionData    = $this->buildOrderItemOptionPayload($options, $itemDetail);
            $optionRows    = $optionData['rows'];
            $optionSummary = (string) $optionData['summary'];
            $optionTotal   = (float) $optionData['total_adjust'];

            $newPrice  = $basePrice + $optionTotal;
            $lineTotal = $newPrice * $qty;

            $this->orderItemModel->update($itemId, [
                'item_detail'    => $itemDetail !== '' ? $itemDetail : null,
                'option_price'   => $optionTotal,
                'option_summary' => $optionSummary !== '' ? $optionSummary : null,
                'price'          => $newPrice,
                'line_total'     => 0,
                'note'           => $note !== '' ? $note : null,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            $this->deleteScopedOrderItemOptions($itemId);

            foreach ($optionRows as $optRow) {
                $this->orderItemOptionModel->insert([
                    'order_item_id' => $itemId,
                    'option_group'  => $optRow['option_group'],
                    'option_name'   => $optRow['option_name'],
                    'price_adjust'  => $optRow['price_adjust'],
                ]);
            }

            if (! $this->isNonBillableOrderItemStatus($item['status'] ?? 'pending')) {
                $this->orderItemModel->update($itemId, [
                    'line_total' => $lineTotal,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $this->recalculateOrderTotal((int) $item['order_id']);

            if ($db->transStatus() === false) {
                $db->transRollback();

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.update_item_failed'),
                ]);
            }

            $db->transCommit();

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => lang('app.updated_success'),
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'updateItem error: ' . $e->getMessage());

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.update_item_failed'),
            ]);
        }
    }

    protected function getReservationLockMinutes(): int
    {
        return 60;
    }

    protected function getReservationActiveStatuses(): array
    {
        return ['pending', 'confirmed', 'seated'];
    }

    protected function findUpcomingReservationByTable(int $tableId)
    {
        $tenantId = (int) (session('tenant_id') ?? 0);
        $branchId = (int) (session('branch_id') ?? 0);

        if ($tenantId <= 0 || $branchId <= 0 || $tableId <= 0) {
            return null;
        }

        $rows = $this->reservationTableModel
            ->select('
                reservations.id,
                reservations.reservation_date,
                reservations.reservation_time,
                reservations.status,
                reservations.customer_name,
                reservations.customer_phone,
                NULL AS total_guest,
                reservation_tables.table_id
            ')
            ->join('reservations', 'reservations.id = reservation_tables.reservation_id', 'inner')
            ->where('reservation_tables.table_id', $tableId)
            ->where('reservations.tenant_id', $tenantId)
            ->where('reservations.branch_id', $branchId)
            ->whereIn('reservations.status', $this->getReservationActiveStatuses())
            ->orderBy('reservations.reservation_date', 'ASC')
            ->orderBy('reservations.reservation_time', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            $reservationDate = trim((string) ($row['reservation_date'] ?? ''));
            $reservationTime = trim((string) ($row['reservation_time'] ?? ''));

            if ($reservationDate === '' || $reservationTime === '') {
                continue;
            }

            $reservationDateTime = strtotime($reservationDate . ' ' . $reservationTime);
            if ($reservationDateTime === false) {
                continue;
            }

            if ($reservationDateTime >= time()) {
                return $row;
            }
        }

        return null;
    }

    protected function getUpcomingReservationLockInfo(int $tableId): array
    {
        $reservation = $this->findUpcomingReservationByTable($tableId);

        if (! $reservation) {
            return [
                'locked'       => false,
                'message'      => null,
                'reservation'  => null,
                'minutes_left' => null,
            ];
        }

        $reservationTs = strtotime(
            ($reservation['reservation_date'] ?? '') . ' ' .
            ($reservation['reservation_time'] ?? '')
        );

        $nowTs = time();

        $minutesLeft = null;

        if ($reservationTs && $reservationTs > $nowTs) {
            $minutesLeft = (int) floor(($reservationTs - $nowTs) / 60);
        }

        $message = lang('app.table_locked_for_reservation');

        if (! empty($reservation['customer_name']) || ! empty($reservation['reservation_time'])) {
            $message .= ' (' .
                trim(($reservation['customer_name'] ?: '-') . ' ' . lang('app.time') . ' ' . ($reservation['reservation_time'] ?: '-')) .
                ')';
        }

        return [
            'locked'       => true,
            'message'      => $message,
            'reservation'  => $reservation,
            'minutes_left' => $minutesLeft,
        ];
    }

    protected function reservationFieldExists(string $field): bool
    {
        try {
            return $this->reservationModel->db->fieldExists($field, 'reservations');
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function currentUserId(): int
    {
        return (int) (session('user_id') ?? 0);
    }

    protected function refreshTableStatusById(int $tableId): void
    {
        if ($tableId <= 0) {
            return;
        }

        $activeOrder = $this->findCurrentOrderByTable($tableId, ['open', 'billing']);

        $this->tableModel->update($tableId, [
            'status' => $activeOrder ? 'occupied' : 'available',
        ]);
    }

    public function availableMoveTables(int $orderId = 0)
    {
        $order = $this->getScopedOrder($orderId, ['open', 'billing']);

        if (! $order) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        $branchId = $this->getCurrentBranchId();

        $rows = $this->tableModel
            ->where('tenant_id', $this->currentTenantId())
            ->where('branch_id', $branchId)
            ->where('deleted_at', null)
            ->where('is_active', 1)
            ->where('id !=', (int) $order['table_id'])
            ->orderBy('table_name', 'ASC')
            ->findAll();

        $data = [];

        foreach ($rows as $row) {
            $otherOpen = $this->findCurrentOrderByTable((int) $row['id'], ['open', 'billing']);

            $data[] = [
                'id'             => (int) $row['id'],
                'table_name'     => (string) ($row['table_name'] ?? '-'),
                'status'         => (string) ($row['status'] ?? 'available'),
                'has_open_order' => $otherOpen ? 1 : 0,
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'tables' => $data,
        ]);
    }

    public function moveTable()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $orderId   = (int) $this->request->getPost('order_id');
        $toTableId = (int) $this->request->getPost('to_table_id');
        $reason    = trim((string) $this->request->getPost('reason'));

        $order = $this->getScopedOrder($orderId, ['open', 'billing']);

        if (! $order) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        $fromTableId = (int) ($order['table_id'] ?? 0);

        if ($fromTableId <= 0 || $toTableId <= 0 || $fromTableId === $toTableId) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.invalid_request'),
            ]);
        }

        $toTable = $this->getScopedTable($toTableId);

        if (! $toTable) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.table_not_found'),
            ]);
        }

        if ((int) ($toTable['is_active'] ?? 0) !== 1 || strtolower((string) ($toTable['status'] ?? '')) === 'disabled') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.table_disabled'),
            ]);
        }

        $existingAtDestination = $this->findCurrentOrderByTable($toTableId, ['open', 'billing']);
        if ($existingAtDestination) {
            return $this->response->setJSON([
                'status'  => 'error',
                'code'    => 'DESTINATION_HAS_OPEN_ORDER',
                'message' => lang('app.destination_table_has_open_bill'),
            ]);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $this->orderModel->update($orderId, [
                'table_id'   => $toTableId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->refreshTableStatusById($fromTableId);
            $this->refreshTableStatusById($toTableId);

            $this->orderTableMoveModel->insert([
                'tenant_id'     => $this->currentTenantId(),
                'branch_id'     => $this->getCurrentBranchId(),
                'order_id'      => $orderId,
                'from_table_id' => $fromTableId,
                'to_table_id'   => $toTableId,
                'moved_by'      => $this->currentUserId(),
                'reason'        => $reason !== '' ? $reason : null,
            ]);

            $latestTickets = $this->kitchenTicketModel->getByOrder($orderId);
            foreach ($latestTickets as $ticket) {
                $this->kitchenLogModel->addLog(
                    0,
                    'new',
                    'ย้ายโต๊ะจาก #' . $fromTableId . ' ไป #' . $toTableId,
                    [
                        'branch_id'     => $this->getCurrentBranchId(),
                        'order_id'      => $orderId,
                        'ticket_id'     => (int) ($ticket['id'] ?? 0),
                        'from_status'   => 'table:' . $fromTableId,
                        'to_status'     => 'table:' . $toTableId,
                        'action_by'     => $this->currentUserId(),
                        'action_source' => 'pos.move_table',
                        'meta_json'     => [
                            'from_table_id' => $fromTableId,
                            'to_table_id'   => $toTableId,
                            'reason'        => $reason,
                        ],
                    ]
                );
            }

            if ($db->transStatus() === false) {
                $db->transRollback();

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.save_failed'),
                ]);
            }

            $db->transCommit();

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => lang('app.move_table_success'),
                'data'    => [
                    'order_id'      => $orderId,
                    'from_table_id' => $fromTableId,
                    'to_table_id'   => $toTableId,
                ],
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'moveTable error: ' . $e->getMessage());

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.save_failed'),
            ]);
        }
    }

    public function availableMergeTargets(int $orderId = 0)
    {
        $order = $this->getScopedOrder($orderId, ['open', 'billing']);

        if (! $order) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        $branchId = $this->getCurrentBranchId();
        $tenantId = $this->currentTenantId();

        $builder = $this->orderModel
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->whereIn('status', ['open', 'billing'])
            ->where('id !=', (int) $order['id']);

        if ($this->db->fieldExists('merged_into_order_id', 'orders')) {
            $builder->groupStart()
                ->where('merged_into_order_id', null)
                ->orWhere('merged_into_order_id', 0)
                ->groupEnd();
        }

        $rows = $builder
            ->orderBy('id', 'DESC')
            ->findAll();

        $tableIds = [];
        foreach ($rows as $row) {
            $tableId = (int) ($row['table_id'] ?? 0);
            if ($tableId > 0) {
                $tableIds[] = $tableId;
            }
        }

        $tableMap = [];
        if (! empty($tableIds) && method_exists($this->tableModel, 'getTableMapByIds')) {
            $tableMap = $this->tableModel->getTableMapByIds(array_values(array_unique($tableIds)), $branchId);
        }

        $targets = [];

        foreach ($rows as $row) {
            $tableId   = (int) ($row['table_id'] ?? 0);
            $tableName = '-';

            if ($tableId > 0 && isset($tableMap[$tableId])) {
                $tableName = (string) ($tableMap[$tableId]['table_name'] ?? '-');
            }

            $targets[] = [
                'id'           => (int) ($row['id'] ?? 0),
                'order_id'     => (int) ($row['id'] ?? 0),
                'order_number' => (string) ($row['order_number'] ?? ''),
                'table_id'     => $tableId,
                'table_name'   => $tableName,
                'status'       => (string) ($row['status'] ?? 'open'),
                'total_price'  => (float) ($row['total_price'] ?? 0),
            ];
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'targets' => $targets,
        ]);
    }

    public function mergeBill()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $sourceOrderId = (int) ($this->request->getPost('source_order_id') ?? 0);
        $sourceTableId = (int) ($this->request->getPost('source_table_id') ?? 0);
        $targetOrderId = (int) ($this->request->getPost('target_order_id') ?? 0);
        $reason        = trim((string) ($this->request->getPost('reason') ?? ''));

        $sourceOrder = null;

        if ($sourceOrderId > 0) {
            $sourceOrder = $this->getScopedOrder($sourceOrderId, ['open', 'billing']);
        } elseif ($sourceTableId > 0) {
            $sourceOrder = $this->findCurrentOrderByTable($sourceTableId, ['open', 'billing']);
        }

        if (! $sourceOrder) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        $targetOrder = $this->getScopedOrder($targetOrderId, ['open', 'billing']);

        if (! $targetOrder) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.merge_target_order_not_found'),
            ]);
        }

        $sourceOrderId = (int) ($sourceOrder['id'] ?? 0);
        $targetOrderId = (int) ($targetOrder['id'] ?? 0);

        if ($sourceOrderId <= 0 || $targetOrderId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.invalid_request'),
            ]);
        }

        if ($sourceOrderId === $targetOrderId) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.cannot_merge_same_order'),
            ]);
        }

        $fromTableId = (int) ($sourceOrder['table_id'] ?? 0);
        $toTableId   = (int) ($targetOrder['table_id'] ?? 0);
        $now         = date('Y-m-d H:i:s');
        $tenantId    = $this->currentTenantId();
        $branchId    = $this->getCurrentBranchId();

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $builder = $this->orderItemModel
                ->where('order_id', $sourceOrderId);

            if ($this->db->fieldExists('tenant_id', 'order_items')) {
                $builder->groupStart()
                    ->where('tenant_id', $tenantId)
                    ->orWhere('tenant_id', null)
                    ->orWhere('tenant_id', 0)
                    ->groupEnd();
            }

            if ($branchId > 0 && $this->db->fieldExists('branch_id', 'order_items')) {
                $builder->groupStart()
                    ->where('branch_id', $branchId)
                    ->orWhere('branch_id', null)
                    ->orWhere('branch_id', 0)
                    ->groupEnd();
            }

            $items = $builder->findAll();

            if (empty($items)) {
                $db->transRollback();

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.merge_bill_failed'),
                ]);
            }

            foreach ($items as $item) {
                $updateItemData = [
                    'order_id'   => $targetOrderId,
                    'updated_at' => $now,
                ];

                if ($this->db->fieldExists('tenant_id', 'order_items')) {
                    $updateItemData['tenant_id'] = $tenantId;
                }

                if ($this->db->fieldExists('branch_id', 'order_items')) {
                    $updateItemData['branch_id'] = $branchId > 0 ? $branchId : null;
                }

                if (! $this->orderItemModel->update((int) $item['id'], $updateItemData)) {
                    log_message('error', 'mergeBill updateItem failed: ' . json_encode($this->orderItemModel->errors()));
                    throw new \RuntimeException('merge item update failed');
                }
            }

            if ($this->db->fieldExists('merged_into_order_id', 'orders')) {
                $okOrder = $this->orderModel->update($sourceOrderId, [
                    'status'               => 'merged',
                    'merged_into_order_id' => $targetOrderId,
                    'updated_at'           => $now,
                ]);
            } else {
                $okOrder = $this->orderModel->update($sourceOrderId, [
                    'status'     => 'merged',
                    'updated_at' => $now,
                ]);
            }

            if (! $okOrder) {
                log_message('error', 'mergeBill update source order failed: ' . json_encode($this->orderModel->errors()));
                throw new \RuntimeException('merge source order update failed');
            }

            if ($this->db->tableExists('order_merges')) {
                $okMerge = $this->orderMergeModel->insert([
                    'tenant_id'       => $tenantId,
                    'branch_id'       => $branchId,
                    'source_order_id' => $sourceOrderId,
                    'target_order_id' => $targetOrderId,
                    'source_table_id' => $fromTableId > 0 ? $fromTableId : null,
                    'target_table_id' => $toTableId > 0 ? $toTableId : null,
                    'merged_by'       => $this->currentUserId(),
                    'reason'          => $reason !== '' ? $reason : null,
                ]);

                if (! $okMerge) {
                    log_message('error', 'mergeBill insert merge log failed: ' . json_encode($this->orderMergeModel->errors()));
                    throw new \RuntimeException('merge log insert failed');
                }
            }

            $this->recalculateOrderTotal($targetOrderId);
            $this->recalculateOrderTotal($sourceOrderId);

            if ($fromTableId > 0) {
                $this->refreshTableStatusById($fromTableId);
            }

            if ($toTableId > 0) {
                $this->refreshTableStatusById($toTableId);
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                log_message('error', 'mergeBill transaction failed: ' . json_encode($db->error()));

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.merge_bill_failed'),
                ]);
            }

            $db->transCommit();

            return $this->response->setJSON([
                'status'          => 'success',
                'message'         => lang('app.merge_bill_success'),
                'target_order_id' => $targetOrderId,
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'mergeBill error: ' . $e->getMessage());

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.merge_bill_failed'),
            ]);
        }
    }
}