<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <form method="post">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.station_name_th') ?></label>
                    <input type="text" name="station_name_th" class="form-control" value="<?= old('station_name_th', $row['station_name_th'] ?? $row['station_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.station_name_en') ?></label>
                    <input type="text" name="station_name_en" class="form-control" value="<?= old('station_name_en', $row['station_name_en'] ?? $row['station_name'] ?? '') ?>">
                </div>
                <input type="hidden" name="station_name" value="<?= esc(old('station_name', $row['station_name'] ?? '')) ?>">

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.color_tag') ?></label>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input type="color" name="color_picker" id="color_picker" class="form-control form-control-color" value="<?= esc($row['color_tag'] ?? '#dc3545') ?>" title="<?= lang('app.choose_color') ?>">
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="color_tag" id="color_tag" class="form-control" value="<?= esc($row['color_tag'] ?? '#dc3545') ?>" placeholder="#dc3545">
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.status') ?></label>
                    <select name="status" class="form-select">
                        <option value="1" <?= (($row['status'] ?? 1) == 1 ? 'selected' : '') ?>><?= lang('app.active') ?></option>
                        <option value="0" <?= (($row['status'] ?? 1) == 0 ? 'selected' : '') ?>><?= lang('app.inactive') ?></option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-success"><?= lang('app.save') ?></button>
                <a href="<?= site_url('kitchen-stations') ?>" class="btn btn-secondary"><?= lang('app.back') ?></a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const colorPicker = document.getElementById('color_picker');
    const colorTag = document.getElementById('color_tag');
    if (colorPicker && colorTag) {
        colorPicker.addEventListener('input', function () { colorTag.value = this.value; });
        colorTag.addEventListener('input', function () {
            const val = this.value.trim();
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) colorPicker.value = val;
        });
    }
</script>
<?= $this->endSection() ?>
