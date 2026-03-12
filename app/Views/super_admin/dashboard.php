<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<style>
    .sa-stat-card {
        border: 1px solid var(--app-border);
        border-radius: var(--radius-md);
        background: var(--app-card);
        padding: 18px;
        height: 100%;
    }

    .sa-stat-label {
        color: var(--app-text-soft);
        font-size: .82rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .sa-stat-value {
        font-size: 1.9rem;
        font-weight: 800;
        line-height: 1.1;
    }

    .sa-link-card {
        border: 1px solid var(--app-border);
        border-radius: var(--radius-md);
        background: var(--app-card);
        padding: 18px;
        height: 100%;
    }
</style>

<div class="card card-soft mb-3">
    <div class="card-body">
        <?php
        echo view('partials/app_page_header', [
            'title' => lang('app.central_dashboard'),
            'desc'  => lang('app.super_admin'),
        ]);
        ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6 col-xl-3">
        <div class="sa-stat-card">
            <div class="sa-stat-label"><?= esc(lang('app.tenants')) ?></div>
            <div class="sa-stat-value"><?= number_format((int) ($tenantCount ?? 0)) ?></div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="sa-stat-card">
            <div class="sa-stat-label"><?= esc(lang('app.active_subscriptions')) ?></div>
            <div class="sa-stat-value text-success"><?= number_format((int) ($activeSubscriptions ?? 0)) ?></div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="sa-stat-card">
            <div class="sa-stat-label"><?= esc(lang('app.expired_subscriptions')) ?></div>
            <div class="sa-stat-value text-danger"><?= number_format((int) ($expiredSubscriptions ?? 0)) ?></div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="sa-stat-card">
            <div class="sa-stat-label"><?= esc(lang('app.subscription_plans')) ?></div>
            <div class="sa-stat-value"><?= number_format((int) ($plans ?? 0)) ?></div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="sa-stat-card">
            <div class="sa-stat-label"><?= esc(lang('app.suspended_subscriptions')) ?></div>
            <div class="sa-stat-value text-warning"><?= number_format((int) ($suspendedSubscriptions ?? 0)) ?></div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="sa-stat-card">
            <div class="sa-stat-label"><?= esc(lang('app.demo_subscriptions')) ?></div>
            <div class="sa-stat-value text-info"><?= number_format((int) ($demoSubscriptions ?? 0)) ?></div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="sa-link-card">
            <h5 class="mb-2"><?= esc(lang('app.tenants')) ?></h5>
            <div class="text-muted mb-3"><?= esc(lang('app.manage_saas_tenants')) ?></div>
            <a href="<?= site_url('super-admin/tenants') ?>" class="btn btn-dark">
                <?= esc(lang('app.tenants')) ?>
            </a>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="sa-link-card">
            <h5 class="mb-2"><?= esc(lang('app.subscription_plans')) ?></h5>
            <div class="text-muted mb-3"><?= esc(lang('app.manage_saas_plans')) ?></div>
            <a href="<?= site_url('super-admin/subscription-plans') ?>" class="btn btn-primary">
                <?= esc(lang('app.subscription_plans')) ?>
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>