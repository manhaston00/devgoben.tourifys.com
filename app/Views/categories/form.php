<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
    $rowId      = (int) ($row['id'] ?? 0);
    $formAction = $rowId > 0
        ? site_url('categories/edit/' . $rowId)
        : site_url('categories/create');
?>

<div class="card card-soft">
    <div class="card-body">
        <form method="post" action="<?= esc($formAction) ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.category_name_th') ?></label>
                    <input
                        type="text"
                        name="category_name_th"
                        class="form-control"
                        value="<?= esc(old('category_name_th', $row['category_name_th'] ?? '')) ?>"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.category_name_en') ?></label>
                    <input
                        type="text"
                        name="category_name_en"
                        class="form-control"
                        value="<?= esc(old('category_name_en', $row['category_name_en'] ?? '')) ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= lang('app.sort_order') ?></label>
                    <input
                        type="number"
                        name="sort_order"
                        class="form-control"
                        value="<?= esc(old('sort_order', $row['sort_order'] ?? 0)) ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= lang('app.status') ?></label>
                    <select name="status" class="form-select">
                        <?php $statusValue = (string) old('status', (string) ($row['status'] ?? 1)); ?>
                        <option value="1" <?= $statusValue === '1' ? 'selected' : '' ?>>
                            <?= lang('app.active') ?>
                        </option>
                        <option value="0" <?= $statusValue === '0' ? 'selected' : '' ?>>
                            <?= lang('app.inactive') ?>
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success"><?= lang('app.save') ?></button>
                <a href="<?= site_url('categories') ?>" class="btn btn-secondary"><?= lang('app.back') ?></a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>