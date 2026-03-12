<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="card card-soft">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h4 class="mb-1"><?= esc($title ?? lang('app.kitchen_monitor')) ?></h4>
                <div class="text-muted">
                    <?= esc(lang('app.kitchen_monitor_desc')) ?>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <select id="stationFilter" class="form-select">
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

                <select id="modeFilter" class="form-select">
                    <option value="all" <?= (($defaultMode ?? 'all') === 'all') ? 'selected' : '' ?>>
                        <?= esc(lang('app.kitchen_mode_all')) ?>
                    </option>
                    <option value="station" <?= (($defaultMode ?? 'all') === 'station') ? 'selected' : '' ?>>
                        <?= esc(lang('app.kitchen_mode_station')) ?>
                    </option>
                </select>
            </div>
        </div>

        <div class="row g-3" id="kdsBoard">
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                        <span><?= esc(lang('app.status_new')) ?></span>
                        <span class="badge bg-secondary" id="count-new">0</span>
                    </div>
                    <div class="card-body bg-light" id="col-new"></div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                        <span><?= esc(lang('app.status_preparing')) ?></span>
                        <span class="badge bg-warning text-dark" id="count-preparing">0</span>
                    </div>
                    <div class="card-body bg-light" id="col-preparing"></div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                        <span><?= esc(lang('app.status_ready')) ?></span>
                        <span class="badge bg-info text-dark" id="count-ready">0</span>
                    </div>
                    <div class="card-body bg-light" id="col-ready"></div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                        <span><?= esc(lang('app.status_served')) ?></span>
                        <span class="badge bg-success" id="count-served">0</span>
                    </div>
                    <div class="card-body bg-light" id="col-served"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const stationFilter = document.getElementById('stationFilter');
    const modeFilter = document.getElementById('modeFilter');
    const pollSeconds = <?= (int) ($pollingSeconds ?? 5) ?>;

    let lastNewCount = 0;
    let boardLoadedOnce = false;
    let isLoadingBoard = false;

    const i18n = {
        kitchen: '<?= esc(lang('app.kitchen')) ?>',
        table: '<?= esc(lang('app.table')) ?>',
        order: '<?= esc(lang('app.order')) ?>',
        noItems: '<?= esc(lang('app.no_items')) ?>',
        saveFailed: '<?= esc(lang('app.save_failed')) ?>',
        sentLabel: '<?= esc(lang('app.sent')) ?>',
        servedLabel: '<?= esc(lang('app.served')) ?>',
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

    function diffMinutes(value) {
        if (!value) {
            return '';
        }

        const d = new Date(String(value).replace(' ', 'T'));
        if (isNaN(d.getTime())) {
            return '';
        }

        const now = new Date();
        const diffMs = now.getTime() - d.getTime();
        const mins = Math.floor(diffMs / 60000);

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

    function normalizeBoardStatus(item) {
        const boardStatus = String(item.board_status || '').toLowerCase().trim();
        if (boardStatus === 'new' || boardStatus === 'preparing' || boardStatus === 'ready' || boardStatus === 'served') {
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
                label: i18n.actionStart,
                className: 'btn-warning'
            };
        }

        if (boardStatus === 'preparing') {
            return {
                nextStatus: 'ready',
                label: i18n.actionReady,
                className: 'btn-info text-dark'
            };
        }

        if (boardStatus === 'ready') {
            return {
                nextStatus: 'served',
                label: i18n.actionServed,
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
                class="btn btn-sm ${action.className} mt-2 me-1 kitchen-status-btn"
                data-item-id="${itemId}"
                data-status="${escapeHtml(action.nextStatus)}">
                ${escapeHtml(action.label)}
            </button>
        `;
    }

    function renderCard(item) {
        const locale = '<?= esc(service('request')->getLocale()) ?>';

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

        const actions = renderActionButtons(item);

        return `
            <div class="border rounded-3 p-3 mb-3 bg-white shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="fw-semibold">${escapeHtml(i18n.table)}: ${escapeHtml(tableName)}</div>
                        <div class="small text-muted">${escapeHtml(i18n.order)}: ${escapeHtml(orderNo)}</div>
                    </div>
                    <span class="badge bg-secondary">${escapeHtml(stationName)}</span>
                </div>

                <div class="fw-semibold mb-1">${escapeHtml(item.product_name || '-')}</div>

                ${item.item_detail ? `<div class="small text-muted mt-1">${escapeHtml(item.item_detail)}</div>` : ''}
                ${item.note ? `<div class="small text-danger mt-1">${escapeHtml(item.note)}</div>` : ''}

                <div class="mt-2 d-flex flex-wrap gap-2">
                    <span class="badge bg-dark">x ${escapeHtml(item.qty || 1)}</span>
                    ${ageText ? `<span class="badge bg-warning text-dark">${escapeHtml(ageText)}</span>` : ''}
                </div>

                <div class="small text-muted mt-2">
                    <div>${escapeHtml(i18n.sentLabel)}: ${escapeHtml(sentAt)}</div>
                    ${item.served_at ? `<div>${escapeHtml(i18n.servedLabel)}: ${escapeHtml(servedAt)}</div>` : ''}
                </div>

                <div class="mt-2">${actions}</div>
            </div>
        `;
    }

    function renderColumn(targetId, rows) {
        const el = document.getElementById(targetId);
        if (!el) {
            return;
        }

        if (!rows || !rows.length) {
            el.innerHTML = `<div class="text-muted small">${escapeHtml(i18n.noItems)}</div>`;
            return;
        }

        el.innerHTML = rows.map(renderCard).join('');
    }

    function updateCounts(data) {
        document.getElementById('count-new').textContent = (data.new || []).length;
        document.getElementById('count-preparing').textContent = (data.preparing || []).length;
        document.getElementById('count-ready').textContent = (data.ready || []).length;
        document.getElementById('count-served').textContent = (data.served || []).length;
    }

    function playBeep() {
        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) {
                return;
            }

            const ctx = new AudioContextClass();
            const oscillator = ctx.createOscillator();
            const gainNode = ctx.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, ctx.currentTime);
            gainNode.gain.setValueAtTime(0.08, ctx.currentTime);

            oscillator.connect(gainNode);
            gainNode.connect(ctx.destination);

            oscillator.start();
            oscillator.stop(ctx.currentTime + 0.18);
        } catch (e) {
            console.error('Kitchen alert sound error:', e);
        }
    }

    async function loadBoard() {
        if (isLoadingBoard) {
            return;
        }

        isLoadingBoard = true;

        const params = new URLSearchParams({
            station_id: stationFilter ? (stationFilter.value || '0') : '0',
            mode: modeFilter ? (modeFilter.value || 'all') : 'all'
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

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.kitchen-status-btn[data-item-id][data-status]');
        if (!btn) {
            return;
        }

        updateStatus(btn.getAttribute('data-item-id'), btn.getAttribute('data-status'), btn);
    });

    if (stationFilter) {
        stationFilter.addEventListener('change', loadBoard);
    }

    if (modeFilter) {
        modeFilter.addEventListener('change', loadBoard);
    }

    loadBoard();
    setInterval(loadBoard, pollSeconds * 1000);
})();
</script>

<?= $this->endSection() ?>