<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="row g-3">
    <div class="col-md-3">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="text-muted"><?= lang('app.users') ?></h6>
                <div class="fs-3 fw-bold"><?= esc($usersCount ?? 0) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="text-muted"><?= lang('app.categories') ?></h6>
                <div class="fs-3 fw-bold"><?= esc($categoriesCount ?? 0) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="text-muted"><?= lang('app.products') ?></h6>
                <div class="fs-3 fw-bold"><?= esc($productsCount ?? 0) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-soft">
            <div class="card-body">
                <h6 class="text-muted"><?= lang('app.zones_tables') ?></h6>
                <div class="fs-3 fw-bold"><?= esc($zonesCount ?? 0) ?> / <?= esc($tablesCount ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>