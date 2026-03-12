<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<style>
    .reservation-wrap {
        width: 100%;
    }

    .reservation-wrap .card {
        width: 100%;
    }

    .section-title {
		font-size: 1rem;
		font-weight: 800;
		margin-bottom: 14px;
		color: var(--app-text);
	}

    .soft-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 14px;
    }

    .status-box {
        min-height: 46px;
        display: flex;
        align-items: center;
        padding: 0 14px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        color: #374151;
        font-weight: 600;
    }

    .hint-text {
        font-size: 12px;
        color: #6b7280;
        margin-top: 6px;
    }

    @media (max-width: 991.98px) {
        .reservation-wrap {
            max-width: 100%;
        }
    }

    .reservation-table-card {
        border: 1px solid #dee2e6;
        border-radius: 14px;
        padding: 12px;
        background: #fff;
        cursor: pointer;
        transition: all .2s ease;
        height: 100%;
        display: block;
    }

    .reservation-table-card:hover {
        box-shadow: 0 4px 14px rgba(0,0,0,.08);
        transform: translateY(-1px);
    }

    .reservation-table-card.is-available {
        border-color: #198754;
        background: #f0fff5;
    }

    .reservation-table-card.is-occupied {
        border-color: #dc3545;
        background: #fff5f5;
        cursor: not-allowed;
        opacity: .9;
    }

    .reservation-table-card.is-disabled {
        border-color: #adb5bd;
        background: #f8f9fa;
        opacity: .7;
        cursor: not-allowed;
    }

    .reservation-table-card.active {
        border-width: 2px;
        border-color: #0d6efd;
        background: #eef4ff;
        box-shadow: 0 0 0 1px rgba(13,110,253,.15);
    }

    .reservation-table-name {
        font-weight: 700;
        font-size: 15px;
        margin-bottom: 4px;
    }

    .reservation-table-meta {
        font-size: 12px;
        color: #6c757d;
        line-height: 1.45;
    }

    .reservation-status-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        border-radius: 999px;
        padding: 4px 8px;
        margin-top: 6px;
    }

    .badge-available {
        background: #d1e7dd;
        color: #0f5132;
    }

    .badge-occupied {
        background: #f8d7da;
        color: #842029;
    }

    .badge-disabled {
        background: #e9ecef;
        color: #495057;
    }

    .badge-selected {
        background: #cfe2ff;
        color: #084298;
    }

    .table-grid-loading {
        min-height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        font-weight: 600;
    }
	.reservation-table-card.is-open-order {
		border-color: #fd7e14;
		background: #fff4e6;
	}

	.badge-open-order {
		background: #ffe5d0;
		color: #9a3412;
	}

	.reservation-table-note {
		font-size: 12px;
		color: #6c757d;
		margin-top: 6px;
		line-height: 1.4;
	}
</style>

