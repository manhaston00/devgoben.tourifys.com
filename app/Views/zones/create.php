<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="mb-3">
            <h5 class="mb-1"><?= lang('app.add_zone') ?></h5>
            <div class="text-muted">
                <?= lang('app.branch_name') ?>: <?= esc($branchName ?? current_branch_name()) ?>
            </div>
        </div>

        <form method="post" action="<?= site_url('zones/create') ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.zone_name_th') ?></label>
                    <input
                        type="text"
                        name="zone_name_th"
                        class="form-control"
                        value="<?= esc(old('zone_name_th', old('zone_name'))) ?>"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.zone_name_en') ?></label>
                    <input
                        type="text"
                        name="zone_name_en"
                        class="form-control"
                        value="<?= esc(old('zone_name_en', old('zone_name'))) ?>"
                    >
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-3">
                    <label class="form-label"><?= lang('app.sort_order') ?></label>
                    <input
                        type="number"
                        name="sort_order"
                        class="form-control"
                        value="<?= esc(old('sort_order', 0)) ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= lang('app.status') ?></label>
                    <select name="status" class="form-select">
                        <option value="1" <?= old('status', '1') === '1' ? 'selected' : '' ?>>
                            <?= lang('app.active') ?>
                        </option>
                        <option value="0" <?= old('status') === '0' ? 'selected' : '' ?>>
                            <?= lang('app.inactive') ?>
                        </option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.description_th') ?></label>
                    <textarea
                        name="description_th"
                        class="form-control"
                        rows="4"
                    ><?= esc(old('description_th', old('description'))) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.description_en') ?></label>
                    <textarea
                        name="description_en"
                        class="form-control"
                        rows="4"
                    ><?= esc(old('description_en', old('description'))) ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-success">
                    <?= lang('app.save') ?>
                </button>

                <a href="<?= site_url('zones') ?>" class="btn btn-secondary">
                    <?= lang('app.back') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>