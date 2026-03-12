<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit = ($formMode ?? 'create') === 'edit';
$action = $isEdit
    ? site_url('super-admin/tenants/update/' . (int) ($row['id'] ?? 0))
    : site_url('super-admin/tenants/store');
?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h4 class="mb-0"><?= esc($title ?? '') ?></h4>
            <a href="<?= site_url('super-admin/tenants') ?>" class="btn btn-secondary"><?= esc(lang('app.back')) ?></a>
        </div>

        <form method="post" action="<?= $action ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.tenant_code')) ?> <span class="text-danger">*</span></label>
                    <input type="text" name="tenant_code" class="form-control" value="<?= esc(old('tenant_code', $row['tenant_code'] ?? '')) ?>" required>
                </div>

                <div class="col-md-8">
                    <label class="form-label"><?= esc(lang('app.tenant_name')) ?> <span class="text-danger">*</span></label>
                    <input type="text" name="tenant_name" class="form-control" value="<?= esc(old('tenant_name', $row['tenant_name'] ?? '')) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.owner_name')) ?></label>
                    <input type="text" name="owner_name" class="form-control" value="<?= esc(old('owner_name', $row['owner_name'] ?? '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.phone')) ?></label>
                    <input type="text" name="phone" class="form-control" value="<?= esc(old('phone', $row['phone'] ?? '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.email')) ?></label>
                    <input type="email" name="email" class="form-control" value="<?= esc(old('email', $row['email'] ?? '')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.status')) ?></label>
                    <select name="status" class="form-select">
                        <option value="active" <?= old('status', $row['status'] ?? 'active') === 'active' ? 'selected' : '' ?>><?= esc(lang('app.active')) ?></option>
                        <option value="demo" <?= old('status', $row['status'] ?? 'active') === 'demo' ? 'selected' : '' ?>><?= esc(lang('app.demo')) ?></option>
                        <option value="suspended" <?= old('status', $row['status'] ?? 'active') === 'suspended' ? 'selected' : '' ?>><?= esc(lang('app.suspended')) ?></option>
                    </select>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <?= esc($isEdit ? lang('app.update') : lang('app.save')) ?>
                </button>
                <a href="<?= site_url('super-admin/tenants') ?>" class="btn btn-light border">
                    <?= esc(lang('app.cancel')) ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>