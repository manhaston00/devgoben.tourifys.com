<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<?php
    $locale = service('request')->getLocale();

    $tr = static function (string $key, string $th, string $en, array $args = []) use ($locale) {
        $text = lang($key, $args);
        if ($text === $key) {
            $text = $locale === 'th' ? $th : $en;
            foreach ($args as $argKey => $argValue) {
                $text = str_replace('{' . $argKey . '}', (string) $argValue, $text);
            }
        }

        return $text;
    };

    $formatReservationTime = static function (string $date, string $time) {
        $date = trim($date);
        $time = trim($time);

        if ($time === '') {
            return '';
        }

        if ($date === '') {
            return $time;
        }

        try {
            $dt = new \DateTime(trim($date . ' ' . $time));
            return $dt->format('H:i');
        } catch (\Throwable $e) {
            return $time;
        }
    };

    $formatCompactDateTime = static function (?string $value) {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        try {
            $dt = new \DateTime($value);
            return $dt->format('H:i');
        } catch (\Throwable $e) {
            return $value;
        }
    };


    $formatRemainingMinutesShort = static function (?int $minutes) use ($tr) {
        $minutes = (int) ($minutes ?? 0);

        if ($minutes === 0) {
            return $tr('app.reservation_time_due', '⏱ ถึงเวลาจองแล้ว', '⏱ Reservation time reached');
        }

        $isOverdue = $minutes < 0;
        $absMinutes = abs($minutes);
        $hours = intdiv($absMinutes, 60);
        $mins  = $absMinutes % 60;

        if ($isOverdue) {
            if ($hours > 0 && $mins > 0) {
                return $tr('app.reservation_time_overdue_hm', '⏱ เลยเวลาจอง {hours} ชม. {minutes} นาที', '⏱ Overdue by {hours}h {minutes}m', ['hours' => $hours, 'minutes' => $mins]);
            }
            if ($hours > 0) {
                return $tr('app.reservation_time_overdue_h', '⏱ เลยเวลาจอง {hours} ชม.', '⏱ Overdue by {hours}h', ['hours' => $hours]);
            }
            return $tr('app.reservation_time_overdue_m', '⏱ เลยเวลาจอง {minutes} นาที', '⏱ Overdue by {minutes}m', ['minutes' => $mins]);
        }

        if ($hours > 0 && $mins > 0) {
            return $tr('app.reservation_time_remaining_hm', '⏱ เหลือ {hours} ชม. {minutes} นาที', '⏱ {hours}h {minutes}m left', ['hours' => $hours, 'minutes' => $mins]);
        }
        if ($hours > 0) {
            return $tr('app.reservation_time_remaining_h', '⏱ เหลือ {hours} ชม.', '⏱ {hours}h left', ['hours' => $hours]);
        }
        return $tr('app.reservation_time_remaining_m', '⏱ เหลือ {minutes} นาที', '⏱ {minutes}m left', ['minutes' => $mins]);
    };

    $formatRemainingMinutesCompact = static function (?int $minutes) use ($tr) {
        $minutes = (int) ($minutes ?? 0);

        if ($minutes === 0) {
            return $tr('app.reservation_time_due_compact', 'ถึงเวลา', 'Due');
        }

        $isOverdue = $minutes < 0;
        $absMinutes = abs($minutes);
        $hours = intdiv($absMinutes, 60);
        $mins  = $absMinutes % 60;

        if ($isOverdue) {
            if ($hours > 0 && $mins > 0) {
                return $tr('app.reservation_time_overdue_compact_hm', 'เลย {hours}ชม.{minutes}น.', '+{hours}h {minutes}m', ['hours' => $hours, 'minutes' => $mins]);
            }
            if ($hours > 0) {
                return $tr('app.reservation_time_overdue_compact_h', 'เลย {hours}ชม.', '+{hours}h', ['hours' => $hours]);
            }
            return $tr('app.reservation_time_overdue_compact_m', 'เลย {minutes}น.', '+{minutes}m', ['minutes' => $mins]);
        }

        if ($hours > 0 && $mins > 0) {
            return $tr('app.reservation_time_remaining_compact_hm', '{hours}ชม.{minutes}น.', '{hours}h {minutes}m', ['hours' => $hours, 'minutes' => $mins]);
        }
        if ($hours > 0) {
            return $tr('app.reservation_time_remaining_compact_h', '{hours}ชม.', '{hours}h', ['hours' => $hours]);
        }
        return $tr('app.reservation_time_remaining_compact_m', '{minutes}น.', '{minutes}m', ['minutes' => $mins]);
    };

    $tables = is_array($tables ?? null) ? $tables : [];
    $zoneMap = [];
    $stats = [
        'all'       => 0,
        'available' => 0,
        'occupied'  => 0,
        'reserved'  => 0,
        'disabled'  => 0,
        'locked'    => 0,
        'openOrder' => 0,
    ];

    foreach ($tables as $table) {
        $stats['all']++;

        $status = strtolower(trim((string) ($table['status'] ?? 'available')));
        $isActive = (int) ($table['is_active'] ?? 0) === 1;
        $isReservationLocked = ! empty($table['reservation_locked']);
        $hasOpenOrder = ! empty($table['has_open_order']);
        $zoneLabel = trim((string) lfield($table, 'zone_name', (string) ($table['zone_name'] ?? '')));

        if ($zoneLabel !== '') {
            $zoneMap[$zoneLabel] = $zoneLabel;
        }

        if (! $isActive || $status === 'disabled' || $status === 'cleaning') {
            $stats['disabled']++;
            continue;
        }

        if ($isReservationLocked) {
            $stats['locked']++;
        }

        if ($hasOpenOrder) {
            $stats['openOrder']++;
        }

        switch ($status) {
            case 'occupied':
                $stats['occupied']++;
                break;
            case 'reserved':
                $stats['reserved']++;
                break;
            case 'available':
            default:
                $stats['available']++;
                break;
        }
    }

    ksort($zoneMap);

    $summaryCards = [
        [
            'value' => $stats['all'],
            'label' => $tr('app.total_tables', 'โต๊ะทั้งหมด', 'Total tables'),
            'class' => 'summary-dark',
        ],
        [
            'value' => $stats['available'],
            'label' => $tr('app.available_tables', 'โต๊ะว่าง', 'Available tables'),
            'class' => 'summary-green',
        ],
        [
            'value' => $stats['occupied'],
            'label' => $tr('app.open_tables', 'โต๊ะกำลังใช้งาน', 'Active tables'),
            'class' => 'summary-blue',
        ],
        [
            'value' => $stats['reserved'] + $stats['locked'],
            'label' => $tr('app.reserved_tables', 'โต๊ะจอง', 'Reserved tables'),
            'class' => 'summary-amber',
        ],
        [
            'value' => $stats['disabled'],
            'label' => $tr('app.unavailable_tables', 'โต๊ะใช้งานไม่ได้', 'Unavailable tables'),
            'class' => 'summary-slate',
        ],
        [
            'value' => $stats['openOrder'],
            'label' => $tr('app.tables_with_open_orders', 'โต๊ะที่มีบิลเปิด', 'Tables with open orders'),
            'class' => 'summary-purple',
        ],
    ];

    $filterTabs = [
        ['key' => 'all', 'label' => $tr('app.all', 'ทั้งหมด', 'All')],
        ['key' => 'available', 'label' => $tr('app.available', 'ว่าง', 'Available')],
        ['key' => 'occupied', 'label' => $tr('app.occupied', 'ไม่ว่าง', 'Occupied')],
        ['key' => 'reserved', 'label' => $tr('app.reserved', 'จอง', 'Reserved')],
        ['key' => 'disabled', 'label' => $tr('app.disabled', 'ปิดใช้งาน', 'Disabled')],
        ['key' => 'open_order', 'label' => $tr('app.open_order_exists', 'มีบิลเปิด', 'Open order')],
    ];
?>

