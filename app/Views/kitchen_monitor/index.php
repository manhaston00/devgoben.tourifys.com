<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
$kdsLocale = (string) (service('request')->getLocale() ?: 'th');
if (! function_exists('kds_lang')) {
    function kds_lang(string $key, string $th, string $en, ?string $locale = null): string
    {
        $locale = $locale ?: (string) (service('request')->getLocale() ?: 'th');
        $text = lang($key);

        if (is_string($text) && $text !== '' && $text !== $key) {
            return $text;
        }

        return $locale === 'th' ? $th : $en;
    }
}
?>

<div class="card card-soft kitchen-monitor-page" id="kitchenMonitorPage">
    <div class="card-body">
        <div id="kdsHeaderBlock" class="kds-header-block">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 kds-top-toolbar">
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

                <button type="button" class="btn btn-outline-secondary" id="servedHistoryBtn">
                    <?= esc(kds_lang('app.kds_history', 'ประวัติย้อนหลัง', 'History', $kdsLocale)) ?>
                </button>

                <button type="button" class="btn btn-outline-dark" id="compactHeaderBtn">
                    <?= esc(kds_lang('app.kds_compact_header', 'โหมดย่อส่วนบน', 'Compact header', $kdsLocale)) ?>
                </button>

                <button type="button" class="btn btn-dark" id="focusModeBtn">
                    <?= esc(lang('app.kitchen_focus_mode')) ?>
                </button>

                <button type="button" class="btn btn-outline-dark" id="fullscreenBtn">
                    <?= esc(lang('app.kitchen_fullscreen')) ?>
                </button>
            </div>
        </div>

        <div class="row g-3 mb-3" id="kdsSummaryRow">
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

            <div class="col-xl-3 col-md-6">
                <div class="kds-summary-card kds-summary-card-cancel-request">
                    <div class="kds-summary-label"><?= esc(kds_lang('app.kds_cancel_requests', 'คำขอยกเลิก', 'Cancel requests', $kdsLocale)) ?></div>
                    <div class="kds-summary-number" id="summary-cancel-request">0</div>
                    <div class="kds-summary-help"><?= esc(kds_lang('app.kds_cancel_requests_help', 'รายการที่รอครัวอนุมัติหรือปฏิเสธ', 'Items waiting for kitchen approval or rejection', $kdsLocale)) ?></div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="kds-summary-card kds-summary-card-served">
                    <div class="kds-summary-label"><?= esc(kds_lang('app.kds_served', 'เสิร์ฟแล้ว', 'Served', $kdsLocale)) ?></div>
                    <div class="kds-summary-number" id="summary-served">0</div>
                    <div class="kds-summary-help"><?= esc(kds_lang('app.kds_served_help', 'รายการที่เสิร์ฟแล้วและยังแสดงในบอร์ดด้านขวา', 'Served items currently visible on the right-side board', $kdsLocale)) ?></div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="kds-summary-card kds-summary-card-cancelled">
                    <div class="kds-summary-label"><?= esc(kds_lang('app.kds_cancelled', 'ยกเลิกแล้ว', 'Cancelled', $kdsLocale)) ?></div>
                    <div class="kds-summary-number" id="summary-cancelled">0</div>
                    <div class="kds-summary-help"><?= esc(kds_lang('app.kds_cancelled_help', 'รายการที่อนุมัติยกเลิกแล้วในประวัติย้อนหลัง', 'Items approved as cancelled in history', $kdsLocale)) ?></div>
                </div>
            </div>
        </div>

        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 kds-sticky-filter-bar" id="kdsStickyFilterBar">
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
                <button type="button" class="btn btn-outline-danger kitchen-filter-btn" data-filter="cancel_request">
                    <?= esc(kds_lang('app.kds_cancel_requests', 'คำขอยกเลิก', 'Cancel requests', $kdsLocale)) ?>
                </button>
                <button type="button" class="btn btn-outline-success kitchen-filter-btn" data-filter="served">
                    <?= esc(kds_lang('app.kds_served', 'เสิร์ฟแล้ว', 'Served', $kdsLocale)) ?>
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
            <div class="col-xxl kds-col-wrap" data-col="new">
                <div class="card border-0 shadow-sm h-100 kds-column-card">
                    <div class="card-header kds-col-header d-flex justify-content-between align-items-center">
                        <span><?= esc(lang('app.status_new')) ?></span>
                        <span class="badge bg-secondary kds-col-count" id="count-new">0</span>
                    </div>
                    <div class="card-body bg-light kds-column-body" id="col-new"></div>
                </div>
            </div>

            <div class="col-xxl kds-col-wrap" data-col="preparing">
                <div class="card border-0 shadow-sm h-100 kds-column-card">
                    <div class="card-header kds-col-header d-flex justify-content-between align-items-center">
                        <span><?= esc(lang('app.status_preparing')) ?></span>
                        <span class="badge bg-warning text-dark kds-col-count" id="count-preparing">0</span>
                    </div>
                    <div class="card-body bg-light kds-column-body" id="col-preparing"></div>
                </div>
            </div>

            <div class="col-xxl kds-col-wrap" data-col="ready">
                <div class="card border-0 shadow-sm h-100 kds-column-card">
                    <div class="card-header kds-col-header d-flex justify-content-between align-items-center">
                        <span><?= esc(lang('app.status_ready')) ?></span>
                        <span class="badge bg-info text-dark kds-col-count" id="count-ready">0</span>
                    </div>
                    <div class="card-body bg-light kds-column-body" id="col-ready"></div>
                </div>
            </div>

            <div class="col-xxl kds-col-wrap" data-col="cancel_request">
                <div class="card border-0 shadow-sm h-100 kds-column-card">
                    <div class="card-header kds-col-header d-flex justify-content-between align-items-center">
                        <span><?= esc(kds_lang('app.kds_cancel_requests', 'คำขอยกเลิก', 'Cancel requests', $kdsLocale)) ?></span>
                        <span class="badge bg-danger kds-col-count" id="count-cancel-request">0</span>
                    </div>
                    <div class="card-body bg-light kds-column-body" id="col-cancel-request"></div>
                </div>
            </div>

            <div class="col-xxl kds-col-wrap" data-col="served">
                <div class="card border-0 shadow-sm h-100 kds-column-card">
                    <div class="card-header kds-col-header d-flex justify-content-between align-items-center">
                        <span><?= esc(kds_lang('app.kds_served', 'เสิร์ฟแล้ว', 'Served', $kdsLocale)) ?></span>
                        <div class="d-flex align-items-center gap-2"><span class="badge bg-success kds-col-count" id="count-served">0</span><span class="small text-muted"><?= esc(kds_lang('app.kds_recent_only', 'ล่าสุด', 'Recent', $kdsLocale)) ?></span></div>
                    </div>
                    <div class="card-body bg-light kds-column-body" id="col-served"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="servedHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="servedHistoryTitle"><?= esc(kds_lang('app.kds_history', 'ประวัติย้อนหลัง', 'History', $kdsLocale)) ?></h5>
                    <div class="small text-muted" id="servedHistorySubTitle"><?= esc(lang('app.kitchen_monitor_desc')) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-lg-7">
                        <div class="input-group">
                            <span class="input-group-text">🔎</span>
                            <input type="text" class="form-control" id="servedHistorySearchInput" placeholder="<?= esc(lang('app.kitchen_search_placeholder')) ?>">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <select class="form-select" id="servedHistoryRange">
                            <option value="today"><?= esc(kds_lang('app.kds_today', 'วันนี้', 'Today', $kdsLocale)) ?></option>
                            <option value="1h"><?= esc(kds_lang('app.kds_last_1_hour', '1 ชั่วโมงล่าสุด', 'Last 1 hour', $kdsLocale)) ?></option>
                            <option value="3h"><?= esc(kds_lang('app.kds_last_3_hours', '3 ชั่วโมงล่าสุด', 'Last 3 hours', $kdsLocale)) ?></option>
                            <option value="all"><?= esc(lang('app.all') ?? 'All') ?></option>
                        </select>
                    </div>
                    <div class="col-lg-2 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary w-100" id="servedHistoryClearBtn"><?= esc(lang('app.clear')) ?></button>
                    </div>
                </div>

                <ul class="nav nav-tabs served-history-tabs mb-3" id="servedHistoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="served-history-tab" data-bs-toggle="tab" data-bs-target="#served-history-pane" type="button" role="tab" aria-controls="served-history-pane" aria-selected="true">
                            <?= esc(kds_lang('app.kds_served', 'เสิร์ฟแล้ว', 'Served', $kdsLocale)) ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="cancelled-history-tab" data-bs-toggle="tab" data-bs-target="#cancelled-history-pane" type="button" role="tab" aria-controls="cancelled-history-pane" aria-selected="false">
                            <?= esc(kds_lang('app.kds_cancelled', 'ยกเลิกแล้ว', 'Cancelled', $kdsLocale)) ?>
                        </button>
                    </li>
                </ul>

                <div class="served-history-meta mb-3" id="servedHistoryMeta"></div>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="served-history-pane" role="tabpanel" aria-labelledby="served-history-tab" tabindex="0">
                        <div class="served-history-list" id="servedHistoryList"></div>
                    </div>
                    <div class="tab-pane fade" id="cancelled-history-pane" role="tabpanel" aria-labelledby="cancelled-history-tab" tabindex="0">
                        <div class="served-history-list" id="cancelledHistoryList"></div>
                    </div>
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

    #kdsSummaryRow {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 1rem;
    }

    #kdsSummaryRow > [class*="col-"] {
        width: auto;
        max-width: none;
        padding-left: 0;
        padding-right: 0;
    }

    #kdsBoard {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 1rem;
        align-items: start;
    }

    #kdsBoard > .kds-col-wrap {
        width: 100%;
        margin: 0;
    }

    #kdsBoard > .kds-col-wrap .kds-column-card {
        height: 100%;
    }

    @media (min-width: 768px) and (max-width: 1399.98px) {
        #kdsBoard > .kds-col-wrap[data-col="served"] .kds-column-body {
            max-height: 56vh;
        }
    }

    @media (max-width: 1399.98px) {
        #kdsSummaryRow {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 991.98px) {
        #kdsSummaryRow {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        #kdsSummaryRow {
            grid-template-columns: 1fr;
        }
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

    .kds-summary-card-cancel-request::before {
        background: #dc3545;
    }

    .kds-summary-card-served::before {
        background: #198754;
    }

    .kds-summary-card-cancelled::before {
        background: #6f42c1;
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

    .kds-summary-card-cancel-request .kds-summary-number {
        color: #b91c1c;
    }

    .kds-summary-card-served .kds-summary-number {
        color: #15803d;
    }

    .kds-summary-card-cancelled .kds-summary-number {
        color: #7c3aed;
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

    .kds-card.kds-new-attention {
        border: 2px solid #2563eb;
        background: #eff6ff;
        animation: kdsPulseBlue 1.25s infinite;
    }

    .kds-card.kds-warning-flash {
        animation: kdsPulseOrange 1.35s infinite;
    }

    .kds-card.kds-danger-flash {
        animation: kdsPulseRed 1s infinite;
    }

    .kds-attention-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin: 10px 0 6px;
    }

    .kds-attention-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 800;
        line-height: 1;
        letter-spacing: .1px;
    }

    .kds-attention-chip-new {
        background: #dbeafe;
        color: #1d4ed8;
        border: 1px solid #93c5fd;
    }

    .kds-attention-chip-do {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #f59e0b;
    }

    .kds-attention-chip-serve {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #22c55e;
    }

    .kds-attention-chip-warn {
        background: #ffedd5;
        color: #c2410c;
        border: 1px solid #fb923c;
    }

    .kds-attention-chip-danger {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #ef4444;
    }

    .kds-help-line {
        margin-top: 8px;
        font-size: 12px;
        font-weight: 700;
        color: #6b7280;
    }

    .kitchen-cancel-btn {
        width: 100%;
        min-height: 50px;
        font-size: 18px;
        font-weight: 800;
        border-radius: 12px;
        margin-top: 10px !important;
    }

    @keyframes kdsPulseBlue {
        0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.45); }
        70% { box-shadow: 0 0 0 10px rgba(37, 99, 235, 0); }
        100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
    }

    @keyframes kdsPulseOrange {
        0% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.40); }
        70% { box-shadow: 0 0 0 12px rgba(249, 115, 22, 0); }
        100% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0); }
    }

    @keyframes kdsPulseRed {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.45); }
        70% { box-shadow: 0 0 0 12px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
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

    .kds-sticky-filter-bar {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #fff;
        padding: 0 0 6px;
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

    .kds-move-box {
        border: 1px dashed rgba(255, 193, 7, 0.55);
        background: rgba(255, 193, 7, 0.08);
        border-radius: 10px;
        padding: 8px 10px;
        margin-top: 10px;
    }

    .kds-merge-box {
        border: 1px dashed rgba(13, 110, 253, 0.55);
        background: rgba(13, 110, 253, 0.08);
        border-radius: 10px;
        padding: 8px 10px;
        margin-top: 10px;
    }

    .kds-cancel-request-box {
        border: 1px dashed rgba(220, 53, 69, 0.55);
        background: rgba(220, 53, 69, 0.08);
        border-radius: 10px;
        padding: 8px 10px;
        margin-top: 10px;
    }

    .kds-cancel-decision-actions {
        display: grid;
        gap: 8px;
        margin-top: 10px;
    }

    .kds-cancel-decision-btn {
        width: 100%;
        min-height: 46px;
        font-size: 16px;
        font-weight: 800;
        border-radius: 12px;
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

    .kds-top-toolbar {
        transition: all .2s ease;
    }

    .kds-header-block {
        transition: all .2s ease;
    }

    .kds-sticky-filter-bar {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #fff;
        padding-top: 4px;
        padding-bottom: 8px;
    }

    .kds-compact .kitchen-monitor-page .card-body {
        padding-top: 12px;
    }

    .kds-compact #kdsHeaderBlock {
        margin-bottom: 8px !important;
    }

    .kds-compact .kds-top-toolbar {
        gap: .5rem !important;
        margin-bottom: .5rem !important;
        align-items: center !important;
    }

    .kds-compact .kds-top-toolbar > div:first-child {
        display: none !important;
    }

    .kds-compact .kds-top-toolbar .d-flex.align-items-center.gap-2.flex-wrap {
        width: 100%;
    }

    .kds-compact .kds-search-group {
        min-width: 280px;
        flex: 1 1 320px;
    }

    .kds-compact .kds-station-filter {
        min-width: 200px;
    }

    .kds-compact #kdsSummaryRow {
        display: none !important;
    }

    .kds-compact .kds-sticky-filter-bar {
        position: sticky;
        top: 0;
        z-index: 11;
        background: #fff;
        padding: 8px 0 10px;
        margin-bottom: 10px !important;
        border-bottom: 1px solid rgba(0,0,0,.06);
    }

    .kds-compact .kds-column-body {
        height: 78vh;
    }

    .kds-compact .kds-col-header {
        padding: 8px 12px;
    }

    .served-history-meta {
        font-size: 13px;
        color: #6b7280;
    }

    .served-history-card {
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 14px;
        background: #fff;
        padding: 12px 14px;
        margin-bottom: 12px;
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.04);
    }

    .served-history-card .title {
        font-size: 18px;
        font-weight: 800;
        line-height: 1.2;
    }

    .served-history-card .sub {
        color: #6b7280;
        font-size: 13px;
    }

    .served-history-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid rgba(0,0,0,.08);
        background: #f8fafc;
    }

    .served-history-empty {
        border: 1px dashed rgba(0,0,0,.12);
        border-radius: 14px;
        padding: 20px;
        text-align: center;
        color: #6b7280;
        background: #fafafa;
    }

    .served-history-tabs .nav-link {
        font-weight: 800;
        border-radius: 12px 12px 0 0;
    }
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const stationFilter = document.getElementById('stationFilter');
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const refreshBoardBtn = document.getElementById('refreshBoardBtn');
    const boardRoot = document.getElementById('kdsBoard');
    const servedHistoryBtn = document.getElementById('servedHistoryBtn');
    const compactHeaderBtn = document.getElementById('compactHeaderBtn');
    const focusModeBtn = document.getElementById('focusModeBtn');
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    const hideEmptyColumns = document.getElementById('hideEmptyColumns');
    const servedHistorySearchInput = document.getElementById('servedHistorySearchInput');
    const servedHistoryRange = document.getElementById('servedHistoryRange');
    const servedHistoryClearBtn = document.getElementById('servedHistoryClearBtn');
    const servedHistoryMeta = document.getElementById('servedHistoryMeta');
    const servedHistoryList = document.getElementById('servedHistoryList');
    const cancelledHistoryList = document.getElementById('cancelledHistoryList');
    const servedHistoryModalEl = document.getElementById('servedHistoryModal');
    const servedHistoryModal = servedHistoryModalEl ? new bootstrap.Modal(servedHistoryModalEl) : null;
    const pollSeconds = <?= (int) ($pollingSeconds ?? 5) ?>;

    let lastNewCount = 0;
    let boardLoadedOnce = false;
    let isLoadingBoard = false;
    let quickFilter = 'all';
    let focusMode = false;
    let compactMode = false;
    let servedHistoryRows = [];
    let lastBoardData = { new: [], preparing: [], ready: [], cancel_request: [], served: [] };

    const locale = '<?= esc(service('request')->getLocale()) ?>';
    const isThaiLocale = locale === 'th';

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
        actionServed: '<?= esc(lang('app.kitchen_action_served')) ?>',
        movedTable: '<?= esc(lang('app.moved_table')) ?>',
        moveNote: '<?= esc(lang('app.move_note')) ?>',
        mergedBill: '<?= esc(lang('app.merged_bill')) ?>',
        serveToTable: '<?= esc(lang('app.serve_to_table')) ?>',
        mergedFromTable: '<?= esc(lang('app.merged_from_table')) ?>',
        mergedTargetOrder: '<?= esc(lang('app.merged_target_order')) ?>',
        mergeReason: '<?= esc(lang('app.merge_reason')) ?>',
        cancelAction: '<?= esc(lang('app.cancel')) ?>',
        cancelConfirm: '<?= esc(lang('app.cancel_confirm')) ?>',
        cancelApproveConfirm: <?= json_encode(kds_lang('app.kds_confirm_cancel_approve', 'ยืนยันอนุมัติการยกเลิกรายการนี้?', 'Confirm approving this cancel request?', $kdsLocale)) ?>,
        cancelRejectConfirm: <?= json_encode(kds_lang('app.kds_confirm_cancel_reject', 'ยืนยันปฏิเสธการยกเลิกรายการนี้?', 'Confirm rejecting this cancel request?', $kdsLocale)) ?>,
        cancelRequestPending: <?= json_encode(kds_lang('app.kds_pending_cancel_request', 'รออนุมัติยกเลิก', 'Pending cancel request', $kdsLocale)) ?>,
        previousStatus: <?= json_encode(kds_lang('app.kds_previous_status', 'สถานะก่อนหน้า', 'Previous status', $kdsLocale)) ?>,
        cancelReason: <?= json_encode(kds_lang('app.kds_reason', 'เหตุผล', 'Reason', $kdsLocale)) ?>,
        requestedAt: <?= json_encode(kds_lang('app.kds_requested_at', 'ขอเมื่อ', 'Requested at', $kdsLocale)) ?>,
        newItem: '<?= esc(lang('app.new_item')) ?>',
        waitingLong: '<?= esc(lang('app.waiting_long')) ?>',
        veryLate: '<?= esc(lang('app.very_late')) ?>',
        doNow: '<?= esc(lang('app.do_now')) ?>',
        rushNow: '<?= esc(lang('app.rush_now')) ?>',
        serveNow: '<?= esc(lang('app.serve_now')) ?>',
        doneState: '<?= esc(lang('app.done_state')) ?>',
        waitingHint: '<?= esc(lang('app.waiting_hint')) ?>',
        newHint: '<?= esc(lang('app.new_hint')) ?>',
        readyHint: '<?= esc(lang('app.ready_hint')) ?>',
        servedHint: '<?= esc(lang('app.served_hint')) ?>',
        preparingHint: '<?= esc(lang('app.preparing_hint')) ?>',
        cancelRequestHint: <?= json_encode(kds_lang('app.kds_cancel_request_hint', 'ตรวจสอบและตัดสินใจคำขอยกเลิกนี้', 'Please review and decide this cancel request', $kdsLocale)) ?>,
        compactEnter: '<?= esc(lang('app.compact_enter')) ?>',
        compactExit: '<?= esc(lang('app.compact_exit')) ?>',
        servedHistory: '<?= esc(lang('app.served_history')) ?>',
        servedHistoryEmpty: '<?= esc(lang('app.served_history_empty')) ?>',
        servedCount: '<?= esc(lang('app.served_count')) ?>',
        lastUpdated: '<?= esc(lang('app.last_updated')) ?>',
        timeRangeToday: '<?= esc(lang('app.time_range_today')) ?>',
        timeRange1h: '<?= esc(lang('app.time_range_1h')) ?>',
        timeRange3h: '<?= esc(lang('app.time_range_3h')) ?>',
        timeRangeAll: '<?= esc(lang('app.time_range_all')) ?>',
        noteCashierReview: '<?= esc(lang('app.note_cashier_review')) ?>',
        historyTable: '<?= esc(lang('app.history_table')) ?>',
        historyServedAt: '<?= esc(lang('app.history_served_at')) ?>',
        cancelRequestApprove: '<?= esc(lang('app.cancel_request_approve')) ?>',
        cancelRequestReject: '<?= esc(lang('app.cancel_request_reject')) ?>',
        cancelRequestApproved: '<?= esc(lang('app.cancel_request_approved')) ?>',
        cancelRequestRejected: '<?= esc(lang('app.cancel_request_rejected')) ?>',
        historyReason: '<?= esc(lang('app.history_reason')) ?>'
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
        if (['new', 'preparing', 'ready', 'served', 'cancel_request'].includes(boardStatus)) {
            return boardStatus;
        }

        const requestStatus = String(item.cancel_request_status || '').toLowerCase().trim();
        if (requestStatus === 'pending' || requestStatus === 'requested' || requestStatus === 'waiting') {
            return 'cancel_request';
        }

        const itemStatus = String(item.item_status || item.status || '').toLowerCase().trim();
        const ticketStatus = String(item.ticket_status || '').toLowerCase().trim();

        if (itemStatus === 'served') {
            return 'served';
        }

        if (itemStatus === 'cancel' || itemStatus === 'cancelled' || itemStatus === 'canceled') {
            return 'served';
        }

        if (itemStatus === 'ready') {
            return 'ready';
        }

        if (itemStatus === 'cooking' || itemStatus === 'preparing' || itemStatus === 'doing') {
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

    function getAttentionMeta(item, boardStatus) {
        const attentionTime = boardStatus === 'served'
            ? (item.served_at || item.updated_at || '')
            : (item.sent_at || item.created_at || '');
        const ageMinutes = getAgeMinutes(attentionTime);
        const meta = {
            cardClass: '',
            chips: [],
            hint: ''
        };

        if (boardStatus === 'new') {
            meta.chips.push(`<span class="kds-attention-chip kds-attention-chip-do">🍳 ${escapeHtml(i18n.doNow)}</span>`);
            meta.hint = i18n.newHint;

            if (ageMinutes <= 2) {
                meta.cardClass += ' kds-new-attention';
                meta.chips.unshift(`<span class="kds-attention-chip kds-attention-chip-new">✨ ${escapeHtml(i18n.newItem)}</span>`);
            }
        } else if (boardStatus === 'preparing') {
            meta.hint = i18n.preparingHint;
        } else if (boardStatus === 'ready') {
            meta.chips.push(`<span class="kds-attention-chip kds-attention-chip-serve">🛎 ${escapeHtml(i18n.serveNow)}</span>`);
            meta.hint = i18n.readyHint;
        } else if (boardStatus === 'cancel_request') {
            meta.chips.push(`<span class="kds-attention-chip kds-attention-chip-danger">🛑 ${escapeHtml(i18n.cancelRequestPending)}</span>`);
            meta.hint = i18n.cancelRequestHint;
        } else if (boardStatus === 'served') {
            meta.hint = i18n.servedHint;
        }

        if (boardStatus !== 'served') {
            if (ageMinutes >= 16) {
                meta.cardClass += ' kds-danger-flash';
                meta.chips.push(`<span class="kds-attention-chip kds-attention-chip-danger">⏰ ${escapeHtml(i18n.veryLate)}</span>`);
                meta.hint = i18n.waitingHint;
            } else if (ageMinutes >= 6) {
                meta.cardClass += ' kds-warning-flash';
                meta.chips.push(`<span class="kds-attention-chip kds-attention-chip-warn">⏳ ${escapeHtml(i18n.waitingLong)}</span>`);
                if (boardStatus === 'preparing') {
                    meta.chips.push(`<span class="kds-attention-chip kds-attention-chip-do">🔥 ${escapeHtml(i18n.rushNow)}</span>`);
                }
                meta.hint = i18n.waitingHint;
            }
        }

        if (boardStatus === 'served') {
            meta.chips.push(`<span class="kds-attention-chip kds-attention-chip-serve">✅ ${escapeHtml(i18n.doneState)}</span>`);
        }

        return meta;
    }

    function renderActionButtons(item) {
        const boardStatus = normalizeBoardStatus(item);
        const action = getActionConfig(boardStatus);

        const itemId = Number(item.order_item_id || item.item_id || 0);
        if (!itemId) {
            return '';
        }

        const requestStatus = String(item.cancel_request_status || '').toLowerCase().trim();
        let html = '';

        if (boardStatus === 'cancel_request' && requestStatus === 'pending' && (item.show_cancel_decision_actions === undefined || Number(item.show_cancel_decision_actions) === 1 || item.show_cancel_decision_actions === true || item.show_cancel_decision_actions === '1')) {
            html += `
                <div class="kds-cancel-decision-actions">
                    <button
                        type="button"
                        class="btn btn-danger kds-cancel-decision-btn kitchen-status-btn"
                        data-item-id="${itemId}"
                        data-status="cancel_approved">
                        ${escapeHtml(i18n.cancelRequestApprove)}
                    </button>

                    <button
                        type="button"
                        class="btn btn-outline-secondary kds-cancel-decision-btn kitchen-status-btn"
                        data-item-id="${itemId}"
                        data-status="cancel_rejected">
                        ${escapeHtml(i18n.cancelRequestReject)}
                    </button>
                </div>
            `;
            return html;
        }

        if (action) {
            html += `
                <button
                    type="button"
                    class="btn ${action.className} kitchen-status-btn"
                    data-item-id="${itemId}"
                    data-status="${escapeHtml(action.nextStatus)}">
                    ${escapeHtml(action.label)}
                </button>
            `;
        }

        if (boardStatus !== 'served' && boardStatus !== 'cancel_request') {
            html += `
                <button
                    type="button"
                    class="btn btn-danger kitchen-cancel-btn"
                    data-item-id="${itemId}"
                    data-status="cancel">
                    ❌ ${escapeHtml(i18n.cancelAction)}
                </button>
            `;
        }

        return html;
    }

    function searchableText(item) {
        return [
            item.table_name || '',
            item.order_number || '',
            item.ticket_no || '',
            item.product_name || '',
            item.item_detail || '',
            item.note || '',
            item.moved_from_table_name || '',
            item.moved_to_table_name || '',
            item.moved_reason || '',
            item.merged_from_table_name || '',
            item.merged_to_table_name || '',
            item.merged_target_order_number || '',
            item.merged_reason || ''
        ].join(' ').toLowerCase();
    }

    function translatePrevStatus(status) {
        const normalized = String(status || '').toLowerCase().trim();

        if (normalized === 'sent') {
            return locale === 'th' ? 'ส่งครัวแล้ว' : 'Sent to kitchen';
        }

        if (normalized === 'pending' || normalized === 'new') {
            return locale === 'th' ? 'ใหม่' : 'New';
        }

        if (normalized === 'preparing' || normalized === 'cooking' || normalized === 'doing') {
            return locale === 'th' ? 'กำลังทำ' : 'Preparing';
        }

        if (normalized === 'ready') {
            return locale === 'th' ? 'พร้อมเสิร์ฟ' : 'Ready';
        }

        if (normalized === 'served') {
            return locale === 'th' ? 'เสิร์ฟแล้ว' : 'Served';
        }

        if (normalized === 'cancel' || normalized === 'cancelled' || normalized === 'canceled') {
            return locale === 'th' ? 'ยกเลิกแล้ว' : 'Cancelled';
        }

        return normalized ? normalized.toUpperCase() : (locale === 'th' ? 'เสิร์ฟแล้ว' : 'Served');
    }

    function renderCancelRequestBox(item, boardStatus) {
        const requestStatus = String(item.cancel_request_status || '').toLowerCase().trim();
        if (!requestStatus) {
            return '';
        }

        const reason = item.cancel_request_reason || item.cancel_request_note || '';
        const requestedAt = item.cancel_requested_at || item.requested_at || '';
        const previousStatus = String(item.cancel_request_prev_status || item.previous_status || item.item_status || '').toLowerCase().trim();
        const previousStatusLabel = translatePrevStatus(previousStatus);

        if (boardStatus === 'cancel_request' || requestStatus === 'pending') {
            return `
                <div class="kds-cancel-request-box">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge text-bg-danger">${escapeHtml(i18n.cancelRequestPending)}</span>
                        <span class="small fw-semibold">${escapeHtml(i18n.previousStatus)}: ${escapeHtml(previousStatusLabel)}</span>
                    </div>
                    ${reason ? `<div class="small text-muted mt-1">${escapeHtml(i18n.cancelReason)}: ${escapeHtml(reason)}</div>` : ''}
                    ${requestedAt ? `<div class="small text-muted mt-1">${escapeHtml(i18n.requestedAt)}: ${escapeHtml(formatDateTime(requestedAt))}</div>` : ''}
                </div>
            `;
        }

        if (requestStatus === 'rejected') {
            return `
                <div class="kds-cancel-request-box">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge text-bg-danger">${escapeHtml(i18n.cancelRequestRejected)}</span>
                    </div>
                    ${reason ? `<div class="small text-muted mt-1">${escapeHtml(i18n.cancelReason)}: ${escapeHtml(reason)}</div>` : ''}
                </div>
            `;
        }

        if (requestStatus === 'approved') {
            return `
                <div class="kds-cancel-request-box">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge text-bg-success">${escapeHtml(i18n.cancelRequestApproved)}</span>
                    </div>
                    ${reason ? `<div class="small text-muted mt-1">${escapeHtml(i18n.cancelReason)}: ${escapeHtml(reason)}</div>` : ''}
                </div>
            `;
        }

        return '';
    }

    function renderCard(item, indexInColumn) {
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
        const ageReference = boardStatus === 'served'
            ? (item.served_at || item.updated_at || '')
            : (item.sent_at || item.created_at || '');
        const ageText = ageReference ? diffMinutes(ageReference) : '';
        const ageClass = ageReference ? ageBadgeClass(ageReference) : 'kds-age-normal';
        const urgencyClass = boardStatus === 'served'
            ? ''
            : (ageReference ? cardUrgencyClass(ageReference) : '');
        const actions = renderActionButtons(item);
        const searchText = searchableText(item);
        const queueNo = Number(indexInColumn || 0) + 1;
        const attentionMeta = getAttentionMeta(item, boardStatus);

        const movedFrom = item.moved_from_table_name || '';
        const movedTo = item.moved_to_table_name || '';
        const movedReason = item.moved_reason || '';

        const mergedFrom = item.merged_from_table_name || '';
        const mergedTo = item.merged_to_table_name || '';
        const mergedTargetOrderNo = item.merged_target_order_number || '';
        const mergedReason = item.merged_reason || '';
        const isMerged = Number(item.is_merged || 0) === 1 || (!!mergedFrom && !!mergedTo && mergedFrom !== mergedTo);

        const moveInfoHtml = (movedFrom && movedTo && movedFrom !== movedTo) ? `
            <div class="kds-move-box">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge text-bg-warning">${escapeHtml(i18n.movedTable)}</span>
                    <span class="small fw-semibold">${escapeHtml(movedFrom)} → ${escapeHtml(movedTo)}</span>
                </div>
                ${movedReason ? `
                    <div class="small text-muted mt-1">
                        ${escapeHtml(i18n.moveNote)}: ${escapeHtml(movedReason)}
                    </div>
                ` : ''}
            </div>
        ` : '';

        const mergeInfoHtml = isMerged ? `
            <div class="kds-merge-box">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge text-bg-primary">${escapeHtml(i18n.mergedBill)}</span>
                    <span class="small fw-semibold">
                        ${escapeHtml(i18n.mergedFromTable)}: ${escapeHtml(mergedFrom || tableName)}
                    </span>
                    <span class="small fw-semibold">
                        → ${escapeHtml(i18n.serveToTable)}: ${escapeHtml(mergedTo || tableName)}
                    </span>
                </div>

                ${mergedTargetOrderNo ? `
                    <div class="small text-muted mt-1">
                        ${escapeHtml(i18n.mergedTargetOrder)}: ${escapeHtml(mergedTargetOrderNo)}
                    </div>
                ` : ''}

                ${mergedReason ? `
                    <div class="small text-muted mt-1">
                        ${escapeHtml(i18n.mergeReason)}: ${escapeHtml(mergedReason)}
                    </div>
                ` : ''}
            </div>
        ` : '';

        return `
            <div
                class="kds-card ${urgencyClass}${attentionMeta.cardClass} p-3 mb-3"
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

                ${moveInfoHtml}
                ${mergeInfoHtml}

                ${renderCancelRequestBox(item, boardStatus)}

                <div class="kds-item-title mt-2 mb-2">${escapeHtml(item.product_name || '-')}</div>

                ${item.item_detail ? `<div class="kds-item-sub text-muted mt-1">${escapeHtml(item.item_detail)}</div>` : ''}
                ${item.note ? `<div class="kds-item-sub text-danger mt-1 fw-semibold">${escapeHtml(item.note)}</div>` : ''}

                <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge bg-dark kds-queue-badge">${escapeHtml(i18n.itemCountLabel)} x ${escapeHtml(item.qty || 1)}</span>
                    ${ageText ? `<span class="badge ${ageClass} kds-queue-badge">${escapeHtml(ageText)}</span>` : ''}
                </div>

                ${attentionMeta.chips.length ? `<div class="kds-attention-strip">${attentionMeta.chips.join('')}</div>` : ''}
                ${attentionMeta.hint ? `<div class="kds-help-line">${escapeHtml(attentionMeta.hint)}</div>` : ''}

                <div class="kds-meta text-muted mt-3">
                    <div>${escapeHtml(i18n.sentLabel)}: ${escapeHtml(sentAt)}</div>
                    ${item.served_at ? `<div>${escapeHtml(i18n.servedLabel)}: ${escapeHtml(servedAt)}</div>` : ''}
                </div>

                <div>${actions}</div>
            </div>
        `;
    }

    function rebucketBoardData(data) {
        const source = data || {};
        const merged = []
            .concat(source.new || [])
            .concat(source.preparing || [])
            .concat(source.ready || [])
            .concat(source.cancel_request || [])
            .concat(source.served || []);

        const bucketed = {
            new: [],
            preparing: [],
            ready: [],
            cancel_request: [],
            served: []
        };

        merged.forEach((item) => {
            const bucket = normalizeBoardStatus(item);
            if (!bucketed[bucket]) {
                bucketed[bucket] = [];
            }
            bucketed[bucket].push(item);
        });

        return bucketed;
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

    function updateCounts(data, summaryMeta = {}) {
    const newCount = Number(summaryMeta.new ?? ((data.new || []).length));
    const preparingCount = Number(summaryMeta.preparing ?? ((data.preparing || []).length));
    const readyCount = Number(summaryMeta.ready ?? ((data.ready || []).length));
    const cancelRequestCount = Number(summaryMeta.cancel_request ?? ((data.cancel_request || []).length));
    const servedCount = Number(summaryMeta.served ?? ((data.served || []).length));
    const cancelledCount = Number(summaryMeta.cancelled ?? 0);
    const totalActive = Number(summaryMeta.active_total ?? (newCount + preparingCount + readyCount + cancelRequestCount));

    const countNewEl = document.getElementById('count-new');
    const countPreparingEl = document.getElementById('count-preparing');
    const countReadyEl = document.getElementById('count-ready');
    const countCancelRequestEl = document.getElementById('count-cancel-request');
    const countServedEl = document.getElementById('count-served');

    if (countNewEl) countNewEl.textContent = newCount;
    if (countPreparingEl) countPreparingEl.textContent = preparingCount;
    if (countReadyEl) countReadyEl.textContent = readyCount;
    if (countCancelRequestEl) countCancelRequestEl.textContent = cancelRequestCount;
    if (countServedEl) countServedEl.textContent = servedCount;

    const summaryTotalEl = document.getElementById('summary-total-active');
    const summaryNewEl = document.getElementById('summary-new');
    const summaryPreparingEl = document.getElementById('summary-preparing');
    const summaryReadyEl = document.getElementById('summary-ready');
    const summaryCancelRequestEl = document.getElementById('summary-cancel-request');
    const summaryServedEl = document.getElementById('summary-served');
    const summaryCancelledEl = document.getElementById('summary-cancelled');

    if (summaryTotalEl) summaryTotalEl.textContent = totalActive;
    if (summaryNewEl) summaryNewEl.textContent = newCount;
    if (summaryPreparingEl) summaryPreparingEl.textContent = preparingCount;
    if (summaryReadyEl) summaryReadyEl.textContent = readyCount;
    if (summaryCancelRequestEl) summaryCancelRequestEl.textContent = cancelRequestCount;
    if (summaryServedEl) summaryServedEl.textContent = servedCount;
    if (summaryCancelledEl) summaryCancelledEl.textContent = cancelledCount;
}

    function playBeep() {
        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) {
                return;
            }

            const ctx = new AudioContextClass();
            const compressor = ctx.createDynamicsCompressor();
            const masterGain = ctx.createGain();

            compressor.threshold.setValueAtTime(-18, ctx.currentTime);
            compressor.knee.setValueAtTime(18, ctx.currentTime);
            compressor.ratio.setValueAtTime(10, ctx.currentTime);
            compressor.attack.setValueAtTime(0.003, ctx.currentTime);
            compressor.release.setValueAtTime(0.25, ctx.currentTime);

            masterGain.gain.setValueAtTime(0.9, ctx.currentTime);
            compressor.connect(masterGain);
            masterGain.connect(ctx.destination);

            const tones = [
                { freq: 1046, start: 0.00, end: 0.22, gain: 0.22 },
                { freq: 1318, start: 0.26, end: 0.48, gain: 0.22 },
                { freq: 1567, start: 0.52, end: 0.82, gain: 0.24 }
            ];

            tones.forEach(function (toneCfg) {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'square';
                osc.frequency.setValueAtTime(toneCfg.freq, ctx.currentTime + toneCfg.start);

                gain.gain.setValueAtTime(0.0001, ctx.currentTime + toneCfg.start);
                gain.gain.exponentialRampToValueAtTime(toneCfg.gain, ctx.currentTime + toneCfg.start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + toneCfg.end);

                osc.connect(gain);
                gain.connect(compressor);

                osc.start(ctx.currentTime + toneCfg.start);
                osc.stop(ctx.currentTime + toneCfg.end + 0.02);
            });
        } catch (e) {
            console.error('Kitchen alert sound error:', e);
        }
    }

    function updateBoardAutoLayout() {
        if (!boardRoot) {
            return;
        }

        const visibleCols = Array.from(document.querySelectorAll('.kds-col-wrap')).filter((col) => !col.classList.contains('is-hidden'));
        const visibleCount = Math.max(visibleCols.length, 1);
        const ww = window.innerWidth || document.documentElement.clientWidth || 0;
        let template = '';

        if (ww <= 767.98) {
            const mobileCols = visibleCount >= 2 ? 2 : 1;
            template = `repeat(${mobileCols}, minmax(0, 1fr))`;
            visibleCols.forEach((col) => {
                const key = String(col.getAttribute('data-col') || '');
                if (key === 'cancel_request' || key === 'served') {
                    col.style.gridColumn = '1 / -1';
                } else {
                    col.style.gridColumn = '';
                }
            });
        } else if (ww <= 1399.98) {
            template = `repeat(${visibleCount}, minmax(220px, 1fr))`;
            visibleCols.forEach((col) => {
                col.style.gridColumn = '';
            });
        } else {
            template = `repeat(${visibleCount}, minmax(0, 1fr))`;
            visibleCols.forEach((col) => {
                col.style.gridColumn = '';
            });
        }

        boardRoot.style.gridTemplateColumns = template;
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

        updateBoardAutoLayout();
    }

    function getServedHistoryStorageKey() {
        const branch = <?= json_encode((string) (session('branch_id') ?? '0')) ?>;
        const tenant = <?= json_encode((string) (session('tenant_id') ?? '0')) ?>;
        return `kds_served_history_${tenant}_${branch}`;
    }

    function restoreServedHistory() {
        try {
            const raw = sessionStorage.getItem(getServedHistoryStorageKey());
            servedHistoryRows = raw ? JSON.parse(raw) : [];
            if (!Array.isArray(servedHistoryRows)) {
                servedHistoryRows = [];
            }
        } catch (e) {
            servedHistoryRows = [];
        }
    }

    function persistServedHistory() {
        try {
            sessionStorage.setItem(getServedHistoryStorageKey(), JSON.stringify(servedHistoryRows.slice(0, 200)));
        } catch (e) {
            console.error('Served history storage error:', e);
        }
    }

    function normalizeServedHistoryItem(row) {
        const itemId = Number(row.order_item_id || row.item_id || row.id || 0);
        const rawStatus = String(row.item_status || row.status || row.board_status || '').toLowerCase();
        const cancelRequestStatus = String(row.cancel_request_status || '').toLowerCase();
        let historyStatus = 'served';

        if (rawStatus === 'cancel' || rawStatus === 'cancelled' || rawStatus === 'canceled' || cancelRequestStatus === 'approved') {
            historyStatus = 'cancelled';
        } else if (cancelRequestStatus === 'rejected') {
            historyStatus = 'cancel_rejected';
        } else if (rawStatus === 'served') {
            historyStatus = 'served';
        }

        return {
            history_key: `${itemId || 0}`,
            item_id: itemId,
            order_number: row.order_number || row.ticket_no || '#',
            table_name: row.table_name || '-',
            product_name: row.product_name || '-',
            item_detail: row.item_detail || '',
            note: row.note || row.cancel_request_reason || row.cancel_request_note || row.cancel_reason || '',
            qty: Number(row.qty || 1),
            station_name: row.station_display_name || row.station_name || i18n.kitchen,
            served_at: row.served_at || row.updated_at || row.sent_at || '',
            sent_at: row.sent_at || '',
            decided_at: row.cancel_decided_at || row.decided_at || row.updated_at || '',
            board_status: normalizeBoardStatus(row),
            item_status: rawStatus,
            cancel_request_status: cancelRequestStatus,
            history_status: historyStatus
        };
    }

    function syncServedHistory(rows) {
        const incoming = (rows || []).map(normalizeServedHistoryItem).filter((row) => Number(row.item_id || 0) > 0);
        if (!incoming.length) {
            return;
        }

        const map = new Map();

        (servedHistoryRows || []).forEach((row) => {
            map.set(String(row.history_key || row.item_id || ''), row);
        });

        incoming.forEach((row) => {
            const key = String(row.history_key || row.item_id || '');
            const existing = map.get(key);

            if (!existing) {
                map.set(key, row);
                return;
            }

            map.set(key, {
                ...existing,
                ...row,
                history_status: existing.history_status && existing.history_status !== 'served'
                    ? existing.history_status
                    : row.history_status,
                decided_at: existing.decided_at || row.decided_at || '',
                note: existing.note || row.note || ''
            });
        });

        servedHistoryRows = Array.from(map.values())
            .sort((a, b) => {
                const da = new Date(String(a.served_at || a.decided_at || '').replace(' ', 'T')).getTime() || 0;
                const db = new Date(String(b.served_at || b.decided_at || '').replace(' ', 'T')).getTime() || 0;
                return db - da;
            })
            .slice(0, 300);

        persistServedHistory();
        renderServedHistory();
    }

    function filterServedHistoryRows(historyTab = 'all') {
    const keyword = String(servedHistorySearchInput ? servedHistorySearchInput.value : '').trim().toLowerCase();
    const range = String(servedHistoryRange ? servedHistoryRange.value : 'today');
    const now = Date.now();

    return servedHistoryRows.filter((row) => {
        const compareTimeRaw = row.served_at || row.decided_at || '';
        const compareTime = new Date(String(compareTimeRaw).replace(' ', 'T')).getTime() || 0;

        let passRange = true;
        if (range === 'today') {
            const d = new Date();
            const start = new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
            passRange = compareTime >= start;
        } else if (range === '1h') {
            passRange = compareTime >= (now - (60 * 60 * 1000));
        } else if (range === '3h') {
            passRange = compareTime >= (now - (3 * 60 * 60 * 1000));
        }

        const hay = [row.table_name, row.order_number, row.product_name, row.item_detail, row.note, row.station_name].join(' ').toLowerCase();
        const passKeyword = !keyword || hay.includes(keyword);
        const rowStatus = String(row.history_status || '').toLowerCase();
        const rowTab = rowStatus === 'served' ? 'served' : 'cancelled';
        const passTab = historyTab === 'all' || rowTab === historyTab;

        return passRange && passKeyword && passTab;
    });
}

    function getServedHistoryStatusChip(row) {
        const status = String(row.history_status || '').toLowerCase();

        if (status === 'cancelled') {
            const decidedAt = row.decided_at ? ` · ${escapeHtml(formatDateTime(row.decided_at))}` : '';
            return `<div class="served-history-chip border-danger text-danger">❌ ${escapeHtml(i18n.cancelRequestApproved)}${decidedAt}</div>`;
        }

        if (status === 'cancel_rejected') {
            const decidedAt = row.decided_at ? ` · ${escapeHtml(formatDateTime(row.decided_at))}` : '';
            return `<div class="served-history-chip border-secondary text-secondary">🚫 ${escapeHtml(i18n.cancelRequestRejected)}${decidedAt}</div>`;
        }

        return `<div class="served-history-chip">✅ ${escapeHtml(i18n.historyServedAt)}: ${escapeHtml(formatDateTime(row.served_at))}</div>`;
    }

    function updateServedHistoryStatus(itemId, historyStatus, extra = {}) {
        const normalizedId = Number(itemId || 0);
        if (normalizedId <= 0) {
            return;
        }

        let changed = false;

        servedHistoryRows = Array.isArray(servedHistoryRows) ? servedHistoryRows : [];

        servedHistoryRows = servedHistoryRows.map((row) => {
            if (Number(row.item_id || 0) !== normalizedId) {
                return row;
            }

            changed = true;

            return {
                ...row,
                history_status: historyStatus,
                item_status: historyStatus === 'cancelled'
                    ? 'cancel'
                    : (historyStatus === 'cancel_rejected' ? 'served' : row.item_status),
                cancel_request_status: historyStatus === 'cancelled'
                    ? 'approved'
                    : (historyStatus === 'cancel_rejected' ? 'rejected' : row.cancel_request_status),
                decided_at: extra.decided_at || row.decided_at || formatDateTime(new Date()),
                note: extra.note || row.note || ''
            };
        });

        if (!changed) {
            const candidates = [
                ...(lastBoardData.new || []),
                ...(lastBoardData.preparing || []),
                ...(lastBoardData.ready || []),
                ...(lastBoardData.cancel_request || []),
                ...(lastBoardData.served || [])
            ];

            const matched = candidates.find((row) => Number(row.order_item_id || row.item_id || row.id || 0) === normalizedId);

            if (matched) {
                const base = normalizeServedHistoryItem(matched);

                servedHistoryRows.unshift({
                    ...base,
                    history_status: historyStatus,
                    item_status: historyStatus === 'cancelled' ? 'cancel' : 'served',
                    cancel_request_status: historyStatus === 'cancelled' ? 'approved' : 'rejected',
                    decided_at: extra.decided_at || base.decided_at || formatDateTime(new Date()),
                    note: extra.note || base.note || ''
                });

                changed = true;
            }
        }

        if (changed) {
            servedHistoryRows = servedHistoryRows
                .sort((a, b) => {
                    const da = new Date(String(a.served_at || a.decided_at || '').replace(' ', 'T')).getTime() || 0;
                    const db = new Date(String(b.served_at || b.decided_at || '').replace(' ', 'T')).getTime() || 0;
                    return db - da;
                })
                .slice(0, 300);

            persistServedHistory();
            renderServedHistory();
        }
    }

    function renderServedHistory() {
    if (!servedHistoryList || !cancelledHistoryList || !servedHistoryMeta) {
        return;
    }

    const servedRows = filterServedHistoryRows('served');
    const cancelledRows = filterServedHistoryRows('cancelled');
    const allRows = [...servedRows, ...cancelledRows].sort((a, b) => {
        const da = new Date(String(a.served_at || a.decided_at || '').replace(' ', 'T')).getTime() || 0;
        const db = new Date(String(b.served_at || b.decided_at || '').replace(' ', 'T')).getTime() || 0;
        return db - da;
    });

    const latestRow = allRows.length ? allRows[0] : null;
    const latest = latestRow ? formatDateTime(latestRow.served_at || latestRow.decided_at || '') : '-';

    servedHistoryMeta.innerHTML = `${escapeHtml(i18n.servedCount)}: <strong>${servedRows.length}</strong> · <?= esc(kds_lang('app.kds_cancelled', 'ยกเลิกแล้ว', 'Cancelled', $kdsLocale)) ?>: <strong>${cancelledRows.length}</strong> · ${escapeHtml(i18n.lastUpdated)}: <strong>${escapeHtml(latest)}</strong> · ${escapeHtml(i18n.noteCashierReview)}`;

    if (!servedRows.length) {
        servedHistoryList.innerHTML = `<div class="served-history-empty">${escapeHtml(i18n.servedHistoryEmpty)}</div>`;
    } else {
        servedHistoryList.innerHTML = servedRows.map((row) => `
            <div class="served-history-card">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                    <div>
                        <div class="title">${escapeHtml(row.product_name)}</div>
                        <div class="sub">${escapeHtml(i18n.historyTable)}: ${escapeHtml(row.table_name)} · ${escapeHtml(i18n.order)}: ${escapeHtml(row.order_number)}</div>
                    </div>
                    <div class="text-end">
                        ${getServedHistoryStatusChip(row)}
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="served-history-chip">${escapeHtml(i18n.itemCountLabel)} x ${escapeHtml(row.qty)}</span>
                    <span class="served-history-chip">${escapeHtml(row.station_name)}</span>
                </div>
                ${row.item_detail ? `<div class="sub mb-1">${escapeHtml(row.item_detail)}</div>` : ''}
                ${row.note ? `<div class="sub text-danger fw-semibold">${escapeHtml(i18n.historyReason)}: ${escapeHtml(row.note)}</div>` : ''}
            </div>
        `).join('');
    }

    if (!cancelledRows.length) {
        cancelledHistoryList.innerHTML = `<div class="served-history-empty"><?= esc(kds_lang('app.kds_no_cancelled_history', 'ยังไม่มีรายการยกเลิกย้อนหลัง', 'No cancelled items in history yet', $kdsLocale)) ?></div>`;
    } else {
        cancelledHistoryList.innerHTML = cancelledRows.map((row) => `
            <div class="served-history-card">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                    <div>
                        <div class="title">${escapeHtml(row.product_name)}</div>
                        <div class="sub">${escapeHtml(i18n.historyTable)}: ${escapeHtml(row.table_name)} · ${escapeHtml(i18n.order)}: ${escapeHtml(row.order_number)}</div>
                    </div>
                    <div class="text-end">
                        ${getServedHistoryStatusChip(row)}
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="served-history-chip">${escapeHtml(i18n.itemCountLabel)} x ${escapeHtml(row.qty)}</span>
                    <span class="served-history-chip">${escapeHtml(row.station_name)}</span>
                </div>
                ${row.item_detail ? `<div class="sub mb-1">${escapeHtml(row.item_detail)}</div>` : ''}
                ${row.note ? `<div class="sub text-danger fw-semibold">${escapeHtml(i18n.historyReason)}: ${escapeHtml(row.note)}</div>` : ''}
            </div>
        `).join('');
    }
}

    function openServedHistory() {
        renderServedHistory();
        if (servedHistoryModal) {
            servedHistoryModal.show();
        }
    }

    function collectServedHistoryCandidates(data) {
        const allRows = [
            ...(Array.isArray(data.new) ? data.new : []),
            ...(Array.isArray(data.preparing) ? data.preparing : []),
            ...(Array.isArray(data.ready) ? data.ready : []),
            ...(Array.isArray(data.cancel_request) ? data.cancel_request : []),
            ...(Array.isArray(data.served) ? data.served : [])
        ];

        return allRows.filter((row) => {
            const reqStatus = String(row.cancel_request_status || '').toLowerCase().trim();
            const itemStatus = String(row.item_status || row.status || '').toLowerCase().trim();
            const boardStatus = String(row.board_status || '').toLowerCase().trim();

            return boardStatus === 'served'
                || reqStatus === 'approved'
                || reqStatus === 'rejected'
                || itemStatus === 'served'
                || itemStatus === 'cancel'
                || itemStatus === 'cancelled'
                || itemStatus === 'canceled';
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

        const rawData = json.data || {};
        const historyMeta = (json.meta && json.meta.history) ? json.meta.history : { served: [], cancelled: [] };
        const summaryMeta = (json.meta && json.meta.summary) ? json.meta.summary : {};
        const boardSettings = (json.meta && json.meta.settings) ? json.meta.settings : {};
        const data = rebucketBoardData(rawData);
        const servedBoardLimit = Number(boardSettings.served_board_limit || 30);
        if (Array.isArray(data.served)) {
            data.served = data.served.slice(0, servedBoardLimit);
        }

        lastBoardData = data;
        syncServedHistory([...(historyMeta.served || []), ...(historyMeta.cancelled || [])]);

        const newCount = (data.new || []).length;

        renderColumn('col-new', data.new || []);
        renderColumn('col-preparing', data.preparing || []);
        renderColumn('col-ready', data.ready || []);
        renderColumn('col-cancel-request', data.cancel_request || []);
        renderColumn('col-served', data.served || []);
        updateCounts(data, summaryMeta);
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

        const normalizedStatus = String(status).toLowerCase();

        if (normalizedStatus === 'cancel') {
            const ok = window.confirm(i18n.cancelConfirm);
            if (!ok) {
                return;
            }
        }

        if (normalizedStatus === 'cancel_approved') {
            const ok = window.confirm(i18n.cancelApproveConfirm);
            if (!ok) {
                return;
            }
        }

        if (normalizedStatus === 'cancel_rejected') {
            const ok = window.confirm(i18n.cancelRejectConfirm);
            if (!ok) {
                return;
            }
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
                if (normalizedStatus === 'cancel_approved') {
                    updateServedHistoryStatus(itemId, 'cancelled');
                } else if (normalizedStatus === 'cancel_rejected') {
                    updateServedHistoryStatus(itemId, 'cancel_rejected');
                }
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
            } else if (filter === 'cancel_request') {
                el.className = 'btn btn-outline-danger kitchen-filter-btn';
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
        } else if (activeFilter === 'cancel_request') {
            activeBtn.className = 'btn btn-danger kitchen-filter-btn active';
        } else if (activeFilter === 'served') {
            activeBtn.className = 'btn btn-success kitchen-filter-btn active';
        }
    }

    function applyCompactMode(enabled) {
        compactMode = !!enabled;
        document.body.classList.toggle('kds-compact', compactMode);
        if (compactHeaderBtn) {
            compactHeaderBtn.textContent = compactMode ? i18n.compactExit : i18n.compactEnter;
            compactHeaderBtn.className = compactMode ? 'btn btn-primary' : 'btn btn-outline-dark';
        }
        try {
            localStorage.setItem('kds_compact_mode', compactMode ? '1' : '0');
        } catch (e) {
            console.error('Compact mode storage error:', e);
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
        const btn = e.target.closest('.kitchen-status-btn[data-item-id][data-status], .kitchen-cancel-btn[data-item-id][data-status]');
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

    if (servedHistoryBtn) {
        servedHistoryBtn.addEventListener('click', openServedHistory);
    }

    if (compactHeaderBtn) {
        compactHeaderBtn.addEventListener('click', function () {
            applyCompactMode(!compactMode);
        });
    }

    if (servedHistorySearchInput) {
        servedHistorySearchInput.addEventListener('input', renderServedHistory);
    }

    if (servedHistoryRange) {
        servedHistoryRange.addEventListener('change', renderServedHistory);
    }


    if (servedHistoryClearBtn) {
        servedHistoryClearBtn.addEventListener('click', function () {
            if (servedHistorySearchInput) {
                servedHistorySearchInput.value = '';
            }
            if (servedHistoryRange) {
                servedHistoryRange.value = 'today';
            }
            renderServedHistory();
        });
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

    window.addEventListener('resize', updateBoardAutoLayout);

    try {
        if (localStorage.getItem('kds_focus_mode') === '1') {
            applyFocusMode(true);
        }
        if (localStorage.getItem('kds_compact_mode') === '1') {
            applyCompactMode(true);
        }
    } catch (e) {
        console.error('Mode restore error:', e);
    }

    restoreServedHistory();
    renderServedHistory();
    applyFilterButtonStyles('all');
    updateBoardAutoLayout();
    updateFullscreenButton();
    loadBoard();
    setInterval(loadBoard, pollSeconds * 1000);
})();
</script>
<?= $this->endSection() ?>