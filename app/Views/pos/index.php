<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<style>
    .pos-header-title {
        font-size: 1.35rem;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .pos-header-subtitle {
        font-size: .92rem;
        color: #6b7280;
    }

    .pos-table-card {
		display: block;
		border-radius: 16px;
		padding: 16px;
		text-decoration: none;
		border: 1px solid var(--app-border);
		box-shadow: var(--app-shadow-sm);
		transition: all .18s ease;
		min-height: 138px;
		color: var(--app-text);
		position: relative;
		overflow: hidden;
	}

    .pos-table-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(0,0,0,.08);
        color: #111827;
    }

    .pos-table-card.disabled-link,
    .pos-table-card.disabled-link:hover {
        cursor: not-allowed;
        transform: none;
        text-decoration: none;
        color: #111827;
        box-shadow: 0 6px 18px rgba(0,0,0,.05);
    }

    .pos-table-card .table-name {
        font-size: 1.02rem;
        font-weight: 700;
        margin-bottom: 4px;
        line-height: 1.25;
        padding-right: 70px;
    }

    .pos-table-card .table-zone {
        font-size: .82rem;
        color: #4b5563;
        margin-bottom: 8px;
    }

    .pos-table-card .table-status {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: .72rem;
        font-weight: 700;
        background: rgba(255,255,255,.78);
        backdrop-filter: blur(2px);
        margin-bottom: 8px;
    }

    .table-badge-top {
        position: absolute;
        top: 12px;
        right: 12px;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: .68rem;
        font-weight: 700;
        background: rgba(255,255,255,.94);
        color: #111827;
        border: 1px solid rgba(255,255,255,.92);
    }

    .table-extra-note {
        margin-top: 8px;
        font-size: .78rem;
        color: #374151;
        line-height: 1.45;
    }

    .table-meta-list {
        margin-top: 8px;
        display: grid;
        gap: 3px;
    }

    .table-meta-item {
        font-size: .76rem;
        line-height: 1.35;
        color: #374151;
    }

    .table-meta-item strong {
        color: #111827;
    }

    .table-bg-available {
        background: linear-gradient(135deg,#dcfce7,#86efac);
        border-color:#4ade80;
    }

    .table-bg-occupied {
        background: linear-gradient(135deg,#fee2e2,#fca5a5);
        border-color:#ef4444;
    }

    .table-bg-reserved {
        background: linear-gradient(135deg,#fef3c7,#fde68a);
        border-color:#f59e0b;
    }

    .table-bg-cleaning {
        background: linear-gradient(135deg,#dbeafe,#93c5fd);
        border-color:#3b82f6;
    }

    .table-bg-disabled {
        background: linear-gradient(135deg,#e5e7eb,#d1d5db);
        border-color:#9ca3af;
    }

    .table-bg-default {
        background: linear-gradient(135deg,#f8fafc,#e5e7eb);
        border-color:#cbd5e1;
    }

    .table-bg-locked {
        background: linear-gradient(135deg,#fef3c7,#fcd34d);
        border-color:#f59e0b;
    }

    .table-action-text {
        margin-top: 8px;
        font-size: .76rem;
        font-weight: 700;
        color: #111827;
    }

    .table-action-text.text-muted-soft {
        color: #6b7280;
    }

    @media (max-width:768px){
        .pos-table-card{
            min-height:120px;
            padding:12px;
        }

        .pos-table-card .table-name{
            font-size:.9rem;
        }

        .table-badge-top{
            font-size:.6rem;
            padding:3px 6px;
        }

        .table-action-text,
        .table-meta-item{
            font-size:.7rem;
        }
    }
</style>

<div class="card card-soft">
    <div class="card-body">
        <?php
			echo view('partials/app_page_header', [
				'title' => lang('app.pos_title'),
				'desc'  => lang('app.select_table_to_start_order'),
			]);
		?>

        <div class="row g-2 g-md-3">
            <?php foreach ($tables as $table): ?>
                <?php
                    $status              = strtolower(trim((string) ($table['status'] ?? 'available')));
                    $isActive            = (int) ($table['is_active'] ?? 0) === 1;
                    $isReservationLocked = !empty($table['reservation_locked']);
                    $hasOpenOrder        = !empty($table['has_open_order']);

                    $reservationData    = $table['reservation_data'] ?? null;
                    $minutesLeft        = isset($table['reservation_minutes_left']) && $table['reservation_minutes_left'] !== null
                        ? (int) $table['reservation_minutes_left']
                        : null;

                    $reservationDate = '';
                    $reservationTime = '';
                    $customerName    = '';

                    if (is_array($reservationData)) {
                        $reservationDate = trim((string) ($reservationData['reservation_date'] ?? ''));
                        $reservationTime = trim((string) ($reservationData['reservation_time'] ?? ''));
                        $customerName    = trim((string) ($reservationData['customer_name'] ?? ''));
                    }

                    $bgClass    = 'table-bg-default';
                    $statusText = lang('app.unknown_status');
                    $canOpen    = false;
                    $topBadge   = '';
                    $actionText = '';

                    if (!$isActive || $status === 'disabled') {
                        $bgClass    = 'table-bg-disabled';
                        $statusText = lang('app.disabled');
                        $canOpen    = false;
                        $topBadge   = lang('app.disabled');
                        $actionText = lang('app.table_not_available');
                    } elseif ($status === 'cleaning') {
                        $bgClass    = 'table-bg-cleaning';
                        $statusText = lang('app.cleaning');
                        $canOpen    = false;
                        $topBadge   = lang('app.cleaning');
                        $actionText = lang('app.table_not_ready');
                    } elseif ($isReservationLocked) {
                        $bgClass    = 'table-bg-locked';
                        $statusText = lang('app.locked');
                        $canOpen    = true;
                        $topBadge   = lang('app.reservation_soon');
                        $actionText = $hasOpenOrder ? lang('app.click_to_open_existing_order') : lang('app.click_to_enter_table');
                    } elseif ($status === 'occupied') {
                        $bgClass    = 'table-bg-occupied';
                        $statusText = lang('app.occupied');
                        $canOpen    = true;
                        $topBadge   = $hasOpenOrder ? lang('app.open_order_exists') : lang('app.in_use');
                        $actionText = $hasOpenOrder ? lang('app.click_to_open_existing_order') : lang('app.click_to_enter_table');
                    } elseif ($status === 'reserved') {
                        $bgClass    = 'table-bg-reserved';
                        $statusText = lang('app.reserved');
                        $canOpen    = true;
                        $topBadge   = lang('app.has_reservation');
                        $actionText = $hasOpenOrder ? lang('app.click_to_open_existing_order') : lang('app.click_to_enter_table');
                    } elseif ($status === 'available') {
                        $bgClass    = 'table-bg-available';
                        $statusText = lang('app.available');
                        $canOpen    = true;
                        $topBadge   = lang('app.ready_to_use');
                        $actionText = lang('app.click_to_open_table');
                    } else {
                        $bgClass    = 'table-bg-default';
                        $statusText = ucfirst($status);
                        $canOpen    = true;
                        $topBadge   = lang('app.available_for_use');
                        $actionText = $hasOpenOrder ? lang('app.click_to_open_existing_order') : lang('app.click_to_enter_table');
                    }

                    $cardClass = 'pos-table-card ' . $bgClass . ($canOpen ? '' : ' disabled-link');
                ?>

                <div class="col-6 col-lg-4 col-xl-3">
                    <?php if ($canOpen): ?>
                        <a href="<?= site_url('pos/table/' . $table['id']) ?>" class="<?= esc($cardClass) ?>">
                            <div class="table-badge-top"><?= esc($topBadge) ?></div>

                            <div class="table-name"><?= esc($table['table_name'] ?? '-') ?></div>

                            <?php $zoneLabel = lfield($table, 'zone_name', (string) ($table['zone_name'] ?? '')); ?>
                            <?php if ($zoneLabel !== ''): ?>
                                <div class="table-zone"><?= lang('app.zone') ?>: <?= esc($zoneLabel) ?></div>
                            <?php endif; ?>

                            <span class="table-status"><?= lang('app.status') ?>: <?= esc($statusText) ?></span>

                            <div class="table-action-text"><?= esc($actionText) ?></div>

                            <?php if ($isReservationLocked): ?>
                                <div class="table-meta-list">
                                    <?php if ($reservationDate !== ''): ?>
                                        <div class="table-meta-item">
                                            <strong><?= lang('app.date') ?>:</strong> <?= esc($reservationDate) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($reservationTime !== ''): ?>
                                        <div class="table-meta-item">
                                            <strong><?= lang('app.time') ?>:</strong> <?= esc($reservationTime) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($customerName !== ''): ?>
                                        <div class="table-meta-item">
                                            <strong><?= lang('app.customer') ?>:</strong> <?= esc($customerName) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($minutesLeft !== null && $minutesLeft > 0): ?>
                                        <div class="table-meta-item">
                                            <strong><?= lang('app.time_left') ?>:</strong> <?= lang('app.remaining_minutes', ['minutes' => $minutesLeft]) ?>
                                        </div>
                                    <?php elseif ($minutesLeft !== null && $minutesLeft <= 0 && $reservationTime !== ''): ?>
                                        <div class="table-meta-item">
                                            <strong><?= lang('app.time_left') ?>:</strong> <?= lang('app.reservation_time_reached') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <div class="<?= esc($cardClass) ?>">
                            <div class="table-badge-top"><?= esc($topBadge) ?></div>

                            <div class="table-name"><?= esc($table['table_name'] ?? '-') ?></div>

                            <?php $zoneLabel = lfield($table, 'zone_name', (string) ($table['zone_name'] ?? '')); ?>
                            <?php if ($zoneLabel !== ''): ?>
                                <div class="table-zone"><?= lang('app.zone') ?>: <?= esc($zoneLabel) ?></div>
                            <?php endif; ?>

                            <span class="table-status"><?= lang('app.status') ?>: <?= esc($statusText) ?></span>

                            <div class="table-action-text text-muted-soft"><?= esc($actionText) ?></div>

                            <?php if ($isReservationLocked): ?>
                                <div class="table-meta-list">
                                    <?php if ($reservationDate !== ''): ?>
                                        <div class="table-meta-item">
                                            <strong><?= lang('app.date') ?>:</strong> <?= esc($reservationDate) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($reservationTime !== ''): ?>
                                        <div class="table-meta-item">
                                            <strong><?= lang('app.time') ?>:</strong> <?= esc($reservationTime) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($customerName !== ''): ?>
                                        <div class="table-meta-item">
                                            <strong><?= lang('app.customer') ?>:</strong> <?= esc($customerName) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($minutesLeft !== null && $minutesLeft > 0): ?>
                                        <div class="table-meta-item">
                                            <strong><?= lang('app.time_left') ?>:</strong> <?= lang('app.remaining_minutes', ['minutes' => $minutesLeft]) ?>
                                        </div>
                                    <?php elseif ($minutesLeft !== null && $minutesLeft <= 0 && $reservationTime !== ''): ?>
                                        <div class="table-meta-item">
                                            <strong><?= lang('app.time_left') ?>:</strong> <?= lang('app.reservation_time_reached') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>