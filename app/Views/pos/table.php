<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<style>
.mobile-category-scroll {
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
    white-space: nowrap;
}
.mobile-category-scroll::-webkit-scrollbar {
    height: 6px;
}
.mobile-category-scroll::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,.2);
    border-radius: 10px;
}

.pos-toast-container {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 2000;
    display: flex;
    flex-direction: column;
    gap: .75rem;
    max-width: min(92vw, 360px);
}
.pos-toast-item {
    background: rgba(33, 37, 41, 0.96);
    color: #fff;
    border-radius: 14px;
    padding: .9rem 1rem;
    box-shadow: 0 12px 30px rgba(0,0,0,.22);
    opacity: 0;
    transform: translateY(-6px);
    transition: opacity .18s ease, transform .18s ease;
    pointer-events: auto;
    font-size: .95rem;
    line-height: 1.45;
}
.pos-toast-item.show { opacity: 1; transform: translateY(0); }
.pos-toast-item.toast-success { background: rgba(25, 135, 84, 0.96); }
.pos-toast-item.toast-error { background: rgba(220, 53, 69, 0.97); }
.pos-toast-item.toast-warning { background: rgba(255, 193, 7, 0.98); color: #212529; }

.pos-touch-shell {
    display: flex;
    flex-direction: column;
    gap: 12px;
    height: calc(100vh - 150px);
    min-height: calc(100vh - 150px);
    overflow: hidden;
}

.pos-touch-header {
    display: flex;
    align-items: stretch;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px;
    border-radius: 24px;
    background: linear-gradient(135deg, #ffffff 0%, #f7f9fc 100%);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    border: 1px solid rgba(148, 163, 184, 0.18);
}

.pos-touch-header-main {
    min-width: 0;
}

.pos-touch-title {
    font-size: clamp(1.2rem, 1.7vw, 1.65rem);
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: .35rem;
}

.pos-touch-subtitle {
    color: #64748b;
    font-size: .95rem;
}

.pos-touch-badges,
.pos-touch-actions,
.pos-section-stack {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}

.pos-touch-actions {
    align-items: center;
    justify-content: flex-end;
}

.pos-touch-btn,
.category-btn,
.product-btn,
#btnOpenOrder,
#btnSendKitchen,
#btnPay,
#btnMoveTable,
#btnMergeBill {
    min-height: 50px;
    border-radius: 16px;
    font-weight: 600;
}

.pos-touch-layout {
    flex: 1;
    display: grid;
    grid-template-columns: minmax(210px, 232px) minmax(0, 1fr) minmax(330px, 390px);
    gap: 12px;
    min-height: 0;
    overflow: hidden;
}

.pos-pane,
.pos-pane-card {
    min-height: 0;
}

.pos-pane-card {
    height: 100%;
    min-height: 0;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 24px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
}

.pos-pane-body {
    height: 100%;
    display: flex;
    flex-direction: column;
    min-height: 0;
    padding: 16px;
    overflow: hidden;
}

.pos-section-title {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: .25rem;
}

.pos-section-note {
    font-size: .86rem;
    color: #64748b;
}

.pos-category-list {
    display: grid;
    gap: .65rem;
    overflow: auto;
    padding-right: .15rem;
}

.pos-category-list .category-btn {
    justify-content: flex-start;
    text-align: left;
    padding-inline: 1rem;
}

.pos-mobile-category {
    display: none;
}

.pos-products-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    margin-bottom: 10px;
}

.pos-products-toolbar .small {
    color: #64748b;
}

.pos-products-search-block {
    margin-bottom: 10px;
}

#productGrid {
    flex: 1;
    overflow: auto;
    margin: 0 !important;
    padding-top: .35rem;
    padding-right: .25rem;
    align-content: flex-start;
}

.product-card {
    display: flex;
}

.product-btn {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-color: rgba(148, 163, 184, 0.35) !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.6);
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}

.product-btn:hover,
.product-btn:focus {
    transform: translateY(-1px);
    border-color: rgba(59, 130, 246, 0.45) !important;
    box-shadow: 0 14px 24px rgba(59, 130, 246, 0.12);
}

.pos-bill-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    margin-bottom: 10px;
    flex-shrink: 0;
}

.pos-bill-scroll {
    flex: 1;
    overflow: auto;
    min-height: 0;
    padding-right: .25rem;
    padding-bottom: .35rem;
}

.pos-bill-summary {
    border-top: 1px solid rgba(148, 163, 184, 0.18);
    margin-top: 10px;
    padding-top: 12px;
    flex-shrink: 0;
    background: #fff;
}

.pos-bill-total-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    margin-bottom: 12px;
}

#billTotal {
    font-size: 1.55rem;
    line-height: 1;
}

.pos-action-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem;
}

.pos-action-grid .btn {
    min-height: 58px;
    font-size: 1rem;
}

#orderBox .card,
#canceledItemsBox .card {
    border-radius: 18px;
}

#orderBox .btn,
#canceledItemsBox .btn {
    border-radius: 12px;
}

.pos-products-search {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    gap: .5rem;
    align-items: center;
}

.pos-products-search .form-control,
.pos-products-search .btn {
    min-height: 46px;
    border-radius: 14px;
}

.pos-products-inline-meta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
    padding: 0 .75rem;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid rgba(148, 163, 184, 0.22);
    color: #64748b;
    font-size: .8rem;
    line-height: 1;
    white-space: nowrap;
}

.pos-inline-shortcuts {
    display: none;
    align-items: center;
    gap: .5rem;
    margin-top: .45rem;
    min-width: 0;
}

.pos-inline-shortcuts.show {
    display: flex;
}

.pos-inline-shortcuts-label {
    flex: 0 0 auto;
    color: #64748b;
    font-size: .8rem;
    font-weight: 600;
    white-space: nowrap;
}

.pos-inline-shortcuts-list {
    display: flex;
    align-items: center;
    gap: .4rem;
    overflow: hidden;
    flex-wrap: wrap;
    min-width: 0;
}

.pos-inline-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
    padding: .3rem .75rem;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.42);
    background: #fff;
    color: #475569;
    font-size: .82rem;
    font-weight: 600;
    white-space: nowrap;
}

.pos-inline-chip:hover,
.pos-inline-chip:focus {
    border-color: rgba(59, 130, 246, 0.45);
    color: #1d4ed8;
    background: #eff6ff;
}

.pos-inline-chip-more {
    background: #f8fafc;
}

.pos-empty-products {
    display: none;
    border: 1px dashed rgba(148, 163, 184, 0.45);
    border-radius: 18px;
    padding: 1rem;
    text-align: center;
    color: #64748b;
    background: #f8fafc;
}

.pos-empty-products.show {
    display: block;
}

.pos-bill-pane-close {
    display: none;
}

.pos-mobile-bill-summary {
    display: none;
}

.pos-mobile-bill-backdrop {
    display: none;
}

@media (max-width: 1399.98px) {
    .pos-touch-shell {
        height: calc(100vh - 138px);
        min-height: calc(100vh - 138px);
    }

    .pos-touch-layout {
        grid-template-columns: minmax(182px, 210px) minmax(0, 1fr) minmax(310px, 360px);
    }
}

@media (max-width: 1199.98px) {
    .mobile-bottom-space {
        padding-bottom: 128px;
    }

    .pos-touch-shell {
        height: auto;
        min-height: auto;
        overflow: visible;
    }

    .pos-touch-layout {
        grid-template-columns: 1fr;
    }

    .pos-pane-card {
        height: auto;
    }

    .pos-pane-body {
        overflow: visible;
    }

    .pos-category-pane {
        display: none;
    }

    .pos-mobile-category {
        display: block;
    }

    #productGrid {
        min-height: 240px;
        max-height: none;
        overflow: visible;
        margin-top: 0;
        padding-top: .35rem;
        padding-bottom: 112px;
    }

    .pos-bill-scroll {
        overflow: auto;
    }

    .pos-bill-pane {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1060;
        padding: 0 10px 10px;
        transform: translateY(calc(100% + 24px));
        transition: transform .22s ease;
        pointer-events: none;
    }

    .pos-bill-pane .pos-pane-card {
        height: min(82vh, 720px);
        border-radius: 24px 24px 20px 20px;
        box-shadow: 0 -18px 40px rgba(15, 23, 42, 0.22);
    }

    .pos-bill-pane-close {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
        min-height: 44px;
        border-radius: 12px;
    }

    .pos-mobile-bill-summary {
        position: fixed;
        left: 12px;
        right: 12px;
        bottom: 12px;
        z-index: 1055;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .85rem 1rem;
        border-radius: 18px;
        background: rgba(15, 23, 42, 0.94);
        color: #fff;
        box-shadow: 0 16px 36px rgba(15, 23, 42, 0.28);
    }

    .pos-mobile-bill-summary .btn {
        min-height: 46px;
        border-radius: 14px;
        white-space: nowrap;
    }

    .pos-mobile-bill-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1050;
        background: rgba(15, 23, 42, 0.35);
        opacity: 0;
        visibility: hidden;
        transition: opacity .2s ease, visibility .2s ease;
        display: block;
    }

    body.pos-bill-drawer-open .pos-bill-pane {
        transform: translateY(0);
        pointer-events: auto;
    }

    body.pos-bill-drawer-open .pos-mobile-bill-backdrop {
        opacity: 1;
        visibility: visible;
    }

    body.pos-bill-drawer-open {
        overflow: hidden;
    }
}

@media (max-width: 767.98px) {
    .pos-touch-header {
        flex-direction: column;
        border-radius: 20px;
        padding: 14px;
    }

    .pos-touch-actions {
        width: 100%;
        justify-content: stretch;
    }

    .pos-touch-actions > * {
        flex: 1 1 100%;
    }

    .pos-pane-card {
        border-radius: 20px;
    }

    .pos-pane-body {
        padding: 14px;
    }

    .pos-products-search {
        grid-template-columns: 1fr auto;
    }

    .pos-products-inline-meta {
        display: none;
    }

    .pos-inline-shortcuts {
        align-items: flex-start;
        flex-direction: column;
        gap: .35rem;
    }

    .pos-inline-shortcuts-list {
        width: 100%;
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-bottom: .15rem;
    }

    .pos-action-grid {
        grid-template-columns: 1fr;
    }

    #productGrid {
        grid-auto-rows: 1fr;
        max-height: none !important;
        overflow: visible !important;
        margin-top: 0 !important;
        padding-top: .35rem;
        padding-bottom: 128px;
    }

    .product-card {
        min-height: 96px;
    }

    .pos-inline-shortcuts {
        margin-top: .35rem;
        margin-bottom: .15rem;
    }

    .pos-inline-shortcuts-list {
        scrollbar-width: none;
    }

    .pos-inline-shortcuts-list::-webkit-scrollbar {
        display: none;
    }
}

@media (max-height: 880px) and (min-width: 1200px) {
    .pos-touch-shell {
        height: calc(100vh - 126px);
        min-height: calc(100vh - 126px);
    }

    .pos-touch-header {
        padding: 14px 16px;
    }

    .pos-pane-body {
        padding: 14px;
    }

    .pos-category-list {
        gap: .5rem;
    }

    .pos-category-list .category-btn,
    .product-btn,
    #btnOpenOrder,
    #btnSendKitchen,
    #btnPay,
    #btnMoveTable,
    #btnMergeBill {
        min-height: 44px;
        border-radius: 14px;
    }

    .pos-products-search .form-control,
    .pos-products-search .btn {
        min-height: 42px;
    }

    .pos-action-grid .btn {
        min-height: 50px;
        font-size: .95rem;
    }

    #billTotal {
        font-size: 1.4rem;
    }
}
</style>
<?php
    $tableIsActive = (int) ($table['is_active'] ?? 0) === 1;
    $tableStatus   = strtolower((string) ($table['status'] ?? 'available'));
    $tableDisabled = (!$tableIsActive || $tableStatus === 'disabled');
    $recentItemsLabel = lang('app.recent_items');
    if ($recentItemsLabel === 'app.recent_items') {
        $recentItemsLabel = service('request')->getLocale() === 'th' ? 'เมนูล่าสุด' : 'Recent items';
    }
