<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
$isEdit = ($formMode ?? 'create') === 'edit';
$row    = $row ?? [];
$action = $isEdit
    ? site_url('users/update/' . (int) ($row['id'] ?? 0))
    : site_url('users/store');
?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h4 class="mb-0"><?= esc($title ?? '') ?></h4>
            <a href="<?= site_url('users') ?>" class="btn btn-secondary">
                <?= esc(lang('app.back')) ?>
            </a>
        </div>

        <form action="<?= esc($action) ?>" method="post">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.full_name')) ?> *</label>
                    <input type="text" name="full_name" class="form-control"
                           value="<?= old('full_name', $row['full_name'] ?? '') ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.username')) ?> *</label>
                    <input type="text" name="username" class="form-control"
                           value="<?= old('username', $row['username'] ?? '') ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.password')) ?><?= $isEdit ? '' : ' *' ?></label>
                    <input type="password" name="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
                    <?php if ($isEdit): ?>
                        <div class="form-text"><?= esc(lang('app.leave_blank_if_no_password_change')) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.pin_code')) ?></label>
                    <input type="text" name="pin_code" class="form-control"
                           value="<?= old('pin_code', $row['pin_code'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.phone')) ?></label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= old('phone', $row['phone'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.email')) ?></label>
                    <input type="email" name="email" class="form-control"
                           value="<?= old('email', $row['email'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.role')) ?> *</label>
                    <select name="role_id" class="form-select" required>
                        <option value=""><?= esc(lang('app.please_select')) ?></option>
                        <?php foreach (($roles ?? []) as $role): ?>
                            <?php
                                $roleLabel = $role['role_name_th']
                                    ?? $role['role_name_en']
                                    ?? $role['role_name']
                                    ?? '';
                            ?>
                            <option value="<?= (int) $role['id'] ?>"
                                <?= (string) old('role_id', $row['role_id'] ?? '') === (string) $role['id'] ? 'selected' : '' ?>>
                                <?= esc($roleLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.branch')) ?></label>
                    <select name="branch_id" class="form-select">
                        <option value=""><?= esc(lang('app.please_select')) ?></option>
                        <?php foreach (($branches ?? []) as $branch): ?>
                            <?php
                                $branchLabel = $branch['branch_name_th']
                                    ?? $branch['branch_name_en']
                                    ?? $branch['branch_name']
                                    ?? '';
                            ?>
                            <option value="<?= (int) $branch['id'] ?>"
                                <?= (string) old('branch_id', $row['branch_id'] ?? '') === (string) $branch['id'] ? 'selected' : '' ?>>
                                <?= esc($branchLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.status')) ?> *</label>
                    <select name="status" class="form-select" required>
                        <option value="1" <?= (string) old('status', $row['status'] ?? '1') === '1' ? 'selected' : '' ?>>
                            <?= esc(lang('app.active')) ?>
                        </option>
                        <option value="0" <?= (string) old('status', $row['status'] ?? '1') === '0' ? 'selected' : '' ?>>
                            <?= esc(lang('app.inactive')) ?>
                        </option>
                    </select>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <?= esc($isEdit ? lang('app.update') : lang('app.save')) ?>
                </button>
                <a href="<?= site_url('users') ?>" class="btn btn-light">
                    <?= esc(lang('app.cancel')) ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>