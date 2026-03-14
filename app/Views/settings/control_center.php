<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
$scopeBase = $isSuperAdmin ? 'platform' : 'tenant';
$scopeBaseUrl = $baseUrl . '?scope=' . $scopeBase;
$scopeBranchUrl = $baseUrl . '?scope=branch'
    . ($tenantId > 0 ? '&tenant_id=' . (int) $tenantId : '')
    . ($branchId > 0 ? '&branch_id=' . (int) $branchId : '');
$scopeTenantUrl = $baseUrl . '?scope=tenant' . ($tenantId > 0 ? '&tenant_id=' . (int) $tenantId : '');
$showScopePicker = $isSuperAdmin;
?>

<div class="card card-soft mb-3">
    <div class="card-body">
        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center gap-3">
            <div>
                <h4 class="mb-1"><?= esc($title ?? lang('settings_control.settings_control_title')) ?></h4>
                <div class="text-muted small"><?= esc(lang('settings_control.settings_control_desc')) ?></div>
            </div>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="<?= esc($scopeBaseUrl) ?>" class="btn <?= $scope === $scopeBase ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                    <?= esc($isSuperAdmin ? lang('settings_control.settings_control_scope_platform') : lang('settings_control.settings_control_scope_tenant')) ?>
                </a>

                <?php if ($isSuperAdmin): ?>
                    <a href="<?= esc($scopeTenantUrl) ?>" class="btn <?= $scope === 'tenant' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                        <?= esc(lang('settings_control.settings_control_scope_tenant')) ?>
                    </a>
                <?php endif; ?>

                <a href="<?= esc($scopeBranchUrl) ?>" class="btn <?= $scope === 'branch' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                    <?= esc(lang('settings_control.settings_control_scope_branch')) ?>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card card-soft mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <?php if (! empty($tenant)): ?>
                <div class="col-12 col-lg-4">
                    <div class="small text-muted mb-1"><?= esc(lang('settings_control.settings_control_current_tenant')) ?></div>
                    <div class="fw-semibold"><?= esc($tenant['tenant_name'] ?? ('#' . (int) ($tenant['id'] ?? 0))) ?></div>
                </div>
            <?php endif; ?>

            <?php if (! empty($branch)): ?>
                <div class="col-12 col-lg-4">
                    <div class="small text-muted mb-1"><?= esc(lang('settings_control.settings_control_current_branch')) ?></div>
                    <div class="fw-semibold"><?= esc($branch['branch_name_th'] ?? $branch['branch_name'] ?? ('#' . (int) ($branch['id'] ?? 0))) ?></div>
                </div>
            <?php endif; ?>

            <div class="col-12 <?= (! empty($tenant) || ! empty($branch)) ? 'col-lg-4' : 'col-lg-12' ?>">
                <div class="small text-muted mb-1"><?= esc(lang('settings_control.settings_control_scope')) ?></div>
                <div class="text-muted small">
                    <?= esc(lang('settings_control.settings_control_scope_desc')) ?>
                </div>
            </div>
        </div>

        <?php if ($showScopePicker): ?>
            <hr class="my-3">

            <form method="get" action="<?= site_url('super-admin/settings/control-center') ?>" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label"><?= esc(lang('settings_control.settings_control_scope')) ?></label>
                    <select name="scope" class="form-select">
                        <option value="platform" <?= $scope === 'platform' ? 'selected' : '' ?>><?= esc(lang('settings_control.settings_control_scope_platform')) ?></option>
                        <option value="tenant" <?= $scope === 'tenant' ? 'selected' : '' ?>><?= esc(lang('settings_control.settings_control_scope_tenant')) ?></option>
                        <option value="branch" <?= $scope === 'branch' ? 'selected' : '' ?>><?= esc(lang('settings_control.settings_control_scope_branch')) ?></option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label"><?= esc(lang('settings_control.settings_control_target_tenant')) ?></label>
                    <select name="tenant_id" class="form-select">
                        <option value="0">--</option>
                        <?php foreach (($tenantOptions ?? []) as $row): ?>
                            <option value="<?= (int) ($row['id'] ?? 0) ?>" <?= (int) ($row['id'] ?? 0) === (int) ($tenantId ?? 0) ? 'selected' : '' ?>>
                                <?= esc($row['tenant_name'] ?? ('#' . (int) ($row['id'] ?? 0))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label"><?= esc(lang('settings_control.settings_control_target_branch')) ?></label>
                    <select name="branch_id" class="form-select">
                        <option value="0">--</option>
                        <?php foreach (($branchOptions ?? []) as $row): ?>
                            <option value="<?= (int) ($row['id'] ?? 0) ?>" <?= (int) ($row['id'] ?? 0) === (int) ($branchId ?? 0) ? 'selected' : '' ?>>
                                <?= esc(($row['branch_name_th'] ?? $row['branch_name'] ?? 'Branch') . ' (#' . (int) ($row['id'] ?? 0) . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary"><?= esc(lang('app.search')) ?></button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="accordion" id="settingsControlAccordion">
    <?php $sectionIndex = 0; ?>
    <?php foreach (($sections ?? []) as $sectionKey => $section): ?>
        <div class="accordion-item mb-3 border rounded-3 overflow-hidden">
            <h2 class="accordion-header" id="heading-<?= esc($sectionKey) ?>">
                <button class="accordion-button <?= $sectionIndex > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= esc($sectionKey) ?>" aria-expanded="<?= $sectionIndex === 0 ? 'true' : 'false' ?>">
                    <?= esc($section['title'] ?? $sectionKey) ?>
                </button>
            </h2>
            <div id="collapse-<?= esc($sectionKey) ?>" class="accordion-collapse collapse <?= $sectionIndex === 0 ? 'show' : '' ?>" data-bs-parent="#settingsControlAccordion">
                <div class="accordion-body">
                    <form method="post" action="<?= esc($saveUrl) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="scope" value="<?= esc($scope) ?>">
                        <input type="hidden" name="section_key" value="<?= esc($sectionKey) ?>">
                        <input type="hidden" name="tenant_id" value="<?= (int) ($tenantId ?? 0) ?>">
                        <input type="hidden" name="branch_id" value="<?= (int) ($branchId ?? 0) ?>">

                        <div class="row g-3">
                            <?php foreach (($section['settings'] ?? []) as $setting): ?>
                                <?php
                                $settingType = (string) ($setting['type'] ?? '');
                                $inputName = (string) ($setting['input_name'] ?? '');
                                $planAllowed = ! empty($setting['plan_allowed']);
                                $editable = ! empty($canEdit) && $planAllowed;
                                ?>
                                <div class="col-12 col-xl-6">
                                    <div class="border rounded-3 p-3 h-100 bg-body-tertiary">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <div class="fw-semibold"><?= esc($setting['label'] ?? $setting['key']) ?></div>
                                                <div class="small text-muted"><?= esc($setting['key'] ?? '-') ?></div>
                                            </div>
                                            <?php if (! $planAllowed): ?>
                                                <span class="badge text-bg-warning"><?= esc(lang('settings_control.settings_control_plan_locked')) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="row g-2 align-items-end">
                                            <div class="col-12 col-md-6">
                                                <?php if ($settingType === 'boolean'): ?>
                                                    <?php if ($editable && $inputName !== ''): ?>
                                                        <input type="hidden" name="<?= esc($inputName) ?>" value="0">
                                                    <?php endif; ?>
                                                    <div class="form-check form-switch">
                                                        <input
                                                            class="form-check-input"
                                                            type="checkbox"
                                                            role="switch"
                                                            id="<?= esc($inputName) ?>"
                                                            name="<?= esc($inputName) ?>"
                                                            value="1"
                                                            <?= ! empty($setting['effective_value']) ? 'checked' : '' ?>
                                                            <?= $editable ? '' : 'disabled' ?>
                                                        >
                                                        <label class="form-check-label" for="<?= esc($inputName) ?>">
                                                            <?= esc(lang('settings_control.settings_control_input_type_boolean')) ?>
                                                        </label>
                                                    </div>
                                                <?php elseif ($settingType === 'number'): ?>
                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        class="form-control"
                                                        name="<?= esc($inputName) ?>"
                                                        value="<?= esc((string) ($setting['effective_value'] ?? '')) ?>"
                                                        <?= $editable ? '' : 'disabled' ?>
                                                    >
                                                <?php else: ?>
                                                    <select class="form-select" name="<?= esc($inputName) ?>" <?= $editable ? '' : 'disabled' ?>>
                                                        <?php foreach (($setting['options'] ?? []) as $optionKey => $optionLabel): ?>
                                                            <option value="<?= esc($optionKey) ?>" <?= (string) ($setting['effective_value'] ?? '') === (string) $optionKey ? 'selected' : '' ?>>
                                                                <?= esc($optionLabel) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-6 col-md-3">
                                                <div class="small text-muted mb-1"><?= esc(lang('settings_control.settings_control_effective_value')) ?></div>
                                                <div class="fw-semibold">
                                                    <?php if ($settingType === 'boolean'): ?>
                                                        <?= ! empty($setting['effective_value']) ? esc(lang('app.yes')) : esc(lang('app.no')) ?>
                                                    <?php else: ?>
                                                        <?= esc((string) ($setting['effective_value'] ?? '')) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="col-6 col-md-3">
                                                <div class="small text-muted mb-1"><?= esc(lang('settings_control.settings_control_value_source')) ?></div>
                                                <div>
                                                    <?php
                                                    $sourceMap = [
                                                        'default'  => lang('settings_control.settings_control_source_default'),
                                                        'platform' => lang('settings_control.settings_control_source_platform'),
                                                        'tenant'   => lang('settings_control.settings_control_source_tenant'),
                                                        'branch'   => lang('settings_control.settings_control_source_branch'),
                                                    ];
                                                    ?>
                                                    <span class="badge text-bg-secondary"><?= esc($sourceMap[$setting['value_source'] ?? 'default'] ?? ($setting['value_source'] ?? '-')) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary" <?= ! empty($canEdit) ? '' : 'disabled' ?>>
                                <?= esc(lang('settings_control.settings_control_save_section')) ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php $sectionIndex++; ?>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
