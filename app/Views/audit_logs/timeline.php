<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h4 class="mb-1"><?= esc($title ?? lang('app.bill_timeline')) ?></h4>
                <div class="text-muted small"><?= esc(lang('app.bill_timeline_order_prefix')) ?> #<?= (int) ($orderId ?? 0) ?></div>
            </div>
            <a href="<?= site_url('audit-logs?order_id=' . (int) ($orderId ?? 0)) ?>" class="btn btn-outline-secondary">
                <?= esc(lang('app.back')) ?>
            </a>
        </div>

        <div class="timeline-simple">
            <?php if (! empty($rows)): ?>
                <?php foreach ($rows as $row): ?>
                    <div class="border rounded-4 p-3 mb-3">
                        <div class="d-flex justify-content-between gap-3 flex-wrap">
                            <div>
                                <div class="fw-semibold"><?= esc($row['action_label'] ?? $row['action_key'] ?? '-') ?></div>
                                <div class="small text-muted"><?= esc($row['created_at'] ?? '-') ?></div>
                            </div>
                            <div class="text-md-end">
                                <div class="fw-semibold"><?= esc($row['actor_name'] ?? $row['full_name'] ?? '-') ?></div>
                                <div class="small text-muted"><?= esc($row['username'] ?? '-') ?></div>
                            </div>
                        </div>

                        <?php $meta = $row['meta_array'] ?? []; ?>
                        <?php if ($meta !== []): ?>
                            <div class="mt-2 small">
                                <?php foreach ($meta as $metaKey => $metaValue): ?>
                                    <div>
                                        <span class="text-muted"><?= esc((string) $metaKey) ?>:</span>
                                        <?= esc(is_scalar($metaValue) ? (string) $metaValue : json_encode($metaValue, JSON_UNESCAPED_UNICODE)) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-muted"><?= esc(lang('app.no_data_found')) ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
