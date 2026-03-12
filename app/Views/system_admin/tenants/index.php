<?= $this->extend('layout/super_admin') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-1"><?= esc(lang('app.tenants')) ?></h4>
                <div class="text-muted"><?= esc(lang('app.manage_saas_tenants')) ?></div>
            </div>

            <a href="<?= site_url('system-admin/tenants/create') ?>" class="btn btn-primary">
                + <?= esc(lang('app.add_tenant')) ?>
            </a>
        </div>

        <form method="get" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text"
                       name="q"
                       value="<?= esc($q ?? '') ?>"
                       class="form-control"
                       placeholder="<?= esc(lang('app.search_tenant_placeholder')) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-dark"><?= esc(lang('app.search')) ?></button>
            </div>
            <div class="col-auto">
                <a href="<?= site_url('system-admin/tenants') ?>" class="btn btn-outline-secondary"><?= esc(lang('app.reset')) ?></a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70">#</th>
                        <th><?= esc(lang('app.tenant_code')) ?></th>
                        <th><?= esc(lang('app.tenant_name')) ?></th>
                        <th><?= esc(lang('app.owner_name')) ?></th>
                        <th><?= esc(lang('app.phone')) ?></th>
                        <th><?= esc(lang('app.email')) ?></th>
                        <th width="100"><?= esc(lang('app.branches')) ?></th>
                        <th width="100"><?= esc(lang('app.users')) ?></th>
                        <th><?= esc(lang('app.subscription')) ?></th>
                        <th width="120"><?= esc(lang('app.status')) ?></th>
                        <th width="230"><?= esc(lang('app.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td><?= (int) ($index + 1) ?></td>
                                <td><?= esc($row['tenant_code'] ?? '-') ?></td>
                                <td><?= esc($row['tenant_name'] ?? '-') ?></td>
                                <td><?= esc($row['owner_name'] ?? '-') ?></td>
                                <td><?= esc($row['phone'] ?? '-') ?></td>
                                <td><?= esc($row['email'] ?? '-') ?></td>
                                <td><?= (int) ($row['branch_count'] ?? 0) ?></td>
                                <td><?= (int) ($row['user_count'] ?? 0) ?></td>
                                <td>
                                    <?php if (!empty($row['billing_type'])): ?>
                                        <div><strong><?= esc($row['billing_type']) ?></strong></div>
                                        <small class="text-muted">
                                            <?= esc(($row['subscription_start_date'] ?? '-') . ' → ' . ($row['subscription_end_date'] ?? '-')) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $status = strtolower((string) ($row['status'] ?? 'active')); ?>
                                    <?php if ($status === 'active'): ?>
                                        <span class="badge bg-success"><?= esc(lang('app.active')) ?></span>
                                    <?php elseif ($status === 'demo'): ?>
                                        <span class="badge bg-info text-dark"><?= esc(lang('app.demo')) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc(lang('app.suspended')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="<?= site_url('system-admin/tenants/view/' . (int) $row['id']) ?>" class="btn btn-sm btn-info text-white">
                                            <?= esc(lang('app.view')) ?>
                                        </a>
                                        <a href="<?= site_url('system-admin/tenants/edit/' . (int) $row['id']) ?>" class="btn btn-sm btn-warning">
                                            <?= esc(lang('app.edit')) ?>
                                        </a>
                                        <form action="<?= site_url('system-admin/tenants/delete/' . (int) $row['id']) ?>" method="post" onsubmit="return confirm('<?= esc(lang('app.confirm_delete_tenant')) ?>')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <?= esc(lang('app.delete')) ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4"><?= esc(lang('app.no_data')) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>