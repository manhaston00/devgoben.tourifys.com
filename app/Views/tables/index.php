<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <h5 class="mb-0"><?= lang('app.restaurant_tables') ?></h5>
            <div class="page-actions">
                <a href="<?= site_url('tables/create') ?>" class="btn btn-primary"><?= lang('app.add_table') ?></a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle w-100" id="mainTable">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th><?= lang('app.table') ?></th>
                        <th><?= lang('app.zone') ?></th>
                        <th width="110"><?= lang('app.seat_count') ?></th>
                        <th width="100"><?= lang('app.min_guest') ?></th>
                        <th width="100"><?= lang('app.max_guest') ?></th>
                        <th width="120"><?= lang('app.table_status') ?></th>
                        <th width="100"><?= lang('app.active') ?></th>
                        <th width="160"><?= lang('app.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): foreach ($rows as $row): ?>
                        <?php
                            $status = strtolower(trim((string) ($row['status'] ?? '')));
                            $labels = [
                                'available' => lang('app.available'),
                                'occupied'  => lang('app.occupied'),
                                'reserved'  => lang('app.reserved'),
                                'disabled'  => lang('app.table_closed'),
                            ];
                            $classes = [
                                'available' => 'bg-success',
                                'occupied'  => 'bg-danger',
                                'reserved'  => 'bg-warning text-dark',
                                'disabled'  => 'bg-dark',
                            ];
                            $statusLabel = $labels[$status] ?? ($row['status'] ?? '-');
                            $statusClass = $classes[$status] ?? 'bg-secondary';
                        ?>
                        <tr>
                            <td><?= esc($row['id'] ?? '') ?></td>
                            <td><?= esc($row['table_name'] ?? '-') ?></td>
                            <td><?= esc($row['zone_name'] ?? '-') ?></td>
                            <td><?= esc($row['seat_count'] ?? 0) ?></td>
                            <td><?= esc($row['min_guest'] ?? 0) ?></td>
                            <td><?= esc($row['max_guest'] ?? 0) ?></td>
                            <td><span class="badge <?= esc($statusClass) ?>"><?= esc($statusLabel) ?></span></td>
                            <td><span class="badge <?= (int) ($row['is_active'] ?? 0) === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= active_text($row['is_active'] ?? 0) ?></span></td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="<?= site_url('tables/edit/' . ($row['id'] ?? 0)) ?>" class="btn btn-sm btn-warning"><?= lang('app.edit') ?></a>
                                    <form action="<?= site_url('tables/delete/' . ($row['id'] ?? 0)) ?>" method="post" onsubmit="return confirm(<?= json_encode(lang('app.confirm_delete')) ?>)">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-danger"><?= lang('app.delete') ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
$(function () {
    if ($.fn.DataTable.isDataTable('#mainTable')) {
        $('#mainTable').DataTable().destroy();
    }

    $('#mainTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']],
        autoWidth: false,
        responsive: false,
        scrollX: true,
        language: <?= json_encode(dt_lang(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    });
});
</script>
<?= $this->endSection() ?>
