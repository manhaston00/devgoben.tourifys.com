<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\KitchenTicketModel;
use App\Models\KitchenStationModel;
use App\Models\OrderItemModel;
use App\Models\KitchenLogModel;

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

    public function index()
    {
        $stationId = (int) ($this->request->getGet('station_id') ?? 0);

        $stations = method_exists($this->kitchenStationModel, 'getVisibleStations')
            ? $this->kitchenStationModel->getVisibleStations(true)
            : $this->kitchenStationModel->findAll();

        return view('kitchen_monitor/index', [
            'title'           => lang('app.kitchen_monitor'),
            'stationId'       => $stationId,
            'stations'        => $stations,
            'defaultMode'     => $stationId > 0 ? 'station' : 'all',
            'pollingSeconds'  => 5,
        ]);
    }

    public function feed()
    {
        $stationId = (int) ($this->request->getGet('station_id') ?? 0);
        $mode      = trim((string) ($this->request->getGet('mode') ?? 'all'));

        $rows = $this->kitchenTicketModel->getMonitorBoardRows(
            $this->currentTenantId(),
            $this->currentBranchId(),
            $stationId > 0 ? $stationId : null,
            $mode === 'station'
        );

        $grouped = [
			'new'       => [],
			'preparing' => [],
			'ready'     => [],
			'served'    => [],
		];

		foreach ($rows as $row) {
			$itemStatus = strtolower((string) ($row['item_status'] ?? ''));
			$ticketStatus = strtolower((string) ($row['ticket_status'] ?? ''));

			if ($itemStatus === 'served') {
				$grouped['served'][] = $row;
				continue;
			}

			if ($itemStatus === 'cooking') {
				$grouped['preparing'][] = $row;
				continue;
			}

			if ($itemStatus === 'sent') {
				if ($ticketStatus === 'done') {
					$grouped['ready'][] = $row;
				} else {
					$grouped['new'][] = $row;
				}
				continue;
			}

			if ($itemStatus === 'pending') {
				$grouped['new'][] = $row;
				continue;
			}
		}

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $grouped,
        ]);
    }

    public function updateStatus()
    {
        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        $status = strtolower(trim((string) ($this->request->getPost('status') ?? '')));

        $allowed = ['new', 'sent', 'preparing', 'ready', 'served', 'cancelled'];

        if ($itemId <= 0 || ! in_array($status, $allowed, true)) {
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

        if ($status === 'sent' && empty($item['sent_at'])) {
            $data['sent_at'] = date('Y-m-d H:i:s');
        }

        if ($status === 'served') {
            $data['served_at'] = date('Y-m-d H:i:s');
        }

        if ($status === 'cancelled') {
            $data['cancelled_at'] = date('Y-m-d H:i:s');
            $data['cancelled_by'] = (int) (session('user_id') ?? 0);
        }

        if (! $this->orderItemModel->update($itemId, $data)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => lang('app.save_failed'),
            ]);
        }

        try {
            if (method_exists($this->kitchenLogModel, 'addLog')) {
                $this->kitchenLogModel->addLog(
                    $itemId,
                    $status,
                    lang('app.kitchen_status_updated')
                );
            }
        } catch (\Throwable $e) {
            log_message('error', 'Kitchen monitor addLog error: ' . $e->getMessage());
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => lang('app.save_success'),
        ]);
    }
}