<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <h5 class="mb-3"><?= lang('app.edit_branch') ?></h5>

        <form method="post" action="<?= site_url('branches/edit/' . (int) $branch['id']) ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label"><?= lang('app.branch_code') ?></label>
                    <input type="text" name="branch_code" class="form-control" value="<?= old('branch_code', $branch['branch_code'] ?? '') ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.branch_name_th') ?></label>
                    <input type="text" name="branch_name_th" class="form-control" value="<?= old('branch_name_th', $branch['branch_name_th'] ?? '') ?>" required>
                </div>

                <div class="col-md-5">
                    <label class="form-label"><?= lang('app.branch_name_en') ?></label>
                    <input type="text" name="branch_name_en" class="form-control" value="<?= old('branch_name_en', $branch['branch_name_en'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.phone') ?></label>
                    <input type="text" name="phone" class="form-control" value="<?= old('phone', $branch['phone'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.email') ?></label>
                    <input type="email" name="email" class="form-control" value="<?= old('email', $branch['email'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.tax_id') ?></label>
                    <input type="text" name="tax_id" class="form-control" value="<?= old('tax_id', $branch['tax_id'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.address_th') ?></label>
                    <textarea name="address_th" class="form-control" rows="3"><?= old('address_th', $branch['address_th'] ?? '') ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.address_en') ?></label>
                    <textarea name="address_en" class="form-control" rows="3"><?= old('address_en', $branch['address_en'] ?? '') ?></textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.branch_logo') ?></label>
                    <input type="text" name="logo" class="form-control" value="<?= old('logo', $branch['logo'] ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label"><?= lang('app.default_language') ?></label>
                    <select name="default_locale" class="form-select">
                        <option value="th" <?= old('default_locale', $branch['default_locale'] ?? 'th') === 'th' ? 'selected' : '' ?>><?= lang('app.thai') ?></option>
                        <option value="en" <?= old('default_locale', $branch['default_locale'] ?? 'th') === 'en' ? 'selected' : '' ?>><?= lang('app.english') ?></option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Timezone</label>
                    <input type="text" name="timezone" class="form-control" value="<?= old('timezone', 'Asia/Bangkok') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= lang('app.branch_open_date') ?></label>
                    <input type="date" name="opened_at" class="form-control" value="<?= old('opened_at', $branch['opened_at'] ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label"><?= lang('app.sort_order') ?></label>
                    <input type="number" name="sort_order" class="form-control" value="<?= old('sort_order', 0) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label"><?= lang('app.status') ?></label>
                    <select name="status" class="form-select">
                        <option value="1" <?= old('status', (string) ($branch['status'] ?? '1')) === '1' ? 'selected' : '' ?>><?= lang('app.active') ?></option>
                        <option value="0" <?= old('status', (string) ($branch['status'] ?? '1')) === '0' ? 'selected' : '' ?>><?= lang('app.inactive') ?></option>
                    </select>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_head_office" id="is_head_office" class="form-check-input" value="1" <?= old('is_head_office', (string) ($branch['is_head_office'] ?? '0')) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_head_office"><?= lang('app.set_as_head_office') ?></label>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= lang('app.save') ?></button>
                <a href="<?= site_url('branches') ?>" class="btn btn-light"><?= lang('app.back') ?></a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
