<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
    $cashierOrders   = $cashierOrders ?? [];
    $cashierSummary  = $cashierSummary ?? ['orders' => 0, 'billing' => 0, 'open' => 0, 'sales_total' => 0];
    $cashierOrdersJs = json_encode($cashierOrders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<style>
    .cashier-page-title {
        font-size: 1.35rem;
        font-weight: 800;
        margin-bottom: 4px;
        line-height: 1.25;
    }

    .cashier-page-subtitle {
        color: #6b7280;
        font-size: .92rem;
    }

    .cashier-shell {
        display: grid;
        grid-template-columns: minmax(320px, 430px) minmax(0, 1fr);
        gap: 16px;
        align-items: stretch;
    }

    .cashier-panel {
        border: 1px solid var(--app-border);
        border-radius: 22px;
        background: #fff;
        box-shadow: var(--app-shadow-sm);
        overflow: hidden;
        min-height: calc(100vh - 210px);
    }

    .cashier-panel-head {
        padding: 18px 18px 14px;
        border-bottom: 1px solid rgba(15, 23, 42, .06);
        background: linear-gradient(180deg, rgba(248,250,252,.94), rgba(255,255,255,.98));
    }

    .cashier-panel-body {
        padding: 16px;
    }

    .cashier-summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-top: 14px;
    }

    .cashier-summary-card {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 18px;
        padding: 12px 14px;
        background: linear-gradient(180deg, rgba(255,255,255,1), rgba(248,250,252,1));
    }

    .cashier-summary-label {
        color: #6b7280;
        font-size: .78rem;
        margin-bottom: 4px;
    }

    .cashier-summary-value {
        font-size: 1.15rem;
        font-weight: 800;
        line-height: 1.2;
        color: #111827;
    }

    .cashier-toolbar {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .cashier-search {
        position: relative;
        flex: 1 1 220px;
    }

    .cashier-search input {
        border-radius: 14px;
        padding-left: 42px;
        min-height: 44px;
    }

    .cashier-search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: .92rem;
    }

    .cashier-status-filters {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .cashier-chip {
        border: 1px solid rgba(15, 23, 42, .08);
        background: #fff;
        color: #334155;
        border-radius: 999px;
        padding: 8px 14px;
        font-size: .82rem;
        font-weight: 700;
        cursor: pointer;
        transition: all .15s ease;
    }

    .cashier-chip.active {
        background: #111827;
        color: #fff;
        border-color: #111827;
    }

    .cashier-order-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: calc(100vh - 360px);
        overflow: auto;
        padding-right: 4px;
    }

    .cashier-order-card {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 20px;
        padding: 14px;
        background: #fff;
        cursor: pointer;
        transition: all .16s ease;
    }

    .cashier-order-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, .06);
    }

    .cashier-order-card.active {
        border-color: rgba(37, 99, 235, .35);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
        background: linear-gradient(180deg, rgba(239,246,255,1), rgba(255,255,255,1));
    }

    .cashier-order-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 10px;
    }

    .cashier-order-no {
        font-weight: 800;
        font-size: .96rem;
        line-height: 1.2;
        color: #111827;
    }

    .cashier-order-table {
        color: #475569;
        font-size: .83rem;
        margin-top: 4px;
    }

    .cashier-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 800;
        padding: 6px 10px;
        white-space: nowrap;
    }

    .cashier-badge.open {
        background: rgba(14, 165, 233, .12);
        color: #0369a1;
    }

    .cashier-badge.billing {
        background: rgba(245, 158, 11, .12);
        color: #b45309;
    }

    .cashier-badge.pending {
        background: rgba(100, 116, 139, .12);
        color: #475569;
    }

    .cashier-badge.preparing {
        background: rgba(59, 130, 246, .12);
        color: #1d4ed8;
    }

    .cashier-badge.ready {
        background: rgba(16, 185, 129, .12);
        color: #047857;
    }

    .cashier-badge.served {
        background: rgba(15, 23, 42, .12);
        color: #0f172a;
    }

    .cashier-badge.cancelled {
        background: rgba(239, 68, 68, .12);
        color: #b91c1c;
    }

    .cashier-meta-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin-top: 12px;
    }

    .cashier-meta-box {
        border-radius: 14px;
        background: #f8fafc;
        padding: 10px 12px;
    }

    .cashier-meta-label {
        color: #64748b;
        font-size: .72rem;
        margin-bottom: 3px;
    }

    .cashier-meta-value {
        color: #111827;
        font-size: .84rem;
        font-weight: 800;
    }

    .cashier-detail-wrap {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .cashier-detail-card {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 20px;
        padding: 16px;
        background: #fff;
    }

    .cashier-detail-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .cashier-detail-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: #111827;
        line-height: 1.2;
    }

    .cashier-detail-subtitle {
        color: #64748b;
        font-size: .86rem;
        margin-top: 4px;
    }

    .cashier-info-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }

    .cashier-info-box {
        border-radius: 16px;
        padding: 12px 14px;
        background: #f8fafc;
        border: 1px solid rgba(15, 23, 42, .05);
    }

    .cashier-info-box .label {
        color: #64748b;
        font-size: .73rem;
        margin-bottom: 4px;
    }

    .cashier-info-box .value {
        color: #0f172a;
        font-size: .9rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .cashier-item-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .cashier-item-row {
        border: 1px solid rgba(15, 23, 42, .07);
        border-radius: 18px;
        padding: 12px 14px;
        background: #fff;
    }

    .cashier-item-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 6px;
    }

    .cashier-item-name {
        font-weight: 800;
        color: #111827;
        line-height: 1.25;
        margin-bottom: 3px;
    }

    .cashier-item-extra,
    .cashier-item-note {
        color: #64748b;
        font-size: .82rem;
        line-height: 1.35;
    }

    .cashier-item-price {
        text-align: right;
        min-width: 94px;
    }

    .cashier-item-price .qty {
        color: #64748b;
        font-size: .78rem;
        margin-bottom: 2px;
    }

    .cashier-item-price .total {
        color: #0f172a;
        font-weight: 800;
    }

    .cashier-payment-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(320px, .9fr);
        gap: 14px;
        align-items: start;
    }

    .cashier-total-stack {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .cashier-total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px dashed rgba(15, 23, 42, .08);
    }

    .cashier-total-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .cashier-total-row.grand {
        padding-top: 12px;
    }

    .cashier-total-row .label {
        color: #64748b;
    }

    .cashier-total-row .value {
        color: #111827;
        font-weight: 800;
    }

    .cashier-total-row.grand .label,
    .cashier-total-row.grand .value {
        font-size: 1.05rem;
    }

    .cashier-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .cashier-method-grid,
    .cashier-quick-grid,
    .cashier-action-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .cashier-method-btn,
    .cashier-quick-btn {
        border-radius: 14px;
        min-height: 44px;
        font-weight: 700;
    }

    .cashier-method-btn.active {
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    .cashier-empty {
        border: 1px dashed rgba(15, 23, 42, .15);
        border-radius: 18px;
        padding: 36px 18px;
        text-align: center;
        color: #64748b;
        background: #fff;
    }

    @media (max-width: 1399.98px) {
        .cashier-info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 1199.98px) {
        .cashier-shell {
            grid-template-columns: 1fr;
        }

        .cashier-panel {
            min-height: auto;
        }

        .cashier-order-list {
            max-height: 420px;
        }
    }

    @media (max-width: 991.98px) {
        .cashier-payment-layout {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .cashier-summary-grid,
        .cashier-meta-grid,
        .cashier-info-grid,
        .cashier-method-grid,
        .cashier-quick-grid,
        .cashier-action-grid {
            grid-template-columns: 1fr 1fr;
        }

        .cashier-panel-head,
        .cashier-panel-body {
            padding-left: 14px;
            padding-right: 14px;
        }

        .cashier-detail-card {
            padding: 14px;
        }
    }

    @media (max-width: 575.98px) {
        .cashier-summary-grid,
        .cashier-meta-grid,
        .cashier-info-grid,
        .cashier-method-grid,
        .cashier-quick-grid,
        .cashier-action-grid {
            grid-template-columns: 1fr;
        }

        .cashier-toolbar {
            flex-direction: column;
        }

        .cashier-order-top,
        .cashier-item-top,
        .cashier-detail-head {
            flex-direction: column;
            align-items: stretch;
        }

        .cashier-item-price {
            text-align: left;
            min-width: auto;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
        <div class="cashier-page-title"><?= esc(lang('app.cashier_screen_title')) ?></div>
        <div class="cashier-page-subtitle"><?= esc(lang('app.cashier_screen_desc')) ?></div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= site_url('pos') ?>" class="btn btn-outline-secondary rounded-pill px-3">
            <?= esc(lang('app.cashier_back_to_tables')) ?>
        </a>
        <button type="button" class="btn btn-primary rounded-pill px-3" id="cashierRefreshBtn">
            <?= esc(lang('app.refresh')) ?>
        </button>
    </div>
</div>

<div class="cashier-shell">
    <div class="cashier-panel">
        <div class="cashier-panel-head">
            <div class="fw-bold fs-5"><?= esc(lang('app.cashier_live_orders')) ?></div>

            <div class="cashier-summary-grid">
                <div class="cashier-summary-card">
                    <div class="cashier-summary-label"><?= esc(lang('app.cashier_summary_orders')) ?></div>
                    <div class="cashier-summary-value" id="cashierSummaryOrders"><?= (int) ($cashierSummary['orders'] ?? 0) ?></div>
                </div>
                <div class="cashier-summary-card">
                    <div class="cashier-summary-label"><?= esc(lang('app.cashier_summary_pending_pay')) ?></div>
                    <div class="cashier-summary-value" id="cashierSummaryBilling"><?= (int) ($cashierSummary['billing'] ?? 0) ?></div>
                </div>
                <div class="cashier-summary-card">
                    <div class="cashier-summary-label"><?= esc(lang('app.cashier_summary_sales_total')) ?></div>
                    <div class="cashier-summary-value" id="cashierSummarySales">฿<?= number_format((float) ($cashierSummary['sales_total'] ?? 0), 2) ?></div>
                </div>
            </div>
        </div>

        <div class="cashier-panel-body">
            <div class="cashier-toolbar">
                <div class="cashier-search">
                    <span class="cashier-search-icon">⌕</span>
                    <input type="text" class="form-control" id="cashierSearchInput" placeholder="<?= esc(lang('app.cashier_search_placeholder')) ?>">
                </div>
            </div>

            <div class="cashier-status-filters">
                <button type="button" class="cashier-chip active" data-filter-status="all">
                    <?= esc(lang('app.cashier_all_statuses')) ?>
                </button>
                <button type="button" class="cashier-chip" data-filter-status="billing">
                    <?= esc(lang('app.cashier_status_billing')) ?>
                </button>
                <button type="button" class="cashier-chip" data-filter-status="open">
                    <?= esc(lang('app.cashier_status_open')) ?>
                </button>
            </div>

            <div class="cashier-order-list" id="cashierOrderList"></div>
        </div>
    </div>

    <div class="cashier-panel">
        <div class="cashier-panel-head">
            <div class="fw-bold fs-5"><?= esc(lang('app.cashier_order_detail')) ?></div>
            <div class="cashier-page-subtitle"><?= esc(lang('app.note_cashier_review')) ?></div>
        </div>

        <div class="cashier-panel-body">
            <div id="cashierDetailRoot" class="cashier-empty">
                <?= esc(lang('app.cashier_select_order')) ?>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const initialOrders = <?= $cashierOrdersJs ?: '[]' ?>;
    const lang = {
        cashierNoOrders: <?= json_encode(lang('app.cashier_no_orders'), JSON_UNESCAPED_UNICODE) ?>,
        cashierNoMatch: <?= json_encode(lang('app.cashier_no_match'), JSON_UNESCAPED_UNICODE) ?>,
        cashierSelectOrder: <?= json_encode(lang('app.cashier_select_order'), JSON_UNESCAPED_UNICODE) ?>,
        cashierOrderItems: <?= json_encode(lang('app.cashier_order_items'), JSON_UNESCAPED_UNICODE) ?>,
        cashierOrderSummary: <?= json_encode(lang('app.cashier_order_summary'), JSON_UNESCAPED_UNICODE) ?>,
        cashierQuickCash: <?= json_encode(lang('app.cashier_quick_cash'), JSON_UNESCAPED_UNICODE) ?>,
        cashierCustomAmount: <?= json_encode(lang('app.cashier_custom_amount'), JSON_UNESCAPED_UNICODE) ?>,
        cashierMarkBilling: <?= json_encode(lang('app.cashier_mark_billing'), JSON_UNESCAPED_UNICODE) ?>,
        cashierOpenPos: <?= json_encode(lang('app.cashier_open_pos'), JSON_UNESCAPED_UNICODE) ?>,
        cashierItemCount: <?= json_encode(lang('app.cashier_item_count'), JSON_UNESCAPED_UNICODE) ?>,
        cashierBillStatus: <?= json_encode(lang('app.cashier_bill_status'), JSON_UNESCAPED_UNICODE) ?>,
        cashierOpenedAt: <?= json_encode(lang('app.cashier_opened_at'), JSON_UNESCAPED_UNICODE) ?>,
        cashierReadyToPay: <?= json_encode(lang('app.cashier_ready_to_pay'), JSON_UNESCAPED_UNICODE) ?>,
        cashierRefreshOrders: <?= json_encode(lang('app.cashier_refresh_orders'), JSON_UNESCAPED_UNICODE) ?>,
        subtotal: <?= json_encode(lang('app.subtotal'), JSON_UNESCAPED_UNICODE) ?>,
        discount: <?= json_encode(lang('app.discount'), JSON_UNESCAPED_UNICODE) ?>,
        serviceCharge: <?= json_encode(lang('app.service_charge'), JSON_UNESCAPED_UNICODE) ?>,
        vat: <?= json_encode(lang('app.vat'), JSON_UNESCAPED_UNICODE) ?>,
        grandTotal: <?= json_encode(lang('app.grand_total'), JSON_UNESCAPED_UNICODE) ?>,
        amountDue: <?= json_encode(lang('app.amount_due'), JSON_UNESCAPED_UNICODE) ?>,
        paymentMethod: <?= json_encode(lang('app.payment_method'), JSON_UNESCAPED_UNICODE) ?>,
        receivedAmount: <?= json_encode(lang('app.received_amount'), JSON_UNESCAPED_UNICODE) ?>,
        change: <?= json_encode(lang('app.change'), JSON_UNESCAPED_UNICODE) ?>,
        closeBillPay: <?= json_encode(lang('app.close_bill_pay'), JSON_UNESCAPED_UNICODE) ?>,
        reopenBill: <?= json_encode(lang('app.reopen_bill'), JSON_UNESCAPED_UNICODE) ?>,
        reopenBillSuccess: <?= json_encode(lang('app.reopen_bill_success'), JSON_UNESCAPED_UNICODE) ?>,
        reopenBillConfirm: <?= json_encode(lang('app.reopen_bill_confirm'), JSON_UNESCAPED_UNICODE) ?>,
        reopenBillFailed: <?= json_encode(lang('app.reopen_bill_failed'), JSON_UNESCAPED_UNICODE) ?>,
        orderCannotReopenBill: <?= json_encode(lang('app.order_cannot_reopen_bill'), JSON_UNESCAPED_UNICODE) ?>,
        cash: <?= json_encode(lang('app.cash'), JSON_UNESCAPED_UNICODE) ?>,
        transfer: <?= json_encode(lang('app.transfer'), JSON_UNESCAPED_UNICODE) ?>,
        card: <?= json_encode(lang('app.card'), JSON_UNESCAPED_UNICODE) ?>,
        orderNotFound: <?= json_encode(lang('app.order_not_found'), JSON_UNESCAPED_UNICODE) ?>,
        saveSuccess: <?= json_encode(lang('app.save_success'), JSON_UNESCAPED_UNICODE) ?>,
        paymentSuccess: <?= json_encode(lang('app.payment_success'), JSON_UNESCAPED_UNICODE) ?>,
        statusOpen: <?= json_encode(lang('app.cashier_status_open'), JSON_UNESCAPED_UNICODE) ?>,
        statusBilling: <?= json_encode(lang('app.cashier_status_billing'), JSON_UNESCAPED_UNICODE) ?>,
        itemPending: <?= json_encode(lang('app.pending'), JSON_UNESCAPED_UNICODE) ?>,
        itemSent: <?= json_encode(lang('app.sent_to_kitchen'), JSON_UNESCAPED_UNICODE) ?>,
        itemPreparing: <?= json_encode(lang('app.preparing'), JSON_UNESCAPED_UNICODE) ?>,
        itemReady: <?= json_encode(lang('app.ready'), JSON_UNESCAPED_UNICODE) ?>,
        itemServed: <?= json_encode(lang('app.served'), JSON_UNESCAPED_UNICODE) ?>,
        itemCancelled: <?= json_encode(lang('app.cancelled'), JSON_UNESCAPED_UNICODE) ?>,
        openBillSuccessBilling: <?= json_encode(lang('app.close_bill_success_billing'), JSON_UNESCAPED_UNICODE) ?>,
        managerOverrideRequired: <?= json_encode(lang('app.manager_override_required'), JSON_UNESCAPED_UNICODE) ?>,
        managerOverrideHelpPinOnly: <?= json_encode(lang('app.manager_override_help_pin_only'), JSON_UNESCAPED_UNICODE) ?>,
        managerOverrideApproved: <?= json_encode(lang('app.manager_override_approved'), JSON_UNESCAPED_UNICODE) ?>,
        managerOverrideFailed: <?= json_encode(lang('app.manager_override_failed'), JSON_UNESCAPED_UNICODE) ?>,
        managerOverrideActionPay: <?= json_encode(lang('app.manager_override_action_pay'), JSON_UNESCAPED_UNICODE) ?>,
        managerOverrideActionCloseBill: <?= json_encode(lang('app.manager_override_action_close_bill'), JSON_UNESCAPED_UNICODE) ?>,
        managerOverrideActionReopenBill: <?= json_encode(lang('app.manager_override_action_reopen_bill'), JSON_UNESCAPED_UNICODE) ?>,
        by: <?= json_encode(lang('app.by'), JSON_UNESCAPED_UNICODE) ?>,
    };

    const cashierPermissions = <?= json_encode([
        'view' => (bool) ($cashierPermissions['view'] ?? false),
        'request_bill' => (bool) ($cashierPermissions['request_bill'] ?? false),
        'close_bill' => (bool) ($cashierPermissions['close_bill'] ?? false),
        'reopen_bill' => (bool) ($cashierPermissions['reopen_bill'] ?? false),
        'pay' => (bool) ($cashierPermissions['pay'] ?? false),
        'manager_override' => (bool) ($cashierPermissions['manager_override'] ?? false),
    ], JSON_UNESCAPED_UNICODE) ?>;

    const managerOverrideModalEl = document.getElementById('managerOverrideModal');
    const managerOverrideModal = managerOverrideModalEl ? new bootstrap.Modal(managerOverrideModalEl) : null;
    let managerOverrideResolver = null;

    const state = {
        orders: Array.isArray(initialOrders) ? initialOrders : [],
        search: '',
        filterStatus: 'all',
        selectedOrderId: null,
        selectedPayload: null,
        paymentMethod: 'cash',
    };

    const els = {
        orderList: document.getElementById('cashierOrderList'),
        detailRoot: document.getElementById('cashierDetailRoot'),
        searchInput: document.getElementById('cashierSearchInput'),
        refreshBtn: document.getElementById('cashierRefreshBtn'),
        summaryOrders: document.getElementById('cashierSummaryOrders'),
        summaryBilling: document.getElementById('cashierSummaryBilling'),
        summarySales: document.getElementById('cashierSummarySales'),
        statusChips: Array.from(document.querySelectorAll('[data-filter-status]')),
    };

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formatMoney(value) {
        const number = Number(value || 0);
        return `฿${number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function formatDateTime(value) {
        if (!value) {
            return '-';
        }

        const safeValue = String(value).replace(' ', 'T');
        const date = new Date(safeValue);

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleString();
    }

    function getOrderStatusLabel(status) {
        return status === 'billing' ? lang.statusBilling : lang.statusOpen;
    }

    function getItemStatusLabel(status) {
        const map = {
            pending: lang.itemPending,
            sent: lang.itemSent,
            preparing: lang.itemPreparing,
            ready: lang.itemReady,
            served: lang.itemServed,
            cancelled: lang.itemCancelled,
            cancel: lang.itemCancelled,
        };

        return map[status] || status || '-';
    }

    function getFilteredOrders() {
        const keyword = state.search.trim().toLowerCase();

        return state.orders.filter((order) => {
            const orderStatus = String(order.status || '').toLowerCase();
            const matchesStatus = state.filterStatus === 'all' ? true : orderStatus === state.filterStatus;

            if (!matchesStatus) {
                return false;
            }

            if (!keyword) {
                return true;
            }

            const haystack = [
                order.order_number,
                order.table_name,
                order.zone_name,
                order.status,
            ].join(' ').toLowerCase();

            return haystack.includes(keyword);
        });
    }

    function updateSummary() {
        const filtered = getFilteredOrders();
        const totalSales = filtered.reduce((sum, order) => sum + Number(order.display_total || 0), 0);
        const billingCount = filtered.filter((order) => String(order.status) === 'billing').length;

        els.summaryOrders.textContent = String(filtered.length);
        els.summaryBilling.textContent = String(billingCount);
        els.summarySales.textContent = formatMoney(totalSales);
    }

    function renderOrderList() {
        const filtered = getFilteredOrders();

        updateSummary();

        if (!filtered.length) {
            els.orderList.innerHTML = `
                <div class="cashier-empty">
                    ${escapeHtml(state.search || state.filterStatus !== 'all' ? lang.cashierNoMatch : lang.cashierNoOrders)}
                </div>
            `;
            return;
        }

        els.orderList.innerHTML = filtered.map((order) => {
            const activeClass = Number(order.id) === Number(state.selectedOrderId) ? 'active' : '';
            const counts = order.item_counts || {};
            const tableName = order.table_name || '-';
            const zoneName = order.zone_name || '-';

            return `
                <div class="cashier-order-card ${activeClass}" data-order-id="${Number(order.id)}">
                    <div class="cashier-order-top">
                        <div>
                            <div class="cashier-order-no">${escapeHtml(order.order_number || '-')}</div>
                            <div class="cashier-order-table">${escapeHtml(tableName)} • ${escapeHtml(zoneName)}</div>
                        </div>
                        <span class="cashier-badge ${escapeHtml(order.status || 'open')}">
                            ${escapeHtml(getOrderStatusLabel(order.status))}
                        </span>
                    </div>

                    <div class="cashier-meta-grid">
                        <div class="cashier-meta-box">
                            <div class="cashier-meta-label">${escapeHtml(lang.cashierItemCount)}</div>
                            <div class="cashier-meta-value">${Number(counts.all || 0)}</div>
                        </div>
                        <div class="cashier-meta-box">
                            <div class="cashier-meta-label">${escapeHtml(getItemStatusLabel('ready'))}</div>
                            <div class="cashier-meta-value">${Number(counts.ready || 0)}</div>
                        </div>
                        <div class="cashier-meta-box">
                            <div class="cashier-meta-label">${escapeHtml(lang.amountDue)}</div>
                            <div class="cashier-meta-value">${escapeHtml(formatMoney(order.display_total || 0))}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        els.orderList.querySelectorAll('[data-order-id]').forEach((node) => {
            node.addEventListener('click', () => {
                const orderId = Number(node.getAttribute('data-order-id') || 0);
                if (!orderId) {
                    return;
                }
                state.selectedOrderId = orderId;
                renderOrderList();
                loadOrder(orderId);
            });
        });
    }


    function managerOverrideActionLabel(actionKey) {
        if (actionKey === 'pay') {
            return lang.managerOverrideActionPay || lang.managerOverrideRequired;
        }
        if (actionKey === 'close_bill') {
            return lang.managerOverrideActionCloseBill || lang.managerOverrideRequired;
        }
        if (actionKey === 'reopen_bill') {
            return lang.managerOverrideActionReopenBill || lang.managerOverrideActionCloseBill || lang.managerOverrideRequired;
        }
        return lang.managerOverrideRequired;
    }

    function requestManagerOverride(actionKey, orderId) {
        return new Promise((resolve) => {
            managerOverrideResolver = resolve;
            document.getElementById('managerOverrideAction').value = String(actionKey || '');
            document.getElementById('managerOverrideOrderId').value = String(orderId || 0);
            document.getElementById('managerOverridePinCode').value = '';
            document.getElementById('managerOverrideHelpText').textContent = `${lang.managerOverrideHelpPinOnly || lang.managerOverrideRequired} - ${managerOverrideActionLabel(actionKey)}`;

            if (managerOverrideModal) {
                managerOverrideModal.show();
                setTimeout(() => document.getElementById('managerOverridePinCode')?.focus(), 150);
            } else {
                resolve(false);
            }
        });
    }

    function buildQuickCashButtons(total) {
        const exact = Number(total || 0);
        const base = Math.ceil(exact);
        const suggestions = Array.from(new Set([
            exact,
            Math.ceil(base / 20) * 20,
            Math.ceil(base / 50) * 50,
            Math.ceil(base / 100) * 100,
        ].filter((value) => value > 0)));

        return suggestions.map((value) => `
            <button type="button" class="btn btn-outline-secondary cashier-quick-btn" data-quick-amount="${value}">
                ${escapeHtml(formatMoney(value))}
            </button>
        `).join('');
    }

    function renderDetail(payload) {
        if (!payload || !payload.order) {
            state.selectedPayload = null;
            els.detailRoot.className = 'cashier-empty';
            els.detailRoot.innerHTML = escapeHtml(lang.cashierSelectOrder);
            return;
        }

        state.selectedPayload = payload;

        const order = payload.order || {};
        const table = payload.table || {};
        const items = Array.isArray(payload.items) ? payload.items : [];
        const counts = payload.counts || {};
        const summary = payload.summary || {};
        const total = Number(summary.display_total || summary.total_price || 0);
        const openedAt = order.opened_at || order.created_at || '';
        const canMarkBilling = String(order.status || '') === 'open'
            && (cashierPermissions.request_bill || cashierPermissions.manager_override);
        const canPay = cashierPermissions.pay || cashierPermissions.manager_override;

        els.detailRoot.className = 'cashier-detail-wrap';
        els.detailRoot.innerHTML = `
            <div class="cashier-detail-card">
                <div class="cashier-detail-head">
                    <div>
                        <div class="cashier-detail-title">${escapeHtml(order.order_number || '-')}</div>
                        <div class="cashier-detail-subtitle">${escapeHtml(table.table_name || order.table_name || '-')} • ${escapeHtml(table.zone_name || order.zone_name || '-')}</div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <span class="cashier-badge ${escapeHtml(order.status || 'open')}">${escapeHtml(getOrderStatusLabel(order.status))}</span>
                        <a href="<?= site_url('pos/table') ?>/${Number(order.table_id || 0)}" class="btn btn-outline-secondary btn-sm rounded-pill">
                            ${escapeHtml(lang.cashierOpenPos)}
                        </a>
                    </div>
                </div>

                <div class="cashier-info-grid">
                    <div class="cashier-info-box">
                        <div class="label">${escapeHtml(lang.table_name)}</div>
                        <div class="value">${escapeHtml(table.table_name || '-')}</div>
                    </div>
                    <div class="cashier-info-box">
                        <div class="label">${escapeHtml(lang.cashierOpenedAt)}</div>
                        <div class="value">${escapeHtml(formatDateTime(openedAt))}</div>
                    </div>
                    <div class="cashier-info-box">
                        <div class="label">${escapeHtml(lang.cashierBillStatus)}</div>
                        <div class="value">${escapeHtml(getOrderStatusLabel(order.status))}</div>
                    </div>
                    <div class="cashier-info-box">
                        <div class="label">${escapeHtml(lang.cashierItemCount)}</div>
                        <div class="value">${Number(counts.all || 0)}</div>
                    </div>
                </div>
            </div>

            <div class="cashier-detail-card">
                <div class="cashier-detail-head">
                    <div>
                        <div class="cashier-detail-title">${escapeHtml(lang.cashierOrderItems)}</div>
                        <div class="cashier-detail-subtitle">${escapeHtml(lang.cashierReadyToPay)}</div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="cashier-badge pending">${escapeHtml(getItemStatusLabel('pending'))}: ${Number(counts.pending || 0)}</span>
                        <span class="cashier-badge preparing">${escapeHtml(getItemStatusLabel('preparing'))}: ${Number(counts.preparing || 0)}</span>
                        <span class="cashier-badge ready">${escapeHtml(getItemStatusLabel('ready'))}: ${Number(counts.ready || 0)}</span>
                    </div>
                </div>

                <div class="cashier-item-list">
                    ${items.length ? items.map((item) => {
                        const normalizedStatus = String(item.status || '').toLowerCase();
                        return `
                            <div class="cashier-item-row">
                                <div class="cashier-item-top">
                                    <div>
                                        <div class="cashier-item-name">${escapeHtml(item.product_name || '-')}</div>
                                        ${item.option_summary ? `<div class="cashier-item-extra">${escapeHtml(item.option_summary)}</div>` : ''}
                                        ${item.item_detail ? `<div class="cashier-item-extra">${escapeHtml(item.item_detail)}</div>` : ''}
                                        ${item.note ? `<div class="cashier-item-note">${escapeHtml(item.note)}</div>` : ''}
                                    </div>

                                    <div class="cashier-item-price">
                                        <div class="qty">x${Number(item.qty || 0)} • ${escapeHtml(getItemStatusLabel(normalizedStatus))}</div>
                                        <div class="total">${escapeHtml(formatMoney(item.line_total || 0))}</div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('') : `<div class="cashier-empty">${escapeHtml(lang.cashierNoOrders)}</div>`}
                </div>
            </div>

            <div class="cashier-detail-card">
                <div class="cashier-detail-head">
                    <div>
                        <div class="cashier-detail-title">${escapeHtml(lang.cashierOrderSummary)}</div>
                        <div class="cashier-detail-subtitle">${escapeHtml(lang.paymentMethod)}</div>
                    </div>
                </div>

                <div class="cashier-payment-layout">
                    <div class="cashier-total-stack">
                        <div class="cashier-total-row">
                            <div class="label">${escapeHtml(lang.subtotal)}</div>
                            <div class="value">${escapeHtml(formatMoney(summary.subtotal || 0))}</div>
                        </div>
                        <div class="cashier-total-row">
                            <div class="label">${escapeHtml(lang.discount)}</div>
                            <div class="value">${escapeHtml(formatMoney(summary.discount_amount || 0))}</div>
                        </div>
                        <div class="cashier-total-row">
                            <div class="label">${escapeHtml(lang.serviceCharge)}</div>
                            <div class="value">${escapeHtml(formatMoney(summary.service_charge || 0))}</div>
                        </div>
                        <div class="cashier-total-row">
                            <div class="label">${escapeHtml(lang.vat)}</div>
                            <div class="value">${escapeHtml(formatMoney(summary.vat_amount || 0))}</div>
                        </div>
                        <div class="cashier-total-row grand">
                            <div class="label">${escapeHtml(lang.grandTotal)}</div>
                            <div class="value">${escapeHtml(formatMoney(total))}</div>
                        </div>
                    </div>

                    <div class="cashier-actions">
                        <div>
                            <label class="form-label fw-semibold">${escapeHtml(lang.paymentMethod)}</label>
                            <div class="cashier-method-grid">
                                <button type="button" class="btn btn-outline-primary cashier-method-btn active" data-payment-method="cash">${escapeHtml(lang.cash)}</button>
                                <button type="button" class="btn btn-outline-primary cashier-method-btn" data-payment-method="transfer">${escapeHtml(lang.transfer)}</button>
                                <button type="button" class="btn btn-outline-primary cashier-method-btn" data-payment-method="card">${escapeHtml(lang.card)}</button>
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">${escapeHtml(lang.cashierQuickCash)}</label>
                            <div class="cashier-quick-grid">
                                ${buildQuickCashButtons(total)}
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">${escapeHtml(lang.cashierCustomAmount)}</label>
                            <input type="number" class="form-control form-control-lg" id="cashierReceivedAmount" min="0" step="0.01" value="${Number(total).toFixed(2)}">
                        </div>

                        <div class="cashier-total-row">
                            <div class="label">${escapeHtml(lang.change)}</div>
                            <div class="value" id="cashierChangePreview">${escapeHtml(formatMoney(0))}</div>
                        </div>

                        <div class="cashier-action-grid">
                            ${canMarkBilling ? `
                                <button type="button" class="btn btn-outline-warning" id="cashierMarkBillingBtn">
                                    ${escapeHtml(lang.cashierMarkBilling)}
                                </button>
                            ` : ''}
                            ${(cashierPermissions.reopen_bill && String(order.status || '') === 'billing') ? `
                                <button type="button" class="btn btn-outline-secondary" id="cashierReopenBillBtn">
                                    ${escapeHtml(lang.reopenBill)}
                                </button>
                            ` : ''}
                            ${canPay ? `
                                <button type="button" class="btn btn-success" id="cashierPayBtn">
                                    ${escapeHtml(lang.closeBillPay)}
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;

        bindDetailActions(total, order);
        recalcChangePreview(total);
    }

    function recalcChangePreview(total) {
        const amountInput = document.getElementById('cashierReceivedAmount');
        const changePreview = document.getElementById('cashierChangePreview');

        if (!amountInput || !changePreview) {
            return;
        }

        const amount = Number(amountInput.value || 0);
        const change = amount - Number(total || 0);
        changePreview.textContent = formatMoney(change > 0 ? change : 0);
    }

    function bindDetailActions(total, order) {
        document.querySelectorAll('.cashier-method-btn').forEach((button) => {
            button.addEventListener('click', () => {
                state.paymentMethod = button.getAttribute('data-payment-method') || 'cash';
                document.querySelectorAll('.cashier-method-btn').forEach((btn) => btn.classList.remove('active'));
                button.classList.add('active');
            });
        });

        document.querySelectorAll('[data-quick-amount]').forEach((button) => {
            button.addEventListener('click', () => {
                const amountInput = document.getElementById('cashierReceivedAmount');
                if (!amountInput) {
                    return;
                }
                amountInput.value = Number(button.getAttribute('data-quick-amount') || total || 0).toFixed(2);
                recalcChangePreview(total);
            });
        });

        const amountInput = document.getElementById('cashierReceivedAmount');
        if (amountInput) {
            amountInput.addEventListener('input', () => recalcChangePreview(total));
        }

        const markBillingBtn = document.getElementById('cashierMarkBillingBtn');
        if (markBillingBtn) {
            markBillingBtn.addEventListener('click', async () => {
                markBillingBtn.disabled = true;
                try {
                    const response = await postJson('<?= site_url('pos/close-bill') ?>', {
                        order_id: Number(order.id || 0),
                    });

                    alert(response.message || lang.openBillSuccessBilling);

                    await refreshOrders(Number(order.id || 0));
                } catch (error) {
                    if (error && error.payload && error.payload.code === 'MANAGER_OVERRIDE_REQUIRED') {
                        const approved = await requestManagerOverride('pay', Number(order.id || 0));
                        if (approved) {
                            payBtn.click();
                        }
                    } else {
                        alert(error.message || lang.orderNotFound);
                    }
                } finally {
                    markBillingBtn.disabled = false;
                }
            });
        }

        const reopenBtn = document.getElementById('cashierReopenBillBtn');
        if (reopenBtn) {
            reopenBtn.addEventListener('click', async () => {
                if (!confirm(lang.reopenBillConfirm)) {
                    return;
                }

                reopenBtn.disabled = true;
                try {
                    const response = await postJson('<?= site_url('pos/reopen-bill') ?>', {
                        order_id: Number(order.id || 0),
                    });

                    alert(response.message || lang.reopenBillSuccess);
                    await refreshOrders(Number(order.id || 0));
                } catch (error) {
                    if (error && error.payload && error.payload.code === 'MANAGER_OVERRIDE_REQUIRED') {
                        const approved = await requestManagerOverride('reopen_bill', Number(order.id || 0));
                        if (approved) {
                            reopenBtn.click();
                        }
                    } else {
                        alert(error.message || lang.reopenBillFailed);
                    }
                } finally {
                    reopenBtn.disabled = false;
                }
            });
        }

        const payBtn = document.getElementById('cashierPayBtn');
        if (payBtn) {
            payBtn.addEventListener('click', async () => {
                const amount = Number((document.getElementById('cashierReceivedAmount') || {}).value || 0);

                payBtn.disabled = true;
                try {
                    const response = await postJson('<?= site_url('pos/pay') ?>', {
                        order_id: Number(order.id || 0),
                        payment_method: state.paymentMethod || 'cash',
                        amount: amount,
                    });

                    const changeMessage = response.change !== undefined
                        ? `\n${lang.change}: ${formatMoney(response.change)}`
                        : '';

                    alert((response.message || lang.paymentSuccess) + changeMessage);

                    await refreshOrders();
                } catch (error) {
                    if (error && error.payload && error.payload.code === 'MANAGER_OVERRIDE_REQUIRED') {
                        const approved = await requestManagerOverride('pay', Number(order.id || 0));
                        if (approved) {
                            payBtn.click();
                        }
                    } else {
                        alert(error.message || lang.orderNotFound);
                    }
                } finally {
                    payBtn.disabled = false;
                }
            });
        }
    }

    async function loadOrder(orderId) {
        if (!orderId) {
            renderDetail(null);
            return;
        }

        els.detailRoot.className = 'cashier-empty';
        els.detailRoot.innerHTML = escapeHtml(lang.cashierRefreshOrders);

        try {
            const response = await fetch(`<?= site_url('pos/cashier-order') ?>/${Number(orderId)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const json = await response.json();

            if (!response.ok || json.status !== 'success') {
                throw new Error(json.message || lang.orderNotFound);
            }

            renderDetail(json.data);
        } catch (error) {
            renderDetail(null);
            alert(error.message || lang.orderNotFound);
        }
    }

    async function postJson(url, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new URLSearchParams(payload),
        });

        const json = await response.json();

        if (!response.ok || (json.status !== 'success' && json.status !== 'warning')) {
            const error = new Error(json.message || 'Request failed');
            error.payload = json;
            throw error;
        }

        return json;
    }


    if (managerOverrideModalEl) {
        managerOverrideModalEl.addEventListener('hidden.bs.modal', () => {
            if (typeof managerOverrideResolver === 'function') {
                const resolver = managerOverrideResolver;
                managerOverrideResolver = null;
                resolver(false);
            }
        });
    }

    document.getElementById('btnConfirmManagerOverride')?.addEventListener('click', async () => {
        const btn = document.getElementById('btnConfirmManagerOverride');
        const actionKey = String(document.getElementById('managerOverrideAction')?.value || '');
        const orderId = Number(document.getElementById('managerOverrideOrderId')?.value || 0);
        const managerPinCode = String(document.getElementById('managerOverridePinCode')?.value || '').trim();

        if (!actionKey || !orderId || !managerPinCode) {
            alert(lang.managerOverrideFailed);
            return;
        }

        btn.disabled = true;
        try {
            const response = await postJson('<?= site_url('pos/manager-override') ?>', {
                action_key: actionKey,
                order_id: orderId,
                manager_pin_code: managerPinCode,
            });

            if (managerOverrideModal) {
                managerOverrideModal.hide();
            }

            const resolver = managerOverrideResolver;
            managerOverrideResolver = null;
            if (typeof resolver === 'function') {
                resolver(true);
            }

            const approvedByText = response.approved_by ? ` ${lang.by || 'by'} ${response.approved_by}` : '';
            alert((response.message || lang.managerOverrideApproved) + approvedByText);
        } catch (error) {
            alert(error.message || lang.managerOverrideFailed);
        } finally {
            btn.disabled = false;
        }
    });

    async function refreshOrders(preferredOrderId = null) {
        if (preferredOrderId) {
            state.selectedOrderId = Number(preferredOrderId);
        }

        const refreshTargetId = Number(state.selectedOrderId || preferredOrderId || 0);
        const currentlyActive = refreshTargetId;

        const refreshed = [];
        for (const order of state.orders) {
            const id = Number(order.id || 0);
            if (!id) {
                continue;
            }

            try {
                const response = await fetch(`<?= site_url('pos/cashier-order') ?>/${id}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const json = await response.json();

                if (response.ok && json.status === 'success' && json.data && json.data.order) {
                    const merged = {
                        ...json.data.order,
                        table_name: json.data.table ? json.data.table.table_name : (order.table_name || null),
                        zone_name: json.data.table ? json.data.table.zone_name : (order.zone_name || null),
                        item_counts: json.data.counts || order.item_counts || {},
                        display_total: Number((json.data.summary || {}).display_total || (json.data.summary || {}).total_price || 0),
                    };
                    refreshed.push(merged);
                }
            } catch (error) {
            }
        }

        state.orders = refreshed;
        renderOrderList();

        if (!state.orders.length) {
            state.selectedOrderId = null;
            renderDetail(null);
            return;
        }

        let nextId = currentlyActive;
        if (!state.orders.some((order) => Number(order.id) === Number(nextId))) {
            nextId = Number(state.orders[0].id || 0);
        }

        state.selectedOrderId = nextId || null;
        renderOrderList();

        if (nextId) {
            await loadOrder(nextId);
        }
    }

    els.searchInput?.addEventListener('input', (event) => {
        state.search = String(event.target.value || '');
        renderOrderList();
    });

    els.statusChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            const status = chip.getAttribute('data-filter-status') || 'all';
            state.filterStatus = status;
            els.statusChips.forEach((item) => item.classList.remove('active'));
            chip.classList.add('active');
            renderOrderList();
        });
    });

    els.refreshBtn?.addEventListener('click', async () => {
        await refreshOrders();
    });

    renderOrderList();

    if (state.orders.length) {
        state.selectedOrderId = Number(state.orders[0].id || 0);
        renderOrderList();
        loadOrder(state.selectedOrderId);
    }
})();
</script>


<div class="modal fade" id="managerOverrideModal" tabindex="-1" aria-labelledby="managerOverrideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="managerOverrideModalLabel"><?= esc(lang('app.manager_override_title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 small mb-3" id="managerOverrideHelpText"><?= esc(lang('app.manager_override_help_pin_only')) ?></div>
                <input type="hidden" id="managerOverrideAction" value="">
                <input type="hidden" id="managerOverrideOrderId" value="">
                <div class="mb-0">
                    <label class="form-label fw-semibold"><?= esc(lang('app.manager_pin_code')) ?></label>
                    <input type="password" class="form-control text-center fs-4 tracking-wide" id="managerOverridePinCode" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]*" placeholder="••••">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= esc(lang('app.cancel')) ?></button>
                <button type="button" class="btn btn-primary" id="btnConfirmManagerOverride"><?= esc(lang('app.approve_and_continue')) ?></button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
