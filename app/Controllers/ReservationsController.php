<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\ReservationLogModel;
use App\Models\ReservationModel;
use App\Models\ReservationTableModel;
use App\Models\TableModel;

class ReservationsController extends BaseController
{
    protected $reservationModel;
    protected $reservationTableModel;
    protected $reservationLogModel;
    protected $tableModel;
    protected $orderModel;
    protected $db;

    public function __construct()
    {
        $this->db                    = \Config\Database::connect();
        $this->reservationModel      = new ReservationModel();
        $this->reservationTableModel = new ReservationTableModel();
        $this->reservationLogModel   = new ReservationLogModel();
        $this->tableModel            = new TableModel();
        $this->orderModel            = new OrderModel();
    }

    protected function denyIfNoPermission(string $permissionKey)
    {
        if (!function_exists('can') || !can($permissionKey)) {
            return redirect()->to(site_url('/'))->with('error', 'คุณไม่มีสิทธิ์เข้าถึงส่วนนี้');
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
	
	protected function getCurrentTenantId(): ?int
	{
		return function_exists('current_tenant_id') ? current_tenant_id() : null;
	}

    protected function getScopedReservation(int $id, bool $withDeleted = false): ?array
	{
		if ($id <= 0) {
			return null;
		}

		$branchId = $this->getCurrentBranchId();
		$tenantId = $this->getCurrentTenantId();

		if ($withDeleted && method_exists($this->reservationModel, 'getFullWithDeleted')) {
			$row = $this->reservationModel->getFullWithDeleted($id, $branchId);
		} else {
			$row = $this->reservationModel->getFull($id, $branchId);
		}

		if (! $row) {
			return null;
		}

		if ($tenantId && (int) ($row['tenant_id'] ?? 0) !== (int) $tenantId) {
			return null;
		}

		return $row;
	}

    protected function zoneBelongsToBranch(int $zoneId, int $branchId): bool
    {
        if ($zoneId <= 0) {
            return true;
        }

        $builder = $this->db->table('zones')->where('id', $zoneId);

        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'zones')) {
            $builder->where('branch_id', $branchId);
        }

        return !empty($builder->get()->getRowArray());
    }

    protected function selectedTablesBelongToBranch(array $tableIds, int $branchId): bool
    {
        if (empty($tableIds)) {
            return false;
        }

        $tableIds = array_values(array_unique(array_map('intval', $tableIds)));
        $tableIds = array_values(array_filter($tableIds, static function ($id) {
            return $id > 0;
        }));

        if (empty($tableIds)) {
            return false;
        }

        $tableMap = $this->tableModel->getTableMapByIds($tableIds, $branchId);

        return count($tableMap) === count($tableIds);
    }


    protected function normalizeTableIds($tableIds): array
    {
        if (!is_array($tableIds)) {
            return [];
        }

        $tableIds = array_map('intval', $tableIds);
        $tableIds = array_filter($tableIds, static function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($tableIds));
    }

    protected function getOpenOrderBusyTables(?int $branchId = null): array
    {
        $branchId = $branchId ?: $this->getCurrentBranchId();

        $builder = $this->db->table('orders o');
        $builder->select('o.id AS order_id, o.table_id, o.created_at, o.status, o.branch_id');
        $builder->where('o.table_id IS NOT NULL', null, false);

        if ($branchId > 0 && $this->db->fieldExists('branch_id', 'orders')) {
            $builder->where('o.branch_id', $branchId);
        }

        $builder->groupStart();
            $builder->whereIn('o.status', ['open', 'billing']);
        $builder->groupEnd();

        $rows = $builder->get()->getResultArray();
        $map = [];

        foreach ($rows as $row) {
            $tableId = (int) ($row['table_id'] ?? 0);
            if ($tableId <= 0) {
                continue;
            }

            $map[$tableId] = [
                'order_id'     => (int) ($row['order_id'] ?? 0),
                'busy_reason'  => 'open_order',
                'busy_until'   => null,
                'order_at'     => $row['created_at'] ?? null,
                'order_status' => $row['status'] ?? null,
            ];
        }

        return $map;
    }

