<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\QuickNoteModel;

class QuickNoteController extends BaseController
{
    protected $quickNoteModel;

    public function __construct()
    {
        $this->quickNoteModel = new QuickNoteModel();
    }

    protected function notePayload(): array
    {
        $nameTh = trim((string) $this->request->getPost('note_name_th'));
        $nameEn = trim((string) $this->request->getPost('note_name_en'));
        $legacy = trim((string) $this->request->getPost('note_name'));

        $name = $nameTh !== '' ? $nameTh : ($nameEn !== '' ? $nameEn : $legacy);

        return [
            'tenant_id'    => $this->currentTenantIdOrFail(),
            'note_name'    => $name,
            'note_name_th' => $nameTh !== '' ? $nameTh : $name,
            'note_name_en' => $nameEn !== '' ? $nameEn : $name,
            'sort_order'   => (int) ($this->request->getPost('sort_order') ?? 0),
            'status'       => ((int) ($this->request->getPost('status') ?? 1)) === 1 ? 1 : 0,
        ];
    }

    protected function validatePayload(array $payload)
    {
        if (trim((string) ($payload['note_name'] ?? '')) === '') {
            return redirect()->back()
                ->withInput()
                ->with('error', lang('app.note_name') . ' ' . lang('app.required'));
        }

        return null;
    }

    public function index()
	{
		$items = $this->quickNoteModel
			->scopedBuilder()
			->where('quick_notes.deleted_at IS NULL', null, false)
			->orderBy('quick_notes.sort_order', 'ASC')
			->orderBy('quick_notes.id', 'DESC')
			->get()
			->getResultArray();

		return view('quick_notes/index', [
			'title' => lang('app.quick_notes'),
			'items' => $items,
		]);
	}

    public function create()
    {
        return view('quick_notes/form', [
            'title'  => lang('app.create_quick_note'),
            'item'   => null,
            'action' => site_url('quick-notes/store'),
        ]);
    }

    public function store()
    {
        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $payload = $this->notePayload();

        if ($redirect = $this->validatePayload($payload)) {
            return $redirect;
        }

        $this->quickNoteModel->insert($payload);

        return redirect()->to(site_url('quick-notes'))
            ->with('success', lang('app.save'));
    }

    public function edit($id = null)
    {
        $id   = (int) $id;
        $item = $this->tenantScopedRowOr404($this->quickNoteModel, $id);

        return view('quick_notes/form', [
            'title'  => lang('app.edit_quick_note'),
            'item'   => $item,
            'action' => site_url('quick-notes/update/' . $id),
        ]);
    }

    public function update($id = null)
    {
        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $id = (int) $id;
        $this->tenantScopedRowOr404($this->quickNoteModel, $id);

        $payload = $this->notePayload();
        unset($payload['tenant_id']);

        if ($redirect = $this->validatePayload($payload)) {
            return $redirect;
        }

        if (method_exists($this->quickNoteModel, 'updateScoped')) {
            $this->quickNoteModel->updateScoped($id, $payload);
        } else {
            $this->quickNoteModel->update($id, $payload);
        }

        return redirect()->to(site_url('quick-notes'))
            ->with('success', lang('app.save'));
    }

    public function toggle($id = null)
    {
        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $id   = (int) $id;
        $item = $this->tenantScopedRowOr404($this->quickNoteModel, $id);

        $newStatus = ((int) ($item['status'] ?? 0) === 1) ? 0 : 1;

        $payload = [
            'status' => $newStatus,
        ];

        if (method_exists($this->quickNoteModel, 'updateScoped')) {
            $this->quickNoteModel->updateScoped($id, $payload);
        } else {
            $this->quickNoteModel->update($id, $payload);
        }

        return redirect()->to(site_url('quick-notes'))
            ->with('success', lang('app.save'));
    }

    public function delete($id = null)
    {
        if ($response = $this->denyIfDemoReadonly()) {
            return $response;
        }

        $id = (int) $id;
        $this->tenantScopedRowOr404($this->quickNoteModel, $id);

        if (method_exists($this->quickNoteModel, 'deleteScoped')) {
            $this->quickNoteModel->deleteScoped($id);
        } else {
            $this->quickNoteModel->delete($id);
        }

        return redirect()->to(site_url('quick-notes'))
            ->with('success', lang('app.delete'));
    }
}