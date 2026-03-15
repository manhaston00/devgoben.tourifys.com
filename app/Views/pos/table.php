<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<style>
/* UX PATCH v1 preserve layout */
.bill-card:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(0,0,0,.08);} 
.bill-card.active{border:2px solid #4F7BFF;background:#F5F8FF;} 
.menu-item:hover{background:#f8fafc;} 
.pos-bill-panel{scroll-behavior:smooth;} 
@media (max-width:1024px){button{min-height:44px;}}

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
#btnMergeBill,
#btnSplitBill {
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

.pos-split-group-panel {
    display: grid;
    gap: .75rem;
    margin-bottom: .9rem;
}

.pos-split-group-panel.is-hidden {
    display: none;
}

.pos-split-group-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    flex-wrap: wrap;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 18px;
    padding: .85rem .95rem;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.pos-split-group-title {
    font-size: .98rem;
    font-weight: 800;
    color: #0f172a;
}

.pos-split-group-note {
    font-size: .82rem;
    color: #64748b;
    margin-top: .2rem;
}

.pos-split-group-stats {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem;
}

.pos-split-group-stat {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    min-height: 34px;
    padding: .32rem .7rem;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.22);
    background: #fff;
    color: #334155;
    font-size: .78rem;
    font-weight: 700;
}

.pos-split-group-list {
    display: grid;
    gap: .65rem;
}

.pos-split-group-card {
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 18px;
    padding: .8rem .9rem;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
}

.pos-split-group-card.is-selected {
    border-color: rgba(37, 99, 235, 0.36);
    background: rgba(59, 130, 246, 0.06);
    box-shadow: 0 14px 28px rgba(37, 99, 235, 0.10);
}

.pos-split-group-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    margin-bottom: .55rem;
}

.pos-split-group-card-title {
    font-weight: 800;
    color: #0f172a;
    word-break: break-word;
}

.pos-split-group-card-role {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    margin-top: .35rem;
    padding: .22rem .6rem;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: .74rem;
    font-weight: 700;
}

.pos-split-group-card-meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .55rem;
    margin-bottom: .65rem;
}

.pos-split-group-card-meta-item {
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 14px;
    padding: .55rem .65rem;
    background: #f8fafc;
}

.pos-split-group-card-meta-label {
    font-size: .72rem;
    font-weight: 700;
    color: #64748b;
    margin-bottom: .18rem;
}

.pos-split-group-card-meta-value {
    font-size: .9rem;
    font-weight: 800;
    color: #0f172a;
}

.pos-split-group-card-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .55rem;
}

.pos-split-group-card-actions .btn {
    min-height: 42px;
    border-radius: 14px;
    font-weight: 700;
}

.pos-split-group-card-toggle {
    width: 100%;
    border: 0;
    background: transparent;
    padding: 0;
    text-align: left;
    cursor: pointer;
}

.pos-split-group-card-toggle:focus-visible {
    outline: 2px solid rgba(37, 99, 235, 0.35);
    outline-offset: 4px;
    border-radius: 16px;
}

.pos-split-group-card-caret {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.24);
    background: #fff;
    color: #475569;
    font-size: .95rem;
    transition: transform .18s ease;
}

.pos-split-group-card.is-expanded .pos-split-group-card-caret {
    transform: rotate(180deg);
}

.pos-split-group-card-preview {
    display: none;
    border-top: 1px dashed rgba(148, 163, 184, 0.24);
    margin-top: .72rem;
    padding-top: .72rem;
}

.pos-split-group-card.is-expanded .pos-split-group-card-preview {
    display: block;
}

.pos-split-group-preview-list {
    display: grid;
    gap: .45rem;
}

.pos-split-group-preview-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 14px;
    padding: .6rem .7rem;
    background: #f8fafc;
}

.pos-split-group-preview-name {
    font-size: .88rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.35;
}

.pos-split-group-preview-note {
    font-size: .76rem;
    color: #64748b;
    margin-top: .18rem;
}

.pos-split-group-preview-qty {
    flex: 0 0 auto;
    font-size: .78rem;
    font-weight: 700;
    color: #1d4ed8;
    background: #eff6ff;
    border-radius: 999px;
    padding: .2rem .55rem;
    min-width: 52px;
    text-align: center;
}

.pos-split-group-empty-preview {
    border: 1px dashed rgba(148, 163, 184, 0.24);
    border-radius: 14px;
    padding: .75rem;
    text-align: center;
    color: #64748b;
    font-size: .82rem;
    background: #f8fafc;
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

.recent-items-modal-list {
    display: grid;
    gap: .75rem;
}

.recent-items-modal-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    width: 100%;
    text-align: left;
    padding: 1rem;
    border: 1px solid rgba(148, 163, 184, 0.25);
    border-radius: 18px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.recent-items-modal-card:hover,
.recent-items-modal-card:focus {
    border-color: rgba(59, 130, 246, 0.45);
    box-shadow: 0 14px 24px rgba(59, 130, 246, 0.10);
}

.recent-items-modal-card-title {
    font-weight: 700;
    color: #0f172a;
}

.recent-items-modal-card-meta {
    font-size: .85rem;
    color: #64748b;
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
        z-index: 1035;
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
        z-index: 1030;
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
        z-index: 1025;
        background: rgba(15, 23, 42, 0.35);
        opacity: 0;
        visibility: hidden;
        transition: opacity .2s ease, visibility .2s ease;
        display: block;
    }

    .modal-backdrop {
        z-index: 1080 !important;
    }

    .modal {
        z-index: 1085 !important;
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
    .pos-split-group-card-meta,
    .pos-split-group-card-actions {
        grid-template-columns: 1fr;
    }

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
        grid-template-columns: repeat(2, minmax(0, 1fr));
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
    #btnMergeBill,
    #btnSplitBill {
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

.pos-bill-header-actions {
    display: flex;
    align-items: center;
    gap: .5rem;
}

#btnBillTimelineQuick {
    min-height: 42px;
    border-radius: 14px;
    padding: .55rem .85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: .35rem;
}

.pos-bill-pane-close {
    min-height: 42px;
    min-width: 42px;
    border-radius: 14px;
}

.pos-bill-timeline-dialog.modal-dialog {
    max-width: 720px;
}

.pos-timeline-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .6rem;
    margin-bottom: .85rem;
}

.pos-timeline-summary-card {
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 16px;
    background: #f8fafc;
    padding: .7rem .8rem;
}

.pos-timeline-summary-label {
    font-size: .74rem;
    font-weight: 700;
    color: #64748b;
    margin-bottom: .2rem;
}

.pos-timeline-summary-value {
    font-size: .96rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.25;
}

.pos-timeline-list {
    display: grid;
    gap: .75rem;
}

.pos-timeline-card {
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 18px;
    background: #fff;
    padding: .9rem 1rem;
}

.pos-timeline-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    margin-bottom: .45rem;
}

.pos-timeline-card-title-wrap {
    min-width: 0;
}

.pos-timeline-card-title {
    font-size: .98rem;
    font-weight: 700;
    line-height: 1.25;
    color: #0f172a;
}

.pos-timeline-card-time {
    font-size: .82rem;
    color: #64748b;
    margin-top: .18rem;
}

.pos-timeline-card-badges {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: .35rem;
}

.pos-timeline-badge {
    display: inline-flex;
    align-items: center;
    padding: .22rem .55rem;
    border-radius: 999px;
    font-size: .74rem;
    font-weight: 600;
    border: 1px solid rgba(148, 163, 184, 0.24);
    color: #475569;
    background: #f8fafc;
}

.pos-timeline-badge.actor {
    background: #f1f5f9;
}

.pos-timeline-badge.type-kitchen {
    background: #fff7ed;
    color: #c2410c;
    border-color: rgba(251, 146, 60, .28);
}

.pos-timeline-badge.type-billing {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: rgba(59, 130, 246, .25);
}

.pos-timeline-badge.type-payment {
    background: #ecfdf5;
    color: #047857;
    border-color: rgba(16, 185, 129, .28);
}

.pos-timeline-badge.type-table {
    background: #faf5ff;
    color: #7c3aed;
    border-color: rgba(168, 85, 247, .24);
}

.pos-timeline-badge.type-warning {
    background: #fef2f2;
    color: #b91c1c;
    border-color: rgba(248, 113, 113, .28);
}

.pos-timeline-badge.type-general {
    background: #f8fafc;
    color: #475569;
}

.pos-timeline-card-body {
    display: grid;
    gap: .45rem;
}

.pos-timeline-row {
    display: grid;
    grid-template-columns: 120px minmax(0, 1fr);
    gap: .5rem;
    align-items: start;
    font-size: .9rem;
}

.pos-timeline-row-label {
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .02em;
    font-size: .72rem;
}

.pos-timeline-row-value {
    color: #0f172a;
    font-weight: 500;
    word-break: break-word;
}

.pos-timeline-empty {
    border: 1px dashed rgba(148,163,184,.35);
    border-radius: 18px;
    padding: 1rem;
    text-align: center;
    color: #64748b;
    background: #f8fafc;
}

@media (max-width: 767.98px) {
    .pos-bill-header-actions {
        width: auto;
        margin-left: auto;
    }

    #btnBillTimelineQuick {
        min-height: 38px;
        padding: .45rem .7rem;
        font-size: .85rem;
    }

    .pos-bill-timeline-dialog.modal-dialog {
        margin: .65rem;
    }

    .pos-timeline-card {
        padding: .8rem .85rem;
        border-radius: 16px;
    }

    .pos-timeline-card-head {
        flex-direction: column;
        gap: .45rem;
    }

    .pos-timeline-card-badges {
        justify-content: flex-start;
    }

    .pos-timeline-summary {
        grid-template-columns: 1fr;
        gap: .5rem;
        margin-bottom: .75rem;
    }

    .pos-timeline-summary-card {
        padding: .65rem .7rem;
        border-radius: 14px;
    }

    .pos-timeline-row {
        grid-template-columns: 86px minmax(0, 1fr);
        gap: .4rem;
        font-size: .84rem;
    }
}


.split-bill-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}
.split-bill-workspace {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(320px, .85fr);
    gap: 1rem;
    align-items: start;
}
.split-bill-pane {
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 22px;
    background: #fff;
    padding: .85rem;
    min-height: 100%;
}
.split-bill-pane.is-target {
    background: linear-gradient(180deg, #f7fcff 0%, #ffffff 100%);
}
.split-bill-pane-head {
    display: flex;
    justify-content: space-between;
    gap: .75rem;
    align-items: flex-start;
    margin-bottom: .9rem;
    flex-wrap: wrap;
}
.split-bill-pane-title {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}
.split-bill-pane-hint {
    font-size: .84rem;
    color: #64748b;
}
.split-bill-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .65rem;
    margin-bottom: .9rem;
}

