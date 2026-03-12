<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-1"><?= esc($title ?? lang('app.users')) ?></h4>
                <div class="text-muted small"><?= esc(lang('app.user_management')) ?></div>
            </div>

            <?php if (function_exists('can') && can('users.create')): ?>
                <a href="<?= site_url('users/create') ?>" class="btn btn-primary">
                    <?= esc(lang('app.create_user')) ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text"
                       id="usersTableSearch"
                       class="form-control"
                       placeholder="<?= esc(lang('app.search')) ?>">
            </div>
        </div>

        <div class="table-responsive">
            <table id="usersTable" class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th><?= esc(lang('app.users')) ?></th>
                        <th><?= esc(lang('app.role')) ?></th>
                        <th><?= esc(lang('app.branch')) ?></th>
                        <th><?= esc(lang('app.phone')) ?></th>
                        <th><?= esc(lang('app.email')) ?></th>
                        <th class="text-center"><?= esc(lang('app.status')) ?></th>
                        <th width="220" class="text-center"><?= esc(lang('app.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($rows)): ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>0</td>
                                <td>
                                    <div class="fw-semibold"><?= esc($row['full_name'] ?? '-') ?></div>
                                    <div class="small text-muted"><?= esc($row['username'] ?? '-') ?></div>
                                </td>
                                <td><?= esc($row['role_name'] ?? '-') ?></td>
                                <td><?= esc(lfield($row, 'branch_name', '-')) ?></td>
                                <td><?= esc($row['phone'] ?? '-') ?></td>
                                <td><?= esc($row['email'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?php if ((int) ($row['status'] ?? 0) === 1): ?>
                                        <span class="badge bg-success"><?= esc(lang('app.active')) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc(lang('app.inactive')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (function_exists('can') && can('users.edit')): ?>
                                        <a href="<?= site_url('users/edit/' . (int) $row['id']) ?>" class="btn btn-sm btn-warning">
                                            <?= esc(lang('app.edit')) ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (function_exists('can') && can('users.delete')): ?>
                                        <form action="<?= site_url('users/delete/' . (int) $row['id']) ?>"
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
        'tableId'            => 'usersTable',
        'searchInputId'      => 'usersTableSearch',
        'pageLength'         => 10,
        'orderColumn'        => 1,
        'orderDir'           => 'asc',
        'nonSortableColumns' => [0, 7],
    ]);
?>
<?= $this->endSection() ?>