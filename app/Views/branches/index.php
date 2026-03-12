<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-1"><?= esc($title ?? lang('app.branches')) ?></h4>
                <div class="text-muted small"><?= esc(lang('app.branch_management')) ?></div>
            </div>

            <?php if (function_exists('can') && can('branches.create')): ?>
                <a href="<?= site_url('branches/create') ?>" class="btn btn-primary">
                    <?= esc(lang('app.add_branch')) ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text"
                       id="branchesTableSearch"
                       class="form-control"
                       placeholder="<?= esc(lang('app.search')) ?>">
            </div>
        </div>

        <div class="table-responsive">
            <table id="branchesTable" class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th><?= esc(lang('app.branch_code')) ?></th>
                        <th><?= esc(lang('app.branch_name_th')) ?></th>
                        <th><?= esc(lang('app.branch_name_en')) ?></th>
                        <th><?= esc(lang('app.tax_id')) ?></th>
                        <th><?= esc(lang('app.phone')) ?></th>
                        <th><?= esc(lang('app.email')) ?></th>
                        <th><?= esc(lang('app.branch_open_date')) ?></th>
                        <th><?= esc(lang('app.set_as_head_office')) ?></th>
                        <th class="text-center"><?= esc(lang('app.status')) ?></th>
                        <th width="200" class="text-center"><?= esc(lang('app.actions')) ?></th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (! empty($rows)): ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>0</td>

                                <td><?= esc($row['branch_code'] ?? '-') ?></td>
                                <td><?= esc($row['branch_name_th'] ?? '-') ?></td>
                                <td><?= esc($row['branch_name_en'] ?? '-') ?></td>
                                <td><?= esc($row['tax_id'] ?? '-') ?></td>
                                <td><?= esc($row['phone'] ?? '-') ?></td>
                                <td><?= esc($row['email'] ?? '-') ?></td>
                                <td><?= esc($row['branch_open_date'] ?? '-') ?></td>
                                <td>
                                    <?php if ((int) ($row['is_head_office'] ?? 0) === 1): ?>
										<span class="badge bg-info text-dark"><?= esc(lang('app.head_office')) ?></span>
									<?php else: ?>
										<span class="text-muted"><?= esc(lang('app.not_head_office')) ?></span>
									<?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <?php if ((int) ($row['status'] ?? 0) === 1): ?>
                                        <span class="badge bg-success"><?= esc(lang('app.active')) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc(lang('app.inactive')) ?></span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <?php if (function_exists('can') && can('branches.edit')): ?>
                                        <a href="<?= site_url('branches/edit/' . (int) $row['id']) ?>"
                                           class="btn btn-sm btn-warning">
                                            <?= esc(lang('app.edit')) ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (function_exists('can') && can('branches.delete')): ?>
                                        <form action="<?= site_url('branches/delete/' . (int) $row['id']) ?>"
                                              method="post"
                                              class="d-inline"
                                              onsubmit="return confirm('<?= esc(lang('app.confirm_delete_item')) ?>');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <?= esc(lang('app.delete')) ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
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
<?php
echo view('partials/super_admin_datatable', [
    'tableId'            => 'branchesTable',
    'searchInputId'      => 'branchesTableSearch',
    'pageLength'         => 10,
    'orderColumn'        => 1,
    'orderDir'           => 'asc',
    'nonSortableColumns' => [0, 10],
]);
?>
<?= $this->endSection() ?>