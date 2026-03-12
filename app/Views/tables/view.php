<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <?php
        echo view('partials/app_page_header', [
            'title' => lang('app.tables'),
            'desc'  => lang('app.quick_option_display_note'),
        ]);
        ?>

        <div class="app-section mb-4">
            <div class="app-section-title"><?= esc(lang('app.add_item')) ?></div>
            <div class="app-section-subtitle"><?= esc(lang('app.tables')) ?></div>

            <form action="<?= site_url('product-quick-options/create') ?>" method="post" class="row g-3">
                <?= csrf_field() ?>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.option_name_th')) ?></label>
                    <input
                        type="text"
                        name="option_name_th"
                        class="form-control"
                        value="<?= esc(old('option_name_th')) ?>"
                        placeholder="<?= esc(lang('app.option_name_th_placeholder')) ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= esc(lang('app.option_name_en')) ?></label>
                    <input
                        type="text"
                        name="option_name_en"
                        class="form-control"
                        value="<?= esc(old('option_name_en')) ?>"
                        placeholder="<?= esc(lang('app.option_name_en_placeholder')) ?>"
                    >
                </div>

                <div class="col-md-2">
                    <label class="form-label"><?= esc(lang('app.price_adjust')) ?></label>
                    <input type="number" step="0.01" min="0" name="price_adjust" class="form-control" value="<?= esc(old('price_adjust', '0')) ?>">
                </div>

                <div class="col-md-1">
                    <label class="form-label"><?= esc(lang('app.sort_order')) ?></label>
                    <input type="number" name="sort_order" class="form-control" value="<?= esc(old('sort_order', '0')) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label"><?= esc(lang('app.specific_product')) ?></label>
                    <select name="product_id" class="form-select">
                        <option value=""><?= esc(lang('app.all_products')) ?></option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= old('product_id') == $p['id'] ? 'selected' : '' ?>>
                                <?= esc($p['product_name'] ?? $p['name'] ?? '-') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label"><?= esc(lang('app.category')) ?></label>
                    <select name="category_id" class="form-select">
                        <option value=""><?= esc(lang('app.all_categories')) ?></option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= old('category_id') == $c['id'] ? 'selected' : '' ?>>
                                <?= esc($c['category_name'] ?? $c['name'] ?? '-') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="form-label"><?= esc(lang('app.status')) ?></label>
                    <select name="status" class="form-select">
                        <option value="1" <?= old('status', '1') == '1' ? 'selected' : '' ?>><?= esc(lang('app.active')) ?></option>
                        <option value="0" <?= old('status') == '0' ? 'selected' : '' ?>><?= esc(lang('app.inactive')) ?></option>
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <?= esc(lang('app.add_item')) ?>
                    </button>
                </div>
            </form>
        </div>

        <?php
        echo view('partials/app_toolbar', [
            'left' => '
                <input type="text"
                       id="productQuickOptionsTableSearch"
                       class="form-control app-search-input"
                       placeholder="' . esc(lang('app.search')) . '">
            ',
        ]);
        ?>

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="mainTable">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th><?= esc(lang('app.option_name_th')) ?></th>
                        <th><?= esc(lang('app.option_name_en')) ?></th>
                        <th width="120"><?= esc(lang('app.price_adjust')) ?></th>
                        <th width="100"><?= esc(lang('app.sort_order')) ?></th>
                        <th width="160"><?= esc(lang('app.specific_product')) ?></th>
                        <th width="160"><?= esc(lang('app.category')) ?></th>
                        <th width="110"><?= esc(lang('app.status')) ?></th>
                        <th width="220" class="text-center"><?= esc(lang('app.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($options)): ?>
                        <?php foreach ($options as $row): ?>
                            <?php $formId = 'updateForm' . (int) $row['id']; ?>
                            <tr>
                                <td><?= (int) $row['id'] ?></td>

                                <td>
                                    <input
                                        type="text"
                                        name="option_name_th"
                                        form="<?= $formId ?>"
                                        class="form-control"
                                        value="<?= esc($row['option_name_th'] ?? $row['option_name'] ?? '') ?>"
                                        placeholder="<?= esc(lang('app.option_name_th_placeholder')) ?>"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="text"
                                        name="option_name_en"
                                        form="<?= $formId ?>"
                                        class="form-control"
                                        value="<?= esc($row['option_name_en'] ?? '') ?>"
                                        placeholder="<?= esc(lang('app.option_name_en_placeholder')) ?>"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="price_adjust"
                                        form="<?= $formId ?>"
                                        class="form-control"
                                        value="<?= esc($row['price_adjust']) ?>"
                                    >
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="sort_order"
                                        form="<?= $formId ?>"
                                        class="form-control"
                                        value="<?= esc($row['sort_order']) ?>"
                                    >
                                </td>

                                <td>
                                    <select name="product_id" form="<?= $formId ?>" class="form-select">
                                        <option value=""><?= esc(lang('app.all_products')) ?></option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?= (int) $p['id'] ?>" <?= (string) ($row['product_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>>
                                                <?= esc($p['product_name'] ?? $p['name'] ?? '-') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td>
                                    <select name="category_id" form="<?= $formId ?>" class="form-select">
                                        <option value=""><?= esc(lang('app.all_categories')) ?></option>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?= (int) $c['id'] ?>" <?= (string) ($row['category_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
                                                <?= esc($c['category_name'] ?? $c['name'] ?? '-') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>

                                <td>
                                    <select name="status" form="<?= $formId ?>" class="form-select">
                                        <option value="1" <?= (int) $row['status'] === 1 ? 'selected' : '' ?>><?= esc(lang('app.active')) ?></option>
                                        <option value="0" <?= (int) $row['status'] === 0 ? 'selected' : '' ?>><?= esc(lang('app.inactive')) ?></option>
                                    </select>
                                </td>

                                <td class="text-center">
                                    <div class="d-flex gap-1 flex-wrap justify-content-center">
                                        <form id="<?= $formId ?>" action="<?= site_url('product-quick-options/update/' . (int) $row['id']) ?>" method="post" class="d-inline">
                                            <?= csrf_field() ?>
                                        </form>

                                        <button type="submit" form="<?= $formId ?>" class="btn btn-sm btn-warning">
                                            <?= esc(lang('app.save')) ?>
                                        </button>

                                        <form action="<?= site_url('product-quick-options/delete/' . (int) $row['id']) ?>"
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
                    <?php else: ?>
                        <tr>
                            <td colspan="9">
                                <div class="app-empty">
                                    <div class="app-empty-title"><?= esc(lang('app.no_data')) ?></div>
                                    <div class="app-empty-text"><?= esc(lang('app.quick_option_display_note')) ?></div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($options)): ?>
            <div class="mt-3 small text-muted">
                <?= esc(lang('app.quick_option_display_note')) ?>:
                <strong>
                    <?= esc(service('request')->getLocale() === 'en' ? lang('app.english_first') : lang('app.thai_first')) ?>
                </strong>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function () {
    const deleteConfirmMessage = <?= json_encode(lang('app.confirm_delete_item'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    $(document).on('submit', '.delete-form', function (e) {
        if (!confirm(deleteConfirmMessage)) {
            e.preventDefault();
            return false;
        }
    });

    const table = $('#mainTable').DataTable({
        pageLength: 10,
        autoWidth: false,
        scrollX: true,
        language: <?= json_encode(dt_lang(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        columnDefs: [
            { targets: [8], orderable: false, searchable: false }
        ]
    });

    $('#productQuickOptionsTableSearch').on('keyup change', function () {
        table.search(this.value).draw();
    });
});
</script>
<?= $this->endSection() ?>