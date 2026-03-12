<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ZoneModel;
use App\Models\BranchModel;

class Zones extends BaseController
{
    protected $zoneModel;
    protected $branchModel;

    public function __construct()
    {
        $this->zoneModel   = new ZoneModel();
        $this->branchModel = new BranchModel();
    }

    protected function denyIfNoPermission(string $permissionKey)
    {
        if (! function_exists('can') || ! can($permissionKey)) {
            return redirect()->to(site_url('/'))->with('error', lang('app.no_permission'));
        }

        return null;
    }

    protected function getZoneDeleteDependencies(int $zoneId): array
    {
        $db       = \Config\Database::connect();
        $tenantId = $this->currentTenantIdOrFail();

        $checks = [
            'restaurant_tables' => ['table' => 'restaurant_tables', 'field' => 'zone_id', 'label' => 'โต๊ะ'],
            'reservations'      => ['table' => 'reservations', 'field' => 'zone_id', 'label' => 'การจอง'],
        ];

        $result = [];

        foreach ($checks as $check) {
            if (! $db->tableExists($check['table']) || ! $db->fieldExists($check['field'], $check['table'])) {
                continue;
            }

            $builder = $db->table($check['table'])
                ->where($check['field'], $zoneId);

            if ($db->fieldExists('tenant_id', $check['table'])) {
                $builder->where('tenant_id', $tenantId);
            }

            if ($db->fieldExists('deleted_at', $check['table'])) {
                $builder->where('deleted_at', null);
            }

            $count = (int) $builder->countAllResults();

            if ($count > 0) {
                $result[] = [
                    'label' => $check['label'],
                    'count' => $count,
                ];
            }
        }

        return $result;
    }

    protected function zonePayload(int $branchId = 0): array
    {
        $nameTh = trim((string) $this->request->getPost('zone_name_th'));
        $nameEn = trim((string) $this->request->getPost('zone_name_en'));
        $descTh = trim((string) $this->request->getPost('description_th'));
        $descEn = trim((string) $this->request->getPost('description_en'));

        $payload = [
            'tenant_id'      => $this->currentTenantIdOrFail(),
            'zone_name_th'   => $nameTh,
            'zone_name_en'   => $nameEn,
            'description_th' => $descTh,
            'description_en' => $descEn,
            'sort_order'     => (int) ($this->request->getPost('sort_order') ?? 0),
            'status'         => (int) ($this->request->getPost('status') ?? 1),
        ];

        if ($branchId > 0) {
            $payload['branch_id'] = $branchId;
        } else {
            $payload['branch_id'] = (int) ($this->request->getPost('branch_id') ?? 0);
        }

        return $payload;
    }

    public function index()
    {
        if ($response = $this->denyIfNoPermission('zones.view')) {
            return $response;
        }

        $branchId = current_branch_id();

        return view('zones/index', [
            'title'    => lang('app.zone_management'),
            'branchId' => $branchId,
            'zones'    => $this->zoneModel->getZonesWithBranch($branchId),
            'branches' => branch_options(),
        ]);
    }

    public function create()
    {
        if ($response = $this->denyIfNoPermission('zones.create')) {
            return $response;
        }

        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $branchId = (int) current_branch_id();

        if ($branchId <= 0) {
            return redirect()->to(site_url('zones'))->with('error', lang('app.branch_name'));
        }

        $this->tenantOwnedOr404('branches', $branchId);

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'zone_name_th' => 'permit_empty|max_length[150]',
                'zone_name_en' => 'permit_empty|max_length[150]',
                'status'       => 'required|in_list[0,1]',
            ];

            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
            }

            $payload = $this->zonePayload($branchId);

            if (
                trim((string) ($payload['zone_name_th'] ?? '')) === '' &&
                trim((string) ($payload['zone_name_en'] ?? '')) === ''
            ) {
                return redirect()->back()->withInput()->with('error', lang('app.zone_name') . ' ' . lang('app.required'));
            }

            $this->zoneModel->insert($payload);

            return redirect()->to(site_url('zones'))->with('success', lang('app.save'));
        }

        return view('zones/create', [
            'title'      => lang('app.add_zone'),
            'branchId'   => $branchId,
            'branchName' => current_branch_name(),
        ]);
    }

    public function edit($id = null)
    {
        if ($response = $this->denyIfNoPermission('zones.edit')) {
            return $response;
        }

        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $zone = $this->tenantScopedRowOr404($this->zoneModel, (int) $id);
        $this->tenantOwnedOr404('branches', (int) ($zone['branch_id'] ?? 0));

        if (! can_access_branch((int) ($zone['branch_id'] ?? 0))) {
            return redirect()->to(site_url('zones'))->with('error', lang('app.no_permission'));
        }

        if ($this->request->getMethod() === 'post') {
            $rules = [
                'zone_name_th' => 'permit_empty|max_length[150]',
                'zone_name_en' => 'permit_empty|max_length[150]',
                'status'       => 'required|in_list[0,1]',
            ];

            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('error', implode('<br>', $this->validator->getErrors()));
            }

            $payload = $this->zonePayload((int) ($zone['branch_id'] ?? 0));

            if (
                trim((string) ($payload['zone_name_th'] ?? '')) === '' &&
                trim((string) ($payload['zone_name_en'] ?? '')) === ''
            ) {
                return redirect()->back()->withInput()->with('error', lang('app.zone_name') . ' ' . lang('app.required'));
            }

            unset($payload['tenant_id']);

            if (method_exists($this->zoneModel, 'updateScoped')) {
                $this->zoneModel->updateScoped((int) $id, $payload);
            } else {
                $this->zoneModel->update((int) $id, $payload);
            }

            return redirect()->to(site_url('zones'))->with('success', lang('app.save'));
        }

        return view('zones/edit', [
            'title' => lang('app.edit'),
            'row'   => $zone,
        ]);
    }

    public function delete($id = null)
    {
        if ($response = $this->denyIfNoPermission('zones.delete')) {
            return $response;
        }

        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $zone = $this->tenantScopedRowOr404($this->zoneModel, (int) $id);
        $this->tenantOwnedOr404('branches', (int) ($zone['branch_id'] ?? 0));

        if (! can_access_branch((int) ($zone['branch_id'] ?? 0))) {
            return redirect()->to(site_url('zones'))->with('error', lang('app.no_permission'));
        }

        $dependencies = $this->getZoneDeleteDependencies((int) $id);

        if (! empty($dependencies)) {
            $parts = array_map(static function ($row) {
                return $row['label'] . ' ' . number_format((int) $row['count']) . ' รายการ';
            }, $dependencies);

            return redirect()->to(site_url('zones'))
                ->with('error', 'ไม่สามารถลบโซนได้ เนื่องจากยังมีข้อมูลผูกอยู่: ' . implode(', ', $parts));
        }

        if (method_exists($this->zoneModel, 'deleteScoped')) {
            $this->zoneModel->deleteScoped((int) $id);
        } else {
            $this->zoneModel->delete((int) $id);
        }

        return redirect()->to(site_url('zones'))->with('success', lang('app.delete'));
    }
}