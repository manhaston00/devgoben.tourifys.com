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
            <h4 class="mb-0"><?= esc($title ?? ($isEdit ? lang('app.edit_tenant') : lang('app.add_tenant'))) ?></h4>
            <a href="<?= site_url('super-admin/tenants') ?>" class="btn btn-secondary"><?= esc(lang('app.back')) ?></a>
        </div>

        <form method="post" action="<?= $action ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.tenant_code')) ?> <span class="text-danger">*</span></label>
                    <input type="text" name="tenant_code" id="tenantCodeInput" class="form-control" value="<?= esc(old('tenant_code', $row['tenant_code'] ?? '')) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Login Prefix <span class="text-danger">*</span></label>
                    <input type="text" name="login_prefix" id="loginPrefixInput" class="form-control" value="<?= esc(old('login_prefix', $row['login_prefix'] ?? '')) ?>" required>
                    <div class="form-text">ใช้สำหรับสร้าง username login จริง เช่น <strong>kob</strong>, <strong>koben</strong></div>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.status')) ?></label>
                    <select name="status" class="form-select" required>
                        <option value="active" <?= old('status', $row['status'] ?? 'active') === 'active' ? 'selected' : '' ?>><?= esc(lang('app.active')) ?></option>
                        <option value="demo" <?= old('status', $row['status'] ?? 'active') === 'demo' ? 'selected' : '' ?>><?= esc(lang('app.demo')) ?></option>
                        <option value="suspended" <?= old('status', $row['status'] ?? 'active') === 'suspended' ? 'selected' : '' ?>><?= esc(lang('app.suspended')) ?></option>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label"><?= esc(lang('app.tenant_name')) ?> <span class="text-danger">*</span></label>
                    <input type="text" name="tenant_name" class="form-control" value="<?= esc(old('tenant_name', $row['tenant_name'] ?? '')) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.owner_name')) ?></label>
                    <input type="text" name="owner_name" class="form-control" value="<?= esc(old('owner_name', $row['owner_name'] ?? '')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.phone')) ?></label>
                    <input type="text" name="phone" class="form-control" value="<?= esc(old('phone', $row['phone'] ?? '')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.email')) ?></label>
                    <input type="email" name="email" class="form-control" value="<?= esc(old('email', $row['email'] ?? '')) ?>">
                </div>

                <div class="col-12"><hr></div>

                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.subscription_plan')) ?> <span class="text-danger">*</span></label>
                    <select name="plan_id" class="form-select" required>
                        <option value=""><?= esc(lang('app.select_subscription_plan')) ?></option>
                        <?php foreach (($plans ?? []) as $plan): ?>
                            <option value="<?= (int) $plan['id'] ?>" <?= (string) old('plan_id', $row['plan_id'] ?? '') === (string) $plan['id'] ? 'selected' : '' ?>>
                                <?= esc($plan['plan_name'] ?? $plan['name_th'] ?? '-') ?> (<?= esc(number_format((float) ($plan['price'] ?? 0), 2)) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.billing_type')) ?></label>
                    <select name="billing_type" class="form-select">
                        <option value="daily" <?= old('billing_type', $row['billing_type'] ?? 'monthly') === 'daily' ? 'selected' : '' ?>><?= esc(lang('app.daily')) ?></option>
                        <option value="monthly" <?= old('billing_type', $row['billing_type'] ?? 'monthly') === 'monthly' ? 'selected' : '' ?>><?= esc(lang('app.monthly')) ?></option>
                        <option value="lifetime" <?= old('billing_type', $row['billing_type'] ?? 'monthly') === 'lifetime' ? 'selected' : '' ?>><?= esc(lang('app.lifetime')) ?></option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.subscription_status')) ?></label>
                    <select name="subscription_status" class="form-select">
                        <option value="active" <?= old('subscription_status', $row['subscription_status'] ?? 'active') === 'active' ? 'selected' : '' ?>><?= esc(lang('app.active')) ?></option>
                        <option value="expired" <?= old('subscription_status', $row['subscription_status'] ?? 'active') === 'expired' ? 'selected' : '' ?>><?= esc(lang('app.expired')) ?></option>
                        <option value="suspended" <?= old('subscription_status', $row['subscription_status'] ?? 'active') === 'suspended' ? 'selected' : '' ?>><?= esc(lang('app.suspended')) ?></option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.start_date')) ?></label>
                    <input type="date" name="start_date" class="form-control" value="<?= esc(old('start_date', $row['start_date'] ?? date('Y-m-d'))) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.end_date')) ?></label>
                    <input type="date" name="end_date" class="form-control" value="<?= esc(old('end_date', $row['end_date'] ?? '')) ?>">
                </div>

                <div class="col-12"><hr></div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.head_office_branch_name')) ?> <span class="text-danger">*</span></label>
                    <input type="text" name="branch_name" class="form-control" value="<?= esc(old('branch_name', $row['branch_name'] ?? 'สำนักงานใหญ่')) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= esc(lang('app.branch_code')) ?> <span class="text-danger">*</span></label>
                    <input type="text" name="branch_code" class="form-control" value="<?= esc(old('branch_code', $row['branch_code'] ?? 'HO')) ?>" required>
                </div>

                <div class="col-12"><hr></div>

                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.admin_full_name')) ?> <span class="text-danger">*</span></label>
                    <input type="text" name="admin_full_name" class="form-control" value="<?= esc(old('admin_full_name', $row['admin_full_name'] ?? 'Administrator')) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= esc(lang('app.admin_username')) ?> <span class="text-danger">*</span></label>
                    <?php
					$adminUsernameValue = old('admin_username', $row['admin_username'] ?? '');
					$loginPrefixValue   = old('login_prefix', $row['login_prefix'] ?? '');

					if ($adminUsernameValue !== '' && $loginPrefixValue !== '') {
						$prefixNeedle = strtolower(trim($loginPrefixValue)) . '_';
						if (strpos(strtolower($adminUsernameValue), $prefixNeedle) === 0) {
							$adminUsernameValue = substr($adminUsernameValue, strlen($prefixNeedle));
						}
					}
					?>
					<input type="text" name="admin_username" id="adminBaseUsernameInput" class="form-control" value="<?= esc($adminUsernameValue) ?>" required>
                    <div class="form-text">
                        Username สำหรับ login จริง:
                        <strong id="tenantAdminLoginPreview"></strong>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">
                        <?= esc(lang('app.password')) ?>
                        <?php if (! $isEdit): ?><span class="text-danger">*</span><?php endif; ?>
                    </label>
                    <input type="text" name="admin_password" class="form-control" value="<?= esc(old('admin_password', '')) ?>" <?= ! $isEdit ? 'required' : '' ?>>
                    <?php if ($isEdit): ?>
                        <div class="form-text"><?= esc(lang('app.leave_blank_keep_password')) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.pin')) ?></label>
                    <input type="text" name="admin_pin_code" class="form-control" value="<?= esc(old('admin_pin_code', $row['admin_pin_code'] ?? '9999')) ?>">
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

<?= $this->section('scripts') ?>
<script>
(function () {
    const tenantCodeInput = document.getElementById('tenantCodeInput');
    const loginPrefixInput = document.getElementById('loginPrefixInput');
    const adminBaseUsernameInput = document.getElementById('adminBaseUsernameInput');
    const preview = document.getElementById('tenantAdminLoginPreview');

    function normalizeCode(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/_+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function normalizeUsername(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9._-]+/g, '');
    }

    function syncPrefixFromTenantCode() {
        if (!loginPrefixInput.value.trim()) {
            loginPrefixInput.value = normalizeCode(tenantCodeInput.value);
        }
    }

    function updatePreview() {
        const prefix = normalizeCode(loginPrefixInput.value || tenantCodeInput.value);
        const base = normalizeUsername(adminBaseUsernameInput.value);
        preview.textContent = base ? (prefix ? (prefix + '_' + base) : base) : '-';
    }

    if (tenantCodeInput) {
        tenantCodeInput.addEventListener('input', function () {
            syncPrefixFromTenantCode();
            updatePreview();
        });
    }

    if (loginPrefixInput) {
        loginPrefixInput.addEventListener('input', updatePreview);
    }

    if (adminBaseUsernameInput) {
        adminBaseUsernameInput.addEventListener('input', updatePreview);
    }

    syncPrefixFromTenantCode();
    updatePreview();
})();
</script>
<?= $this->endSection() ?>