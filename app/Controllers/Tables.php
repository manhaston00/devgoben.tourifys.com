<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TableModel;
use App\Models\ZoneModel;

class Tables extends BaseController
{
    protected $tableModel;
    protected $zoneModel;

    public function __construct()
    {
        $this->tableModel = new TableModel();
        $this->zoneModel  = new ZoneModel();
    }

    protected function tablePayload(int $branchId = 0): array
    {
        $zoneId    = (int) $this->request->getPost('zone_id');
        $tableName = trim((string) $this->request->getPost('table_name'));
        $seatCount = (int) ($this->request->getPost('seat_count') ?? 0);
        $minGuest  = (int) ($this->request->getPost('min_guest') ?? 0);
        $maxGuest  = (int) ($this->request->getPost('max_guest') ?? 0);
        $status    = trim((string) $this->request->getPost('status'));
        $isActive  = (int) ($this->request->getPost('is_active') ?? 1);
        $sortOrder = (int) ($this->request->getPost('sort_order') ?? 0);
        $note      = trim((string) ($this->request->getPost('note') ?? ''));

        if ($status === '') {
            $status = 'available';
        }

        if ($minGuest < 0) {
            $minGuest = 0;
        }

        if ($maxGuest < 0) {
            $maxGuest = 0;
        }

        return [
            'tenant_id'  => $this->currentTenantIdOrFail(),
            'branch_id'  => $branchId > 0 ? $branchId : (int) ($this->request->getPost('branch_id') ?? 0),
            'zone_id'    => $zoneId,
            'table_name' => $tableName,
            'seat_count' => $seatCount,
            'min_guest'  => $minGuest,
            'max_guest'  => $maxGuest,
            'status'     => $status,
            'is_active'  => $isActive,
            'sort_order' => $sortOrder,
            'note'       => $note,
        ];
    }

    protected function validateTablePayload(array $data)
    {
        $zoneId    = (int) ($data['zone_id'] ?? 0);
        $tableName = trim((string) ($data['table_name'] ?? ''));
        $seatCount = (int) ($data['seat_count'] ?? 0);
        $minGuest  = (int) ($data['min_guest'] ?? 0);
        $maxGuest  = (int) ($data['max_guest'] ?? 0);
        $branchId  = (int) ($data['branch_id'] ?? 0);

        if ($branchId <= 0) {
            return redirect()->back()->withInput()->with('error', lang('app.branch_not_found_in_session'));
        }

        if ($zoneId <= 0) {
            return redirect()->back()->withInput()->with('error', lang('app.please_select_zone'));
        }

        if ($tableName === '') {
            return redirect()->back()->withInput()->with('error', lang('app.please_enter_table_name'));
        }

        if ($seatCount <= 0) {
            return redirect()->back()->withInput()->with('error', lang('app.seat_count_must_be_greater_than_zero'));
        }

        if ($maxGuest > 0 && $minGuest > $maxGuest) {
            return redirect()->back()->withInput()->with('error', lang('app.min_guest_cannot_exceed_max_guest'));
        }

        try {
            $this->tenantOwnedByBranchOr404('zones', $zoneId, $branchId);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', lang('app.please_select_zone'));
        }

        return null;
    }

    protected function getZoneOptions(int $branchId): array
    {
        return $this->zoneModel
            ->scopeTenant()
            ->where('zones.branch_id', $branchId)
            ->where('zones.status', 1)
            ->orderBy('zones.sort_order', 'ASC')
            ->orderBy('zones.id', 'ASC')
            ->findAll();
    }

    public function index()
    {
        $branchId = $this->currentBranchId();

        if ($branchId > 0) {
            $this->tenantOwnedOr404('branches', $branchId);
        }

        return view('tables/index', [
            'title' => lang('app.restaurant_tables'),
            'rows'  => $this->tableModel->getTablesFull($branchId),
        ]);
    }

    public function create()
    {
        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $branchId = $this->currentBranchId();

        if ($branchId <= 0) {
            return redirect()->to(site_url('tables'))
                ->with('error', lang('app.branch_not_found_in_session'));
        }

        $this->tenantOwnedOr404('branches', $branchId);

        if ($this->request->getMethod() === 'post') {
            $saveData = $this->tablePayload($branchId);

            if ($redirect = $this->validateTablePayload($saveData)) {
                return $redirect;
            }

            $this->tableModel->insert($saveData);

            return redirect()->to(site_url('tables'))
                ->with('success', lang('app.table_created_successfully'));
        }

        return view('tables/form', [
            'title' => lang('app.add_table'),
            'row'   => null,
            'zones' => $this->getZoneOptions($branchId),
        ]);
    }

    public function edit($id = null)
    {
        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $id       = (int) $id;
        $branchId = $this->currentBranchId();

        if ($branchId > 0) {
            $row = $this->tenantOwnedByBranchOr404('restaurant_tables', $id, $branchId);
        } else {
            $row = $this->tenantScopedRowOr404($this->tableModel, $id);
            $branchId = (int) ($row['branch_id'] ?? 0);
        }

        if ($this->request->getMethod() === 'post') {
            $saveData = $this->tablePayload((int) ($row['branch_id'] ?? $branchId));
            unset($saveData['tenant_id']);
            unset($saveData['branch_id']);

            if ($redirect = $this->validateTablePayload(array_merge($saveData, [
                'branch_id' => (int) ($row['branch_id'] ?? $branchId),
            ]))) {
                return $redirect;
            }

            if (method_exists($this->tableModel, 'updateScoped')) {
                $this->tableModel->updateScoped($id, $saveData);
            } else {
                $this->tableModel->update($id, $saveData);
            }

            return redirect()->to(site_url('tables'))
                ->with('success', lang('app.table_updated_successfully'));
        }

        return view('tables/form', [
            'title' => lang('app.edit_table'),
            'row'   => $row,
            'zones' => $this->getZoneOptions((int) ($row['branch_id'] ?? $branchId)),
        ]);
    }

    public function delete($id = null)
    {
        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(site_url('tables'))
                ->with('error', lang('app.invalid_method'));
        }

        $id       = (int) $id;
        $branchId = $this->currentBranchId();

        if ($branchId > 0) {
            $this->tenantOwnedByBranchOr404('restaurant_tables', $id, $branchId);
        } else {
            $this->tenantScopedRowOr404($this->tableModel, $id);
        }

        if (method_exists($this->tableModel, 'deleteScoped')) {
            $this->tableModel->deleteScoped($id);
        } else {
            $this->tableModel->delete($id);
        }

        return redirect()->to(site_url('tables'))
            ->with('success', lang('app.table_deleted_successfully'));
    }
}