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
    }

    protected function getActiveOrderStatuses(): array
    {
        return ['open', 'billing'];
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

		$sourceTableId = (int) ($merge['source_table_id'] ?? 0);
		$targetTableId = (int) ($merge['target_table_id'] ?? 0);
		$targetOrderId = (int) ($merge['target_order_id'] ?? 0);

		$sourceTableName = null;
		$targetTableName = null;
		$targetOrderNumber = null;

		if ($sourceTableId > 0 && method_exists($this->tableModel, 'getTableMapByIds')) {
			$sourceMap = $this->tableModel->getTableMapByIds([$sourceTableId], $branchId);
			$sourceTableName = $sourceMap[$sourceTableId]['table_name'] ?? null;
		}

		if ($targetTableId > 0 && method_exists($this->tableModel, 'getTableMapByIds')) {
			$targetMap = $this->tableModel->getTableMapByIds([$targetTableId], $branchId);
			$targetTableName = $targetMap[$targetTableId]['table_name'] ?? null;
		}

		if ($targetOrderId > 0) {
			$targetOrder = $this->orderModel
				->where('tenant_id', $this->currentTenantId())
				->where('branch_id', $branchId)
				->where('id', $targetOrderId)
				->first();

			if ($targetOrder) {
				$targetOrderNumber = $targetOrder['order_number'] ?? null;
			}
		}

		return [
			'source_order_id'      => (int) ($merge['source_order_id'] ?? 0),
			'target_order_id'      => $targetOrderId,
			'source_table_id'      => $sourceTableId,
			'target_table_id'      => $targetTableId,
			'source_table_name'    => $sourceTableName,
			'target_table_name'    => $targetTableName,
			'target_order_number'  => $targetOrderNumber,
			'reason'               => $merge['reason'] ?? null,
			'can_open_new_order'   => true,
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

		$items = $this->orderItemModel->getByOrder((int) $order['id']);

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
				'line_total'     => $finalPrice,
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
                $lineTotal = (float) ($item['price'] ?? 0) * $qty;

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

            if ($db->transStatus() === false) {
                $db->transRollback();

                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => lang('app.send_kitchen_failed'),
                ]);
            }

            $db->transCommit();

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

		$pendingItems = $this->orderItemModel->countPendingByOrder($orderId);
		if ($pendingItems > 0) {
			return $this->response->setJSON([
				'status'  => 'error',
				'message' => lang('app.pending_items_must_send_first'),
			]);
		}

		$total = (float) ($order['total_price'] ?? 0);
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

    public function updateItemStatus()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        $status = strtolower(trim((string) ($this->request->getPost('status') ?? '')));

        $allowedStatuses = ['sent', 'preparing', 'ready', 'served', 'cancelled'];

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

        $data = [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($status === 'preparing') {
            $data['started_at'] = date('Y-m-d H:i:s');
        }

        if ($status === 'ready') {
            $data['ready_at'] = date('Y-m-d H:i:s');
        }

        if ($status === 'served') {
            $data['served_at'] = date('Y-m-d H:i:s');
        }

        if (! $this->orderItemModel->update($itemId, $data)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.save_failed'),
            ]);
        }

        if (isset($this->kitchenLogModel) && $this->kitchenLogModel) {
            try {
                if (method_exists($this->kitchenLogModel, 'addLog')) {
                    $this->kitchenLogModel->addLog(
                        $itemId,
                        $status,
                        lang('app.kitchen_status_updated')
                    );
                } else {
                    $this->kitchenLogModel->insert([
                        'tenant_id'    => (int) ($item['tenant_id'] ?? current_tenant_id()),
                        'order_id'     => (int) ($item['order_id'] ?? 0),
                        'order_item_id'=> $itemId,
                        'status'       => $status,
                        'note'         => lang('app.kitchen_status_updated'),
                        'created_by'   => (int) (session('user_id') ?? 0),
                    ]);
                }
            } catch (\Throwable $e) {
                log_message('error', 'updateItemStatus kitchen log error: ' . $e->getMessage());
            }
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => lang('app.save_success'),
        ]);
    }

    public function requestBill()
    {
        if ($response = $this->jsonPosWriteDenied()) {
            return $response;
        }

        $orderId = (int) $this->request->getPost('order_id');

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

        $pendingItems = $this->orderItemModel->countPendingByOrder($orderId);

        if ($pendingItems > 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.pending_items_must_send_first'),
            ]);
        }

        $this->orderModel->update($orderId, [
            'status'     => 'billing',
            'closed_by'  => session()->get('user_id') ?: null,
            'closed_at'  => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
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

        $pendingItems = $this->orderItemModel->countPendingByOrder($orderId);

        if ($pendingItems > 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.pending_items_must_send_first'),
            ]);
        }

        $this->orderModel->update($orderId, [
            'status'     => 'billing',
            'closed_by'  => session()->get('user_id') ?: null,
            'closed_at'  => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => lang('app.close_bill_success_billing'),
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
			->where('order_id', $orderId)
			->where('status !=', 'cancel');

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

        $subtotal = 0;
        $now      = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $qty       = (int) ($item['qty'] ?? 0);
            $unitPrice = (float) ($item['price'] ?? 0);
            $lineTotal = $unitPrice * $qty;

            $this->orderItemModel->update((int) $item['id'], [
                'line_total' => $lineTotal,
                'updated_at' => $now,
            ]);

            $subtotal += $lineTotal;
        }

        $discount = 0;
        $service  = 0;
        $vat      = 0;
        $total    = $subtotal - $discount + $service + $vat;

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
                'line_total'     => $lineTotal,
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