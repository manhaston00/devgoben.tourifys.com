<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <h5 class="mb-0"><?= lang('app.menu_categories') ?></h5>

            <div class="page-actions">
                <a href="<?= site_url('categories/create') ?>" class="btn btn-primary">
                    <?= lang('app.add_category') ?>
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="mainTable">
                <thead>
                    <tr>
                        <th width="80">#</th>
                        <th><?= lang('app.category_name') ?></th>
                        <th width="120"><?= lang('app.sort_order') ?></th>
                        <th width="120"><?= lang('app.status') ?></th>
                        <th width="180"><?= lang('app.actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($rows)): ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $locale = service('request')->getLocale();

                                if ($locale === 'th') {
                                    $categoryName = trim((string) ($row['category_name_th'] ?? '')) !== ''
                                        ? (string) $row['category_name_th']
                                        : (string) ($row['category_name'] ?? '-');
                                } else {
                                    $categoryName = trim((string) ($row['category_name_en'] ?? '')) !== ''
                                        ? (string) $row['category_name_en']
                                        : (string) ($row['category_name'] ?? '-');
                                }

                                $status = (int) ($row['status'] ?? 0);
                                $badgeClass = $status === 1 ? 'success' : 'secondary';
                                $statusText = $status === 1 ? lang('app.active') : lang('app.inactive');
                            ?>
                            <tr>
                                <td><?= (int) ($row['id'] ?? 0) ?></td>
                                <td><?= esc($categoryName) ?></td>
                                <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
                                <td>
                                    <span class="badge bg-<?= esc($badgeClass) ?>">
                                        <?= esc($statusText) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="<?= site_url('categories/edit/' . (int) ($row['id'] ?? 0)) ?>"
                                           class="btn btn-sm btn-warning">
                                            <?= lang('app.edit') ?>
                                        </a>

                                        <form action="<?= site_url('categories/delete/' . (int) ($row['id'] ?? 0)) ?>"
                                              method="post"
                                              class="d-inline delete-form">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <?= lang('app.delete') ?>
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
    const deleteConfirmMessage = <?= json_encode(lang('app.confirm_delete_category'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const dtLanguage = <?= json_encode(dt_lang(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    $('#mainTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']],
        autoWidth: false,
        scrollX: true,
        language: dtLanguage
    });

    $(document).on('submit', '.delete-form', function (e) {
        if (! confirm(deleteConfirmMessage)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
<?= $this->endSection() ?>