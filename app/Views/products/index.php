<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-1"><?= esc($title ?? lang('app.products')) ?></h4>
                <div class="text-muted"><?= esc(lang('app.products')) ?></div>
            </div>

            <?php if (function_exists('can') && can('products.create')): ?>
                <a href="<?= site_url('products/create') ?>" class="btn btn-primary">
                    <?= esc(lang('app.add_product')) ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="mainTable">
                <thead>
                    <tr>
                        <th width="80">#</th>
                        <th><?= esc(lang('app.product_name')) ?></th>
                        <th><?= esc(lang('app.category')) ?></th>
                        <th><?= esc(lang('app.kitchen_station')) ?></th>
                        <th width="120"><?= esc(lang('app.cost')) ?></th>
                        <th width="120"><?= esc(lang('app.price')) ?></th>
                        <th width="100"><?= esc(lang('app.status')) ?></th>
                        <th width="180" class="text-center"><?= esc(lang('app.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($products)): ?>
                        <?php foreach ($products as $row): ?>
                            <tr>
                                <td><?= (int) $row['id'] ?></td>
                                <td><?= esc(lfield($row, 'product_name', '-')) ?></td>
                                <td><?= esc(lfield($row, 'category_name', '-')) ?></td>
                                <td><?= esc(lfield($row, 'station_name', '-')) ?></td>
                                <td><?= number_format((float) ($row['cost'] ?? 0), 2) ?></td>
                                <td><?= number_format((float) ($row['price'] ?? 0), 2) ?></td>
                                <td>
                                    <?php if ((int) ($row['status'] ?? 0) === 1): ?>
                                        <span class="badge bg-success"><?= esc(lang('app.active')) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc(lang('app.inactive')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                                        <?php if (function_exists('can') && can('products.edit')): ?>
                                            <a href="<?= site_url('products/edit/' . (int) $row['id']) ?>" class="btn btn-sm btn-warning">
                                                <?= esc(lang('app.edit')) ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (function_exists('can') && can('products.delete')): ?>
                                            <form method="post"
                                                  action="<?= site_url('products/delete/' . (int) $row['id']) ?>"
                                                  class="d-inline"
                                                  onsubmit="return confirm('<?= esc(lang('app.confirm_delete'), 'js') ?>')">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <?= esc(lang('app.delete')) ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                <?= esc(lang('app.no_data')) ?>
                            </td>
                        </tr>
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
        autoWidth: false,
        language: <?= json_encode(dt_lang(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    });
});
</script>
<?= $this->endSection() ?>