<style>
    .pos-overview-shell {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .pos-overview-topbar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: .8rem;
        padding: 14px 16px;
        border: 1px solid #dbe5f1;
        border-radius: 20px;
        background: #ffffff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }

    .pos-summary-card-mobile-secondary {
        display: block;
    }

    .pos-reservation-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .28rem;
        min-height: 30px;
        padding: 0 .62rem;
        border-radius: 999px;
        border: 1px solid #dbe5f1;
        background: #ffffff;
        color: #475569;
        font-size: .68rem;
        font-weight: 800;
        white-space: nowrap;
        text-decoration: none;
    }

    .pos-reservation-trigger:hover {
        border-color: #bfdbfe;
        color: #1d4ed8;
        background: #f8fbff;
    }

    .pos-reservation-badge-soft {
        background: #fff7ed;
        border-color: #fed7aa;
        color: #c2410c;
    }

    .pos-reservation-inline-note {
        display: inline-flex;
        align-items: center;
        gap: .24rem;
        padding: .28rem .5rem;
        border-radius: 999px;
        background: #fffaf3;
        border: 1px solid #fde2bf;
        color: #9a3412;
        font-size: .62rem;
        font-weight: 800;
        line-height: 1;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pos-reservation-detail-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.48);
        z-index: 1050;
    }

    .pos-reservation-detail-backdrop.show {
        display: block;
    }

    .pos-reservation-detail-sheet {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1055;
        background: #ffffff;
        border-radius: 22px 22px 0 0;
        box-shadow: 0 -16px 36px rgba(15, 23, 42, 0.18);
        padding: 14px 14px calc(14px + env(safe-area-inset-bottom));
        transform: translateY(105%);
        transition: transform .22s ease, opacity .22s ease, visibility .22s ease;
        max-height: 82vh;
        overflow-y: auto;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .pos-reservation-detail-sheet.show {
        transform: translateY(0);
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .pos-reservation-detail-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .85rem;
        margin-bottom: 12px;
        position: sticky;
        top: 0;
        background: #ffffff;
        padding-bottom: 10px;
        z-index: 1;
    }

    .pos-reservation-detail-title {
        font-size: 1rem;
        font-weight: 900;
        color: #0f172a;
        margin-bottom: .18rem;
    }

    .pos-reservation-detail-subtitle {
        color: #64748b;
        font-size: .8rem;
        font-weight: 700;
    }

    .pos-reservation-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }

    .pos-reservation-detail-item {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 10px 12px;
        background: #f8fafc;
    }

    .pos-reservation-detail-label {
        font-size: .7rem;
        font-weight: 800;
        color: #64748b;
        margin-bottom: .2rem;
    }

    .pos-reservation-detail-value {
        font-size: .9rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.3;
        word-break: break-word;
    }

    .pos-reservation-detail-note {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 12px;
        background: #fffdf8;
        color: #334155;
        font-size: .84rem;
        font-weight: 700;
        line-height: 1.45;
        margin-bottom: 12px;
    }

    .pos-reservation-detail-actions {
        display: flex;
        gap: .6rem;
        flex-wrap: wrap;
    }

    .pos-reservation-detail-actions .btn,
    .pos-reservation-detail-actions a {
        min-height: 40px;
        border-radius: 12px;
        font-weight: 800;
    }

    .pos-overview-title {
        font-size: clamp(1.3rem, 2vw, 1.8rem);
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: .35rem;
        color: #0f172a;
    }

    .pos-overview-subtitle {
        color: #64748b;
        font-size: .95rem;
    }

    .pos-overview-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        width: min(100%, 760px);
        justify-content: flex-end;
        align-items: center;
    }

    .pos-topbar-ops {
        display: flex;
        align-items: center;
        gap: .45rem;
        flex-wrap: wrap;
        justify-content: flex-end;
        flex: 0 0 auto;
    }

    .pos-desktop-toolbar-note {
        display: inline-flex;
        align-items: center;
        color: #64748b;
        font-size: .8rem;
        font-weight: 700;
        margin-right: .15rem;
    }

    .pos-overview-search {
        position: relative;
        flex: 1 1 260px;
        min-width: 220px;
    }

    .pos-overview-search .form-control {
        min-height: 46px;
        border-radius: 15px;
        padding-left: 42px;
        border-color: #cbd5e1;
        box-shadow: none;
    }

    .pos-overview-search .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
    }

    .pos-overview-search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: .95rem;
        color: #94a3b8;
        pointer-events: none;
    }

    .pos-overview-search-clear {
        min-height: 46px;
        min-width: 46px;
        border-radius: 15px;
        font-weight: 700;
    }

    .pos-zone-toolbar,
    .pos-filter-toolbar {
        display: flex;
        gap: .75rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .pos-toolbar-label {
        font-size: .86rem;
        font-weight: 700;
        color: #334155;
    }

    .pos-zone-chips,
    .pos-filter-chips {
        display: flex;
        gap: .55rem;
        flex-wrap: wrap;
    }

    .pos-chip-btn {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #1e293b;
        border-radius: 999px;
        min-height: 38px;
        padding: .45rem .9rem;
        font-size: .82rem;
        font-weight: 700;
        line-height: 1;
        transition: all .18s ease;
    }

    .pos-chip-btn:hover {
        border-color: #93c5fd;
        color: #1d4ed8;
        background: #f8fbff;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.08);
    }

    .pos-chip-btn.active {
        background: #1e3a8a;
        border-color: #1e3a8a;
        color: #ffffff;
        box-shadow: 0 12px 28px rgba(30, 58, 138, 0.18);
    }

    .pos-filter-reset-btn {
        border: 1px dashed #cbd5e1;
        background: #f8fafc;
        color: #475569;
        border-radius: 999px;
        min-height: 38px;
        padding: .45rem .9rem;
        font-size: .8rem;
        font-weight: 800;
        line-height: 1;
        transition: all .18s ease;
        white-space: nowrap;
    }

    .pos-filter-reset-btn:hover {
        border-color: #93c5fd;
        color: #1d4ed8;
        background: #eff6ff;
    }

    .pos-summary-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
    }

    .pos-summary-card {
        position: relative;
        overflow: hidden;
        padding: 14px 14px 13px;
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        box-shadow: none;
    }

    .pos-summary-value {
        font-size: 1.45rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        margin-bottom: .3rem;
    }

    .pos-summary-label {
        font-size: .82rem;
        color: #334155;
        line-height: 1.35;
        font-weight: 700;
        max-width: 140px;
    }

    .summary-dark { background: #f8fafc; border-color: #cfd8e3; }
    .summary-green { background: #f1faf5; border-color: #c9e9d4; }
    .summary-blue { background: #f3f7ff; border-color: #cfe0ff; }
    .summary-amber { background: #fff8ef; border-color: #f7d8ae; }
    .summary-slate { background: #f8fafc; border-color: #dde5ee; }
    .summary-purple { background: #f7f5ff; border-color: #e1d9fb; }

    .pos-results-meta {
        display: none;
    }

    .pos-results-actions {
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .pos-mobile-filter-toggle {
        display: none;
        min-height: 36px;
        border-radius: 999px;
        font-weight: 800;
        padding: 0 14px;
        white-space: nowrap;
        box-shadow: none;
    }

    .pos-live-inline {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        min-height: 36px;
        padding: 0 .78rem;
        border-radius: 999px;
        border: 1px solid #dbe5f1;
        background: #ffffff;
        color: #334155;
        font-size: .72rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .pos-live-inline.syncing {
        color: #1d4ed8;
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .pos-live-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #22c55e;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.14);
        flex: 0 0 auto;
    }

    .pos-live-refresh-btn {
        min-height: 36px;
        border-radius: 999px;
        padding: 0 .85rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .pos-results-title {
        font-size: 1rem;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -.01em;
    }

    .pos-results-note {
        font-size: .86rem;
        color: #64748b;
    }

    .pos-overview-topbar,
    .pos-results-meta,
    .pos-table-grid {
        position: relative;
        z-index: 1;
    }

    .pos-results-actions .pos-results-note {
        text-align: right;
    }

    .pos-table-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .pos-table-card {
        display: flex;
        flex-direction: column;
        gap: 10px;
        min-height: 160px;
        padding: 12px;
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.035);
        color: #0f172a;
        text-decoration: none;
        transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
        position: relative;
        overflow: hidden;
        background: #ffffff;
    }

    .pos-table-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        color: #0f172a;
    }

    .pos-table-card-clickable {
        cursor: pointer;
    }

    .pos-table-card-clickable:focus-visible {
        outline: 0;
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12), 0 10px 24px rgba(15, 23, 42, 0.08);
    }

    .pos-table-card-updated {
        animation: posTablePulse 1.2s ease;
        border-color: #93c5fd;
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.10), 0 0 0 3px rgba(59, 130, 246, 0.10);
    }

    @keyframes posTablePulse {
        0% {
            border-color: #bfdbfe;
            box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.24);
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
        }
        45% {
            border-color: #93c5fd;
            box-shadow: 0 0 0 8px rgba(37, 99, 235, 0.10), 0 10px 24px rgba(15, 23, 42, 0.08);
            background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
        }
        100% {
            border-color: #dbe5f1;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            background: #ffffff;
        }
    }

    .pos-table-card.disabled-link,
    .pos-table-card.disabled-link:hover {
        transform: none;
        cursor: not-allowed;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    }

    .pos-table-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: #94a3b8;
    }

    .pos-table-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .55rem;
        position: relative;
        z-index: 1;
    }

    .pos-table-name {
        font-size: 1.02rem;
        font-weight: 900;
        line-height: 1.05;
        margin-bottom: .18rem;
        letter-spacing: -.01em;
        word-break: break-word;
        overflow-wrap: anywhere;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        max-width: 100%;
    }

    .pos-table-zone {
        color: #64748b;
        font-size: .72rem;
        line-height: 1.2;
        font-weight: 700;
    }

    .pos-table-card-head > div {
        flex: 1 1 auto;
        min-width: 0;
    }

    .pos-table-mobile-meta {
        display: none;
        font-size: .66rem;
        line-height: 1.2;
        color: #64748b;
        font-weight: 700;
        margin-top: .18rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pos-table-top-badge {
        padding: .28rem .5rem;
        border-radius: 999px;
        font-size: .62rem;
        font-weight: 800;
        line-height: 1;
        background: #ffffff;
        color: #334155;
        border: 1px solid #dbe5f1;
        white-space: nowrap;
        flex: 0 0 auto;
        max-width: 118px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pos-table-body {
        display: flex;
        flex-direction: column;
        gap: 7px;
        position: relative;
        z-index: 1;
    }

    .pos-table-status-row {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        align-items: flex-start;
    }

    .pos-status-pill,
    .pos-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: .24rem;
        padding: .26rem .48rem;
        border-radius: 999px;
        background: #ffffff;
        color: #0f172a;
        font-size: .63rem;
        font-weight: 800;
        line-height: 1;
        border: 1px solid #dbe5f1;
        white-space: nowrap;
    }

    .pos-table-signals {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 10px;
    }

    .pos-table-signal {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        max-width: 100%;
        border-radius: 999px;
        padding: 4px 9px;
        font-size: .68rem;
        font-weight: 800;
        line-height: 1.2;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .pos-table-signal--slate {
        background: #f8fafc;
        color: #334155;
        border-color: #cbd5e1;
    }

    .pos-table-signal--blue {
        background: #eff6ff;
        color: #1d4ed8;
        border-color: #bfdbfe;
    }

    .pos-table-signal--amber {
        background: #fffbeb;
        color: #b45309;
        border-color: #fde68a;
    }

    .pos-table-signal--violet {
        background: #f5f3ff;
        color: #7c3aed;
        border-color: #ddd6fe;
    }

    .pos-table-signal--emerald {
        background: #ecfdf5;
        color: #047857;
        border-color: #a7f3d0;
    }

    .pos-table-signal--teal {
        background: #f0fdfa;
        color: #0f766e;
        border-color: #99f6e4;
    }

    .pos-table-signal--rose {
        background: #fff1f2;
        color: #be123c;
        border-color: #fecdd3;
    }

    .pos-table-signal-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .pos-table-metrics {
        display: grid;
        grid-template-columns: 1fr;
        gap: 6px;
    }

    .pos-table-metric {
        padding: .55rem .62rem;
        border-radius: 12px;
        background: rgba(255,255,255,.82);
        border: 1px solid rgba(219, 229, 241, .95);
        min-height: 0;
    }

    .pos-table-metric-label {
        font-size: .62rem;
        color: #64748b;
        line-height: 1.15;
        margin-bottom: .18rem;
        font-weight: 700;
    }

    .pos-table-metric-value {
        font-size: .78rem;
        color: #0f172a;
        font-weight: 800;
        line-height: 1.12;
        word-break: break-word;
    }

    .pos-table-foot {
        margin-top: auto;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .5rem;
        position: relative;
        z-index: 1;
    }

    .pos-table-action {
        display: none;
    }

    .pos-table-indicator {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .28rem;
        font-size: .7rem;
        color: #0f172a;
        background: #ffffff;
        border: 1px solid #dbe5f1;
        border-radius: 999px;
        padding: .34rem .58rem;
        font-weight: 800;
        min-width: 0;
    }

    .pos-indicator-desktop { display: inline; }
    .pos-indicator-mobile { display: none; }

    .table-bg-available {
        background: #ffffff;
        border-color: #d2f0df;
    }

    .table-bg-available::before {
        background: #16a34a;
    }

    .table-bg-occupied {
        background: #ffffff;
        border-color: #f8c9c9;
    }

    .table-bg-occupied::before {
        background: #dc2626;
    }

    .table-bg-reserved {
        background: #ffffff;
        border-color: #f6ddb0;
    }

    .table-bg-reserved::before {
        background: #d97706;
    }

    .table-bg-cleaning {
        background: #ffffff;
        border-color: #cadcff;
    }

    .table-bg-cleaning::before {
        background: #2563eb;
    }

    .table-bg-disabled {
        background: #ffffff;
        border-color: #d7dee8;
    }

    .table-bg-disabled::before {
        background: #64748b;
    }

    .table-bg-default {
        background: #ffffff;
        border-color: #dde5ee;
    }

    .table-bg-default::before {
        background: #475569;
    }

    .table-bg-locked {
        background: #ffffff;
        border-color: #f7cfaa;
    }

    .table-bg-locked::before {
        background: #ea580c;
    }

    .table-bg-available .pos-table-indicator {
        background: #f0fdf4;
        border-color: #ccefd7;
        color: #166534;
    }

    .table-bg-occupied .pos-table-indicator {
        background: #fef2f2;
        border-color: #f8c9c9;
        color: #b91c1c;
    }

    .table-bg-reserved .pos-table-indicator,
    .table-bg-locked .pos-table-indicator {
        background: #fff7ed;
        border-color: #f7cfaa;
        color: #c2410c;
    }

    .table-bg-disabled .pos-table-indicator {
        background: #f8fafc;
        border-color: #d7dee8;
        color: #475569;
    }

    .pos-empty-result {
        display: none;
        padding: 32px 18px;
        border: 1px dashed rgba(148, 163, 184, 0.45);
        border-radius: 24px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        text-align: center;
        color: #64748b;
        font-weight: 700;
    }

    .pos-empty-result.show {
        display: block;
    }

    .pos-mobile-filters {
        display: flex;
        flex-direction: column;
        gap: 14px;
        position: relative;
        z-index: 1;
        width: 100%;
    }

    .pos-mobile-filter-backdrop {
        display: none;
    }

    .pos-mobile-sheet-head {
        display: none;
    }

    @media (max-width: 1399.98px) {
        .pos-summary-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .pos-table-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 991.98px) {
        .pos-overview-topbar {
            padding: 16px;
        }

        .pos-overview-actions {
            width: 100%;
            justify-content: stretch;
        }

        .pos-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pos-table-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 768px) {
        .pos-reservation-detail-backdrop {
            backdrop-filter: blur(2px);
        }

        .pos-reservation-detail-sheet {
            left: 50%;
            right: auto;
            bottom: auto;
            top: 50%;
            width: min(820px, calc(100vw - 40px));
            max-height: min(84vh, 760px);
            padding: 20px;
            border-radius: 24px;
            box-shadow: 0 24px 64px rgba(15, 23, 42, 0.24);
            transform: translate(-50%, calc(-50% + 18px)) scale(.98);
        }

        .pos-reservation-detail-sheet.show {
            transform: translate(-50%, -50%) scale(1);
        }

        .pos-reservation-detail-head {
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 16px;
        }

        .pos-reservation-detail-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .pos-reservation-detail-note {
            margin-top: 14px;
        }

        .pos-reservation-detail-actions {
            justify-content: flex-end;
            gap: .75rem;
            margin-top: 16px;
        }
    }

    @media (max-width: 767.98px) {
        .pos-overview-shell {
            gap: 12px;
        }

        .pos-overview-topbar {
            gap: 8px;
            padding: 10px;
            border-radius: 18px;
            position: sticky;
            top: calc(env(safe-area-inset-top) + 8px);
            z-index: 1025;
        }

        .pos-overview-subtitle {
            display: none;
        }

        .pos-overview-actions {
            width: 100%;
            gap: 6px;
            justify-content: stretch;
        }

        .pos-topbar-ops {
            width: 100%;
            justify-content: space-between;
            gap: .4rem;
        }

        .pos-overview-search {
            flex: 1 1 100%;
            min-width: 100%;
        }

        .pos-overview-search .form-control {
            min-height: 40px;
            padding-left: 40px;
        }

        .pos-overview-search-clear {
            display: none;
        }

        .pos-mobile-filter-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 96px;
            min-height: 34px;
            padding: 0 12px;
            font-size: .72rem;
        }

        .pos-mobile-filter-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.42);
            z-index: 1040;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .22s ease, visibility .22s ease;
        }

        .pos-mobile-filter-backdrop.show {
            display: block;
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .pos-mobile-filters {
            display: block;
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1045;
            background: #ffffff;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 -16px 32px rgba(15, 23, 42, 0.16);
            padding: 12px 12px calc(12px + env(safe-area-inset-bottom));
            transform: translateY(105%);
            transition: transform .22s ease, opacity .22s ease, visibility .22s ease;
            max-height: 78vh;
            overflow-y: auto;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .pos-mobile-filters.show {
            transform: translateY(0);
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .pos-mobile-sheet-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
            position: sticky;
            top: 0;
            background: #ffffff;
            z-index: 2;
            padding-bottom: 8px;
        }

        .pos-mobile-sheet-title {
            font-size: .96rem;
            font-weight: 800;
            color: #0f172a;
        }

        .pos-mobile-sheet-actions {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .pos-mobile-sheet-close {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: #fff;
            color: #0f172a;
            font-size: 1.15rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .pos-zone-toolbar,
        .pos-filter-toolbar {
            display: block;
        }

        .pos-toolbar-label {
            display: block;
            margin-bottom: 6px;
        }

        .pos-filter-toolbar-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
            margin-bottom: 6px;
            flex-wrap: wrap;
        }

        .pos-zone-chips,
        .pos-filter-chips {
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: .2rem;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .pos-zone-chips::-webkit-scrollbar,
        .pos-filter-chips::-webkit-scrollbar {
            display: none;
        }

        .pos-summary-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-top: 2px;
        }

        .pos-summary-card-mobile-secondary {
            display: none;
        }

        .pos-summary-card {
            border-radius: 14px;
            padding: 10px;
            min-height: auto;
        }

        .pos-summary-value {
            font-size: .96rem;
            margin-bottom: .1rem;
        }

        .pos-summary-label {
            font-size: .63rem;
            max-width: none;
            line-height: 1.1;
        }

        .pos-results-meta {
            margin-top: -2px;
            align-items: flex-start;
            gap: .5rem;
        }

        .pos-results-actions {
            width: 100%;
            justify-content: flex-start;
            gap: .45rem;
        }

        .pos-results-actions .pos-results-note {
            display: none;
        }

        .pos-live-inline,
        .pos-live-refresh-btn,
        .pos-mobile-filter-toggle,
        .pos-filter-reset-btn {
            min-height: 30px;
            font-size: .66rem;
        }

        .pos-live-inline {
            padding: 0 .55rem;
            gap: .32rem;
        }

        .pos-live-refresh-btn {
            display: none;
        }

        .pos-mobile-filter-toggle {
            min-width: 86px;
            padding: 0 11px;
        }

        .pos-filter-reset-btn {
            padding: 0 .7rem;
        }

        .pos-table-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .pos-table-card {
            min-height: 118px;
            padding: 10px 9px 9px;
            border-radius: 16px;
            gap: 6px;
            box-shadow: 0 5px 12px rgba(15, 23, 42, 0.04);
        }

        .pos-table-name {
            font-size: .92rem;
            line-height: 1.02;
            margin-bottom: 0;
        }

        .pos-table-zone,
        .pos-table-action,
        .pos-table-metrics,
        .pos-meta-pill {
            display: none;
        }

        .pos-table-mobile-meta {
            display: block;
        }

        .pos-table-card-head {
            gap: .3rem;
        }

        .pos-table-top-badge {
            padding: .22rem .38rem;
            font-size: .52rem;
            max-width: 82px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pos-table-body {
            gap: 5px;
        }

        .pos-table-status-row {
            gap: .26rem;
        }

        .pos-status-pill {
            padding: .24rem .38rem;
            font-size: .53rem;
            gap: .14rem;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pos-table-foot {
            justify-content: space-between;
            align-items: center;
        }

        .pos-reservation-trigger {
            min-height: 26px;
            padding: 0 .48rem;
            font-size: .58rem;
        }

        .pos-reservation-inline-note {
            font-size: .56rem;
            padding: .24rem .42rem;
        }

        .pos-reservation-detail-grid {
            grid-template-columns: 1fr;
        }

        .pos-table-indicator {
            font-size: .61rem;
            padding: .28rem .42rem;
            width: 100%;
            justify-content: center;
            border-radius: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pos-indicator-desktop { display: none; }
        .pos-indicator-mobile { display: inline; }
    }

    @media (max-width: 420px) {
        .pos-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pos-table-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .pos-table-card {
            min-height: 126px;
        }

        .pos-reservation-detail-sheet {
            padding-left: 12px;
            padding-right: 12px;
        }

        .pos-topbar-ops {
            gap: .4rem;
        }

        .pos-live-inline,
        .pos-mobile-filter-toggle,
        .pos-filter-reset-btn {
            font-size: .64rem;
        }
    }
</style>

<div class="pos-mobile-filter-backdrop" id="mobileFilterBackdrop"></div>
<div class="pos-reservation-detail-backdrop" id="reservationDetailBackdrop"></div>
<div class="pos-reservation-detail-sheet" id="reservationDetailSheet" aria-hidden="true">
    <div class="pos-reservation-detail-head">
        <div>
            <div class="pos-reservation-detail-title" id="reservationDetailTitle"><?= esc($tr('app.reservation_details', 'รายละเอียดการจอง', 'Reservation details')) ?></div>
            <div class="pos-reservation-detail-subtitle" id="reservationDetailSubtitle"><?= esc($tr('app.view_reservation_info', 'ดูข้อมูลการจองของโต๊ะนี้', 'View reservation information for this table')) ?></div>
        </div>
        <button type="button" class="pos-mobile-sheet-close" id="btnCloseReservationDetail" aria-label="<?= esc($tr('app.close', 'ปิด', 'Close')) ?>">×</button>
    </div>

    <div class="pos-reservation-detail-grid">
        <div class="pos-reservation-detail-item">
            <div class="pos-reservation-detail-label"><?= esc($tr('app.table', 'โต๊ะ', 'Table')) ?></div>
            <div class="pos-reservation-detail-value" id="reservationDetailTable">-</div>
        </div>
        <div class="pos-reservation-detail-item">
            <div class="pos-reservation-detail-label"><?= esc($tr('app.status', 'สถานะ', 'Status')) ?></div>
            <div class="pos-reservation-detail-value" id="reservationDetailStatus">-</div>
        </div>
        <div class="pos-reservation-detail-item">
            <div class="pos-reservation-detail-label"><?= esc($tr('app.time', 'เวลา', 'Time')) ?></div>
            <div class="pos-reservation-detail-value" id="reservationDetailTime">-</div>
        </div>
        <div class="pos-reservation-detail-item">
            <div class="pos-reservation-detail-label"><?= esc($tr('app.customer', 'ลูกค้า', 'Customer')) ?></div>
            <div class="pos-reservation-detail-value" id="reservationDetailCustomer">-</div>
        </div>
        <div class="pos-reservation-detail-item">
            <div class="pos-reservation-detail-label"><?= esc($tr('app.party_size', 'จำนวนที่นั่ง', 'Party size')) ?></div>
            <div class="pos-reservation-detail-value" id="reservationDetailPartySize">-</div>
        </div>
        <div class="pos-reservation-detail-item">
            <div class="pos-reservation-detail-label"><?= esc($tr('app.phone', 'เบอร์ติดต่อ', 'Phone')) ?></div>
            <div class="pos-reservation-detail-value" id="reservationDetailPhone">-</div>
        </div>
    </div>

    <div class="pos-reservation-detail-note" id="reservationDetailNote"><?= esc($tr('app.no_additional_notes', 'ไม่มีหมายเหตุเพิ่มเติม', 'No additional notes')) ?></div>

    <div class="pos-reservation-detail-actions">
        <a href="#" class="btn btn-primary" id="reservationDetailOpenTable"><?= esc($tr('app.go_to_table', 'ไปที่โต๊ะ', 'Go to table')) ?></a>
        <button type="button" class="btn btn-outline-secondary" id="reservationDetailCloseAction"><?= esc($tr('app.close', 'ปิด', 'Close')) ?></button>
    </div>
</div>

<div class="card card-soft">
    <div class="card-body">
        <div class="pos-overview-shell">
            <div class="pos-overview-topbar">
                <div>
                    <div class="pos-overview-title"><?= esc($tr('app.pos_title', 'POS', 'POS')) ?></div>
                    <div class="pos-overview-subtitle"><?= esc($tr('app.select_table_to_start_order', 'เลือกโต๊ะเพื่อเริ่มออเดอร์หรือเข้าบิลเดิม', 'Select a table to start a new order or continue an existing bill')) ?></div>
                </div>

                <div class="pos-overview-actions">
                    <div class="pos-overview-search">
                        <span class="pos-overview-search-icon">⌕</span>
                        <input
                            type="text"
                            class="form-control"
                            id="tableSearchInput"
                            placeholder="<?= esc($tr('app.search_table_placeholder', 'ค้นหาโต๊ะ / โซน / ลูกค้าจอง', 'Search table / zone / reservation')) ?>"
                            autocomplete="off"
                        >
                    </div>
                    <button type="button" class="btn btn-outline-secondary pos-overview-search-clear" id="btnClearTableSearch">×</button>
                    <div class="pos-topbar-ops">
                        <button type="button" class="btn btn-outline-secondary pos-mobile-filter-toggle" id="btnToggleMobileFilters"><?= esc($tr('app.filter_data', 'กรองข้อมูล', 'Filter')) ?></button>
                        <button type="button" class="pos-filter-reset-btn" id="btnResetTableFiltersTop"><?= esc($tr('app.reset', 'รีเซ็ต', 'Reset')) ?></button>
                        <div class="pos-live-inline" id="liveRefreshBadge">
                            <span class="pos-live-dot" aria-hidden="true"></span>
                            <span id="liveRefreshLabel"><?= esc($tr('app.live_updates', 'อัปเดตสด', 'Live updates')) ?></span>
                            <span id="liveRefreshCountdown">10s</span>
                        </div>
                        <button type="button" class="btn btn-outline-secondary pos-live-refresh-btn" id="btnManualRefresh"><?= esc($tr('app.refresh', 'รีเฟรช', 'Refresh')) ?></button>
                    </div>
                </div>
            </div>

            <div class="pos-mobile-filters" id="mobileFiltersWrap">
                <div class="pos-mobile-sheet-head">
                    <div class="pos-mobile-sheet-title"><?= esc($tr('app.filter_data', 'กรองข้อมูล', 'Filter')) ?></div>
                    <div class="pos-mobile-sheet-actions">
                        <button type="button" class="pos-filter-reset-btn" id="btnResetTableFiltersSheet"><?= esc($tr('app.reset', 'รีเซ็ต', 'Reset')) ?></button>
                        <button type="button" class="btn btn-outline-secondary pos-live-refresh-btn" id="btnManualRefreshSheet"><?= esc($tr('app.refresh', 'รีเฟรช', 'Refresh')) ?></button>
                        <button type="button" class="pos-mobile-sheet-close" id="btnCloseMobileFilters" aria-label="<?= esc($tr('app.close', 'ปิด', 'Close')) ?>">×</button>
                    </div>
                </div>

                <div class="pos-summary-grid">
                    <?php foreach ($summaryCards as $summary): ?>
                        <div class="pos-summary-card <?= esc($summary['class']) ?> <?= $summary['class'] === 'summary-dark' || $summary['class'] === 'summary-green' || $summary['class'] === 'summary-purple' ? '' : 'pos-summary-card-mobile-secondary' ?>">
                            <div class="pos-summary-value"><?= esc((string) $summary['value']) ?></div>
                            <div class="pos-summary-label"><?= esc($summary['label']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="pos-zone-toolbar">
                    <div class="pos-toolbar-label"><?= esc($tr('app.zone', 'โซน', 'Zone')) ?></div>
                    <div class="pos-zone-chips" id="zoneFilterToolbar">
                        <button type="button" class="pos-chip-btn active" data-zone="all"><?= esc($tr('app.all_zones', 'ทุกโซน', 'All zones')) ?></button>
                        <?php foreach ($zoneMap as $zoneLabel): ?>
                            <button type="button" class="pos-chip-btn" data-zone="<?= esc(mb_strtolower($zoneLabel, 'UTF-8')) ?>"><?= esc($zoneLabel) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pos-filter-toolbar">
                    <div class="pos-filter-toolbar-head">
                        <div class="pos-toolbar-label"><?= esc($tr('app.status', 'สถานะ', 'Status')) ?></div>
                        <button type="button" class="pos-filter-reset-btn" id="btnResetTableFiltersInline"><?= esc($tr('app.reset', 'รีเซ็ต', 'Reset')) ?></button>
                    </div>
                    <div class="pos-filter-chips" id="statusFilterToolbar">
                        <?php foreach ($filterTabs as $index => $filter): ?>
                            <button type="button" class="pos-chip-btn <?= $index === 0 ? 'active' : '' ?>" data-filter="<?= esc($filter['key']) ?>"><?= esc($filter['label']) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="pos-results-meta">
                <div>
                    <div class="pos-results-title"><?= esc($tr('app.table_overview', 'ภาพรวมโต๊ะ', 'Table overview')) ?></div>
                    <div class="pos-results-note" id="tableResultsCounter"><?= esc($tr('app.showing_tables_count', 'กำลังแสดง {count} โต๊ะ', 'Showing {count} tables', ['count' => count($tables)])) ?></div>
                </div>
                <div class="pos-results-actions">
                    <div class="pos-results-note"><?= esc($tr('app.select_table_to_start_order', 'เลือกโต๊ะเพื่อเริ่มออเดอร์หรือเข้าบิลเดิม', 'Select a table to start a new order or continue an existing bill')) ?></div>
                </div>
            </div>

            <div class="pos-empty-result" id="emptyTableState"><?= esc($tr('app.no_tables_match_filter', 'ไม่พบโต๊ะที่ตรงกับเงื่อนไขที่เลือก', 'No tables matched the selected filters')) ?></div>

            <div class="pos-table-grid" id="posTableGrid">
                <?php foreach ($tables as $table): ?>
                    <?php
                        $status = strtolower(trim((string) ($table['status'] ?? 'available')));
                        $isActive = (int) ($table['is_active'] ?? 0) === 1;
                        $isReservationLocked = ! empty($table['reservation_locked']);
                        $hasOpenOrder = ! empty($table['has_open_order']);

                        $reservationData = $table['reservation_data'] ?? null;
                        $minutesLeft = isset($table['reservation_minutes_left']) && $table['reservation_minutes_left'] !== null
                            ? (int) $table['reservation_minutes_left']
                            : null;

                        $reservationDate = '';
                        $reservationTime = '';
                        $reservationDisplayTime = '';
                        $customerName = '';
                        $reservationPhone = '';
                        $reservationNote = '';
                        $reservationSource = '';
                        $reservationPax = null;

                        if (is_array($reservationData)) {
                            $reservationDate = trim((string) ($reservationData['reservation_date'] ?? ''));
                            $reservationTime = trim((string) ($reservationData['reservation_time'] ?? ''));
                            $reservationDisplayTime = $formatReservationTime($reservationDate, $reservationTime);
                            $customerName = trim((string) ($reservationData['customer_name'] ?? ''));
                            $reservationPhone = trim((string) ($reservationData['customer_phone'] ?? ($reservationData['phone'] ?? '')));
                            $reservationNote = trim((string) ($reservationData['notes'] ?? ($reservationData['note'] ?? '')));
                            $reservationSource = trim((string) ($reservationData['source'] ?? ''));
                            if (($reservationData['pax'] ?? null) !== null && $reservationData['pax'] !== '') {
                                $reservationPax = (int) $reservationData['pax'];
                            } elseif (($reservationData['guest_count'] ?? null) !== null && $reservationData['guest_count'] !== '') {
                                $reservationPax = (int) $reservationData['guest_count'];
                            }
                        }

                        $reservationPartyText = $reservationPax !== null && $reservationPax > 0
                            ? $tr('app.party_size_count', '{count} ที่', '{count} seats', ['count' => $reservationPax])
                            : '';
                        $reservationStatusText = '';
                        $reservationBadgeText = '';
                        $reservationIndicatorText = '';
                        $reservationSummaryText = '';
                        $reservationDetailTitle = '';
                        $hasReservationData = $customerName !== '' || $reservationDisplayTime !== '' || $reservationPhone !== '' || $reservationNote !== '' || $reservationPartyText !== '';

                        $zoneLabel = trim((string) lfield($table, 'zone_name', (string) ($table['zone_name'] ?? '')));
                        $bgClass = 'table-bg-default';
                        $statusText = $tr('app.unknown_status', 'ไม่ทราบสถานะ', 'Unknown status');
                        $canOpen = false;
                        $topBadge = '';
                        $actionText = '';
                        $primaryIndicator = '';
                        $metricOneLabel = $tr('app.zone', 'โซน', 'Zone');
                        $metricOneValue = $zoneLabel !== '' ? $zoneLabel : '-';
                        $metricTwoLabel = $tr('app.order_status', 'สถานะบิล', 'Order state');
                        $metricTwoValue = $hasOpenOrder
                            ? $tr('app.open_order_exists', 'มีบิลเปิด', 'Open order')
                            : $tr('app.no_bill_yet', 'ยังไม่มีบิล', 'No bill yet');

                        if (! $isActive || $status === 'disabled' || $status === 'cleaning') {
                            $bgClass = 'table-bg-disabled';
                            $statusText = $tr('app.disabled', 'ปิดใช้งาน', 'Disabled');
                            $canOpen = false;
                            $topBadge = $tr('app.disabled', 'ปิดใช้งาน', 'Disabled');
                            $actionText = $tr('app.table_not_available', 'โต๊ะนี้ยังไม่พร้อมใช้งาน', 'This table is not available');
                            $primaryIndicator = $tr('app.unavailable', 'ใช้งานไม่ได้', 'Unavailable');
                            $metricTwoValue = $tr('app.table_not_available', 'โต๊ะนี้ยังไม่พร้อมใช้งาน', 'This table is not available');
                        } elseif ($isReservationLocked) {
                            $bgClass = 'table-bg-locked';
                            $statusText = $tr('app.reserved', 'จอง', 'Reserved');
                            $canOpen = true;
                            $reservationStatusText = $tr('app.has_reservation', 'มีการจอง', 'Has reservation');
                            $reservationBadgeText = $tr('app.reserved_short', 'จอง', 'Reserved');
                            $topBadge = $reservationBadgeText;
                            $actionText = $hasOpenOrder
                                ? $tr('app.click_to_open_existing_order', 'กดเพื่อเข้าบิลเดิม', 'Tap to open existing bill')
                                : $tr('app.click_to_enter_table', 'กดเพื่อเข้าโต๊ะนี้', 'Tap to enter table');
                            $reservationIndicatorText = $minutesLeft !== null && $minutesLeft > 0
                                ? $formatRemainingMinutesShort($minutesLeft)
                                : $tr('app.waiting_for_reserved_guest', 'รอลูกค้าจอง', 'Waiting for reserved guest');
                            $primaryIndicator = $reservationIndicatorText;
                            $metricTwoValue = $customerName !== '' ? $customerName : ($reservationDisplayTime !== '' ? $reservationDisplayTime : $tr('app.has_reservation', 'มีการจอง', 'Has reservation'));
                            $metricTwoLabel = $customerName !== ''
                                ? $tr('app.customer', 'ลูกค้า', 'Customer')
                                : $tr('app.time', 'เวลา', 'Time');
                            $reservationSummaryText = implode(' · ', array_filter([$reservationDisplayTime, $customerName, $reservationPartyText]));
                            $reservationDetailTitle = $reservationStatusText;
                        } elseif ($status === 'occupied') {
                            $bgClass = 'table-bg-occupied';
                            $statusText = $tr('app.occupied', 'ไม่ว่าง', 'Occupied');
                            $canOpen = true;
                            $topBadge = $hasOpenOrder
                                ? $tr('app.open_order_exists', 'มีบิลเปิด', 'Open order')
                                : $tr('app.in_use', 'กำลังใช้งาน', 'In use');
                            $actionText = $hasOpenOrder
                                ? $tr('app.click_to_open_existing_order', 'กดเพื่อเข้าบิลเดิม', 'Tap to open existing bill')
                                : $tr('app.click_to_enter_table', 'กดเพื่อเข้าโต๊ะนี้', 'Tap to enter table');
                            $primaryIndicator = $hasOpenOrder
                                ? $tr('app.open_order_exists', 'มีบิลเปิด', 'Open order')
                                : $tr('app.in_use', 'กำลังใช้งาน', 'In use');
                        } elseif ($status === 'reserved') {
                            $bgClass = 'table-bg-reserved';
                            $statusText = $tr('app.reserved', 'จอง', 'Reserved');
                            $canOpen = true;
                            $reservationStatusText = $tr('app.has_reservation', 'มีการจอง', 'Has reservation');
                            $reservationBadgeText = $tr('app.reserved_short', 'จอง', 'Reserved');
                            $topBadge = $reservationBadgeText;
                            $actionText = $hasOpenOrder
                                ? $tr('app.click_to_open_existing_order', 'กดเพื่อเข้าบิลเดิม', 'Tap to open existing bill')
                                : $tr('app.click_to_enter_table', 'กดเพื่อเข้าโต๊ะนี้', 'Tap to enter table');
                            $reservationIndicatorText = $minutesLeft !== null && $minutesLeft > 0
                                ? $formatRemainingMinutesShort($minutesLeft)
                                : ($reservationDisplayTime !== ''
                                    ? $tr('app.reservation_at_time', 'จอง {time}', 'Reserved {time}', ['time' => $reservationDisplayTime])
                                    : $tr('app.has_reservation', 'มีการจอง', 'Has reservation'));
                            $primaryIndicator = $reservationIndicatorText;
                            $metricTwoValue = $reservationDisplayTime !== '' ? $reservationDisplayTime : $tr('app.reserved', 'จอง', 'Reserved');
                            $metricTwoLabel = $tr('app.time', 'เวลา', 'Time');
                            $reservationSummaryText = implode(' · ', array_filter([$reservationDisplayTime, $customerName, $reservationPartyText]));
                            $reservationDetailTitle = $reservationStatusText;
                        } elseif ($status === 'available') {
                            $bgClass = 'table-bg-available';
                            $statusText = $tr('app.available', 'ว่าง', 'Available');
                            $canOpen = true;
                            $topBadge = $tr('app.ready_to_use', 'พร้อมใช้งาน', 'Ready to use');
                            $actionText = $tr('app.click_to_open_table', 'กดเพื่อเปิดโต๊ะ', 'Tap to open table');
                            $primaryIndicator = $tr('app.ready_to_use', 'พร้อมใช้งาน', 'Ready to use');
                        } else {
                            $bgClass = 'table-bg-default';
                            $statusText = ucfirst($status);
                            $canOpen = true;
                            $topBadge = $tr('app.available_for_use', 'พร้อมใช้งาน', 'Available for use');
                            $actionText = $hasOpenOrder
                                ? $tr('app.click_to_open_existing_order', 'กดเพื่อเข้าบิลเดิม', 'Tap to open existing bill')
                                : $tr('app.click_to_enter_table', 'กดเพื่อเข้าโต๊ะนี้', 'Tap to enter table');
                            $primaryIndicator = $statusText;
                        }

                        if ($reservationSummaryText === '' && $hasReservationData) {
                            $reservationSummaryText = implode(' · ', array_filter([$reservationDisplayTime, $customerName, $reservationPartyText]));
                        }

                        if ($reservationDetailTitle === '') {
                            $reservationDetailTitle = $reservationStatusText !== '' ? $reservationStatusText : $statusText;
                        }

                        $reservationBadgeClass = ($status === 'reserved' || $isReservationLocked) ? ' pos-reservation-badge-soft' : '';
                        $reservationInlineText = $reservationSummaryText;
                        if ($reservationInlineText === '' && $hasReservationData && $customerName !== '') {
                            $reservationInlineText = $customerName;
                        }

                        $reservationDetailTimeText = trim(implode(' • ', array_filter([
                            $reservationDisplayTime !== '' ? $reservationDisplayTime : $reservationTime,
                            $minutesLeft !== null && $minutesLeft > 0 ? $formatRemainingMinutesShort($minutesLeft) : ''
                        ])));
                        if ($reservationDetailTimeText === '') {
                            $reservationDetailTimeText = '-';
                        }

                        $reservationNoteText = $reservationNote !== ''
                            ? $reservationNote
                            : $tr('app.no_additional_notes', 'ไม่มีหมายเหตุเพิ่มเติม', 'No additional notes');

                        $mobileMetaText = trim((string) ($metricTwoValue !== '' && $metricTwoValue !== '-' ? $metricTwoValue : $metricOneValue));
                        if ($status === 'reserved' || $isReservationLocked) {
                            $mobileMetaText = $customerName !== ''
                                ? trim(implode(' · ', array_filter([$customerName, $reservationDisplayTime, $reservationPartyText])))
                                : ($reservationSummaryText !== ''
                                    ? $reservationSummaryText
                                    : ($reservationDisplayTime !== '' ? $reservationDisplayTime : $mobileMetaText));
                        }
                        if ($mobileMetaText === '') {
                            $mobileMetaText = $zoneLabel !== '' ? $zoneLabel : $tr('app.no_zone', 'ไม่ระบุโซน', 'No zone');
                        }

                        $searchParts = [
                            (string) ($table['table_name'] ?? ''),
                            $zoneLabel,
                            $customerName,
                            $reservationDate,
                            $reservationTime,
                            $statusText,
                            $topBadge,
                        ];
                        $searchText = mb_strtolower(trim(implode(' ', array_filter($searchParts))), 'UTF-8');
                        $zoneSearch = mb_strtolower($zoneLabel, 'UTF-8');

                        $statusFilter = $status;
                        if (! $isActive || $status === 'disabled' || $status === 'cleaning') {
                            $statusFilter = 'disabled';
                        } elseif ($isReservationLocked) {
                            $statusFilter = 'reserved';
                        }

                        $sortPriority = 90;
                        if (! $isActive || $status === 'disabled' || $status === 'cleaning') {
                            $sortPriority = 90;
                        } elseif ($isReservationLocked) {
                            $sortPriority = 10;
                        } elseif ($status === 'reserved') {
                            $sortPriority = 20;
                        } elseif ($hasOpenOrder) {
                            $sortPriority = 30;
                        } elseif ($status === 'occupied') {
                            $sortPriority = 40;
                        } elseif ($status === 'available') {
                            $sortPriority = 50;
                        }

                        $updatedAtRaw = trim((string) ($table['updated_at'] ?? ''));
                        $updatedAtTs = $updatedAtRaw !== '' ? strtotime($updatedAtRaw) : 0;
                        if ($updatedAtTs === false) {
                            $updatedAtTs = 0;
                        }

                        $attrs = sprintf(
                            'data-table-id="%s" data-search="%s" data-zone="%s" data-status="%s" data-open-order="%s" data-locked="%s" data-sort-priority="%s" data-updated-at="%s" data-name-order="%s" data-reservation-minutes="%s"',
                            (string) ($table['id'] ?? 0),
                            esc($searchText),
                            esc($zoneSearch),
                            esc($statusFilter),
                            $hasOpenOrder ? '1' : '0',
                            $isReservationLocked ? '1' : '0',
                            (string) $sortPriority,
                            (string) $updatedAtTs,
                            esc(mb_strtolower((string) ($table['table_name'] ?? ''), 'UTF-8')),
                            $minutesLeft !== null ? (string) $minutesLeft : ''
                        );

                        $cardClass = 'pos-table-card ' . $bgClass . ($canOpen ? '' : ' disabled-link');
                        $indicatorText = $primaryIndicator !== '' ? $primaryIndicator : $statusText;
                        $indicatorTextMobile = $indicatorText;
                        if (($status === 'reserved' || $isReservationLocked) && $minutesLeft !== null) {
                            $compactIndicator = $formatRemainingMinutesCompact($minutesLeft);
                            if ($compactIndicator !== '') {
                                $indicatorTextMobile = $compactIndicator;
                            }
                        }
                        $reservationRoute = site_url('pos/table/' . $table['id']);
                    ?>

                    <?php
                        $signalItems = [];

                        if ($hasOpenOrder) {
                            $currentOrderStatus = strtolower(trim((string) ($table['current_order_status'] ?? 'open')));
                            $currentOrderTotal = (float) ($table['current_order_total'] ?? 0);
                            $paidTotal = (float) ($table['payment_paid_total'] ?? 0);

                            if ($currentOrderStatus === 'billing') {
                                $signalItems[] = [
                                    'tone' => 'amber',
                                    'label' => $tr('app.waiting_payment_signal', 'รอชำระเงิน', 'Waiting payment'),
                                ];
                            } elseif (! empty($table['payment_partial']) && $currentOrderTotal > 0) {
                                $signalItems[] = [
                                    'tone' => 'violet',
                                    'label' => $tr('app.partial_payment_signal', 'ชำระบางส่วน', 'Partial payment'),
                                ];
                            } else {
                                $signalItems[] = [
                                    'tone' => 'blue',
                                    'label' => $tr('app.open_bill_signal', 'มีบิลเปิด', 'Open bill'),
                                ];
                            }

                            $readyCount = (int) ($table['kitchen_ready_count'] ?? 0);
                            $preparingCount = (int) ($table['kitchen_preparing_count'] ?? 0);
                            $sentCount = (int) ($table['kitchen_sent_count'] ?? 0);
                            $pendingCount = (int) ($table['kitchen_pending_count'] ?? 0);
                            $cancelRequestCount = (int) ($table['kitchen_cancel_request_count'] ?? 0);
                            $servedCount = (int) ($table['kitchen_served_count'] ?? 0);
                            $remainingCount = (int) ($table['kitchen_remaining_count'] ?? 0);
                            $servedPartial = ! empty($table['kitchen_served_partial']);
                            $servedAll = ! empty($table['kitchen_served_all']);

                            if (! $servedAll && ! $servedPartial && $servedCount > 0) {
                                if ($remainingCount > 0) {
                                    $servedPartial = true;
                                } else {
                                    $servedAll = true;
                                }
                            }
                            $mergeInCount = (int) ($table['merge_in_count'] ?? 0);
                            $moveInCount = (int) ($table['move_in_count'] ?? 0);
                            $wasReopened = ! empty($table['was_reopened']);

                            if ($readyCount > 0) {
                                $signalItems[] = [
                                    'tone' => 'emerald',
                                    'label' => $tr('app.ready_to_serve_signal', 'พร้อมเสิร์ฟ {count}', 'Ready {count}', ['count' => $readyCount]),
                                ];
                            } elseif ($preparingCount > 0) {
                                $signalItems[] = [
                                    'tone' => 'amber',
                                    'label' => $tr('app.preparing_signal', 'กำลังทำ {count}', 'Preparing {count}', ['count' => $preparingCount]),
                                ];
                            } elseif ($sentCount > 0) {
                                $signalItems[] = [
                                    'tone' => 'blue',
                                    'label' => $tr('app.sent_to_kitchen_signal', 'ส่งครัวแล้ว {count}', 'Sent to kitchen {count}', ['count' => $sentCount]),
                                ];
                            } elseif ($pendingCount > 0) {
                                $signalItems[] = [
                                    'tone' => 'slate',
                                    'label' => $tr('app.pending_kitchen_signal', 'รอส่งครัว {count}', 'Pending kitchen {count}', ['count' => $pendingCount]),
                                ];
                            }

                            if ($cancelRequestCount > 0) {
                                $signalItems[] = [
                                    'tone' => 'rose',
                                    'label' => $tr('app.cancel_request_signal', 'รอยืนยันยกเลิก {count}', 'Cancel request {count}', ['count' => $cancelRequestCount]),
                                ];
                            }

                            if ($servedAll) {
                                $signalItems[] = [
                                    'tone' => 'emerald',
                                    'label' => $tr('app.served_complete_signal', 'เสิร์ฟครบแล้ว {count}', 'Served all {count}', ['count' => $servedCount]),
                                ];
                            } elseif ($servedPartial) {
                                $signalItems[] = [
                                    'tone' => 'teal',
                                    'label' => $tr('app.served_partial_signal', 'เสิร์ฟแล้ว {served} / คงเหลือ {remaining}', 'Served {served} / Remaining {remaining}', ['served' => $servedCount, 'remaining' => $remainingCount]),
                                ];
                            }

                            if ($mergeInCount > 0) {
                                $signalItems[] = [
                                    'tone' => 'violet',
                                    'label' => $tr('app.merged_signal', 'รวมมาจาก {count} โต๊ะ', 'Merged from {count}', ['count' => $mergeInCount]),
                                ];
                            }

                            if ($moveInCount > 0) {
                                $signalItems[] = [
                                    'tone' => 'slate',
                                    'label' => $tr('app.moved_signal', 'ย้ายโต๊ะแล้ว', 'Moved table'),
                                ];
                            }

                            if ($wasReopened) {
                                $signalItems[] = [
                                    'tone' => 'violet',
                                    'label' => $tr('app.reopened_signal', 'Reopen แล้ว', 'Reopened'),
                                ];
                            }
                        }
                    ?>

                    <?php ob_start(); ?>
                    <div class="pos-table-card-head">
                        <div>
                            <div class="pos-table-name" title="<?= esc($table['table_name'] ?? '-') ?>"><?= esc($table['table_name'] ?? '-') ?></div>
                            <div class="pos-table-zone"><?= esc($zoneLabel !== '' ? $zoneLabel : $tr('app.no_zone', 'ไม่ระบุโซน', 'No zone')) ?></div>
                            <div class="pos-table-mobile-meta"><?= esc($mobileMetaText) ?></div>
                        </div>
                        <span class="pos-table-top-badge<?= esc($reservationBadgeClass) ?>"><?= esc($topBadge) ?></span>
                    </div>

                    <div class="pos-table-body">
                        <div class="pos-table-status-row">
                            <span class="pos-status-pill"><?= esc($tr('app.status', 'สถานะ', 'Status')) ?>: <?= esc($statusText) ?></span>
                            <?php if ($hasOpenOrder): ?>
                                <span class="pos-meta-pill"><?= esc($tr('app.open_order_exists', 'มีบิลเปิด', 'Open order')) ?></span>
                            <?php endif; ?>
                            <?php if ($status === 'reserved' || $isReservationLocked): ?>
                                <span class="pos-meta-pill"><?= esc($tr('app.has_reservation', 'มีการจอง', 'Has reservation')) ?></span>
                            <?php endif; ?>
                            <?php if ($reservationInlineText !== ''): ?>
                                <span class="pos-reservation-inline-note"><?= esc($reservationInlineText) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (! empty($signalItems)): ?>
                            <div class="pos-table-signals">
                                <?php foreach ($signalItems as $signalItem): ?>
                                    <span class="pos-table-signal pos-table-signal--<?= esc((string) ($signalItem['tone'] ?? 'slate')) ?>" title="<?= esc((string) ($signalItem['label'] ?? '')) ?>">
                                        <span class="pos-table-signal-text"><?= esc((string) ($signalItem['label'] ?? '')) ?></span>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="pos-table-metrics">
                            <div class="pos-table-metric">
                                <div class="pos-table-metric-label"><?= esc($metricOneLabel) ?></div>
                                <div class="pos-table-metric-value"><?= esc($metricOneValue) ?></div>
                            </div>
                            <div class="pos-table-metric">
                                <div class="pos-table-metric-label"><?= esc($metricTwoLabel) ?></div>
                                <div class="pos-table-metric-value"><?= esc($metricTwoValue) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="pos-table-foot">
                        <div class="pos-table-action"><?= esc($actionText) ?></div>
                        <?php if ($hasReservationData): ?>
                            <button
                                type="button"
                                class="pos-reservation-trigger"
                                data-reservation-trigger="1"
                                data-table-name="<?= esc($table['table_name'] ?? '-') ?>"
                                data-status-label="<?= esc($reservationDetailTitle) ?>"
                                data-time-label="<?= esc($reservationDetailTimeText) ?>"
                                data-customer-label="<?= esc($customerName !== '' ? $customerName : '-') ?>"
                                data-party-label="<?= esc($reservationPartyText !== '' ? $reservationPartyText : '-') ?>"
                                data-phone-label="<?= esc($reservationPhone !== '' ? $reservationPhone : '-') ?>"
                                data-note-label="<?= esc($reservationNoteText) ?>"
                                data-open-href="<?= esc($reservationRoute) ?>"
                            ><?= esc($tr('app.view_details', 'ดูรายละเอียด', 'View')) ?></button>
                        <?php endif; ?>
                        <div class="pos-table-indicator"><span class="pos-indicator-desktop"><?= esc($indicatorText) ?></span><span class="pos-indicator-mobile"><?= esc($indicatorTextMobile) ?></span></div>
                    </div>
                    <?php $cardInnerHtml = trim((string) ob_get_clean()); ?>

                    <?php if ($canOpen): ?>
                        <div
                            class="<?= esc($cardClass) ?> pos-table-card-clickable"
                            <?= $attrs ?>
                            data-href="<?= esc(site_url('pos/table/' . $table['id'])) ?>"
                            role="link"
                            tabindex="0"
                            aria-label="<?= esc($tr('app.open_table_named', 'เปิดโต๊ะ {table}', 'Open table {table}', ['table' => (string) ($table['table_name'] ?? '-')])) ?>"
                        >
                            <?= $cardInnerHtml ?>
                        </div>
                    <?php else: ?>
                        <div class="<?= esc($cardClass) ?>" <?= $attrs ?>>
                            <?= $cardInnerHtml ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const STORAGE_KEY = 'pos_overview_state_v1';
        const REFRESH_SECONDS = 3;
        const CHANGE_CHECK_URL = <?= json_encode(site_url('pos/overview-changes')) ?>;
        const GRID_PARTIAL_URL = <?= json_encode(site_url('pos/overview-grid-partial')) ?>;

        const searchInput = document.getElementById('tableSearchInput');
        const clearButton = document.getElementById('btnClearTableSearch');
        const zoneToolbar = document.getElementById('zoneFilterToolbar');
        const statusToolbar = document.getElementById('statusFilterToolbar');
        const mobileFiltersWrap = document.getElementById('mobileFiltersWrap');
        const mobileToggleButton = document.getElementById('btnToggleMobileFilters');
        const mobileFilterBackdrop = document.getElementById('mobileFilterBackdrop');
        const closeMobileFiltersButton = document.getElementById('btnCloseMobileFilters');
        const reservationDetailBackdrop = document.getElementById('reservationDetailBackdrop');
        const reservationDetailSheet = document.getElementById('reservationDetailSheet');
        const reservationDetailTable = document.getElementById('reservationDetailTable');
        const reservationDetailStatus = document.getElementById('reservationDetailStatus');
        const reservationDetailTime = document.getElementById('reservationDetailTime');
        const reservationDetailCustomer = document.getElementById('reservationDetailCustomer');
        const reservationDetailPartySize = document.getElementById('reservationDetailPartySize');
        const reservationDetailPhone = document.getElementById('reservationDetailPhone');
        const reservationDetailNote = document.getElementById('reservationDetailNote');
        const reservationDetailOpenTable = document.getElementById('reservationDetailOpenTable');
        const closeReservationDetailButton = document.getElementById('btnCloseReservationDetail');
        const closeReservationDetailAction = document.getElementById('reservationDetailCloseAction');
        const counter = document.getElementById('tableResultsCounter');
        const emptyState = document.getElementById('emptyTableState');
        const tableGrid = document.getElementById('posTableGrid');
        const liveRefreshBadge = document.getElementById('liveRefreshBadge');
        const liveRefreshLabel = document.getElementById('liveRefreshLabel');
        const liveRefreshCountdown = document.getElementById('liveRefreshCountdown');
        const manualRefreshButton = document.getElementById('btnManualRefresh');
        const manualRefreshSheetButton = document.getElementById('btnManualRefreshSheet');
        const resetTopButton = document.getElementById('btnResetTableFiltersTop');
        const resetInlineButton = document.getElementById('btnResetTableFiltersInline');
        const resetSheetButton = document.getElementById('btnResetTableFiltersSheet');
        const mobileResultsCounterChip = document.getElementById('mobileResultsCounterChip');
        const showingTemplate = <?= json_encode($tr('app.showing_tables_count', 'กำลังแสดง {count} โต๊ะ', 'Showing {count} tables')) ?>;
        const syncingText = <?= json_encode($tr('app.live_short', 'สด', 'Live')) ?>;
        const liveText = <?= json_encode($tr('app.live_short', 'สด', 'Live')) ?>;

        let activeZone = 'all';
        let activeStatus = 'all';
        let countdown = REFRESH_SECONDS;
        let refreshTimer = null;
        let isRefreshing = false;
        let isTyping = false;
        let typingTimer = null;
        let lastVersion = '';
        let lastCursor = '';
        let pendingRefresh = false;
        let interactionReleaseTimer = null;
        let interactionHoldUntil = 0;
        let rotateSyncTimer = null;

        function getCards() {
            return Array.from(document.querySelectorAll('#posTableGrid .pos-table-card'));
        }

        function saveState() {
            try {
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                    search: searchInput ? searchInput.value : '',
                    zone: activeZone,
                    status: activeStatus
                }));
            } catch (error) {}
        }

        function restoreState() {
            try {
                const raw = sessionStorage.getItem(STORAGE_KEY);
                if (!raw) return;
                const state = JSON.parse(raw);
                if (searchInput && typeof state.search === 'string') {
                    searchInput.value = state.search;
                }
                if (typeof state.zone === 'string' && state.zone !== '') {
                    activeZone = state.zone;
                }
                if (typeof state.status === 'string' && state.status !== '') {
                    activeStatus = state.status;
                }
            } catch (error) {}
        }

        function triggerPendingRefreshIfReady(force = false) {
            if (!pendingRefresh || shouldDeferRealtime() || isRefreshing) return;
            pendingRefresh = false;
            checkOverviewChanges(force);
        }

        function markInteraction(delay = 1400) {
            interactionHoldUntil = Date.now() + delay;
            if (interactionReleaseTimer) {
                window.clearTimeout(interactionReleaseTimer);
            }
            interactionReleaseTimer = window.setTimeout(function () {
                if (!shouldDeferRealtime()) {
                    triggerPendingRefreshIfReady(true);
                }
            }, Math.max(delay, 120));
        }

        function isInteractionPaused() {
            return Date.now() < interactionHoldUntil;
        }

        function closeMobileFilters() {
            if (!mobileFiltersWrap) return;
            mobileFiltersWrap.classList.remove('show');
            mobileFilterBackdrop?.classList.remove('show');
            if (!reservationDetailSheet?.classList.contains('show')) {
                document.body.classList.remove('overflow-hidden');
            }
            markInteraction(280);
            window.setTimeout(function () {
                triggerPendingRefreshIfReady(true);
            }, 320);
        }

        function closeReservationDetail() {
            reservationDetailSheet?.classList.remove('show');
            reservationDetailBackdrop?.classList.remove('show');
            reservationDetailSheet?.setAttribute('aria-hidden', 'true');
            if (!mobileFiltersWrap?.classList.contains('show')) {
                document.body.classList.remove('overflow-hidden');
            }
            markInteraction(240);
            window.setTimeout(function () {
                triggerPendingRefreshIfReady(true);
            }, 280);
        }

        function openReservationDetail(trigger) {
            if (!trigger || !reservationDetailSheet) return;
            markInteraction(1800);

            reservationDetailTable.textContent = trigger.getAttribute('data-table-name') || '-';
            reservationDetailStatus.textContent = trigger.getAttribute('data-status-label') || '-';
            reservationDetailTime.textContent = trigger.getAttribute('data-time-label') || '-';
            reservationDetailCustomer.textContent = trigger.getAttribute('data-customer-label') || '-';
            reservationDetailPartySize.textContent = trigger.getAttribute('data-party-label') || '-';
            reservationDetailPhone.textContent = trigger.getAttribute('data-phone-label') || '-';
            reservationDetailNote.textContent = trigger.getAttribute('data-note-label') || '-';
            reservationDetailOpenTable.setAttribute('href', trigger.getAttribute('data-open-href') || '#');

            reservationDetailSheet.classList.add('show');
            reservationDetailBackdrop?.classList.add('show');
            reservationDetailSheet.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        }

        function openMobileFilters() {
            if (!mobileFiltersWrap || window.innerWidth > 767.98) return;
            markInteraction(1800);
            mobileFiltersWrap.classList.add('show');
            mobileFilterBackdrop?.classList.add('show');
            document.body.classList.add('overflow-hidden');
        }

        function syncMobileFilterState() {
            if (!mobileFiltersWrap) return;

            mobileFiltersWrap.classList.add('no-transition');
            mobileFilterBackdrop?.classList.add('no-transition');

            if (window.innerWidth > 767.98) {
                mobileFiltersWrap.classList.remove('show');
                mobileFilterBackdrop?.classList.remove('show');
                if (!reservationDetailSheet?.classList.contains('show')) {
                    document.body.classList.remove('overflow-hidden');
                }
            } else if (!mobileFiltersWrap.classList.contains('show')) {
                mobileFiltersWrap.classList.remove('show');
                mobileFilterBackdrop?.classList.remove('show');
                if (!reservationDetailSheet?.classList.contains('show')) {
                    document.body.classList.remove('overflow-hidden');
                }
            }

            window.clearTimeout(rotateSyncTimer);
            rotateSyncTimer = window.setTimeout(function () {
                mobileFiltersWrap.classList.remove('no-transition');
                mobileFilterBackdrop?.classList.remove('no-transition');
            }, 80);
        }

        function setActiveButton(toolbar, value, attribute) {
            if (!toolbar) return;
            toolbar.querySelectorAll('[data-' + attribute + ']').forEach((button) => {
                button.classList.toggle('active', button.getAttribute('data-' + attribute) === value);
            });
        }

        function updateCounter(count) {
            const text = showingTemplate.replace('{count}', String(count));
            if (counter) {
                counter.textContent = text;
            }
            if (mobileResultsCounterChip) {
                mobileResultsCounterChip.textContent = text;
            }
        }

        function setRefreshingState(refreshing) {
            isRefreshing = refreshing;
            liveRefreshBadge?.classList.toggle('syncing', refreshing);
            if (liveRefreshLabel) {
                liveRefreshLabel.textContent = refreshing ? syncingText : liveText;
            }
            if (manualRefreshButton) {
                manualRefreshButton.disabled = refreshing;
            }
            if (manualRefreshSheetButton) {
                manualRefreshSheetButton.disabled = refreshing;
            }
        }

        function resetCountdown() {
            countdown = REFRESH_SECONDS;
            if (liveRefreshCountdown) {
                liveRefreshCountdown.textContent = countdown + 's';
            }
        }

        function shouldDeferRealtime() {
            return document.hidden
                || isTyping
                || isRefreshing
                || isInteractionPaused()
                || !!reservationDetailSheet?.classList.contains('show')
                || (window.innerWidth <= 767.98 && !!mobileFiltersWrap?.classList.contains('show'));
        }

        function parseHtmlDocument(html) {
            const parser = new DOMParser();
            return parser.parseFromString(html, 'text/html');
        }

        function highlightChangedCards(tableIds = []) {
            tableIds.forEach((tableId) => {
                const card = document.querySelector('#posTableGrid .pos-table-card[data-table-id="' + CSS.escape(String(tableId)) + '"]');
                if (!card) return;
                card.classList.remove('pos-table-card-updated');
                void card.offsetWidth;
                card.classList.add('pos-table-card-updated');
                window.setTimeout(() => card.classList.remove('pos-table-card-updated'), 950);
            });
        }

        function replaceChangedCardsFromDoc(doc, changedTableIds = []) {
            const nextGrid = doc.getElementById('posTableGrid');
            if (!nextGrid || !tableGrid) return false;

            if (!Array.isArray(changedTableIds) || changedTableIds.length === 0) {
                if (tableGrid.innerHTML !== nextGrid.innerHTML) {
                    tableGrid.innerHTML = nextGrid.innerHTML;
                    return true;
                }
                return false;
            }

            const currentIds = Array.from(tableGrid.querySelectorAll('.pos-table-card[data-table-id]')).map((card) => String(card.getAttribute('data-table-id') || ''));
            const nextIds = Array.from(nextGrid.querySelectorAll('.pos-table-card[data-table-id]')).map((card) => String(card.getAttribute('data-table-id') || ''));
            const normalizedChangedIds = changedTableIds.map((tableId) => String(tableId));
            const needsFullReplace = currentIds.length !== nextIds.length
                || currentIds.some((id, index) => id !== nextIds[index])
                || normalizedChangedIds.some((tableId) => !tableGrid.querySelector('.pos-table-card[data-table-id="' + CSS.escape(tableId) + '"]') || !nextGrid.querySelector('.pos-table-card[data-table-id="' + CSS.escape(tableId) + '"]'));

            if (needsFullReplace) {
                if (tableGrid.innerHTML !== nextGrid.innerHTML) {
                    tableGrid.innerHTML = nextGrid.innerHTML;
                    return true;
                }
                return false;
            }

            let replaced = 0;
            normalizedChangedIds.forEach((tableId) => {
                const selector = '.pos-table-card[data-table-id="' + CSS.escape(tableId) + '"]';
                const currentCard = tableGrid.querySelector(selector);
                const nextCard = nextGrid.querySelector(selector);
                if (!currentCard || !nextCard) return;
                if (currentCard.outerHTML !== nextCard.outerHTML) {
                    currentCard.replaceWith(nextCard.cloneNode(true));
                    replaced += 1;
                }
            });

            return replaced > 0;
        }

        function sortVisibleCards() {
            return;
        }

        function applyFilters() {
            const keyword = (searchInput?.value || '').trim().toLowerCase();
            let visibleCount = 0;

            getCards().forEach((card) => {
                const searchText = (card.getAttribute('data-search') || '').toLowerCase();
                const zone = (card.getAttribute('data-zone') || '').toLowerCase();
                const status = (card.getAttribute('data-status') || '').toLowerCase();
                const hasOpenOrder = card.getAttribute('data-open-order') === '1';
                const isLocked = card.getAttribute('data-locked') === '1';

                const matchKeyword = keyword === '' || searchText.includes(keyword);
                const matchZone = activeZone === 'all' || zone === activeZone;

                let matchStatus = activeStatus === 'all' || status === activeStatus;
                if (activeStatus === 'open_order') {
                    matchStatus = hasOpenOrder;
                }

                const visible = matchKeyword && matchZone && matchStatus;
                card.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });

            sortVisibleCards();
            updateCounter(visibleCount);
            if (emptyState) {
                emptyState.classList.toggle('show', visibleCount === 0);
            }
            saveState();
        }

        function resetAllFilters(options = {}) {
            const shouldFocusSearch = options.focusSearch === true;

            activeZone = 'all';
            activeStatus = 'all';

            if (searchInput) {
                searchInput.value = '';
                if (shouldFocusSearch) {
                    searchInput.focus();
                }
            }

            setActiveButton(zoneToolbar, activeZone, 'zone');
            setActiveButton(statusToolbar, activeStatus, 'filter');
            applyFilters();
            resetCountdown();
        }

        async function refreshGridPartial(changedTableIds = []) {
            if (shouldDeferRealtime()) {
                pendingRefresh = true;
                return;
            }

            setRefreshingState(true);
            saveState();

            try {
                const response = await fetch(GRID_PARTIAL_URL + '?_ts=' + Date.now(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-POS-Overview-Grid': '1'
                    },
                    credentials: 'same-origin',
                    cache: 'no-store'
                });

                if (!response.ok) throw new Error('grid_refresh_failed');

                const html = await response.text();
                const doc = parseHtmlDocument('<div id="posTableGrid">' + html + '</div>');
                const replaced = replaceChangedCardsFromDoc(doc, changedTableIds);

                if (!replaced) {
                    const nextGrid = doc.getElementById('posTableGrid');
                    if (nextGrid && tableGrid && tableGrid.innerHTML !== nextGrid.innerHTML) {
                        tableGrid.innerHTML = nextGrid.innerHTML;
                    }
                }

                applyFilters();
                highlightChangedCards(changedTableIds);
                resetCountdown();
                pendingRefresh = false;
            } catch (error) {
                pendingRefresh = true;
                resetCountdown();
            } finally {
                setRefreshingState(false);
            }
        }

        async function checkOverviewChanges(force = false) {
            if (shouldDeferRealtime()) {
                pendingRefresh = true;
                return;
            }

            if (isRefreshing) return;
            setRefreshingState(true);

            try {
                const query = new URLSearchParams({
                    _ts: String(Date.now()),
                    version: lastVersion,
                    cursor: lastCursor
                });

                const response = await fetch(CHANGE_CHECK_URL + '?' + query.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-POS-Overview-Changes': '1'
                    },
                    credentials: 'same-origin',
                    cache: 'no-store'
                });

                if (!response.ok) throw new Error('change_check_failed');

                const payload = await response.json();
                const hasChanges = force || payload.has_changes === true;
                lastVersion = String(payload.version || lastVersion || '');
                lastCursor = String(payload.cursor || lastCursor || '');
                const changedTableIds = Array.isArray(payload.changed_table_ids) ? payload.changed_table_ids : [];

                setRefreshingState(false);

                if (hasChanges) {
                    await refreshGridPartial(changedTableIds);
                } else {
                    pendingRefresh = false;
                    resetCountdown();
                }
            } catch (error) {
                setRefreshingState(false);
                await refreshGridPartial([]);
            }
        }

        function startRefreshTicker() {
            resetCountdown();

            if (refreshTimer) {
                window.clearInterval(refreshTimer);
            }

            refreshTimer = window.setInterval(function () {
                if (shouldDeferRealtime()) {
                    if (liveRefreshCountdown) {
                        liveRefreshCountdown.textContent = Math.max(countdown, 0) + 's';
                    }
                    return;
                }
                countdown -= 1;
                if (liveRefreshCountdown) {
                    liveRefreshCountdown.textContent = Math.max(countdown, 0) + 's';
                }
                if (countdown <= 0) {
                    checkOverviewChanges();
                }
            }, 1000);
        }

        restoreState();
        syncMobileFilterState();
        setActiveButton(zoneToolbar, activeZone, 'zone');
        setActiveButton(statusToolbar, activeStatus, 'filter');
        applyFilters();
        startRefreshTicker();
        checkOverviewChanges(true);

        tableGrid?.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-reservation-trigger="1"]');
            if (trigger) {
                event.preventDefault();
                event.stopPropagation();
                openReservationDetail(trigger);
                return;
            }

            const clickableCard = event.target.closest('.pos-table-card-clickable[data-href]');
            if (!clickableCard) return;
            if (event.target.closest('button, a, input, select, textarea, label')) return;

            const href = clickableCard.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        });

        tableGrid?.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') return;

            const clickableCard = event.target.closest('.pos-table-card-clickable[data-href]');
            if (!clickableCard) return;
            if (event.target.closest('[data-reservation-trigger="1"]')) return;

            event.preventDefault();
            const href = clickableCard.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        });

        closeReservationDetailButton?.addEventListener('click', closeReservationDetail);
        closeReservationDetailAction?.addEventListener('click', closeReservationDetail);
        reservationDetailBackdrop?.addEventListener('click', closeReservationDetail);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMobileFilters();
                closeReservationDetail();
            }
        });

        [zoneToolbar, statusToolbar, mobileFiltersWrap, tableGrid, reservationDetailSheet].forEach((element) => {
            element?.addEventListener('pointerdown', function () {
                markInteraction(1200);
            }, { passive: true });
        });

        [window, tableGrid, mobileFiltersWrap].forEach((element) => {
            element?.addEventListener('scroll', function () {
                markInteraction(1000);
            }, { passive: true });
        });

        window.addEventListener('wheel', function () {
            markInteraction(900);
        }, { passive: true });

        document.addEventListener('focusin', function (event) {
            if (event.target && event.target.closest('#mobileFiltersWrap, #reservationDetailSheet, #tableSearchInput')) {
                markInteraction(1200);
            }
        });

        mobileToggleButton?.addEventListener('click', function () {
            if (!mobileFiltersWrap || window.innerWidth > 767.98) return;
            if (mobileFiltersWrap.classList.contains('show')) {
                closeMobileFilters();
            } else {
                openMobileFilters();
            }
        });

        closeMobileFiltersButton?.addEventListener('click', closeMobileFilters);
        mobileFilterBackdrop?.addEventListener('click', closeMobileFilters);

        zoneToolbar?.addEventListener('click', function (event) {
            const button = event.target.closest('[data-zone]');
            if (!button) return;
            markInteraction(700);
            activeZone = button.getAttribute('data-zone') || 'all';
            setActiveButton(zoneToolbar, activeZone, 'zone');
            applyFilters();
            resetCountdown();
        });

        statusToolbar?.addEventListener('click', function (event) {
            const button = event.target.closest('[data-filter]');
            if (!button) return;
            markInteraction(700);
            activeStatus = button.getAttribute('data-filter') || 'all';
            setActiveButton(statusToolbar, activeStatus, 'filter');
            applyFilters();
            resetCountdown();
        });

        searchInput?.addEventListener('input', function () {
            isTyping = true;
            markInteraction(1200);
            window.clearTimeout(typingTimer);
            typingTimer = window.setTimeout(function () {
                isTyping = false;
                resetCountdown();
            }, 900);
            applyFilters();
        });

        searchInput?.addEventListener('focus', function () {
            isTyping = true;
        });

        searchInput?.addEventListener('blur', function () {
            window.clearTimeout(typingTimer);
            typingTimer = window.setTimeout(function () {
                isTyping = false;
                resetCountdown();
            }, 250);
        });

        clearButton?.addEventListener('click', function () {
            if (!searchInput) return;
            markInteraction(700);
            searchInput.value = '';
            searchInput.focus();
            applyFilters();
            resetCountdown();
        });

        resetTopButton?.addEventListener('click', function () {
            markInteraction(700);
            resetAllFilters({ focusSearch: true });
        });

        resetInlineButton?.addEventListener('click', function () {
            markInteraction(700);
            resetAllFilters();
        });

        resetSheetButton?.addEventListener('click', function () {
            markInteraction(700);
            resetAllFilters();
        });

        manualRefreshButton?.addEventListener('click', function () {
            markInteraction(400);
            checkOverviewChanges(true);
        });
        manualRefreshSheetButton?.addEventListener('click', function () {
            markInteraction(400);
            checkOverviewChanges(true);
        });

        window.addEventListener('resize', function () {
            markInteraction(320);
            syncMobileFilterState();
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                resetCountdown();
                checkOverviewChanges(true);
            }
        });

        window.setTimeout(function () {
            triggerPendingRefreshIfReady(true);
        }, 120);
    })();
</script>

<?= $this->endSection() ?>
