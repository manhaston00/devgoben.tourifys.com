<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="app-page-head">
            <div class="app-page-head-left">
                <h4><?= esc(lang('app.settings')) ?></h4>
                <div class="app-page-head-desc"><?= esc(lang('app.settings')) ?></div>
            </div>
        </div>

        <div class="row g-3">
            <?php if (! empty($canBranchSettings)): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="app-section h-100">
                        <div class="app-section-title"><?= esc(lang('app.branch_settings')) ?></div>
                        <div class="app-section-subtitle"><?= esc(lang('app.current_branch')) ?></div>

                        <div class="d-grid gap-2">
                            <a href="<?= site_url('settings/branch') ?>" class="btn btn-primary">
                                <?= esc(lang('app.branch_settings')) ?>
                            </a>

                            <?php if (! empty($currentBranchId)): ?>
                                <div class="small text-muted">
                                    <?= esc(lang('app.branch')) ?> #<?= esc((string) $currentBranchId) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (! empty($canBranches)): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="app-section h-100">
                        <div class="app-section-title"><?= esc(lang('app.branches')) ?></div>
                        <div class="app-section-subtitle"><?= esc(lang('app.manage_branches')) ?></div>

                        <div class="d-grid">
                            <a href="<?= site_url('branches') ?>" class="btn btn-outline-primary">
                                <?= esc(lang('app.branches')) ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (! empty($canUsers)): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="app-section h-100">
                        <div class="app-section-title"><?= esc(lang('app.users')) ?></div>
                        <div class="app-section-subtitle"><?= esc(lang('app.user_management')) ?></div>

                        <div class="d-grid">
                            <a href="<?= site_url('users') ?>" class="btn btn-outline-primary">
                                <?= esc(lang('app.users')) ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (! empty($canRoles)): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="app-section h-100">
                        <div class="app-section-title"><?= esc(lang('app.roles')) ?></div>
                        <div class="app-section-subtitle"><?= esc(lang('app.set_permissions')) ?></div>

                        <div class="d-grid">
                            <a href="<?= site_url('roles') ?>" class="btn btn-outline-primary">
                                <?= esc(lang('app.roles')) ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (! empty($canAuditLogs)): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="app-section h-100">
                        <div class="app-section-title"><?= esc(lang('app.audit_logs')) ?></div>
                        <div class="app-section-subtitle"><?= esc(lang('app.audit_logs_desc')) ?></div>

                        <div class="d-grid">
                            <a href="<?= site_url('audit-logs') ?>" class="btn btn-outline-primary">
                                <?= esc(lang('app.audit_logs')) ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
