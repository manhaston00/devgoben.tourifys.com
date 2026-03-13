<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<style>
    .audit-filter-card,
    .audit-table-card {
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
    }
    .audit-detail-stack {
        display: flex;
        flex-direction: column;
        gap: .5rem;
        min-width: 260px;
    }
    .audit-detail-summary {
        font-size: .84rem;
        color: #64748b;
        line-height: 1.45;
    }
    .audit-detail-grid {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
    }
    .audit-detail-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .6rem;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: .78rem;
        line-height: 1.2;
    }
    .audit-detail-chip.filter {
        background: #eff6ff;
        border-color: #bfdbfe;
    }
    .audit-detail-chip-label {
        color: #475569;
        font-weight: 700;
    }
    .audit-detail-chip-value {
        color: #0f172a;
    }
    .audit-action-key {
        display: inline-block;
        margin-top: .25rem;
        padding: .2rem .5rem;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-size: .72rem;
        word-break: break-word;
    }
    .audit-ref-stack {
        display: flex;
        flex-direction: column;
        gap: .15rem;
    }
    .audit-ref-main {
        font-weight: 600;
        color: #0f172a;
    }
    .audit-ref-sub {
        font-size: .82rem;
        color: #64748b;
    }
    @media (max-width: 767.98px) {
        .audit-detail-stack {
            min-width: 220px;
        }
        #auditLogsTable th,
        #auditLogsTable td {
            white-space: normal;
            vertical-align: top;
        }
    }
</style>