    protected function validateSelectedTablesAvailable(
        array $selectedTableIds,
        string $reservationDate,
        string $reservationTime,
        int $durationMinutes,
        ?int $excludeReservationId = null
    ): array {
        $errors   = [];
        $branchId = $this->getCurrentBranchId();

        if (empty($selectedTableIds)) {
            $errors[] = 'กรุณาเลือกโต๊ะอย่างน้อย 1 โต๊ะ';
            return $errors;
        }

        if (! $this->selectedTablesBelongToBranch($selectedTableIds, $branchId)) {
            $errors[] = 'มีโต๊ะที่ไม่อยู่ในสาขาปัจจุบันหรือไม่สามารถใช้งานได้';
            return $errors;
        }

        $conflictTableIds = $this->reservationModel->getConflictingReservationTableIds(
            $reservationDate,
            $reservationTime,
            $durationMinutes,
            $excludeReservationId,
            $branchId
        );

        $openOrderBusyMap = $this->getOpenOrderBusyTables($branchId);
        $tableMap = $this->tableModel->getTableMapByIds($selectedTableIds, $branchId);

        foreach ($selectedTableIds as $tableId) {
            $tableName = $tableMap[$tableId]['table_name'] ?? ('โต๊ะ #' . $tableId);

            if (in_array($tableId, $conflictTableIds, true)) {
                $errors[] = $tableName . ' ถูกจองทับช่วงเวลาแล้ว';
            }

            if (isset($openOrderBusyMap[$tableId])) {
                $errors[] = $tableName . ' กำลังเปิดบิลใช้งานอยู่';
            }
        }

        return array_values(array_unique($errors));
    }

    protected function setTablesStatus(array $tableIds, string $status): void
    {
        if (empty($tableIds)) {
            return;
        }

        $branchId = $this->getCurrentBranchId();
        $tableMap = $this->tableModel->getTableMapByIds($tableIds, $branchId);

        foreach ($tableIds as $tableId) {
            $tableId = (int) $tableId;
            if ($tableId <= 0 || empty($tableMap[$tableId])) {
                continue;
            }

            $table = $tableMap[$tableId];

            if (($table['status'] ?? '') === 'disabled') {
                continue;
            }

            if ($status === 'available') {
                $openOrder = $this->orderModel
                    ->where('table_id', $tableId)
                    ->where('branch_id', $branchId)
                    ->whereIn('status', ['open', 'billing'])
                    ->orderBy('id', 'DESC')
                    ->first();

                if ($openOrder) {
                    continue;
                }
            }

            $this->tableModel->update($tableId, [
                'status' => $status,
            ]);
        }
    }

    public function index()
    {
        if ($resp = $this->denyIfNoPermission('reservations.view')) {
            return $resp;
        }

        $filters = [
			'date'    => trim((string) $this->request->getGet('date')),
			'status'  => trim((string) $this->request->getGet('status')),
			'zone_id' => (int) ($this->request->getGet('zone_id') ?? 0),
			'keyword' => trim((string) $this->request->getGet('keyword')),
		];

        $reservations = $this->reservationModel->getList($filters, $this->getCurrentBranchId());

        return view('reservations/index', [
            'title'        => 'รายการจองโต๊ะ',
            'reservations' => $reservations,
            'filters'      => $filters,
        ]);
    }

    public function create()
    {
        if ($resp = $this->denyIfNoPermission('reservations.create')) {
            return $resp;
        }

        $tables = $this->tableModel->getUsableTables($this->getCurrentBranchId());

        return view('reservations/create', [
            'title'  => 'เพิ่มการจองโต๊ะ',
            'tables' => $tables,
        ]);
    }

