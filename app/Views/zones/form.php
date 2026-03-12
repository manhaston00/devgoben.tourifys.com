<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <form method="post">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.zone_name') ?></label>
                    <input type="text" name="zone_name" class="form-control" value="<?= esc($row['zone_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= lang('app.sort_order') ?></label>
                    <input type="number" name="sort_order" class="form-control" value="<?= esc($row['sort_order'] ?? 0) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?= lang('app.status') ?></label>
                    <select name="status" class="form-select">
                        <option value="1" <?= (($row['status'] ?? 1) == 1 ? 'selected' : '') ?>><?= lang('app.active') ?></option>
                        <option value="0" <?= (($row['status'] ?? 1) == 0 ? 'selected' : '') ?>><?= lang('app.inactive') ?></option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-success"><?= lang('app.save') ?></button>
                <a href="<?= site_url('zones') ?>" class="btn btn-secondary"><?= lang('app.back') ?></a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