?>
<div class="pos-touch-shell mobile-bottom-space">
    <div class="pos-touch-header">
        <div class="pos-touch-header-main">
            <div class="pos-touch-title"><?= esc(lang('app.table')) ?> <?= esc($table['table_name'] ?? '-') ?></div>
            <div class="pos-touch-subtitle">
                <?= $tableDisabled
                    ? esc(lang('app.table_disabled_cannot_order'))
                    : esc(lang('app.select_menu_to_add_bill')) ?>
            </div>

            <div class="pos-touch-badges mt-3 align-items-center">
                <?php
                    $tableStatusText = lang('app.available');

                    if ($tableDisabled) {
                        $tableStatusText = lang('app.disabled');
                    } else {
                        switch ($tableStatus) {
                            case 'occupied':
                                $tableStatusText = lang('app.occupied');
                                break;
                            case 'reserved':
                                $tableStatusText = lang('app.reserved');
                                break;
                            case 'cleaning':
                                $tableStatusText = lang('app.cleaning');
                                break;
                            case 'disabled':
                                $tableStatusText = lang('app.disabled');
                                break;
                            case 'available':
                            default:
                                $tableStatusText = lang('app.available');
                                break;
                        }
                    }
                ?>
                <span class="badge <?= $tableDisabled ? 'text-bg-dark' : 'text-bg-info' ?>" id="tableStatusBadge">
                    <?= esc(lang('app.table_status')) ?>: <?= esc($tableStatusText) ?>
                </span>

                <span class="badge text-bg-secondary" id="orderStatusBadge">
                    <?= esc(lang('app.bill_status')) ?>: <?= esc(lang('app.no_open_bill')) ?>
                </span>

                <?php if ($tableDisabled): ?>
                    <span class="badge text-bg-danger"><?= esc(lang('app.this_table_disabled')) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="pos-touch-actions">
            <button
                type="button"
                class="btn btn-primary pos-touch-btn"
                id="btnOpenOrder"
                <?= $tableDisabled ? 'disabled' : '' ?>
            >
                <?= $tableDisabled ? esc(lang('app.table_disabled')) : esc(lang('app.open_bill')) ?>
            </button>
        </div>
    </div>

    <?php if ($tableDisabled): ?>
        <div class="alert alert-warning border-0 rounded-4 mb-0">
            <?= esc(lang('app.table_disabled_full_notice')) ?>
        </div>
    <?php endif; ?>

    <div class="pos-mobile-category">
        <div class="pos-pane-card">
            <div class="pos-pane-body">
                <div class="small text-muted mb-2"><?= esc(lang('app.categories')) ?></div>
                <div class="d-flex gap-2 overflow-auto pb-1 mobile-category-scroll" id="mobileCategoryBar">
                    <button type="button" class="btn btn-dark flex-shrink-0 category-btn active" data-id="all"><?= esc(lang('app.all')) ?></button>
                    <?php foreach ($categories as $cat): ?>
                        <?php $categoryName = lfield($cat, 'category_name', '-'); ?>
                        <button type="button" class="btn btn-outline-dark flex-shrink-0 category-btn" data-id="<?= (int) $cat['id'] ?>">
                            <?= esc($categoryName) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="pos-touch-layout">
        <div class="pos-pane pos-category-pane">
            <div class="pos-pane-card">
                <div class="pos-pane-body">
                    <div class="pos-section-title"><?= esc(lang('app.categories')) ?></div>
                    <div class="pos-section-note mb-3"><?= esc(lang('app.select_menu_to_add_bill')) ?></div>

                    <div class="pos-category-list">
                        <button type="button" class="btn btn-dark category-btn active" data-id="all"><?= esc(lang('app.all')) ?></button>
                        <?php foreach ($categories as $cat): ?>
                            <?php $categoryName = lfield($cat, 'category_name', '-'); ?>
                            <button type="button" class="btn btn-outline-dark category-btn" data-id="<?= (int) $cat['id'] ?>">
                                <?= esc($categoryName) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="pos-pane">
            <div class="pos-pane-card">
                <div class="pos-pane-body">
                    <div class="pos-products-toolbar">
                        <div>
                            <div class="pos-section-title"><?= esc(lang('app.categories')) ?></div>
                            <div class="small"><?= esc(lang('app.select_menu_to_add_bill')) ?></div>
                        </div>

                        <div class="pos-section-stack align-items-center justify-content-end">
                            <div id="mergeTraceBox" class="w-100"></div>
                        </div>
                    </div>

                    <div class="pos-products-search-block compact-mode">
                        <div class="pos-products-search">
                            <input
                                type="text"
                                class="form-control"
                                id="productSearch"
                                placeholder="<?= esc(lang('app.search')) ?>..."
                                autocomplete="off"
                            >
                            <div class="pos-products-inline-meta">
                                <span id="productResultCount"><?= count($products ?? []) ?></span>
                                <span class="mx-1">•</span>
                                <span id="activeCategoryLabel"><?= esc(lang('app.all')) ?></span>
                            </div>
                            <button type="button" class="btn btn-outline-secondary" id="btnClearProductSearch">×</button>
                        </div>

                        <div class="pos-inline-shortcuts" id="recentProductToolbar">
                            <span class="pos-inline-shortcuts-label"><?= esc($recentItemsLabel) ?></span>
                            <div class="pos-inline-shortcuts-list" id="recentProductChips"></div>
                        </div>
                    </div>

                    <div class="pos-empty-products" id="productEmptyState"><?= esc(lang('app.no_data')) ?></div>

                    <div class="row g-3" id="productGrid">
                        <?php foreach ($products as $product): ?>
                            <?php $productName = lfield($product, 'product_name', '-'); ?>
                            <div
                                class="col-6 col-md-4 product-card"
                                data-category="<?= (int) ($product['category_id'] ?? 0) ?>"
                                data-name="<?= esc(mb_strtolower($productName, 'UTF-8')) ?>"
                            >
                                <button
                                    type="button"
                                    class="btn btn-light border w-100 text-start rounded-4 p-3 product-btn h-100"
                                    data-id="<?= (int) $product['id'] ?>"
                                    data-name="<?= esc($productName) ?>"
                                    <?= $tableDisabled ? 'disabled' : '' ?>
                                >
                                    <div class="fw-bold mb-1"><?= esc($productName) ?></div>
                                    <div class="text-muted small">฿<?= number_format((float) ($product['price'] ?? 0), 2) ?></div>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="pos-pane pos-bill-pane">
            <div class="pos-pane-card">
                <div class="pos-pane-body">
                    <div class="pos-bill-header">
                        <div>
                            <div class="pos-section-title"><?= esc(lang('app.current_bill')) ?></div>
                            <div class="pos-section-note" id="orderNoLabel">-</div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary pos-bill-pane-close" id="btnCloseBillDrawer">×</button>
                    </div>

                    <div class="pos-bill-scroll">
                        <div id="orderMetaIndicators" class="d-flex flex-wrap gap-2 mb-3"></div>
                        <div id="billMergeAuditBox" class="mb-3"></div>
                        <div id="billMoveAuditBox" class="mb-3"></div>
                        <div id="billRequestAlertBox" class="mt-2 mb-3"></div>

                        <div id="orderBox">
                            <div class="text-muted"><?= esc(lang('app.no_bill_yet')) ?></div>
                        </div>

                        <div id="canceledItemsBox"></div>
                    </div>

                    <div class="pos-bill-summary">
                        <div class="pos-bill-total-row">
                            <span><?= esc(lang('app.grand_total')) ?></span>
                            <strong id="billTotal">฿0.00</strong>
                        </div>

                        <div class="pos-action-grid">
                            <button
                                type="button"
                                class="btn btn-warning"
                                id="btnSendKitchen"
                                <?= $tableDisabled ? 'disabled' : '' ?>
                            >
                                <?= esc(lang('app.send_to_kitchen')) ?>
                            </button>

                            <button
                                type="button"
                                class="btn btn-success"
                                id="btnPay"
                                <?= $tableDisabled ? 'disabled' : '' ?>
                            >
                                <?= esc(lang('app.close_bill_pay')) ?>
                            </button>

                            <button
                                type="button"
                                class="btn btn-outline-primary"
                                id="btnMoveTable"
                                <?= $tableDisabled ? 'disabled' : '' ?>
                            >
                                <?= esc(lang('app.move_table')) ?>
                            </button>

                            <button
                                type="button"
                                class="btn btn-outline-dark"
                                id="btnMergeBill"
                                <?= $tableDisabled ? 'disabled' : '' ?>
                            >
                                <?= esc(lang('app.merge_bill')) ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="pos-mobile-bill-summary" id="mobileBillSummary">
    <div class="d-flex flex-column">
        <strong><?= esc(lang('app.current_bill')) ?></strong>
        <span class="small text-white-50" id="mobileBillSummaryMeta">0</span>
    </div>
    <div class="d-flex align-items-center gap-3">
        <strong id="mobileBillSummaryTotal">฿0.00</strong>
        <button type="button" class="btn btn-light" id="btnOpenBillDrawer"><?= esc(lang('app.current_bill')) ?></button>
    </div>
</div>
<div class="pos-mobile-bill-backdrop" id="mobileBillBackdrop"></div>

