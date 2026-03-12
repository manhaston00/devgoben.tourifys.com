<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h4 class="mb-0"><?= esc(lang('app.tenant_detail')) ?></h4>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= site_url('super-admin/tenants/edit/' . (int) ($row['id'] ?? 0)) ?>" class="btn btn-warning">
                    <?= esc(lang('app.edit')) ?>
                </a>
                <a href="<?= site_url('super-admin/tenants') ?>" class="btn btn-secondary">
                    <?= esc(lang('app.back')) ?>
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <tbody>
                    <tr><th width="250"><?= esc(lang('app.tenant_code')) ?></th><td><?= esc($row['tenant_code'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.tenant_name')) ?></th><td><?= esc($row['tenant_name'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.owner_name')) ?></th><td><?= esc($row['owner_name'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.phone')) ?></th><td><?= esc($row['phone'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.email')) ?></th><td><?= esc($row['email'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.status')) ?></th><td><?= esc($row['status'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.branches')) ?></th><td><?= (int) ($row['branch_count'] ?? 0) ?></td></tr>
                    <tr><th><?= esc(lang('app.users')) ?></th><td><?= (int) ($row['user_count'] ?? 0) ?></td></tr>
                    <tr><th><?= esc(lang('app.subscription_plan')) ?></th><td><?= esc(lfield($row, 'plan_name') !== '' ? lfield($row, 'plan_name') : '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.subscription_status')) ?></th><td><?= esc($row['subscription_status'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.billing_type')) ?></th><td><?= esc($row['billing_type'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.start_date')) ?></th><td><?= esc($row['subscription_start_date'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.end_date')) ?></th><td><?= esc($row['subscription_end_date'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.created_at')) ?></th><td><?= esc($row['created_at'] ?? '-') ?></td></tr>
                    <tr><th><?= esc(lang('app.updated_at')) ?></th><td><?= esc($row['updated_at'] ?? '-') ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>