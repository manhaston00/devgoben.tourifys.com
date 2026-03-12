<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="row g-4">
            <div class="col-lg-8">
                <h3 class="mb-3"><?= lang('app.cashier_table') ?> <?= esc($order['table_name']) ?></h3>
                <div class="mb-3 text-muted">
                    <div><?= lang('app.bill_no') ?> <strong><?= esc($order['order_no']) ?></strong></div>
                    <div><?= lang('app.opened_by') ?> <?= esc($order['opened_by_name']) ?></div>
                    <div><?= lang('app.status_colon') ?> <?= esc($order['status']) ?></div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th><?= lang('app.menu') ?></th>
                                <th class="text-center"><?= lang('app.qty') ?></th>
                                <th class="text-end"><?= lang('app.unit_price') ?></th>
                                <th class="text-end"><?= lang('app.total') ?></th>
                                <th><?= lang('app.status') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($items ?? []) as $item): ?>
                                <tr>
                                    <td><?= esc($item['name_th'] ?? $item['product_name'] ?? '-') ?></td>
                                    <td class="text-center"><?= esc($item['qty'] ?? 0) ?></td>
                                    <td class="text-end"><?= number_format((float) ($item['price'] ?? 0), 2) ?></td>
                                    <td class="text-end"><?= number_format((float) ($item['total_price'] ?? 0), 2) ?></td>
                                    <td><?= esc($item['send_kitchen_status'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="border rounded-4 p-3 bg-light">
                    <h5 class="mb-3"><?= lang('app.summary') ?></h5>
                    <div class="d-flex justify-content-between mb-2"><span><?= lang('app.subtotal') ?></span><strong><?= number_format((float) ($order['subtotal'] ?? 0), 2) ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span><?= lang('app.discount') ?></span><strong><?= number_format((float) ($order['discount_amount'] ?? 0), 2) ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span><?= lang('app.service_charge') ?></span><strong><?= number_format((float) ($order['service_charge'] ?? 0), 2) ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span><?= lang('app.vat') ?></span><strong><?= number_format((float) ($order['vat_amount'] ?? 0), 2) ?></strong></div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3"><span><?= lang('app.net_total') ?></span><strong><?= number_format((float) ($order['grand_total'] ?? 0), 2) ?></strong></div>

                    <?php if (($order['status'] ?? '') !== 'paid'): ?>
                        <form method="post" id="payForm">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label"><?= lang('app.payment_method') ?></label>
                                <select name="payment_method" class="form-select">
                                    <option value="cash"><?= lang('app.cash') ?></option>
                                    <option value="transfer"><?= lang('app.transfer') ?></option>
                                    <option value="card"><?= lang('app.card') ?></option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?= lang('app.note') ?></label>
                                <textarea name="note" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success w-100"><?= lang('app.close_bill_and_pay') ?></button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success mb-0"><?= lang('app.bill_paid') ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.getElementById('payForm')?.addEventListener('submit', function (e) {
    if (!confirm(<?= json_encode(lang('app.confirm_pay_and_close_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>)) {
        e.preventDefault();
    }
});
</script>
<?= $this->endSection() ?>
