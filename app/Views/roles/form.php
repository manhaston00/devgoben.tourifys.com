<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit = ($formMode ?? 'create') === 'edit';
$row    = $row ?? [];

$action = $isEdit
    ? site_url('roles/update/' . (int) ($row['id'] ?? 0))
    : site_url('roles/store');

$roleNameTh   = old('role_name_th', $row['role_name_th'] ?? '');
$roleNameEn   = old('role_name_en', $row['role_name_en'] ?? '');
$roleName     = old('role_name', $row['role_name'] ?? '');
$descTh       = old('description_th', $row['description_th'] ?? '');
$descEn       = old('description_en', $row['description_en'] ?? '');
$desc         = old('description', $row['description'] ?? '');
$status       = (string) old('status', isset($row['status']) ? $row['status'] : '1');
?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-1"><?= esc($title ?? '') ?></h4>
                <div class="text-muted small">
                    <?= esc($isEdit ? lang('app.edit_role') : lang('app.create_role')) ?>
                </div>
            </div>

            <a href="<?= site_url('roles') ?>" class="btn btn-secondary">
                <?= esc(lang('app.back')) ?>
            </a>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
        <?php endif; ?>

        <form action="<?= esc($action) ?>" method="post">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.role_name_th')) ?></label>
                    <input type="text" name="role_name_th" class="form-control" value="<?= esc($roleNameTh) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.role_name_en')) ?></label>
                    <input type="text" name="role_name_en" class="form-control" value="<?= esc($roleNameEn) ?>">
                </div>

                <div class="col-md-12">
                    <label class="form-label"><?= esc(lang('app.role_name')) ?></label>
                    <input type="text" name="role_name" class="form-control" value="<?= esc($roleName) ?>">
                    <div class="form-text">
                        <?= esc(lang('app.role_name_fallback_hint')) ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.description_th')) ?></label>
                    <textarea name="description_th" class="form-control" rows="4"><?= esc($descTh) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.description_en')) ?></label>
                    <textarea name="description_en" class="form-control" rows="4"><?= esc($descEn) ?></textarea>
                </div>

                <div class="col-md-12">
                    <label class="form-label"><?= esc(lang('app.description')) ?></label>
                    <input type="text" name="description" class="form-control" value="<?= esc($desc) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.status')) ?></label>
                    <select name="status" class="form-select" required>
                        <option value="1" <?= $status === '1' ? 'selected' : '' ?>>
                            <?= esc(lang('app.active')) ?>
                        </option>
                        <option value="0" <?= $status === '0' ? 'selected' : '' ?>>
                            <?= esc(lang('app.inactive')) ?>
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <?= esc($isEdit ? lang('app.update') : lang('app.save')) ?>
                </button>

                <a href="<?= site_url('roles') ?>" class="btn btn-light">
                    <?= esc(lang('app.cancel')) ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>