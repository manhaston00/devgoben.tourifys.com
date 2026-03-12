<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft kitchen-monitor-page" id="kitchenMonitorPage">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div>
                <h4 class="mb-1"><?= esc($title ?? lang('app.kitchen_monitor')) ?></h4>
                <div class="text-muted small"><?= esc(lang('app.kitchen_monitor_desc')) ?></div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="input-group kds-search-group">
                    <span class="input-group-text">🔎</span>
                    <input
                        type="text"
                        id="searchInput"
                        class="form-control"
                        placeholder="<?= esc(lang('app.kitchen_search_placeholder')) ?>"
                    >
                    <button type="button" class="btn btn-outline-secondary" id="clearSearchBtn">
                        <?= esc(lang('app.clear')) ?>
                    </button>
                </div>

                <select id="stationFilter" class="form-select kds-station-filter">
                    <option value="0"><?= esc(lang('app.all_stations')) ?></option>
                    <?php foreach (($stations ?? []) as $station): ?>
                        <?php
                            $name = $station['station_name'] ?? '';

                            if (service('request')->getLocale() === 'th' && ! empty($station['station_name_th'])) {
                                $name = $station['station_name_th'];
                            } elseif (service('request')->getLocale() !== 'th' && ! empty($station['station_name_en'])) {
                                $name = $station['station_name_en'];
                            }
                        ?>
                        <option value="<?= (int) ($station['id'] ?? 0) ?>" <?= ((int) ($stationId ?? 0) === (int) ($station['id'] ?? 0)) ? 'selected' : '' ?>>
                            <?= esc($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="button" class="btn btn-outline-primary" id="refreshBoardBtn">
                    <?= esc(lang('app.refresh')) ?>
                </button>

                <button type="button" class="btn btn-dark" id="focusModeBtn">
                    <?= esc(lang('app.kitchen_focus_mode')) ?>
                </button>

                <button type="button" class="btn btn-outline-dark" id="fullscreenBtn">
                    <?= esc(lang('app.kitchen_fullscreen')) ?>
                </button>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-md-6">
                <div class="kds-summary-card kds-summary-card-main">
                    <div class="kds-summary-label"><?= esc(lang('app.kitchen_queue_total')) ?></div>
                    <div class="kds-summary-number" id="summary-total-active">0</div>
                    <div class="kds-summary-help"><?= esc(lang('app.kitchen_queue_total_desc')) ?></div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="kds-summary-card kds-summary-card-new">
                    <div class="kds-summary-label"><?= esc(lang('app.status_new')) ?></div>
                    <div class="kds-summary-number" id="summary-new">0</div>
                    <div class="kds-summary-help"><?= esc(lang('app.kitchen_waiting_queue')) ?></div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="kds-summary-card kds-summary-card-preparing">
                    <div class="kds-summary-label"><?= esc(lang('app.status_preparing')) ?></div>
                    <div class="kds-summary-number" id="summary-preparing">0</div>
                    <div class="kds-summary-help"><?= esc(lang('app.kitchen_cooking_queue')) ?></div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="kds-summary-card kds-summary-card-ready">
                    <div class="kds-summary-label"><?= esc(lang('app.status_ready')) ?></div>
                    <div class="kds-summary-number" id="summary-ready">0</div>
                    <div class="kds-summary-help"><?= esc(lang('app.kitchen_ready_queue')) ?></div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="d-flex flex-wrap gap-2" id="quickFilters">
                <button type="button" class="btn btn-dark kitchen-filter-btn active" data-filter="all">
                    <?= esc(lang('app.all')) ?>
                </button>
                <button type="button" class="btn btn-outline-secondary kitchen-filter-btn" data-filter="new">
                    <?= esc(lang('app.status_new')) ?>
                </button>
                <button type="button" class="btn btn-outline-warning kitchen-filter-btn" data-filter="preparing">
                    <?= esc(lang('app.status_preparing')) ?>
                </button>
                <button type="button" class="btn btn-outline-info kitchen-filter-btn" data-filter="ready">
                    <?= esc(lang('app.status_ready')) ?>
                </button>
                <button type="button" class="btn btn-outline-success kitchen-filter-btn" data-filter="served">
                    <?= esc(lang('app.status_served')) ?>
                </button>
            </div>

            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="hideEmptyColumns">
                <label class="form-check-label small" for="hideEmptyColumns">
                    <?= esc(lang('app.kitchen_hide_empty_columns')) ?>
                </label>
            </div>
        </div>

        <div class="row g-3" id="kdsBoard">
            <div class="col-xxl-3 col-lg-6 kds-col-wrap" data-col="new">
                <div class="card border-0 shadow-sm h-100 kds-column-card">
                    <div class="card-header kds-col-header d-flex justify-content-between align-items-center">
                        <span><?= esc(lang('app.status_new')) ?></span>
                        <span class="badge bg-secondary kds-col-count" id="count-new">0</span>
                    </div>
                    <div class="card-body bg-light kds-column-body" id="col-new"></div>
                </div>
            </div>

            <div class="col-xxl-3 col-lg-6 kds-col-wrap" data-col="preparing">
                <div class="card border-0 shadow-sm h-100 kds-column-card">
                    <div class="card-header kds-col-header d-flex justify-content-between align-items-center">
                        <span><?= esc(lang('app.status_preparing')) ?></span>
                        <span class="badge bg-warning text-dark kds-col-count" id="count-preparing">0</span>
                    </div>
                    <div class="card-body bg-light kds-column-body" id="col-preparing"></div>
                </div>
            </div>

            <div class="col-xxl-3 col-lg-6 kds-col-wrap" data-col="ready">
                <div class="card border-0 shadow-sm h-100 kds-column-card">
                    <div class="card-header kds-col-header d-flex justify-content-between align-items-center">
                        <span><?= esc(lang('app.status_ready')) ?></span>
                        <span class="badge bg-info text-dark kds-col-count" id="count-ready">0</span>
                    </div>
                    <div class="card-body bg-light kds-column-body" id="col-ready"></div>
                </div>
            </div>

            <div class="col-xxl-3 col-lg-6 kds-col-wrap" data-col="served">
                <div class="card border-0 shadow-sm h-100 kds-column-card">
                    <div class="card-header kds-col-header d-flex justify-content-between align-items-center">
                        <span><?= esc(lang('app.status_served')) ?></span>
                        <span class="badge bg-success kds-col-count" id="count-served">0</span>
                    </div>
                    <div class="card-body bg-light kds-column-body" id="col-served"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .kds-search-group {
        min-width: 320px;
    }

    .kds-station-filter {
        min-width: 220px;
    }

    .kitchen-filter-btn {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 10px;
    }

    .kitchen-filter-btn.active {
        color: #fff !important;
        background-color: #111827 !important;
        border-color: #111827 !important;
    }

    .kds-summary-card {
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.05);
        padding: 14px 16px;
        min-height: 108px;
        position: relative;
        overflow: hidden;
    }

    .kds-summary-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 5px;
        height: 100%;
        background: #111827;
        opacity: .9;
    }

    .kds-summary-card-new::before {
        background: #6b7280;
    }

    .kds-summary-card-preparing::before {
        background: #f59e0b;
    }

    .kds-summary-card-ready::before {
        background: #06b6d4;
    }

    .kds-summary-label {
        font-size: 12px;
        font-weight: 800;
        color: #374151;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .kds-summary-number {
        font-size: 36px;
        line-height: 1;
        font-weight: 900;
        color: #111827;
        margin-bottom: 6px;
    }

    .kds-summary-card-main .kds-summary-number {
        font-size: 40px;
    }

    .kds-summary-card-preparing .kds-summary-number {
        color: #d97706;
    }

    .kds-summary-card-ready .kds-summary-number {
        color: #0891b2;
    }

    .kds-summary-card-new .kds-summary-number {
        color: #4b5563;
    }

    .kds-summary-help {
        font-size: 12px;
        color: #6b7280;
        line-height: 1.35;
    }

    .kds-col-header {
        font-size: 14px;
        font-weight: 800;
        padding: 10px 14px;
        position: sticky;
        top: 0;
        z-index: 2;
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,.06);
    }

    .kds-column-card {
        overflow: hidden;
    }

    .kds-column-body {
        height: 62vh;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 12px;
        scrollbar-width: thin;
    }

    .kds-column-body::-webkit-scrollbar {
        width: 8px;
    }

    .kds-column-body::-webkit-scrollbar-thumb {
        background: rgba(107, 114, 128, .45);
        border-radius: 999px;
    }

    .kds-column-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .kds-card {
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.05);
        transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
        min-height: 200px;
    }

    .kds-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 .35rem .75rem rgba(0,0,0,.08);
    }

    .kds-card.kds-urgent {
        border: 2px solid #ef4444;
        background: #fff5f5;
    }

    .kds-card.kds-warning {
        border: 2px solid #fb923c;
        background: #fff8f1;
    }

    .kds-age-normal {
        background: #facc15;
        color: #111827;
    }

    .kds-age-warning {
        background: #fb923c;
        color: #fff;
    }

    .kds-age-danger {
        background: #ef4444;
        color: #fff;
    }

    .kds-col-wrap.is-hidden {
        display: none !important;
    }

    .kds-card.is-search-hidden {
        display: none !important;
    }

    .kds-queue-badge,
    .kds-station-badge {
        font-size: 11px;
        font-weight: 700;
        border-radius: 999px;
        padding: 5px 9px;
    }

    .kds-item-title {
        font-size: 18px;
        line-height: 1.25;
        font-weight: 800;
    }

    .kds-item-sub {
        font-size: 14px;
        line-height: 1.4;
    }

    .kds-meta {
        font-size: 13px;
    }

    .kitchen-status-btn {
        width: 100%;
        min-height: 50px;
        font-size: 18px;
        font-weight: 800;
        border-radius: 12px;
        margin-top: 12px !important;
    }

    .kds-col-count {
        min-width: 28px;
        text-align: center;
    }

    .kds-focus-mode .sidebar,
    .kds-focus-mode .app-sidebar,
    .kds-focus-mode aside,
    .kds-focus-mode .navbar,
    .kds-focus-mode .topbar,
    .kds-focus-mode .main-sidebar {
        display: none !important;
    }

    .kds-focus-mode .content-wrapper,
    .kds-focus-mode .main-content,
    .kds-focus-mode .page-content,
    .kds-focus-mode .app-content,
    .kds-focus-mode main {
        margin-left: 0 !important;
        padding-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    .kds-focus-mode .container-fluid,
    .kds-focus-mode .container,
    .kds-focus-mode .page-inner {
        max-width: 100% !important;
        width: 100% !important;
    }

    .kds-focus-mode .kitchen-monitor-page {
        border-radius: 0 !important;
        margin: 0 !important;
    }

    .kds-browser-fullscreen .kds-column-body {
        height: 74vh;
    }

    @media (max-width: 1200px) {
        .kds-search-group {
            min-width: 260px;
        }

        .kds-item-title {
            font-size: 17px;
        }

        .kitchen-status-btn {
            font-size: 17px;
        }

        .kds-column-body {
            height: 56vh;
        }
    }
	.kds-grid {
		display: grid;
		grid-template-columns: repeat(4, minmax(0, 1fr));
		gap: 12px;
	}

	.kds-column {
		min-width: 0;
		display: flex;
		flex-direction: column;
		height: calc(100vh - 180px);
	}

	.kds-column-body {
		overflow-y: auto;
		padding-right: 4px;
	}

	.kds-card {
		border-radius: 14px;
		padding: 10px;
		margin-bottom: 10px;
		font-size: 0.95rem;
	}

	.kds-card .kds-title {
		font-size: 1rem;
		line-height: 1.2;
	}

	.kds-card .kds-meta,
	.kds-card .kds-items {
		font-size: 0.85rem;
		line-height: 1.2;
	}

	@media (max-width: 1199.98px) and (min-width: 768px) {
		.kds-grid {
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 8px;
		}

		.kds-card {
			padding: 8px;
			margin-bottom: 8px;
			font-size: 0.82rem;
		}

		.kds-card .kds-title {
			font-size: 0.9rem;
		}

		.kds-card .kds-meta,
		.kds-card .kds-items {
			font-size: 0.75rem;
		}

		.kds-column {
			height: calc(100vh - 140px);
		}
	}

	@media (max-width: 767.98px) {
		.kds-grid {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
	}
</style>

<script>
(function () {
    const stationFilter = document.getElementById('stationFilter');
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const refreshBoardBtn = document.getElementById('refreshBoardBtn');
    const focusModeBtn = document.getElementById('focusModeBtn');
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    const hideEmptyColumns = document.getElementById('hideEmptyColumns');
    const pollSeconds = <?= (int) ($pollingSeconds ?? 5) ?>;

    let lastNewCount = 0;
    let boardLoadedOnce = false;
    let isLoadingBoard = false;
    let quickFilter = 'all';
    let focusMode = false;

    const i18n = {
        kitchen: '<?= esc(lang('app.kitchen')) ?>',
        table: '<?= esc(lang('app.table')) ?>',
        order: '<?= esc(lang('app.order')) ?>',
        noItems: '<?= esc(lang('app.no_items')) ?>',
        saveFailed: '<?= esc(lang('app.save_failed')) ?>',
        sentLabel: '<?= esc(lang('app.sent')) ?>',
        servedLabel: '<?= esc(lang('app.served')) ?>',
        queueLabel: '<?= esc(lang('app.kitchen_queue')) ?>',
        itemCountLabel: '<?= esc(lang('app.kitchen_item_count')) ?>',
        focusModeEnter: '<?= esc(lang('app.kitchen_focus_mode')) ?>',
        focusModeExit: '<?= esc(lang('app.kitchen_exit_focus_mode')) ?>',
        fullscreenEnter: '<?= esc(lang('app.kitchen_fullscreen')) ?>',
        fullscreenExit: '<?= esc(lang('app.kitchen_exit_fullscreen')) ?>',
        actionStart: '<?= esc(lang('app.kitchen_action_start')) ?>',
        actionReady: '<?= esc(lang('app.kitchen_action_ready')) ?>',
        actionServed: '<?= esc(lang('app.kitchen_action_served')) ?>'
    };

    const csrfName = <?= json_encode(csrf_token()) ?>;
    let csrfHash = <?= json_encode(csrf_hash()) ?>;

    function escapeHtml(text) {
        return String(text ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDateTime(value) {
        if (!value) {
            return '-';
        }

        const d = new Date(String(value).replace(' ', 'T'));
        if (isNaN(d.getTime())) {
            return value;
        }

        return d.toLocaleString();
    }

    function getAgeMinutes(value) {
        if (!value) {
            return 0;
        }

        const d = new Date(String(value).replace(' ', 'T'));
        if (isNaN(d.getTime())) {
            return 0;
        }

        const now = new Date();
        return Math.floor((now.getTime() - d.getTime()) / 60000);
    }

    function diffMinutes(value) {
        const mins = getAgeMinutes(value);

        if (mins < 1) {
            return '0m';
        }

        if (mins < 60) {
            return mins + 'm';
        }

        const hours = Math.floor(mins / 60);
        const remain = mins % 60;
        return hours + 'h ' + remain + 'm';
    }

    function ageBadgeClass(value) {
        const mins = getAgeMinutes(value);

        if (mins >= 16) {
            return 'kds-age-danger';
        }

        if (mins >= 6) {
            return 'kds-age-warning';
        }

        return 'kds-age-normal';
    }

    function cardUrgencyClass(value) {
        const mins = getAgeMinutes(value);

        if (mins >= 16) {
            return 'kds-urgent';
        }

        if (mins >= 6) {
            return 'kds-warning';
        }

        return '';
    }

    function normalizeBoardStatus(item) {
        const boardStatus = String(item.board_status || '').toLowerCase().trim();
        if (['new', 'preparing', 'ready', 'served'].includes(boardStatus)) {
            return boardStatus;
        }

        const itemStatus = String(item.item_status || '').toLowerCase().trim();
        const ticketStatus = String(item.ticket_status || '').toLowerCase().trim();

        if (itemStatus === 'served') {
            return 'served';
        }

        if (itemStatus === 'ready') {
            return 'ready';
        }

        if (itemStatus === 'cooking') {
            return 'preparing';
        }

        if (itemStatus === 'sent' && ticketStatus === 'done') {
            return 'ready';
        }

        return 'new';
    }

    function getActionConfig(boardStatus) {
        if (boardStatus === 'new') {
            return {
                nextStatus: 'preparing',
                label: '▶ ' + i18n.actionStart,
                className: 'btn-warning'
            };
        }

        if (boardStatus === 'preparing') {
            return {
                nextStatus: 'ready',
                label: '✔ ' + i18n.actionReady,
                className: 'btn-info text-dark'
            };
        }

        if (boardStatus === 'ready') {
            return {
                nextStatus: 'served',
                label: '✅ ' + i18n.actionServed,
                className: 'btn-success'
            };
        }

        return null;
    }

    function renderActionButtons(item) {
        const boardStatus = normalizeBoardStatus(item);
        const action = getActionConfig(boardStatus);

        if (!action) {
            return '';
        }

        const itemId = Number(item.order_item_id || item.item_id || 0);
        if (!itemId) {
            return '';
        }

        return `
            <button
                type="button"
                class="btn ${action.className} kitchen-status-btn"
                data-item-id="${itemId}"
                data-status="${escapeHtml(action.nextStatus)}">
                ${escapeHtml(action.label)}
            </button>
        `;
    }

    function searchableText(item) {
        return [
            item.table_name || '',
            item.order_number || '',
            item.ticket_no || '',
            item.product_name || '',
            item.item_detail || '',
            item.note || ''
        ].join(' ').toLowerCase();
    }

    function renderCard(item, indexInColumn) {
        const locale = '<?= esc(service('request')->getLocale()) ?>';
        const boardStatus = normalizeBoardStatus(item);

        let stationName = item.station_display_name || item.station_name || i18n.kitchen;

        if (!item.station_display_name) {
            if (locale === 'th' && item.station_name_th) {
                stationName = item.station_name_th;
            } else if (locale !== 'th' && item.station_name_en) {
                stationName = item.station_name_en;
            }
        }

        const tableName = item.table_name || '-';
        const orderNo = item.order_number || item.ticket_no || '#';
        const sentAt = item.sent_at ? formatDateTime(item.sent_at) : '-';
        const servedAt = item.served_at ? formatDateTime(item.served_at) : '-';
        const ageText = item.sent_at ? diffMinutes(item.sent_at) : '';
        const ageClass = item.sent_at ? ageBadgeClass(item.sent_at) : 'kds-age-normal';
        const urgencyClass = item.sent_at ? cardUrgencyClass(item.sent_at) : '';
        const actions = renderActionButtons(item);
        const searchText = searchableText(item);
        const queueNo = Number(indexInColumn || 0) + 1;

        return `
            <div
                class="kds-card ${urgencyClass} p-3 mb-3"
                data-board-status="${escapeHtml(boardStatus)}"
                data-search="${escapeHtml(searchText)}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-dark kds-queue-badge">${escapeHtml(i18n.queueLabel)} #${queueNo}</span>
                        <span class="badge bg-secondary kds-station-badge">${escapeHtml(stationName)}</span>
                    </div>
                </div>

                <div class="mb-2">
                    <div class="fw-bold kds-item-sub">${escapeHtml(i18n.table)}: ${escapeHtml(tableName)}</div>
                    <div class="small text-muted">${escapeHtml(i18n.order)}: ${escapeHtml(orderNo)}</div>
                </div>

                <div class="kds-item-title mb-2">${escapeHtml(item.product_name || '-')}</div>

                ${item.item_detail ? `<div class="kds-item-sub text-muted mt-1">${escapeHtml(item.item_detail)}</div>` : ''}
                ${item.note ? `<div class="kds-item-sub text-danger mt-1 fw-semibold">${escapeHtml(item.note)}</div>` : ''}

                <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge bg-dark kds-queue-badge">${escapeHtml(i18n.itemCountLabel)} x ${escapeHtml(item.qty || 1)}</span>
                    ${ageText ? `<span class="badge ${ageClass} kds-queue-badge">${escapeHtml(ageText)}</span>` : ''}
                </div>

                <div class="kds-meta text-muted mt-3">
                    <div>${escapeHtml(i18n.sentLabel)}: ${escapeHtml(sentAt)}</div>
                    ${item.served_at ? `<div>${escapeHtml(i18n.servedLabel)}: ${escapeHtml(servedAt)}</div>` : ''}
                </div>

                <div>${actions}</div>
            </div>
        `;
    }

    function renderColumn(targetId, rows) {
		const el = document.getElementById(targetId);
		if (!el) {
			return;
		}

		if (!rows || !rows.length) {
			el.innerHTML = `<div class="text-muted small kds-empty">${escapeHtml(i18n.noItems)}</div>`;
			return;
		}

		el.innerHTML = rows.map((row, index) => renderCard(row, index)).join('');
	}

    function updateCounts(data) {
        const newCount = (data.new || []).length;
        const preparingCount = (data.preparing || []).length;
        const readyCount = (data.ready || []).length;
        const servedCount = (data.served || []).length;
        const totalActive = newCount + preparingCount + readyCount;

        document.getElementById('count-new').textContent = newCount;
        document.getElementById('count-preparing').textContent = preparingCount;
        document.getElementById('count-ready').textContent = readyCount;
        document.getElementById('count-served').textContent = servedCount;

        document.getElementById('summary-total-active').textContent = totalActive;
        document.getElementById('summary-new').textContent = newCount;
        document.getElementById('summary-preparing').textContent = preparingCount;
        document.getElementById('summary-ready').textContent = readyCount;
    }

    function playBeep() {
        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) {
                return;
            }

            const ctx = new AudioContextClass();

            const tone1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            tone1.type = 'sine';
            tone1.frequency.setValueAtTime(880, ctx.currentTime);
            gain1.gain.setValueAtTime(0.08, ctx.currentTime);
            tone1.connect(gain1);
            gain1.connect(ctx.destination);
            tone1.start();
            tone1.stop(ctx.currentTime + 0.20);

            const tone2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            tone2.type = 'sine';
            tone2.frequency.setValueAtTime(988, ctx.currentTime + 0.24);
            gain2.gain.setValueAtTime(0.08, ctx.currentTime + 0.24);
            tone2.connect(gain2);
            gain2.connect(ctx.destination);
            tone2.start(ctx.currentTime + 0.24);
            tone2.stop(ctx.currentTime + 0.52);
        } catch (e) {
            console.error('Kitchen alert sound error:', e);
        }
    }

    function applyClientFilters() {
        const keyword = String(searchInput ? searchInput.value : '').trim().toLowerCase();
        const cards = document.querySelectorAll('.kds-card');
        const columnWraps = document.querySelectorAll('.kds-col-wrap');

        cards.forEach((card) => {
            const cardStatus = String(card.getAttribute('data-board-status') || '').toLowerCase();
            const cardSearch = String(card.getAttribute('data-search') || '').toLowerCase();

            const matchQuickFilter = (quickFilter === 'all') || (cardStatus === quickFilter);
            const matchSearch = !keyword || cardSearch.includes(keyword);

            card.classList.toggle('is-search-hidden', !(matchQuickFilter && matchSearch));
        });

        columnWraps.forEach((col) => {
            const visibleCards = col.querySelectorAll('.kds-card:not(.is-search-hidden)').length;
            const shouldHide = hideEmptyColumns && hideEmptyColumns.checked && visibleCards === 0;
            col.classList.toggle('is-hidden', shouldHide);
        });
    }

    async function loadBoard() {
        if (isLoadingBoard) {
            return;
        }

        isLoadingBoard = true;

        const params = new URLSearchParams({
            station_id: stationFilter ? (stationFilter.value || '0') : '0',
            mode: 'all'
        });

        try {
            const res = await fetch(`<?= site_url('kitchen-monitor/feed') ?>?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const json = await res.json();

            if (!json || json.status !== 'success') {
                return;
            }

            const data = json.data || {};
            const newCount = (data.new || []).length;

            renderColumn('col-new', data.new || []);
            renderColumn('col-preparing', data.preparing || []);
            renderColumn('col-ready', data.ready || []);
            renderColumn('col-served', data.served || []);
            updateCounts(data);
            applyClientFilters();

            if (boardLoadedOnce && newCount > lastNewCount) {
                playBeep();
            }

            lastNewCount = newCount;
            boardLoadedOnce = true;
        } catch (error) {
            console.error('Kitchen board load error:', error);
        } finally {
            isLoadingBoard = false;
        }
    }

    async function updateStatus(itemId, status, buttonEl) {
        if (!itemId || !status) {
            return;
        }

        const body = new URLSearchParams();
        body.append('item_id', itemId);
        body.append('status', status);
        body.append(csrfName, csrfHash);

        if (buttonEl) {
            buttonEl.disabled = true;
        }

        try {
            const res = await fetch(`<?= site_url('kitchen-monitor/update-status') ?>`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: body.toString()
            });

            const json = await res.json();

            if (json && json.token) {
                csrfHash = json.token;
            }

            if (json && json.status === 'success') {
                await loadBoard();
            } else {
                alert((json && json.message) ? json.message : i18n.saveFailed);
                if (buttonEl) {
                    buttonEl.disabled = false;
                }
            }
        } catch (error) {
            console.error('Kitchen status update error:', error);
            alert(i18n.saveFailed);
            if (buttonEl) {
                buttonEl.disabled = false;
            }
        }
    }

    function applyFilterButtonStyles(activeFilter) {
        document.querySelectorAll('.kitchen-filter-btn').forEach((el) => {
            const filter = String(el.getAttribute('data-filter') || '');
            el.classList.remove('active');

            if (filter === 'all') {
                el.className = 'btn btn-outline-dark kitchen-filter-btn';
            } else if (filter === 'new') {
                el.className = 'btn btn-outline-secondary kitchen-filter-btn';
            } else if (filter === 'preparing') {
                el.className = 'btn btn-outline-warning kitchen-filter-btn';
            } else if (filter === 'ready') {
                el.className = 'btn btn-outline-info kitchen-filter-btn';
            } else if (filter === 'served') {
                el.className = 'btn btn-outline-success kitchen-filter-btn';
            }
        });

        const activeBtn = document.querySelector(`.kitchen-filter-btn[data-filter="${activeFilter}"]`);
        if (!activeBtn) {
            return;
        }

        if (activeFilter === 'all') {
            activeBtn.className = 'btn btn-dark kitchen-filter-btn active';
        } else if (activeFilter === 'new') {
            activeBtn.className = 'btn btn-secondary kitchen-filter-btn active';
        } else if (activeFilter === 'preparing') {
            activeBtn.className = 'btn btn-warning kitchen-filter-btn active';
        } else if (activeFilter === 'ready') {
            activeBtn.className = 'btn btn-info text-dark kitchen-filter-btn active';
        } else if (activeFilter === 'served') {
            activeBtn.className = 'btn btn-success kitchen-filter-btn active';
        }
    }

    function applyFocusMode(enabled) {
        focusMode = !!enabled;
        document.body.classList.toggle('kds-focus-mode', focusMode);

        if (focusModeBtn) {
            focusModeBtn.textContent = focusMode ? i18n.focusModeExit : i18n.focusModeEnter;
            focusModeBtn.className = focusMode ? 'btn btn-primary' : 'btn btn-dark';
        }

        try {
            localStorage.setItem('kds_focus_mode', focusMode ? '1' : '0');
        } catch (e) {
            console.error('Focus mode storage error:', e);
        }
    }

    async function toggleFullscreen() {
        try {
            if (!document.fullscreenElement) {
                await document.documentElement.requestFullscreen();
            } else {
                await document.exitFullscreen();
            }
        } catch (e) {
            console.error('Fullscreen error:', e);
        }
    }

    function updateFullscreenButton() {
        const isFullscreen = !!document.fullscreenElement;
        document.body.classList.toggle('kds-browser-fullscreen', isFullscreen);

        if (fullscreenBtn) {
            fullscreenBtn.textContent = isFullscreen ? i18n.fullscreenExit : i18n.fullscreenEnter;
            fullscreenBtn.className = isFullscreen ? 'btn btn-primary' : 'btn btn-outline-dark';
        }
    }

    document.addEventListener('fullscreenchange', function () {
        updateFullscreenButton();
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.kitchen-status-btn[data-item-id][data-status]');
        if (btn) {
            updateStatus(btn.getAttribute('data-item-id'), btn.getAttribute('data-status'), btn);
            return;
        }

        const filterBtn = e.target.closest('.kitchen-filter-btn[data-filter]');
        if (filterBtn) {
            quickFilter = String(filterBtn.getAttribute('data-filter') || 'all');
            applyFilterButtonStyles(quickFilter);
            applyClientFilters();
            return;
        }
    });

    if (stationFilter) {
        stationFilter.addEventListener('change', loadBoard);
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyClientFilters);
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function () {
            searchInput.value = '';
            applyClientFilters();
            searchInput.focus();
        });
    }

    if (refreshBoardBtn) {
        refreshBoardBtn.addEventListener('click', loadBoard);
    }

    if (focusModeBtn) {
        focusModeBtn.addEventListener('click', function () {
            applyFocusMode(!focusMode);
        });
    }

    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function () {
            toggleFullscreen();
        });
    }

    if (hideEmptyColumns) {
        hideEmptyColumns.addEventListener('change', applyClientFilters);
    }

    try {
        if (localStorage.getItem('kds_focus_mode') === '1') {
            applyFocusMode(true);
        }
    } catch (e) {
        console.error('Focus mode restore error:', e);
    }

    applyFilterButtonStyles('all');
    updateFullscreenButton();
    loadBoard();
    setInterval(loadBoard, pollSeconds * 1000);
})();
</script>

<?= $this->endSection() ?>