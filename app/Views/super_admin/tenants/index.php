<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-1"><?= esc($title ?? lang('app.tenants')) ?></h4>
                <div class="text-muted small"><?= esc(lang('app.manage_saas_tenants')) ?></div>
            </div>

            <?php if (function_exists('can') && can('tenants.create')): ?>
                <a href="<?= esc($createUrl ?? site_url('super-admin/tenants/create')) ?>" class="btn btn-primary">
                    <?= esc(lang('app.add_tenant')) ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text"
                       id="tenantsTableSearch"
                       class="form-control"
                       placeholder="<?= esc(lang('app.search_tenant_placeholder')) ?>">
            </div>
        </div>

        <div class="table-responsive">
            <table id="tenantsTable" class="table table-bordered align-middle w-100">
                <thead>
                    <tr>
                        <th width="60">#</th>
                        <th><?= esc(lang('app.tenant_code')) ?></th>
                        <th><?= esc(lang('app.tenant_name')) ?></th>
                        <th><?= esc(lang('app.owner_name')) ?></th>
                        <th><?= esc(lang('app.phone')) ?></th>
                        <th><?= esc(lang('app.email')) ?></th>
                        <th><?= esc(lang('app.subscription_plan')) ?></th>
                        <th><?= esc(lang('app.subscription_status')) ?></th>
                        <th class="text-center" width="120"><?= esc(lang('app.status')) ?></th>
                        <th class="text-center" width="220"><?= esc(lang('app.actions')) ?></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function () {
    var table = $('#tenantsTable').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        scrollX: true,
        pageLength: 10,
        order: [[2, 'asc']],
        ajax: {
            url: <?= json_encode($datatableUrl ?? site_url('super-admin/tenants/data')) ?>,
            type: 'GET'
        },
        language: <?= json_encode(dt_lang(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        columns: [
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'tenant_code' },
            { data: 'tenant_name' },
            { data: 'owner_name' },
            { data: 'phone' },
            { data: 'email' },
            { data: 'plan_name' },
            { data: 'subscription_status', className: 'text-center' },
            { data: 'tenant_status', className: 'text-center', orderable: false, searchable: false },
            { data: 'actions', className: 'text-center', orderable: false, searchable: false }
        ]
    });

    $('#tenantsTableSearch').on('keyup change', function () {
        table.search(this.value).draw();
    });
});
</script>
<?= $this->endSection() ?>