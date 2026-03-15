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
use App\Services\SplitBillService;

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
    protected $splitBillService;
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
        $this->splitBillService          = new SplitBillService();
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
            log_message('error', 'writeAuditLog error: ' . $e->getMessage());
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

    protected function getKitchenTicketAuditContext(int $ticketId): array
    {
        if ($ticketId <= 0) {
            return [
                'ticket_no'   => '',
                'batch_no'    => null,
                'station_id'  => null,
                'station_name'=> '',
            ];
        }

        $tenantId = $this->currentTenantId();
        $branchId = $this->getCurrentBranchId();

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

        $locale = (string) (service('request')->getLocale() ?? 'th');
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
            'branch_id'       => $this->getCurrentBranchId(),
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

        $runtimeSettingKey = '';
        $normalizedKey     = strtolower(trim($featureKey));

        if (strpos($normalizedKey, 'feature.') === 0 || strpos($normalizedKey, 'menu.') === 0 || strpos($normalizedKey, 'billing.') === 0 || strpos($normalizedKey, 'payment.') === 0 || strpos($normalizedKey, 'printing.') === 0 || strpos($normalizedKey, 'media.') === 0) {
            $runtimeSettingKey = $featureKey;
        }

        $planFeatureKey = $runtimeSettingKey;

        if ($runtimeSettingKey !== '' && function_exists('runtime_setting_plan_feature_key')) {
            $mappedPlanFeatureKey = runtime_setting_plan_feature_key($runtimeSettingKey);

            if ($mappedPlanFeatureKey !== '') {
                $planFeatureKey = $mappedPlanFeatureKey;
            }
        }

        if ($planFeatureKey !== '' && function_exists('feature_enabled') && ! feature_enabled($planFeatureKey)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $messageKey ? lang($messageKey) : lang('app.feature_not_available_for_plan'),
                'code'    => 'FEATURE_NOT_ENABLED',
            ]);
        }

        if ($runtimeSettingKey !== '' && function_exists('setting_bool') && ! setting_bool($runtimeSettingKey, true)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.feature_disabled_in_settings'),
                'code'    => 'FEATURE_DISABLED_IN_SETTINGS',
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

    protected function managerOverridePermissionMap(): array
    {
        return [
            'request_bill' => 'cashier.request_bill',
            'close_bill'   => 'cashier.close_bill',
            'reopen_bill'  => 'cashier.reopen_bill',
            'undo_payment' => 'cashier.undo_payment',
            'void_item'    => 'cashier.void_item',
            'pay'          => 'cashier.pay',
            'split_bill'   => 'cashier.split_bill',
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

    protected function isBillableNormalizedOrderItem(array $item): bool
    {
        $normalized = $this->normalizeOrderItemRowForResponse($item);
        $status     = $this->normalizeOrderItemStatus($normalized['status'] ?? '');

        if ($this->isNonBillableOrderItemStatus($status)) {
            return false;
        }

        $requestStatus = strtolower(trim((string) ($normalized['cancel_request_status'] ?? '')));
        if (in_array($requestStatus, ['approved', 'accepted'], true)) {
            return false;
        }

        if (trim((string) ($normalized['cancelled_at'] ?? '')) !== '') {
            return false;
        }

        return true;
    }

    protected function buildOrderFinancialSnapshot(array $order, array $items): array
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $normalized = $this->normalizeOrderItemRowForResponse($item);
            $qty        = max(0, (int) ($normalized['qty'] ?? 0));
            $unitPrice  = (float) ($normalized['price'] ?? 0);
            $lineTotal  = $this->isBillableNormalizedOrderItem($normalized) ? ($unitPrice * $qty) : 0.0;
            $subtotal  += $lineTotal;
        }

        $discount = (float) ($order['discount_amount'] ?? 0);
        $service  = (float) ($order['service_charge'] ?? 0);
        $vat      = (float) ($order['vat_amount'] ?? 0);
        $total    = $subtotal - $discount + $service + $vat;

        if ($total < 0) {
            $total = 0;
        }

        return [
            'subtotal'    => round($subtotal, 2),
            'total_price' => round($total, 2),
        ];
    }

    protected function getEffectiveOrderTotal(int $orderId): float
    {
        $this->recalculateOrderTotal($orderId);

        $freshOrder = $this->getScopedOrder($orderId);

        return (float) ($freshOrder['total_price'] ?? 0);
    }

    protected function getOverviewTables(): array
    {
        $branchId = $this->getCurrentBranchId();
        $tables   = $this->tableModel->getTablesFull($branchId);
        $orderIds = [];

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

            $table['has_open_order']         = $hasOpenOrder ? 1 : 0;
            $table['current_order_id']       = ! empty($currentOrder['id']) ? (int) $currentOrder['id'] : null;
            $table['current_order_status']   = $currentOrder['status'] ?? null;
            $table['reservation_locked']     = $lockInfo['locked'];
            $table['reservation_message']    = $lockInfo['message'];
            $table['reservation_data']       = $lockInfo['reservation'];
            $table['kitchen_pending_count']        = 0;
            $table['kitchen_preparing_count']      = 0;
            $table['kitchen_ready_count']          = 0;
            $table['kitchen_served_count']         = 0;
            $table['kitchen_remaining_count']      = 0;
            $table['kitchen_served_partial']       = 0;
            $table['kitchen_served_all']           = 0;
            $table['cancel_request_count']         = 0;
            $table['kitchen_cancel_request_count'] = 0;
            $table['merge_in_count']               = 0;
            $table['was_moved']                    = 0;
            $table['move_in_count']                = 0;
            $table['was_reopened']                 = 0;
            $table['payment_partial_count']        = 0;
            $table['payment_partial']              = 0;

            if (! empty($currentOrder['id'])) {
                $orderIds[] = (int) $currentOrder['id'];
            }
        }
        unset($table);

        $signalMap = $this->buildOverviewOrderSignalMap($orderIds);

        foreach ($tables as &$table) {
            $orderId = (int) ($table['current_order_id'] ?? 0);
            if ($orderId > 0 && isset($signalMap[$orderId])) {
                $table = array_merge($table, $signalMap[$orderId]);
            }
        }
        unset($table);

        return $tables;
    }

    protected function buildOverviewOrderSignalMap(array $orderIds): array
    {
        $tenantId = $this->currentTenantId();
        $branchId = $this->getCurrentBranchId();
        $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
        $map      = [];

        foreach ($orderIds as $orderId) {
            if ($orderId > 0) {
                $map[$orderId] = [
                    'kitchen_pending_count'        => 0,
                    'kitchen_sent_count'           => 0,
                    'kitchen_preparing_count'      => 0,
                    'kitchen_ready_count'          => 0,
                    'kitchen_served_count'         => 0,
                    'kitchen_remaining_count'      => 0,
                    'kitchen_total_item_count'     => 0,
                    'kitchen_served_percent'       => 0,
                    'kitchen_served_partial'       => 0,
                    'kitchen_served_all'           => 0,
                    'kitchen_served_item_names'    => [],
                    'kitchen_ready_item_names'     => [],
                    'kitchen_preparing_item_names' => [],
                    'kitchen_last_served_at'       => null,
                    'kitchen_focus_state'          => '',
                    'kitchen_focus_label'          => '',
                    'cancel_request_count'         => 0,
                    'kitchen_cancel_request_count' => 0,
                    'merge_in_count'               => 0,
                    'was_moved'                    => 0,
                    'move_in_count'                => 0,
                    'was_reopened'                 => 0,
                    'payment_partial_count'        => 0,
                    'payment_partial'              => 0,
                ];
            }
        }

        if (empty($map)) {
            return $map;
        }

        $inOrderIds = array_keys($map);

        $itemBuilder = $this->db->table('order_items');
        $itemBuilder->select('order_id, status, product_name, item_detail');
        if ($this->db->fieldExists('served_at', 'order_items')) {
            $itemBuilder->select('served_at');
        }
        if ($this->db->fieldExists('cancel_request_status', 'order_items')) {
            $itemBuilder->select('cancel_request_status');
        }
        if ($this->db->fieldExists('tenant_id', 'order_items')) {
            $itemBuilder->where('tenant_id', $tenantId);
        }
        $itemBuilder->whereIn('order_id', $inOrderIds);

        $appendSignalName = static function (array &$signalRow, string $key, string $name): void {
            $name = trim($name);
            if ($name === '') {
                return;
            }

            $signalRow[$key] = isset($signalRow[$key]) && is_array($signalRow[$key]) ? $signalRow[$key] : [];

            if (in_array($name, $signalRow[$key], true)) {
                return;
            }

            if (count($signalRow[$key]) >= 3) {
                return;
            }

            $signalRow[$key][] = $name;
        };

        foreach ($itemBuilder->get()->getResultArray() as $row) {
            $orderId = (int) ($row['order_id'] ?? 0);
            if (! isset($map[$orderId])) {
                continue;
            }

            $status = $this->normalizeOrderItemStatus($row['status'] ?? 'pending');
            $servedAt = trim((string) ($row['served_at'] ?? ''));
            if ($servedAt !== '' && $status === 'pending') {
                $status = 'served';
            }
            $cancelRequestStatus = strtolower(trim((string) ($row['cancel_request_status'] ?? '')));
            $productName = trim((string) ($row['product_name'] ?? $row['item_detail'] ?? ''));

            if (in_array($cancelRequestStatus, ['pending', 'requested', 'waiting'], true)) {
                $map[$orderId]['cancel_request_count']++;
                $map[$orderId]['kitchen_cancel_request_count']++;
            }

            if ($status === 'ready') {
                $map[$orderId]['kitchen_ready_count']++;
                $appendSignalName($map[$orderId], 'kitchen_ready_item_names', $productName);
            } elseif ($status === 'served') {
                $map[$orderId]['kitchen_served_count']++;
                $appendSignalName($map[$orderId], 'kitchen_served_item_names', $productName);
                if ($servedAt !== '') {
                    $existingServedAt = trim((string) ($map[$orderId]['kitchen_last_served_at'] ?? ''));
                    $existingTs = $existingServedAt !== '' ? (strtotime($existingServedAt) ?: 0) : 0;
                    $servedTs = strtotime($servedAt) ?: 0;
                    if ($servedTs > 0 && $servedTs >= $existingTs) {
                        $map[$orderId]['kitchen_last_served_at'] = $servedAt;
                    }
                }
            } elseif (in_array($status, ['preparing', 'cooking', 'processing'], true)) {
                $map[$orderId]['kitchen_preparing_count']++;
                $appendSignalName($map[$orderId], 'kitchen_preparing_item_names', $productName);
            } elseif (in_array($status, ['sent', 'submitted', 'sent_to_kitchen'], true)) {
                $map[$orderId]['kitchen_sent_count']++;
            } elseif (in_array($status, ['pending', 'new', 'open'], true)) {
                $map[$orderId]['kitchen_pending_count']++;
            }
        }

        foreach ($map as $orderId => &$signalRow) {
            $remainingCount = (int) ($signalRow['kitchen_pending_count'] ?? 0)
                + (int) ($signalRow['kitchen_sent_count'] ?? 0)
                + (int) ($signalRow['kitchen_preparing_count'] ?? 0)
                + (int) ($signalRow['kitchen_ready_count'] ?? 0);
            $servedCount = (int) ($signalRow['kitchen_served_count'] ?? 0);

            if ($servedCount <= 0) {
                $servedFallbackBuilder = $this->db->table('order_items');
                $servedFallbackBuilder->select('COUNT(*) AS served_count', false);
                $servedFallbackBuilder->where('order_id', $orderId);

                if ($this->db->fieldExists('tenant_id', 'order_items')) {
                    $servedFallbackBuilder->where('tenant_id', $tenantId);
                }

                $servedFallbackBuilder->groupStart()
                    ->where('status', 'served');

                if ($this->db->fieldExists('served_at', 'order_items')) {
                    $servedFallbackBuilder->orWhere('served_at IS NOT NULL', null, false);
                }

                $servedFallbackBuilder->groupEnd();

                $servedFallbackRow = $servedFallbackBuilder->get()->getRowArray();
                $servedCount = (int) ($servedFallbackRow['served_count'] ?? 0);
                $signalRow['kitchen_served_count'] = $servedCount;
            }

            $totalKitchenCount = $servedCount + $remainingCount;

            $signalRow['kitchen_remaining_count'] = $remainingCount;
            $signalRow['kitchen_total_item_count'] = $totalKitchenCount;
            $signalRow['kitchen_served_percent'] = $totalKitchenCount > 0
                ? (int) round(($servedCount / $totalKitchenCount) * 100)
                : 0;
            $signalRow['kitchen_served_partial'] = ($servedCount > 0 && $remainingCount > 0) ? 1 : 0;
            $signalRow['kitchen_served_all'] = ($servedCount > 0 && $remainingCount === 0) ? 1 : 0;

            $translateSignal = static function (string $key, array $args, string $fallbackTh, string $fallbackEn) {
                $text = lang($key, $args);
                if ($text !== $key) {
                    return $text;
                }

                $locale = service('request')->getLocale();
                $fallback = $locale === 'th' ? $fallbackTh : $fallbackEn;
                foreach ($args as $argKey => $argValue) {
                    $fallback = str_replace('{' . $argKey . '}', (string) $argValue, $fallback);
                }

                return $fallback;
            };

            $focusLabel = '';
            $focusState = '';
            if ((int) ($signalRow['kitchen_cancel_request_count'] ?? 0) > 0) {
                $focusState = 'cancel_request';
                $focusLabel = $translateSignal('app.cancel_request_signal', ['count' => (int) ($signalRow['kitchen_cancel_request_count'] ?? 0)], 'รอยืนยันยกเลิก {count}', 'Cancel request {count}');
            } elseif ((int) ($signalRow['kitchen_ready_count'] ?? 0) > 0) {
                $focusState = 'ready';
                $focusLabel = $translateSignal('app.ready_to_serve_signal', ['count' => (int) ($signalRow['kitchen_ready_count'] ?? 0)], 'พร้อมเสิร์ฟ {count}', 'Ready {count}');
            } elseif ((int) ($signalRow['kitchen_preparing_count'] ?? 0) > 0) {
                $focusState = 'preparing';
                $focusLabel = $translateSignal('app.preparing_signal', ['count' => (int) ($signalRow['kitchen_preparing_count'] ?? 0)], 'กำลังทำ {count}', 'Preparing {count}');
            } elseif ((int) ($signalRow['kitchen_sent_count'] ?? 0) > 0) {
                $focusState = 'sent';
                $focusLabel = $translateSignal('app.sent_to_kitchen_signal', ['count' => (int) ($signalRow['kitchen_sent_count'] ?? 0)], 'ส่งครัวแล้ว {count}', 'Sent to kitchen {count}');
            } elseif ((int) ($signalRow['kitchen_pending_count'] ?? 0) > 0) {
                $focusState = 'pending';
                $focusLabel = $translateSignal('app.pending_kitchen_signal', ['count' => (int) ($signalRow['kitchen_pending_count'] ?? 0)], 'รอส่งครัว {count}', 'Pending kitchen {count}');
            } elseif ((int) ($signalRow['kitchen_served_all'] ?? 0) === 1) {
                $focusState = 'served_complete';
                $focusLabel = $translateSignal('app.served_complete_signal', ['count' => $servedCount], 'เสิร์ฟครบแล้ว {count}', 'Served all {count}');
            } elseif ((int) ($signalRow['kitchen_served_partial'] ?? 0) === 1) {
                $focusState = 'served_partial';
                $focusLabel = $translateSignal('app.served_partial_signal', ['served' => $servedCount, 'remaining' => $remainingCount], 'เสิร์ฟแล้ว {served} / คงเหลือ {remaining}', 'Served {served} / Remaining {remaining}');
            }
            $signalRow['kitchen_focus_state'] = $focusState;
            $signalRow['kitchen_focus_label'] = $focusLabel;
        }
        unset($signalRow);

        if ($this->db->tableExists('payments')) {
            $paymentBuilder = $this->db->table('payments');
            $paymentBuilder->select('order_id, COUNT(*) AS total_count', false);
            if ($this->db->fieldExists('tenant_id', 'payments')) {
                $paymentBuilder->where('tenant_id', $tenantId);
            }
            $paymentBuilder->whereIn('order_id', $inOrderIds);
            if ($this->db->fieldExists('status', 'payments')) {
                $paymentBuilder->whereNotIn('status', ['void', 'voided', 'refund', 'refunded']);
            }
            $paymentBuilder->groupBy('order_id');

            foreach ($paymentBuilder->get()->getResultArray() as $row) {
                $orderId = (int) ($row['order_id'] ?? 0);
                if (isset($map[$orderId])) {
                    $map[$orderId]['payment_partial_count'] = max(0, ((int) ($row['total_count'] ?? 0)) - 1);
                    $map[$orderId]['payment_partial'] = $map[$orderId]['payment_partial_count'] > 0 ? 1 : 0;
                }
            }
        }

        if ($this->db->tableExists('order_merges')) {
            $mergeBuilder = $this->db->table('order_merges');
            $mergeBuilder->select('target_order_id AS order_id, COUNT(*) AS total_count', false);
            $mergeBuilder->where('tenant_id', $tenantId);
            if ($this->db->fieldExists('deleted_at', 'order_merges')) {
                $mergeBuilder->where('deleted_at', null);
            }
            if ($branchId > 0 && $this->db->fieldExists('branch_id', 'order_merges')) {
                $mergeBuilder->where('branch_id', $branchId);
            }
            $mergeBuilder->whereIn('target_order_id', $inOrderIds);
            $mergeBuilder->groupBy('target_order_id');

            foreach ($mergeBuilder->get()->getResultArray() as $row) {
                $orderId = (int) ($row['order_id'] ?? 0);
                if (isset($map[$orderId])) {
                    $map[$orderId]['merge_in_count'] = (int) ($row['total_count'] ?? 0);
                }
            }
        }

        if ($this->db->tableExists('order_table_moves')) {
            $moveBuilder = $this->db->table('order_table_moves');
            $moveBuilder->select('order_id');
            $moveBuilder->where('tenant_id', $tenantId);
            if ($branchId > 0 && $this->db->fieldExists('branch_id', 'order_table_moves')) {
                $moveBuilder->where('branch_id', $branchId);
            }
            $moveBuilder->whereIn('order_id', $inOrderIds);
            $moveBuilder->groupBy('order_id');

            foreach ($moveBuilder->get()->getResultArray() as $row) {
                $orderId = (int) ($row['order_id'] ?? 0);
                if (isset($map[$orderId])) {
                    $map[$orderId]['was_moved'] = 1;
                    $map[$orderId]['move_in_count'] = 1;
                }
            }
        }

        if ($this->db->tableExists('audit_logs')) {
            $auditBuilder = $this->db->table('audit_logs');
            $auditBuilder->select('order_id');
            $auditBuilder->where('tenant_id', $tenantId);
            if ($branchId > 0 && $this->db->fieldExists('branch_id', 'audit_logs')) {
                $auditBuilder->where('branch_id', $branchId);
            }
            $auditBuilder->whereIn('order_id', $inOrderIds);
            $auditBuilder->whereIn('action_key', ['cashier.reopen_bill', 'pos.reopen_bill', 'reopen_bill']);
            $auditBuilder->groupBy('order_id');

            foreach ($auditBuilder->get()->getResultArray() as $row) {
                $orderId = (int) ($row['order_id'] ?? 0);
                if (isset($map[$orderId])) {
                    $map[$orderId]['was_reopened'] = 1;
                }
            }
        }

        return $map;
    }

    protected function getPosOverviewRealtimeSnapshot(): array
    {
        $tenantId = $this->currentTenantId();
        $branchId = $this->getCurrentBranchId();
        $latest   = '1970-01-01 00:00:00';
        $parts    = [];

        $advanceLatest = static function (?string $value) use (&$latest): void {
            $value = trim((string) $value);
            if ($value !== '' && strtotime($value) > strtotime($latest)) {
                $latest = $value;
            }
        };

        $tableBuilder = $this->db->table('restaurant_tables');
        $tableBuilder->select('MAX(updated_at) AS latest_at, COUNT(*) AS total_count');
        $tableBuilder->where('tenant_id', $tenantId);
        $tableBuilder->where('deleted_at', null);
        if ($branchId > 0) {
            $tableBuilder->where('branch_id', $branchId);
        }
        $tableMeta = (array) ($tableBuilder->get()->getRowArray() ?? []);
        $advanceLatest($tableMeta['latest_at'] ?? '');
        $parts['tables'] = [
            'latest_at' => (string) ($tableMeta['latest_at'] ?? ''),
            'count'     => (int) ($tableMeta['total_count'] ?? 0),
        ];

        $orderBuilder = $this->db->table('orders');
        $orderBuilder->select('MAX(updated_at) AS latest_at, COUNT(*) AS total_count');
        $orderBuilder->where('tenant_id', $tenantId);
        if ($branchId > 0) {
            $orderBuilder->where('branch_id', $branchId);
        }
        $orderMeta = (array) ($orderBuilder->get()->getRowArray() ?? []);
        $advanceLatest($orderMeta['latest_at'] ?? '');
        $parts['orders'] = [
            'latest_at' => (string) ($orderMeta['latest_at'] ?? ''),
            'count'     => (int) ($orderMeta['total_count'] ?? 0),
        ];

        $reservationBuilder = $this->db->table('reservations');
        $reservationBuilder->select('MAX(updated_at) AS latest_at, COUNT(*) AS total_count');
        $reservationBuilder->where('tenant_id', $tenantId);
        $reservationBuilder->where('deleted_at', null);
        if ($branchId > 0) {
            $reservationBuilder->where('branch_id', $branchId);
        }
        $reservationMeta = (array) ($reservationBuilder->get()->getRowArray() ?? []);
        $advanceLatest($reservationMeta['latest_at'] ?? '');
        $parts['reservations'] = [
            'latest_at' => (string) ($reservationMeta['latest_at'] ?? ''),
            'count'     => (int) ($reservationMeta['total_count'] ?? 0),
        ];

        $reservationTableBuilder = $this->db->table('reservation_tables rt');
        $reservationTableBuilder->select('MAX(rt.updated_at) AS latest_at, COUNT(*) AS total_count');
        $reservationTableBuilder->join('reservations r', 'r.id = rt.reservation_id AND r.tenant_id = rt.tenant_id AND r.deleted_at IS NULL', 'inner');
        $reservationTableBuilder->where('rt.tenant_id', $tenantId);
        $reservationTableBuilder->where('rt.deleted_at', null);
        if ($branchId > 0) {
            $reservationTableBuilder->where('r.branch_id', $branchId);
        }
        $reservationTableMeta = (array) ($reservationTableBuilder->get()->getRowArray() ?? []);
        $advanceLatest($reservationTableMeta['latest_at'] ?? '');
        $parts['reservation_tables'] = [
            'latest_at' => (string) ($reservationTableMeta['latest_at'] ?? ''),
            'count'     => (int) ($reservationTableMeta['total_count'] ?? 0),
        ];

        $paymentBuilder = $this->db->table('payments p');
        $paymentBuilder->select('MAX(p.updated_at) AS latest_at, COUNT(*) AS total_count');
        $paymentBuilder->join('orders o', 'o.id = p.order_id AND o.tenant_id = p.tenant_id', 'inner');
        $paymentBuilder->where('p.tenant_id', $tenantId);
        if ($branchId > 0) {
            $paymentBuilder->where('o.branch_id', $branchId);
        }
        $paymentMeta = (array) ($paymentBuilder->get()->getRowArray() ?? []);
        $advanceLatest($paymentMeta['latest_at'] ?? '');
        $parts['payments'] = [
            'latest_at' => (string) ($paymentMeta['latest_at'] ?? ''),
            'count'     => (int) ($paymentMeta['total_count'] ?? 0),
        ];

        $moveBuilder = $this->db->table('order_table_moves');
        $moveBuilder->select('MAX(updated_at) AS latest_at, COUNT(*) AS total_count');
        $moveBuilder->where('tenant_id', $tenantId);
        if ($branchId > 0) {
            $moveBuilder->where('branch_id', $branchId);
        }
        $moveMeta = (array) ($moveBuilder->get()->getRowArray() ?? []);
        $advanceLatest($moveMeta['latest_at'] ?? '');
        $parts['moves'] = [
            'latest_at' => (string) ($moveMeta['latest_at'] ?? ''),
            'count'     => (int) ($moveMeta['total_count'] ?? 0),
        ];

        $mergeBuilder = $this->db->table('order_merges');
        $mergeBuilder->select('MAX(updated_at) AS latest_at, COUNT(*) AS total_count');
        $mergeBuilder->where('tenant_id', $tenantId);
        $mergeBuilder->where('deleted_at', null);
        if ($branchId > 0) {
            $mergeBuilder->where('branch_id', $branchId);
        }
        $mergeMeta = (array) ($mergeBuilder->get()->getRowArray() ?? []);
        $advanceLatest($mergeMeta['latest_at'] ?? '');
        $parts['merges'] = [
            'latest_at' => (string) ($mergeMeta['latest_at'] ?? ''),
            'count'     => (int) ($mergeMeta['total_count'] ?? 0),
        ];

        return [
            'version' => sha1(json_encode([
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'latest'    => $latest,
                'parts'     => $parts,
            ])),
            'cursor'  => $latest,
            'parts'   => $parts,
        ];
    }

    protected function getChangedOverviewTableIdsSince(string $cursor): array
    {
        $tenantId = $this->currentTenantId();
        $branchId = $this->getCurrentBranchId();
        $changed  = [];

        if ($cursor === '' || strtotime($cursor) === false) {
            return [];
        }

        $orderBuilder = $this->db->table('orders');
        $orderBuilder->distinct();
        $orderBuilder->select('table_id');
        $orderBuilder->where('tenant_id', $tenantId);
        $orderBuilder->where('updated_at >', $cursor);
        if ($branchId > 0) {
            $orderBuilder->where('branch_id', $branchId);
        }
        foreach ($orderBuilder->get()->getResultArray() as $row) {
            $tableId = (int) ($row['table_id'] ?? 0);
            if ($tableId > 0) {
                $changed[$tableId] = $tableId;
            }
        }

        $tableBuilder = $this->db->table('restaurant_tables');
        $tableBuilder->distinct();
        $tableBuilder->select('id');
        $tableBuilder->where('tenant_id', $tenantId);
        $tableBuilder->where('deleted_at', null);
        $tableBuilder->where('updated_at >', $cursor);
        if ($branchId > 0) {
            $tableBuilder->where('branch_id', $branchId);
        }
        foreach ($tableBuilder->get()->getResultArray() as $row) {
            $tableId = (int) ($row['id'] ?? 0);
            if ($tableId > 0) {
                $changed[$tableId] = $tableId;
            }
        }

        $reservationBuilder = $this->db->table('reservation_tables rt');
        $reservationBuilder->distinct();
        $reservationBuilder->select('rt.table_id');
        $reservationBuilder->join('reservations r', 'r.id = rt.reservation_id AND r.tenant_id = rt.tenant_id AND r.deleted_at IS NULL', 'inner');
        $reservationBuilder->where('rt.tenant_id', $tenantId);
        $reservationBuilder->where('rt.deleted_at', null);
        $reservationBuilder->groupStart()
            ->where('rt.updated_at >', $cursor)
            ->orWhere('r.updated_at >', $cursor)
            ->groupEnd();
        if ($branchId > 0) {
            $reservationBuilder->where('r.branch_id', $branchId);
        }
        foreach ($reservationBuilder->get()->getResultArray() as $row) {
            $tableId = (int) ($row['table_id'] ?? 0);
            if ($tableId > 0) {
                $changed[$tableId] = $tableId;
            }
        }

        return array_values($changed);
    }

    public function index()
    {
        if ($response = $this->denyIfFeatureNotEnabled('pos.access', lang('app.plan_cannot_access_pos'))) {
            return $response;
        }

        return view('pos/index', [
            'tables' => $this->getOverviewTables(),
        ]);
    }

    public function overviewGridPartial()
    {
        if ($response = $this->denyIfFeatureNotEnabled('pos.access', lang('app.plan_cannot_access_pos'))) {
            return $response;
        }

        return view('pos/_table_grid', [
            'tables' => $this->getOverviewTables(),
        ]);
    }

    public function overviewChanges()
    {
        if ($response = $this->denyIfFeatureNotEnabled('pos.access', lang('app.plan_cannot_access_pos'))) {
            return $response;
        }

        $snapshot = $this->getPosOverviewRealtimeSnapshot();
        $version  = trim((string) $this->request->getGet('version'));
        $cursor   = trim((string) $this->request->getGet('cursor'));

        $hasChanges = $version === '' || ! hash_equals($snapshot['version'], $version);

        return $this->response->setJSON([
            'status'            => 'success',
            'has_changes'       => $hasChanges,
            'version'           => $snapshot['version'],
            'cursor'            => $snapshot['cursor'],
            'changed_table_ids' => $hasChanges ? $this->getChangedOverviewTableIdsSince($cursor) : [],
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
            'tablePermissions' => [
                'void_item'        => $this->userHasPermissionKey('cashier.void_item'),
                'manager_override' => $this->userHasPermissionKey('cashier.manager_override'),
                'split_bill'       => $this->userHasPermissionKey('cashier.split_bill'),
            ],
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

        $builder->whereIn('orders.status', ['open', 'billing', 'paid']);
        $builder->orderBy("CASE WHEN orders.status = 'billing' THEN 0 WHEN orders.status = 'open' THEN 1 ELSE 2 END", '', false);
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
        $order = $this->getScopedOrder($orderId, ['open', 'billing', 'paid']);

        if (! $order) {
            return null;
        }

        $this->recalculateOrderTotal($orderId);
        $order = $this->getScopedOrder($orderId, ['open', 'billing', 'paid']);

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

        $payments = $this->paymentModel->getByOrder($orderId);
        $activePayments = array_values(array_filter($payments, static function (array $payment): bool {
            return (string) ($payment['payment_status'] ?? 'paid') === 'paid';
        }));

        return [
            'order' => $order,
            'items' => $items,
            'table' => $table,
            'counts' => $counts,
            'summary' => $summary,
            'payments' => $payments,
            'active_payments' => $activePayments,
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
        ], 'cashier.screen.' . $this->getCurrentBranchId(), 5);

        $orders = $this->getCashierOrdersForBranch();

        $summary = [
			'open'        => 0,
			'billing'     => 0,
			'paid'        => 0,
			'sales_total' => 0.0,
		];

        foreach ($orders as $order) {

			$status = $order['status'] ?? 'open';

			if ($status === 'billing') {

				$summary['billing'] = ($summary['billing'] ?? 0) + 1;

			} elseif ($status === 'paid') {

				$summary['paid'] = ($summary['paid'] ?? 0) + 1;

			} else {

				$summary['open'] = ($summary['open'] ?? 0) + 1;

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
                'reopen_bill'      => $this->userHasPermissionKey('cashier.reopen_bill'),
                'pay'              => $this->userHasPermissionKey('cashier.pay'),
                'undo_payment'     => $this->userHasPermissionKey('cashier.undo_payment'),
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
        ], 'cashier.order.' . $orderId . '.' . $this->getCurrentBranchId(), 3);

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

    protected function buildMoveNoticeFromRow(array $move): ?array
    {
        if (empty($move)) {
            return null;
        }

        $branchId       = $this->getCurrentBranchId();
        $fromTableId    = (int) ($move['from_table_id'] ?? 0);
        $toTableId      = (int) ($move['to_table_id'] ?? 0);
        $orderId        = (int) ($move['order_id'] ?? 0);
        $fromTableName  = $move['from_table_name'] ?? null;
        $toTableName    = $move['to_table_name'] ?? null;
        $orderNumber    = null;
        $movedByName    = $move['moved_by_name'] ?? null;

        if (($fromTableName === null || $fromTableName === '') && $fromTableId > 0 && method_exists($this->tableModel, 'getTableMapByIds')) {
            $fromMap = $this->tableModel->getTableMapByIds([$fromTableId], $branchId);
            $fromTableName = $fromMap[$fromTableId]['table_name'] ?? null;
        }

        if (($toTableName === null || $toTableName === '') && $toTableId > 0 && method_exists($this->tableModel, 'getTableMapByIds')) {
            $toMap = $this->tableModel->getTableMapByIds([$toTableId], $branchId);
            $toTableName = $toMap[$toTableId]['table_name'] ?? null;
        }

        if ($orderId > 0) {
            $orderBuilder = $this->orderModel
                ->where('tenant_id', $this->currentTenantId())
                ->where('id', $orderId);

            if ($branchId > 0 && $this->db->fieldExists('branch_id', 'orders')) {
                $orderBuilder->where('branch_id', $branchId);
            }

            $orderRow = $orderBuilder->first();
            if ($orderRow) {
                $orderNumber = $orderRow['order_number'] ?? null;
            }
        }

        return [
            'move_id'          => (int) ($move['id'] ?? 0),
            'order_id'         => $orderId,
            'from_table_id'    => $fromTableId,
            'to_table_id'      => $toTableId,
            'from_table_name'  => $fromTableName,
            'to_table_name'    => $toTableName,
            'order_number'     => $orderNumber,
            'reason'           => $move['reason'] ?? null,
            'moved_by_name'    => $movedByName,
            'moved_at'         => $move['created_at'] ?? null,
        ];
    }

    protected function getLatestMoveNoticeBySourceTable(int $tableId): ?array
    {
        if ($tableId <= 0 || ! $this->db->tableExists('order_table_moves')) {
            return null;
        }

        $builder = $this->db->table('order_table_moves otm');
        $builder->select('otm.*, u.full_name AS moved_by_name, u.username AS moved_by_username, rtf.table_name AS from_table_name, rtt.table_name AS to_table_name');
        $builder->join('users u', 'u.id = otm.moved_by', 'left');
        $builder->join('restaurant_tables rtf', 'rtf.id = otm.from_table_id', 'left');
        $builder->join('restaurant_tables rtt', 'rtt.id = otm.to_table_id', 'left');
        $builder->where('otm.tenant_id', $this->currentTenantId());
        $builder->where('otm.from_table_id', $tableId);

        $branchId = $this->getCurrentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'order_table_moves')) {
            $builder->where('otm.branch_id', $branchId);
        }

        $move = $builder->orderBy('otm.id', 'DESC')->get()->getFirstRow('array');
        if (! $move) {
            return null;
        }

        $checkpointOrderId = (int) ($move['order_id'] ?? 0);
        if ($checkpointOrderId > 0) {
            $orderBuilder = $this->db->table('orders');
            $orderBuilder->select('id');
            $orderBuilder->where('tenant_id', $this->currentTenantId());
            $orderBuilder->where('table_id', $tableId);
            $orderBuilder->where('id >', $checkpointOrderId);

            if ($branchId > 0 && $this->db->fieldExists('branch_id', 'orders')) {
                $orderBuilder->where('branch_id', $branchId);
            }

            $newerOrder = $orderBuilder->orderBy('id', 'DESC')->get()->getFirstRow('array');
            if ($newerOrder) {
                return null;
            }
        }

        return $this->buildMoveNoticeFromRow($move);
    }

    protected function getMoveTraceByOrder(int $orderId): array
    {
        if ($orderId <= 0 || ! $this->db->tableExists('order_table_moves')) {
            return [];
        }

        $builder = $this->db->table('order_table_moves otm');
        $builder->select('otm.*, u.full_name AS moved_by_name, u.username AS moved_by_username, rtf.table_name AS from_table_name, rtt.table_name AS to_table_name');
        $builder->join('users u', 'u.id = otm.moved_by', 'left');
        $builder->join('restaurant_tables rtf', 'rtf.id = otm.from_table_id', 'left');
        $builder->join('restaurant_tables rtt', 'rtt.id = otm.to_table_id', 'left');
        $builder->where('otm.tenant_id', $this->currentTenantId());
        $builder->where('otm.order_id', $orderId);

        $branchId = $this->getCurrentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'order_table_moves')) {
            $builder->where('otm.branch_id', $branchId);
        }

        $rows = $builder->orderBy('otm.id', 'ASC')->get()->getResultArray();
        if (empty($rows)) {
            return [];
        }

        return array_values(array_filter(array_map(fn (array $row) => $this->buildMoveNoticeFromRow($row), $rows)));
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
            $builder->where('branch_id', $branchId);
        }

        $merge = $builder->orderBy('id', 'DESC')->first();

        if (! $merge) {
            return null;
        }

        $sourceOrderId = (int) ($merge['source_order_id'] ?? 0);
        $targetOrderId = (int) ($merge['target_order_id'] ?? 0);
        $checkpointId  = max($sourceOrderId, $targetOrderId);

        if ($checkpointId > 0) {
            $orderBuilder = $this->db->table('orders');
            $orderBuilder->select('id');
            $orderBuilder->where('tenant_id', $this->currentTenantId());
            $orderBuilder->where('table_id', $tableId);
            $orderBuilder->where('id >', $checkpointId);

            if ($branchId > 0 && $this->db->fieldExists('branch_id', 'orders')) {
                $orderBuilder->where('branch_id', $branchId);
            }

            $newerOrder = $orderBuilder->orderBy('id', 'DESC')->get()->getFirstRow('array');
            if ($newerOrder) {
                return null;
            }
        }

        return $this->buildMergeNoticeFromRow($merge);
    }


    protected function getMergeTraceByTargetOrder(int $targetOrderId): array
    {
        if ($targetOrderId <= 0 || ! $this->db->tableExists('order_merges')) {
            return [];
        }

        $builder = $this->db->table('order_merges om');
        $builder->select([
            'om.id',
            'om.source_order_id',
            'om.target_order_id',
            'om.source_table_id',
            'om.target_table_id',
            'om.reason',
            'om.created_at',
            'so.order_number AS source_order_number',
            'to2.order_number AS target_order_number',
            'st.table_name AS source_table_name',
            'tt.table_name AS target_table_name',
            'u.full_name AS merged_by_name',
        ]);
        $builder->join('orders so', 'so.id = om.source_order_id', 'left');
        $builder->join('orders to2', 'to2.id = om.target_order_id', 'left');
        $builder->join('restaurant_tables st', 'st.id = om.source_table_id', 'left');
        $builder->join('restaurant_tables tt', 'tt.id = om.target_table_id', 'left');
        $builder->join('users u', 'u.id = om.merged_by', 'left');
        $builder->where('om.tenant_id', $this->currentTenantId());
        $builder->where('om.target_order_id', $targetOrderId);

        $branchId = $this->getCurrentBranchId();
        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'order_merges')) {
            $builder->where('om.branch_id', $branchId);
        }

        $rows = $builder
            ->orderBy('om.id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'merge_id'            => (int) ($row['id'] ?? 0),
                'source_order_id'     => (int) ($row['source_order_id'] ?? 0),
                'target_order_id'     => (int) ($row['target_order_id'] ?? 0),
                'source_table_id'     => isset($row['source_table_id']) ? (int) $row['source_table_id'] : null,
                'target_table_id'     => isset($row['target_table_id']) ? (int) $row['target_table_id'] : null,
                'source_order_number' => (string) ($row['source_order_number'] ?? ''),
                'target_order_number' => (string) ($row['target_order_number'] ?? ''),
                'source_table_name'   => (string) ($row['source_table_name'] ?? ''),
                'target_table_name'   => (string) ($row['target_table_name'] ?? ''),
                'merged_by_name'      => (string) ($row['merged_by_name'] ?? ''),
                'reason'              => $row['reason'] ?? null,
                'merged_at'           => $row['created_at'] ?? null,
            ];
        }, $rows);
    }


    public function tableTimeline($tableId)
    {
        if ($response = $this->jsonFeatureDenied('pos.access', 'app.plan_cannot_access_pos')) {
            return $response;
        }

        $tableId = (int) $tableId;
        $table = $this->getScopedTable($tableId);
        if (! $table) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => lang('app.table_not_found'),
            ]);
        }

        $order = $this->findCurrentOrderByTable($tableId);
        if (! $order) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => lang('app.no_bill_yet'),
            ]);
        }

        $orderId = (int) ($order['id'] ?? 0);
        $rows = $this->auditLogModel->getTimelineByOrderId($orderId);

        $normalized = array_map(function (array $row): array {
            $meta = [];
            if (! empty($row['meta_json'])) {
                $decoded = json_decode((string) $row['meta_json'], true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }

            return [
                'id' => (int) ($row['id'] ?? 0),
                'action_key' => (string) ($row['action_key'] ?? ''),
                'action_label' => (string) ($row['action_label'] ?? ''),
                'actor_name' => (string) ($row['actor_name'] ?? $row['full_name'] ?? $row['username'] ?? ''),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'meta' => $meta,
            ];
        }, $rows);

        $this->writeAuditLog([
            'target_type'  => 'order',
            'target_id'    => $orderId,
            'order_id'     => $orderId,
            'table_id'     => $tableId,
            'action_key'   => 'audit_logs.view',
            'action_label' => lang('app.view_timeline'),
            'meta_json'    => [
                'screen'    => 'pos_table_timeline',
                'branch_id' => $this->getCurrentBranchId(),
            ],
        ], 'pos.timeline.' . $orderId . '.' . $this->getCurrentBranchId(), 3);

        return $this->response->setJSON([
            'status' => 'success',
            'table' => [
                'id' => (int) ($table['id'] ?? 0),
                'table_name' => (string) ($table['table_name'] ?? ''),
            ],
            'order' => [
                'id' => $orderId,
                'order_number' => (string) ($order['order_number'] ?? ''),
                'status' => (string) ($order['status'] ?? ''),
            ],
            'rows' => $normalized,
        ]);
    }

    public function currentOrder($tableId)
    {
        if ($response = $this->jsonFeatureDenied('pos.access', 'app.plan_cannot_access_pos')) {
            return $response;
        }

        $tableId = (int) $tableId;
        $requestedOrderId = (int) ($this->request->getGet('order_id') ?? 0);

        $table = $this->getScopedTable($tableId);
        if (! $table) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.table_not_found'),
            ]);
        }

        $activeOrder = $this->findCurrentOrderByTable($tableId);

        if (! $activeOrder) {
            return $this->response->setJSON([
                'status'        => 'empty',
                'merged_notice' => $this->getLatestMergedNoticeByTable($tableId),
                'moved_notice'  => $this->getLatestMoveNoticeBySourceTable($tableId),
                'move_trace'    => [],
                'split_group'   => [],
            ]);
        }

        $order = $activeOrder;

        if ($requestedOrderId > 0) {
            $requestedOrder = $this->getScopedOrder($requestedOrderId);
            if ($requestedOrder && (int) ($requestedOrder['table_id'] ?? 0) === $tableId && $this->ordersBelongToSameSplitGroup($activeOrder, $requestedOrder)) {
                $order = $requestedOrder;
            }
        }

        $this->recalculateOrderTotal((int) $order['id']);
        $freshOrder = $this->getScopedOrder((int) $order['id']);
        if ($freshOrder) {
            $order = $freshOrder;
        }

        $items = $this->orderItemModel->getByOrder((int) $order['id']);
        $items = array_map(fn (array $item): array => $this->normalizeOrderItemRowForResponse($item), $items);

        $financialSnapshot = $this->buildOrderFinancialSnapshot($order, $items);
        $order['subtotal'] = $financialSnapshot['subtotal'];
        $order['total_price'] = $financialSnapshot['total_price'];

        return $this->response->setJSON([
            'status'            => 'success',
            'order'             => $order,
            'items'             => $items,
            'selected_order_id' => (int) ($order['id'] ?? 0),
            'split_group'       => $this->buildTableSplitGroupSummary($activeOrder, (int) ($order['id'] ?? 0)),
            'merged_notice'     => null,
            'merge_trace'       => $this->getMergeTraceByTargetOrder((int) ($order['id'] ?? 0)),
            'moved_notice'      => null,
            'move_trace'        => $this->getMoveTraceByOrder((int) ($order['id'] ?? 0)),
        ]);
    }

    protected function ordersBelongToSameSplitGroup(array $leftOrder, array $rightOrder): bool
    {
        return $this->resolveSplitGroupRootId($leftOrder) === $this->resolveSplitGroupRootId($rightOrder)
            && (int) ($leftOrder['table_id'] ?? 0) === (int) ($rightOrder['table_id'] ?? 0);
    }

    protected function resolveSplitGroupRootId(array $order): int
    {
        $orderId = (int) ($order['id'] ?? 0);
        $rootId = (int) ($order['split_root_order_id'] ?? 0);
        $parentId = (int) ($order['parent_order_id'] ?? 0);

        if ($rootId > 0) {
            return $rootId;
        }

        if ($parentId > 0) {
            return $parentId;
        }

        return $orderId;
    }

    protected function getSplitGroupOrdersByAnchor(array $anchorOrder): array
    {
        $tableId = (int) ($anchorOrder['table_id'] ?? 0);
        $rootId = $this->resolveSplitGroupRootId($anchorOrder);

        if ($tableId <= 0 || $rootId <= 0) {
            return [];
        }

        return $this->scopedOrderQuery(null)
            ->where('table_id', $tableId)
            ->whereNotIn('status', ['merged'])
            ->groupStart()
                ->where('id', $rootId)
                ->orWhere('split_root_order_id', $rootId)
                ->orWhere('parent_order_id', $rootId)
            ->groupEnd()
            ->orderBy('CASE WHEN orders.id = ' . $rootId . ' THEN 0 ELSE 1 END', '', false)
            ->orderBy('split_no', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    protected function buildTableSplitGroupSummary(array $anchorOrder, int $selectedOrderId = 0): array
    {
        $rows = $this->getSplitGroupOrdersByAnchor($anchorOrder);
        if (empty($rows)) {
            return [];
        }

        $summaries = [];

        foreach ($rows as $row) {
            $orderId = (int) ($row['id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $this->recalculateOrderTotal($orderId);
            $freshOrder = $this->getScopedOrder($orderId) ?: $row;
            $items = $this->orderItemModel->getByOrder($orderId);
            $items = array_map(fn (array $item): array => $this->normalizeOrderItemRowForResponse($item), $items);
            $financialSnapshot = $this->buildOrderFinancialSnapshot($freshOrder, $items);
            $status = strtolower(trim((string) ($freshOrder['status'] ?? 'open')));
            $billType = strtolower(trim((string) ($freshOrder['bill_type'] ?? 'normal')));

            $role = 'normal';
            if ($orderId === $this->resolveSplitGroupRootId($anchorOrder)) {
                $role = 'root';
            } elseif ($billType === 'split' || (int) ($freshOrder['split_no'] ?? 0) > 0) {
                $role = 'child';
            } elseif ((int) ($freshOrder['parent_order_id'] ?? 0) > 0) {
                $role = 'parent';
            }

            $previewItems = [];
            foreach ($items as $item) {
                $normalizedItem = $this->normalizeOrderItemRowForResponse($item);
                $normalizedItemStatus = $this->normalizeOrderItemStatus($normalizedItem['status'] ?? '');
                $cancelRequestStatus = strtolower(trim((string) ($normalizedItem['cancel_request_status'] ?? '')));
                $isHiddenPreviewItem = in_array($normalizedItemStatus, ['cancel', 'cancelled', 'canceled'], true)
                    || in_array($cancelRequestStatus, ['approved', 'accepted'], true)
                    || trim((string) ($normalizedItem['cancelled_at'] ?? '')) !== '';

                if ($isHiddenPreviewItem) {
                    continue;
                }

                $itemName = trim((string) ($normalizedItem['product_name'] ?? $normalizedItem['name'] ?? $normalizedItem['title'] ?? ''));
                if ($itemName === '') {
                    $itemName = 'Item #' . (int) ($normalizedItem['id'] ?? 0);
                }

                $itemQty = max(1, (int) ($normalizedItem['qty'] ?? 1));
                $previewItems[] = [
                    'name'       => $itemName,
                    'qty'        => $itemQty,
                    'status'     => $normalizedItemStatus,
                    'line_total' => $this->isBillableNormalizedOrderItem($normalizedItem)
                        ? round(((float) ($normalizedItem['price'] ?? 0)) * $itemQty, 2)
                        : 0.0,
                    'note'       => trim((string) ($normalizedItem['note'] ?? '')),
                ];
            }

            $summaries[] = [
                'id'             => $orderId,
                'order_number'   => (string) ($freshOrder['order_number'] ?? ''),
                'status'         => $status,
                'bill_type'      => $billType,
                'role'           => $role,
                'split_no'       => (int) ($freshOrder['split_no'] ?? 0),
                'item_count'     => count($items),
                'total_price'    => (float) ($financialSnapshot['total_price'] ?? 0),
                'can_pay'        => in_array($status, ['open', 'billing'], true),
                'is_paid'        => $status === 'paid' || ! empty($freshOrder['paid_at']),
                'is_selected'    => $selectedOrderId > 0 ? $selectedOrderId === $orderId : ((int) ($anchorOrder['id'] ?? 0) === $orderId),
                'preview_items'  => $previewItems,
                'preview_count'  => count($previewItems),
            ];
        }

        return $summaries;
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

        $order = $this->getScopedOrder($orderId, ['open']);
        if (! $order || ($order['status'] ?? '') !== 'open') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.bill_status_cannot_add_items'),
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

    if ($response = $this->jsonFeatureDenied('feature.refund_void.enabled')) {
        return $response;
    }

    $itemId = (int) ($this->request->getPost('item_id') ?? 0);
    $reason = trim((string) ($this->request->getPost('reason') ?? ''));

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
    $order   = $check['order'];
    $orderId = (int) ($item['order_id'] ?? 0);

    if ($response = $this->ensurePermissionOrManagerOverride('cashier.void_item', 'void_item', $orderId)) {
        return $response;
    }

    $db = \Config\Database::connect();
    $db->transBegin();

    try {
        $now          = date('Y-m-d H:i:s');
        $userId       = (int) (session('user_id') ?? 0) ?: null;
        $overrideData = $this->consumeManagerOverrideApproval('void_item', $orderId);

        $updateData = [
            'status'       => 'cancel',
            'cancelled_at' => $now,
            'cancelled_by' => $userId,
            'updated_at'   => $now,
            'line_total'   => 0,
        ];

        if ($this->orderItemsFieldExists('cancel_request_status')) {
            $updateData['cancel_request_status'] = 'approved';
        }
        if ($this->orderItemsFieldExists('cancel_request_note')) {
            $updateData['cancel_request_note'] = $reason;
        }
        if ($this->orderItemsFieldExists('cancel_request_reason')) {
            $updateData['cancel_request_reason'] = $reason;
        }
        if ($this->orderItemsFieldExists('cancel_request_prev_status')) {
            $updateData['cancel_request_prev_status'] = 'pending';
        }
        if ($this->orderItemsFieldExists('cancel_requested_at')) {
            $updateData['cancel_requested_at'] = $now;
        }
        if ($this->orderItemsFieldExists('cancel_requested_by')) {
            $updateData['cancel_requested_by'] = $userId;
        }
        if ($this->orderItemsFieldExists('cancel_decided_at')) {
            $updateData['cancel_decided_at'] = $now;
        }
        if ($this->orderItemsFieldExists('cancel_decided_by')) {
            $updateData['cancel_decided_by'] = $userId;
        }

        $this->orderItemModel->update($itemId, $updateData);
        $this->recalculateOrderTotal($orderId);

        $this->writeAuditLog([
            'target_type'  => 'order_item',
            'target_id'    => $itemId,
            'order_id'     => $orderId,
            'table_id'     => isset($order['table_id']) ? (int) $order['table_id'] : null,
            'action_key'   => 'pos.void_item',
            'action_label' => lang('app.audit_log_void_item'),
            'ref_code'     => $order['order_number'] ?? null,
            'meta_json'    => [
                'item_id'       => $itemId,
                'product_name'  => (string) ($item['product_name'] ?? ''),
                'qty'           => (int) ($item['qty'] ?? 0),
                'reason'        => $reason !== '' ? $reason : (lang('app.canceled') ?: 'Cancelled'),
                'from_status'   => 'pending',
                'to_status'     => 'cancel',
                'action_source' => 'pos.void_item',
                'override_by'   => $overrideData['approved_by_name'] ?? null,
            ],
        ]);

        if ($db->transStatus() === false) {
            $db->transRollback();

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.void_item_failed'),
            ]);
        }

        $db->transCommit();

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => lang('app.void_item_success'),
            'mode'    => 'voided',
        ]);
    } catch (\Throwable $e) {
        $db->transRollback();
        log_message('error', 'removeItem/voidItem error: ' . $e->getMessage());

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => lang('app.void_item_failed'),
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
                'payment_status' => 'paid',
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

        if ($status === 'served') {
            if (! $this->isServeItemFeatureEnabled()) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.feature_not_available_for_plan'),
                ]);
            }

            if (! $this->canServeItems()) {
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.no_permission'),
                ]);
            }
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

        if ($storedStatus === 'served') {
            $order = $this->getScopedOrder((int) ($item['order_id'] ?? 0), ['open', 'billing', 'paid']);

            $this->writeAuditLog([
                'target_type'  => 'order_item',
                'target_id'    => $itemId,
                'order_id'     => (int) ($item['order_id'] ?? 0),
                'table_id'     => isset($order['table_id']) ? (int) ($order['table_id'] ?? 0) : null,
                'action_key'   => 'pos.item_served',
                'action_label' => lang('app.audit_log_item_served'),
                'ref_code'     => $order['order_number'] ?? null,
                'meta_json'    => $this->buildServedAuditMeta(
                    array_merge($item, ['id' => $itemId]),
                    $order,
                    $currentStatus,
                    (string) ($data['served_at'] ?? date('Y-m-d H:i:s')),
                    'pos.update_item_status',
                    'pos_table'
                ),
            ], 'pos.item_served.' . $itemId . '.' . ($data['served_at'] ?? ''), 3);
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



    protected function parseSplitBillItemsFromRequest(): array
    {
        $items = $this->request->getPost('items');

        if (is_string($items) && trim($items) !== '') {
            $decoded = json_decode($items, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $items = $decoded;
            }
        }

        if (! is_array($items)) {
            return [];
        }

        $rows = [];
        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rows[] = [
                'order_item_id' => (int) ($row['order_item_id'] ?? 0),
                'split_qty'     => (int) ($row['split_qty'] ?? 0),
            ];
        }

        return $rows;
    }

    protected function splitBillViewPermissionDenied()
    {
        if ($this->userHasPermissionKey('cashier.split_bill_view') || $this->userHasPermissionKey('cashier.split_bill')) {
            return null;
        }

        return $this->response->setJSON([
            'status'  => 'error',
            'message' => lang('app.no_permission'),
            'code'    => 'NO_PERMISSION',
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


    public function reopenBill()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        if ($response = $this->jsonFeatureDenied('feature.reopen_bill.enabled')) {
            return $response;
        }

        $orderId = (int) $this->request->getPost('order_id');

        if ($response = $this->ensurePermissionOrManagerOverride('cashier.reopen_bill', 'reopen_bill', $orderId)) {
            return $response;
        }

        $order = $this->getScopedOrder($orderId);
        if (! $order) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        if (($order['status'] ?? '') !== 'billing') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_cannot_reopen_bill'),
            ]);
        }

        $this->recalculateOrderTotal($orderId);

        $this->orderModel->update($orderId, [
            'status'     => 'open',
            'closed_by'  => null,
            'closed_at'  => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $tableId = (int) ($order['table_id'] ?? 0);
        if ($tableId > 0) {
            $this->updateScopedTableStatus($tableId, 'occupied');
        }

        $this->writeAuditLog([
            'branch_id'    => (int) ($order['branch_id'] ?? 0) ?: null,
            'target_type'  => 'order',
            'target_id'    => $orderId,
            'action_key'   => 'cashier.reopen_bill',
            'action_label' => lang('app.audit_log_reopen_bill'),
            'ref_code'     => (string) ($order['order_number'] ?? ''),
            'order_id'     => $orderId,
            'table_id'     => $tableId ?: null,
            'meta_json'    => [
                'from_status' => 'billing',
                'to_status'   => 'open',
                'override_by' => session('override_approved_by_name') ?? null,
            ],
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => lang('app.reopen_bill_success'),
        ]);
    }


    public function undoPayment()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        if ($response = $this->jsonFeatureDenied('feature.refund_void.enabled')) {
            return $response;
        }

        $orderId = (int) $this->request->getPost('order_id');
        $reason = trim((string) $this->request->getPost('reason'));

        if ($reason === '') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.undo_payment_reason_required'),
            ]);
        }

        if ($response = $this->ensurePermissionOrManagerOverride('cashier.undo_payment', 'undo_payment', $orderId)) {
            return $response;
        }

        $order = $this->getScopedOrder($orderId);
        if (! $order) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        if (($order['status'] ?? '') !== 'paid') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_cannot_undo_payment'),
            ]);
        }

        $payments = $this->paymentModel->getByOrder($orderId);
        $activePayments = array_values(array_filter($payments, static function (array $payment): bool {
            return (string) ($payment['payment_status'] ?? 'paid') === 'paid';
        }));

        if (empty($activePayments)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_cannot_undo_payment'),
            ]);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $now = date('Y-m-d H:i:s');
            $userId = session()->get('user_id') ?: null;

            if ($db->fieldExists('payment_status', 'payments')) {
                foreach ($activePayments as $payment) {
                    $this->paymentModel->update((int) ($payment['id'] ?? 0), [
                        'payment_status' => 'voided',
                        'voided_by'      => $userId,
                        'voided_at'      => $now,
                        'void_reason'    => $reason,
                    ]);
                }
            } else {
                foreach ($activePayments as $payment) {
                    $this->paymentModel->delete((int) ($payment['id'] ?? 0));
                }
            }

            $this->orderModel->update($orderId, [
                'status'     => 'billing',
                'paid_by'    => null,
                'paid_at'    => null,
                'updated_at' => $now,
            ]);

            $tableId = (int) ($order['table_id'] ?? 0);
            if ($tableId > 0) {
                $this->updateScopedTableStatus($tableId, 'occupied');
            }

            if ($db->transStatus() === false) {
                $db->transRollback();

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.undo_payment_failed'),
                ]);
            }

            $db->transCommit();

            $this->writeAuditLog([
                'branch_id'    => (int) ($order['branch_id'] ?? 0) ?: null,
                'target_type'  => 'order',
                'target_id'    => $orderId,
                'action_key'   => 'cashier.undo_payment',
                'action_label' => lang('app.audit_log_undo_payment'),
                'ref_code'     => (string) ($order['order_number'] ?? ''),
                'order_id'     => $orderId,
                'table_id'     => $tableId ?: null,
                'payment_id'   => (int) ($activePayments[0]['id'] ?? 0) ?: null,
                'meta_json'    => [
                    'from_status'      => 'paid',
                    'to_status'        => 'billing',
                    'reason'           => $reason,
                    'voided_payments'  => array_map(static fn (array $payment): int => (int) ($payment['id'] ?? 0), $activePayments),
                    'override_by'      => session('override_approved_by_name') ?? null,
                ],
            ]);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => lang('app.undo_payment_success'),
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'undoPayment error: ' . $e->getMessage());

            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.undo_payment_failed'),
            ]);
        }
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
            $builder->where('tenant_id', $tenantId);
        }

        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'order_items')) {
            $builder->where('branch_id', $branchId);
        }

        $items = $builder->findAll();

        $subtotal = 0.0;
        $now      = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $normalizedItem = $this->normalizeOrderItemRowForResponse($item);
            $qty            = (int) ($normalizedItem['qty'] ?? 0);
            $unitPrice      = (float) ($normalizedItem['price'] ?? 0);
            $lineTotal      = $this->isBillableNormalizedOrderItem($normalizedItem) ? ($unitPrice * $qty) : 0.0;

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


    public function splitBillPreview()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        if ($response = $this->jsonFeatureDenied('feature.split_bill.enabled')) {
            return $response;
        }

        $orderId = (int) ($this->request->getPost('order_id') ?? 0);
        $items   = $this->parseSplitBillItemsFromRequest();
        $reason  = trim((string) ($this->request->getPost('reason') ?? ''));

        if ($response = $this->ensurePermissionOrManagerOverride('cashier.split_bill', 'split_bill', $orderId)) {
            return $response;
        }

        if ($orderId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        if (empty($items)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.split_bill_no_items_selected'),
            ]);
        }

        try {
            $preview = $this->splitBillService->preview(
                $orderId,
                $items,
                $this->currentTenantId(),
                $this->getCurrentBranchId()
            );

            return $this->response->setJSON([
                'status' => 'success',
                'message'=> lang('app.split_bill_preview_ready'),
                'data'   => $preview,
                'reason' => $reason,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'splitBillPreview error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function splitBillConfirm()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        if ($response = $this->jsonFeatureDenied('feature.split_bill.enabled')) {
            return $response;
        }

        $orderId = (int) ($this->request->getPost('order_id') ?? 0);
        $items   = $this->parseSplitBillItemsFromRequest();
        $reason  = trim((string) ($this->request->getPost('reason') ?? ''));

        if ($response = $this->ensurePermissionOrManagerOverride('cashier.split_bill', 'split_bill', $orderId)) {
            return $response;
        }

        if ($orderId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        if (empty($items)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.split_bill_no_items_selected'),
            ]);
        }

        try {
            $result = $this->splitBillService->confirm(
                $orderId,
                $items,
                $this->currentUserId(),
                $this->currentTenantId(),
                $this->getCurrentBranchId(),
                $reason
            );

            $order = $this->getScopedOrder($orderId);
            $this->writeAuditLog([
                'branch_id'    => (int) ($order['branch_id'] ?? 0) ?: null,
                'target_type'  => 'order',
                'target_id'    => (int) ($result['child_order_id'] ?? 0),
                'action_key'   => 'cashier.split_bill',
                'action_label' => lang('app.audit_log_split_bill'),
                'ref_code'     => (string) ($order['order_number'] ?? ''),
                'order_id'     => (int) ($result['parent_order_id'] ?? 0),
                'table_id'     => (int) ($order['table_id'] ?? 0) ?: null,
                'meta_json'    => [
                    'root_order_id'      => (int) ($result['root_order_id'] ?? 0),
                    'source_order_id'    => (int) ($result['parent_order_id'] ?? 0),
                    'target_order_id'    => (int) ($result['child_order_id'] ?? 0),
                    'child_order_number' => (string) ($result['child_order_number'] ?? ''),
                    'split_no'           => (int) ($result['split_no'] ?? 0),
                    'split_group_code'   => (string) ($result['split_group_code'] ?? ''),
                    'reason'             => $reason !== '' ? $reason : null,
                    'moved_items'        => $result['items'] ?? [],
                    'override_by'        => session('override_approved_by_name') ?? null,
                ],
            ]);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => lang('app.split_bill_success'),
                'data'    => $result,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'splitBillConfirm error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage() ?: lang('app.split_bill_failed'),
            ]);
        }
    }

    public function cashierOrderSplitGroup($orderId = null)
    {
        if ($response = $this->jsonFeatureDenied('pos.access', 'app.plan_cannot_access_pos')) {
            return $response;
        }

        if ($response = $this->splitBillViewPermissionDenied()) {
            return $response;
        }

        if ($response = $this->jsonFeatureDenied('feature.split_bill.enabled')) {
            return $response;
        }

        $orderId = (int) $orderId;
        if ($orderId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        try {
            $payload = $this->splitBillService->getSplitGroup($orderId, $this->currentTenantId(), $this->getCurrentBranchId());

            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $payload,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function cashierOrderSplitHistory($orderId = null)
    {
        if ($response = $this->jsonFeatureDenied('pos.access', 'app.plan_cannot_access_pos')) {
            return $response;
        }

        if ($response = $this->splitBillViewPermissionDenied()) {
            return $response;
        }

        if ($response = $this->jsonFeatureDenied('feature.split_bill.enabled')) {
            return $response;
        }

        $orderId = (int) $orderId;
        if ($orderId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.order_not_found'),
            ]);
        }

        try {
            $payload = $this->splitBillService->getSplitHistory($orderId, $this->currentTenantId(), $this->getCurrentBranchId());

            $this->writeAuditLog([
                'target_type'  => 'order',
                'target_id'    => $orderId,
                'action_key'   => 'cashier.split_bill_view',
                'action_label' => lang('app.audit_log_split_bill_view'),
                'order_id'     => $orderId,
                'meta_json'    => [
                    'screen'    => 'cashier_split_history',
                    'branch_id' => $this->getCurrentBranchId(),
                ],
            ], 'cashier.split_bill_view.' . $orderId . '.' . $this->getCurrentBranchId(), 5);

            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $payload,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
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
                    lang('app.move_table') . ' #' . $fromTableId . ' -> #' . $toTableId,
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
                            'reason'        => $reason !== '' ? $reason : (lang('app.canceled') ?: 'Cancelled'),
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

            $this->writeAuditLog([
                'branch_id'    => (int) ($order['branch_id'] ?? 0) ?: null,
                'target_type'  => 'order',
                'target_id'    => $orderId,
                'action_key'   => 'pos.move_table',
                'action_label' => lang('app.move_table'),
                'ref_code'     => (string) ($order['order_number'] ?? ''),
                'order_id'     => $orderId,
                'table_id'     => $toTableId > 0 ? $toTableId : null,
                'meta_json'    => [
                    'from_table_id' => $fromTableId,
                    'to_table_id'   => $toTableId,
                    'reason'        => $reason !== '' ? $reason : null,
                ],
            ]);

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

        if ($response = $this->jsonFeatureDenied('feature.merge_bill.enabled')) {
            return $response;
        }

        if (! $this->userHasPermissionKey('pos.open_table')) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.no_permission'),
                'code'    => 'NO_PERMISSION',
            ]);
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
                $builder->where('tenant_id', $tenantId);
            }

            if ($branchId > 0 && $this->db->fieldExists('branch_id', 'order_items')) {
                $builder->where('branch_id', $branchId);
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

            $this->writeAuditLog([
                'branch_id'    => $branchId > 0 ? $branchId : null,
                'target_type'  => 'order',
                'target_id'    => $sourceOrderId,
                'action_key'   => 'pos.merge_bill',
                'action_label' => lang('app.merge_bill'),
                'ref_code'     => (string) ($sourceOrder['order_number'] ?? ''),
                'order_id'     => $sourceOrderId,
                'table_id'     => $fromTableId > 0 ? $fromTableId : null,
                'meta_json'    => [
                    'source_order_id' => $sourceOrderId,
                    'target_order_id' => $targetOrderId,
                    'source_table_id' => $fromTableId > 0 ? $fromTableId : null,
                    'target_table_id' => $toTableId > 0 ? $toTableId : null,
                    'reason'          => $reason !== '' ? $reason : null,
                ],
            ]);

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