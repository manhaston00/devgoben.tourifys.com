<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="row justify-content-center">
    <div class="col-xl-7 col-lg-8">
        <div class="card card-soft">
            <div class="card-body">
                <?php
                ob_start();
                ?>
                    <a href="<?= site_url('quick-notes') ?>" class="btn btn-secondary">
                        <?= esc(lang('app.back')) ?>
                    </a>
                <?php
                $actions = ob_get_clean();

                echo view('partials/app_page_header', [
                    'title'   => $title ?? lang('app.quick_notes'),
                    'desc'    => lang('app.quick_note_hint'),
                    'actions' => $actions,
                ]);
                ?>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <form action="<?= esc($action ?? site_url('quick-notes/store')) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="note_name" value="<?= esc(old('note_name', $item['note_name'] ?? '')) ?>">

                    <div class="app-section">
                        <div class="app-section-title"><?= esc(lang('app.quick_notes')) ?></div>
                        <div class="app-section-subtitle"><?= esc(lang('app.quick_note_hint')) ?></div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?= esc(lang('app.note_name_th')) ?></label>
                                <input type="text"
                                       name="note_name_th"
                                       class="form-control"
                                       value="<?= esc(old('note_name_th', $item['note_name_th'] ?? $item['note_name'] ?? '')) ?>"
                                       placeholder="<?= esc(lang('app.note_name_th')) ?>"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><?= esc(lang('app.note_name_en')) ?></label>
                                <input type="text"
                                       name="note_name_en"
                                       class="form-control"
                                       value="<?= esc(old('note_name_en', $item['note_name_en'] ?? $item['note_name'] ?? '')) ?>"
                                       placeholder="<?= esc(lang('app.note_name_en')) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><?= esc(lang('app.sort_order')) ?></label>
                                <input type="number"
                                       name="sort_order"
                                       class="form-control"
                                       value="<?= esc(old('sort_order', $item['sort_order'] ?? 0)) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><?= esc(lang('app.status')) ?></label>
                                <select name="status" class="form-select">
                                    <option value="1" <?= (string) old('status', $item['status'] ?? 1) === '1' ? 'selected' : '' ?>>
                                        <?= esc(lang('app.active')) ?>
                                    </option>
                                    <option value="0" <?= (string) old('status', $item['status'] ?? 1) === '0' ? 'selected' : '' ?>>
                                        <?= esc(lang('app.inactive')) ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="<?= site_url('quick-notes') ?>" class="btn btn-light border">
                            <?= esc(lang('app.cancel')) ?>
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <?= esc(lang('app.save')) ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>