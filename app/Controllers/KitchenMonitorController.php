<?php

namespace App\Controllers;

use App\Models\KitchenLogModel;
use App\Models\KitchenStationModel;
use App\Models\KitchenTicketModel;
use App\Models\OrderItemModel;

class KitchenMonitorController extends BaseController
{
    protected $kitchenTicketModel;
    protected $kitchenStationModel;
    protected $orderItemModel;
    protected $kitchenLogModel;

    public function __construct()
    {
        $this->kitchenTicketModel  = new KitchenTicketModel();
        $this->kitchenStationModel = new KitchenStationModel();
        $this->orderItemModel      = new OrderItemModel();
        $this->kitchenLogModel     = new KitchenLogModel();
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