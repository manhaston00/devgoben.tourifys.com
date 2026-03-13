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
            $row['meta_array']       = $this->decodeJsonField($row['meta_json'] ?? null);
            $row['old_values_array'] = $this->decodeJsonField($row['old_values_json'] ?? null);
            $row['new_values_array'] = $this->decodeJsonField($row['new_values_json'] ?? null);
            $row['detail_blocks']    = $this->buildDetailBlocks($row);
            $row['detail_summary']   = $this->buildDetailSummary($row);
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
            $row['meta_array']       = $this->decodeJsonField($row['meta_json'] ?? null);
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

    protected function buildDetailBlocks(array $row): array
    {
        $meta   = is_array($row['meta_array'] ?? null) ? $row['meta_array'] : [];
        $blocks = [];

        foreach ($meta as $metaKey => $metaValue) {
            if ($metaKey === 'filters' && is_array($metaValue)) {
                foreach ($metaValue as $filterKey => $filterValue) {
                    $normalized = $this->normalizeDetailValue($filterValue);
                    if ($normalized === null) {
                        continue;
                    }

                    $blocks[] = [
                        'group' => 'filters',
                        'label' => $this->resolveDetailLabel((string) $filterKey, true),
                        'value' => $normalized,
                    ];
                }

                continue;
            }

            $normalized = $this->normalizeDetailValue($metaValue);
            if ($normalized === null) {
                continue;
            }

            $blocks[] = [
                'group' => 'meta',
                'label' => $this->resolveDetailLabel((string) $metaKey),
                'value' => $normalized,
            ];
        }

        if ($blocks === [] && ! empty($row['ref_code'])) {
            $blocks[] = [
                'group' => 'meta',
                'label' => lang('app.reference'),
                'value' => (string) $row['ref_code'],
            ];
        }

        return $blocks;
    }

    protected function buildDetailSummary(array $row): string
    {
        $parts = [];

        if (! empty($row['ref_code'])) {
            $parts[] = lang('app.reference') . ': ' . $row['ref_code'];
        }

        if ((int) ($row['order_id'] ?? 0) > 0) {
            $parts[] = lang('app.order_number') . ': #' . (int) $row['order_id'];
        }

        if (! empty($row['table_name'])) {
            $parts[] = lang('app.table') . ': ' . $row['table_name'];
        }

        $summary = implode(' • ', $parts);

        return $summary !== '' ? $summary : lang('app.audit_logs_no_additional_details');
    }

    protected function normalizeDetailValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? lang('app.yes') : lang('app.no');
        }

        if (is_scalar($value)) {
            $stringValue = trim((string) $value);
            return $stringValue === '' ? null : $stringValue;
        }

        if (is_array($value)) {
            $parts = [];
            foreach ($value as $nestedKey => $nestedValue) {
                if ($nestedValue === null || $nestedValue === '') {
                    continue;
                }

                if (is_scalar($nestedValue)) {
                    $parts[] = $this->resolveDetailLabel((string) $nestedKey) . ': ' . (string) $nestedValue;
                }
            }

            return $parts !== [] ? implode(' • ', $parts) : null;
        }

        return null;
    }

    protected function resolveDetailLabel(string $key, bool $isFilter = false): string
    {
        $map = [
            'screen'     => 'audit_details_screen',
            'branch_id'  => 'audit_details_branch_id',
            'filters'    => 'audit_details_filters',
            'ticket_no'  => 'audit_details_ticket_no',
            'ticket_id'  => 'audit_details_ticket_id',
            'batch_no'   => 'audit_details_batch_no',
            'item_count' => 'audit_details_item_count',
            'date_from'  => 'date_from',
            'date_to'    => 'date_to',
            'search'     => 'search',
            'action_key' => 'action',
            'order_id'   => 'order_number',
            'user_id'    => 'user',
            'branch'     => 'branch',
        ];

        $langKey = $map[$key] ?? null;
        if ($langKey !== null) {
            return lang('app.' . $langKey);
        }

        return $isFilter ? lang('app.filter') : $key;
    }
}
