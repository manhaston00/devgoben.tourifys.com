<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <?php
        ob_start();
        ?>
            <div class="d-flex gap-2 flex-wrap">
                <?php if (function_exists('can') && can('reservations.edit')): ?>
                    <a href="<?= site_url('reservations/edit/' . ($row['id'] ?? 0)) ?>" class="btn btn-warning">
                        <?= esc(lang('app.edit')) ?>
                    </a>
                <?php endif; ?>
                <a href="<?= site_url('reservations') ?>" class="btn btn-secondary">
                    <?= esc(lang('app.back')) ?>
                </a>
            </div>
        <?php
        $actions = ob_get_clean();

        echo view('partials/app_page_header', [
            'title'   => lang('app.reservation_details'),
            'desc'    => lang('app.reservations'),
            'actions' => $actions,
        ]);
        ?>

        <div class="table-responsive">
            <table class="table table-bordered align-middle app-detail-table mb-0">
                <tbody>
                    <tr><th><?= esc(lang('app.reservation_code')) ?></th><td><?= esc($row['reservation_code'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.customer_name')) ?></th><td><?= esc($row['customer_name'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.customer_phone')) ?></th><td><?= esc($row['customer_phone'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.reservation_date')) ?></th><td><?= esc($row['reservation_date'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.reservation_time')) ?></th><td><?= esc($row['reservation_time'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.guest_count')) ?></th><td><?= esc($row['guest_count'] ?? 0) ?></td></tr>
                    <tr><th><?= esc(lang('app.duration')) ?></th><td><?= esc($row['duration_minutes'] ?? 0) ?> <?= esc(lang('app.minutes')) ?></td></tr>
                    <tr><th><?= esc(lang('app.zone')) ?></th><td><?= esc(lfield($row, 'zone_name') ?: ($row['zone_name'] ?? '-')) ?></td></tr>
                    <tr><th><?= esc(lang('app.table')) ?></th><td><?php if (!empty($row['table_name'])): ?><?= esc(lfield($row, 'table_name') ?: $row['table_name']) ?><?php elseif (!empty($tableIds)): ?><?= esc(implode(', ', $tableIds)) ?><?php else: ?>-<?php endif; ?></td></tr>
                    <tr><th><?= esc(lang('app.status')) ?></th><td><?= reservation_status_badge($row['status'] ?? '') ?></td></tr>
                    <tr><th><?= esc(lang('app.source')) ?></th><td><?= esc($row['source'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.special_note')) ?></th><td><?= nl2br(esc($row['special_note'] ?? '-')) ?></td></tr>
                    <tr><th><?= esc(lang('app.internal_note')) ?></th><td><?= nl2br(esc($row['internal_note'] ?? '-')) ?></td></tr>
                    <tr><th><?= esc(lang('app.checkin_at')) ?></th><td><?= esc($row['checkin_at'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.seated_at')) ?></th><td><?= esc($row['seated_at'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.created_at')) ?></th><td><?= esc($row['created_at'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.updated_at')) ?></th><td><?= esc($row['updated_at'] ?? '-') ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>