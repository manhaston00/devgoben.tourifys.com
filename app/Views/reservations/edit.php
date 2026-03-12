<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
$oldTableIds = old('table_ids');
$selectedTableIds = is_array($oldTableIds)
    ? array_map('intval', $oldTableIds)
    : array_map('intval', $tableIds ?? []);

$currentZoneId = (int) old('zone_id', $reservation['zone_id'] ?? 0);

/**
 * สร้างรายการ<?= lang('app.zone') ?>จาก tables
 * คาดว่า getTablesFull() จะมี zone_id, zone_name
 */
$zonesMap = [];
if (!empty($tables)) {
    foreach ($tables as $tb) {
        $zId   = (int) ($tb['zone_id'] ?? 0);
        $zName = trim((string) ($tb['zone_name'] ?? ''));

        if ($zId > 0) {
            $zonesMap[$zId] = $zName !== '' ? $zName : (lang('app.zone') . ' #' . $zId);
        }
    }
}
ksort($zonesMap);
?>

<style>
    .reservation-table-card {
		display: block;
		width: 100%;
		border: 1px solid #dee2e6;
		border-radius: 14px;
		padding: 12px;
		background: #fff;
		cursor: pointer;
		transition: all .2s ease;
		height: 100%;
	}

    .reservation-table-card:hover {
        border-color: #0d6efd;
        box-shadow: 0 4px 14px rgba(0,0,0,.08);
        transform: translateY(-1px);
    }

    .reservation-table-card.active {
        border-color: #198754;
        background: #eaf7f0;
        box-shadow: 0 0 0 1px rgba(25,135,84,.2);
    }

    .reservation-table-card.disabled-card {
        opacity: .65;
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

    .table-check {
        width: 18px;
        height: 18px;
    }

    .sticky-filter-bar {
        position: sticky;
        top: 10px;
        z-index: 5;
        background: #fff;
    }

    .badge-soft {
        background: #f1f3f5;
        color: #495057;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 12px;
        display: inline-block;
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

<div class="card card-soft">
    <div class="card-body">
        <?php
			ob_start();
			?>
				<a href="<?= site_url('reservations') ?>" class="btn btn-secondary">
					<?= lang('app.back') ?>
				</a>
			<?php
			$actions = ob_get_clean();

			echo view('partials/app_page_header', [
				'title'   => lang('app.edit_reservation'),
				'desc'    => lang('app.reservation_code') . ': ' . ($reservation['reservation_code'] ?? '-'),
				'actions' => $actions,
			]);
		?>

        <form method="post" action="<?= site_url('reservations/update/' . $reservation['id']) ?>">
            <?= csrf_field() ?>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.customer_name') ?></label>
                    <input type="text"
                           name="customer_name"
                           class="form-control"
                           value="<?= esc(old('customer_name', $reservation['customer_name'] ?? '')) ?>"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.customer_phone') ?></label>
                    <input type="text"
                           name="customer_phone"
                           class="form-control"
                           value="<?= esc(old('customer_phone', $reservation['customer_phone'] ?? '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= lang('app.reservation_date') ?></label>
                    <input type="date"
                           name="reservation_date"
                           class="form-control"
                           value="<?= esc(old('reservation_date', $reservation['reservation_date'] ?? '')) ?>"
                           required>
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= lang('app.reservation_time') ?></label>
                    <input type="time"
                           name="reservation_time"
                           class="form-control"
                           value="<?= esc(old('reservation_time', $reservation['reservation_time'] ?? '')) ?>"
                           required>
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= lang('app.guest_count') ?></label>
                    <input type="number"
                           name="guest_count"
                           class="form-control"
                           min="1"
                           value="<?= esc(old('guest_count', $reservation['guest_count'] ?? 1)) ?>"
                           required>
                </div>

                <div class="col-md-3">
                    <label class="form-label"><?= lang('app.duration_minutes') ?></label>
                    <input type="number"
                           name="duration_minutes"
                           class="form-control"
                           min="30"
                           step="30"
                           value="<?= esc(old('duration_minutes', $reservation['duration_minutes'] ?? 60)) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.status') ?></label>
                    <?php $currentStatus = old('status', $reservation['status'] ?? 'confirmed'); ?>
                    <select name="status" class="form-select">
                        <option value="pending" <?= $currentStatus === 'pending' ? 'selected' : '' ?>><?= lang('app.pending') ?></option>
                        <option value="confirmed" <?= $currentStatus === 'confirmed' ? 'selected' : '' ?>><?= lang('app.confirmed') ?></option>
                        <option value="completed" <?= $currentStatus === 'completed' ? 'selected' : '' ?>><?= lang('app.completed') ?></option>
                        <option value="cancelled" <?= $currentStatus === 'cancelled' ? 'selected' : '' ?>><?= lang('app.cancelled') ?></option>
                        <option value="seated" <?= $currentStatus === 'seated' ? 'selected' : '' ?>><?= lang('app.seated') ?></option>
                        <option value="no_show" <?= $currentStatus === 'no_show' ? 'selected' : '' ?>><?= lang('app.no_show') ?></option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.source') ?></label>
                    <?php $currentSource = old('source', $reservation['source'] ?? 'phone'); ?>
                    <select name="source" class="form-select">
                        <option value="phone" <?= $currentSource === 'phone' ? 'selected' : '' ?>><?= lang('app.phone') ?></option>
                        <option value="walkin" <?= $currentSource === 'walkin' ? 'selected' : '' ?>>Walk-in</option>
                        <option value="facebook" <?= $currentSource === 'facebook' ? 'selected' : '' ?>>Facebook</option>
                        <option value="line" <?= $currentSource === 'line' ? 'selected' : '' ?>>LINE</option>
                        <option value="other" <?= $currentSource === 'other' ? 'selected' : '' ?>><?= lang('app.other') ?></option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label"><?= lang('app.zone') ?></label>
                    <select name="zone_id" id="zone_id" class="form-select">
                        <option value="0">-- <?= lang('app.all_zones') ?> --</option>
                        <?php foreach ($zonesMap as $zId => $zName): ?>
                            <option value="<?= $zId ?>" <?= $currentZoneId === (int) $zId ? 'selected' : '' ?>>
                                <?= esc($zName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 mt-2">
                    <hr>
                    <div class="sticky-filter-bar pb-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label"><?= lang('app.zone') ?> / <?= lang('app.table') ?></label>
                                <select id="table_zone_filter" class="form-select">
                                    <option value="all"><?= lang('app.all_zones') ?></option>
                                    <?php foreach ($zonesMap as $zId => $zName): ?>
                                        <option value="<?= $zId ?>" <?= $currentZoneId === (int) $zId ? 'selected' : '' ?>>
                                            <?= esc($zName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label"><?= lang('app.search') ?><?= lang('app.table') ?></label>
                                <input type="text" id="table_search" class="form-control" placeholder="<?= lang('app.search') ?><?= lang('app.table') ?> / <?= lang('app.zone') ?> / <?= lang('app.seats') ?>">
                            </div>

                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button type="button" id="btn_select_all_visible" class="btn btn-outline-primary w-100">
                                        <?= lang('app.select_all_visible') ?>
                                    </button>
                                    <button type="button" id="btn_clear_all" class="btn btn-outline-danger w-100">
                                        <?= lang('app.clear_selected') ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                            <span class="badge-soft"><?= lang('app.multiple_tables_hint_short') ?></span>
                            <span class="badge-soft"><?= lang('app.selected') ?>: <strong id="selected_count"><?= count($selectedTableIds) ?></strong> <?= lang('app.table') ?></span>
							<span class="badge-soft"><?= lang('app.available_hint') ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label"><?= lang('app.select_table') ?></label>

                    <div class="row g-3" id="table_grid">
                        <?php if (!empty($tables)): ?>
                            <?php foreach ($tables as $t): ?>
                                <?php
                                $tableId    = (int) ($t['id'] ?? 0);
                                $tableName  = trim((string) ($t['table_name'] ?? (lang('app.table') . ' #' . $tableId)));
                                $zoneId     = (int) ($t['zone_id'] ?? 0);
                                $zoneName   = trim((string) ($t['zone_name'] ?? lang('app.unspecified')));
                                $capacity   = trim((string) ($t['capacity'] ?? $t['seat_capacity'] ?? ''));
                                $status     = trim((string) ($t['status'] ?? ''));
                                $isChecked  = in_array($tableId, $selectedTableIds, true);
                                $searchText = strtolower($tableName . ' ' . $zoneName . ' ' . $capacity . ' ' . $status);
                                ?>
                                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 table-item"
                                     data-zone-id="<?= $zoneId ?>"
                                     data-search="<?= esc($searchText, 'attr') ?>">
                                    <label class="reservation-table-card <?= $isChecked ? 'active' : '' ?>" for="table_<?= $tableId ?>">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div>
                                                <div class="reservation-table-name"><?= esc($tableName) ?></div>
                                                <div class="reservation-table-meta">
                                                    <?= lang('app.zone') ?>: <?= esc($zoneName !== '' ? $zoneName : lang('app.unspecified')) ?><br>
                                                    <?php if ($capacity !== ''): ?>
                                                        <?= lang('app.seats') ?>: <?= esc($capacity) ?><br>
                                                    <?php endif; ?>
                                                    <?php if ($status !== ''): ?>
                                                        <?= lang('app.status') ?>: <?= esc($status) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <input type="checkbox"
                                                   class="form-check-input table-check"
                                                   id="table_<?= $tableId ?>"
                                                   name="table_ids[]"
                                                   value="<?= $tableId ?>"
                                                   <?= $isChecked ? 'checked' : '' ?>>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-warning mb-0">
                                    <?= lang('app.no_matching_tables') ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.special_note') ?></label>
                    <textarea name="special_note"
                              class="form-control"
                              rows="3"><?= esc(old('special_note', $reservation['special_note'] ?? '')) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= lang('app.internal_note') ?></label>
                    <textarea name="internal_note"
                              class="form-control"
                              rows="3"><?= esc(old('internal_note', $reservation['internal_note'] ?? '')) ?></textarea>
                </div>

            </div>

            <div class="mt-4 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-success">
                    <?= lang('app.save') ?>
                </button>
                <a href="<?= site_url('reservations') ?>" class="btn btn-secondary">
                    <?= lang('app.back') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const zoneFilter = document.getElementById('table_zone_filter');
    const searchInput = document.getElementById('table_search');
    function getTableItems() {
		return document.querySelectorAll('.table-item');
	}
    const countEl = document.getElementById('selected_count');
    const btnSelectAllVisible = document.getElementById('btn_select_all_visible');
    const btnClearAll = document.getElementById('btn_clear_all');
    const zoneMain = document.getElementById('zone_id');

    function updateCardStates() {
        document.querySelectorAll('.table-check').forEach(function (checkbox) {
            const card = checkbox.closest('.reservation-table-card');
            if (!card) return;

            if (checkbox.checked) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });

        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.table-check:checked').length;
        if (countEl) {
            countEl.textContent = checked;
        }
    }

    function applyTableFilter() {
        const zoneValue = zoneFilter ? zoneFilter.value : 'all';
        const keyword = (searchInput ? searchInput.value : '').trim().toLowerCase();

        getTableItems().forEach(function (item) {
            const itemZoneId = item.getAttribute('data-zone-id') || '0';
            const itemSearch = (item.getAttribute('data-search') || '').toLowerCase();

            const matchZone = zoneValue === 'all' || zoneValue === itemZoneId;
            const matchKeyword = keyword === '' || itemSearch.includes(keyword);

            item.style.display = (matchZone && matchKeyword) ? '' : 'none';
        });
    }

    if (zoneFilter) {
		zoneFilter.addEventListener('change', function () {
			applyTableFilter();

			if (zoneMain) {
				zoneMain.value = this.value === 'all' ? '0' : this.value;
				zoneMain.dispatchEvent(new Event('change'));
			}
		});
	}

    if (searchInput) {
        searchInput.addEventListener('input', applyTableFilter);
    }

    document.querySelectorAll('.table-check').forEach(function (checkbox) {
        checkbox.addEventListener('change', updateCardStates);
    });

    if (btnSelectAllVisible) {
        btnSelectAllVisible.addEventListener('click', function () {
            document.querySelectorAll('.table-item').forEach(function (item) {
                if (item.style.display === 'none') return;

                const checkbox = item.querySelector('.table-check');
                if (checkbox && !checkbox.disabled) {
					checkbox.checked = true;
					checkbox.dispatchEvent(new Event('change'));
				}
            });

            updateCardStates();
        });
    }

    if (btnClearAll) {
        btnClearAll.addEventListener('click', function () {
            document.querySelectorAll('.table-check').forEach(function (checkbox) {
				if (!checkbox.disabled) {
					checkbox.checked = false;
					checkbox.dispatchEvent(new Event('change'));
				}
			});
			updateCardStates();
        });
    }

    applyTableFilter();
    updateCardStates();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const reservationDateEl = document.querySelector('[name="reservation_date"]');
    const reservationTimeEl = document.querySelector('[name="reservation_time"]');
    const guestCountEl      = document.querySelector('[name="guest_count"]');
    const durationEl        = document.querySelector('[name="duration_minutes"]');
    const zoneEl            = document.querySelector('[name="zone_id"]');
    const tableGrid         = document.getElementById('table_grid');

    const reservationId = <?= isset($reservation['id']) ? (int) $reservation['id'] : 0 ?>;
	let selectedTableIds = <?= json_encode(array_values(array_map('intval', $selectedTableIds ?? []))) ?>;

    async function loadAvailableTables() {
        const reservation_date = reservationDateEl ? reservationDateEl.value : '';
        const reservation_time = reservationTimeEl ? reservationTimeEl.value : '';
        const guest_count      = guestCountEl ? guestCountEl.value : '';
        const duration_minutes = durationEl ? durationEl.value : '';
        const zone_id          = zoneEl ? zoneEl.value : '';

        if (!reservation_date || !reservation_time) {
            return;
        }

        const url = new URL('<?= site_url('reservations/available-tables') ?>', window.location.origin);
        url.searchParams.set('reservation_date', reservation_date);
        url.searchParams.set('reservation_time', reservation_time);
        url.searchParams.set('guest_count', guest_count);
        url.searchParams.set('duration_minutes', duration_minutes);
        url.searchParams.set('zone_id', zone_id);
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
                return;
            }

            renderTables(data.tables);
        } catch (error) {
            console.error('Load available tables failed:', error);
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

			const searchText = [
				t.table_name || '',
				t.zone_name || '',
				t.capacity || '',
				badgeText || '',
				busyReason || ''
			].join(' ').toLowerCase();

			html += `
				<div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 table-item"
					 data-zone-id="${parseInt(t.zone_id || 0, 10)}"
					 data-search="${escapeHtml(searchText)}">
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
				const tableId = parseInt(checkbox.value || 0, 10);
				const card = checkbox.closest('.reservation-table-card');
				const badge = card ? card.querySelector('.reservation-status-badge') : null;

				if (checkbox.checked) {
					if (!selectedTableIds.includes(tableId)) {
						selectedTableIds.push(tableId);
					}
				} else {
					selectedTableIds = selectedTableIds.filter(id => id !== tableId);
				}

				if (card && badge) {
					if (checkbox.checked) {
						card.classList.remove('is-available', 'is-occupied', 'is-disabled', 'is-open-order');
						card.classList.add('active');
						badge.className = 'reservation-status-badge badge-selected';
						badge.textContent = '<?= lang('app.selected') ?>';
					} else {
						card.classList.remove('active');
						card.classList.add('is-available');
						badge.className = 'reservation-status-badge badge-available';
						badge.textContent = '<?= lang('app.available') ?>';
					}
				}

				const countEl = document.getElementById('selected_count');
				if (countEl) {
					countEl.textContent = selectedTableIds.length;
				}
			});
		});
	}

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    [reservationDateEl, reservationTimeEl, guestCountEl, durationEl, zoneEl].forEach(function (el) {
        if (el) {
            el.addEventListener('change', loadAvailableTables);
        }
    });

    loadAvailableTables();
});
</script>
<?= $this->endSection() ?>