.split-bill-quick-guide {
    display: flex;
    gap: .75rem;
    align-items: flex-start;
    flex-wrap: wrap;
    border: 1px solid rgba(148, 163, 184, 0.18);
    background: linear-gradient(180deg, #f8fdff 0%, #f8fafc 100%);
    border-radius: 18px;
    padding: .85rem 1rem;
    margin-bottom: .95rem;
}
.split-bill-quick-guide-icon {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 202, 240, 0.12);
    color: #0891b2;
    font-weight: 800;
    flex: 0 0 auto;
}
.split-bill-quick-guide-title {
    font-weight: 700;
    color: #0f172a;
    margin-bottom: .15rem;
}
.split-bill-quick-guide-text {
    font-size: .85rem;
    color: #475569;
    line-height: 1.45;
}
.split-bill-mobile-totalbar {
    display: none;
}
.split-bill-summary-card {
    border: 1px solid rgba(148, 163, 184, 0.16);
    border-radius: 18px;
    padding: .8rem .85rem;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
}
.split-bill-summary-card.is-accent {
    background: rgba(13, 202, 240, 0.08);
    border-color: rgba(13, 202, 240, 0.25);
}
.split-bill-summary-label {
    font-size: .77rem;
    color: #64748b;
    margin-bottom: .2rem;
}
.split-bill-summary-value {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}
.split-bill-section-badge {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .32rem .7rem;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    font-size: .76rem;
    font-weight: 700;
}
.split-bill-items,
.split-bill-target-list {
    display: grid;
    gap: .75rem;
}
.split-bill-card {
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 18px;
    background: #fff;
    padding: .72rem .8rem;
    transition: .18s ease;
}
.split-bill-card.is-active {
    border-color: rgba(13, 202, 240, 0.42);
    background: rgba(13, 202, 240, 0.08);
    box-shadow: 0 12px 30px rgba(13, 202, 240, 0.10);
}
.split-bill-card.is-target {
    border-color: rgba(13, 202, 240, 0.22);
}
.split-bill-card-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: .75rem;
    margin-bottom: .45rem;
}
.split-bill-card-name {
    font-weight: 700;
    color: #0f172a;
}
.split-bill-card-option {
    font-size: .82rem;
    color: #0ea5e9;
}
.split-bill-card-note {
    font-size: .82rem;
    color: #64748b;
}
.split-bill-card-meta {
    display: flex;
    gap: .45rem;
    flex-wrap: wrap;
    margin-top: .55rem;
}
.split-bill-meta-chip {
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid rgba(148, 163, 184, 0.2);
    padding: .25rem .6rem;
    font-size: .76rem;
    color: #334155;
}
.split-bill-source-card {
    cursor: default;
}
.split-bill-source-card:hover {
    border-color: rgba(37, 99, 235, 0.25);
    box-shadow: 0 10px 20px rgba(37, 99, 235, 0.08);
}
.split-bill-source-card-main {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: .6rem;
    align-items: flex-start;
    margin-bottom: .3rem;
}
.split-bill-main-tap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 94px;
    min-height: 36px;
    padding: .52rem .78rem;
    border-radius: 14px;
    border: 1px solid rgba(14, 165, 233, 0.34);
    background: #f7fcff;
    color: #0f766e;
    font-weight: 700;
    font-size: .82rem;
    line-height: 1;
}
.split-bill-main-tap.is-disabled,
.split-bill-main-tap:disabled {
    opacity: 1;
    border-color: rgba(148, 163, 184, 0.24);
    background: #f8fafc;
    color: #94a3b8;
}
.split-bill-main-tap small {
    display: none;
}
.split-bill-state-note {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .2rem .55rem;
    margin-top: .45rem;
    border-radius: 999px;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    color: #c2410c;
    font-size: .72rem;
    font-weight: 700;
    line-height: 1.2;
}
.split-bill-action-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: .55rem;
    align-items: center;
    margin-top: .7rem;
}
.split-bill-quick-actions {
    display: inline-flex;
    gap: .4rem;
    flex-wrap: wrap;
}
.split-bill-quick-actions .btn,
.split-bill-card-actions .btn,
.split-bill-qty-stepper .btn {
    border-radius: 12px;
}
.split-bill-quick-actions .btn,
.split-bill-card-actions .btn {
    min-height: 34px;
    padding: .42rem .72rem;
    font-size: .78rem;
    font-weight: 700;
}
.split-bill-qty-stepper {
    display: inline-flex;
    align-items: center;
    gap: .28rem;
    flex-shrink: 0;
}
.split-bill-qty-stepper .btn {
    width: 32px;
    height: 32px;
    padding: 0;
    font-weight: 700;
}
.split-bill-qty-stepper .split-bill-qty-display {
    min-width: 38px;
    text-align: center;
    border-radius: 12px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    background: #fff;
    padding: .34rem .45rem;
    font-weight: 700;
    font-size: .82rem;
    color: #0f172a;
}
.split-bill-empty-state {
    border: 1px dashed rgba(148, 163, 184, 0.45);
    border-radius: 18px;
    padding: 1rem;
    text-align: center;
    color: #64748b;
    background: rgba(248, 250, 252, 0.8);
}
.split-bill-preview-box {
    margin-top: 1rem;
    border-top: 1px dashed rgba(148, 163, 184, 0.28);
    padding-top: .9rem;
}
.split-bill-preview-box .split-bill-summary-grid {
    margin-bottom: 0;
}
.split-bill-live-badge {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    border-radius: 999px;
    background: rgba(13, 202, 240, 0.12);
    color: #0f766e;
    padding: .28rem .6rem;
    font-size: .76rem;
    font-weight: 700;
}
.split-bill-target-summary {
    border: 1px dashed rgba(14, 165, 233, 0.2);
    border-radius: 16px;
    background: #fcfeff;
    padding: .7rem;
    margin-bottom: .75rem;
}
.split-bill-target-summary-title {
    font-size: .82rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: .35rem;
}
.split-bill-target-summary-meta {
    display: flex;
    gap: .45rem;
    flex-wrap: wrap;
}
.split-bill-card-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: .85rem;
}
.split-bill-target-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
    margin-bottom: .75rem;
    padding-bottom: .75rem;
    border-bottom: 1px dashed rgba(148, 163, 184, 0.25);
}
.split-bill-target-hero strong {
    font-size: 1.15rem;
    color: #0f172a;
}
.split-bill-footer-layout {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    width: 100%;
    align-items: center;
    flex-wrap: wrap;
}
.split-bill-footer-summary {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}
.split-bill-footer-chip {
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid rgba(148, 163, 184, 0.18);
    padding: .4rem .7rem;
    font-size: .8rem;
    color: #334155;
}
.split-bill-footer-actions {
    display: flex;
    align-items: center;
    gap: .6rem;
    flex-wrap: wrap;
}
.split-bill-legacy-preview-toggle {
    white-space: nowrap;
}
@media (max-width: 1199.98px) {
    .split-bill-summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 991.98px) {
    .split-bill-workspace {
        grid-template-columns: 1fr;
    }
    .split-bill-pane {
        min-height: auto;
    }
    .split-bill-pane.is-target {
    }
}
@media (max-width: 767.98px) {

.split-bill-summary-grid {
    gap: 10px;
    margin-bottom: 12px;
}
.split-bill-summary-card {
    padding: 9px 12px;
    border-radius: 15px;
    background: #fbfcfd;
    border: 1px solid #e6ecf2;
    min-height: 64px;
}
.split-bill-summary-card.is-accent,
.split-bill-pane.is-target,
.split-bill-mobile-totalbar .split-bill-summary-card.is-accent {
    background: #f4fbff;
    border-color: #cfe8f6;
}
.split-bill-summary-label {
    font-size: 12px;
    color: #7b8794;
}
.split-bill-summary-value {
    font-size: 15px;
    color: #1f2937;
}
.split-bill-quick-guide {
    margin-bottom: 12px;
    padding: 10px 12px;
    border-radius: 16px;
    background: #f8fbfd;
    border: 1px solid #e5edf3;
}
.split-bill-quick-guide-icon {
    width: 34px;
    height: 34px;
    font-size: 14px;
    background: #eaf7fd;
    color: #1991c1;
}
.split-bill-workspace {
    gap: 12px;
}
.split-bill-pane {
    padding: 10px;
    border-radius: 16px;
    background: #fcfdff;
    border: 1px solid #e6ecf2;
}
.split-bill-pane-head {
    margin-bottom: 10px;
}
.split-bill-pane-title {
    font-size: 18px;
    margin-top: 6px !important;
}
.split-bill-pane-hint,
.split-bill-quick-guide-text {
    font-size: 12px;
    line-height: 1.45;
    color: #667085;
}
.split-bill-section-badge,
.split-bill-live-badge {
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    background: #eef6fb;
    color: #32789a;
}
.split-bill-items,
.split-bill-target-list {
    gap: 10px;
}
.split-bill-card {
    padding: 10px;
    border-radius: 15px;
    border: 1px solid #e8edf3;
    background: #fff;
    box-shadow: none;
}
.split-bill-card.is-active,
.split-bill-card.is-target {
    border-color: #cfe2f3;
    background: #fbfdff;
}
.split-bill-source-card-main {
    display: grid;
    grid-template-columns: minmax(0,1fr) auto;
    gap: 10px;
    align-items: start;
    margin-bottom: 8px;
}
.split-bill-card-name {
    font-size: 15px;
    line-height: 1.35;
    margin-bottom: 2px;
}
.split-bill-card-option,
.split-bill-card-note {
    font-size: 11px;
    line-height: 1.35;
}
.split-bill-card-meta {
    gap: 6px;
    margin-bottom: 8px;
}
.split-bill-meta-chip,
.split-bill-footer-chip {
    padding: 4px 8px;
    font-size: 11px;
    border-radius: 999px;
    background: #f5f7fa;
    border: 1px solid #e5eaf0;
    color: #5b6572;
}
.split-bill-main-tap {
    min-width: 92px;
    min-height: 34px;
    padding: 7px 9px;
    border-radius: 11px;
    background: #eefafd;
    border: 1px solid #bfe8f5;
    color: #1683ab;
    font-size: 11px;
    line-height: 1.1;
    text-align: center;
    cursor: pointer;
}
.split-bill-main-tap small {
    display: none;
}
.split-bill-action-row,
.split-bill-card-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    flex-wrap: nowrap;
}
.split-bill-quick-actions {
    display: inline-flex;
    gap: 6px;
    flex-wrap: wrap;
}
.split-bill-quick-actions .btn,
.split-bill-card-actions .btn,
.split-bill-qty-stepper .btn {
    min-height: 32px;
    padding: 5px 10px;
    border-radius: 11px;
    font-size: 11px;
    font-weight: 600;
}
.split-bill-qty-stepper {
    gap: 6px;
    flex-shrink: 0;
}
.split-bill-qty-stepper .btn {
    min-width: 30px;
    padding: 5px 0;
}
.split-bill-qty-stepper .split-bill-qty-display {
    min-width: 34px;
    min-height: 30px;
    padding: 5px 7px;
    font-size: 12px;
    border-radius: 11px;
    background: #f7f9fb;
    border: 1px solid #e3e8ef;
}
.split-bill-target-hero {
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px dashed #cae5f2;
    background: #fff;
    margin-bottom: 10px;
}
.split-bill-target-hero strong {
    font-size: 24px;
    color: #1f2937;
}
.split-bill-preview-box {
    padding: 10px;
    border-radius: 14px;
    background: #fff;
    border: 1px solid #e6ecf2;
}
.split-bill-footer-layout {
    gap: 10px;
    align-items: center;
}
.split-bill-footer-actions .btn {
    min-height: 40px;
    border-radius: 14px;
    padding: 8px 14px;
}
@media (max-width: 991.98px) {
    .split-bill-workspace {
        grid-template-columns: 1fr;
    }
    .split-bill-pane.is-target {
    }
    .split-bill-mobile-totalbar {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 12px;
    }
}
@media (max-width: 767.98px) {
    .split-bill-summary-grid {
        grid-template-columns: 1fr 1fr;
    }
    .split-bill-summary-card {
        min-height: 68px;
        padding: 9px 12px;
    }
    .split-bill-summary-value {
        font-size: 14px;
    }
    .split-bill-pane {
        padding: 10px;
        border-radius: 16px;
    }
    .split-bill-source-card-main {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    .split-bill-main-tap {
        width: 100%;
        min-width: 0;
    }
    .split-bill-action-row,
    .split-bill-card-actions {
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
    }
    .split-bill-quick-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
        width: 100%;
    }
    .split-bill-quick-actions .btn {
        width: 100%;
        padding: 7px 10px;
    }
    .split-bill-qty-stepper {
        margin-left: auto;
    }
    .split-bill-footer-layout {
        display: grid;
        gap: 10px;
    }
    .split-bill-footer-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .split-bill-footer-actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
    }
    .split-bill-footer-actions .btn {
        width: 100%;
        min-height: 42px;
        padding: 8px 6px;
        font-size: 12px;
    }
    .split-bill-state-note {
        margin-top: 0;
        font-size: 10px;
        padding: 4px 7px;
    }
}

    #splitBillModal .modal-dialog {
        margin: .35rem;
        max-width: calc(100vw - .7rem);
    }
    #splitBillModal .modal-content {
        border-radius: 20px;
    }
    #splitBillModal .modal-footer {
        position: sticky;
        bottom: 0;
        background: rgba(255,255,255,0.98);
        border-top: 1px solid rgba(148, 163, 184, 0.12);
        z-index: 4;
    }
    .split-bill-summary-grid {
        grid-template-columns: 1fr 1fr;
    }
    .split-bill-pane {
        padding: .9rem;
    }
    .split-bill-source-card-main {
        grid-template-columns: 1fr;
    }
    .split-bill-main-tap {
        width: 100%;
    }
    .split-bill-action-row,
    .split-bill-card-actions,
    .split-bill-target-hero {
        flex-direction: column;
        align-items: stretch;
    }
    .split-bill-quick-actions,
    .split-bill-footer-actions,
    .split-bill-footer-summary,
    .split-bill-target-summary-meta {
        width: 100%;
    }
    .split-bill-quick-actions .btn,
    .split-bill-footer-actions .btn,
    .split-bill-card-actions .btn {
        flex: 1 1 auto;
    }
    .split-bill-preview-box .split-bill-summary-grid {
        grid-template-columns: 1fr 1fr;
    }
    .split-bill-mobile-totalbar {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .55rem;
        margin-bottom: .75rem;
    }
    .split-bill-quick-guide {
        padding: .75rem .85rem;
    }
}



