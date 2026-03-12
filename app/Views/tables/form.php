<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
$locale = service('request')->getLocale();
$row    = is_array($row ?? null) ? $row : [];

if (!function_exists('zone_label')) {
    function zone_label(array $zone, string $locale): string
    {
        $nameTh = trim((string) ($zone['zone_name_th'] ?? ''));
        $nameEn = trim((string) ($zone['zone_name_en'] ?? ''));

        if ($locale === 'en') {
            return $nameEn !== '' ? $nameEn : ($nameTh !== '' ? $nameTh : (lang('app.zone_number_prefix') . ' #' . ($zone['id'] ?? '')));
        }

        return $nameTh !== '' ? $nameTh : ($nameEn !== '' ? $nameEn : (lang('app.zone_number_prefix') . ' #' . ($zone['id'] ?? '')));
    }
}

$selectedZoneId = old('zone_id', $row['zone_id'] ?? '');
$tableName      = old('table_name', $row['table_name'] ?? '');
$seatCount      = old('seat_count', $row['seat_count'] ?? 4);
$minGuest       = old('min_guest', $row['min_guest'] ?? 1);
$maxGuest       = old('max_guest', $row['max_guest'] ?? 4);
$sortOrder      = old('sort_order', $row['sort_order'] ?? 0);
$status         = old('status', $row['status'] ?? 'available');
$isActive       = old('is_active', $row['is_active'] ?? 1);
$note           = old('note', $row['note'] ?? '');
?>

<div class="card card-soft">
    <div class="card-body">
        <form method="post">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.zone') ?></label>
                    <select name="zone_id" class="form-select" required>
                        <option value=""><?= lang('app.select_zone_placeholder') ?></option>
                        <?php foreach ($zones as $zone): ?>
                            <option value="<?= esc($zone['id']) ?>" <?= ((string) $selectedZoneId === (string) $zone['id'] ? 'selected' : '') ?>>
                                <?= esc(zone_label($zone, $locale)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.table_name') ?></label>
                    <input type="text" name="table_name" class="form-control" value="<?= esc($tableName) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.seat_count') ?></label>
                    <input type="number" min="1" name="seat_count" class="form-control" value="<?= esc($seatCount) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.min_guest') ?></label>
                    <input type="number" min="0" name="min_guest" class="form-control" value="<?= esc($minGuest) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.max_guest') ?></label>
                    <input type="number" min="0" name="max_guest" class="form-control" value="<?= esc($maxGuest) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.sort_order') ?></label>
                    <input type="number" name="sort_order" class="form-control" value="<?= esc($sortOrder) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.table_status') ?></label>
                    <select name="status" class="form-select">
                        <option value="available" <?= $status === 'available' ? 'selected' : '' ?>><?= lang('app.available') ?></option>
                        <option value="occupied" <?= $status === 'occupied' ? 'selected' : '' ?>><?= lang('app.occupied') ?></option>
                        <option value="reserved" <?= $status === 'reserved' ? 'selected' : '' ?>><?= lang('app.reserved') ?></option>
                        <option value="billing" <?= $status === 'billing' ? 'selected' : '' ?>><?= lang('app.billing') ?></option>
                        <option value="cleaning" <?= $status === 'cleaning' ? 'selected' : '' ?>><?= lang('app.cleaning') ?></option>
                        <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>><?= lang('app.disabled') ?></option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.active_status') ?></label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= ((string) $isActive === '1' ? 'selected' : '') ?>><?= lang('app.active') ?></option>
                        <option value="0" <?= ((string) $isActive === '0' ? 'selected' : '') ?>><?= lang('app.inactive') ?></option>
                    </select>
                </div>

                <div class="col-md-12">
                    <label class="form-label"><?= lang('app.note') ?></label>
                    <textarea name="note" class="form-control" rows="3"><?= esc($note) ?></textarea>
                </div>
            </div>

            <div class="mt-3">
                <button class="btn btn-success"><?= lang('app.save') ?></button>
                <a href="<?= site_url('tables') ?>" class="btn btn-secondary"><?= lang('app.back') ?></a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