<div class="modal fade" id="productOptionModal" tabindex="-1" aria-labelledby="productOptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="productOptionModalLabel"><?= esc(lang('app.add_food_item')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="modalMode" value="add">
                <input type="hidden" id="editingItemId" value="">
                <input type="hidden" id="editingProductId" value="">

                <div class="mb-3">
                    <div class="fw-bold fs-5" id="modalProductName">-</div>
                    <div class="text-muted small" id="modalProductPrice">฿0.00</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= esc(lang('app.food_detail_or_custom')) ?></label>

                    <div class="d-flex flex-wrap gap-2 mb-2" id="quickDetailBox">
                        <div class="text-muted small"><?= esc(lang('app.no_data')) ?></div>
                    </div>

                    <input
                        type="text"
                        class="form-control"
                        id="modalItemDetail"
                        placeholder="<?= esc(lang('app.food_detail_placeholder')) ?>"
                    >

                    <div class="form-text">
                        <?= esc(lang('app.food_detail_help')) ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= esc(lang('app.additional_note')) ?></label>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <?php foreach (($quickNotes ?? []) as $note): ?>
                            <?php $quickNoteText = lfield($note, 'note_name', '-'); ?>
                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-sm modal-quick-note"
                                data-value="<?= esc($quickNoteText) ?>"
                            >
                                <?= esc($quickNoteText ?: '-') ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" class="form-control" id="modalItemNote" placeholder="<?= esc(lang('app.additional_note_placeholder')) ?>">
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger" id="btnClearModalOption"><?= esc(lang('app.clear')) ?></button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= esc(lang('app.cancel')) ?></button>
                    <button type="button" class="btn btn-success ms-auto" id="btnConfirmAddProduct"><?= esc(lang('app.add_to_bill')) ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel"><?= esc(lang('app.close_bill_pay')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <div class="small text-muted"><?= esc(lang('app.amount_due')) ?></div>
                    <div class="fs-3 fw-bold text-success" id="paymentBillTotal">฿0.00</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= esc(lang('app.payment_method')) ?></label>
                    <select class="form-select" id="paymentMethod">
                        <option value="cash"><?= esc(lang('app.cash')) ?></option>
                        <option value="transfer"><?= esc(lang('app.transfer')) ?></option>
                        <option value="card"><?= esc(lang('app.card')) ?></option>
                    </select>
                </div>

                <div class="mb-3" id="paymentCashWrap">
                    <label class="form-label fw-semibold"><?= esc(lang('app.received_amount')) ?></label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-lg" id="paymentAmount" placeholder="0.00">
                </div>

                <div class="mb-3">
                    <div class="small text-muted"><?= esc(lang('app.change')) ?></div>
                    <div class="fs-4 fw-bold text-primary" id="paymentChange">฿0.00</div>
                </div>

                <div class="mb-2 fw-semibold"><?= esc(lang('app.quick_buttons')) ?></div>
                <div class="row g-2 mb-3">
                    <div class="col-4"><button type="button" class="btn btn-outline-secondary w-100 quick-pay" data-type="exact"><?= esc(lang('app.exact')) ?></button></div>
                    <div class="col-4"><button type="button" class="btn btn-outline-secondary w-100 quick-pay" data-type="20">20</button></div>
                    <div class="col-4"><button type="button" class="btn btn-outline-secondary w-100 quick-pay" data-type="50">50</button></div>
                    <div class="col-4"><button type="button" class="btn btn-outline-secondary w-100 quick-pay" data-type="100">100</button></div>
                    <div class="col-4"><button type="button" class="btn btn-outline-secondary w-100 quick-pay" data-type="500">500</button></div>
                    <div class="col-4"><button type="button" class="btn btn-outline-secondary w-100 quick-pay" data-type="1000">1000</button></div>
                </div>

                <div class="mb-2 fw-semibold"><?= esc(lang('app.auto_rounding')) ?></div>
                <div class="row g-2">
                    <div class="col-4"><button type="button" class="btn btn-outline-dark w-100 quick-round" data-round="1"><?= esc(lang('app.round_integer')) ?></button></div>
                    <div class="col-4"><button type="button" class="btn btn-outline-dark w-100 quick-round" data-round="10"><?= esc(lang('app.round_tens')) ?></button></div>
                    <div class="col-4"><button type="button" class="btn btn-outline-dark w-100 quick-round" data-round="100"><?= esc(lang('app.round_hundreds')) ?></button></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= esc(lang('app.cancel')) ?></button>
                <button type="button" class="btn btn-success" id="btnConfirmPay"><?= esc(lang('app.confirm_close_bill_pay')) ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="moveTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title"><?= esc(lang('app.move_table')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?= esc(lang('app.select_new_table')) ?></label>
                    <select class="form-select" id="moveToTableId">
                        <option value=""><?= esc(lang('app.please_select')) ?></option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= esc(lang('app.note')) ?></label>
                    <input type="text" class="form-control" id="moveTableReason" placeholder="<?= esc(lang('app.additional_note_placeholder')) ?>">
                </div>

                <div class="small text-muted" id="moveTableHint"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= esc(lang('app.cancel')) ?></button>
                <button type="button" class="btn btn-primary" id="btnConfirmMoveTable"><?= esc(lang('app.confirm')) ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mergeBillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title"><?= esc(lang('app.confirm_merge_bill')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label"><?= esc(lang('app.select_target_bill')) ?></label>
                    <select class="form-select" id="mergeTargetOrderId">
                        <option value=""><?= esc(lang('app.please_select')) ?></option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= esc(lang('app.merge_note')) ?></label>
                    <input type="text" class="form-control" id="mergeBillReason" placeholder="<?= esc(lang('app.additional_note_placeholder')) ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <?= esc(lang('app.cancel')) ?>
                </button>
                <button type="button" class="btn btn-dark" id="btnConfirmMergeBill">
                    <?= esc(lang('app.confirm')) ?>
                </button>
            </div>
        </div>
    </div>
</div>


<div class="pos-toast-container" id="posToastContainer" aria-live="polite" aria-atomic="true"></div>

<div class="modal fade" id="managerOverrideModal" tabindex="-1" aria-labelledby="managerOverrideModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="managerOverrideModalLabel"><?= esc(lang('app.manager_override_title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-2 small mb-3" id="managerOverrideHelpText">
                    <?= esc(lang('app.manager_override_help_pin_only')) ?>
                </div>
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

<?= $this->section('scripts') ?>

<div class="modal fade" id="mergeAuditModal" tabindex="-1" aria-labelledby="mergeAuditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="mergeAuditModalLabel"><?= esc(lang('app.merge_audit_title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= esc(lang('app.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div id="mergeAuditModalBody">
                    <div class="text-muted"><?= esc(lang('app.no_data')) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="moveAuditModal" tabindex="-1" aria-labelledby="moveAuditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="moveAuditModalLabel"><?= esc(lang('app.move_audit_title')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= esc(lang('app.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div id="moveAuditModalBody">
                    <div class="text-muted"><?= esc(lang('app.no_data')) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    const TABLE_ID = <?= json_encode((int) ($table['id'] ?? 0)) ?>;
    const TABLE_IS_ACTIVE = <?= json_encode(!$tableDisabled) ?>;
    let CURRENT_ORDER_ID = <?= json_encode(!empty($currentOrder['id']) ? (int) $currentOrder['id'] : null) ?>;
    let CURRENT_ORDER_STATUS = <?= json_encode($currentOrder['status'] ?? null) ?>;
    let SELECTED_PRODUCT_ID = null;
    let SELECTED_PRODUCT_NAME = '';
    let SELECTED_PRODUCT_PRICE = 0;
    let AUTO_REFRESH_TIMER = null;

    const moveTableModalEl = document.getElementById('moveTableModal');
    const moveTableModal = moveTableModalEl ? new bootstrap.Modal(moveTableModalEl) : null;

    const mergeBillModalEl = document.getElementById('mergeBillModal');
    const mergeBillModal = mergeBillModalEl ? new bootstrap.Modal(mergeBillModalEl) : null;

    const TXT = {
        tableDisabled: <?= json_encode(lang('app.table_disabled'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        tableDisabledCannotOrder: <?= json_encode(lang('app.table_disabled_cannot_order'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        noOpenBill: <?= json_encode(lang('app.no_open_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        billStatus: <?= json_encode(lang('app.bill_status'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        openBill: <?= json_encode(lang('app.open_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        tableAlreadyOpen: <?= json_encode(lang('app.table_already_open'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        openBillStatus: <?= json_encode(lang('app.status_open'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        billingStatus: <?= json_encode(lang('app.status_billing'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        paidStatus: <?= json_encode(lang('app.status_paid'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        cancelStatus: <?= json_encode(lang('app.status_cancel'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        pending: <?= json_encode(lang('app.pending'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        sentKitchen: <?= json_encode(lang('app.sent_to_kitchen'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        cooking: <?= json_encode(lang('app.cooking'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        served: <?= json_encode(lang('app.served'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        canceled: <?= json_encode(lang('app.canceled'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        preparingStatus: <?= json_encode(service('request')->getLocale() === 'th' ? 'กำลังทำ' : 'Preparing', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        readyStatus: <?= json_encode(service('request')->getLocale() === 'th' ? 'พร้อมเสิร์ฟ' : 'Ready', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        cancelRequestedStatus: <?= json_encode(service('request')->getLocale() === 'th' ? 'รออนุมัติยกเลิก' : 'Waiting for cancel approval', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        cancelRejectedStatus: <?= json_encode(service('request')->getLocale() === 'th' ? 'ปฏิเสธการยกเลิกแล้ว' : 'Cancel request rejected', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        cancelledStatus: <?= json_encode(service('request')->getLocale() === 'th' ? 'ยกเลิกแล้ว' : 'Cancelled', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        unknownStatus: <?= json_encode(lang('app.unknown_status'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        addFoodItem: <?= json_encode(lang('app.add_food_item'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        editFoodItem: <?= json_encode(lang('app.edit_food_item'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        addToBill: <?= json_encode(lang('app.add_to_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        saveEdit: <?= json_encode(lang('app.save_edit'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        noQuickOptions: <?= json_encode(lang('app.no_quick_options_for_product'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        loadQuickOptionsFailed: <?= json_encode(lang('app.load_quick_options_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        noItemsYet: <?= json_encode(lang('app.no_food_items_yet'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        detailLabel: <?= json_encode(lang('app.detail'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        optionLabel: <?= json_encode(lang('app.options'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        noteLabel: <?= json_encode(lang('app.note'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        edit: <?= json_encode(lang('app.edit'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        remove: <?= json_encode(lang('app.remove'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        loadBillFailed: <?= json_encode(lang('app.load_bill_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        openBillFirst: <?= json_encode(lang('app.please_open_bill_first'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        billCannotAddItems: <?= json_encode(lang('app.bill_status_cannot_add_items'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        itemCannotEdit: <?= json_encode(lang('app.item_cannot_edit'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        billNotFound: <?= json_encode(lang('app.bill_not_found'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        editItemNotFound: <?= json_encode(lang('app.edit_item_not_found'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        selectedItemNotFound: <?= json_encode(lang('app.selected_item_not_found'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        addItemFailed: <?= json_encode(lang('app.add_item_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        editItemFailed: <?= json_encode(lang('app.edit_item_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        updateItemFailed: <?= json_encode(lang('app.update_item_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        removeItemFailed: <?= json_encode(lang('app.remove_item_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        noBillYet: <?= json_encode(lang('app.no_bill_yet'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        sendKitchenFailed: <?= json_encode(lang('app.send_kitchen_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        billCannotPay: <?= json_encode(lang('app.bill_cannot_pay_yet'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        hasPendingItemsConfirm: <?= json_encode(lang('app.pending_items_pay_confirm'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        zeroBillConfirm: <?= json_encode(lang('app.zero_bill_confirm'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        paymentAmountInvalid: <?= json_encode(lang('app.invalid_payment_amount'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        amountLessThanBill: <?= json_encode(lang('app.received_less_than_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        paymentFailed: <?= json_encode(lang('app.payment_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        paymentSuccess: <?= json_encode(lang('app.payment_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        managerOverrideRequired: <?= json_encode(lang('app.manager_override_required'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        managerOverrideHelpPinOnly: <?= json_encode(lang('app.manager_override_help_pin_only'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        managerOverrideApproved: <?= json_encode(lang('app.manager_override_approved'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        managerOverrideFailed: <?= json_encode(lang('app.manager_override_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        managerOverrideActionPay: <?= json_encode(lang('app.manager_override_action_pay'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        closeBillSuccess: <?= json_encode(lang('app.close_bill_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        changeLabel: <?= json_encode(lang('app.change'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        allProductsDisabled: <?= json_encode(lang('app.table_disabled'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        moveTableFailed: <?= json_encode(lang('app.move_table_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        moveTableSuccess: <?= json_encode(lang('app.move_table_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        moveBill: <?= json_encode(lang('app.move_table'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        moveAuditSummary: <?= json_encode(lang('app.move_audit_summary'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        movedBillNotice: <?= json_encode(lang('app.moved_bill_notice'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        movedFrom: <?= json_encode(lang('app.moved_from'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        movedTo: <?= json_encode(lang('app.moved_to'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        movedFromTables: <?= json_encode(lang('app.moved_from_tables'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        moveAuditCount: <?= json_encode(lang('app.move_audit_count'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        viewMoveAudit: <?= json_encode(lang('app.view_move_audit'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        movedBy: <?= json_encode(lang('app.moved_by'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        movedAt: <?= json_encode(lang('app.moved_at'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        moveReason: <?= json_encode(lang('app.move_reason'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        noMoveReason: <?= json_encode(lang('app.no_move_reason'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        destinationBusy: <?= json_encode(lang('app.destination_table_has_open_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergeBill: <?= json_encode(lang('app.merge_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergeBillFailed: <?= json_encode(lang('app.merge_bill_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergeBillSuccess: <?= json_encode(lang('app.merge_bill_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergedFromTables: <?= json_encode(lang('app.merged_from_tables'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        sourceBill: <?= json_encode(lang('app.source_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergedBy: <?= json_encode(lang('app.merged_by'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergedAt: <?= json_encode(lang('app.merged_at'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergeReason: <?= json_encode(lang('app.merge_reason'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        noMergeReason: <?= json_encode(lang('app.no_merge_reason'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergeAuditTitle: <?= json_encode(lang('app.merge_audit_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergeAuditSummary: <?= json_encode(lang('app.merge_audit_summary'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        viewMergeAudit: <?= json_encode(lang('app.view_merge_audit'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergedSourcesCount: <?= json_encode(lang('app.merged_sources_count'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        selectTargetBill: <?= json_encode(lang('app.select_target_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        cancelRequestPending: <?= json_encode(service('request')->getLocale() === 'th' ? 'รออนุมัติยกเลิก' : 'Waiting for cancel approval', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        cancelRequestRejected: <?= json_encode(service('request')->getLocale() === 'th' ? 'ครัวปฏิเสธการยกเลิก' : 'Kitchen rejected cancellation', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        cancelRequestApproved: <?= json_encode(service('request')->getLocale() === 'th' ? 'ครัวอนุมัติยกเลิกแล้ว' : 'Cancellation approved by kitchen', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        itemStillBillable: <?= json_encode(service('request')->getLocale() === 'th' ? 'รายการนี้ยังต้องคิดเงินตามปกติ' : 'This item is still billable', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        retryCancelRequest: <?= json_encode(service('request')->getLocale() === 'th' ? 'ขอยกเลิกอีกครั้ง' : 'Request cancel again', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        acknowledge: <?= json_encode(service('request')->getLocale() === 'th' ? 'รับทราบ' : 'Acknowledge', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        billHasPendingCancelRequest: <?= json_encode(service('request')->getLocale() === 'th' ? 'มีรายการรออนุมัติยกเลิก' : 'There are items waiting for cancel approval', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        billHasRejectedCancelRequest: <?= json_encode(service('request')->getLocale() === 'th' ? 'มีรายการถูกปฏิเสธการยกเลิก' : 'There are items with rejected cancel requests', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        requestCancelToKitchen: <?= json_encode(service('request')->getLocale() === 'th' ? 'ขอยกเลิก' : 'Request cancel', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        requestCancelSentToKitchen: <?= json_encode(service('request')->getLocale() === 'th' ? 'ส่งคำขอยกเลิกไปที่ครัวแล้ว' : 'Cancel request sent to kitchen', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        canceledItemsSection: <?= json_encode(service('request')->getLocale() === 'th' ? 'รายการที่ยกเลิกแล้ว' : 'Cancelled items', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        canceledItemsNoCharge: <?= json_encode(service('request')->getLocale() === 'th' ? 'รายการนี้ไม่คิดเงินแล้ว' : 'This item is no longer billable', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        noData: <?= json_encode(lang('app.no_data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        recentItems: <?= json_encode($recentItemsLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        more: <?= json_encode(service('request')->getLocale() === 'th' ? 'เพิ่มเติม' : 'More', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };

    const productOptionModalEl = document.getElementById('productOptionModal');
    const productOptionModal = productOptionModalEl ? new bootstrap.Modal(productOptionModalEl) : null;

    const paymentModalEl = document.getElementById('paymentModal');
    const paymentModal = paymentModalEl ? new bootstrap.Modal(paymentModalEl) : null;
    const mergeAuditModalEl = document.getElementById('mergeAuditModal');
    const mergeAuditModal = mergeAuditModalEl ? new bootstrap.Modal(mergeAuditModalEl) : null;
    const moveAuditModalEl = document.getElementById('moveAuditModal');
    const moveAuditModal = moveAuditModalEl ? new bootstrap.Modal(moveAuditModalEl) : null;
    const managerOverrideModalEl = document.getElementById('managerOverrideModal');
    const managerOverrideModal = managerOverrideModalEl ? new bootstrap.Modal(managerOverrideModalEl) : null;
    let managerOverrideResolver = null;

    function money(num) {
        return '฿' + parseFloat(num || 0).toFixed(2);
    }

    function escapeHtml(text) {
        return $('<div>').text(text ?? '').html();
    }

    function notify(message, type) {
        const text = $.trim(String(message || ''));
        if (!text) {
            return;
        }

        const safeType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
        const container = document.getElementById('posToastContainer');

        if (!container) {
            window.notify(text);
            return;
        }

        const toast = document.createElement('div');
        toast.className = 'pos-toast-item toast-' + safeType;
        toast.textContent = text;
        container.appendChild(toast);

        window.requestAnimationFrame(function () {
            toast.classList.add('show');
        });

        window.setTimeout(function () {
            toast.classList.remove('show');
            window.setTimeout(function () {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 220);
        }, 2600);
    }

    function getBillTotalNumber() {
        return parseFloat(($('#billTotal').text() || '0').replace('฿', '').replace(/,/g, '').trim() || 0);
    }

    function roundUp(value, step) {
        value = parseFloat(value || 0);
        step = parseFloat(step || 1);
        return Math.ceil(value / step) * step;
    }

    function updatePaymentSummary() {
        const total = getBillTotalNumber();
        const method = $('#paymentMethod').val();
        let amount = parseFloat($('#paymentAmount').val() || 0);

        if (method !== 'cash') {
            amount = total;
            $('#paymentCashWrap').hide();
        } else {
            $('#paymentCashWrap').show();
        }

        const change = Math.max(amount - total, 0);

        $('#paymentBillTotal').text(money(total));
        $('#paymentChange').text(money(change));
    }


    function managerOverrideActionLabel(actionKey) {
        if (actionKey === 'pay') {
            return TXT.managerOverrideActionPay || TXT.managerOverrideRequired;
        }
        return TXT.managerOverrideRequired;
    }


    function normalizeSearchText(text) {
        return String(text || '').toLowerCase().trim();
    }

    function setBillDrawerOpen(isOpen) {
        $('body').toggleClass('pos-bill-drawer-open', !!isOpen);
    }

    function updateMobileBillSummary(itemCount, totalAmount) {
        $('#mobileBillSummaryMeta').text((itemCount || 0) + ' · ' + ($('#orderNoLabel').text().trim() || '-'));
        $('#mobileBillSummaryTotal').text(money(totalAmount || 0));
    }

    function renderRecentProductToolbar(items) {
        const list = Array.isArray(items) ? items : [];
        const seen = new Set();
        const recent = [];

        list.forEach(function (item) {
            const productId = Number(item && item.product_id ? item.product_id : 0);
            const productName = $.trim(item && item.product_name ? item.product_name : '');
            const productPrice = parseFloat(item && item.price ? item.price : 0);

            if (!productId || !productName || seen.has(productId)) {
                return;
            }

            seen.add(productId);
            recent.push({
                product_id: productId,
                product_name: productName,
                price: productPrice
            });
        });

        const visible = recent.slice(0, 2);
        const extraCount = Math.max(recent.length - visible.length, 0);
        let html = '';

        visible.forEach(function (item) {
            html += '<button type="button" class="btn pos-inline-chip btn-inline-recent" data-id="' + item.product_id + '" data-name="' + escapeHtml(item.product_name) + '" data-price="' + item.price + '">' + escapeHtml(item.product_name) + '</button>';
        });

        if (extraCount > 0) {
            html += '<button type="button" class="btn pos-inline-chip pos-inline-chip-more" id="btnRecentItemsMore">+' + extraCount + '</button>';
        }

        $('#recentProductChips').html(html);
        $('#recentProductToolbar').toggleClass('show', recent.length > 0);
    }

    function applyProductFilters() {
        const activeCategoryId = String($('.category-btn.active').data('id') || 'all');
        const keyword = normalizeSearchText($('#productSearch').val());
        let visibleCount = 0;

        $('#activeCategoryLabel').text($('.category-btn.active').first().text().trim() || '<?= esc(lang('app.all')) ?>');

        $('.product-card').each(function () {
            const $card = $(this);
            const categoryId = String($card.data('category') || '');
            const searchName = normalizeSearchText($card.data('name') || $card.find('.fw-bold').text());
            const categoryMatch = activeCategoryId === 'all' || categoryId === activeCategoryId;
            const keywordMatch = !keyword || searchName.indexOf(keyword) !== -1;
            const show = categoryMatch && keywordMatch;

            $card.toggle(show);

            if (show) {
                visibleCount++;
            }
        });

        $('#productResultCount').text(String(visibleCount));
        $('#productEmptyState').toggleClass('show', visibleCount === 0);
    }

    function requestManagerOverride(actionKey, orderId) {
        return new Promise(function (resolve) {
            managerOverrideResolver = resolve;
            $('#managerOverrideAction').val(actionKey || '');
            $('#managerOverrideOrderId').val(orderId || CURRENT_ORDER_ID || 0);
            $('#managerOverridePinCode').val('');
            $('#managerOverrideHelpText').text((TXT.managerOverrideHelpPinOnly || TXT.managerOverrideRequired || '') + ' - ' + managerOverrideActionLabel(actionKey));

            if (managerOverrideModal) {
                managerOverrideModal.show();
                setTimeout(function () { $('#managerOverridePinCode').trigger('focus').trigger('select'); }, 150);
            } else {
                resolve(false);
            }
        });
    }

    function openPaymentModal() {
        const total = getBillTotalNumber();

        $('#paymentMethod').val('cash');
        $('#paymentAmount').val(total.toFixed(2));
        $('#paymentBillTotal').text(money(total));
        $('#paymentChange').text(money(0));
        $('#paymentCashWrap').show();

        if (paymentModal) {
            paymentModal.show();
        }
    }

    function statusText(status) {
        if (status === 'open') return TXT.openBillStatus;
        if (status === 'billing') return TXT.billingStatus;
        if (status === 'paid') return TXT.paidStatus;
        if (status === 'cancel') return TXT.cancelStatus;
        if (status === 'merged') return <?= json_encode(lang('app.merged_order_status'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        return TXT.noOpenBill;
    }

    function statusBadgeClass(status) {
        if (status === 'open') return 'text-bg-primary';
        if (status === 'billing') return 'text-bg-warning';
        if (status === 'paid') return 'text-bg-success';
        if (status === 'cancel') return 'text-bg-danger';
        if (status === 'merged') return 'text-bg-dark';
        return 'text-bg-secondary';
    }

    function normalizeItemStatus(status) {
        return String(status || 'pending').toLowerCase().trim();
    }

    function itemStatusBadge(status) {
        status = normalizeItemStatus(status);

        if (status === 'pending' || status === 'open' || status === 'new') return 'secondary';
        if (status === 'sent') return 'primary';
        if (status === 'preparing' || status === 'cooking') return 'warning';
        if (status === 'ready') return 'info';
        if (status === 'served') return 'success';
        if (status === 'cancel_requested' || status === 'cancelrequest' || status === 'cancel-request') return 'warning text-dark';
        if (status === 'cancel_rejected' || status === 'rejected') return 'danger';
        if (status === 'cancel' || status === 'cancelled' || status === 'canceled') return 'danger';
        return 'secondary';
    }

    function itemStatusText(status) {
        status = normalizeItemStatus(status);

        if (status === 'pending' || status === 'open' || status === 'new') return TXT.pending;
        if (status === 'sent') return TXT.sentKitchen;
        if (status === 'preparing' || status === 'cooking') return TXT.preparingStatus || TXT.cooking;
        if (status === 'ready') return TXT.readyStatus || TXT.sentKitchen;
        if (status === 'served') return TXT.served;
        if (status === 'cancel_requested' || status === 'cancelrequest' || status === 'cancel-request') return TXT.cancelRequestedStatus || TXT.cancelRequestPending;
        if (status === 'cancel_rejected' || status === 'rejected') return TXT.cancelRejectedStatus || TXT.cancelRequestRejected;
        if (status === 'cancel' || status === 'cancelled' || status === 'canceled') return TXT.cancelledStatus || TXT.canceled;
        return TXT.unknownStatus;
    }

    function getDisplayStatusMeta(item) {
        const requestState = getRequestStateMeta(item);
        const rawStatus = normalizeItemStatus(item && item.status ? item.status : 'pending');

        if (requestState && requestState.key === 'pending') {
            return {
                badgeClass: itemStatusBadge('cancel_requested'),
                text: itemStatusText('cancel_requested')
            };
        }

        if (requestState && requestState.key === 'rejected') {
            return {
                badgeClass: itemStatusBadge('cancel_rejected'),
                text: itemStatusText('cancel_rejected')
            };
        }

        if (requestState && requestState.key === 'approved') {
            return {
                badgeClass: itemStatusBadge('cancelled'),
                text: itemStatusText('cancelled')
            };
        }

        return {
            badgeClass: itemStatusBadge(rawStatus),
            text: itemStatusText(rawStatus)
        };
    }

    function parseCommaText(text) {
        return (text || '')
            .toString()
            .split(',')
            .map(function (x) { return $.trim(x); })
            .filter(function (x) { return x !== ''; });
    }

    function setSelectedQuickNotesFromText(text) {
        const selectedNotes = parseCommaText(text);

        $('.modal-quick-note').each(function () {
            const value = $.trim($(this).data('value') || '');

            if (selectedNotes.includes(value)) {
                $(this).addClass('active btn-dark').removeClass('btn-outline-secondary');
            } else {
                $(this).removeClass('active btn-dark').addClass('btn-outline-secondary');
            }
        });
    }

    function clearModalOption() {
        $('#modalItemDetail').val('').data('manual-detail', '');
        $('#modalItemNote').val('').data('manual-note', '');
        $('#editingItemId').val('');
        $('#editingProductId').val('');
        $('#quickDetailBox').html('<div class="text-muted small">' + TXT.noQuickOptions + '</div>');

        $('.modal-quick-detail, .modal-quick-note')
            .removeClass('active btn-dark')
            .addClass('btn-outline-secondary');

        $('#productOptionModalLabel').text(TXT.addFoodItem);
        $('#btnConfirmAddProduct').text(TXT.addToBill);
    }

    function openProductModal(productId, productName, productPrice) {
        if (!TABLE_IS_ACTIVE) {
            notify(TXT.tableDisabled);
            return;
        }

        SELECTED_PRODUCT_ID = productId;
        SELECTED_PRODUCT_NAME = productName;
        SELECTED_PRODUCT_PRICE = productPrice;

        clearModalOption();

        $('#modalMode').val('add');
        $('#editingProductId').val(productId);
        $('#modalProductName').text(productName);
        $('#modalProductPrice').text(money(productPrice));
        $('#productOptionModalLabel').text(TXT.addFoodItem);
        $('#btnConfirmAddProduct').text(TXT.addToBill);

        loadProductQuickOptions(productId, []);

        if (productOptionModal) {
            productOptionModal.show();
        }
    }

    function openEditItemModal(item) {
        clearModalOption();

        const selectedDetails = parseCommaText(item.item_detail || '');
        const selectedNotes = $.trim(item.note || '');

        SELECTED_PRODUCT_ID = item.product_id || '';

        $('#modalMode').val('edit');
        $('#editingItemId').val(item.id);
        $('#editingProductId').val(item.product_id || '');
        $('#modalProductName').text(item.product_name || '-');
        $('#modalProductPrice').text(money(item.price || 0));
        $('#modalItemDetail').val(item.item_detail || '').data('manual-detail', $.trim(item.item_detail || ''));
        $('#modalItemNote').val(item.note || '').data('manual-note', $.trim(item.note || ''));
        $('#productOptionModalLabel').text(TXT.editFoodItem);
        $('#btnConfirmAddProduct').text(TXT.saveEdit);

        setSelectedQuickNotesFromText(selectedNotes);
        loadProductQuickOptions(item.product_id || '', selectedDetails);

        if (productOptionModal) {
            productOptionModal.show();
        }
    }

    function loadProductQuickOptions(productId, preselectedValues = []) {
        if (!productId) {
            $('#quickDetailBox').html('<div class="text-muted small">' + TXT.noQuickOptions + '</div>');
            return;
        }

        $.get("<?= site_url('pos/product-quick-options') ?>/" + productId)
            .done(function (res) {
                if (!res || res.status !== 'success') {
                    $('#quickDetailBox').html('<div class="text-muted small">' + TXT.noQuickOptions + '</div>');
                    return;
                }

                let html = '';

                (res.options || []).forEach(function (opt) {
                    const rawName = (opt.option_name || '').toString();
                    const safeName = escapeHtml(rawName);
                    const priceAdjust = parseFloat(opt.price_adjust || 0);
                    const priceText = priceAdjust > 0 ? ` (+${money(priceAdjust)})` : '';
                    const isActive = preselectedValues.includes($.trim(rawName));

                    html += `
                        <button
                            type="button"
                            class="btn btn-sm modal-quick-detail ${isActive ? 'btn-dark active' : 'btn-outline-secondary'}"
                            data-value="${rawName.replace(/"/g, '&quot;')}"
                            data-price="${priceAdjust}"
                        >
                            ${safeName}${priceText}
                        </button>
                    `;
                });

                if (html === '') {
                    html = '<div class="text-muted small">' + TXT.noQuickOptions + '</div>';
                }

                $('#quickDetailBox').html(html);
            })
            .fail(function (xhr) {
                console.error('loadProductQuickOptions error:', xhr.responseText);
                $('#quickDetailBox').html('<div class="text-danger small">' + TXT.loadQuickOptionsFailed + '</div>');
            });
    }

    function collectSelectedQuickDetails() {
        const details = [];

        $('#quickDetailBox .modal-quick-detail.active').each(function () {
            const name = $.trim($(this).data('value') || '');
            const priceAdjust = parseFloat($(this).data('price') || 0);

            if (name !== '') {
                details.push({
                    group: <?= json_encode(lang('app.food_detail'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                    name: name,
                    price_adjust: priceAdjust
                });
            }
        });

        return details;
    }

    function collectSelectedQuickDetailNames() {
        return collectSelectedQuickDetails().map(function (row) {
            return $.trim(row.name || '');
        }).filter(function (name) {
            return name !== '';
        });
    }

    function syncQuickDetailsToInput() {
        const selectedDetails = collectSelectedQuickDetailNames();
        const manualDetail = $.trim($('#modalItemDetail').data('manual-detail') || '');
        const merged = [...selectedDetails];

        if (manualDetail !== '') {
            const exists = merged.some(function (text) {
                return $.trim(text) === manualDetail;
            });

            if (!exists) {
                merged.push(manualDetail);
            }
        }

        $('#modalItemDetail').val(merged.join(', '));
    }

    function collectSelectedQuickNotes() {
        const notes = [];

        $('.modal-quick-note.active').each(function () {
            const value = $.trim($(this).data('value') || '');
            if (value !== '') {
                notes.push(value);
            }
        });

        return notes;
    }

    function syncQuickNotesToInput() {
        const selectedNotes = collectSelectedQuickNotes();
        const manualNote = $.trim($('#modalItemNote').data('manual-note') || '');
        const merged = [...selectedNotes];

        if (manualNote !== '') {
            const exists = merged.some(function (text) {
                return $.trim(text) === manualNote;
            });

            if (!exists) {
                merged.push(manualNote);
            }
        }

        $('#modalItemNote').val(merged.join(', '));
    }
	
    function renderMergeTrace(order = null) {
        const traces = Array.isArray(order && order.merge_trace) ? order.merge_trace : [];

        if (!traces.length) {
            $('#mergeTraceBox').html('');
            $('#billMergeAuditBox').html('');
            $('#mergeAuditModalBody').html('<div class="text-muted">' + escapeHtml(TXT.noData) + '</div>');
            return;
        }

        const compactTableNames = traces
            .map(function (trace) {
                return $.trim(trace.source_table_name || '');
            })
            .filter(function (value, index, array) {
                return value !== '' && array.indexOf(value) === index;
            });

        const compactLabel = compactTableNames.length ? compactTableNames.join(', ') : '-';
        const summaryHtml = `
            <div class="border rounded-4 px-3 py-2 bg-light-subtle">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="small text-muted mb-1">${escapeHtml(TXT.mergeAuditSummary)}</div>
                        <div class="fw-semibold">${escapeHtml(TXT.mergedFromTables)}: ${escapeHtml(compactLabel)}</div>
                        <div class="small text-muted">${escapeHtml(TXT.mergedSourcesCount)}: ${escapeHtml(String(traces.length))}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="btnViewMergeAudit">${escapeHtml(TXT.viewMergeAudit)}</button>
                </div>
            </div>
        `;

        $('#billMergeAuditBox').html(summaryHtml);

        const itemsHtml = traces.map(function (trace, index) {
            const sourceTableName = escapeHtml(trace.source_table_name || '-');
            const sourceOrderNumber = escapeHtml(trace.source_order_number || '-');
            const mergedByName = escapeHtml(trace.merged_by_name || '-');
            const mergedAt = escapeHtml(trace.merged_at || '-');
            const reason = $.trim(trace.reason || '') !== ''
                ? escapeHtml(trace.reason || '')
                : escapeHtml(TXT.noMergeReason);

            return `
                <div class="border rounded-4 p-3 ${index > 0 ? 'mt-2' : ''}">
                    <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
                        <span class="badge rounded-pill text-bg-warning">${escapeHtml(TXT.mergeBill)}</span>
                        <span class="badge rounded-pill text-bg-dark">${sourceTableName}</span>
                        <span class="badge rounded-pill text-bg-secondary">#${sourceOrderNumber}</span>
                    </div>
                    <div class="small text-muted">${escapeHtml(TXT.sourceBill)}: ${sourceOrderNumber}</div>
                    <div class="small text-muted">${escapeHtml(TXT.mergedBy)}: ${mergedByName}</div>
                    <div class="small text-muted">${escapeHtml(TXT.mergedAt)}: ${mergedAt}</div>
                    <div class="small text-muted">${escapeHtml(TXT.mergeReason)}: ${reason}</div>
                </div>
            `;
        }).join('');

        $('#mergeAuditModalBody').html(itemsHtml);
        $('#mergeTraceBox').html('');
    }


    function renderMoveTrace(order = null) {
        const movedNotice = order && order.moved_notice ? order.moved_notice : null;
        const traces = Array.isArray(order && order.move_trace) ? order.move_trace : [];
        const detailRows = traces.length ? traces : (movedNotice ? [movedNotice] : []);

        if (!detailRows.length) {
            $('#billMoveAuditBox').html('');
            $('#moveAuditModalBody').html('<div class="text-muted">' + escapeHtml(TXT.noData) + '</div>');
            return;
        }

        const latestMove = detailRows[detailRows.length - 1];
        const uniqueFromTables = detailRows
            .map(function (trace) {
                return $.trim(trace.from_table_name || '');
            })
            .filter(function (value, index, array) {
                return value !== '' && array.indexOf(value) === index;
            });

        const compactLabel = uniqueFromTables.length ? uniqueFromTables.join(', ') : '-';
        const latestFromTable = escapeHtml(latestMove && latestMove.from_table_name ? latestMove.from_table_name : '-');
        const latestToTable = escapeHtml(latestMove && latestMove.to_table_name ? latestMove.to_table_name : '-');

        const summaryHtml = `
            <div class="border rounded-4 px-3 py-2 bg-light-subtle">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="small text-muted mb-1">${escapeHtml(TXT.moveAuditSummary)}</div>
                        <div class="fw-semibold">${escapeHtml(TXT.movedFrom)}: ${latestFromTable}</div>
                        <div class="small text-muted">${escapeHtml(TXT.movedTo)}: ${latestToTable}</div>
                        <div class="small text-muted">${escapeHtml(TXT.movedFromTables)}: ${escapeHtml(compactLabel)}</div>
                        <div class="small text-muted">${escapeHtml(TXT.moveAuditCount)}: ${escapeHtml(String(detailRows.length))}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="btnViewMoveAudit">${escapeHtml(TXT.viewMoveAudit)}</button>
                </div>
            </div>
        `;

        $('#billMoveAuditBox').html(summaryHtml);

        const itemsHtml = detailRows.map(function (trace, index) {
            const fromTableName = escapeHtml(trace.from_table_name || '-');
            const toTableName = escapeHtml(trace.to_table_name || '-');
            const sourceOrderNumber = escapeHtml(trace.order_number || '-');
            const movedByName = escapeHtml(trace.moved_by_name || '-');
            const movedAt = escapeHtml(trace.moved_at || '-');
            const reason = $.trim(trace.reason || '') !== ''
                ? escapeHtml(trace.reason || '')
                : escapeHtml(TXT.noMoveReason);

            return `
                <div class="border rounded-4 p-3 ${index > 0 ? 'mt-2' : ''}">
                    <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
                        <span class="badge rounded-pill text-bg-primary">${escapeHtml(TXT.moveBill)}</span>
                        <span class="badge rounded-pill text-bg-secondary">${escapeHtml(TXT.movedFrom)}: ${fromTableName}</span>
                        <span class="badge rounded-pill text-bg-light text-dark">${escapeHtml(TXT.movedTo)}: ${toTableName}</span>
                        <span class="badge rounded-pill text-bg-dark">#${sourceOrderNumber}</span>
                    </div>
                    <div class="small text-muted">${escapeHtml(TXT.sourceBill)}: ${sourceOrderNumber}</div>
                    <div class="small text-muted">${escapeHtml(TXT.movedBy)}: ${movedByName}</div>
                    <div class="small text-muted">${escapeHtml(TXT.movedAt)}: ${movedAt}</div>
                    <div class="small text-muted">${escapeHtml(TXT.moveReason)}: ${reason}</div>
                </div>
            `;
        }).join('');

        $('#moveAuditModalBody').html(itemsHtml);
    }

    function renderOrderMetaIndicators(order = null) {
        const indicators = [];
        const mergedNotice = order && order.merged_notice ? order.merged_notice : null;
        const traces = Array.isArray(order && order.merge_trace) ? order.merge_trace : [];
        const movedNotice = order && order.moved_notice ? order.moved_notice : null;
        const moveTrace = Array.isArray(order && order.move_trace) ? order.move_trace : [];
        const latestMove = moveTrace.length ? moveTrace[moveTrace.length - 1] : null;

        if (traces.length > 0) {
            indicators.push(`
                <span class="badge rounded-pill text-bg-warning">${escapeHtml(TXT.mergeBill)}</span>
                <span class="badge rounded-pill text-bg-secondary">${escapeHtml(TXT.mergedSourcesCount)}: ${escapeHtml(String(traces.length))}</span>
            `);
        }

        if (mergedNotice && mergedNotice.target_table_name) {
            const targetTableName = escapeHtml(mergedNotice.target_table_name || '-');
            const targetOrderNumber = escapeHtml(mergedNotice.target_order_number || '-');

            indicators.push(`
                <span class="badge rounded-pill text-bg-warning">${escapeHtml(TXT.mergeBill)} → ${targetTableName}</span>
                <span class="badge rounded-pill text-bg-dark">#${targetOrderNumber}</span>
            `);
        }

        if (movedNotice && movedNotice.to_table_name) {
            const targetTableName = escapeHtml(movedNotice.to_table_name || '-');
            const orderNumber = escapeHtml(movedNotice.order_number || '-');

            indicators.push(`
                <span class="badge rounded-pill text-bg-primary">${escapeHtml(TXT.movedBillNotice)}</span>
                <span class="badge rounded-pill text-bg-secondary">${escapeHtml(TXT.movedTo)}: ${targetTableName}</span>
                <span class="badge rounded-pill text-bg-dark">#${orderNumber}</span>
            `);
        }

        if (latestMove && latestMove.from_table_name) {
            const fromTableName = escapeHtml(latestMove.from_table_name || '-');
            const toTableName = escapeHtml(latestMove.to_table_name || '-');

            indicators.push(`
                <span class="badge rounded-pill text-bg-primary">${escapeHtml(TXT.movedBillNotice)}</span>
                <span class="badge rounded-pill text-bg-secondary">${escapeHtml(TXT.movedFrom)}: ${fromTableName}</span>
                <span class="badge rounded-pill text-bg-light text-dark">${escapeHtml(TXT.movedTo)}: ${toTableName}</span>
            `);
        }

        $('#orderMetaIndicators').html(indicators.join(''));
    }

    function updateOrderHeader(order = null) {
		const $badge = $('#orderStatusBadge');
		$badge.removeClass('text-bg-primary text-bg-warning text-bg-success text-bg-danger text-bg-secondary text-bg-dark');
		$badge.addClass(statusBadgeClass(CURRENT_ORDER_STATUS));
		$badge.text(TXT.billStatus + ': ' + statusText(CURRENT_ORDER_STATUS));

        renderOrderMetaIndicators(order);
        renderMergeTrace(order);
        renderMoveTrace(order);

		if (!TABLE_IS_ACTIVE) {
			$('#btnOpenOrder').prop('disabled', true).text(TXT.tableDisabled);
			$('#btnSendKitchen').prop('disabled', true);
			$('#btnPay').prop('disabled', true);
			$('#btnMoveTable').prop('disabled', true);
			$('#btnMergeBill').prop('disabled', true);
			$('.product-btn').prop('disabled', true);
		} else {
			if (CURRENT_ORDER_ID && (CURRENT_ORDER_STATUS === 'open' || CURRENT_ORDER_STATUS === 'billing')) {
				$('#btnOpenOrder').prop('disabled', true).text(TXT.tableAlreadyOpen);
			} else {
				$('#btnOpenOrder').prop('disabled', false).text(TXT.openBill);
			}

			$('#btnSendKitchen').prop('disabled', CURRENT_ORDER_STATUS !== 'open');
			$('#btnPay').prop('disabled', !(CURRENT_ORDER_STATUS === 'open' || CURRENT_ORDER_STATUS === 'billing'));
			$('#btnMoveTable').prop('disabled', !(CURRENT_ORDER_STATUS === 'open' || CURRENT_ORDER_STATUS === 'billing'));
			$('#btnMergeBill').prop('disabled', !(CURRENT_ORDER_STATUS === 'open' || CURRENT_ORDER_STATUS === 'billing'));
			$('.product-btn').prop('disabled', false);
		}

		if (CURRENT_ORDER_STATUS === 'open') {
			$('#btnPay').text(<?= json_encode(lang('app.close_bill_pay'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
		} else if (CURRENT_ORDER_STATUS === 'billing') {
			$('#btnPay').text(<?= json_encode(lang('app.pay'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
		} else {
			$('#btnPay').text(<?= json_encode(lang('app.close_bill_pay'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
		}

		if (order && order.order_number) {
			$('#orderNoLabel').text(order.order_number);
		} else {
			$('#orderNoLabel').text('-');
		}
	}

    function canEditItem(status) {
        return !status || status === 'pending' || status === 'open' || status === 'new';
    }

    function getCancelRequestStatus(item) {
        return String(item.cancel_request_status || item.request_cancel_status || item.cancel_status || '').toLowerCase().trim();
    }

    function getRequestStateMeta(item) {
        const requestStatus = getCancelRequestStatus(item);

        if (requestStatus === 'pending' || requestStatus === 'requested' || requestStatus === 'waiting') {
            return {
                key: 'pending',
                badgeClass: 'warning text-dark',
                title: TXT.cancelRequestPending,
                hint: '',
            };
        }

        if (requestStatus === 'rejected' || requestStatus === 'deny' || requestStatus === 'declined') {
            return {
                key: 'rejected',
                badgeClass: 'danger',
                title: TXT.cancelRequestRejected,
                hint: TXT.itemStillBillable,
            };
        }

        if (requestStatus === 'approved' || requestStatus === 'accepted') {
            return {
                key: 'approved',
                badgeClass: 'success',
                title: TXT.cancelRequestApproved,
                hint: '',
            };
        }

        return null;
    }

    function renderBillRequestAlerts(items) {
        const list = Array.isArray(items) ? items : [];
        const pendingCount = list.filter(function (item) {
            const state = getRequestStateMeta(item);
            return state && state.key === 'pending';
        }).length;

        const rejectedCount = list.filter(function (item) {
            const state = getRequestStateMeta(item);
            return state && state.key === 'rejected';
        }).length;

        let html = '';

        if (pendingCount > 0) {
            html += `
                <div class="alert alert-warning border-0 rounded-4 py-2 px-3 mb-2">
                    <div class="fw-semibold">${escapeHtml(TXT.billHasPendingCancelRequest)}</div>
                    <div class="small">${pendingCount}</div>
                </div>
            `;
        }

        if (rejectedCount > 0) {
            html += `
                <div class="alert alert-danger border-0 rounded-4 py-2 px-3 mb-2">
                    <div class="fw-semibold">${escapeHtml(TXT.billHasRejectedCancelRequest)}</div>
                    <div class="small">${rejectedCount}</div>
                </div>
            `;
        }

        $('#billRequestAlertBox').html(html);
    }

    function renderItems(order, items) {
        let activeHtml = '';
        let canceledHtml = '';

        if (!items || items.length === 0) {
            activeHtml = '<div class="text-muted">' + TXT.noItemsYet + '</div>';
            $('#billRequestAlertBox').html('');
            $('#canceledItemsBox').html('');
        } else {
            items.forEach(function (item) {
                const normalizedStatus = normalizeItemStatus(item.status || 'pending');
                const editable = canEditItem(item.status);
                const requestState = getRequestStateMeta(item);
                const displayStatus = getDisplayStatusMeta(item);
                const isCanceled = normalizedStatus === 'cancel' || normalizedStatus === 'cancelled' || normalizedStatus === 'canceled' || (requestState && requestState.key === 'approved');
                const canRequestCancel = !editable && !isCanceled && (!requestState || requestState.key === 'rejected');

                let requestHtml = '';
                let actionButtons = '';
                let secondaryButtons = '';

                if (requestState) {
                    requestHtml = `
                        <div class="mt-2">
                            <div class="alert alert-${requestState.key === 'pending' ? 'warning' : requestState.key === 'rejected' ? 'danger' : 'secondary'} border-0 rounded-4 py-2 px-3 mb-2">
                                <div class="fw-semibold small">${escapeHtml(requestState.title)}</div>
                                ${requestState.hint ? `<div class="small mt-1">${escapeHtml(requestState.hint)}</div>` : ''}
                            </div>
                        </div>
                    `;

                    if (requestState.key === 'rejected') {
                        secondaryButtons = `
                            <div class="d-flex gap-2 flex-wrap mt-2">
                                <button type="button" class="btn btn-outline-danger btn-sm btn-retry-cancel-request" data-id="${item.id}">${escapeHtml(TXT.retryCancelRequest)}</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm btn-ack-cancel-request" data-id="${item.id}">${escapeHtml(TXT.acknowledge)}</button>
                            </div>
                        `;
                    }
                }

                if (editable) {
                    actionButtons = `
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary btn-qty" data-id="${item.id}" data-type="minus">-</button>
                            <button type="button" class="btn btn-outline-secondary btn-qty" data-id="${item.id}" data-type="plus">+</button>
                            <button
                                type="button"
                                class="btn btn-outline-primary btn-edit-item"
                                data-id="${item.id}"
                                data-product-id="${item.product_id ?? ''}"
                                data-product-name="${escapeHtml(item.product_name ?? '')}"
                                data-price="${item.price ?? 0}"
                                data-item-detail="${escapeHtml(item.item_detail ?? '')}"
                                data-note="${escapeHtml(item.note ?? '')}"
                                data-status="${escapeHtml(item.status ?? '')}"
                            >${TXT.edit}</button>
                            <button type="button" class="btn btn-outline-danger btn-cancel-direct" data-id="${item.id}">${TXT.remove}</button>
                        </div>
                    `;
                } else if (canRequestCancel) {
                    actionButtons = `
                        <button type="button" class="btn btn-outline-danger btn-sm btn-request-cancel" data-id="${item.id}">${escapeHtml(TXT.requestCancelToKitchen)}</button>
                    `;
                }

                const cardHtml = `
                    <div class="border rounded-4 p-2 mb-2 ${isCanceled ? 'bg-light' : ''}" data-item-status="${escapeHtml(item.status ?? 'pending')}" data-cancel-request-status="${escapeHtml(getCancelRequestStatus(item))}">
                        <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                            <div class="fw-bold">${escapeHtml(item.product_name ?? '')}</div>
                            <span class="badge bg-${displayStatus.badgeClass}">${escapeHtml(displayStatus.text)}</span>
                        </div>

                        ${item.item_detail ? `<div class="small text-dark">${TXT.detailLabel}: ${escapeHtml(item.item_detail)}</div>` : ''}
                        ${item.option_summary ? `<div class="small text-primary">${TXT.optionLabel}: ${escapeHtml(item.option_summary)}</div>` : ''}
                        ${item.note ? `<div class="small text-muted mb-2">${TXT.noteLabel}: ${escapeHtml(item.note)}</div>` : ''}
                        ${requestHtml}

                        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                            <div>${money(item.price)} x ${item.qty}</div>
                            <div class="d-flex gap-2 flex-wrap">
                                ${actionButtons}
                            </div>
                        </div>

                        ${isCanceled ? `<div class="small text-danger mt-2 fw-semibold">${escapeHtml(TXT.canceledItemsNoCharge)}</div>` : ''}
                        ${secondaryButtons}
                    </div>
                `;

                if (isCanceled) {
                    canceledHtml += cardHtml;
                } else {
                    activeHtml += cardHtml;
                }
            });

            renderBillRequestAlerts(items);
        }

        if (!activeHtml) {
            activeHtml = '<div class="text-muted">' + TXT.noItemsYet + '</div>';
        }

        if (canceledHtml) {
            canceledHtml = `
                <div class="mt-3 pt-3 border-top">
                    <div class="fw-bold mb-2 text-danger">${escapeHtml(TXT.canceledItemsSection)}</div>
                    ${canceledHtml}
                </div>
            `;
        }

        const activeItemCount = items.filter(function (item) {
            const normalizedStatus = normalizeItemStatus(item.status || 'pending');
            const requestState = getRequestStateMeta(item);
            return !(normalizedStatus === 'cancel' || normalizedStatus === 'cancelled' || normalizedStatus === 'canceled' || (requestState && requestState.key === 'approved'));
        }).length;

        $('#orderBox').html(activeHtml);
        $('#canceledItemsBox').html(canceledHtml);
        $('#billTotal').text(money(order.total_price || 0));
        updateMobileBillSummary(activeItemCount, order.total_price || 0);
        renderRecentProductToolbar(items || []);
    }

    function isAnyModalOpen() {
        return $('#productOptionModal').hasClass('show')
            || $('#paymentModal').hasClass('show')
            || $('#moveTableModal').hasClass('show')
            || $('#mergeBillModal').hasClass('show');
    }

    function loadOrder() {
		if (isAnyModalOpen()) {
			return;
		}

		$.get("<?= site_url('pos/current-order') ?>/" + TABLE_ID)
			.done(function (res) {
				if (!res) {
					CURRENT_ORDER_ID = null;
					CURRENT_ORDER_STATUS = null;
					$('#orderBox').html('<div class="text-muted">' + TXT.noBillYet + '</div>');
					$('#billTotal').text('฿0.00');
					updateOrderHeader(null);
					return;
				}

				if (res.status === 'empty') {
					CURRENT_ORDER_ID = null;
					CURRENT_ORDER_STATUS = null;
					$('#orderBox').html('<div class="text-muted">' + TXT.noBillYet + '</div>');
					$('#billTotal').text('฿0.00');
					updateOrderHeader({
						merged_notice: res.merged_notice || null,
                        moved_notice: res.moved_notice || null,
                        move_trace: Array.isArray(res.move_trace) ? res.move_trace : []
					});
					return;
				}

				if (res.status !== 'success') {
					CURRENT_ORDER_ID = null;
					CURRENT_ORDER_STATUS = null;
					$('#orderBox').html('<div class="text-muted">' + TXT.noBillYet + '</div>');
					$('#billTotal').text('฿0.00');
					updateOrderHeader(null);
					return;
				}

				CURRENT_ORDER_ID = res.order.id;
				CURRENT_ORDER_STATUS = res.order.status || 'open';

				const orderData = Object.assign({}, res.order, {
					merged_notice: res.merged_notice || null,
					merge_trace: Array.isArray(res.merge_trace) ? res.merge_trace : [],
                    moved_notice: res.moved_notice || null,
                    move_trace: Array.isArray(res.move_trace) ? res.move_trace : []
				});

				renderItems(orderData, res.items || []);
				updateOrderHeader(orderData);
			})
			.fail(function (xhr) {
				console.error('loadOrder error:', xhr.responseText);
				$('#orderBox').html('<div class="text-danger">' + TXT.loadBillFailed + '</div>');
				$('#billTotal').text('฿0.00');
			});
	}

    function startAutoRefresh() {
        if (AUTO_REFRESH_TIMER) {
            clearInterval(AUTO_REFRESH_TIMER);
        }

        AUTO_REFRESH_TIMER = setInterval(function () {
            if (TABLE_ID > 0 && !isAnyModalOpen()) {
                loadOrder();
            }
        }, 5000);
    }

    function loadMoveTableOptions() {
        if (!CURRENT_ORDER_ID) {
            return;
        }

        $('#moveToTableId').html('<option value=""><?= esc(lang('app.please_select')) ?></option>');
        $('#moveTableHint').text('');

        $.get("<?= site_url('pos/available-tables') ?>/" + CURRENT_ORDER_ID)
            .done(function (res) {
                if (!res || res.status !== 'success') {
                    return;
                }

                let html = '<option value=""><?= esc(lang('app.please_select')) ?></option>';

                (res.tables || []).forEach(function (row) {
                    const disabled = Number(row.has_open_order || 0) === 1 ? 'disabled' : '';
                    const busyText = Number(row.has_open_order || 0) === 1 ? ' (' + TXT.billStatus + ')' : '';
                    html += '<option value="' + row.id + '" ' + disabled + '>' + row.table_name + busyText + '</option>';
                });

                $('#moveToTableId').html(html);
            });
    }

    function loadMergeTargets() {
		if (!CURRENT_ORDER_ID) {
			return;
		}

		$('#mergeTargetOrderId').html('<option value="">' + TXT.selectTargetBill + '</option>');

		$.get("<?= site_url('pos/merge-targets') ?>/" + CURRENT_ORDER_ID)
			.done(function (res) {
				if (!res || res.status !== 'success') {
					return;
				}

				let html = '<option value="">' + TXT.selectTargetBill + '</option>';

				(res.targets || []).forEach(function (row) {
					const orderId = Number(row.order_id || row.id || 0);
					const orderNumber = row.order_number || ('#' + orderId);
					const tableName = row.table_name || '-';
					const totalPrice = Number(row.total_price || 0).toFixed(2);

					const label = tableName + ' / ' + orderNumber + ' / ฿' + totalPrice;
					html += '<option value="' + orderId + '">' + label + '</option>';
				});

				$('#mergeTargetOrderId').html(html);
			})
			.fail(function (xhr) {
				console.error('loadMergeTargets error:', xhr.responseText);
				$('#mergeTargetOrderId').html('<option value="">' + TXT.selectTargetBill + '</option>');
			});
	}

    $(document).on('click', '#btnOpenOrder', function () {
        if (!TABLE_IS_ACTIVE) {
            notify(TXT.tableDisabled);
            return;
        }

        $.post("<?= site_url('pos/open-order') ?>", {
            table_id: TABLE_ID
        })
        .done(function (res) {
            if (res.status === 'success') {
                CURRENT_ORDER_ID = res.order_id;
                CURRENT_ORDER_STATUS = 'open';
                loadOrder();
            } else {
                notify(res.message || TXT.loadBillFailed);
            }
        })
        .fail(function (xhr) {
            console.error('openOrder error:', xhr.responseText);
            notify(TXT.loadBillFailed);
        });
    });

    $(document).on('click', '.product-btn', function () {
        if (!CURRENT_ORDER_ID) {
            notify(TXT.openBillFirst);
            return;
        }

        if (!(CURRENT_ORDER_STATUS === 'open' || CURRENT_ORDER_STATUS === 'billing')) {
            notify(TXT.billCannotAddItems);
            return;
        }

        const productId = $(this).data('id');
        const productName = $(this).data('name') || $(this).find('.fw-bold').text().trim();
        const productPriceText = $(this).find('.text-muted').text().replace('฿', '').replace(/,/g, '').trim();
        const productPrice = parseFloat(productPriceText || 0);

        openProductModal(productId, productName, productPrice);
        loadProductQuickOptions(productId);
    });

    $(document).on('click', '.btn-inline-recent', function () {
        if (!CURRENT_ORDER_ID) {
            notify(TXT.openBillFirst);
            return;
        }

        const productId = Number($(this).data('id') || 0);
        const productName = $(this).data('name') || '';
        const productPrice = parseFloat($(this).data('price') || 0);

        if (!productId || !productName) {
            return;
        }

        openProductModal(productId, productName, productPrice);
        loadProductQuickOptions(productId);
    });

    $(document).on('click', '#btnRecentItemsMore', function () {
        $('#orderBox .pos-bill-item, #orderBox .border.rounded-4').first().get(0)?.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    });

    $(document).on('click', '.btn-edit-item', function () {
        const item = {
            id: $(this).data('id'),
            product_id: $(this).data('product-id'),
            product_name: $(this).data('product-name'),
            price: $(this).data('price'),
            item_detail: $(this).data('item-detail'),
            note: $(this).data('note'),
            status: $(this).data('status')
        };

        if (!canEditItem(item.status)) {
            notify(TXT.itemCannotEdit);
            return;
        }

        openEditItemModal(item);
    });

    $(document).on('click', '.modal-quick-detail', function () {
        $(this).toggleClass('active btn-dark btn-outline-secondary');
        syncQuickDetailsToInput();
    });

    $(document).on('click', '.modal-quick-note', function () {
        $(this).toggleClass('active btn-dark btn-outline-secondary');
        syncQuickNotesToInput();
    });

    $(document).on('input', '#modalItemDetail', function () {
        $(this).data('manual-detail', $.trim($(this).val()));
    });

    $(document).on('input', '#modalItemNote', function () {
        $(this).data('manual-note', $.trim($(this).val()));
    });

    $(document).on('click', '#btnClearModalOption', function () {
        $('#modalItemDetail').val('').data('manual-detail', '');
        $('#modalItemNote').val('').data('manual-note', '');
        $('.modal-quick-detail, .modal-quick-note')
            .removeClass('active btn-dark')
            .addClass('btn-outline-secondary');
    });

    $(document).on('click', '#btnConfirmAddProduct', function () {
        if (!TABLE_IS_ACTIVE) {
            notify(TXT.tableDisabled);
            return;
        }

        if (!CURRENT_ORDER_ID) {
            notify(TXT.billNotFound);
            return;
        }

        const mode = $('#modalMode').val();
        const itemDetail = $.trim($('#modalItemDetail').val());
        const note = $.trim($('#modalItemNote').val());
        const options = collectSelectedQuickDetails();

        if (mode === 'edit') {
            const itemId = $('#editingItemId').val();

            if (!itemId) {
                notify(TXT.editItemNotFound);
                return;
            }

            $.post("<?= site_url('pos/update-item') ?>", {
                item_id: itemId,
                item_detail: itemDetail,
                note: note,
                options: JSON.stringify(options)
            })
            .done(function (res) {
                if (res.status === 'success') {
                    if (productOptionModal) {
                        productOptionModal.hide();
                    }
                    clearModalOption();
                    loadOrder();
                } else {
                    notify(res.message || TXT.editItemFailed);
                }
            })
            .fail(function (xhr) {
                console.error('updateItem error:', xhr.responseText);
                notify(TXT.editItemFailed);
            });

            return;
        }

        if (!SELECTED_PRODUCT_ID) {
            notify(TXT.selectedItemNotFound);
            return;
        }

        $.post("<?= site_url('pos/add-item') ?>", {
            order_id: CURRENT_ORDER_ID,
            product_id: SELECTED_PRODUCT_ID,
            item_detail: itemDetail,
            note: note,
            options: JSON.stringify(options)
        })
        .done(function (res) {
            if (res.status === 'success') {
                if (productOptionModal) {
                    productOptionModal.hide();
                }
                clearModalOption();
                loadOrder();
            } else {
                notify(res.message || TXT.addItemFailed);
            }
        })
        .fail(function (xhr) {
            console.error('addItem error:', xhr.responseText);
            notify(TXT.addItemFailed);
        });
    });

    $(document).on('click', '.btn-qty', function () {
        $.post("<?= site_url('pos/update-item-qty') ?>", {
            item_id: $(this).data('id'),
            type: $(this).data('type')
        })
        .done(function (res) {
            if (res && res.status === 'error') {
                notify(res.message || TXT.updateItemFailed);
                return;
            }
            loadOrder();
        })
        .fail(function (xhr) {
            console.error('updateItemQty error:', xhr.responseText);
            notify(TXT.updateItemFailed);
        });
    });

    $(document).on('click', '.btn-cancel-direct', function () {
        $.post("<?= site_url('pos/remove-item') ?>", {
            item_id: $(this).data('id')
        })
        .done(function (res) {
            if (res && res.status === 'error') {
                notify(res.message || TXT.removeItemFailed);
                return;
            }
            loadOrder();
        })
        .fail(function (xhr) {
            console.error('cancelDirect error:', xhr.responseText);
            notify(TXT.removeItemFailed);
        });
    });

    $(document).on('click', '.btn-request-cancel', function () {
        $.post("<?= site_url('pos/update-item-status') ?>", {
            item_id: $(this).data('id'),
            status: 'cancel_requested'
        })
        .done(function (res) {
            if (res && res.status === 'error') {
                notify(res.message || TXT.updateItemFailed);
                return;
            }
            if (res && (res.mode === 'cancel' || res.mode === 'cancelled' || res.mode === 'cancelled_direct')) {
                loadOrder();
                return;
            }
            notify(TXT.requestCancelSentToKitchen);
            loadOrder();
        })
        .fail(function (xhr) {
            console.error('requestCancel error:', xhr.responseText);
            notify(TXT.updateItemFailed);
        });
    });

    $(document).on('click', '.btn-retry-cancel-request', function () {
        $.post("<?= site_url('pos/update-item-status') ?>", {
            item_id: $(this).data('id'),
            status: 'cancel_requested'
        })
        .done(function (res) {
            if (res && res.status === 'error') {
                notify(res.message || TXT.updateItemFailed);
                return;
            }
            loadOrder();
        })
        .fail(function (xhr) {
            console.error('retryCancelRequest error:', xhr.responseText);
            notify(TXT.updateItemFailed);
        });
    });

    $(document).on('click', '.btn-ack-cancel-request', function () {
        const $wrap = $(this).closest('[data-cancel-request-status]');
        $wrap.attr('data-cancel-request-status', 'acknowledged');
        $wrap.find('.alert.alert-danger').remove();
        $(this).closest('.d-flex').remove();
        renderBillRequestAlerts($('#orderBox [data-item-status]').map(function () {
            return {
                cancel_request_status: $(this).attr('data-cancel-request-status') || ''
            };
        }).get());
    });

    let SEND_KITCHEN_BUSY = false;

    function generateRequestUuid() {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }
        return 'rk-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    $(document).off('click', '#btnSendKitchen');

    $(document).on('click', '#btnSendKitchen', function () {
        if (SEND_KITCHEN_BUSY) {
            return;
        }

        if (!TABLE_IS_ACTIVE) {
            notify(TXT.tableDisabled);
            return;
        }

        if (!CURRENT_ORDER_ID) {
            notify(TXT.noBillYet);
            return;
        }

        SEND_KITCHEN_BUSY = true;

        const $btn = $('#btnSendKitchen');
        const originalText = $btn.text();

        $btn.prop('disabled', true);
        $btn.text('...');

        const requestUuid = generateRequestUuid();

        $.ajax({
            url: "<?= site_url('pos/send-kitchen') ?>",
            type: "POST",
            dataType: "json",
            data: {
                order_id: CURRENT_ORDER_ID,
                request_uuid: requestUuid
            },
            complete: function () {
                SEND_KITCHEN_BUSY = false;
                $btn.prop('disabled', false);
                $btn.text(originalText);
            },
            success: function (res) {
                if (res && res.status === 'success') {
                    notify(res.message || <?= json_encode(lang('app.sent_to_kitchen_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
                    loadOrder();
                } else {
                    notify((res && res.message) ? res.message : TXT.sendKitchenFailed);
                }
            },
            error: function (xhr) {
                console.error('sendKitchen error:', xhr.responseText);
                notify(TXT.sendKitchenFailed);
            }
        });
    });

    $(document).on('click', '#btnPay', function () {
		if (!TABLE_IS_ACTIVE) {
			notify(TXT.tableDisabled);
			return;
		}

		if (!CURRENT_ORDER_ID) {
			notify(TXT.noBillYet);
			return;
		}

		if (CURRENT_ORDER_STATUS !== 'open' && CURRENT_ORDER_STATUS !== 'billing') {
			notify(TXT.billCannotPay);
			return;
		}

		const hasPending = $('#orderBox [data-item-status="pending"]').length > 0;
		if (hasPending) {
			notify('<?= esc(lang('app.pending_items_must_send_first')) ?>');
			return;
		}

		const total = getBillTotalNumber();

		if (total === 0) {
			if (!confirm(TXT.zeroBillConfirm)) {
				return;
			}

			$.post("<?= site_url('pos/pay') ?>", {
				order_id: CURRENT_ORDER_ID,
				payment_method: 'cash',
				amount: 0
			}).done(async function (res) {
				if (!res || res.status !== 'success') {
					if (res && res.code === 'MANAGER_OVERRIDE_REQUIRED') {
						const approved = await requestManagerOverride('pay', CURRENT_ORDER_ID);
						if (approved) {
							$('#btnPay').trigger('click');
						}
						return;
					}
					notify((res && res.message) ? res.message : TXT.paymentFailed);
					return;
				}

				notify(res.message || TXT.closeBillSuccess);
				CURRENT_ORDER_ID = null;
				CURRENT_ORDER_STATUS = null;
				loadOrder();
			});

			return;
		}

		openPaymentModal();
	});

    $(document).on('click', '.category-btn', function () {
        const categoryId = $(this).data('id');

        $('.category-btn').removeClass('active btn-dark').addClass('btn-outline-dark');
        $('.category-btn[data-id="' + categoryId + '"]').removeClass('btn-outline-dark').addClass('active btn-dark');

        applyProductFilters();
    });

    $(document).on('input', '#productSearch', function () {
        applyProductFilters();
    });

    $(document).on('click', '#btnClearProductSearch', function () {
        $('#productSearch').val('');
        applyProductFilters();
        $('#productSearch').trigger('focus');
    });

    $(document).on('click', '#btnOpenBillDrawer', function () {
        setBillDrawerOpen(true);
    });

    $(document).on('click', '#btnCloseBillDrawer, #mobileBillBackdrop', function () {
        setBillDrawerOpen(false);
    });

    $(window).on('resize', function () {
        if (window.innerWidth > 1199) {
            setBillDrawerOpen(false);
        }
    });

    $(document).on('change', '#paymentMethod', function () {
        const total = getBillTotalNumber();

        if ($(this).val() !== 'cash') {
            $('#paymentAmount').val(total.toFixed(2));
        }

        updatePaymentSummary();
    });

    $(document).on('input', '#paymentAmount', function () {
        updatePaymentSummary();
    });

    $(document).on('click', '.quick-pay', function () {
        const total = getBillTotalNumber();
        const type = $(this).data('type');

        if (type === 'exact') {
            $('#paymentAmount').val(total.toFixed(2));
        } else {
            $('#paymentAmount').val(parseFloat(type).toFixed(2));
        }

        updatePaymentSummary();
    });

    $(document).on('click', '.quick-round', function () {
        const total = getBillTotalNumber();
        const step = parseFloat($(this).data('round') || 1);
        const rounded = roundUp(total, step);

        $('#paymentAmount').val(rounded.toFixed(2));
        updatePaymentSummary();
    });

    $(document).on('click', '#btnConfirmPay', function () {
        if (!TABLE_IS_ACTIVE) {
            notify(TXT.tableDisabled);
            return;
        }

        if (!CURRENT_ORDER_ID) {
            notify(TXT.noBillYet);
            return;
        }

        const total = getBillTotalNumber();
        const paymentMethod = $('#paymentMethod').val();
        let amount = parseFloat($('#paymentAmount').val() || 0);

        if (paymentMethod !== 'cash') {
            amount = total;
        }

        if (isNaN(amount) || amount < 0) {
            notify(TXT.paymentAmountInvalid);
            return;
        }

        if (paymentMethod === 'cash' && amount < total) {
            notify(TXT.amountLessThanBill);
            return;
        }

        $.post("<?= site_url('pos/pay') ?>", {
            order_id: CURRENT_ORDER_ID,
            payment_method: paymentMethod,
            amount: amount
        })
        .done(async function (res) {
            if (res.status !== 'success') {
                if (res && res.code === 'MANAGER_OVERRIDE_REQUIRED') {
                    const approved = await requestManagerOverride('pay', CURRENT_ORDER_ID);
                    if (approved) {
                        $('#btnConfirmPay').trigger('click');
                    }
                    return;
                }
                notify(res.message || TXT.paymentFailed);
                return;
            }

            if (paymentModal) {
                paymentModal.hide();
            }

            notify((res.message || TXT.paymentSuccess) + '\n' + TXT.changeLabel + ': ' + money(res.change || 0));
            CURRENT_ORDER_ID = null;
            CURRENT_ORDER_STATUS = null;
            loadOrder();
        })
        .fail(function (xhr) {
            console.error('pay error:', xhr.responseText);
            notify(TXT.paymentFailed);
        });
    });


    if (managerOverrideModalEl) {
        managerOverrideModalEl.addEventListener('hidden.bs.modal', function () {
            if (typeof managerOverrideResolver === 'function') {
                const resolver = managerOverrideResolver;
                managerOverrideResolver = null;
                resolver(false);
            }
        });
    }

    $(document).on('click', '#btnConfirmManagerOverride', function () {
        const actionKey = $('#managerOverrideAction').val() || '';
        const orderId = parseInt($('#managerOverrideOrderId').val() || CURRENT_ORDER_ID || 0, 10);
        const managerPinCode = $.trim($('#managerOverridePinCode').val() || '');
        const $btn = $(this);

        if (!actionKey || !orderId || !managerPinCode) {
            notify(TXT.managerOverrideFailed);
            return;
        }

        $btn.prop('disabled', true);

        $.post("<?= site_url('pos/manager-override') ?>", {
            action_key: actionKey,
            order_id: orderId,
            manager_pin_code: managerPinCode
        })
        .done(function (res) {
            if (!res || res.status !== 'success') {
                notify((res && res.message) ? res.message : TXT.managerOverrideFailed);
                return;
            }

            if (managerOverrideModal) {
                managerOverrideModal.hide();
            }

            const resolver = managerOverrideResolver;
			managerOverrideResolver = null;
			if (typeof resolver === 'function') {
				resolver(true);
			}

			const approvedByText = res.approved_by ? (' ' + (TXT.by || 'by') + ' ' + res.approved_by) : '';
			notify((res.message || TXT.managerOverrideApproved || 'Manager override approved') + approvedByText);
        })
        .fail(function (xhr) {
            console.error('manager override error:', xhr.responseText);
            notify(TXT.managerOverrideFailed);
        })
        .always(function () {
            $btn.prop('disabled', false);
        });
    });

    $(document).on('click', '#btnMoveTable', function () {
        if (!CURRENT_ORDER_ID) {
            notify(TXT.noBillYet);
            return;
        }

        loadMoveTableOptions();

        if (moveTableModal) {
            moveTableModal.show();
        }
    });

    $(document).on('click', '#btnConfirmMoveTable', function () {
        if (!CURRENT_ORDER_ID) {
            notify(TXT.noBillYet);
            return;
        }

        const toTableId = Number($('#moveToTableId').val() || 0);
        const reason = $.trim($('#moveTableReason').val());

        if (!toTableId) {
            notify('<?= esc(lang('app.please_select')) ?>');
            return;
        }

        $.post("<?= site_url('pos/move-table') ?>", {
            order_id: CURRENT_ORDER_ID,
            to_table_id: toTableId,
            reason: reason
        })
        .done(function (res) {
            if (!res || res.status !== 'success') {
                notify((res && res.message) ? res.message : TXT.moveTableFailed);
                return;
            }

            if (moveTableModal) {
                moveTableModal.hide();
            }

            notify(res.message || TXT.moveTableSuccess);
            window.location.reload();
        })
        .fail(function (xhr) {
            console.error('moveTable error:', xhr.responseText);
            notify(TXT.moveTableFailed);
        });
    });

    $(document).on('click', '#btnMoveTable, #btnMergeBill, #btnConfirmMoveTable, #btnConfirmMergeBill, #btnViewMergeAudit, #btnViewMoveAudit', function (e) {
        e.preventDefault();
        e.stopPropagation();
    });

    $(document).on('click', '#btnViewMergeAudit', function () {
        if (mergeAuditModal) {
            mergeAuditModal.show();
        }
    });

    $(document).on('click', '#btnViewMoveAudit', function () {
        if (moveAuditModal) {
            moveAuditModal.show();
        }
    });

    $(document).on('click', '#btnMergeBill', function () {
        if (!CURRENT_ORDER_ID) {
            notify(TXT.noBillYet);
            return;
        }

        loadMergeTargets();

        if (mergeBillModal) {
            mergeBillModal.show();
        }
    });

    $(document).on('click', '#btnConfirmMergeBill', function () {
        if (!CURRENT_ORDER_ID) {
            notify(TXT.noBillYet);
            return;
        }

        const targetOrderId = Number($('#mergeTargetOrderId').val() || 0);
        const reason = $.trim($('#mergeBillReason').val());

        if (!targetOrderId) {
            notify(TXT.selectTargetBill);
            return;
        }

        $.post("<?= site_url('pos/merge-bill') ?>", {
            source_order_id: CURRENT_ORDER_ID,
            target_order_id: targetOrderId,
            reason: reason
        })
        .done(function (res) {
            if (!res || res.status !== 'success') {
                notify((res && res.message) ? res.message : TXT.mergeBillFailed);
                return;
            }

            if (mergeBillModal) {
                mergeBillModal.hide();
            }

            notify(res.message || TXT.mergeBillSuccess);
            window.location.reload();
        })
        .fail(function (xhr) {
            console.error('mergeBill error:', xhr.responseText);
            notify(TXT.mergeBillFailed);
        });
    });

    if (productOptionModalEl) {
        productOptionModalEl.addEventListener('hidden.bs.modal', function () {
            clearModalOption();
        });
    }

    if (moveTableModalEl) {
        moveTableModalEl.addEventListener('hidden.bs.modal', function () {
            $('#moveToTableId').val('');
            $('#moveTableReason').val('');
            $('#moveTableHint').text('');
        });
    }

    if (mergeBillModalEl) {
        mergeBillModalEl.addEventListener('hidden.bs.modal', function () {
            $('#mergeTargetOrderId').val('');
            $('#mergeBillReason').val('');
        });
    }

    updateOrderHeader();
    applyProductFilters();
    renderRecentProductToolbar([]);
    loadOrder();
    startAutoRefresh();
});
</script>
<?= $this->endSection() ?>