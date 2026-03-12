<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <?php
        ob_start();
        ?>
            <a href="<?= site_url('quick-notes/create') ?>" class="btn btn-primary">
                <?= esc(lang('app.create_quick_note')) ?>
            </a>
        <?php
        $actions = ob_get_clean();

        echo view('partials/app_page_header', [
            'title'   => lang('app.quick_notes'),
            'desc'    => lang('app.quick_note_hint_list'),
            'actions' => $actions,
        ]);

        echo view('partials/app_toolbar', [
            'left' => '
                <input type="text"
                       id="quickNotesTableSearch"
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
                        <th><?= esc(lang('app.note_name')) ?></th>
                        <th width="120"><?= esc(lang('app.sort_order')) ?></th>
                        <th width="120"><?= esc(lang('app.status')) ?></th>
                        <th width="220" class="text-center"><?= esc(lang('app.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($items)): ?>
                        <?php foreach ($items as $i => $row): ?>
                            <?php
                                $status     = (int) ($row['status'] ?? 0);
                                $toggleText = $status === 1 ? lang('app.disable') : lang('app.enable');
                                $noteName   = lfield($row, 'note_name');
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= esc($noteName) ?></td>
                                <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
                                <td class="text-center">
                                    <span class="badge text-bg-<?= $status === 1 ? 'success' : 'secondary' ?>">
                                        <?= esc(active_text($status)) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                                        <a href="<?= site_url('quick-notes/edit/' . (int) ($row['id'] ?? 0)) ?>"
                                           class="btn btn-sm btn-warning">
                                            <?= esc(lang('app.edit')) ?>
                                        </a>

                                        <form action="<?= site_url('quick-notes/toggle/' . (int) ($row['id'] ?? 0)) ?>"
                                              method="post"
                                              class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <?= esc($toggleText) ?>
                                            </button>
                                        </form>

                                        <form action="<?= site_url('quick-notes/delete/' . (int) ($row['id'] ?? 0)) ?>"
                                              method="post"
                                              class="d-inline delete-form">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <?= esc(lang('app.delete')) ?>
                                            </button>
                                        </form>
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
    const deleteConfirmMessage = <?= json_encode(lang('app.confirm_delete_item'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    $(document).on('submit', '.delete-form', function (e) {
        if (!confirm(deleteConfirmMessage)) {
            e.preventDefault();
            return false;
        }
    });

    const dtLanguage = <?= json_encode(dt_lang(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    dtLanguage.emptyTable = <?= json_encode(lang('app.no_data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    dtLanguage.zeroRecords = <?= json_encode(lang('app.no_data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const table = $('#mainTable').DataTable({
        pageLength: 10,
        autoWidth: false,
        language: dtLanguage,
        columnDefs: [
            { targets: [0, 3, 4], orderable: false }
        ]
    });

    $('#quickNotesTableSearch').on('keyup change', function () {
        table.search(this.value).draw();
    });
});
</script>
<?= $this->endSection() ?>