<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <?php
        ob_start();
        ?>
            <?php if (function_exists('can') && can('roles.create')): ?>
                <a href="<?= site_url('roles/create') ?>" class="btn btn-primary">
                    <?= esc(lang('app.add_role')) ?>
                </a>
            <?php endif; ?>
        <?php
        $actions = ob_get_clean();

        echo view('partials/app_page_header', [
            'title'   => lang('app.role_list'),
            'desc'    => lang('app.roles'),
            'actions' => $actions,
        ]);

        echo view('partials/app_toolbar', [
            'left' => '
                <input type="text"
                       id="mainTableSearch"
                       class="form-control app-search-input"
                       placeholder="' . esc(lang('app.search')) . '">
            ',
        ]);
        ?>

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="mainTable">
                <thead>
                    <tr>
                        <th width="80">#</th>
                        <th><?= esc(lang('app.role_name')) ?></th>
                        <th><?= esc(lang('app.description')) ?></th>
                        <th width="120" class="text-center"><?= esc(lang('app.status')) ?></th>
                        <th width="220" class="text-center"><?= esc(lang('app.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($rows)): ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $roleName = function_exists('lfield')
                                    ? lfield($row, 'role_name')
                                    : ($row['role_name'] ?? '-');

                                $description = function_exists('lfield')
                                    ? lfield($row, 'description')
                                    : ($row['description'] ?? '-');
                            ?>
                            <tr>
                                <td><?= (int) ($row['id'] ?? 0) ?></td>
                                <td><?= esc($roleName ?: '-') ?></td>
                                <td><?= esc($description ?: '-') ?></td>
                                <td class="text-center">
                                    <?php if ((int) ($row['status'] ?? 0) === 1): ?>
                                        <span class="badge bg-success"><?= esc(active_text(1)) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc(active_text(0)) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                                        <?php if (function_exists('can') && can('roles.edit')): ?>
                                            <a href="<?= site_url('roles/edit/' . (int) ($row['id'] ?? 0)) ?>" class="btn btn-sm btn-warning">
                                                <?= esc(lang('app.edit')) ?>
                                            </a>

                                            <a href="<?= site_url('roles/permissions/' . (int) ($row['id'] ?? 0)) ?>" class="btn btn-sm btn-info text-white">
                                                <?= esc(lang('app.permissions')) ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if (function_exists('can') && can('roles.delete')): ?>
                                            <form action="<?= site_url('roles/delete/' . (int) ($row['id'] ?? 0)) ?>"
                                                  method="post"
                                                  class="d-inline delete-form">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <?= esc(lang('app.delete')) ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
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
<script>
$(function () {
    const table = $('#mainTable').DataTable({
        pageLength: 10,
        order: $('#mainTable tbody tr').length > 0 ? [[0, 'desc']] : [],
        autoWidth: false,
        scrollX: true,
        language: $.extend(true, {}, <?= json_encode(dt_lang(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, {
            emptyTable: <?= json_encode(lang('app.no_role_data_found'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        })
    });

    $('#mainTableSearch').on('keyup change', function () {
        table.search(this.value).draw();
    });

    $(document).on('submit', '.delete-form', function (e) {
        if (!confirm(<?= json_encode(lang('app.confirm_delete_item'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
<?= $this->endSection() ?>