<div class="card card-soft audit-filter-card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-1"><?= esc($title ?? lang('app.audit_logs')) ?></h4>
                <div class="text-muted small"><?= esc(lang('app.audit_logs_desc')) ?></div>
            </div>
        </div>

        <form method="get" class="row g-2 mb-0">
            <div class="col-md-2 col-sm-6">
                <label class="form-label"><?= esc(lang('app.date_from')) ?></label>
                <input type="date" name="date_from" value="<?= esc($filters['date_from'] ?? '') ?>" class="form-control">
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label"><?= esc(lang('app.date_to')) ?></label>
                <input type="date" name="date_to" value="<?= esc($filters['date_to'] ?? '') ?>" class="form-control">
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label"><?= esc(lang('app.branch')) ?></label>
                <select name="branch_id" class="form-select">
                    <option value="0"><?= esc(lang('app.all_branches')) ?></option>
                    <?php foreach (($branches ?? []) as $branch): ?>
                        <option value="<?= (int) ($branch['id'] ?? 0) ?>" <?= (int) ($filters['branch_id'] ?? 0) === (int) ($branch['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= esc(lfield($branch, 'branch_name', $branch['branch_code'] ?? '-')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label"><?= esc(lang('app.user')) ?></label>
                <select name="user_id" class="form-select">
                    <option value="0"><?= esc(lang('app.all_users')) ?></option>
                    <?php foreach (($users ?? []) as $user): ?>
                        <option value="<?= (int) ($user['id'] ?? 0) ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) ($user['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= esc(($user['full_name'] ?? '-') . ' (' . ($user['username'] ?? '-') . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label"><?= esc(lang('app.action')) ?></label>
                <select name="action_key" class="form-select">
                    <option value=""><?= esc(lang('app.all_actions')) ?></option>
                    <?php foreach (($actions ?? []) as $action): ?>
                        <?php $actionKey = (string) ($action['action_key'] ?? ''); ?>
                        <option value="<?= esc($actionKey) ?>" <?= ($filters['action_key'] ?? '') === $actionKey ? 'selected' : '' ?>>
                            <?= esc(($action['action_label'] ?? '') !== '' ? $action['action_label'] : $actionKey) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label"><?= esc(lang('app.order_number')) ?></label>
                <input type="number" min="0" name="order_id" value="<?= esc((string) ($filters['order_id'] ?? '')) ?>" class="form-control" placeholder="ID">
            </div>
            <div class="col-md-8 col-sm-12">
                <label class="form-label"><?= esc(lang('app.search')) ?></label>
                <input type="text" name="search" value="<?= esc($filters['search'] ?? '') ?>" class="form-control" placeholder="<?= esc(lang('app.audit_logs_search_placeholder')) ?>">
            </div>
            <div class="col-md-4 col-sm-12 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100"><?= esc(lang('app.filter')) ?></button>
                <a href="<?= site_url('audit-logs') ?>" class="btn btn-outline-secondary w-100"><?= esc(lang('app.reset')) ?></a>
            </div>
        </form>
    </div>
</div>

<div class="card card-soft audit-table-card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="auditLogsTable" class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th><?= esc(lang('app.datetime')) ?></th>
                        <th><?= esc(lang('app.user')) ?></th>
                        <th><?= esc(lang('app.branch')) ?></th>
                        <th><?= esc(lang('app.action')) ?></th>
                        <th><?= esc(lang('app.reference')) ?></th>
                        <th><?= esc(lang('app.details')) ?></th>
                        <th width="140" class="text-center"><?= esc(lang('app.timeline')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($rows ?? []) as $index => $row): ?>
                        <tr>
                            <td><?= (int) $index + 1 ?></td>
                            <td>
                                <div class="fw-semibold"><?= esc($row['created_at'] ?? '-') ?></div>
                                <?php if (! empty($row['ip_address'])): ?>
                                    <div class="small text-muted"><?= esc($row['ip_address']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= esc($row['actor_name'] ?? $row['full_name'] ?? '-') ?></div>
                                <div class="small text-muted"><?= esc($row['username'] ?? '-') ?></div>
                            </td>
                            <td><?= esc(lfield($row, 'branch_name', '-')) ?></td>
                            <td>
                                <div class="fw-semibold"><?= esc($row['action_label'] ?? $row['action_key'] ?? '-') ?></div>
                                <div class="audit-action-key"><?= esc($row['action_key'] ?? '-') ?></div>
                            </td>
                            <td>
                                <div class="audit-ref-stack">
                                    <div class="audit-ref-main"><?= esc($row['ref_code'] ?? ($row['order_number'] ?? '-')) ?></div>
                                    <?php if ((int) ($row['order_id'] ?? 0) > 0): ?>
                                        <div class="audit-ref-sub"><?= esc(lang('app.order_number')) ?> #<?= (int) $row['order_id'] ?></div>
                                    <?php endif; ?>
                                    <?php if (! empty($row['table_name'])): ?>
                                        <div class="audit-ref-sub"><?= esc(lang('app.table')) ?>: <?= esc($row['table_name']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="audit-detail-stack">
                                    <div class="audit-detail-summary"><?= esc($row['detail_summary'] ?? lang('app.audit_logs_no_additional_details')) ?></div>
                                    <?php $detailBlocks = $row['detail_blocks'] ?? []; ?>
                                    <?php if ($detailBlocks !== []): ?>
                                        <div class="audit-detail-grid">
                                            <?php foreach ($detailBlocks as $detail): ?>
                                                <div class="audit-detail-chip <?= esc($detail['group'] ?? 'meta') ?>">
                                                    <span class="audit-detail-chip-label"><?= esc($detail['label'] ?? '-') ?></span>
                                                    <span class="audit-detail-chip-value"><?= esc($detail['value'] ?? '-') ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ((int) ($row['order_id'] ?? 0) > 0): ?>
                                    <a href="<?= site_url('audit-logs/order/' . (int) $row['order_id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <?= esc(lang('app.view_timeline')) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php
    echo view('partials/super_admin_datatable', [
        'tableId'            => 'auditLogsTable',
        'pageLength'         => 25,
        'orderColumn'        => 1,
        'orderDir'           => 'desc',
        'nonSortableColumns' => [0, 7],
    ]);
?>
<?= $this->endSection() ?>
