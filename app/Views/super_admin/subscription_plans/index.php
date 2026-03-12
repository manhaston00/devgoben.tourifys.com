<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-1"><?= esc($title ?? lang('app.subscription_plans')) ?></h4>
                <div class="text-muted small"><?= esc(lang('app.manage_saas_plans')) ?></div>
            </div>

            <?php if (function_exists('can') && can('plans.create')): ?>
                <a href="<?= site_url('super-admin/subscription-plans/create') ?>" class="btn btn-primary">
                    <?= esc(lang('app.add_subscription_plan')) ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text"
                       id="plansTableSearch"
                       class="form-control"
                       placeholder="<?= esc(lang('app.search_code_or_plan_name')) ?>">
            </div>
        </div>

        <div class="table-responsive">
            <table id="plansTable" class="table table-bordered align-middle w-100">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th><?= esc(lang('app.plan_code')) ?></th>
                        <th><?= esc(lang('app.plan_name')) ?></th>
                        <th><?= esc(lang('app.plan_type')) ?></th>
                        <th class="text-end"><?= esc(lang('app.price')) ?></th>
                        <th class="text-center"><?= esc(lang('app.max_branches')) ?></th>
                        <th class="text-center"><?= esc(lang('app.max_users')) ?></th>
                        <th class="text-center"><?= esc(lang('app.status')) ?></th>
                        <th class="text-center"><?= esc(lang('app.sort_order')) ?></th>
                        <th width="220" class="text-center"><?= esc(lang('app.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($rows)): ?>
                        <?php foreach ($rows as $index => $row): ?>
                            <?php
                                $planType = (string) ($row['plan_type'] ?? 'monthly');

                                if ($planType === 'daily') {
                                    $typeLabel = lang('app.daily');
                                } elseif ($planType === 'monthly') {
                                    $typeLabel = lang('app.monthly');
                                } elseif ($planType === 'lifetime') {
                                    $typeLabel = lang('app.lifetime');
                                } elseif ($planType === 'demo') {
                                    $typeLabel = lang('app.demo');
                                } else {
                                    $typeLabel = ucfirst($planType !== '' ? $planType : '-');
                                }

                                $statusHtml = '';
                                if (! empty($row['deleted_at'])) {
                                    $statusHtml = '<span class="badge bg-danger">' . esc(lang('app.deleted')) . '</span>';
                                } elseif ((int) ($row['is_active'] ?? 0) === 1) {
                                    $statusHtml = '<span class="badge bg-success">' . esc(lang('app.active')) . '</span>';
                                } else {
                                    $statusHtml = '<span class="badge bg-secondary">' . esc(lang('app.inactive')) . '</span>';
                                }

                                ob_start();
                            ?>
                                <?php if (empty($row['deleted_at'])): ?>
                                    <?php if (function_exists('can') && can('plans.edit')): ?>
                                        <a href="<?= site_url('super-admin/subscription-plans/edit/' . (int) $row['id']) ?>" class="btn btn-sm btn-warning">
                                            <?= esc(lang('app.edit')) ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (function_exists('can') && can('plans.delete')): ?>
                                        <form action="<?= site_url('super-admin/subscription-plans/delete/' . (int) $row['id']) ?>"
                                              method="post"
                                              class="d-inline"
                                              onsubmit="return confirm('<?= esc(lang('app.confirm_delete_plan'), 'js') ?>');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <?= esc(lang('app.delete')) ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (function_exists('can') && can('plans.delete')): ?>
                                        <form action="<?= site_url('super-admin/subscription-plans/restore/' . (int) $row['id']) ?>"
                                              method="post"
                                              class="d-inline"
                                              onsubmit="return confirm('<?= esc(lang('app.confirm_restore_plan'), 'js') ?>');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <?= esc(lang('app.restore')) ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php
                                $actionsHtml = trim(ob_get_clean());
                            ?>
                            <tr>
                                <td><?= (int) ($index + 1) ?></td>
                                <td><?= esc($row['code'] ?? $row['plan_code'] ?? '-') ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc($row['name_th'] ?? $row['plan_name_th'] ?? $row['plan_name'] ?? '-') ?></div>
                                    <?php if (! empty($row['name_en']) || ! empty($row['plan_name_en'])): ?>
                                        <div class="small text-muted"><?= esc($row['name_en'] ?? $row['plan_name_en'] ?? '') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?= esc($typeLabel) ?></div>
                                    <?php if (! empty($row['duration_days'])): ?>
                                        <div class="small text-muted"><?= esc($row['duration_days']) ?> <?= esc(lang('app.days')) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= number_format((float) ($row['price'] ?? 0), 2) ?></td>
                                <td class="text-center"><?= esc($row['max_branches'] ?? 0) ?></td>
                                <td class="text-center"><?= esc($row['max_users'] ?? 0) ?></td>
                                <td class="text-center"><?= $statusHtml ?></td>
                                <td class="text-center"><?= esc($row['sort_order'] ?? 0) ?></td>
                                <td class="text-center"><?= $actionsHtml ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function () {
        var table = $('#plansTable').DataTable({
            processing: true,
            serverSide: false,
            autoWidth: false,
            scrollX: true,
            pageLength: 10,
            order: [[1, 'asc']],
            language: <?= json_encode(dt_lang(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            columnDefs: [
                { targets: [9], orderable: false, searchable: false },
                { targets: [7], searchable: false },
                { targets: [8], searchable: false }
            ]
        });

        $('#plansTableSearch').on('keyup change', function () {
            table.search(this.value).draw();
        });
    });
</script>
<?= $this->endSection() ?>