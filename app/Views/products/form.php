<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit = ! empty($row['id']);
$action = $isEdit
    ? site_url('products/update/' . (int) $row['id'])
    : site_url('products/store');
?>

<div class="card card-soft">
    <div class="card-body">
        <div class="mb-4">
            <h4 class="mb-1"><?= esc($title ?? ($isEdit ? lang('app.edit_product') : lang('app.add_product'))) ?></h4>
            <div class="text-muted"><?= esc(lang('app.products')) ?></div>
        </div>

        <form method="post" action="<?= $action ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.product_name_th')) ?></label>
                    <input type="text"
                           name="product_name_th"
                           class="form-control"
                           value="<?= esc(old('product_name_th', $row['product_name_th'] ?? '')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.product_name_en')) ?></label>
                    <input type="text"
                           name="product_name_en"
                           class="form-control"
                           value="<?= esc(old('product_name_en', $row['product_name_en'] ?? '')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.product_name')) ?></label>
                    <input type="text"
                           name="product_name"
                           class="form-control"
                           value="<?= esc(old('product_name', $row['product_name'] ?? '')) ?>"
                           required>
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.sku')) ?></label>
                    <input type="text"
                           name="sku"
                           class="form-control"
                           value="<?= esc(old('sku', $row['sku'] ?? '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.category')) ?></label>
                    <select name="category_id" class="form-select" required>
                        <option value=""><?= esc(lang('app.select_category')) ?></option>
                        <?php foreach (($categories ?? []) as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= (string) old('category_id', $row['category_id'] ?? '') === (string) $cat['id'] ? 'selected' : '' ?>>
                                <?= esc(lfield($cat, 'category_name', '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.kitchen_station')) ?></label>
                    <select name="kitchen_station_id" class="form-select">
                        <option value=""><?= esc(lang('app.select_kitchen_station')) ?></option>
                        <?php foreach (($kitchenStations ?? []) as $station): ?>
                            <option value="<?= (int) $station['id'] ?>" <?= (string) old('kitchen_station_id', $row['kitchen_station_id'] ?? '') === (string) $station['id'] ? 'selected' : '' ?>>
                                <?= esc(lfield($station, 'station_name', '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.sort_order')) ?></label>
                    <input type="number"
                           name="sort_order"
                           class="form-control"
                           value="<?= esc(old('sort_order', $row['sort_order'] ?? 0)) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.cost')) ?></label>
                    <input type="number"
                           name="cost"
                           class="form-control"
                           step="0.01"
                           min="0"
                           value="<?= esc(old('cost', $row['cost'] ?? 0)) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.price')) ?></label>
                    <input type="number"
                           name="price"
                           class="form-control"
                           step="0.01"
                           min="0"
                           value="<?= esc(old('price', $row['price'] ?? 0)) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label"><?= esc(lang('app.available')) ?></label>
                    <select name="is_available" class="form-select">
                        <option value="1" <?= (string) old('is_available', $row['is_available'] ?? 1) === '1' ? 'selected' : '' ?>><?= esc(lang('app.yes')) ?></option>
                        <option value="0" <?= (string) old('is_available', $row['is_available'] ?? 1) === '0' ? 'selected' : '' ?>><?= esc(lang('app.no')) ?></option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label"><?= esc(lang('app.recommended')) ?></label>
                    <select name="is_recommended" class="form-select">
                        <option value="0" <?= (string) old('is_recommended', $row['is_recommended'] ?? 0) === '0' ? 'selected' : '' ?>><?= esc(lang('app.no')) ?></option>
                        <option value="1" <?= (string) old('is_recommended', $row['is_recommended'] ?? 0) === '1' ? 'selected' : '' ?>><?= esc(lang('app.yes')) ?></option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label"><?= esc(lang('app.status')) ?></label>
                    <select name="status" class="form-select">
                        <option value="1" <?= (string) old('status', $row['status'] ?? 1) === '1' ? 'selected' : '' ?>><?= esc(lang('app.active')) ?></option>
                        <option value="0" <?= (string) old('status', $row['status'] ?? 1) === '0' ? 'selected' : '' ?>><?= esc(lang('app.inactive')) ?></option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.description_th')) ?></label>
                    <textarea name="description_th" class="form-control" rows="3"><?= esc(old('description_th', $row['description_th'] ?? '')) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.description_en')) ?></label>
                    <textarea name="description_en" class="form-control" rows="3"><?= esc(old('description_en', $row['description_en'] ?? '')) ?></textarea>
                </div>

                <div class="col-12">
                    <label class="form-label"><?= esc(lang('app.description')) ?></label>
                    <textarea name="description" class="form-control" rows="3"><?= esc(old('description', $row['description'] ?? '')) ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="<?= site_url('products') ?>" class="btn btn-light">
                    <?= esc(lang('app.back')) ?>
                </a>

                <button type="submit" class="btn btn-primary">
                    <?= esc($isEdit ? lang('app.save') : lang('app.add_product')) ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>