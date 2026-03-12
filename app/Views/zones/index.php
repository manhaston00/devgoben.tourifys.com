<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0"><?= lang('app.zone_management') ?></h5>
                <div class="text-muted small"><?= lang('app.current_branch') ?>: <?= esc(current_branch_name()) ?></div>
            </div>
            <a href="<?= site_url('zones/create') ?>" class="btn btn-primary">+ <?= lang('app.add_zone') ?></a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="mainTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= lang('app.zone_name') ?></th>
                        <th><?= lang('app.branch_name') ?></th>
                        <th><?= lang('app.description') ?></th>
                        <th width="100"><?= lang('app.sort_order') ?></th>
                        <th width="120"><?= lang('app.status') ?></th>
                        <th width="180"><?= lang('app.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($zones)): ?>
                        <?php foreach ($zones as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= esc($row['zone_name_th'] ?: ($row['zone_name_en'] ?: '-')) ?></td>
                                <td><?= esc($row['branch_name_th'] ?: ($row['branch_name_en'] ?: current_branch_name())) ?></td>
                                <td><?= esc($row['description_th'] ?: ($row['description_en'] ?: '-')) ?></td>
                                <td><?= esc($row['sort_order'] ?? 0) ?></td>
                                <td>
                                    <span class="badge <?= (int) ($row['status'] ?? 0) === 1 ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= esc(active_text($row['status'] ?? 0)) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= site_url('zones/edit/' . (int) $row['id']) ?>" class="btn btn-sm btn-warning">
                                        <?= lang('app.edit') ?>
                                    </a>

                                    <form action="<?= site_url('zones/delete/' . (int) $row['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('<?= esc(lang('app.confirm_delete_zone'), 'js') ?>')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <?= lang('app.delete') ?>
                                        </button>
                                    </form>
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
    const dtLanguage = <?= json_encode(dt_lang(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    dtLanguage.emptyTable = dtLanguage.emptyTable || <?= json_encode(lang('app.no_zone_data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    dtLanguage.zeroRecords = dtLanguage.zeroRecords || <?= json_encode(lang('app.no_zone_data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    $('#mainTable').DataTable({
        language: dtLanguage
    });
});
</script>
<?= $this->endSection() ?>