<div class="reservation-wrap">
    <div class="card card-soft">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div>
                    <h4 class="mb-1"><?= isset($reservation) ? lang('app.edit_reservation') : lang('app.create_reservation') ?></h4>
                    <div class="text-muted small"><?= lang('app.fill_customer_date_time') ?></div>
                </div>
                <a href="<?= site_url('reservations') ?>" class="btn btn-secondary"><?= lang('app.back') ?></a>
            </div>

            <?php
            $zoneMap = [];
            if (!empty($tables)) {
                foreach ($tables as $t) {
                    $zid   = (int) ($t['zone_id'] ?? 0);
                    $zname = trim((string) ($t['zone_name'] ?? ''));
                    if ($zid > 0 && $zname !== '') {
                        $zoneMap[$zid] = $zname;
                    }
                }
            }

            $oldTableIds = old('table_ids');

			if (!is_array($oldTableIds) || empty($oldTableIds)) {
				$oldTableIds = $tableIds ?? [];
			}

			if (!is_array($oldTableIds)) {
				$oldTableIds = [];
			}

			$oldTableIds = array_map('intval', $oldTableIds);
            ?>

            <form method="post" action="<?= isset($reservation) ? site_url('reservations/update/' . (int) $reservation['id']) : site_url('reservations/store') ?>">
                <?= csrf_field() ?>

                <div class="app-section mb-4">
				<div class="section-title"><?= lang('app.customer_information') ?></div>
				<div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label"><?= lang('app.customer_name') ?> <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="customer_name"
                            class="form-control"
                            value="<?= old('customer_name', $reservation['customer_name'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label"><?= lang('app.customer_phone') ?></label>
                        <input
                            type="text"
                            name="customer_phone"
                            class="form-control"
                            value="<?= old('customer_phone', $reservation['customer_phone'] ?? '') ?>"
                        >
                    </div>
                </div>
                </div>

                <div class="section-title"><?= lang('app.reservation_information') ?></div>
                <div class="row g-3 mb-4">
                    <div class="col-lg-3 col-md-6">
						<label class="form-label"><?= lang('app.reservation_date') ?> <span class="text-danger">*</span></label>
						<input
							type="date"
							name="reservation_date"
							class="form-control"
							value="<?= esc(isset($reservation) ? old('reservation_date', $reservation['reservation_date'] ?? date('Y-m-d')) : date('Y-m-d')) ?>"
							required
						>
					</div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label"><?= lang('app.reservation_time') ?> <span class="text-danger">*</span></label>
                        <input
                            type="time"
                            name="reservation_time"
                            class="form-control"
                            value="<?= old('reservation_time', $reservation['reservation_time'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label"><?= lang('app.guest_count') ?> <span class="text-danger">*</span></label>
                        <input
                            type="number"
                            name="guest_count"
                            class="form-control"
                            min="1"
                            value="<?= old('guest_count', $reservation['guest_count'] ?? 1) ?>"
                            required
                        >
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label"><?= lang('app.duration_minutes') ?></label>
                        <input
                            type="number"
                            name="duration_minutes"
                            class="form-control"
                            min="30"
                            step="30"
                            value="<?= old('duration_minutes', $reservation['duration_minutes'] ?? 60) ?>"
                        >
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label class="form-label"><?= lang('app.status') ?></label>
                        <div class="status-box">confirmed</div>
                        <div class="hint-text"><?= lang('app.system_auto_confirmed') ?></div>
                    </div>

                    <div class="col-lg-6 col-md-6">
                        <label class="form-label"><?= lang('app.zone') ?></label>
                        <select name="zone_id" id="zoneFilter" class="form-select">
                            <option value="">-- <?= lang('app.all_zones') ?> --</option>
                            <?php foreach ($zoneMap as $zid => $zname): ?>
                                <option value="<?= $zid ?>" <?= (string) old('zone_id', $reservation['zone_id'] ?? '') === (string) $zid ? 'selected' : '' ?>>
                                    <?= esc($zname) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-6 col-md-6">
                        <label class="form-label"><?= lang('app.source') ?></label>
                        <select name="source" class="form-select">
                            <option value="phone" <?= old('source', $reservation['source'] ?? 'phone') === 'phone' ? 'selected' : '' ?>><?= lang('app.phone') ?></option>
                            <option value="walk_in" <?= old('source', $reservation['source'] ?? '') === 'walk_in' ? 'selected' : '' ?>>Walk-in</option>
                            <option value="line" <?= old('source', $reservation['source'] ?? '') === 'line' ? 'selected' : '' ?>>LINE</option>
                            <option value="facebook" <?= old('source', $reservation['source'] ?? '') === 'facebook' ? 'selected' : '' ?>>Facebook</option>
                            <option value="other" <?= old('source', $reservation['source'] ?? '') === 'other' ? 'selected' : '' ?>><?= lang('app.other') ?></option>
                        </select>
                    </div>
                </div>

                <div class="app-section mb-4">
				<div class="section-title"><?= lang('app.select_table') ?></div>
				<div class="soft-box">
                    <div id="table_grid" class="row g-3">
                        <div class="col-12">
                            <div class="table-grid-loading"><?= lang('app.loading_tables') ?></div>
                        </div>
                    </div>
                    <div class="hint-text"><?= lang('app.available_hint') ?></div>
                    <div class="hint-text"><?= lang('app.multiple_tables_hint') ?></div>
                </div>
                </div>

                <div class="section-title"><?= lang('app.notes') ?></div>
                <div class="row g-3 mb-4">
                    <div class="col-lg-6">
                        <label class="form-label"><?= lang('app.special_note') ?></label>
                        <textarea
                            name="special_note"
                            class="form-control"
                            rows="4"
                        ><?= old('special_note', $reservation['special_note'] ?? '') ?></textarea>
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label"><?= lang('app.internal_note') ?></label>
                        <textarea
                            name="internal_note"
                            class="form-control"
                            rows="4"
                        ><?= old('internal_note', $reservation['internal_note'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end flex-wrap mt-4">
					<a href="<?= site_url('reservations') ?>" class="btn btn-light border"><?= lang('app.cancel') ?></a>
					<button type="submit" class="btn btn-primary"><?= lang('app.save') ?></button>
				</div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const reservationDateEl = document.querySelector('[name="reservation_date"]');
    const reservationTimeEl = document.querySelector('[name="reservation_time"]');
    const guestCountEl      = document.querySelector('[name="guest_count"]');
    const durationEl        = document.querySelector('[name="duration_minutes"]');
    const zoneEl            = document.querySelector('[name="zone_id"]');
    const tableGrid         = document.getElementById('table_grid');

    const reservationId = <?= isset($reservation['id']) ? (int) $reservation['id'] : 0 ?>;
    const selectedFromOld = <?= json_encode(array_values($oldTableIds)) ?>;

    let selectedTableIds = [...selectedFromOld];

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function syncSelectedTableIds() {
        const checked = document.querySelectorAll('.table-check:checked');
        selectedTableIds = Array.from(checked).map(el => parseInt(el.value, 10)).filter(Boolean);
    }

    function showLoading() {
        if (!tableGrid) return;
        tableGrid.innerHTML = `
            <div class="col-12">
                <div class="table-grid-loading"><?= lang('app.loading_tables') ?></div>
            </div>
        `;
    }

    async function loadAvailableTables() {
        const reservation_date = reservationDateEl ? reservationDateEl.value : '';
        const reservation_time = reservationTimeEl ? reservationTimeEl.value : '';
        const guest_count      = guestCountEl ? guestCountEl.value : '';
        const duration_minutes = durationEl ? durationEl.value : '';
        const zone_id          = zoneEl ? zoneEl.value : '';

        if (!tableGrid) return;

        if (!reservation_date || !reservation_time) {
            tableGrid.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning mb-0"><?= lang('app.select_date_time_first') ?></div>
                </div>
            `;
            return;
        }

        showLoading();

        const url = new URL('<?= site_url('reservations/available-tables') ?>', window.location.origin);
        url.searchParams.set('reservation_date', reservation_date);
        url.searchParams.set('reservation_time', reservation_time);
        url.searchParams.set('guest_count', guest_count || '');
        url.searchParams.set('duration_minutes', duration_minutes || '');
        url.searchParams.set('zone_id', zone_id || '');

        if (reservationId > 0) {
            url.searchParams.set('reservation_id', reservationId);
        }

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (!data || data.status !== 'success' || !Array.isArray(data.tables)) {
                tableGrid.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger mb-0"><?= lang('app.table_loading_failed') ?></div>
                    </div>
                `;
                return;
            }

            renderTables(data.tables);
        } catch (error) {
            console.error('Load available tables failed:', error);
            tableGrid.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger mb-0"><?= lang('app.table_loading_error') ?></div>
                </div>
            `;
        }
    }

    function renderTables(tables) {
		if (!tableGrid) return;

		let html = '';

		if (!tables.length) {
			html = '<div class="col-12"><div class="alert alert-warning mb-0"><?= lang('app.no_matching_tables') ?></div></div>';
			tableGrid.innerHTML = html;
			return;
		}

		tables.forEach(function (t) {
			const tableId = parseInt(t.id || 0, 10);
			const checked = selectedTableIds.includes(tableId);
			const isAvail = !!parseInt(t.is_available ?? 0, 10);
			const busyReason = String(t.busy_reason || '');
			const busyUntil = t.busy_until || '';

			let cardClass = '';
			let badgeClass = '';
			let badgeText = '';
			let disabled = '';
			let noteText = '';

			if (checked) {
				cardClass = 'active';
				badgeClass = 'badge-selected';
				badgeText = '<?= lang('app.selected') ?>';
			} else if (isAvail) {
				cardClass = 'is-available';
				badgeClass = 'badge-available';
				badgeText = '<?= lang('app.available') ?>';
			} else if (busyReason === 'open_order') {
				cardClass = 'is-open-order';
				badgeClass = 'badge-open-order';
				badgeText = '<?= lang('app.open_order') ?>';
				disabled = 'disabled';

				if (busyUntil) {
					noteText = '<?= lang('app.occupied_note') ?><br><?= lang('app.busy_until') ?> ' + formatBusyUntil(busyUntil);
				} else {
					noteText = '<?= lang('app.occupied_note') ?>';
				}
			} else {
				cardClass = 'is-occupied';
				badgeClass = 'badge-occupied';
				badgeText = '<?= lang('app.booked') ?>';
				disabled = 'disabled';

				if (busyUntil) {
					noteText = '<?= lang('app.reserved_until') ?> ' + formatBusyUntil(busyUntil);
				} else {
					noteText = '<?= lang('app.reserved_in_period') ?>';
				}
			}

			html += `
				<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
					<label class="reservation-table-card ${cardClass}" for="table_${tableId}">
						<div class="d-flex justify-content-between align-items-start gap-2">
							<div class="flex-grow-1">
								<div class="reservation-table-name">${escapeHtml(t.table_name || (''+ tableId + '' + tableId))}</div>
								<div class="reservation-table-meta">
									<?= lang('app.zone') ?>: ${escapeHtml(t.zone_name || '<?= lang('app.unspecified') ?>')}<br>
									<?= lang('app.seats') ?>: ${escapeHtml(String(t.capacity || '-'))}
								</div>
								<span class="reservation-status-badge ${badgeClass}">${badgeText}</span>
								${noteText ? `<div class="reservation-table-note">${noteText}</div>` : ''}
							</div>

							<input
								type="checkbox"
								class="form-check-input table-check"
								id="table_${tableId}"
								name="table_ids[]"
								value="${tableId}"
								${checked ? 'checked' : ''}
								${disabled}
							>
						</div>
					</label>
				</div>
			`;
		});

		tableGrid.innerHTML = html;
		bindTableEvents();
	}
	
	function formatBusyUntil(dateTimeStr) {
		if (!dateTimeStr) return '-';

		const dt = new Date(dateTimeStr.replace(' ', 'T'));
		if (isNaN(dt.getTime())) {
			return dateTimeStr;
		}

		const dd = String(dt.getDate()).padStart(2, '0');
		const mm = String(dt.getMonth() + 1).padStart(2, '0');
		const yyyy = dt.getFullYear();
		const hh = String(dt.getHours()).padStart(2, '0');
		const mi = String(dt.getMinutes()).padStart(2, '0');

		return `${dd}/${mm}/${yyyy} ${hh}:${mi}`;
	}

    function bindTableEvents() {
        document.querySelectorAll('.table-check').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                syncSelectedTableIds();

                const card = checkbox.closest('.reservation-table-card');
                const badge = card ? card.querySelector('.reservation-status-badge') : null;
                if (!card || !badge) return;

                if (checkbox.checked) {
                    card.classList.remove('is-available', 'is-occupied', 'is-disabled');
                    card.classList.add('active');
                    badge.className = 'reservation-status-badge badge-selected';
                    badge.textContent = '<?= lang('app.selected') ?>';
                } else {
                    card.classList.remove('active');
                    card.classList.add('is-available');
                    badge.className = 'reservation-status-badge badge-available';
                    badge.textContent = '<?= lang('app.available') ?>';
                }
            });
        });
    }

    [reservationDateEl, reservationTimeEl, guestCountEl, durationEl, zoneEl].forEach(function (el) {
        if (el) {
            el.addEventListener('change', loadAvailableTables);
            el.addEventListener('input', loadAvailableTables);
        }
    });

    loadAvailableTables();
});
</script>
<?= $this->endSection() ?>