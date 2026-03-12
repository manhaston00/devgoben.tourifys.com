<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <h5 class="mb-0"><?= lang('app.kitchen_stations') ?></h5>
            <a href="<?= site_url('kitchen-stations/create') ?>" class="btn btn-primary">+ <?= lang('app.create_kitchen_station') ?></a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="mainTable">
                <thead>
                    <tr>
                        <th width="80">#</th>
                        <th><?= lang('app.station_name') ?></th>
                        <th width="160"><?= lang('app.color_tag') ?></th>
                        <th width="120"><?= lang('app.status') ?></th>
                        <th width="150"><?= lang('app.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): foreach ($rows as $row): ?>
                        <tr>
                            <td><?= esc($row['id'] ?? '') ?></td>
                            <td><?= esc(is_array(lfield($row, 'station_name')) ? '' : lfield($row, 'station_name')) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:<?= esc($row['color_tag'] ?? '#6c757d') ?>;border:1px solid #ddd;"></span>
                                    <span><?= esc($row['color_tag'] ?? '#6c757d') ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= (int) ($row['status'] ?? 0) === 1 ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= esc(active_text($row['status'] ?? 0)) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= site_url('kitchen-stations/edit/' . (int) ($row['id'] ?? 0)) ?>" class="btn btn-sm btn-warning"><?= lang('app.edit') ?></a>
                                <form action="<?= site_url('kitchen-stations/delete/' . (int) ($row['id'] ?? 0)) ?>" method="post" class="d-inline" onsubmit="return confirm('<?= esc(lang('app.confirm_delete_item'), 'js') ?>')">
									<?= csrf_field() ?>
									<button type="submit" class="btn btn-sm btn-danger"><?= lang('app.delete') ?></button>
								</form>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center text-muted"><?= lang('app.no_kitchen_station_data') ?></td></tr>
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
    $('#mainTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']],
        autoWidth: false,
        scrollX: true,
        language: <?= json_encode(dt_lang(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    });
});
</script>
<?= $this->endSection() ?>