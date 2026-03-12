<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
$row      = $row ?? [];
$features = $features ?? [];
$isEdit   = ! empty($row['id']);

$featureEnabled = static function (array $features, string $key, int $default = 0): bool {
    return (int) old(str_replace('.', '_', $key), isset($features[$key]['enabled']) ? (int) $features[$key]['enabled'] : $default) === 1;
};

$featureLimit = static function (array $features, string $key, string $default = ''): string {
    return (string) old('limit_' . str_replace('.', '_', $key), $features[$key]['limit'] ?? $default);
};

$planType = old('plan_type', $row['plan_type'] ?? 'monthly');
$isActive = (int) old('is_active', $row['is_active'] ?? 1);
$isPublic = (int) old('is_public', $row['is_public'] ?? 1);
?>

<style>
    .plan-form-section {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 18px;
        background: #fff;
        height: 100%;
    }

    .plan-form-title {
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 14px;
    }

    .plan-form-subtitle {
        font-size: .82rem;
        color: #6b7280;
        margin-top: -8px;
        margin-bottom: 14px;
    }

    .feature-box {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 12px 14px;
        background: #f9fafb;
        height: 100%;
    }

    .feature-box .form-check {
        margin-bottom: 0;
    }

    .feature-limit-box {
        border: 1px dashed #d1d5db;
        border-radius: 14px;
        padding: 14px;
        background: #fcfcfd;
    }
