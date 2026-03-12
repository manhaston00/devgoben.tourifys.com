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
</style>
<?php
    $tableIsActive = (int) ($table['is_active'] ?? 0) === 1;
    $tableStatus   = strtolower((string) ($table['status'] ?? 'available'));
    $tableDisabled = (!$tableIsActive || $tableStatus === 'disabled');
?>
<div class="row g-3 mobile-bottom-space">
    <div class="col-lg-2 d-none d-lg-block">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h5 class="mb-3"><?= esc(lang('app.categories')) ?></h5>
                <div class="d-grid gap-2">
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

    <div class="col-12 d-lg-none">
        <div class="card card-soft">
            <div class="card-body py-2">
                <div class="small text-muted mb-2"><?= esc(lang('app.categories')) ?></div>
                <div class="d-flex gap-2 overflow-auto pb-1 mobile-category-scroll" id="mobileCategoryBar">
                    <button type="button" class="btn btn-dark flex-shrink-0 category-btn active" data-id="all"><?= esc(lang('app.all')) ?></button>
                    <?php foreach ($categories as $cat): ?>
                        <?php $categoryName = lfield($cat, 'category_name', '-'); ?>
                        <button
                            type="button"
                            class="btn btn-outline-secondary flex-shrink-0 category-btn"
                            data-id="<?= (int) $cat['id'] ?>"
                        >
                            <?= esc($categoryName) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h4 class="mb-1"><?= esc(lang('app.table')) ?> <?= esc($table['table_name'] ?? '-') ?></h4>
                        <div class="small text-muted mb-2">
                            <?= $tableDisabled
                                ? esc(lang('app.table_disabled_cannot_order'))
                                : esc(lang('app.select_menu_to_add_bill')) ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
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
                            <span
                                class="badge <?= $tableDisabled ? 'text-bg-dark' : 'text-bg-info' ?>"
                                id="tableStatusBadge"
                            >
                                <?= esc(lang('app.table_status')) ?>:
                                <?= esc($tableStatusText) ?>
                            </span>

                            <span class="badge text-bg-secondary" id="orderStatusBadge">
                                <?= esc(lang('app.bill_status')) ?>: <?= esc(lang('app.no_open_bill')) ?>
                            </span>

                            <?php if ($tableDisabled): ?>
                                <span class="badge text-bg-danger"><?= esc(lang('app.this_table_disabled')) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button
                            type="button"
                            class="btn btn-primary"
                            id="btnOpenOrder"
                            <?= $tableDisabled ? 'disabled' : '' ?>
                        >
                            <?= $tableDisabled ? esc(lang('app.table_disabled')) : esc(lang('app.open_bill')) ?>
                        </button>
                    </div>
                </div>

                <?php if ($tableDisabled): ?>
                    <div class="alert alert-warning border-0 rounded-4 mb-3">
                        <?= esc(lang('app.table_disabled_full_notice')) ?>
                    </div>
                <?php endif; ?>

                <div class="row g-3" id="productGrid">
                    <?php foreach ($products as $product): ?>
                        <?php $productName = lfield($product, 'product_name', '-'); ?>
                        <div class="col-6 col-md-4 product-card" data-category="<?= (int) ($product['category_id'] ?? 0) ?>">
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

    <div class="col-lg-4">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><?= esc(lang('app.current_bill')) ?></h5>
                    <span class="small text-muted" id="orderNoLabel">-</span>
					<div id="mergedNoticeBox" class="mt-2"></div>
                </div>

                <div id="orderBox">
                    <div class="text-muted"><?= esc(lang('app.no_bill_yet')) ?></div>
                </div>

                <hr>

                <div class="d-flex justify-content-between mb-2">
                    <span><?= esc(lang('app.grand_total')) ?></span>
                    <strong id="billTotal">฿0.00</strong>
                </div>

                <div class="d-grid gap-2">
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

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
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
        closeBillSuccess: <?= json_encode(lang('app.close_bill_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        changeLabel: <?= json_encode(lang('app.change'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        allProductsDisabled: <?= json_encode(lang('app.table_disabled'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        moveTableFailed: <?= json_encode(lang('app.move_table_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        moveTableSuccess: <?= json_encode(lang('app.move_table_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        destinationBusy: <?= json_encode(lang('app.destination_table_has_open_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergeBill: <?= json_encode(lang('app.merge_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergeBillFailed: <?= json_encode(lang('app.merge_bill_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergeBillSuccess: <?= json_encode(lang('app.merge_bill_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        selectTargetBill: <?= json_encode(lang('app.select_target_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };

    const productOptionModalEl = document.getElementById('productOptionModal');
    const productOptionModal = productOptionModalEl ? new bootstrap.Modal(productOptionModalEl) : null;

    const paymentModalEl = document.getElementById('paymentModal');
    const paymentModal = paymentModalEl ? new bootstrap.Modal(paymentModalEl) : null;

    function money(num) {
        return '฿' + parseFloat(num || 0).toFixed(2);
    }

    function escapeHtml(text) {
        return $('<div>').text(text ?? '').html();
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

    function itemStatusBadge(status) {
        if (status === 'pending') return 'secondary';
        if (status === 'sent') return 'primary';
        if (status === 'cooking') return 'warning';
        if (status === 'served') return 'success';
        if (status === 'cancel') return 'danger';
        return 'secondary';
    }

    function itemStatusText(status) {
        if (status === 'pending') return TXT.pending;
        if (status === 'sent') return TXT.sentKitchen;
        if (status === 'cooking') return TXT.cooking;
        if (status === 'served') return TXT.served;
        if (status === 'cancel') return TXT.canceled;
        return TXT.unknownStatus;
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
            alert(TXT.tableDisabled);
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
	
	function renderMergedNotice(notice = null) {
		if (!notice || !notice.target_table_name) {
			$('#mergedNoticeBox').html('');
			return;
		}

		const targetTableName = escapeHtml(notice.target_table_name || '-');
		const targetOrderNumber = escapeHtml(notice.target_order_number || '-');
		const reason = escapeHtml(notice.reason || '');

		let html = `
			<div class="alert alert-warning border-0 rounded-4 py-2 px-3 mb-0">
				<div class="fw-bold mb-1">บิลนี้ถูกรวมไปแล้ว</div>
				<div class="small">ไปที่โต๊ะ: <strong>${targetTableName}</strong></div>
				<div class="small">บิลปลายทาง: <strong>${targetOrderNumber}</strong></div>
				${reason ? `<div class="small text-muted mt-1">เหตุผล: ${reason}</div>` : ''}
			</div>
		`;

		$('#mergedNoticeBox').html(html);
	}

    function updateOrderHeader(order = null) {
		const $badge = $('#orderStatusBadge');
		const mergedNotice = order && order.merged_notice ? order.merged_notice : null;

		$badge.removeClass('text-bg-primary text-bg-warning text-bg-success text-bg-danger text-bg-secondary text-bg-dark');
		$badge.addClass(statusBadgeClass(CURRENT_ORDER_STATUS));
		$badge.text(TXT.billStatus + ': ' + statusText(CURRENT_ORDER_STATUS));

		renderMergedNotice(mergedNotice);

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

    function renderItems(order, items) {
        let html = '';

        if (!items || items.length === 0) {
            html = '<div class="text-muted">' + TXT.noItemsYet + '</div>';
        } else {
            items.forEach(function (item) {
                const editable = canEditItem(item.status);

                html += `
                    <div class="border rounded-4 p-2 mb-2" data-item-status="${escapeHtml(item.status ?? 'pending')}">
                        <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                            <div class="fw-bold">${escapeHtml(item.product_name ?? '')}</div>
                            <span class="badge bg-${itemStatusBadge(item.status)}">${escapeHtml(itemStatusText(item.status ?? 'pending'))}</span>
                        </div>

                        ${item.item_detail ? `<div class="small text-dark">${TXT.detailLabel}: ${escapeHtml(item.item_detail)}</div>` : ''}
                        ${item.option_summary ? `<div class="small text-primary">${TXT.optionLabel}: ${escapeHtml(item.option_summary)}</div>` : ''}
                        ${item.note ? `<div class="small text-muted mb-2">${TXT.noteLabel}: ${escapeHtml(item.note)}</div>` : ''}

                        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                            <div>${money(item.price)} x ${item.qty}</div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary btn-qty" data-id="${item.id}" data-type="minus" ${editable ? '' : 'disabled'}>-</button>
                                <button type="button" class="btn btn-outline-secondary btn-qty" data-id="${item.id}" data-type="plus" ${editable ? '' : 'disabled'}>+</button>
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
                                    ${editable ? '' : 'disabled'}
                                >${TXT.edit}</button>
                                <button type="button" class="btn btn-outline-danger btn-remove" data-id="${item.id}" ${editable ? '' : 'disabled'}>${TXT.remove}</button>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        $('#orderBox').html(html);
        $('#billTotal').text(money(order.total_price || 0));
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
						merged_notice: res.merged_notice || null
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
					merged_notice: res.merged_notice || null
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
            alert(TXT.tableDisabled);
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
                alert(res.message || TXT.loadBillFailed);
            }
        })
        .fail(function (xhr) {
            console.error('openOrder error:', xhr.responseText);
            alert(TXT.loadBillFailed);
        });
    });

    $(document).on('click', '.product-btn', function () {
        if (!CURRENT_ORDER_ID) {
            alert(TXT.openBillFirst);
            return;
        }

        if (!(CURRENT_ORDER_STATUS === 'open' || CURRENT_ORDER_STATUS === 'billing')) {
            alert(TXT.billCannotAddItems);
            return;
        }

        const productId = $(this).data('id');
        const productName = $(this).data('name') || $(this).find('.fw-bold').text().trim();
        const productPriceText = $(this).find('.text-muted').text().replace('฿', '').replace(/,/g, '').trim();
        const productPrice = parseFloat(productPriceText || 0);

        openProductModal(productId, productName, productPrice);
        loadProductQuickOptions(productId);
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
            alert(TXT.itemCannotEdit);
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
            alert(TXT.tableDisabled);
            return;
        }

        if (!CURRENT_ORDER_ID) {
            alert(TXT.billNotFound);
            return;
        }

        const mode = $('#modalMode').val();
        const itemDetail = $.trim($('#modalItemDetail').val());
        const note = $.trim($('#modalItemNote').val());
        const options = collectSelectedQuickDetails();

        if (mode === 'edit') {
            const itemId = $('#editingItemId').val();

            if (!itemId) {
                alert(TXT.editItemNotFound);
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
                    alert(res.message || TXT.editItemFailed);
                }
            })
            .fail(function (xhr) {
                console.error('updateItem error:', xhr.responseText);
                alert(TXT.editItemFailed);
            });

            return;
        }

        if (!SELECTED_PRODUCT_ID) {
            alert(TXT.selectedItemNotFound);
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
                alert(res.message || TXT.addItemFailed);
            }
        })
        .fail(function (xhr) {
            console.error('addItem error:', xhr.responseText);
            alert(TXT.addItemFailed);
        });
    });

    $(document).on('click', '.btn-qty', function () {
        $.post("<?= site_url('pos/update-item-qty') ?>", {
            item_id: $(this).data('id'),
            type: $(this).data('type')
        })
        .done(function (res) {
            if (res && res.status === 'error') {
                alert(res.message || TXT.updateItemFailed);
                return;
            }
            loadOrder();
        })
        .fail(function (xhr) {
            console.error('updateItemQty error:', xhr.responseText);
            alert(TXT.updateItemFailed);
        });
    });

    $(document).on('click', '.btn-remove', function () {
        $.post("<?= site_url('pos/remove-item') ?>", {
            item_id: $(this).data('id')
        })
        .done(function (res) {
            if (res && res.status === 'error') {
                alert(res.message || TXT.removeItemFailed);
                return;
            }
            loadOrder();
        })
        .fail(function (xhr) {
            console.error('removeItem error:', xhr.responseText);
            alert(TXT.removeItemFailed);
        });
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
            alert(TXT.tableDisabled);
            return;
        }

        if (!CURRENT_ORDER_ID) {
            alert(TXT.noBillYet);
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
                    alert(res.message || <?= json_encode(lang('app.sent_to_kitchen_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
                    loadOrder();
                } else {
                    alert((res && res.message) ? res.message : TXT.sendKitchenFailed);
                }
            },
            error: function (xhr) {
                console.error('sendKitchen error:', xhr.responseText);
                alert(TXT.sendKitchenFailed);
            }
        });
    });

    $(document).on('click', '#btnPay', function () {
		if (!TABLE_IS_ACTIVE) {
			alert(TXT.tableDisabled);
			return;
		}

		if (!CURRENT_ORDER_ID) {
			alert(TXT.noBillYet);
			return;
		}

		if (CURRENT_ORDER_STATUS !== 'open' && CURRENT_ORDER_STATUS !== 'billing') {
			alert(TXT.billCannotPay);
			return;
		}

		const hasPending = $('#orderBox [data-item-status="pending"]').length > 0;
		if (hasPending) {
			alert('<?= esc(lang('app.pending_items_must_send_first')) ?>');
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
			}).done(function (res) {
				if (!res || res.status !== 'success') {
					alert((res && res.message) ? res.message : TXT.paymentFailed);
					return;
				}

				alert(res.message || TXT.closeBillSuccess);
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

        if (categoryId === 'all') {
            $('.product-card').show();
        } else {
            $('.product-card').hide();
            $('.product-card[data-category="' + categoryId + '"]').show();
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
            alert(TXT.tableDisabled);
            return;
        }

        if (!CURRENT_ORDER_ID) {
            alert(TXT.noBillYet);
            return;
        }

        const total = getBillTotalNumber();
        const paymentMethod = $('#paymentMethod').val();
        let amount = parseFloat($('#paymentAmount').val() || 0);

        if (paymentMethod !== 'cash') {
            amount = total;
        }

        if (isNaN(amount) || amount < 0) {
            alert(TXT.paymentAmountInvalid);
            return;
        }

        if (paymentMethod === 'cash' && amount < total) {
            alert(TXT.amountLessThanBill);
            return;
        }

        $.post("<?= site_url('pos/pay') ?>", {
            order_id: CURRENT_ORDER_ID,
            payment_method: paymentMethod,
            amount: amount
        })
        .done(function (res) {
            if (res.status !== 'success') {
                alert(res.message || TXT.paymentFailed);
                return;
            }

            if (paymentModal) {
                paymentModal.hide();
            }

            alert((res.message || TXT.paymentSuccess) + '\n' + TXT.changeLabel + ': ' + money(res.change || 0));
            CURRENT_ORDER_ID = null;
            CURRENT_ORDER_STATUS = null;
            loadOrder();
        })
        .fail(function (xhr) {
            console.error('pay error:', xhr.responseText);
            alert(TXT.paymentFailed);
        });
    });

    $(document).on('click', '#btnMoveTable', function () {
        if (!CURRENT_ORDER_ID) {
            alert(TXT.noBillYet);
            return;
        }

        loadMoveTableOptions();

        if (moveTableModal) {
            moveTableModal.show();
        }
    });

    $(document).on('click', '#btnConfirmMoveTable', function () {
        if (!CURRENT_ORDER_ID) {
            alert(TXT.noBillYet);
            return;
        }

        const toTableId = Number($('#moveToTableId').val() || 0);
        const reason = $.trim($('#moveTableReason').val());

        if (!toTableId) {
            alert('<?= esc(lang('app.please_select')) ?>');
            return;
        }

        $.post("<?= site_url('pos/move-table') ?>", {
            order_id: CURRENT_ORDER_ID,
            to_table_id: toTableId,
            reason: reason
        })
        .done(function (res) {
            if (!res || res.status !== 'success') {
                alert((res && res.message) ? res.message : TXT.moveTableFailed);
                return;
            }

            if (moveTableModal) {
                moveTableModal.hide();
            }

            alert(res.message || TXT.moveTableSuccess);
            window.location.reload();
        })
        .fail(function (xhr) {
            console.error('moveTable error:', xhr.responseText);
            alert(TXT.moveTableFailed);
        });
    });

    $(document).on('click', '#btnMergeBill', function () {
        if (!CURRENT_ORDER_ID) {
            alert(TXT.noBillYet);
            return;
        }

        loadMergeTargets();

        if (mergeBillModal) {
            mergeBillModal.show();
        }
    });

    $(document).on('click', '#btnConfirmMergeBill', function () {
        if (!CURRENT_ORDER_ID) {
            alert(TXT.noBillYet);
            return;
        }

        const targetOrderId = Number($('#mergeTargetOrderId').val() || 0);
        const reason = $.trim($('#mergeBillReason').val());

        if (!targetOrderId) {
            alert(TXT.selectTargetBill);
            return;
        }

        $.post("<?= site_url('pos/merge-bill') ?>", {
            source_order_id: CURRENT_ORDER_ID,
            target_order_id: targetOrderId,
            reason: reason
        })
        .done(function (res) {
            if (!res || res.status !== 'success') {
                alert((res && res.message) ? res.message : TXT.mergeBillFailed);
                return;
            }

            if (mergeBillModal) {
                mergeBillModal.hide();
            }

            alert(res.message || TXT.mergeBillSuccess);
            window.location.reload();
        })
        .fail(function (xhr) {
            console.error('mergeBill error:', xhr.responseText);
            alert(TXT.mergeBillFailed);
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
    loadOrder();
    startAutoRefresh();
});
</script>
<?= $this->endSection() ?>