<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\KitchenStationModel;

class KitchenStations extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new KitchenStationModel();
    }

    protected function stationPayload(): array
    {
        $nameTh = trim((string) $this->request->getPost('station_name_th'));
        $nameEn = trim((string) $this->request->getPost('station_name_en'));
        $legacy = trim((string) $this->request->getPost('station_name'));

        $name = $nameTh !== '' ? $nameTh : ($nameEn !== '' ? $nameEn : $legacy);

        return [
            'tenant_id'       => $this->currentTenantIdOrFail(),
            'station_name'    => $name,
            'station_name_th' => $nameTh !== '' ? $nameTh : $name,
            'station_name_en' => $nameEn !== '' ? $nameEn : $name,
            'color_tag'       => trim((string) $this->request->getPost('color_tag')),
            'status'          => ((int) ($this->request->getPost('status') ?? 1)) === 1 ? 1 : 0,
        ];
    }

    protected function validatePayload(array $payload)
    {
        if (trim((string) ($payload['station_name'] ?? '')) === '') {
            return redirect()->back()
                ->withInput()
                ->with('error', lang('app.station_name') . ' ' . lang('app.required'));
        }

        return null;
    }

    public function index()
	{
		return view('kitchen_stations/index', [
			'title' => lang('app.kitchen_stations'),
			'rows'  => $this->model
				->scopedBuilder()
				->where('kitchen_stations.deleted_at IS NULL', null, false)
				->orderBy('kitchen_stations.id', 'ASC')
				->get()
				->getResultArray(),
		]);
	}

    public function create()
    {
        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        if ($this->request->getMethod() === 'post') {
            $payload = $this->stationPayload();

            if ($redirect = $this->validatePayload($payload)) {
                return $redirect;
            }

            $this->model->insert($payload);

            return redirect()->to(site_url('kitchen-stations'))
                ->with('success', lang('app.save'));
        }

        return view('kitchen_stations/form', [
            'title' => lang('app.create_kitchen_station'),
            'row'   => null,
        ]);
    }

    public function edit($id)
    {
        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $row = $this->tenantScopedRowOr404($this->model, (int) $id);

        if ($this->request->getMethod() === 'post') {
            $payload = $this->stationPayload();
            unset($payload['tenant_id']);

            if ($redirect = $this->validatePayload($payload)) {
                return $redirect;
            }

            if (method_exists($this->model, 'updateScoped')) {
                $this->model->updateScoped((int) $id, $payload);
            } else {
                $this->model->update((int) $id, $payload);
            }

            return redirect()->to(site_url('kitchen-stations'))
                ->with('success', lang('app.save'));
        }

        return view('kitchen_stations/form', [
            'title' => lang('app.edit_kitchen_station'),
            'row'   => $row,
        ]);
    }

    public function delete($id)
    {
        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        if ($this->request->getMethod() !== 'post') {
            return redirect()->to(site_url('kitchen-stations'))
                ->with('error', lang('app.invalid_method'));
        }

        $this->tenantScopedRowOr404($this->model, (int) $id);

        if (method_exists($this->model, 'deleteScoped')) {
            $this->model->deleteScoped((int) $id);
        } else {
            $this->model->delete((int) $id);
        }

        return redirect()->to(site_url('kitchen-stations'))
            ->with('success', lang('app.delete'));
    }
}