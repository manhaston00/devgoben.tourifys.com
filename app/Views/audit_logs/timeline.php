<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<?php
    $tr = static function (string $key, string $th, string $en, array $args = []) {
        $text = lang($key, $args);
        if ($text === $key) {
            $locale = service('request')->getLocale();
            $text = $locale === 'th' ? $th : $en;
            foreach ($args as $argKey => $argValue) {
                $text = str_replace('{' . $argKey . '}', (string) $argValue, $text);
            }
        }
        return $text;
    };

    $labelMap = [
        'ticket_no' => $tr('app.ticket_no', 'เลขตั๋ว', 'Ticket no'),
        'batch_no' => $tr('app.batch_no', 'รอบส่งครัว', 'Batch no'),
        'item_count' => $tr('app.item_count', 'จำนวนรายการ', 'Item count'),
        'product_name' => $tr('app.product_name', 'สินค้า', 'Product'),
        'qty' => $tr('app.qty', 'จำนวน', 'Qty'),
        'table_name' => $tr('app.table', 'โต๊ะ', 'Table'),
        'station_name' => $tr('app.kitchen_station', 'สถานีครัว', 'Kitchen station'),
        'served_at' => $tr('app.served_at', 'เวลาเสิร์ฟ', 'Served at'),
        'source_screen' => $tr('app.source', 'หน้าที่ทำรายการ', 'Source'),
        'from_status' => $tr('app.from_status', 'จากสถานะ', 'From status'),
        'to_status' => $tr('app.to_status', 'เป็นสถานะ', 'To status'),
    ];

    $statusMap = [
        'pending' => $tr('app.pending', 'รอดำเนินการ', 'Pending'),
        'sent' => $tr('app.sent_to_kitchen', 'ส่งครัวแล้ว', 'Sent to kitchen'),
        'preparing' => $tr('app.preparing', 'กำลังทำ', 'Preparing'),
        'ready' => $tr('app.ready', 'พร้อมเสิร์ฟ', 'Ready'),
        'served' => $tr('app.served', 'เสิร์ฟแล้ว', 'Served'),
        'cancel_request' => $tr('app.cancel_request', 'รอยืนยันยกเลิก', 'Cancel request'),
        'cancelled' => $tr('app.cancelled', 'ยกเลิกแล้ว', 'Cancelled'),
    ];

    $sourceMap = [
        'kitchen_monitor' => $tr('app.kitchen_monitor', 'จอครัว', 'Kitchen monitor'),
        'pos_table' => $tr('app.pos_table', 'หน้าโต๊ะ', 'POS table'),
        'cashier' => $tr('app.cashier', 'แคชเชียร์', 'Cashier'),
    ];

    $formatValue = static function ($key, $value) use ($statusMap, $sourceMap) {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        $value = (string) $value;
        if (($key === 'from_status' || $key === 'to_status') && isset($statusMap[$value])) {
            return $statusMap[$value];
        }
        if ($key === 'source_screen' && isset($sourceMap[$value])) {
            return $sourceMap[$value];
        }
        return $value === '' ? '-' : $value;
    };
