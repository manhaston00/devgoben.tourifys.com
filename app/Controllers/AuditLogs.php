<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\BranchModel;
use App\Models\UserModel;

class AuditLogs extends BaseController
{
    protected $auditLogModel;
    protected $branchModel;
    protected $userModel;

    public function __construct()
    {
        $this->auditLogModel = new AuditLogModel();
        $this->branchModel   = new BranchModel();
        $this->userModel     = new UserModel();
    }

    protected function writeAuditLog(array $payload): void
    {
        try {
            $this->auditLogModel->add($payload);
        } catch (\Throwable $e) {
            log_message('error', 'AuditLogs writeAuditLog error: ' . $e->getMessage());
        }
    }

    public function index()
    {
        $filters = [
            'branch_id' => (int) ($this->request->getGet('branch_id') ?? 0),
            'user_id'   => (int) ($this->request->getGet('user_id') ?? 0),
            'action_key'=> trim((string) ($this->request->getGet('action_key') ?? '')),
            'order_id'  => (int) ($this->request->getGet('order_id') ?? 0),
            'date_from' => trim((string) ($this->request->getGet('date_from') ?? '')),
            'date_to'   => trim((string) ($this->request->getGet('date_to') ?? '')),
            'search'    => trim((string) ($this->request->getGet('search') ?? '')),
        ];

        if ($filters['date_from'] === '') {
            $filters['date_from'] = date('Y-m-d');
        }

        if ($filters['date_to'] === '') {
            $filters['date_to'] = date('Y-m-d');
        }

        $this->writeAuditLog([
            'target_type'  => 'audit_logs',
            'action_key'   => 'audit_logs.view',
            'action_label' => lang('app.audit_log_access'),
            'meta_json'    => [
                'filters' => array_filter($filters, static fn ($value) => $value !== '' && $value !== 0),
            ],
        ]);

        $rows = $this->auditLogModel->search($filters, 500);
        foreach ($rows as &$row) {
            $row['meta_array'] = $this->decodeJsonField($row['meta_json'] ?? null);
            $row['old_values_array'] = $this->decodeJsonField($row['old_values_json'] ?? null);
            $row['new_values_array'] = $this->decodeJsonField($row['new_values_json'] ?? null);
        }
        unset($row);

        return view('audit_logs/index', [
            'title'         => lang('app.audit_logs'),
            'rows'          => $rows,
            'filters'       => $filters,
            'branches'      => $this->branchModel->getTenantBranches(true),
            'users'         => $this->userModel->getUsersWithRole($this->currentTenantIdOrFail()),
            'actions'       => $this->auditLogModel->getActionOptions(),
        ]);
    }

    public function orderTimeline($orderId = null)
    {
        $orderId = (int) $orderId;

        $this->writeAuditLog([
            'target_type'  => 'order',
            'target_id'    => $orderId,
            'order_id'     => $orderId,
            'action_key'   => 'audit_logs.view',
            'action_label' => lang('app.audit_log_access'),
            'meta_json'    => [
                'screen' => 'order_timeline',
            ],
        ]);

        $rows = $this->auditLogModel->getTimelineByOrderId($orderId);

        foreach ($rows as &$row) {
            $row['meta_array'] = $this->decodeJsonField($row['meta_json'] ?? null);
            $row['old_values_array'] = $this->decodeJsonField($row['old_values_json'] ?? null);
            $row['new_values_array'] = $this->decodeJsonField($row['new_values_json'] ?? null);
        }
        unset($row);

        return view('audit_logs/timeline', [
            'title'   => lang('app.bill_timeline'),
            'orderId' => $orderId,
            'rows'    => $rows,
        ]);
    }

    protected function decodeJsonField($value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
