<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<style>
    .permission-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        overflow: hidden;
    }
    .permission-card + .permission-card {
        margin-top: 16px;
    }
    .permission-card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        padding: 14px 16px;
    }
    .permission-card-body {
        padding: 16px;
    }
    .module-title {
        font-size: 1rem;
        font-weight: 700;
        margin: 0;
        text-transform: capitalize;
    }
    .permission-item {
        border: 1px solid #edf2f7;
        border-radius: 12px;
        padding: 10px 12px;
        height: 100%;
        background: #fff;
    }
    .permission-key {
        font-size: 12px;
        color: #6b7280;
        word-break: break-word;
    }
    .sticky-save-bar {
        position: sticky;
        bottom: 0;
        z-index: 20;
        background: rgba(255,255,255,.95);
        border-top: 1px solid #e5e7eb;
        padding: 12px;
        backdrop-filter: blur(8px);
        margin-top: 18px;
        border-radius: 14px;
    }
</style>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h5 class="mb-1"><?= esc(lang('app.set_permissions')) ?></h5>
                <div class="text-muted">
                    <?= esc(lang('app.role')) ?>: <strong><?= esc($role['role_name'] ?? '-') ?></strong>
                    <?php if (!empty($role['description'])): ?>
                        | <?= esc($role['description']) ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-outline-primary" id="checkAllBtn"><?= esc(lang('app.select_all')) ?></button>
                <button type="button" class="btn btn-outline-secondary" id="uncheckAllBtn"><?= esc(lang('app.clear_all')) ?></button>
                <a href="<?= site_url('roles') ?>" class="btn btn-secondary"><?= esc(lang('app.back')) ?></a>
            </div>
        </div>

        <form method="post">
            <?= csrf_field() ?>

            <?php if (!empty($groupedPermissions)): ?>
                <?php foreach ($groupedPermissions as $module => $permissions): ?>
                    <div class="permission-card">
                        <div class="permission-card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                            <h6 class="module-title mb-0"><?= esc(module_label($module)) ?></h6>

                            <div class="d-flex align-items-center gap-2">
                                <input class="form-check-input module-check-all"
                                       type="checkbox"
                                       id="module_all_<?= esc($module) ?>"
                                       data-module="<?= esc($module) ?>">
                                <label class="form-check-label" for="module_all_<?= esc($module) ?>">
                                    <?= esc(lang('app.select_module')) ?>
                                </label>
                            </div>
                        </div>

                        <div class="permission-card-body">
                            <div class="row g-3">
                                <?php foreach ($permissions as $permission): ?>
                                    <div class="col-12 col-md-6 col-xl-4">
                                        <div class="permission-item">
                                            <div class="form-check">
                                                <input class="form-check-input permission-checkbox module-<?= esc($module) ?>"
                                                       type="checkbox"
                                                       name="permission_ids[]"
                                                       value="<?= esc($permission['id']) ?>"
                                                       id="permission_<?= esc($permission['id']) ?>"
                                                       <?= in_array((int) $permission['id'], $currentPermissionIds, true) ? 'checked' : '' ?>>

                                                <label class="form-check-label w-100" for="permission_<?= esc($permission['id']) ?>">
                                                    <div class="fw-semibold"><?= esc(lfield($permission, 'label', $permission['label'] ?? '-')) ?></div>
                                                    <div class="permission-key"><?= esc($permission['permission_key'] ?? '-') ?></div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-warning mb-0"><?= esc(lang('app.no_permissions_found')) ?></div>
            <?php endif; ?>

            <div class="sticky-save-bar">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div class="text-muted">
                        <?= esc(lang('app.choose_permissions_for_role')) ?>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-success"><?= esc(lang('app.save_permissions')) ?></button>
                        <a href="<?= site_url('roles') ?>" class="btn btn-secondary"><?= esc(lang('app.back')) ?></a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const allPermissionCheckboxes = document.querySelectorAll('.permission-checkbox');
    const moduleCheckAllBoxes = document.querySelectorAll('.module-check-all');
    const checkAllBtn = document.getElementById('checkAllBtn');
    const uncheckAllBtn = document.getElementById('uncheckAllBtn');

    function syncModuleMaster(moduleName) {
        const moduleItems = document.querySelectorAll('.module-' + CSS.escape(moduleName));
        const moduleMaster = document.querySelector('.module-check-all[data-module="' + moduleName + '"]');

        if (!moduleMaster || moduleItems.length === 0) return;

        let checkedCount = 0;
        moduleItems.forEach(item => {
            if (item.checked) checkedCount++;
        });

        moduleMaster.checked = (checkedCount === moduleItems.length);
    }

    moduleCheckAllBoxes.forEach(master => {
        master.addEventListener('change', function () {
            const moduleName = this.dataset.module;
            const items = document.querySelectorAll('.module-' + CSS.escape(moduleName));

            items.forEach(item => {
                item.checked = this.checked;
            });
        });
    });

    allPermissionCheckboxes.forEach(item => {
        item.addEventListener('change', function () {
            const classes = Array.from(this.classList);
            const moduleClass = classes.find(c => c.startsWith('module-') && c !== 'module-check-all');
            if (!moduleClass) return;

            const moduleName = moduleClass.replace('module-', '');
            syncModuleMaster(moduleName);
        });
    });

    moduleCheckAllBoxes.forEach(master => {
        syncModuleMaster(master.dataset.module);
    });

    if (checkAllBtn) {
        checkAllBtn.addEventListener('click', function () {
            allPermissionCheckboxes.forEach(item => item.checked = true);
            moduleCheckAllBoxes.forEach(master => master.checked = true);
        });
    }

    if (uncheckAllBtn) {
        uncheckAllBtn.addEventListener('click', function () {
            allPermissionCheckboxes.forEach(item => item.checked = false);
            moduleCheckAllBoxes.forEach(master => master.checked = false);
        });
    }
});
</script>
<?= $this->endSection() ?>