?>
<style>
.timeline-clean { display:flex; flex-direction:column; gap:12px; }
.timeline-clean-card { border:1px solid #e2e8f0; border-radius:18px; padding:14px; background:#fff; }
.timeline-clean-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:10px; }
.timeline-clean-title { font-weight:800; color:#0f172a; }
.timeline-clean-time { font-size:.8rem; color:#64748b; }
.timeline-clean-actor { text-align:right; font-size:.82rem; }
.timeline-clean-lead { font-size:.95rem; font-weight:700; color:#0f172a; margin-bottom:8px; }
.timeline-clean-chips { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px; }
.timeline-chip { display:inline-flex; align-items:center; padding:4px 9px; border-radius:999px; border:1px solid #dbe4ee; background:#f8fafc; font-size:.76rem; font-weight:700; color:#334155; }
.timeline-chip--served { background:#ecfeff; border-color:#99f6e4; color:#0f766e; }
.timeline-chip--kitchen { background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
.timeline-chip--note { background:#fff7ed; border-color:#fed7aa; color:#c2410c; }
.timeline-clean-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px 12px; }
.timeline-clean-meta { min-width:0; }
.timeline-clean-label { display:block; font-size:.72rem; font-weight:700; color:#64748b; margin-bottom:2px; text-transform:uppercase; }
.timeline-clean-value { display:block; font-size:.84rem; color:#0f172a; word-break:break-word; }
@media (max-width: 767.98px) {
  .timeline-clean-card { padding:12px; border-radius:16px; }
  .timeline-clean-head { flex-direction:column; gap:6px; }
  .timeline-clean-actor { text-align:left; }
  .timeline-clean-grid { grid-template-columns:1fr; gap:8px; }
}
</style>
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

        <div class="timeline-clean">
            <?php if (! empty($rows)): ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                        $meta = is_array($row['meta_array'] ?? null) ? $row['meta_array'] : [];
                        $actionKey = (string) ($row['action_key'] ?? '');
                        $isServed = $actionKey === 'pos.item_served';
                        $isKitchen = $actionKey === 'pos.send_kitchen';
                        $lead = (string) ($row['action_label'] ?? $row['action_key'] ?? '-');
                        if ($isServed && ! empty($meta['product_name'])) {
                            $lead = trim((string) $meta['product_name']) . ' × ' . (int) ($meta['qty'] ?? 1);
                        } elseif ($isKitchen && ! empty($meta['item_count'])) {
                            $lead = $tr('app.sent_items_summary', 'ส่งรายการเข้าครัว {count} รายการ', 'Sent {count} items to kitchen', ['count' => (int) $meta['item_count']]);
                        }

                        $displayKeys = ['product_name','qty','item_count','ticket_no','batch_no','table_name','station_name','served_at','source_screen','from_status','to_status'];
                    ?>
                    <div class="timeline-clean-card">
                        <div class="timeline-clean-head">
                            <div>
                                <div class="timeline-clean-title"><?= esc($row['action_label'] ?? $row['action_key'] ?? '-') ?></div>
                                <div class="timeline-clean-time"><?= esc($row['created_at'] ?? '-') ?></div>
                            </div>
                            <div class="timeline-clean-actor">
                                <div class="fw-semibold"><?= esc($row['actor_name'] ?? $row['full_name'] ?? '-') ?></div>
                                <div class="text-muted small"><?= esc($row['username'] ?? '-') ?></div>
                            </div>
                        </div>

                        <div class="timeline-clean-lead"><?= esc($lead) ?></div>

                        <div class="timeline-clean-chips">
                            <?php if ($isServed): ?>
                                <span class="timeline-chip timeline-chip--served"><?= esc($tr('app.served', 'เสิร์ฟแล้ว', 'Served')) ?></span>
                            <?php endif; ?>
                            <?php if ($isKitchen): ?>
                                <span class="timeline-chip timeline-chip--kitchen"><?= esc($tr('app.sent_to_kitchen', 'ส่งครัวแล้ว', 'Sent to kitchen')) ?></span>
                            <?php endif; ?>
                            <?php if (! empty($meta['ticket_no'])): ?>
                                <span class="timeline-chip"><?= esc($tr('app.ticket_short', 'ตั๋ว {ticket}', 'Ticket {ticket}', ['ticket' => (string) $meta['ticket_no']])) ?></span>
                            <?php endif; ?>
                            <?php if (! empty($meta['batch_no'])): ?>
                                <span class="timeline-chip timeline-chip--note"><?= esc($tr('app.batch_short', 'รอบ {batch}', 'Batch {batch}', ['batch' => (string) $meta['batch_no']])) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($meta !== []): ?>
                            <div class="timeline-clean-grid">
                                <?php foreach ($displayKeys as $metaKey): ?>
                                    <?php if (! array_key_exists($metaKey, $meta) || $meta[$metaKey] === null || $meta[$metaKey] === '') continue; ?>
                                    <div class="timeline-clean-meta">
                                        <span class="timeline-clean-label"><?= esc($labelMap[$metaKey] ?? $metaKey) ?></span>
                                        <span class="timeline-clean-value"><?= esc($formatValue($metaKey, $meta[$metaKey])) ?></span>
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