    public function store()
    {
        if ($resp = $this->denyIfNoPermission('reservations.create')) {
            return $resp;
        }

        $customerName    = trim((string) $this->request->getPost('customer_name'));
        $customerPhone   = trim((string) $this->request->getPost('customer_phone'));
        $reservationDate = trim((string) $this->request->getPost('reservation_date'));
        $reservationTime = trim((string) $this->request->getPost('reservation_time'));
        $guestCount      = (int) $this->request->getPost('guest_count');
        $durationMinutes = (int) ($this->request->getPost('duration_minutes') ?: 60);
        $zoneId          = (int) ($this->request->getPost('zone_id') ?: 0);
        $source          = trim((string) ($this->request->getPost('source') ?: 'phone'));
        $specialNote     = trim((string) $this->request->getPost('special_note'));
        $internalNote    = trim((string) $this->request->getPost('internal_note'));
        $tableIds        = $this->normalizeTableIds($this->request->getPost('table_ids'));
        $branchId        = $this->getCurrentBranchId();

        if ($customerName === '' || $reservationDate === '' || $reservationTime === '' || $guestCount <= 0) {
            return redirect()->back()->withInput()->with('error', 'กรุณากรอกข้อมูลให้ครบ');
        }

        if (empty($tableIds)) {
            return redirect()->back()->withInput()->with('error', 'กรุณาเลือกโต๊ะอย่างน้อย 1 โต๊ะ');
        }

        if ($branchId <= 0) {
            return redirect()->back()->withInput()->with('error', 'ไม่พบสาขาปัจจุบัน');
        }

        if (! $this->zoneBelongsToBranch($zoneId, $branchId)) {
            return redirect()->back()->withInput()->with('error', 'โซนที่เลือกไม่อยู่ในสาขาปัจจุบัน');
        }

        $tableErrors = $this->validateSelectedTablesAvailable(
            $tableIds,
            $reservationDate,
            $reservationTime,
            $durationMinutes,
            null
        );

        if (!empty($tableErrors)) {
            return redirect()->back()->withInput()->with('error', implode(' / ', $tableErrors));
        }

        $insertData = [
			'tenant_id'        => function_exists('current_tenant_id') ? current_tenant_id() : null,
			'reservation_code' => $this->reservationModel->generateReservationCode(),
			'customer_name'    => $customerName,
			'customer_phone'   => $customerPhone ?: null,
			'reservation_date' => $reservationDate,
			'reservation_time' => $reservationTime,
			'guest_count'      => $guestCount,
			'duration_minutes' => $durationMinutes,
			'branch_id'        => $branchId,
			'zone_id'          => $zoneId ?: null,
			'status'           => 'confirmed',
			'source'           => $source ?: 'phone',
			'special_note'     => $specialNote ?: null,
			'internal_note'    => $internalNote ?: null,
			'created_by'       => session()->get('user_id') ?: null,
			'updated_by'       => session()->get('user_id') ?: null,
		];

        $this->db->transBegin();

        try {
            $insertResult = $this->reservationModel->insert($insertData);

            if ($insertResult === false) {
                $this->db->transRollback();

                $modelErrors = $this->reservationModel->errors();
                $dbError     = $this->db->error();

                log_message('error', 'reservation insert model errors: ' . json_encode($modelErrors, JSON_UNESCAPED_UNICODE));
                log_message('error', 'reservation insert db error: ' . json_encode($dbError, JSON_UNESCAPED_UNICODE));
                log_message('error', 'reservation insert data: ' . json_encode($insertData, JSON_UNESCAPED_UNICODE));

                $errorText = 'บันทึกการจองไม่สำเร็จ';
                if (!empty($modelErrors)) {
                    $errorText .= ' : ' . implode(' / ', $modelErrors);
                } elseif (!empty($dbError['message'])) {
                    $errorText .= ' : ' . $dbError['message'];
                }

                return redirect()->back()->withInput()->with('error', $errorText);
            }

            $reservationId = (int) $this->reservationModel->getInsertID();

            if ($reservationId <= 0) {
                $this->db->transRollback();
                return redirect()->back()->withInput()->with('error', 'ไม่สามารถสร้างเลขที่การจองได้');
            }

            $this->reservationTableModel->replaceReservationTables($reservationId, $tableIds);
            $this->reservationLogModel->addLog($reservationId, 'create', null, 'confirmed', 'สร้างรายการจอง');

            if ($this->db->transStatus() === false) {
                $dbError = $this->db->error();
                $this->db->transRollback();

                log_message('error', 'reservation transaction error: ' . json_encode($dbError, JSON_UNESCAPED_UNICODE));

                return redirect()->back()->withInput()->with(
                    'error',
                    'บันทึกการจองไม่สำเร็จ' . (!empty($dbError['message']) ? ' : ' . $dbError['message'] : '')
                );
            }

            $this->db->transCommit();

            return redirect()->to(site_url('reservations'))->with('success', 'บันทึกการจองเรียบร้อย');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'reservation store error: ' . $e->getMessage());
            log_message('error', 'reservation store trace: ' . $e->getTraceAsString());

            return redirect()->back()->withInput()->with('error', 'เกิดข้อผิดพลาดระหว่างบันทึกการจอง : ' . $e->getMessage());
        }
    }

    public function view($id)
    {
        if ($resp = $this->denyIfNoPermission('reservations.view')) {
            return $resp;
        }

        $id  = (int) $id;
        $row = $this->getScopedReservation($id);

        if (!$row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Reservation not found');
        }

        $tableIds = $this->reservationTableModel->getTableIdsByReservation($id);

        return view('reservations/view', [
            'title'    => 'รายละเอียดการจอง',
            'row'      => $row,
            'tableIds' => $tableIds,
        ]);
    }

    public function edit($id)
    {
        if ($resp = $this->denyIfNoPermission('reservations.edit')) {
            return $resp;
        }

        $id = (int) $id;
        $reservation = $this->getScopedReservation($id);

        if (!$reservation) {
            return redirect()->to(site_url('reservations'))->with('error', 'ไม่พบข้อมูลการจอง');
        }

        $tables   = $this->tableModel->getUsableTables();
        $tableIds = $this->reservationTableModel->getTableIdsByReservation($id);

        return view('reservations/edit', [
            'title'       => 'แก้ไขการจอง',
            'reservation' => $reservation,
            'tables'      => $tables,
            'tableIds'    => $tableIds,
        ]);
    }

    public function update($id)
    {
        if ($resp = $this->denyIfNoPermission('reservations.edit')) {
            return $resp;
        }

        $id          = (int) $id;
        $reservation = $this->getScopedReservation($id);

        if (!$reservation) {
            return redirect()->to(site_url('reservations'))->with('error', 'ไม่พบรายการจอง');
        }

        $customerName    = trim((string) $this->request->getPost('customer_name'));
        $customerPhone   = trim((string) $this->request->getPost('customer_phone'));
        $reservationDate = trim((string) $this->request->getPost('reservation_date'));
        $reservationTime = trim((string) $this->request->getPost('reservation_time'));
        $guestCount      = (int) $this->request->getPost('guest_count');
        $durationMinutes = (int) ($this->request->getPost('duration_minutes') ?: 60);
        $zoneId          = (int) ($this->request->getPost('zone_id') ?: 0);
        $source          = trim((string) ($this->request->getPost('source') ?: 'phone'));
        $specialNote     = trim((string) $this->request->getPost('special_note'));
        $internalNote    = trim((string) $this->request->getPost('internal_note'));
        $status          = trim((string) ($this->request->getPost('status') ?: ($reservation['status'] ?? 'confirmed')));
        $tableIds        = $this->normalizeTableIds($this->request->getPost('table_ids'));
        $branchId        = $this->getCurrentBranchId();

        if ($customerName === '' || $reservationDate === '' || $reservationTime === '' || $guestCount <= 0) {
            return redirect()->back()->withInput()->with('error', 'กรุณากรอกข้อมูลให้ครบ');
        }

        if (empty($tableIds)) {
            return redirect()->back()->withInput()->with('error', 'กรุณาเลือกโต๊ะอย่างน้อย 1 โต๊ะ');
        }

        if ($branchId <= 0) {
            return redirect()->back()->withInput()->with('error', 'ไม่พบสาขาปัจจุบัน');
        }

        if (! $this->zoneBelongsToBranch($zoneId, $branchId)) {
            return redirect()->back()->withInput()->with('error', 'โซนที่เลือกไม่อยู่ในสาขาปัจจุบัน');
        }

        $tableErrors = $this->validateSelectedTablesAvailable(
            $tableIds,
            $reservationDate,
            $reservationTime,
            $durationMinutes,
            $id
        );

        if (!empty($tableErrors)) {
            return redirect()->back()->withInput()->with('error', implode(' / ', $tableErrors));
        }

        $allowedStatuses = ['pending', 'confirmed', 'cancelled', 'completed', 'seated', 'no_show'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = $reservation['status'] ?? 'confirmed';
        }

        $oldStatus = $reservation['status'] ?? null;

        $updateData = [
            'customer_name'    => $customerName,
            'customer_phone'   => $customerPhone ?: null,
            'reservation_date' => $reservationDate,
            'reservation_time' => $reservationTime,
            'guest_count'      => $guestCount,
            'duration_minutes' => $durationMinutes,
            'zone_id'          => $zoneId ?: null,
            'source'           => $source ?: 'phone',
            'special_note'     => $specialNote ?: null,
            'internal_note'    => $internalNote ?: null,
            'status'           => $status,
            'updated_by'       => session()->get('user_id') ?: null,
        ];

        if ($status === 'cancelled' && empty($reservation['cancelled_at'])) {
            $updateData['cancelled_at'] = date('Y-m-d H:i:s');
        }

        if ($status === 'no_show' && empty($reservation['no_show_at'])) {
            $updateData['no_show_at'] = date('Y-m-d H:i:s');
        }

        if ($status === 'completed' && empty($reservation['completed_at'])) {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }

        $this->db->transBegin();

        try {
            $result = $this->reservationModel->update($id, $updateData);

            if ($result === false) {
                $this->db->transRollback();

                $modelErrors = $this->reservationModel->errors();
                $errorText   = 'แก้ไขการจองไม่สำเร็จ';

                if (!empty($modelErrors)) {
                    $errorText .= ' : ' . implode(' / ', $modelErrors);
                }

                return redirect()->back()->withInput()->with('error', $errorText);
            }

            $this->reservationTableModel->replaceReservationTables($id, $tableIds);

			if (in_array($status, ['cancelled', 'no_show', 'completed'], true)) {
				$this->setTablesStatus($tableIds, 'available');
			} elseif ($status === 'seated') {
				$this->setTablesStatus($tableIds, 'occupied');
			}

            $this->reservationLogModel->addLog($id, 'update', $oldStatus, $status, 'แก้ไขรายการจอง');

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return redirect()->back()->withInput()->with('error', 'แก้ไขการจองไม่สำเร็จ');
            }

            $this->db->transCommit();

            return redirect()->to(site_url('reservations'))->with('success', 'แก้ไขการจองเรียบร้อย');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'reservation update error: ' . $e->getMessage());
            log_message('error', 'reservation update trace: ' . $e->getTraceAsString());

            return redirect()->back()->withInput()->with('error', 'เกิดข้อผิดพลาดระหว่างแก้ไขการจอง : ' . $e->getMessage());
        }
    }

    public function cancel($id)
    {
        if ($resp = $this->denyIfNoPermission('reservations.edit')) {
            return $resp;
        }

        return $this->changeStatus((int) $id, 'cancelled', 'cancel', 'ยกเลิกการจอง');
    }

    public function noShow($id)
    {
        if ($resp = $this->denyIfNoPermission('reservations.edit')) {
            return $resp;
        }

        return $this->changeStatus((int) $id, 'no_show', 'no_show', 'ลูกค้าไม่มาตามนัด');
    }

    public function checkin($id)
    {
        if ($resp = $this->denyIfNoPermission('reservations.edit')) {
            return $resp;
        }

        $id  = (int) $id;
        $row = $this->getScopedReservation($id);

        if (!$row) {
            return redirect()->to(site_url('reservations'))->with('error', 'ไม่พบรายการจอง');
        }

        $oldStatus = $row['status'] ?? null;

        $this->db->transBegin();

        try {
            $this->reservationModel->update($id, [
                'status'     => 'seated',
                'checkin_at' => date('Y-m-d H:i:s'),
                'seated_at'  => date('Y-m-d H:i:s'),
                'updated_by' => session()->get('user_id') ?: null,
            ]);

            $tableIds = $this->reservationTableModel->getTableIdsByReservation($id);
            $this->setTablesStatus($tableIds, 'occupied');

            $this->reservationLogModel->addLog($id, 'checkin', $oldStatus, 'seated', 'เช็กอินลูกค้า');

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return redirect()->to(site_url('reservations'))->with('error', 'เช็กอินไม่สำเร็จ');
            }

            $this->db->transCommit();

            return redirect()->to(site_url('reservations'))->with('success', 'เช็กอินเรียบร้อย');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'reservation checkin error: ' . $e->getMessage());

            return redirect()->to(site_url('reservations'))->with('error', 'เกิดข้อผิดพลาดระหว่างเช็กอิน');
        }
    }

    public function openBill($id)
	{
		if ($resp = $this->denyIfNoPermission('reservations.edit')) {
			return $resp;
		}

		if ($resp = $this->denyIfNoPermission('pos.view')) {
			return $resp;
		}

		$id          = (int) $id;
		$reservation = $this->getScopedReservation($id);

		if (!$reservation) {
			return redirect()->to(site_url('reservations'))->with('error', 'ไม่พบรายการจอง');
		}

		$tableIds = $this->reservationTableModel->getTableIdsByReservation($id);
		if (empty($tableIds)) {
			return redirect()->to(site_url('reservations'))->with('error', 'ไม่พบโต๊ะในรายการจอง');
		}

		$tableId = (int) $tableIds[0];
		$table = $this->tableModel->getUsableTable($tableId, $this->getCurrentBranchId());

		if (!$table) {
			return redirect()->to(site_url('reservations'))->with('error', 'ไม่พบข้อมูลโต๊ะ');
		}

		if ((int) ($table['is_active'] ?? 1) !== 1 || ($table['status'] ?? '') === 'disabled') {
			return redirect()->to(site_url('reservations'))->with('error', 'โต๊ะนี้ไม่พร้อมใช้งาน');
		}

		// ถ้ามี order_id เดิม ให้เช็กก่อนว่ายังเป็นบิลเปิดอยู่จริงไหม
		if (!empty($reservation['order_id'])) {
			$existingOrder = $this->orderModel
				->where('id', (int) $reservation['order_id'])
				->where('tenant_id', $this->getCurrentTenantId())
				->where('branch_id', $this->getCurrentBranchId())
				->first();

			if ($existingOrder && in_array(($existingOrder['status'] ?? ''), ['open', 'billing'], true)) {
				return redirect()->to(site_url('pos/table/' . $tableId))
					->with('error', 'รายการจองนี้เปิดบิลแล้ว');
			}
		}

		// เช็กว่าที่โต๊ะนี้ยังมีบิลเปิดอยู่หรือไม่
		$openOrderOnTable = $this->orderModel
			->where('table_id', $tableId)
			->where('branch_id', $this->getCurrentBranchId())
			->whereIn('status', ['open', 'billing'])
			->orderBy('id', 'DESC')
			->first();

		if ($openOrderOnTable) {
			return redirect()->to(site_url('pos/table/' . $tableId))
				->with('error', 'โต๊ะนี้มีบิลเปิดอยู่แล้ว');
		}

		$this->db->transBegin();

		try {
			// ล้าง order_id เก่าก่อน ถ้าเคยมีแต่ปิดไปแล้ว
			if (!empty($reservation['order_id'])) {
				$this->reservationModel->update($id, [
					'order_id'   => null,
					'updated_by' => session()->get('user_id') ?: null,
				]);
			}

			$this->orderModel->insert([
				'tenant_id'       => function_exists('current_tenant_id') ? current_tenant_id() : null,
				'branch_id'       => $this->getCurrentBranchId(),
				'table_id'        => $tableId,
				'reservation_id'  => $id,
				'order_number'    => 'ORD' . date('YmdHis') . rand(10, 99),
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

			if ($orderId <= 0) {
				$this->db->transRollback();
				return redirect()->to(site_url('reservations'))->with('error', 'ไม่สามารถสร้างบิลใหม่ได้');
			}

			$this->reservationModel->update($id, [
				'status'     => 'seated',
				'order_id'   => $orderId,
				'checkin_at' => !empty($reservation['checkin_at']) ? $reservation['checkin_at'] : date('Y-m-d H:i:s'),
				'seated_at'  => !empty($reservation['seated_at']) ? $reservation['seated_at'] : date('Y-m-d H:i:s'),
				'updated_by' => session()->get('user_id') ?: null,
			]);

			$this->tableModel->update($tableId, [
				'status' => 'occupied',
			]);

			$this->reservationLogModel->addLog($id, 'open_bill', $reservation['status'] ?? null, 'seated', 'เปิดบิลจากการจอง');

			if ($this->db->transStatus() === false) {
				$this->db->transRollback();
				return redirect()->to(site_url('reservations'))->with('error', 'เปิดบิลไม่สำเร็จ');
			}

			$this->db->transCommit();

			return redirect()->to(site_url('pos/table/' . $tableId))->with('success', 'เปิดบิลใหม่จากการจองเรียบร้อย');
		} catch (\Throwable $e) {
			$this->db->transRollback();
			log_message('error', 'reservation openBill error: ' . $e->getMessage());
			log_message('error', 'reservation openBill trace: ' . $e->getTraceAsString());

			return redirect()->to(site_url('reservations'))->with('error', 'เกิดข้อผิดพลาดระหว่างเปิดบิล');
		}
	}

    protected function changeStatus(int $id, string $newStatus, string $action, string $message)
	{
		$row = $this->getScopedReservation($id);

		if (!$row) {
			return redirect()->to(site_url('reservations'))->with('error', 'ไม่พบรายการจอง');
		}

		$data = [
			'status'     => $newStatus,
			'updated_by' => session()->get('user_id') ?: null,
		];

		if ($newStatus === 'cancelled' && empty($row['cancelled_at'])) {
			$data['cancelled_at'] = date('Y-m-d H:i:s');
		}

		if ($newStatus === 'no_show' && empty($row['no_show_at'])) {
			$data['no_show_at'] = date('Y-m-d H:i:s');
		}

		// ถ้าในตาราง reservations ไม่มี completed_at ให้ลบบล็อกนี้ออก
		if ($newStatus === 'completed' && isset($row['completed_at']) && empty($row['completed_at'])) {
			$data['completed_at'] = date('Y-m-d H:i:s');
		}

		$this->db->transBegin();

		try {
			$result = $this->reservationModel->update($id, $data);

			if ($result === false) {
				$this->db->transRollback();

				$modelErrors = $this->reservationModel->errors();
				$errorText   = $message . 'ไม่สำเร็จ';

				if (!empty($modelErrors)) {
					$errorText .= ' : ' . implode(' / ', $modelErrors);
				}

				return redirect()->to(site_url('reservations'))->with('error', $errorText);
			}

			// ดึงโต๊ะที่ผูกกับการจองไว้เหมือนเดิม เพื่อให้หน้า index/view/history ยังเห็นได้
			$tableIds = $this->reservationTableModel->getTableIdsByReservation($id);

			// ปล่อยสถานะโต๊ะกลับเป็นว่าง แต่ "ไม่ลบ" relation ใน reservation_tables
			if (in_array($newStatus, ['cancelled', 'no_show', 'completed'], true)) {
				$this->setTablesStatus($tableIds, 'available');
			}

			if ($newStatus === 'seated') {
				$this->setTablesStatus($tableIds, 'occupied');
			}

			$this->reservationLogModel->addLog($id, $action, $row['status'] ?? null, $newStatus, $message);

			if ($this->db->transStatus() === false) {
				$this->db->transRollback();
				return redirect()->to(site_url('reservations'))->with('error', $message . 'ไม่สำเร็จ');
			}

			$this->db->transCommit();

			return redirect()->to(site_url('reservations'))->with('success', $message . 'เรียบร้อย');
		} catch (\Throwable $e) {
			$this->db->transRollback();
			log_message('error', 'reservation changeStatus error: ' . $e->getMessage());
			log_message('error', 'reservation changeStatus trace: ' . $e->getTraceAsString());

			return redirect()->to(site_url('reservations'))->with('error', 'เกิดข้อผิดพลาดระหว่างอัปเดตสถานะ : ' . $e->getMessage());
		}
	}

    public function availableTables()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Invalid request'
            ]);
        }

        $reservationDate = trim((string) $this->request->getGet('reservation_date'));
        $reservationTime = trim((string) $this->request->getGet('reservation_time'));
        $guestCount      = (int) ($this->request->getGet('guest_count') ?? 0);
        $durationMinutes = (int) ($this->request->getGet('duration_minutes') ?? 60);
        $zoneId          = (int) ($this->request->getGet('zone_id') ?? 0);
        $reservationId   = (int) ($this->request->getGet('reservation_id') ?? 0);

        if ($reservationDate === '' || $reservationTime === '') {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'กรุณาระบุวันที่และเวลา'
            ]);
        }

        $startTimestamp = strtotime($reservationDate . ' ' . $reservationTime);
        if ($startTimestamp === false) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'รูปแบบวันที่หรือเวลาไม่ถูกต้อง'
            ]);
        }

        if ($durationMinutes <= 0) {
            $durationMinutes = 60;
        }

        $branchId = $this->getCurrentBranchId();

        if ($branchId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'ไม่พบสาขาปัจจุบัน'
            ]);
        }

        if (! $this->zoneBelongsToBranch($zoneId, $branchId)) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'โซนที่เลือกไม่อยู่ในสาขาปัจจุบัน'
            ]);
        }

        $tables = $this->tableModel->getUsableTables(
			$branchId,
			$zoneId > 0 ? $zoneId : null,
			$guestCount > 0 ? $guestCount : null
		);

        $conflictMap = $this->reservationModel->getConflictingReservationsByTable(
            $reservationDate,
            $reservationTime,
            $durationMinutes,
            $reservationId > 0 ? $reservationId : null,
            $branchId
        );

        $openOrderBusyMap = $this->getOpenOrderBusyTables($branchId);

        $result = [];

        foreach ($tables as $table) {
            $tableId = (int) ($table['id'] ?? 0);

            $capacity = 0;
            if (!empty($table['max_guest'])) {
                $capacity = (int) $table['max_guest'];
            } elseif (!empty($table['seat_count'])) {
                $capacity = (int) $table['seat_count'];
            }

            $isAvailable = 1;
            $busyReason  = null;
            $busyUntil   = null;
            $openOrderId = null;

            if (isset($conflictMap[$tableId])) {
                $isAvailable = 0;
                $busyReason  = 'reservation';
                $busyUntil   = $conflictMap[$tableId]['busy_until'] ?? null;
            } elseif (isset($openOrderBusyMap[$tableId])) {
                $isAvailable = 0;
                $busyReason  = 'open_order';
                $busyUntil   = null;
                $openOrderId = !empty($openOrderBusyMap[$tableId]['order_id'])
                    ? (int) $openOrderBusyMap[$tableId]['order_id']
                    : null;
            }

            $result[] = [
                'id'            => $tableId,
                'table_name'    => $table['table_name'] ?? '',
                'zone_id'       => (int) ($table['zone_id'] ?? 0),
                'zone_name'     => $table['zone_name'] ?? '',
                'capacity'      => $capacity,
                'seat_count'    => (int) ($table['seat_count'] ?? 0),
                'min_guest'     => (int) ($table['min_guest'] ?? 0),
                'max_guest'     => (int) ($table['max_guest'] ?? 0),
                'table_status'  => $table['status'] ?? '',
                'is_available'  => $isAvailable,
                'busy_reason'   => $busyReason,
                'busy_until'    => $busyUntil,
                'open_order_id' => $openOrderId,
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'tables' => $result,
        ]);
    }
}