</style>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-1"><?= esc($title ?? lang('app.subscription_plan')) ?></h4>
                <div class="text-muted small"><?= esc(lang('app.plan_form_help')) ?></div>
            </div>

            <a href="<?= site_url('super-admin/subscription-plans') ?>" class="btn btn-secondary">
                <?= esc(lang('app.back')) ?>
            </a>
        </div>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    <?php foreach ((array) session()->getFlashdata('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= $isEdit ? site_url('super-admin/subscription-plans/update/' . (int) $row['id']) : site_url('super-admin/subscription-plans/store') ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-12">
                    <div class="plan-form-section">
                        <div class="plan-form-title"><?= esc(lang('app.plan_information')) ?></div>
                        <div class="plan-form-subtitle"><?= esc(lang('app.plan_information_help')) ?></div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label"><?= esc(lang('app.plan_code')) ?></label>
                                <input type="text"
                                       name="code"
                                       class="form-control"
                                       value="<?= esc(old('code', $row['code'] ?? $row['plan_code'] ?? '')) ?>"
                                       required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><?= esc(lang('app.plan_type')) ?></label>
                                <select name="plan_type" class="form-select" required>
                                    <option value="daily" <?= $planType === 'daily' ? 'selected' : '' ?>><?= esc(lang('app.daily')) ?></option>
                                    <option value="monthly" <?= $planType === 'monthly' ? 'selected' : '' ?>><?= esc(lang('app.monthly')) ?></option>
                                    <option value="lifetime" <?= $planType === 'lifetime' ? 'selected' : '' ?>><?= esc(lang('app.lifetime')) ?></option>
                                    <option value="demo" <?= $planType === 'demo' ? 'selected' : '' ?>><?= esc(lang('app.demo')) ?></option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><?= esc(lang('app.duration_days')) ?></label>
                                <input type="number"
                                       name="duration_days"
                                       class="form-control"
                                       value="<?= esc(old('duration_days', $row['duration_days'] ?? '')) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><?= esc(lang('app.plan_name_th')) ?></label>
                                <input type="text"
                                       name="name_th"
                                       class="form-control"
                                       value="<?= esc(old('name_th', $row['name_th'] ?? $row['plan_name_th'] ?? '')) ?>"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><?= esc(lang('app.plan_name_en')) ?></label>
                                <input type="text"
                                       name="name_en"
                                       class="form-control"
                                       value="<?= esc(old('name_en', $row['name_en'] ?? $row['plan_name_en'] ?? '')) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><?= esc(lang('app.description_th')) ?></label>
                                <textarea name="description_th" class="form-control" rows="4"><?= esc(old('description_th', $row['description_th'] ?? '')) ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label"><?= esc(lang('app.description_en')) ?></label>
                                <textarea name="description_en" class="form-control" rows="4"><?= esc(old('description_en', $row['description_en'] ?? '')) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="plan-form-section">
                        <div class="plan-form-title"><?= esc(lang('app.pricing_visibility')) ?></div>
                        <div class="plan-form-subtitle"><?= esc(lang('app.pricing_visibility_help')) ?></div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label"><?= esc(lang('app.price')) ?></label>
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       name="price"
                                       class="form-control"
                                       value="<?= esc(old('price', $row['price'] ?? 0)) ?>"
                                       required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><?= esc(lang('app.currency')) ?></label>
                                <input type="text"
                                       name="currency"
                                       class="form-control"
                                       value="<?= esc(old('currency', $row['currency'] ?? 'THB')) ?>"
                                       required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><?= esc(lang('app.max_branches')) ?></label>
                                <input type="number"
                                       min="1"
                                       name="max_branches"
                                       class="form-control"
                                       value="<?= esc(old('max_branches', $row['max_branches'] ?? 1)) ?>"
                                       required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><?= esc(lang('app.max_users')) ?></label>
                                <input type="number"
                                       min="1"
                                       name="max_users"
                                       class="form-control"
                                       value="<?= esc(old('max_users', $row['max_users'] ?? 5)) ?>"
                                       required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><?= esc(lang('app.sort_order')) ?></label>
                                <input type="number"
                                       name="sort_order"
                                       class="form-control"
                                       value="<?= esc(old('sort_order', $row['sort_order'] ?? 0)) ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><?= esc(lang('app.active_status')) ?></label>
                                <select name="is_active" class="form-select">
                                    <option value="1" <?= $isActive === 1 ? 'selected' : '' ?>><?= esc(lang('app.active')) ?></option>
                                    <option value="0" <?= $isActive === 0 ? 'selected' : '' ?>><?= esc(lang('app.inactive')) ?></option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><?= esc(lang('app.public_visibility')) ?></label>
                                <select name="is_public" class="form-select">
                                    <option value="1" <?= $isPublic === 1 ? 'selected' : '' ?>><?= esc(lang('app.visible')) ?></option>
                                    <option value="0" <?= $isPublic === 0 ? 'selected' : '' ?>><?= esc(lang('app.hidden')) ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="plan-form-section">
                        <div class="plan-form-title"><?= esc(lang('app.plan_features')) ?></div>
                        <div class="plan-form-subtitle"><?= esc(lang('app.plan_features_help')) ?></div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="feature-box">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="feature_pos_access" name="feature_pos_access" value="1" <?= $featureEnabled($features, 'pos.access') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="feature_pos_access"><?= esc(lang('app.pos_access')) ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="feature-box">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="feature_pos_sell" name="feature_pos_sell" value="1" <?= $featureEnabled($features, 'pos.sell') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="feature_pos_sell"><?= esc(lang('app.pos_sell')) ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="feature-box">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="feature_reservations_manage" name="feature_reservations_manage" value="1" <?= $featureEnabled($features, 'reservations.manage') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="feature_reservations_manage"><?= esc(lang('app.reservation_management')) ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="feature-box">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="feature_tables_manage" name="feature_tables_manage" value="1" <?= $featureEnabled($features, 'tables.manage') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="feature_tables_manage"><?= esc(lang('app.table_management')) ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="feature-box">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="feature_zones_manage" name="feature_zones_manage" value="1" <?= $featureEnabled($features, 'zones.manage') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="feature_zones_manage"><?= esc(lang('app.zone_management')) ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="feature-box">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="feature_reports_basic" name="feature_reports_basic" value="1" <?= $featureEnabled($features, 'reports.basic') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="feature_reports_basic"><?= esc(lang('app.basic_reports')) ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="feature-box">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="feature_multi_branch" name="feature_multi_branch" value="1" <?= $featureEnabled($features, 'multi.branch') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="feature_multi_branch"><?= esc(lang('app.multi_branch')) ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="feature-limit-box">
                            <div class="fw-semibold mb-3"><?= esc(lang('app.additional_limits')) ?></div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"><?= esc(lang('app.branches_limit')) ?></label>
                                    <input type="number"
                                           min="1"
                                           name="limit_branches"
                                           class="form-control"
                                           value="<?= esc($featureLimit($features, 'branches_limit', (string) ($row['max_branches'] ?? 1))) ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label"><?= esc(lang('app.users_limit')) ?></label>
                                    <input type="number"
                                           min="1"
                                           name="limit_users"
                                           class="form-control"
                                           value="<?= esc($featureLimit($features, 'users_limit', (string) ($row['max_users'] ?? 5))) ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label"><?= esc(lang('app.products_limit')) ?></label>
                                    <input type="number"
                                           min="1"
                                           name="limit_products"
                                           class="form-control"
                                           value="<?= esc($featureLimit($features, 'products_limit', '100')) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="<?= site_url('super-admin/subscription-plans') ?>" class="btn btn-light border">
                            <?= esc(lang('app.cancel')) ?>
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <?= esc($isEdit ? lang('app.update') : lang('app.save')) ?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>