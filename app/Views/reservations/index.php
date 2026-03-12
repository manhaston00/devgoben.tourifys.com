<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <?php
        ob_start();
        ?>
            <?php if (function_exists('can') && can('reservations.create')): ?>
                <a href="<?= site_url('reservations/create') ?>" class="btn btn-primary">
                    <?= esc(lang('app.add_reservation')) ?>
                </a>
            <?php endif; ?>
        <?php
        $actions = ob_get_clean();

        echo view('partials/app_page_header', [
            'title'   => lang('app.reservations_list'),
            'desc'    => lang('app.reservations'),
            'actions' => $actions,
        ]);

        echo view('partials/app_toolbar', [
            'left' => '
                <input type="text"
                       id="reservationTableSearch"
                       class="form-control app-search-input"
                       placeholder="' . esc(lang('app.search')) . '">
            ',
        ]);
        ?>

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="reservationTable">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th><?= esc(lang('app.customer_name')) ?></th>
                        <th><?= esc(lang('app.customer_phone')) ?></th>
                        <th><?= esc(lang('app.reservation_date')) ?></th>
                        <th><?= esc(lang('app.reservation_time')) ?></th>
                        <th><?= esc(lang('app.guest_count')) ?></th>
                        <th><?= esc(lang('app.table')) ?></th>
                        <th><?= esc(lang('app.status')) ?></th>
                        <th width="260" class="text-center"><?= esc(lang('app.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($reservations)): ?>
                        <?php foreach ($reservations as $i => $r): ?>
                            <?php $status = $r['status'] ?? ''; ?>
                            <tr>
                                <td><?= (int) ($i + 1) ?></td>
                                <td><?= esc($r['customer_name'] ?? '-') ?></td>
                                <td><?= esc($r['customer_phone'] ?? '-') ?></td>
                                <td><?= esc($r['reservation_date'] ?? '-') ?></td>
                                <td><?= esc($r['reservation_time'] ?? '-') ?></td>
                                <td><?= esc($r['guest_count'] ?? 0) ?></td>
                                <td><?= esc(lfield($r, 'table_name') ?: ($r['table_name'] ?? '-')) ?></td>
                                <td class="text-center"><?= reservation_status_badge($status) ?></td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                                        <?php if (function_exists('can') && can('reservations.view')): ?>
                                            <a href="<?= site_url('reservations/view/' . (int) ($r['id'] ?? 0)) ?>" class="btn btn-sm btn-info text-white">
                                                <?= esc(lang('app.view')) ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (function_exists('can') && can('reservations.edit')): ?>
                                            <a href="<?= site_url('reservations/edit/' . (int) ($r['id'] ?? 0)) ?>" class="btn btn-sm btn-warning">
                                                <?= esc(lang('app.edit')) ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (function_exists('can') && can('reservations.edit') && in_array($status, ['pending', 'confirmed'], true)): ?>
                                            <form action="<?= site_url('reservations/checkin/' . (int) ($r['id'] ?? 0)) ?>" method="post" class="d-inline" onsubmit="return confirm('<?= esc(lang('app.confirm_checkin'), 'js') ?>')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-success"><?= esc(lang('app.check_in')) ?></button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (function_exists('can') && can('reservations.edit') && in_array($status, ['confirmed', 'seated'], true)): ?>
                                            <form action="<?= site_url('reservations/open-bill/' . (int) ($r['id'] ?? 0)) ?>" method="post" class="d-inline" onsubmit="return confirm('<?= esc(lang('app.confirm_open_bill'), 'js') ?>')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-primary"><?= esc(lang('app.open_bill')) ?></button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (function_exists('can') && can('reservations.edit') && in_array($status, ['pending', 'confirmed'], true)): ?>
                                            <form action="<?= site_url('reservations/cancel/' . (int) ($r['id'] ?? 0)) ?>" method="post" class="d-inline" onsubmit="return confirm('<?= esc(lang('app.confirm_cancel_reservation'), 'js') ?>')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-secondary"><?= esc(lang('app.cancelled')) ?></button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (function_exists('can') && can('reservations.edit') && in_array($status, ['pending', 'confirmed'], true)): ?>
                                            <form action="<?= site_url('reservations/no-show/' . (int) ($r['id'] ?? 0)) ?>" method="post" class="d-inline" onsubmit="return confirm('<?= esc(lang('app.confirm_no_show'), 'js') ?>')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-dark"><?= esc(lang('app.no_show')) ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
    const hasRows = $('#reservationTable tbody tr').length > 0;

    const table = $('#reservationTable').DataTable({
        pageLength: 25,
        autoWidth: false,
        order: hasRows ? [[3, 'desc'], [4, 'desc']] : [],
        language: $.extend(true, {}, <?= json_encode(dt_lang(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, {
            emptyTable: <?= json_encode(lang('app.no_reservations'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        }),
        columnDefs: [
            { targets: [0, 7, 8], orderable: false }
        ]
    });

    $('#reservationTableSearch').on('keyup change', function () {
        table.search(this.value).draw();
    });
});
</script>
<?= $this->endSection() ?>