/* ==== Split Bill Final Polish ==== */
.split-bill-card.is-active{
    background:#f8fcff;
    border-color:#b9deee;
    box-shadow:0 4px 12px rgba(14,165,233,.06);
}
.split-bill-pane.is-target{
    background:#f9fcfe;
    border-color:#d9e8ef;
}
.split-bill-main-tap{
    min-width:88px;
    min-height:32px;
    padding:.38rem .62rem;
    border-radius:999px;
    background:#f3f7fa;
    border:1px solid #d8e2ea;
    color:#4b5563;
    font-size:.74rem;
    font-weight:700;
    cursor:default;
}
.split-bill-main-tap.is-disabled,
.split-bill-main-tap:disabled{
    background:#fff7ed;
    border-color:#fed7aa;
    color:#c2410c;
}
@media (max-width: 767.98px){
    .split-bill-main-tap{
        min-width:74px;
        min-height:28px;
        padding:5px 8px;
        font-size:10px;
    }
    .split-bill-card.is-active{
        background:#fbfeff;
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

    $viewAllLabel = lang('app.view_all');
    if ($viewAllLabel === 'app.view_all') {
        $viewAllLabel = service('request')->getLocale() === 'th' ? 'ดูทั้งหมด' : 'View all';
    }

    $addToBillLabel = lang('app.add_to_bill');
    if ($addToBillLabel === 'app.add_to_bill') {
        $addToBillLabel = service('request')->getLocale() === 'th' ? 'เพิ่มลงบิล' : 'Add to bill';
    }

    $splitBillLabels = [
        'quickGuideTitle' => lang('app.split_bill_quick_guide_title'),
        'quickGuideText' => lang('app.split_bill_quick_guide_text'),
        'sourceBadge' => lang('app.split_bill_source_badge'),
        'targetBadge' => lang('app.split_bill_target_badge'),
        'selectSingles' => lang('app.split_bill_select_all_single_qty'),
        'clearAll' => lang('app.split_bill_clear_all'),
        'originalBill' => lang('app.split_bill_original_bill'),
        'newBill' => lang('app.split_bill_new_bill'),
        'livePreview' => lang('app.split_bill_live_preview'),
        'touchToMove' => lang('app.split_bill_touch_to_move'),
        'targetEmptyHint' => lang('app.split_bill_target_empty_hint'),
        'emptyNewBill' => lang('app.split_bill_empty_new_bill'),
        'noPreview' => lang('app.split_bill_no_preview'),
        'reason' => lang('app.split_bill_reason'),
        'reasonPlaceholder' => lang('app.split_bill_reason_placeholder'),
        'confirm' => lang('app.confirm_split_bill'),
        'notBillable' => lang('app.split_bill_not_billable'),
        'stillBillable' => lang('app.split_bill_still_billable'),
    ];

    $splitBillFallbacks = service('request')->getLocale() === 'th'
        ? [
            'quickGuideTitle' => 'วิธีใช้แบบเร็ว',
            'quickGuideText' => 'รายการที่มี 1 ชิ้น แตะการ์ดเพื่อย้ายได้ทันที • ถ้ามีหลายชิ้น ใช้ปุ่ม ย้าย 1 ชิ้น / ย้ายทั้งหมด หรือปุ่ม + - เพื่อปรับจำนวน',
            'sourceBadge' => 'แตะทางนี้เพื่อย้าย',
            'targetBadge' => 'ตรวจสอบก่อนยืนยัน',
            'selectSingles' => 'เลือก 1 ชิ้นทั้งหมด',
            'clearAll' => 'ล้างทั้งหมด',
            'originalBill' => 'บิลเดิม',
            'newBill' => 'บิลใหม่',
            'livePreview' => 'อัปเดตสด',
            'touchToMove' => 'แตะเพื่อย้ายทันที',
            'targetEmptyHint' => 'รายการที่เลือกไว้จะมาอยู่ฝั่งนี้ทันที',
            'emptyNewBill' => 'เริ่มจากแตะรายการฝั่งบิลเดิม',
            'noPreview' => 'ยังไม่มีข้อมูลพรีวิว',
            'reason' => 'เหตุผลการแยกบิล',
            'reasonPlaceholder' => 'เช่น ลูกค้าจ่ายแยก / แยกเฉพาะเครื่องดื่ม / แยกของลูกค้าโต๊ะเดียวกัน',
            'confirm' => 'ยืนยันแยกบิล',
            'notBillable' => 'ยังไม่คิดเงิน',
            'stillBillable' => 'ยังคิดเงินอยู่',
        ]
        : [
            'quickGuideTitle' => 'Quick guide',
            'quickGuideText' => 'Tap a single-qty card to move it instantly • For multi-qty items, use Move 1 / Move all or the + - buttons to adjust the quantity.',
            'sourceBadge' => 'Tap here to move',
            'targetBadge' => 'Review before confirm',
            'selectSingles' => 'Select all single-qty',
            'clearAll' => 'Clear all',
            'originalBill' => 'Original Bill',
            'newBill' => 'New Bill',
            'livePreview' => 'Live preview',
            'touchToMove' => 'Tap to move instantly',
            'targetEmptyHint' => 'Selected items will appear here instantly.',
            'emptyNewBill' => 'Start by tapping items from the original bill.',
            'noPreview' => 'No preview yet',
            'reason' => 'Split reason',
            'reasonPlaceholder' => 'For example: separate payment / drinks only / split for one guest',
            'confirm' => 'Confirm Split Bill',
            'notBillable' => 'Not billable',
            'stillBillable' => 'Still billable',
        ];

    foreach ($splitBillFallbacks as $key => $fallbackValue) {
        if (($splitBillLabels[$key] ?? '') === 'app.split_bill_' . strtolower(preg_replace('/([A-Z])/', '_$1', $key)) || ($splitBillLabels[$key] ?? '') === 'app.confirm_split_bill') {
            $splitBillLabels[$key] = $fallbackValue;
        } elseif (($splitBillLabels[$key] ?? '') === '' || strpos((string) ($splitBillLabels[$key] ?? ''), 'app.') === 0) {
            $splitBillLabels[$key] = $fallbackValue;
        }
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
                        <div class="pos-bill-header-actions">
                            <button
                                type="button"
                                class="btn btn-outline-secondary btn-sm"
                                id="btnBillTimelineQuick"
                                disabled
                            >
                                <span>🕘</span>
                                <span><?= esc(lang('app.timeline')) ?></span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary pos-bill-pane-close" id="btnCloseBillDrawer">×</button>
                        </div>
                    </div>

                    <div class="pos-bill-scroll">
                        <div id="splitGroupPanel" class="pos-split-group-panel is-hidden"></div>
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

                            <button
                                type="button"
                                class="btn btn-outline-info"
                                id="btnSplitBill"
                                <?= $tableDisabled ? 'disabled' : '' ?>
                            >
                                <?= esc(lang('app.split_bill') ?? 'Split Bill') ?>
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

<div class="modal fade" id="billTimelineModal" tabindex="-1" aria-labelledby="billTimelineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable pos-bill-timeline-dialog">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="billTimelineModalLabel"><?= esc(lang('app.bill_timeline')) ?></h5>
                    <div class="small text-muted" id="billTimelineMeta">-</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="billTimelineSummary" class="pos-timeline-summary"></div>
                <div id="billTimelineBody" class="pos-timeline-list">
                    <div class="pos-timeline-empty"><?= esc(lang('app.no_data')) ?></div>
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



<div class="modal fade" id="splitBillModal" tabindex="-1" aria-labelledby="splitBillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="splitBillModalLabel"><?= esc(lang('app.split_bill')) ?></h5>
                    <div class="small text-muted" id="splitBillModalMeta">-</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= esc(lang('app.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="split-bill-summary-grid" id="splitBillSummaryGrid">
                    <div class="split-bill-summary-card">
                        <div class="split-bill-summary-label"><?= esc(lang('app.table') ?? (service('request')->getLocale() === 'th' ? 'โต๊ะ' : 'Table')) ?></div>
                        <div class="split-bill-summary-value" id="splitBillSummaryTable">-</div>
                    </div>
                    <div class="split-bill-summary-card">
                        <div class="split-bill-summary-label"><?= esc(lang('app.split_bill_summary_items') ?? (service('request')->getLocale() === 'th' ? 'จำนวนรายการ' : 'Items')) ?></div>
                        <div class="split-bill-summary-value" id="splitBillSummaryItems">0</div>
                    </div>
                    <div class="split-bill-summary-card">
                        <div class="split-bill-summary-label"><?= esc(lang('app.split_bill_summary_qty') ?? (service('request')->getLocale() === 'th' ? 'จำนวนรวม' : 'Qty')) ?></div>
                        <div class="split-bill-summary-value" id="splitBillSummaryQty">0</div>
                    </div>
                    <div class="split-bill-summary-card is-accent">
                        <div class="split-bill-summary-label"><?= esc(lang('app.split_bill_preview_child_total') ?? (service('request')->getLocale() === 'th' ? 'ยอดบิลใหม่' : 'New bill total')) ?></div>
                        <div class="split-bill-summary-value" id="splitBillSummaryTotal"><?= esc(number_format(0, 2)) ?></div>
                    </div>
                </div>

                <div class="split-bill-quick-guide">
                    <div class="split-bill-quick-guide-icon">↔</div>
                    <div>
                        <div class="split-bill-quick-guide-title"><?= esc($splitBillLabels['quickGuideTitle']) ?></div>
                        <div class="split-bill-quick-guide-text"><?= esc($splitBillLabels['quickGuideText']) ?></div>
                    </div>
                </div>
                <div class="split-bill-mobile-totalbar">
                    <div class="split-bill-summary-card is-accent">
                        <div class="split-bill-summary-label"><?= esc(lang('app.split_bill_new_bill')) ?></div>
                        <div class="split-bill-summary-value" id="splitBillMobileTotal"><?= esc(number_format(0, 2)) ?></div>
                    </div>
                    <div class="split-bill-summary-card">
                        <div class="split-bill-summary-label"><?= esc(lang('app.split_bill_summary_qty')) ?></div>
                        <div class="split-bill-summary-value" id="splitBillMobileQty">0</div>
                    </div>
                </div>

                <div class="split-bill-workspace">
                    <div class="split-bill-pane">
                        <div class="split-bill-pane-head">
                            <div>
                                <div class="split-bill-section-badge"><?= esc($splitBillLabels['sourceBadge']) ?></div>
                                <div class="split-bill-pane-title mt-2"><?= esc($splitBillLabels['originalBill']) ?></div>
                                <div class="split-bill-pane-hint"><?= esc($splitBillLabels['touchToMove']) ?></div>
                            </div>
                            <div class="split-bill-toolbar">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnSplitBillSelectSingles">
                                    <?= esc($splitBillLabels['selectSingles']) ?>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSplitBillClearAll">
                                    <?= esc($splitBillLabels['clearAll']) ?>
                                </button>
                            </div>
                        </div>
                        <div id="splitBillItemsBox" class="split-bill-items">
                            <div class="split-bill-empty-state"><?= esc(lang('app.no_data')) ?></div>
                        </div>
                    </div>

                    <div class="split-bill-pane is-target">
                        <div class="split-bill-pane-head">
                            <div>
                                <div class="split-bill-section-badge"><?= esc($splitBillLabels['targetBadge']) ?></div>
                                <div class="split-bill-pane-title mt-2"><?= esc($splitBillLabels['newBill']) ?></div>
                                <div class="split-bill-pane-hint" id="splitBillPreviewHint"><?= esc(lang('app.split_bill_select_items_hint') ?? (service('request')->getLocale() === 'th' ? 'เริ่มแตะรายการทางซ้ายเพื่อย้ายไปบิลใหม่' : 'Start tapping items on the left to move them into the new bill')) ?></div>
                            </div>
                            <div class="split-bill-live-badge"><?= esc($splitBillLabels['livePreview']) ?></div>
                        </div>

                        <div class="split-bill-target-summary">
                            <div class="split-bill-target-hero">
                                <div>
                                    <div class="split-bill-target-summary-title"><?= esc(lang('app.split_bill_items_moved') ?? (service('request')->getLocale() === 'th' ? 'รายการที่จะย้ายไปบิลใหม่' : 'Items moving to new bill')) ?></div>
                                    <div class="split-bill-pane-hint"><?= esc($splitBillLabels['targetEmptyHint']) ?></div>
                                </div>
                                <strong id="splitBillTargetHeroTotal"><?= esc(number_format(0, 2)) ?></strong>
                            </div>
                            <div class="split-bill-target-summary-meta">
                                <span class="split-bill-footer-chip" id="splitBillTargetSummaryItems">0</span>
                                <span class="split-bill-footer-chip" id="splitBillTargetSummaryQty">0</span>
                            </div>
                        </div>

                        <div id="splitBillTargetBox" class="split-bill-target-list">
                            <div class="split-bill-empty-state"><?= esc($splitBillLabels['emptyNewBill']) ?></div>
                        </div>

                        <div class="split-bill-preview-box small" id="splitBillPreviewBox">
                            <div class="text-muted"><?= esc($splitBillLabels['noPreview']) ?></div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-semibold" for="splitBillReason"><?= esc($splitBillLabels['reason']) ?></label>
                            <textarea class="form-control" id="splitBillReason" rows="2" placeholder="<?= esc($splitBillLabels['reasonPlaceholder']) ?>"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="split-bill-footer-layout">
                    <div class="split-bill-footer-summary">
                        <span class="split-bill-footer-chip" id="splitBillFooterTable">-</span>
                        <span class="split-bill-footer-chip" id="splitBillFooterItems">0</span>
                        <span class="split-bill-footer-chip" id="splitBillFooterQty">0</span>
                        <span class="split-bill-footer-chip" id="splitBillFooterTotal"><?= esc(number_format(0, 2)) ?></span>
                    </div>
                    <div class="split-bill-footer-actions">
                        <button type="button" class="btn btn-outline-secondary split-bill-legacy-preview-toggle" id="btnSplitBillPreviewRefresh">
                            <?= esc(lang('app.refresh') ?? (service('request')->getLocale() === 'th' ? 'รีเฟรช' : 'Refresh')) ?>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= esc(lang('app.cancel')) ?></button>
                        <button type="button" class="btn btn-info" id="btnConfirmSplitBill" disabled>
                            <?= esc($splitBillLabels['confirm']) ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="recentItemsModal" tabindex="-1" aria-labelledby="recentItemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="recentItemsModalLabel"><?= esc($recentItemsLabel) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= esc(lang('app.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="recent-items-modal-list" id="recentItemsModalBody">
                    <div class="text-muted"><?= esc(lang('app.no_data')) ?></div>
                </div>
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
    const TABLE_NAME_CONST = <?= json_encode((string) ($table['table_name'] ?? '-'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let CURRENT_ORDER_ID = <?= json_encode(!empty($currentOrder['id']) ? (int) $currentOrder['id'] : null) ?>;
    let CURRENT_ORDER_STATUS = <?= json_encode($currentOrder['status'] ?? null) ?>;
    // Compatibility shim: some older event handlers still reference CURRENT_TABLE_NAME.
    // Keep this global/fallback in sync with the visible table label to prevent runtime errors.
    let CURRENT_TABLE_NAME = String(TABLE_NAME_CONST || '').trim();
    window.CURRENT_TABLE_NAME = CURRENT_TABLE_NAME;
    let SELECTED_PRODUCT_ID = null;
    let SELECTED_PRODUCT_NAME = '';
    let SELECTED_PRODUCT_PRICE = 0;
    let AUTO_REFRESH_TIMER = null;
    let RECENT_PRODUCT_ITEMS = [];
    let CURRENT_ORDER_DATA = null;
    let CURRENT_ORDER_ITEMS = [];
    let CURRENT_SPLIT_GROUP = [];
    let SPLIT_PREVIEW_CACHE = null;
    let SPLIT_BILL_WORKSPACE_STATE = null;

    const moveTableModalEl = document.getElementById('moveTableModal');
    const moveTableModal = moveTableModalEl ? new bootstrap.Modal(moveTableModalEl) : null;

    const mergeBillModalEl = document.getElementById('mergeBillModal');
    const mergeBillModal = mergeBillModalEl ? new bootstrap.Modal(mergeBillModalEl) : null;

    const splitBillModalEl = document.getElementById('splitBillModal');
    const splitBillModal = splitBillModalEl ? new bootstrap.Modal(splitBillModalEl) : null;

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
        voidItem: <?= json_encode(lang('app.void_item'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        voidItemTitle: <?= json_encode(lang('app.void_item_modal_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        voidItemReason: <?= json_encode(lang('app.void_item_reason'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        voidItemReasonPlaceholder: <?= json_encode(lang('app.void_item_reason_placeholder'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        voidItemReasonRequired: <?= json_encode(lang('app.void_item_reason_required'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        voidItemConfirm: <?= json_encode(lang('app.void_item_confirm'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        voidItemSuccess: <?= json_encode(lang('app.void_item_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        voidItemFailed: <?= json_encode(lang('app.void_item_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
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
        managerOverrideActionVoidItem: <?= json_encode(lang('app.manager_override_action_void_item'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
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
        splitGroupTitle: <?= json_encode(lang('app.split_group_panel_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupNote: <?= json_encode(lang('app.split_group_panel_note'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupBills: <?= json_encode(lang('app.split_group_bills_count'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupPaid: <?= json_encode(lang('app.split_group_paid_count'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupRemaining: <?= json_encode(lang('app.split_group_remaining_total'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillRoleRoot: <?= json_encode(lang('app.split_bill_root'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillRoleParent: <?= json_encode(lang('app.split_bill_parent'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillRoleChild: <?= json_encode(lang('app.split_bill_child'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupSelectBill: <?= json_encode(lang('app.split_group_select_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupPayBill: <?= json_encode(lang('app.split_group_pay_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupAlreadyPaid: <?= json_encode(lang('app.split_group_already_paid'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupSelected: <?= json_encode(lang('app.selected'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupItemsLabel: <?= json_encode(lang('app.split_bill_summary_items'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupTotalLabel: <?= json_encode(lang('app.grand_total'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupItemsInBill: <?= json_encode(lang('app.split_group_items_in_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupShowItems: <?= json_encode(lang('app.split_group_show_items'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupHideItems: <?= json_encode(lang('app.split_group_hide_items'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitGroupNoItems: <?= json_encode(lang('app.split_group_no_items'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        currentBill: <?= json_encode(lang('app.current_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
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
        viewAll: <?= json_encode($viewAllLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        addToBillAction: <?= json_encode($addToBillLabel, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        timeline: <?= json_encode(lang('app.timeline'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        viewTimeline: <?= json_encode(lang('app.view_timeline'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        billTimeline: <?= json_encode(lang('app.bill_timeline'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        timelineEmpty: <?= json_encode(lang('app.no_data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        loadTimelineFailed: <?= json_encode(service('request')->getLocale() === 'th' ? 'โหลดไทม์ไลน์ไม่สำเร็จ' : 'Unable to load timeline', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        sentToKitchenTimeline: <?= json_encode(service('request')->getLocale() === 'th' ? 'ส่งรายการเข้าครัว' : 'Sent items to kitchen', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        viewedCashierTimeline: <?= json_encode(service('request')->getLocale() === 'th' ? 'เปิดหน้าชำระเงิน' : 'Opened cashier screen', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        paymentTimeline: <?= json_encode(service('request')->getLocale() === 'th' ? 'ชำระเงินแล้ว' : 'Payment completed', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        moveTimeline: <?= json_encode(service('request')->getLocale() === 'th' ? 'ย้ายโต๊ะ' : 'Moved table', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        mergeTimeline: <?= json_encode(service('request')->getLocale() === 'th' ? 'รวมบิล' : 'Merged bill', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        reopenTimeline: <?= json_encode(service('request')->getLocale() === 'th' ? 'เปิดบิลกลับมาอีกครั้ง' : 'Reopened bill', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        undoPaymentTimeline: <?= json_encode(service('request')->getLocale() === 'th' ? 'ยกเลิกการชำระเงิน' : 'Undid payment', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        voidItemTimeline: <?= json_encode(lang('app.audit_log_void_item'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        actorLabel: <?= json_encode(service('request')->getLocale() === 'th' ? 'ผู้ทำรายการ' : 'Staff', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        kitchenType: <?= json_encode(service('request')->getLocale() === 'th' ? 'ครัว' : 'Kitchen', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        billingType: <?= json_encode(service('request')->getLocale() === 'th' ? 'บิล' : 'Billing', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        paymentType: <?= json_encode(service('request')->getLocale() === 'th' ? 'ชำระเงิน' : 'Payment', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        tableType: <?= json_encode(service('request')->getLocale() === 'th' ? 'โต๊ะ' : 'Table', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        generalType: <?= json_encode(service('request')->getLocale() === 'th' ? 'ทั่วไป' : 'General', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        timelineSummaryServed: <?= json_encode(service('request')->getLocale() === 'th' ? 'เสิร์ฟแล้ว' : 'Served', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        timelineSummaryKitchen: <?= json_encode(service('request')->getLocale() === 'th' ? 'ส่งเข้าครัว' : 'Sent to kitchen', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        timelineSummaryLatest: <?= json_encode(service('request')->getLocale() === 'th' ? 'ล่าสุด' : 'Latest update', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        sourceScreenKitchenBoard: <?= json_encode(service('request')->getLocale() === 'th' ? 'บอร์ดครัว' : 'Kitchen board', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        sourceScreenPosTable: <?= json_encode(service('request')->getLocale() === 'th' ? 'หน้าโต๊ะ POS' : 'POS table', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        statusServed: <?= json_encode(service('request')->getLocale() === 'th' ? 'เสิร์ฟแล้ว' : 'Served', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        statusReady: <?= json_encode(service('request')->getLocale() === 'th' ? 'พร้อมเสิร์ฟ' : 'Ready', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        statusPreparing: <?= json_encode(service('request')->getLocale() === 'th' ? 'กำลังทำ' : 'Preparing', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        statusSent: <?= json_encode(service('request')->getLocale() === 'th' ? 'ส่งครัวแล้ว' : 'Sent to kitchen', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBill: <?= json_encode(lang('app.split_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillPreviewReady: <?= json_encode(lang('app.split_bill_preview_ready'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillSuccess: <?= json_encode(lang('app.split_bill_success'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillFailed: <?= json_encode(lang('app.split_bill_failed'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillNoItemsSelected: <?= json_encode(lang('app.split_bill_no_items_selected'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillItemCancelled: <?= json_encode(lang('app.split_bill_item_cancelled'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillRoot: <?= json_encode(service('request')->getLocale() === 'th' ? 'บิลหลัก' : 'Root Bill', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillParent: <?= json_encode(service('request')->getLocale() === 'th' ? 'บิลหลักที่ถูกแยกแล้ว' : 'Split Parent', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillChild: <?= json_encode(service('request')->getLocale() === 'th' ? 'บิลแยก' : 'Split Child', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillSelectItemsHint: <?= json_encode(service('request')->getLocale() === 'th' ? 'เลือกรายการก่อนเพื่อดูยอดสรุป' : 'Select items to see the summary', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillSummaryItems: <?= json_encode(lang('app.split_bill_summary_items'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillSummaryQty: <?= json_encode(lang('app.split_bill_summary_qty'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillSelectSingle: <?= json_encode(lang('app.split_bill_select_single'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillSelected: <?= json_encode(service('request')->getLocale() === 'th' ? 'เลือกแล้ว' : 'Selected', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillItemsMoved: <?= json_encode(lang('app.split_bill_items_moved'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillNoPreview: <?= json_encode(service('request')->getLocale() === 'th' ? 'ยังไม่มีข้อมูลตัวอย่าง' : 'No preview yet', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillNoEligibleItems: <?= json_encode(service('request')->getLocale() === 'th' ? 'ไม่มีรายการที่สามารถแยกบิลได้' : 'No eligible items for split bill', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillQty: <?= json_encode(service('request')->getLocale() === 'th' ? 'จำนวนที่แยก' : 'Split qty', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillSourceQty: <?= json_encode(service('request')->getLocale() === 'th' ? 'จำนวนเดิม' : 'Original qty', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillRemainingQty: <?= json_encode(service('request')->getLocale() === 'th' ? 'คงเหลือ' : 'Remaining', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillPreviewChildTotal: <?= json_encode(service('request')->getLocale() === 'th' ? 'ยอดรวมบิลใหม่' : 'New bill total', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillPreviewParentTotal: <?= json_encode(service('request')->getLocale() === 'th' ? 'ยอดคงเหลือบิลเดิม' : 'Remaining parent total', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillSelectAtLeastOne: <?= json_encode(service('request')->getLocale() === 'th' ? 'กรุณาเลือกจำนวนอย่างน้อย 1 รายการ' : 'Please enter at least one split quantity', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillLoading: <?= json_encode(service('request')->getLocale() === 'th' ? 'กำลังโหลดตัวอย่าง...' : 'Loading preview...', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillIneligibleStatus: <?= json_encode(service('request')->getLocale() === 'th' ? 'รายการนี้ไม่สามารถแยกบิลได้' : 'This item cannot be split', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillTapToMoveHint: <?= json_encode(lang('app.split_bill_tap_to_move_hint'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillTouchToMove: <?= json_encode(lang('app.split_bill_touch_to_move'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillMoveOne: <?= json_encode((lang('app.split_bill_move_one') === 'app.split_bill_move_one' ? (service('request')->getLocale() === 'th' ? 'ย้าย 1 ชิ้น' : 'Move 1') : lang('app.split_bill_move_one')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillMoveAll: <?= json_encode((lang('app.split_bill_move_all') === 'app.split_bill_move_all' ? (service('request')->getLocale() === 'th' ? 'ย้ายทั้งหมด' : 'Move all') : lang('app.split_bill_move_all')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillMoveBack: <?= json_encode(lang('app.split_bill_move_back'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillBackOne: <?= json_encode(lang('app.split_bill_back_one'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillMovedQty: <?= json_encode(lang('app.split_bill_moved_qty'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillTargetReady: <?= json_encode(lang('app.split_bill_target_ready'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillTableLabel: <?= json_encode(lang('app.table'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillEmptyNewBill: <?= json_encode(lang('app.split_bill_empty_new_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillTouchToMove: <?= json_encode(lang('app.split_bill_touch_to_move'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillQuickGuideTitle: <?= json_encode(lang('app.split_bill_quick_guide_title'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillQuickGuideText: <?= json_encode(lang('app.split_bill_quick_guide_text'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillSourceBadge: <?= json_encode(lang('app.split_bill_source_badge'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillTargetBadge: <?= json_encode(lang('app.split_bill_target_badge'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillTouchToMoveHint: <?= json_encode(lang('app.split_bill_tap_to_move_hint'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillTargetReady: <?= json_encode(lang('app.split_bill_target_ready'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillTargetEmptyHint: <?= json_encode(lang('app.split_bill_target_empty_hint'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillBackOne: <?= json_encode(lang('app.split_bill_back_one'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillMovedQty: <?= json_encode(lang('app.split_bill_moved_qty'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillMoveOne: <?= json_encode((lang('app.split_bill_move_one') === 'app.split_bill_move_one' ? (service('request')->getLocale() === 'th' ? 'ย้าย 1 ชิ้น' : 'Move 1') : lang('app.split_bill_move_one')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillMoveAll: <?= json_encode((lang('app.split_bill_move_all') === 'app.split_bill_move_all' ? (service('request')->getLocale() === 'th' ? 'ย้ายทั้งหมด' : 'Move all') : lang('app.split_bill_move_all')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillMoveBack: <?= json_encode(lang('app.split_bill_move_back'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillLivePreview: <?= json_encode($splitBillLabels['livePreview'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillOriginalBill: <?= json_encode($splitBillLabels['originalBill'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillMovedFully: <?= json_encode(service('request')->getLocale() === 'th' ? 'ย้ายครบแล้ว' : 'Moved fully', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillNewBill: <?= json_encode($splitBillLabels['newBill'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillEmptyNewBill: <?= json_encode(lang('app.split_bill_empty_new_bill'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBillNotBillable: <?= json_encode($splitBillLabels['notBillable'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        reasonLabel: <?= json_encode(lang('app.note'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };

    const productOptionModalEl = document.getElementById('productOptionModal');
    const productOptionModal = productOptionModalEl ? new bootstrap.Modal(productOptionModalEl) : null;

    const paymentModalEl = document.getElementById('paymentModal');
    const paymentModal = paymentModalEl ? new bootstrap.Modal(paymentModalEl) : null;
    const recentItemsModalEl = document.getElementById('recentItemsModal');
    const recentItemsModal = recentItemsModalEl ? new bootstrap.Modal(recentItemsModalEl) : null;
    const mergeAuditModalEl = document.getElementById('mergeAuditModal');
    const mergeAuditModal = mergeAuditModalEl ? new bootstrap.Modal(mergeAuditModalEl) : null;
    const moveAuditModalEl = document.getElementById('moveAuditModal');
    const moveAuditModal = moveAuditModalEl ? new bootstrap.Modal(moveAuditModalEl) : null;
    const managerOverrideModalEl = document.getElementById('managerOverrideModal');
    const managerOverrideModal = managerOverrideModalEl ? new bootstrap.Modal(managerOverrideModalEl) : null;
    const billTimelineModalEl = document.getElementById('billTimelineModal');
    const billTimelineModal = billTimelineModalEl ? new bootstrap.Modal(billTimelineModalEl) : null;
    let managerOverrideResolver = null;

    const TABLE_PERMISSIONS = {
        voidItem: <?= json_encode((bool) ($tablePermissions['void_item'] ?? false), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        splitBill: <?= json_encode((bool) ($tablePermissions['split_bill'] ?? false), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };

    const voidItemModalEl = document.getElementById('voidItemModal');
    const voidItemModal = voidItemModalEl ? new bootstrap.Modal(voidItemModalEl) : null;

    function money(num) {
        return '฿' + parseFloat(num || 0).toFixed(2);
    }

    function escapeHtml(text) {
        return $('<div>').text(text ?? '').html();
    }



    function formatDateTime(value, options = {}) {
        if (!value) {
            return '-';
        }

        const date = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return escapeHtml(String(value));
        }

        const locale = <?= json_encode(service('request')->getLocale() === 'th' ? 'th-TH' : 'en-US') ?>;
        const mode = options.mode || 'full';

        if (mode === 'time') {
            return date.toLocaleTimeString(locale, {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        if (mode === 'compact') {
            return date.toLocaleString(locale, {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        return date.toLocaleString(locale, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function timelineActionMeta(actionKey, actionLabel) {
        const key = String(actionKey || '').toLowerCase();
        const rawLabel = String(actionLabel || '').trim();

        const map = {
            'pos.send_kitchen': { title: TXT.sentToKitchenTimeline, type: 'kitchen' },
            'cashier.view': { title: TXT.viewedCashierTimeline, type: 'billing' },
            'cashier.pay': { title: TXT.paymentTimeline, type: 'payment' },
            'cashier.undo_payment': { title: TXT.undoPaymentTimeline, type: 'payment' },
            'cashier.reopen_bill': { title: TXT.reopenTimeline, type: 'billing' },
            'pos.void_item': { title: TXT.voidItemTimeline, type: 'general' },
            'pos.move_table': { title: TXT.moveTimeline, type: 'table' },
            'pos.merge_bill': { title: TXT.mergeTimeline, type: 'table' },
            'audit_logs.view': { title: TXT.viewTimeline, type: 'general' },
            'pos.item_served': { title: TXT.servedTimeline, type: 'kitchen' }
        };

        if (map[key]) {
            return map[key];
        }

        if (rawLabel !== '') {
            return { title: rawLabel, type: 'general' };
        }

        return { title: key || TXT.timeline, type: 'general' };
    }

    function timelineTypeLabel(type) {
        if (type === 'kitchen') return TXT.kitchenType;
        if (type === 'billing') return TXT.billingType;
        if (type === 'payment') return TXT.paymentType;
        if (type === 'table') return TXT.tableType;
        return TXT.generalType;
    }

    function beautifyTimelineMetaLabel(key) {
        const map = {
            ticket_no: <?= json_encode(service('request')->getLocale() === 'th' ? 'เลขตั๋วครัว' : 'Kitchen ticket') ?>,
            batch_no: <?= json_encode(service('request')->getLocale() === 'th' ? 'รอบส่งครัว' : 'Kitchen batch') ?>,
            item_count: <?= json_encode(service('request')->getLocale() === 'th' ? 'จำนวนรายการ' : 'Items') ?>,
            payment_method: <?= json_encode(service('request')->getLocale() === 'th' ? 'วิธีชำระเงิน' : 'Payment method') ?>,
            amount: <?= json_encode(service('request')->getLocale() === 'th' ? 'ยอดเงิน' : 'Amount') ?>,
            received_amount: <?= json_encode(service('request')->getLocale() === 'th' ? 'รับเงินมา' : 'Received') ?>,
            change_amount: <?= json_encode(service('request')->getLocale() === 'th' ? 'เงินทอน' : 'Change') ?>,
            reason: <?= json_encode(service('request')->getLocale() === 'th' ? 'เหตุผล' : 'Reason') ?>,
            from_table_name: <?= json_encode(service('request')->getLocale() === 'th' ? 'จากโต๊ะ' : 'From table') ?>,
            to_table_name: <?= json_encode(service('request')->getLocale() === 'th' ? 'ไปโต๊ะ' : 'To table') ?>,
            source_order_number: <?= json_encode(service('request')->getLocale() === 'th' ? 'บิลต้นทาง' : 'Source bill') ?>,
            target_order_number: <?= json_encode(service('request')->getLocale() === 'th' ? 'บิลปลายทาง' : 'Target bill') ?>,
            served_at: <?= json_encode(service('request')->getLocale() === 'th' ? 'เวลาเสิร์ฟ' : 'Served at') ?>,
            from_status: <?= json_encode(service('request')->getLocale() === 'th' ? 'จากสถานะ' : 'From status') ?>,
            to_status: <?= json_encode(service('request')->getLocale() === 'th' ? 'เป็นสถานะ' : 'To status') ?>,
            table_name: <?= json_encode(service('request')->getLocale() === 'th' ? 'โต๊ะ' : 'Table') ?>,
            station_name: <?= json_encode(service('request')->getLocale() === 'th' ? 'สถานีครัว' : 'Kitchen station') ?>,
            source_screen: <?= json_encode(service('request')->getLocale() === 'th' ? 'หน้าที่ทำรายการ' : 'Source screen') ?>,
            permission_key: <?= json_encode(service('request')->getLocale() === 'th' ? 'สิทธิ์ที่ใช้' : 'Permission used') ?>,
            actor_role_name: <?= json_encode(service('request')->getLocale() === 'th' ? 'บทบาทผู้ใช้' : 'Actor role') ?>
        };

        return map[key] || key.replace(/_/g, ' ');
    }

    function formatTimelineStatus(value) {
        const status = String(value || '').toLowerCase().trim();
        if (status === 'served') return TXT.statusServed;
        if (status === 'ready') return TXT.statusReady;
        if (status === 'preparing' || status === 'cooking' || status === 'processing') return TXT.statusPreparing;
        if (status === 'sent' || status === 'submitted' || status === 'sent_to_kitchen') return TXT.statusSent;
        return String(value || '');
    }

    function formatTimelineSourceScreen(value) {
        const screen = String(value || '').toLowerCase().trim();
        if (screen === 'kitchen_board') return TXT.sourceScreenKitchenBoard;
        if (screen === 'pos_table') return TXT.sourceScreenPosTable;
        return String(value || '');
    }

    function timelineVisibleMeta(actionKey, meta) {
        const key = String(actionKey || '').toLowerCase();
        const source = meta && typeof meta === 'object' ? meta : {};
        const rows = [];
        const pushRow = function (label, value) {
            if (value === null || value === undefined || value === '') {
                return;
            }
            rows.push({ label: label, value: value });
        };

        if (key === 'pos.item_served') {
            pushRow(beautifyTimelineMetaLabel('table_name'), source.table_name || '');
            pushRow(beautifyTimelineMetaLabel('station_name'), source.station_name || '');
            pushRow(beautifyTimelineMetaLabel('served_at'), source.served_at ? formatDateTime(String(source.served_at), { mode: 'compact' }) : '');
            pushRow(beautifyTimelineMetaLabel('source_screen'), source.source_screen ? formatTimelineSourceScreen(source.source_screen) : '');
            return rows;
        }

        if (key === 'pos.send_kitchen') {
            pushRow(beautifyTimelineMetaLabel('ticket_no'), source.ticket_no || '');
            pushRow(beautifyTimelineMetaLabel('batch_no'), source.batch_no ? '#' + String(source.batch_no) : '');
            pushRow(beautifyTimelineMetaLabel('item_count'), source.item_count ? String(source.item_count) : '');
            pushRow(beautifyTimelineMetaLabel('station_name'), source.station_name || '');
            pushRow(beautifyTimelineMetaLabel('table_name'), source.table_name || '');
            return rows;
        }

        pushRow(beautifyTimelineMetaLabel('ticket_no'), source.ticket_no || '');
        pushRow(beautifyTimelineMetaLabel('batch_no'), source.batch_no ? '#' + String(source.batch_no) : '');
        pushRow(beautifyTimelineMetaLabel('table_name'), source.table_name || '');
        pushRow(beautifyTimelineMetaLabel('source_screen'), source.source_screen ? formatTimelineSourceScreen(source.source_screen) : '');
        return rows;
    }


    function timelineHighlightChips(actionKey, meta) {
        const key = String(actionKey || '').toLowerCase();
        const source = meta && typeof meta === 'object' ? meta : {};
        const chips = [];
        if (key === 'pos.item_served') {
            chips.push(TXT.statusServed);
            if (source.qty) chips.push(String(source.qty) + '×');
            if (source.station_name) chips.push(String(source.station_name));
        }
        if (key === 'pos.send_kitchen') {
            chips.push(TXT.statusSent);
            if (source.item_count) chips.push(String(source.item_count) + ' <?= service('request')->getLocale() === 'th' ? 'รายการ' : 'items' ?>');
            if (source.batch_no) chips.push('#' + String(source.batch_no));
        }
        return chips.slice(0, 3);
    }


    function buildTimelineLead(actionKey, meta) {
        const key = String(actionKey || '').toLowerCase();
        const source = meta && typeof meta === 'object' ? meta : {};
        if (key === 'pos.item_served') {
            if (source.product_name) {
                return '<?= service('request')->getLocale() === 'th' ? 'เสิร์ฟแล้ว' : 'Served' ?> • ' + String(source.product_name) + (source.qty ? ' × ' + String(source.qty) : '');
            }
            return TXT.statusServed;
        }
        if (key === 'pos.send_kitchen') {
            if (source.item_count) {
                return '<?= service('request')->getLocale() === 'th' ? 'ส่งเข้าครัว' : 'Sent to kitchen' ?> • ' + String(source.item_count) + ' <?= service('request')->getLocale() === 'th' ? 'รายการ' : 'items' ?>';
            }
            return TXT.statusSent;
        }
        return '';
    }


    function renderTimelineSummary(rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            return '';
        }
        let servedCount = 0;
        let kitchenCount = 0;
        let latest = '';
        rows.forEach(function (row) {
            const key = String(row.action_key || '').toLowerCase();
            if (key === 'pos.item_served') servedCount += 1;
            if (key === 'pos.send_kitchen') kitchenCount += 1;
            if (!latest && row.created_at) latest = formatDateTime(row.created_at, { mode: 'compact' });
        });
        return '<div class="pos-timeline-summary-card"><div class="pos-timeline-summary-label">' + escapeHtml(TXT.timelineSummaryServed) + '</div><div class="pos-timeline-summary-value">' + escapeHtml(String(servedCount)) + ' <?= service('request')->getLocale() === 'th' ? 'รายการ' : 'items' ?></div></div>'
            + '<div class="pos-timeline-summary-card"><div class="pos-timeline-summary-label">' + escapeHtml(TXT.timelineSummaryKitchen) + '</div><div class="pos-timeline-summary-value">' + escapeHtml(String(kitchenCount)) + ' <?= service('request')->getLocale() === 'th' ? 'ครั้ง' : 'times' ?></div></div>'
            + '<div class="pos-timeline-summary-card"><div class="pos-timeline-summary-label">' + escapeHtml(TXT.timelineSummaryLatest) + '</div><div class="pos-timeline-summary-value">' + escapeHtml(latest || '-') + '</div></div>';
    }


    function renderTimelineRows(rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            return '<div class="pos-timeline-empty">' + escapeHtml(TXT.timelineEmpty) + '</div>';
        }

        return rows.map(function (row) {
            const action = timelineActionMeta(row.action_key, row.action_label);
            const metaRows = timelineVisibleMeta(row.action_key, row.meta || {});
            const actorName = row.actor_name || row.username || row.full_name || '-';
            const highlights = timelineHighlightChips(row.action_key, row.meta || {});
            const leadText = buildTimelineLead(row.action_key, row.meta || {});

            const bodyHtml = (leadText
                ? '<div class="pos-timeline-lead">' + escapeHtml(leadText) + '</div>'
                : '')
                + (highlights.length
                ? '<div class="pos-timeline-highlight">' + highlights.map(function (item) {
                    return '<span class="pos-timeline-highlight-chip">' + escapeHtml(item) + '</span>';
                }).join('') + '</div>'
                : '')
                + (metaRows.length
                ? '<div class="pos-timeline-meta-grid">' + metaRows.map(function (metaRow) {
                    return '<div class="pos-timeline-row">'
                        + '<div class="pos-timeline-row-label">' + escapeHtml(metaRow.label) + '</div>'
                        + '<div class="pos-timeline-row-value">' + escapeHtml(String(metaRow.value)) + '</div>'
                        + '</div>';
                }).join('') + '</div>'
                : '<div class="pos-timeline-row">'
                    + '<div class="pos-timeline-row-label">' + escapeHtml(TXT.noteLabel) + '</div>'
                    + '<div class="pos-timeline-row-value">-</div>'
                    + '</div>');

            return '<div class="pos-timeline-card">'
                + '<div class="pos-timeline-card-head">'
                + '  <div class="pos-timeline-card-title-wrap">'
                + '      <div class="pos-timeline-card-title">' + escapeHtml(action.title) + '</div>'
                + '      <div class="pos-timeline-card-time">' + escapeHtml(formatDateTime(row.created_at || '', { mode: 'compact' })) + '</div>'
                + '  </div>'
                + '  <div class="pos-timeline-card-badges">'
                + '      <span class="pos-timeline-badge actor">' + escapeHtml(actorName) + '</span>'
                + '      <span class="pos-timeline-badge type-' + escapeHtml(action.type) + '">' + escapeHtml(timelineTypeLabel(action.type)) + '</span>'
                + '  </div>'
                + '</div>'
                + '<div class="pos-timeline-card-body">' + bodyHtml + '</div>'
                + '</div>';
        }).join('');
    }

    function openBillTimeline() {
        if (!CURRENT_ORDER_ID) {
            notify(TXT.noBillYet, 'warning');
            return;
        }

        const body = document.getElementById('billTimelineBody');
        const meta = document.getElementById('billTimelineMeta');
        const summary = document.getElementById('billTimelineSummary');
        if (!body || !meta || !billTimelineModal) {
            return;
        }

        meta.textContent = ($('#tableName').text() || '').trim() + ' • ' + ($('#orderNoLabel').text() || '-').trim();
        body.innerHTML = '<div class="pos-timeline-empty">Loading...</div>';
        billTimelineModal.show();

        $.get(<?= json_encode(site_url('pos/table-timeline')) ?> + '/' + TABLE_ID)
            .done(function (res) {
                if (!res || res.status !== 'success') {
                    body.innerHTML = '<div class="pos-timeline-empty">' + escapeHtml((res && res.message) ? res.message : TXT.loadTimelineFailed) + '</div>';
                    return;
                }

                if (res.order && res.order.order_number) {
                    meta.textContent = ($('#tableName').text() || '').trim() + ' • ' + res.order.order_number;
                }

                if (summary) {
                    summary.innerHTML = renderTimelineSummary(res.rows || []);
                }
                body.innerHTML = renderTimelineRows(res.rows || []);
            })
            .fail(function (xhr) {
                console.error('timeline error:', xhr.responseText);
                body.innerHTML = '<div class="pos-timeline-empty">' + escapeHtml(TXT.loadTimelineFailed) + '</div>';
            });
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
        if (actionKey === 'void_item') {
            return TXT.managerOverrideActionVoidItem || TXT.voidItem || TXT.managerOverrideRequired;
        }
        return TXT.managerOverrideRequired;
    }


    function normalizeSearchText(text) {
        return String(text || '').toLowerCase().trim();
    }

    function setBillDrawerOpen(isOpen) {
        $('body').toggleClass('pos-bill-drawer-open', !!isOpen);
    }

    function updateMobileBillSummary(itemCount, totalAmount, orderNumber) {
        const safeOrderNumber = $.trim(String(orderNumber || $('#orderNoLabel').text() || '-'));
        const metaText = safeOrderNumber && safeOrderNumber !== '-' ? safeOrderNumber : TXT.noBillYet;

        $('#mobileBillSummaryMeta').text(metaText);
        $('#mobileBillSummaryTotal').text(money(totalAmount || 0));
    }

    function buildRecentProductItems(items) {
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

        return recent;
    }

    function renderRecentItemsModal() {
        if (!recentItemsModalEl) {
            return;
        }

        if (!RECENT_PRODUCT_ITEMS.length) {
            $('#recentItemsModalBody').html('<div class="text-muted">' + escapeHtml(TXT.noData) + '</div>');
            return;
        }

        const html = RECENT_PRODUCT_ITEMS.map(function (item) {
            return `
                <button
                    type="button"
                    class="recent-items-modal-card btn-open-recent-modal-item"
                    data-id="${item.product_id}"
                    data-name="${escapeHtml(item.product_name)}"
                    data-price="${item.price}"
                >
                    <div>
                        <div class="recent-items-modal-card-title">${escapeHtml(item.product_name)}</div>
                        <div class="recent-items-modal-card-meta">${money(item.price || 0)}</div>
                    </div>
                    <span class="btn btn-outline-primary btn-sm rounded-pill">${escapeHtml(TXT.addToBillAction)}</span>
                </button>
            `;
        }).join('');

        $('#recentItemsModalBody').html(html);
    }

    function renderRecentProductToolbar(items) {
        RECENT_PRODUCT_ITEMS = buildRecentProductItems(items);

        const visible = RECENT_PRODUCT_ITEMS.slice(0, 2);
        const extraCount = Math.max(RECENT_PRODUCT_ITEMS.length - visible.length, 0);
        let html = '';

        visible.forEach(function (item) {
            html += '<button type="button" class="btn pos-inline-chip btn-inline-recent" data-id="' + item.product_id + '" data-name="' + escapeHtml(item.product_name) + '" data-price="' + item.price + '">' + escapeHtml(item.product_name) + '</button>';
        });

        if (extraCount > 0) {
            html += '<button type="button" class="btn pos-inline-chip pos-inline-chip-more" id="btnRecentItemsMore">' + escapeHtml(TXT.viewAll) + ' (' + extraCount + ')</button>';
        }

        $('#recentProductChips').html(html);
        $('#recentProductToolbar').toggleClass('show', RECENT_PRODUCT_ITEMS.length > 0);
        renderRecentItemsModal();
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


    function getSplitBillBadge(order) {
        if (!order || !order.bill_type) {
            return '';
        }

        const billType = String(order.bill_type || 'normal').toLowerCase();
        if (billType === 'split_parent') {
            const label = (Number(order.split_root_order_id || 0) === Number(order.id || 0)) ? TXT.splitBillRoot : TXT.splitBillParent;
            return `<span class="split-bill-group-chip bg-info-subtle text-info-emphasis">${escapeHtml(label)}</span>`;
        }

        if (billType === 'split_child') {
            const splitNo = Number(order.split_no || 0);
            const label = splitNo > 0 ? `${TXT.splitBillChild} #${splitNo}` : TXT.splitBillChild;
            return `<span class="split-bill-group-chip bg-warning-subtle text-warning-emphasis">${escapeHtml(label)}</span>`;
        }

        return '';
    }

    function isSplitBillableItem(item) {
        const status = normalizeItemStatus(item && item.status ? item.status : 'pending');
        const requestState = getRequestStateMeta(item || {});
        const cancelledAt = item && item.cancelled_at ? String(item.cancelled_at).trim() : '';

        if (status === 'pending' || status === 'open' || status === 'new') {
            return false;
        }
        if (status === 'cancel' || status === 'cancelled' || status === 'canceled') {
            return false;
        }
        if (cancelledAt !== '') {
            return false;
        }
        if (requestState && requestState.key === 'approved') {
            return false;
        }
        return true;
    }

    function getSplittableItems() {
        return (Array.isArray(CURRENT_ORDER_ITEMS) ? CURRENT_ORDER_ITEMS : []).filter(function (item) {
            const status = normalizeItemStatus(item.status || 'pending');
            if (status === 'cancel' || status === 'cancelled' || status === 'canceled') {
                return false;
            }
            return Number(item.qty || 0) > 0;
        });
    }

    function syncCurrentTableName() {
        const titleText = $('.pos-touch-title').first().text().trim();
        const fallbackName = String(TABLE_NAME_CONST || '').trim();
        let parsedName = fallbackName;

        if (titleText) {
            const match = titleText.match(/(?:โต๊ะ|Table)\s+(.+)$/i);
            if (match && match[1]) {
                parsedName = $.trim(match[1]);
            }
        }

        CURRENT_TABLE_NAME = parsedName || fallbackName || '-';
        window.CURRENT_TABLE_NAME = CURRENT_TABLE_NAME;
    }

    function buildSplitBillWorkspaceState() {
        const items = getSplittableItems().map(function (item) {
            const sourceQty = Math.max(0, Number(item.qty || 0));
            const unitPrice = Number(item.price || item.unit_price || item.product_price || 0);
            const isBillable = isSplitBillableItem(item);
            return {
                id: Number(item.id || 0),
                product_name: String(item.product_name || item.name || ''),
                option_summary: String(item.option_summary || item.option_text || item.option_name || ''),
                note: String(item.note || item.remark || ''),
                status: String(item.status || 'pending'),
                source_qty: sourceQty,
                moved_qty: 0,
                remaining_qty: sourceQty,
                price: unitPrice,
                effective_price: isBillable ? unitPrice : 0,
                is_billable: isBillable ? 1 : 0
            };
        });

        return {
            table_name: CURRENT_TABLE_NAME || '',
            items: items
        };
    }

    function resetSplitBillModal() {
        syncCurrentTableName();
        SPLIT_PREVIEW_CACHE = null;
        SPLIT_BILL_WORKSPACE_STATE = buildSplitBillWorkspaceState();
        $('#splitBillReason').val('');
        $('#splitBillPreviewHint').text(TXT.splitBillTapToMoveHint || TXT.splitBillSelectItemsHint);
        $('#splitBillPreviewBox').html('<div class="text-muted">' + escapeHtml(TXT.splitBillNoPreview) + '</div>');
        $('#splitBillSummaryTable').text(CURRENT_TABLE_NAME || '-');
        $('#splitBillSummaryItems').text('0');
        $('#splitBillSummaryQty').text('0');
        $('#splitBillSummaryTotal').text(money(0));
        $('#splitBillFooterTable').text((TXT.splitBillTableLabel || 'Table') + ': ' + (CURRENT_TABLE_NAME || '-'));
        $('#splitBillFooterItems').text((TXT.splitBillSummaryItems || 'Items') + ': 0');
        $('#splitBillFooterQty').text((TXT.splitBillSummaryQty || 'Qty') + ': 0');
        $('#splitBillFooterTotal').text((TXT.splitBillPreviewChildTotal || 'New bill total') + ': ' + money(0));
        $('#splitBillTargetHeroTotal').text(money(0));
        $('#splitBillMobileTotal').text(money(0));
        $('#splitBillMobileQty').text('0');
        $('#splitBillTargetSummaryItems').text((TXT.splitBillSummaryItems || 'Items') + ': 0');
        $('#splitBillTargetSummaryQty').text((TXT.splitBillSummaryQty || 'Qty') + ': 0');
        $('#btnConfirmSplitBill').prop('disabled', true);
    }

    function findSplitBillWorkspaceItem(orderItemId) {
        if (!SPLIT_BILL_WORKSPACE_STATE || !Array.isArray(SPLIT_BILL_WORKSPACE_STATE.items)) {
            return null;
        }
        return SPLIT_BILL_WORKSPACE_STATE.items.find(function (item) {
            return Number(item.id || 0) === Number(orderItemId || 0);
        }) || null;
    }

    function setWorkspaceItemMovedQty(orderItemId, nextQty) {
        const item = findSplitBillWorkspaceItem(orderItemId);
        if (!item) {
            return;
        }
        const max = Math.max(0, Number(item.source_qty || 0));
        let qty = Number(nextQty || 0);
        if (qty < 0) qty = 0;
        if (qty > max) qty = max;
        item.moved_qty = qty;
        item.remaining_qty = max - qty;
    }

    function moveSplitBillItem(orderItemId, deltaQty) {
        const item = findSplitBillWorkspaceItem(orderItemId);
        if (!item) {
            return;
        }
        setWorkspaceItemMovedQty(orderItemId, Number(item.moved_qty || 0) + Number(deltaQty || 0));
        renderSplitBillWorkspace();
    }

    function moveAllSplitBillItem(orderItemId) {
        const item = findSplitBillWorkspaceItem(orderItemId);
        if (!item) {
            return;
        }
        setWorkspaceItemMovedQty(orderItemId, Number(item.source_qty || 0));
        renderSplitBillWorkspace();
    }

    function returnSplitBillItem(orderItemId, deltaQty) {
        const item = findSplitBillWorkspaceItem(orderItemId);
        if (!item) {
            return;
        }
        setWorkspaceItemMovedQty(orderItemId, Number(item.moved_qty || 0) - Number(deltaQty || 0));
        renderSplitBillWorkspace();
    }

    function clearSplitBillWorkspace() {
        if (!SPLIT_BILL_WORKSPACE_STATE || !Array.isArray(SPLIT_BILL_WORKSPACE_STATE.items)) {
            return;
        }
        SPLIT_BILL_WORKSPACE_STATE.items.forEach(function (item) {
            item.moved_qty = 0;
            item.remaining_qty = Number(item.source_qty || 0);
        });
        SPLIT_PREVIEW_CACHE = null;
        renderSplitBillWorkspace();
    }

    function collectSplitBillItems() {
        const stateItems = SPLIT_BILL_WORKSPACE_STATE && Array.isArray(SPLIT_BILL_WORKSPACE_STATE.items)
            ? SPLIT_BILL_WORKSPACE_STATE.items
            : [];

        return stateItems.filter(function (item) {
            return Number(item.moved_qty || 0) > 0;
        }).map(function (item) {
            return {
                order_item_id: Number(item.id || 0),
                split_qty: Number(item.moved_qty || 0)
            };
        });
    }

    function getSplitBillWorkspaceSummary() {
        const stateItems = SPLIT_BILL_WORKSPACE_STATE && Array.isArray(SPLIT_BILL_WORKSPACE_STATE.items)
            ? SPLIT_BILL_WORKSPACE_STATE.items
            : [];

        return stateItems.reduce(function (carry, item) {
            const movedQty = Number(item.moved_qty || 0);
            const sourceQty = Number(item.source_qty || 0);
            const effectivePrice = Number(item.effective_price != null ? item.effective_price : (item.price || 0));
            const isBillable = Number(item.is_billable || 0) === 1;

            if (movedQty > 0) {
                carry.selected_items += 1;
                carry.selected_qty += movedQty;
                if (isBillable) {
                    carry.child_total += movedQty * effectivePrice;
                }
            }

            if (isBillable) {
                carry.parent_total += Math.max(0, sourceQty - movedQty) * effectivePrice;
            }

            return carry;
        }, {
            selected_items: 0,
            selected_qty: 0,
            child_total: 0,
            parent_total: 0
        });
    }

    function renderSplitBillSourcePane() {
        const items = SPLIT_BILL_WORKSPACE_STATE && Array.isArray(SPLIT_BILL_WORKSPACE_STATE.items)
            ? SPLIT_BILL_WORKSPACE_STATE.items
            : [];

        if (!items.length) {
            $('#splitBillItemsBox').html('<div class="split-bill-empty-state">' + escapeHtml(TXT.splitBillNoEligibleItems) + '</div>');
            return;
        }

        const html = items.map(function (item) {
            const sourceQty = Number(item.source_qty || 0);
            const movedQty = Number(item.moved_qty || 0);
            const remainingQty = Number(item.remaining_qty || 0);
            const canMove = remainingQty > 0;
            const isActive = movedQty > 0;
            const isBillable = Number(item.is_billable || 0) === 1;
            const statusLabel = movedQty > 0
                ? (remainingQty <= 0
                    ? (TXT.splitBillMovedFully || 'Moved fully')
                    : ((TXT.splitBillMovedQty || 'Moved') + ': ' + movedQty))
                : (TXT.splitBillRemainingQty + ': ' + remainingQty);

            return `
                <div class="split-bill-card split-bill-source-card ${isActive ? 'is-active' : ''}" data-split-item-id="${item.id}" data-order-item-id="${item.id}">
                    <div class="split-bill-source-card-main">
                        <div>
                            <div class="split-bill-card-name">${escapeHtml(item.product_name || '')}</div>
                            ${item.option_summary ? `<div class="split-bill-card-option">${escapeHtml(item.option_summary)}</div>` : ''}
                            ${item.note ? `<div class="split-bill-card-note">${escapeHtml(item.note)}</div>` : ''}
                            ${!isBillable ? `<div class="split-bill-state-note">${escapeHtml(TXT.splitBillNotBillable || 'Not billable')}</div>` : ''}
                        </div>
                        <span class="split-bill-main-tap ${canMove ? '' : 'is-disabled'}">${escapeHtml(statusLabel)}</span>
                    </div>
                    <div class="split-bill-card-meta">
                        <span class="split-bill-meta-chip">${escapeHtml(TXT.splitBillSourceQty)}: <strong>${escapeHtml(String(sourceQty))}</strong></span>
                        <span class="split-bill-meta-chip">${escapeHtml(TXT.splitBillRemainingQty)}: <strong>${escapeHtml(String(remainingQty))}</strong></span>
                        <span class="split-bill-meta-chip">${escapeHtml(TXT.splitBillMovedQty || 'Moved')}: <strong>${escapeHtml(String(movedQty))}</strong></span>
                        <span class="split-bill-meta-chip">${money(item.effective_price != null ? item.effective_price : (item.price || 0))}</span>
                    </div>
                    <div class="split-bill-action-row">
                        <div class="split-bill-quick-actions">
                            <button type="button" class="btn btn-outline-info btn-sm split-bill-move-one" data-order-item-id="${item.id}" ${canMove ? '' : 'disabled'}>${escapeHtml(TXT.splitBillMoveOne || 'Move 1')}</button>
                            <button type="button" class="btn btn-outline-primary btn-sm split-bill-move-all" data-order-item-id="${item.id}" ${canMove ? '' : 'disabled'}>${escapeHtml(TXT.splitBillMoveAll || 'Move all')}</button>
                        </div>
                        <div class="split-bill-qty-stepper">
                            <button type="button" class="btn btn-outline-secondary split-bill-workspace-step" data-order-item-id="${item.id}" data-type="minus" ${movedQty > 0 ? '' : 'disabled'}>-</button>
                            <span class="split-bill-qty-display">${escapeHtml(String(movedQty))}</span>
                            <button type="button" class="btn btn-outline-secondary split-bill-workspace-step" data-order-item-id="${item.id}" data-type="plus" ${canMove ? '' : 'disabled'}>+</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        $('#splitBillItemsBox').html(html);
    }

    function renderSplitBillTargetPane() {
        const stateItems = SPLIT_BILL_WORKSPACE_STATE && Array.isArray(SPLIT_BILL_WORKSPACE_STATE.items)
            ? SPLIT_BILL_WORKSPACE_STATE.items
            : [];
        const movedItems = stateItems.filter(function (item) {
            return Number(item.moved_qty || 0) > 0;
        });

        if (!movedItems.length) {
            $('#splitBillTargetBox').html('<div class="split-bill-empty-state">' + escapeHtml(TXT.splitBillEmptyNewBill || TXT.splitBillNoPreview) + '</div>');
            return;
        }

        const html = movedItems.map(function (item) {
            const movedQty = Number(item.moved_qty || 0);
            const effectivePrice = Number(item.effective_price != null ? item.effective_price : (item.price || 0));
            const isBillable = Number(item.is_billable || 0) === 1;
            const lineTotal = movedQty * effectivePrice;
            return `
                <div class="split-bill-card is-target" data-target-item-id="${item.id}">
                    <div class="split-bill-card-head">
                        <div>
                            <div class="split-bill-card-name">${escapeHtml(item.product_name || '')}</div>
                            ${item.option_summary ? `<div class="split-bill-card-option">${escapeHtml(item.option_summary)}</div>` : ''}
                            ${item.note ? `<div class="split-bill-card-note">${escapeHtml(item.note)}</div>` : ''}
                            ${!isBillable ? `<div class="split-bill-state-note">${escapeHtml(TXT.splitBillNotBillable || 'Not billable')}</div>` : ''}
                        </div>
                        <span class="split-bill-meta-chip">${escapeHtml(TXT.splitBillQty)} ${escapeHtml(String(movedQty))}</span>
                    </div>
                    <div class="split-bill-card-meta">
                        <span class="split-bill-meta-chip">${money(effectivePrice)} × ${escapeHtml(String(movedQty))}</span>
                        <span class="split-bill-meta-chip">${money(lineTotal)}</span>
                    </div>
                    <div class="split-bill-card-actions">
                        <div class="split-bill-quick-actions">
                            <button type="button" class="btn btn-outline-secondary btn-sm split-bill-return-one" data-order-item-id="${item.id}">${escapeHtml(TXT.splitBillBackOne || '-1')}</button>
                            <button type="button" class="btn btn-outline-danger btn-sm split-bill-return-all" data-order-item-id="${item.id}">${escapeHtml(TXT.splitBillMoveBack || 'Move back')}</button>
                        </div>
                        <div class="split-bill-qty-stepper">
                            <button type="button" class="btn btn-outline-secondary split-bill-target-step" data-order-item-id="${item.id}" data-type="minus">-</button>
                            <span class="split-bill-qty-display">${escapeHtml(String(movedQty))}</span>
                            <button type="button" class="btn btn-outline-secondary split-bill-target-step" data-order-item-id="${item.id}" data-type="plus" ${(Number(item.remaining_qty || 0) > 0) ? '' : 'disabled'}>+</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        $('#splitBillTargetBox').html(html);
    }

    function renderSplitBillLiveSummary() {
        const summary = getSplitBillWorkspaceSummary();
        $('#splitBillSummaryTable').text(CURRENT_TABLE_NAME || '-');
        $('#splitBillSummaryItems').text(String(summary.selected_items || 0));
        $('#splitBillSummaryQty').text(String(summary.selected_qty || 0));
        $('#splitBillSummaryTotal').text(money(summary.child_total || 0));
        $('#splitBillFooterTable').text((TXT.splitBillTableLabel || 'Table') + ': ' + (CURRENT_TABLE_NAME || '-'));
        $('#splitBillFooterItems').text((TXT.splitBillSummaryItems || 'Items') + ': ' + String(summary.selected_items || 0));
        $('#splitBillFooterQty').text((TXT.splitBillSummaryQty || 'Qty') + ': ' + String(summary.selected_qty || 0));
        $('#splitBillFooterTotal').text((TXT.splitBillPreviewChildTotal || 'New bill total') + ': ' + money(summary.child_total || 0));
        $('#splitBillTargetHeroTotal').text(money(summary.child_total || 0));
        $('#splitBillTargetSummaryItems').text((TXT.splitBillSummaryItems || 'Items') + ': ' + String(summary.selected_items || 0));
        $('#splitBillTargetSummaryQty').text((TXT.splitBillSummaryQty || 'Qty') + ': ' + String(summary.selected_qty || 0));
        $('#splitBillMobileTotal').text(money(summary.child_total || 0));
        $('#splitBillMobileQty').text(String(summary.selected_qty || 0));

        if (summary.selected_qty > 0) {
            $('#splitBillPreviewHint').text(TXT.splitBillTargetReady || TXT.splitBillPreviewReady);
            $('#splitBillPreviewBox').html(`
                <div class="split-bill-preview-summary">
                    <div class="split-bill-summary-card">
                        <div class="split-bill-summary-label">${escapeHtml(TXT.splitBillSummaryItems)}</div>
                        <div class="split-bill-summary-value">${escapeHtml(String(summary.selected_items || 0))}</div>
                    </div>
                    <div class="split-bill-summary-card">
                        <div class="split-bill-summary-label">${escapeHtml(TXT.splitBillSummaryQty)}</div>
                        <div class="split-bill-summary-value">${escapeHtml(String(summary.selected_qty || 0))}</div>
                    </div>
                    <div class="split-bill-summary-card is-accent">
                        <div class="split-bill-summary-label">${escapeHtml(TXT.splitBillPreviewChildTotal)}</div>
                        <div class="split-bill-summary-value">${money(summary.child_total || 0)}</div>
                    </div>
                    <div class="split-bill-summary-card">
                        <div class="split-bill-summary-label">${escapeHtml(TXT.splitBillPreviewParentTotal)}</div>
                        <div class="split-bill-summary-value">${money(summary.parent_total || 0)}</div>
                    </div>
                </div>
            `);
        } else {
            $('#splitBillPreviewHint').text(TXT.splitBillTapToMoveHint || TXT.splitBillSelectItemsHint);
            $('#splitBillPreviewBox').html('<div class="text-muted">' + escapeHtml(TXT.splitBillNoPreview) + '</div>');
        }

        $('#btnConfirmSplitBill').prop('disabled', !(summary.selected_qty > 0));
    }

    function renderSplitBillWorkspace() {
        renderSplitBillSourcePane();
        renderSplitBillTargetPane();
        renderSplitBillLiveSummary();
    }

    function renderSplitBillPreview(payload) {
        const data = payload && payload.data ? payload.data : null;
        if (!data) {
            renderSplitBillLiveSummary();
            return;
        }

        const summary = getSplitBillWorkspaceSummary();
        const childItems = Array.isArray(data.child_items) ? data.child_items : [];
        const childQty = childItems.reduce(function (sum, item) { return sum + Number(item.split_qty || 0); }, 0);
        $('#splitBillPreviewHint').text(payload.message || TXT.splitBillPreviewReady);
        $('#splitBillPreviewBox').html(`
            <div class="split-bill-preview-summary">
                <div class="split-bill-summary-card">
                    <div class="split-bill-summary-label">${escapeHtml(TXT.splitBillSummaryItems)}</div>
                    <div class="split-bill-summary-value">${escapeHtml(String(summary.selected_items || childItems.length || 0))}</div>
                </div>
                <div class="split-bill-summary-card">
                    <div class="split-bill-summary-label">${escapeHtml(TXT.splitBillSummaryQty)}</div>
                    <div class="split-bill-summary-value">${escapeHtml(String(summary.selected_qty || childQty || 0))}</div>
                </div>
                <div class="split-bill-summary-card is-accent">
                    <div class="split-bill-summary-label">${escapeHtml(TXT.splitBillPreviewChildTotal)}</div>
                    <div class="split-bill-summary-value">${money(summary.child_total || 0)}</div>
                </div>
                <div class="split-bill-summary-card">
                    <div class="split-bill-summary-label">${escapeHtml(TXT.splitBillPreviewParentTotal)}</div>
                    <div class="split-bill-summary-value">${money(summary.parent_total || 0)}</div>
                </div>
            </div>
        `);
        $('#btnConfirmSplitBill').prop('disabled', !(summary.selected_qty > 0));
    }

    function requestSplitBillPreview() {
        const items = collectSplitBillItems();
        if (!CURRENT_ORDER_ID) {
            notify(TXT.noBillYet);
            return;
        }
        if (!items.length) {
            SPLIT_PREVIEW_CACHE = null;
            renderSplitBillPreview(null);
            notify(TXT.splitBillSelectAtLeastOne);
            return;
        }

        $('#splitBillPreviewHint').text(TXT.splitBillLoading);
        $('#splitBillPreviewBox').html('<div class="text-muted">' + escapeHtml(TXT.splitBillLoading) + '</div>');
        $('#btnConfirmSplitBill').prop('disabled', true);

        $.post("<?= site_url('pos/split-bill/preview') ?>", {
            order_id: CURRENT_ORDER_ID,
            items: items,
            reason: $.trim($('#splitBillReason').val())
        })
        .done(function (res) {
            if (!res || res.status !== 'success') {
                SPLIT_PREVIEW_CACHE = null;
                renderSplitBillPreview(null);
                notify((res && res.message) ? res.message : TXT.splitBillFailed);
                return;
            }
            SPLIT_PREVIEW_CACHE = res;
            renderSplitBillPreview(res);
        })
        .fail(function (xhr) {
            console.error('splitBillPreview error:', xhr.responseText);
            SPLIT_PREVIEW_CACHE = null;
            renderSplitBillPreview(null);
            notify(TXT.splitBillFailed);
        });
    }

    function openSplitBillModal() {
        if (!CURRENT_ORDER_ID) {
            notify(TXT.noBillYet);
            return;
        }
        if (!TABLE_PERMISSIONS.splitBill) {
            notify(TXT.managerOverrideRequired || TXT.splitBillFailed);
            return;
        }
        resetSplitBillModal();
        renderSplitBillWorkspace();
        const orderNo = CURRENT_ORDER_DATA && CURRENT_ORDER_DATA.order_number ? CURRENT_ORDER_DATA.order_number : '-';
        $('#splitBillModalMeta').text('#' + orderNo);
        if (splitBillModal) {
            splitBillModal.show();
        }
    }

    function renderOrderMetaIndicators(order = null) {
        const indicators = [];
        const splitBadge = getSplitBillBadge(order);
        if (splitBadge) {
            indicators.push(splitBadge);
        }
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
			$('#btnSplitBill').prop('disabled', true);
			$('#btnBillTimelineQuick').prop('disabled', true);
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
			$('#btnSplitBill').prop('disabled', !(TABLE_PERMISSIONS.splitBill && (CURRENT_ORDER_STATUS === 'open' || CURRENT_ORDER_STATUS === 'billing')));
			$('#btnBillTimelineQuick').prop('disabled', !CURRENT_ORDER_ID);
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

    function shouldHideCanceledItemFromVisibleHistory(item) {
        const normalizedStatus = normalizeItemStatus(item && item.status ? item.status : 'pending');
        const requestState = getRequestStateMeta(item);
        const isCanceled = normalizedStatus === 'cancel' || normalizedStatus === 'cancelled' || normalizedStatus === 'canceled' || (requestState && requestState.key === 'approved');

        if (!isCanceled) {
            return false;
        }

        const previousStatus = normalizeItemStatus((item && item.cancel_request_prev_status) ? item.cancel_request_prev_status : '');
        return previousStatus === 'pending' || previousStatus === 'open' || previousStatus === 'new';
    }


    function splitGroupRoleLabel(role, splitNo) {
        const normalizedRole = String(role || '').toLowerCase().trim();
        const splitNumber = parseInt(splitNo || 0, 10);

        if (normalizedRole === 'root') {
            return TXT.splitBillRoleRoot;
        }

        if (normalizedRole === 'parent') {
            return TXT.splitBillRoleParent;
        }

        if (normalizedRole === 'child') {
            if (splitNumber > 0) {
                return TXT.splitBillRoleChild + ' #' + splitNumber;
            }

            return TXT.splitBillRoleChild;
        }

        return TXT.currentBill;
    }

    function renderSplitGroupPanel(group, selectedOrderId) {
        const $panel = $('#splitGroupPanel');
        const normalizedGroup = Array.isArray(group) ? group : [];
        CURRENT_SPLIT_GROUP = normalizedGroup;

        if (!$panel.length) {
            return;
        }

        if (normalizedGroup.length <= 1) {
            $panel.addClass('is-hidden').empty();
            return;
        }

        const paidCount = normalizedGroup.filter(function (bill) {
            return !!bill.is_paid;
        }).length;

        const remainingTotal = normalizedGroup.reduce(function (sum, bill) {
            return sum + (bill.can_pay ? Number(bill.total_price || 0) : 0);
        }, 0);

        let cardsHtml = '';

        normalizedGroup.forEach(function (bill) {
            const billId = Number(bill.id || 0);
            const isSelected = Number(selectedOrderId || 0) === billId;
            const canPay = !!bill.can_pay;
            const statusLabel = statusText(bill.status || 'open');
            const statusClass = statusBadgeClass(bill.status || 'open');
            const roleLabel = splitGroupRoleLabel(bill.role || '', bill.split_no || 0);
            const previewItems = Array.isArray(bill.preview_items) ? bill.preview_items : [];
            const actionSecondaryLabel = isSelected ? TXT.splitGroupSelected : TXT.splitGroupSelectBill;

            const previewHtml = previewItems.length
                ? previewItems.map(function (item) {
                    const itemName = escapeHtml(item.name || '-');
                    const itemQty = Number(item.qty || 0);
                    const itemNote = String(item.note || '').trim();

                    return `
                        <div class="pos-split-group-preview-item">
                            <div class="min-w-0">
                                <div class="pos-split-group-preview-name">${itemName}</div>
                                ${itemNote !== '' ? `<div class="pos-split-group-preview-note">${escapeHtml(itemNote)}</div>` : ''}
                            </div>
                            <div class="pos-split-group-preview-qty">x ${itemQty}</div>
                        </div>
                    `;
                }).join('')
                : `<div class="pos-split-group-empty-preview">${escapeHtml(TXT.splitGroupNoItems)}</div>`;

            cardsHtml += `
                <div class="pos-split-group-card ${isSelected ? 'is-selected is-expanded' : ''}" data-order-id="${billId}">
                    <div class="pos-split-group-card-head">
                        <div>
                            <div class="pos-split-group-card-title">${escapeHtml(bill.order_number || '-')}</div>
                            <div class="pos-split-group-card-role">${escapeHtml(roleLabel)}</div>
                        </div>
                        <span class="badge ${escapeHtml(statusClass)}">${escapeHtml(statusLabel)}</span>
                    </div>

                    <div class="pos-split-group-card-meta">
                        <div class="pos-split-group-card-meta-item">
                            <div class="pos-split-group-card-meta-label">${escapeHtml(TXT.splitGroupItemsLabel)}</div>
                            <div class="pos-split-group-card-meta-value">${Number(bill.preview_count || bill.item_count || 0)}</div>
                        </div>
                        <div class="pos-split-group-card-meta-item">
                            <div class="pos-split-group-card-meta-label">${escapeHtml(TXT.splitGroupTotalLabel)}</div>
                            <div class="pos-split-group-card-meta-value">${escapeHtml(money(Number(bill.total_price || 0)))}</div>
                        </div>
                    </div>

                    ${isSelected ? `
                        <div class="pos-split-group-card-preview">
                            <div class="pos-split-group-card-meta-label mb-2">${escapeHtml(TXT.splitGroupItemsInBill)}</div>
                            <div class="pos-split-group-preview-list js-split-group-selected-detail" data-order-id="${billId}">${previewHtml}</div>
                        </div>
                    ` : ''}

                    <div class="pos-split-group-card-actions">
                        <button type="button" class="btn btn-outline-primary btn-select-split-group-bill" data-order-id="${billId}">${escapeHtml(actionSecondaryLabel)}</button>
                        ${canPay
                            ? `<button type="button" class="btn btn-success btn-pay-split-group-bill" data-order-id="${billId}">${escapeHtml(TXT.splitGroupPayBill)}</button>`
                            : `<button type="button" class="btn btn-outline-secondary" disabled>${escapeHtml(TXT.splitGroupAlreadyPaid)}</button>`}
                    </div>
                </div>
            `;
        });

        $panel
            .removeClass('is-hidden')
            .html(`
                <div class="pos-split-group-head">
                    <div>
                        <div class="pos-split-group-title">${escapeHtml(TXT.splitGroupTitle)}</div>
                        <div class="pos-split-group-note">${escapeHtml(TXT.splitGroupNote)}</div>
                    </div>
                    <div class="pos-split-group-stats">
                        <span class="pos-split-group-stat">${escapeHtml(TXT.splitGroupBills)}: ${normalizedGroup.length}</span>
                        <span class="pos-split-group-stat">${escapeHtml(TXT.splitGroupPaid)}: ${paidCount}/${normalizedGroup.length}</span>
                        <span class="pos-split-group-stat">${escapeHtml(TXT.splitGroupRemaining)}: ${escapeHtml(money(remainingTotal))}</span>
                    </div>
                </div>
                <div class="pos-split-group-list">${cardsHtml}</div>
            `);
    }

    function syncSplitGroupSelectedDetail(activeHtml, canceledHtml) {
        const hasSplitGroup = Array.isArray(CURRENT_SPLIT_GROUP) && CURRENT_SPLIT_GROUP.length > 1;
        const $orderBox = $('#orderBox');
        const $canceledBox = $('#canceledItemsBox');

        if (!hasSplitGroup) {
            $orderBox.removeClass('d-none');
            $canceledBox.removeClass('d-none');
            $('.js-split-group-selected-detail').each(function () {
                $(this).removeClass('is-mounted-detail');
            });
            return;
        }

        const currentOrderId = Number(CURRENT_ORDER_ID || 0);
        let mounted = false;

        $('.js-split-group-selected-detail').each(function () {
            const $target = $(this);
            const targetOrderId = Number($target.data('order-id') || 0);

            if (targetOrderId === currentOrderId) {
                const detailHtml = `
                    <div class="pos-split-group-detail-mounted">
                        <div class="pos-split-group-detail-active">${activeHtml}</div>
                        ${canceledHtml || ''}
                    </div>
                `;
                $target.html(detailHtml).addClass('is-mounted-detail');
                mounted = true;
            } else if ($target.hasClass('is-mounted-detail')) {
                $target.removeClass('is-mounted-detail');
            }
        });

        if (mounted) {
            $orderBox.addClass('d-none').html('');
            $canceledBox.addClass('d-none').html('');
        } else {
            $orderBox.removeClass('d-none');
            $canceledBox.removeClass('d-none');
        }
    }

    function renderItems(order, items) {
        let activeHtml = '';
        let servedHtml = '';
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
                const isServed = normalizedStatus === 'served';
                const canRequestCancel = !editable && !isCanceled && !isServed && (!requestState || requestState.key === 'rejected');

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
                            <button type="button" class="btn btn-outline-danger btn-void-item" data-id="${item.id}" data-product-name="${escapeHtml(item.product_name ?? '')}">${TXT.voidItem}</button>
                        </div>
                    `;
                } else if (canRequestCancel) {
                    actionButtons = `
                        <button type="button" class="btn btn-outline-danger btn-sm btn-request-cancel" data-id="${item.id}">${escapeHtml(TXT.requestCancelToKitchen)}</button>
                    `;
                }

                const servedAt = item.served_at ? `
                    <div class="small text-success mt-2">✓ ${escapeHtml(formatDateTime(String(item.served_at || ''), { mode: 'compact' }))}</div>
                ` : '';

                const cardHtml = `
                    <div class="border rounded-4 p-2 mb-2 ${isCanceled ? 'bg-light' : isServed ? 'bg-success bg-opacity-10 border-success-subtle' : ''}" data-item-status="${escapeHtml(item.status ?? 'pending')}" data-cancel-request-status="${escapeHtml(getCancelRequestStatus(item))}">
                        <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                            <div class="fw-bold">${escapeHtml(item.product_name ?? '')}</div>
                            <span class="badge bg-${displayStatus.badgeClass}">${escapeHtml(displayStatus.text)}</span>
                        </div>

                        ${item.item_detail ? `<div class="small text-dark">${TXT.detailLabel}: ${escapeHtml(item.item_detail)}</div>` : ''}
                        ${item.option_summary ? `<div class="small text-primary">${TXT.optionLabel}: ${escapeHtml(item.option_summary)}</div>` : ''}
                        ${item.note ? `<div class="small text-muted mb-2">${TXT.noteLabel}: ${escapeHtml(item.note)}</div>` : ''}
                        ${requestHtml}
                        ${servedAt}

                        <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                            <div>${money(item.price)} x ${item.qty}</div>
                            <div class="d-flex gap-2 flex-wrap">
                                ${actionButtons}
                            </div>
                        </div>

                        ${isCanceled ? `<div class="small text-danger mt-2 fw-semibold">${escapeHtml(TXT.canceledItemsNoCharge)}</div>` : ''}
                        ${isServed ? `<div class="small text-success mt-2 fw-semibold">${escapeHtml(TXT.servedItemsHint)}</div>` : ''}
                        ${secondaryButtons}
                    </div>
                `;

                if (isCanceled) {
                    if (!shouldHideCanceledItemFromVisibleHistory(item)) {
                        canceledHtml += cardHtml;
                    }
                } else if (isServed) {
                    servedHtml += cardHtml;
                } else {
                    activeHtml += cardHtml;
                }
            });

            renderBillRequestAlerts(items);
        }

        if (!activeHtml) {
            activeHtml = '<div class="text-muted">' + TXT.noItemsYet + '</div>';
        }

        if (servedHtml) {
            activeHtml += `
                <div class="mt-3 pt-3 border-top">
                    <div class="fw-bold mb-2 text-success">${escapeHtml(TXT.servedItemsSection)}</div>
                    ${servedHtml}
                </div>
            `;
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

        $('#orderBox').html(activeHtml).removeClass('d-none');
        $('#canceledItemsBox').html(canceledHtml).removeClass('d-none');
        syncSplitGroupSelectedDetail(activeHtml, canceledHtml);
        $('#billTotal').text(money(order.total_price || 0));
        updateMobileBillSummary(activeItemCount, order.total_price || 0, order.order_number || '');
        renderRecentProductToolbar(items || []);
    }

    function isAnyModalOpen() {
        return $('#productOptionModal').hasClass('show')
            || $('#paymentModal').hasClass('show')
            || $('#moveTableModal').hasClass('show')
            || $('#mergeBillModal').hasClass('show')
            || $('#splitBillModal').hasClass('show')
            || $('#mergeAuditModal').hasClass('show')
            || $('#moveAuditModal').hasClass('show')
            || $('#managerOverrideModal').hasClass('show')
            || $('#recentItemsModal').hasClass('show')
            || $('#billTimelineModal').hasClass('show');
    }

    function loadOrder(orderId = null, options = {}) {
		if (isAnyModalOpen() && !options.force) {
			return $.Deferred().resolve().promise();
		}

        const params = {};
        const requestedOrderId = parseInt(orderId || 0, 10);
        if (requestedOrderId > 0) {
            params.order_id = requestedOrderId;
        }

		return $.get("<?= site_url('pos/current-order') ?>/" + TABLE_ID, params)
			.done(function (res) {
				if (!res) {
					CURRENT_ORDER_ID = null;
					CURRENT_ORDER_STATUS = null;
                    CURRENT_ORDER_ITEMS = [];
                    CURRENT_SPLIT_GROUP = [];
                    CURRENT_ORDER_DATA = null;
                    renderSplitGroupPanel([], 0);
					$('#orderBox').html('<div class="text-muted">' + TXT.noBillYet + '</div>');
					$('#billTotal').text('฿0.00');
					updateOrderHeader(null);
					return;
				}

				if (res.status === 'empty') {
					CURRENT_ORDER_ID = null;
					CURRENT_ORDER_STATUS = null;
                    CURRENT_ORDER_ITEMS = [];
                    CURRENT_SPLIT_GROUP = [];
                    CURRENT_ORDER_DATA = null;
                    renderSplitGroupPanel([], 0);
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
                    CURRENT_ORDER_ITEMS = [];
                    CURRENT_SPLIT_GROUP = [];
                    CURRENT_ORDER_DATA = null;
                    renderSplitGroupPanel([], 0);
					$('#orderBox').html('<div class="text-muted">' + TXT.noBillYet + '</div>');
					$('#billTotal').text('฿0.00');
					updateOrderHeader(null);
					return;
				}

				CURRENT_ORDER_ID = res.order.id;
				CURRENT_ORDER_STATUS = res.order.status || 'open';
                CURRENT_ORDER_ITEMS = Array.isArray(res.items) ? res.items : [];
                CURRENT_SPLIT_GROUP = (Array.isArray(res.split_group) ? res.split_group : []).map(function (bill) {
                    const billId = Number(bill.id || 0);
                    bill.is_expanded = billId === Number(CURRENT_ORDER_ID || 0) || !!bill.is_selected;
                    return bill;
                });

				const orderData = Object.assign({}, res.order, {
					merged_notice: res.merged_notice || null,
					merge_trace: Array.isArray(res.merge_trace) ? res.merge_trace : [],
                    moved_notice: res.moved_notice || null,
                    move_trace: Array.isArray(res.move_trace) ? res.move_trace : [],
                    split_group: CURRENT_SPLIT_GROUP
				});

                CURRENT_ORDER_DATA = orderData;
                renderSplitGroupPanel(CURRENT_SPLIT_GROUP, CURRENT_ORDER_ID);
				renderItems(orderData, res.items || []);
				updateOrderHeader(orderData);
			})
			.fail(function (xhr) {
				console.error('loadOrder error:', xhr.responseText);
                    renderSplitGroupPanel([], 0);
				$('#orderBox').removeClass('d-none').html('<div class="text-danger">' + TXT.loadBillFailed + '</div>');
				$('#canceledItemsBox').removeClass('d-none').html('');
				$('#billTotal').text('฿0.00');
			});
	}

    function startAutoRefresh() {
        if (AUTO_REFRESH_TIMER) {
            clearInterval(AUTO_REFRESH_TIMER);
        }

        AUTO_REFRESH_TIMER = setInterval(function () {
            if (TABLE_ID > 0 && !isAnyModalOpen()) {
                loadOrder(CURRENT_ORDER_ID || null);
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
        renderRecentItemsModal();

        if (recentItemsModal) {
            recentItemsModal.show();
        }
    });

    $(document).on('click', '.btn-open-recent-modal-item', function () {
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

        if (recentItemsModal) {
            recentItemsModal.hide();
        }

        window.setTimeout(function () {
            openProductModal(productId, productName, productPrice);
            loadProductQuickOptions(productId);
        }, 180);
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

    
$(document).on('click', '.btn-void-item', async function () {
    const itemId = parseInt($(this).data('id') || 0, 10);
    const $btn = $(this);

    if (!itemId) {
        notify(TXT.selectedItemNotFound);
        return;
    }

    $btn.prop('disabled', true);

    const submitVoid = function () {
        return $.post("<?= site_url('pos/remove-item') ?>", {
            item_id: itemId,
            reason: ''
        });
    };

    try {
        let res = await submitVoid();

        if (res && res.status === 'manager_override_required') {
            const approved = await requestManagerOverride('void_item', CURRENT_ORDER_ID);
            if (!approved) {
                return;
            }
            res = await submitVoid();
        }

        if (!res || res.status !== 'success') {
            notify((res && res.message) ? res.message : TXT.voidItemFailed);
            return;
        }

        if (voidItemModal) {
            voidItemModal.hide();
        }
        loadOrder();
    } catch (xhr) {
        console.error('voidItem error:', xhr && xhr.responseText ? xhr.responseText : xhr);
        notify(TXT.voidItemFailed);
    } finally {
        $btn.prop('disabled', false);
    }
});


$(document).on('click', '#btnConfirmVoidItem', async function () {
    const itemId = parseInt($('#voidItemId').val() || 0, 10);
    const reason = $.trim($('#voidItemReason').val() || '');
    const $btn = $(this);

    if (!itemId) {
        notify(TXT.selectedItemNotFound);
        return;
    }

    $btn.prop('disabled', true);

    const submitVoid = function () {
        return $.post("<?= site_url('pos/remove-item') ?>", {
            item_id: itemId,
            reason: reason
        });
    };

    try {
        let res = await submitVoid();

        if (res && res.status === 'manager_override_required') {
            const approved = await requestManagerOverride('void_item', CURRENT_ORDER_ID);
            if (!approved) {
                return;
            }
            res = await submitVoid();
        }

        if (!res || res.status !== 'success') {
            notify((res && res.message) ? res.message : TXT.voidItemFailed);
            return;
        }

        if (voidItemModal) {
            voidItemModal.hide();
        }
        notify(res.message || TXT.voidItemSuccess);
        loadOrder();
    } catch (xhr) {
        console.error('voidItem error:', xhr && xhr.responseText ? xhr.responseText : xhr);
        notify(TXT.voidItemFailed);
    } finally {
        $btn.prop('disabled', false);
    }
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


    $(document).on('click', '.btn-select-split-group-bill', function () {
        const targetOrderId = parseInt($(this).data('order-id') || 0, 10);
        if (!targetOrderId) {
            return;
        }

        if (targetOrderId === CURRENT_ORDER_ID) {
            renderSplitGroupPanel(CURRENT_SPLIT_GROUP, CURRENT_ORDER_ID);
            return;
        }

        loadOrder(targetOrderId, { force: true });
    });

    $(document).on('click', '.btn-pay-split-group-bill', function () {
        const targetOrderId = parseInt($(this).data('order-id') || 0, 10);
        if (!targetOrderId) {
            return;
        }

        const openPayment = function () {
            if ($('#btnPay').prop('disabled')) {
                showToast(TXT.billCannotPay, 'warning');
                return;
            }

            $('#btnPay').trigger('click');
        };

        if (targetOrderId === CURRENT_ORDER_ID) {
            openPayment();
            return;
        }

        loadOrder(targetOrderId, { force: true }).done(function () {
            openPayment();
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
                CURRENT_ORDER_DATA = null;
                CURRENT_ORDER_ITEMS = [];
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


    $(document).on('click', '#btnSplitBill', function () {
        openSplitBillModal();
    });

    $(document).on('click', '#splitBillItemsBox .split-bill-main-tap', function (e) {
        e.stopPropagation();
        if ($(this).is('[disabled]') || $(this).hasClass('is-disabled')) {
            return;
        }
        const itemId = Number($(this).data('order-item-id') || 0);
        moveSplitBillItem(itemId, 1);
    });

    $(document).on('click', '#splitBillItemsBox .split-bill-workspace-step', function (e) {
        e.stopPropagation();
        const itemId = Number($(this).data('order-item-id') || 0);
        const type = String($(this).data('type') || 'plus');
        if (type === 'minus') {
            returnSplitBillItem(itemId, 1);
        } else {
            moveSplitBillItem(itemId, 1);
        }
    });

    $(document).on('click', '#splitBillItemsBox .split-bill-move-one', function (e) {
        e.stopPropagation();
        const itemId = Number($(this).data('order-item-id') || 0);
        moveSplitBillItem(itemId, 1);
    });

    $(document).on('click', '#splitBillItemsBox .split-bill-move-all', function (e) {
        e.stopPropagation();
        const itemId = Number($(this).data('order-item-id') || 0);
        moveAllSplitBillItem(itemId);
    });

    $(document).on('click', '#splitBillItemsBox .split-bill-move-qty', function (e) {
        e.stopPropagation();
        const itemId = Number($(this).data('order-item-id') || 0);
        const qty = Number($(this).data('qty') || 0);
        if (qty > 0) {
            moveSplitBillItem(itemId, qty);
        }
    });

    $(document).on('click', '#splitBillTargetBox .split-bill-return-one', function (e) {
        e.stopPropagation();
        const itemId = Number($(this).data('order-item-id') || 0);
        returnSplitBillItem(itemId, 1);
    });

    $(document).on('click', '#splitBillTargetBox .split-bill-return-all', function (e) {
        e.stopPropagation();
        const itemId = Number($(this).data('order-item-id') || 0);
        setWorkspaceItemMovedQty(itemId, 0);
        renderSplitBillWorkspace();
    });

    $(document).on('click', '#splitBillTargetBox .split-bill-target-step', function (e) {
        e.stopPropagation();
        const itemId = Number($(this).data('order-item-id') || 0);
        const type = String($(this).data('type') || 'minus');
        if (type === 'minus') {
            returnSplitBillItem(itemId, 1);
        } else {
            moveSplitBillItem(itemId, 1);
        }
    });

    $(document).on('click', '#btnSplitBillSelectSingles', function () {
        const items = SPLIT_BILL_WORKSPACE_STATE && Array.isArray(SPLIT_BILL_WORKSPACE_STATE.items)
            ? SPLIT_BILL_WORKSPACE_STATE.items
            : [];
        items.forEach(function (item) {
            if (Number(item.source_qty || 0) === 1) {
                setWorkspaceItemMovedQty(item.id, 1);
            }
        });
        renderSplitBillWorkspace();
    });

    $(document).on('click', '#btnSplitBillClearAll', function () {
        clearSplitBillWorkspace();
    });

    $(document).on('click', '#btnSplitBillPreviewRefresh', function () {
        requestSplitBillPreview();
    });

    $(document).on('click', '#btnConfirmSplitBill', function () {
        const items = collectSplitBillItems();
        if (!CURRENT_ORDER_ID) {
            notify(TXT.noBillYet);
            return;
        }
        if (!items.length) {
            notify(TXT.splitBillSelectAtLeastOne);
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true);

        $.post("<?= site_url('pos/split-bill/confirm') ?>", {
            order_id: CURRENT_ORDER_ID,
            items: items,
            reason: $.trim($('#splitBillReason').val())
        })
        .done(function (res) {
            if (!res || res.status !== 'success') {
                notify((res && res.message) ? res.message : TXT.splitBillFailed);
                $btn.prop('disabled', false);
                return;
            }

            if (splitBillModal) {
                splitBillModal.hide();
            }

            notify(res.message || TXT.splitBillSuccess);
            loadOrder();
        })
        .fail(function (xhr) {
            console.error('splitBillConfirm error:', xhr.responseText);
            notify(TXT.splitBillFailed);
        })
        .always(function () {
            $btn.prop('disabled', false);
        });
    });

    $(document).on('hidden.bs.modal', '#splitBillModal', function () {
        resetSplitBillModal();
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

    $(document).on('click', '#btnMoveTable, #btnMergeBill, #btnSplitBill, #btnConfirmMoveTable, #btnConfirmMergeBill, #btnConfirmSplitBill, #btnSplitBillPreview, #btnSplitBillPreviewRefresh, #btnViewMergeAudit, #btnViewMoveAudit', function (e) {
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

    if (recentItemsModalEl) {
        recentItemsModalEl.addEventListener('hidden.bs.modal', function () {
            const activeTrigger = document.activeElement;
            if (activeTrigger && typeof activeTrigger.blur === 'function') {
                activeTrigger.blur();
            }
        });
    }

    $(document).on('click', '#btnBillTimelineQuick', function () {
        openBillTimeline();
    });

    updateOrderHeader();
    applyProductFilters();
    renderRecentProductToolbar([]);
    updateMobileBillSummary(0, 0, '');
    loadOrder();
    startAutoRefresh();
});
</script>
<?= $this->endSection() ?>

<style>
/* Split Bill final i18n + polish */
.split-bill-quick-guide{padding:.7rem .9rem}
.split-bill-quick-guide-text{line-height:1.35}
.split-bill-target-summary{margin-bottom:.6rem}
.split-bill-pane.is-target .split-bill-empty-state{min-height:54px;display:flex;align-items:center;justify-content:center}
@media (max-width:767.98px){
  .split-bill-quick-guide{padding:.65rem .8rem;margin-bottom:.75rem}
  .split-bill-pane-head{margin-bottom:.7rem}
  .split-bill-target-summary{margin-bottom:.5rem}
}
</style>

