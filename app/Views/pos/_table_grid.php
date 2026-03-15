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
?>

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
                            $reservationSummaryText = implode(' • ', array_filter([$reservationDisplayTime, $customerName, $reservationPartyText]));
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
                            $reservationSummaryText = implode(' • ', array_filter([$reservationDisplayTime, $customerName, $reservationPartyText]));
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
                            $reservationSummaryText = implode(' • ', array_filter([$reservationDisplayTime, $customerName, $reservationPartyText]));
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
                            (string) ((int) $updatedAtTs),
                            esc(mb_strtolower((string) ($table['table_name'] ?? ''), 'UTF-8')),
                            (string) ($minutesLeft !== null ? $minutesLeft : 9999)
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
                        $flowCounts = [];
                        $primaryFlowTone = 'slate';
                        $primaryFlowLabel = $tr('app.open_order_exists', 'มีบิลเปิด', 'Open order');
                        $servedCount = 0;
                        $remainingCount = 0;
                        $servedTotalCount = 0;
                        $servedPercent = 0;
                        $servedPartial = false;
                        $servedAll = false;
                        $servedItemNames = [];
                        $servedTooltip = '';
                        $servedProgressLabel = $tr('app.served_progress', 'ความคืบหน้าการเสิร์ฟ', 'Serving progress');
                        $servedProgressText = '';
                        $servedProgressNote = '';
                        $servedProgressNoteClass = 'pos-served-progress-note';
                        $servedLastAt = trim((string) ($table['kitchen_last_served_at'] ?? ''));
                        $overviewNextFocus = trim((string) ($table['kitchen_focus_label'] ?? ''));
                        $overviewNextFocusText = '';
                        $overviewServedNamesText = '';
                        $readyCount = 0;
                        $preparingCount = 0;
                        $sentCount = 0;
                        $pendingCount = 0;
                        $cancelRequestCount = 0;

                        if ($hasOpenOrder) {
                            $currentOrderStatus = strtolower(trim((string) ($table['current_order_status'] ?? 'open')));
                            $currentOrderTotal = (float) ($table['current_order_total'] ?? 0);

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
                            $servedCount = (int) ($table['kitchen_served_count'] ?? ($table['served_count'] ?? 0));
                            $remainingCount = (int) ($table['kitchen_remaining_count'] ?? 0);
                            $servedTotalCount = (int) ($table['kitchen_total_item_count'] ?? ($servedCount + $remainingCount));
                            $servedPercent = (int) ($table['kitchen_served_percent'] ?? 0);
                            $servedPartial = ! empty($table['kitchen_served_partial']);
                            $servedAll = ! empty($table['kitchen_served_all']);
                            $servedItemNames = array_values(array_filter(is_array($table['kitchen_served_item_names'] ?? null) ? $table['kitchen_served_item_names'] : []));
                            $mergeInCount = (int) ($table['merge_in_count'] ?? 0);
                            $moveInCount = (int) ($table['move_in_count'] ?? 0);
                            $wasReopened = ! empty($table['was_reopened']);

                            if (! $servedAll && ! $servedPartial && $servedCount > 0) {
                                $activeKitchenCount = $readyCount + $preparingCount + $sentCount + $pendingCount + $cancelRequestCount;
                                if ($remainingCount > 0 || $activeKitchenCount > 0) {
                                    $servedPartial = true;
                                    if ($remainingCount <= 0) {
                                        $remainingCount = $activeKitchenCount;
                                    }
                                } else {
                                    $servedAll = true;
                                }
                            }

                            if ($servedTotalCount <= 0 && ($servedCount > 0 || $remainingCount > 0)) {
                                $servedTotalCount = $servedCount + $remainingCount;
                            }
                            if ($servedPercent <= 0 && $servedTotalCount > 0 && $servedCount > 0) {
                                $servedPercent = (int) round(($servedCount / $servedTotalCount) * 100);
                            }

                            if ($readyCount > 0) {
                                $signalItems[] = [
                                    'tone' => 'emerald',
                                    'label' => $tr('app.ready_to_serve_signal', 'พร้อมเสิร์ฟ {count}', 'Ready {count}', ['count' => $readyCount]),
                                ];
                                $flowCounts[] = [
                                    'tone' => 'emerald',
                                    'short' => $tr('app.ready_short_count', 'พร้อม {count}', 'Ready {count}', ['count' => $readyCount]),
                                ];
                            }

                            if ($preparingCount > 0) {
                                $signalItems[] = [
                                    'tone' => 'amber',
                                    'label' => $tr('app.preparing_signal', 'กำลังทำ {count}', 'Preparing {count}', ['count' => $preparingCount]),
                                ];
                                $flowCounts[] = [
                                    'tone' => 'amber',
                                    'short' => $tr('app.preparing_short_count', 'ทำอยู่ {count}', 'Prep {count}', ['count' => $preparingCount]),
                                ];
                            }

                            if ($sentCount > 0) {
                                $signalItems[] = [
                                    'tone' => 'blue',
                                    'label' => $tr('app.sent_to_kitchen_signal', 'ส่งครัวแล้ว {count}', 'Sent to kitchen {count}', ['count' => $sentCount]),
                                ];
                                $flowCounts[] = [
                                    'tone' => 'blue',
                                    'short' => $tr('app.sent_short_count', 'ส่งครัว {count}', 'Sent {count}', ['count' => $sentCount]),
                                ];
                            }

                            if ($pendingCount > 0) {
                                $signalItems[] = [
                                    'tone' => 'slate',
                                    'label' => $tr('app.pending_kitchen_signal', 'รอส่งครัว {count}', 'Pending kitchen {count}', ['count' => $pendingCount]),
                                ];
                                $flowCounts[] = [
                                    'tone' => 'slate',
                                    'short' => $tr('app.pending_short_count', 'ค้างส่ง {count}', 'Pending {count}', ['count' => $pendingCount]),
                                ];
                            }

                            if ($cancelRequestCount > 0) {
                                $signalItems[] = [
                                    'tone' => 'rose',
                                    'label' => $tr('app.cancel_request_signal', 'รอยืนยันยกเลิก {count}', 'Cancel request {count}', ['count' => $cancelRequestCount]),
                                ];
                                $flowCounts[] = [
                                    'tone' => 'rose',
                                    'short' => $tr('app.cancel_request_short_count', 'ยกเลิก {count}', 'Cancel {count}', ['count' => $cancelRequestCount]),
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

                            if ($servedCount > 0) {
                                $flowCounts[] = [
                                    'tone' => $servedAll ? 'emerald' : 'teal',
                                    'short' => $servedAll
                                        ? $tr('app.served_all_short_count', 'เสิร์ฟครบ {count}', 'Served all {count}', ['count' => $servedCount])
                                        : $tr('app.served_short_count', 'เสิร์ฟ {count}', 'Served {count}', ['count' => $servedCount]),
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

                            if ($cancelRequestCount > 0) {
                                $primaryFlowTone = 'rose';
                                $primaryFlowLabel = $tr('app.cancel_request_primary', 'มีรายการรอยืนยันยกเลิก {count}', 'Cancel request {count}', ['count' => $cancelRequestCount]);
                            } elseif ($readyCount > 0) {
                                $primaryFlowTone = 'emerald';
                                $primaryFlowLabel = $tr('app.ready_primary', 'พร้อมเสิร์ฟ {count}', 'Ready to serve {count}', ['count' => $readyCount]);
                            } elseif ($preparingCount > 0) {
                                $primaryFlowTone = 'amber';
                                $primaryFlowLabel = $tr('app.preparing_primary', 'กำลังทำ {count}', 'Preparing {count}', ['count' => $preparingCount]);
                            } elseif ($sentCount > 0) {
                                $primaryFlowTone = 'blue';
                                $primaryFlowLabel = $tr('app.sent_primary', 'ส่งครัวแล้ว {count}', 'Sent {count}', ['count' => $sentCount]);
                            } elseif ($pendingCount > 0) {
                                $primaryFlowTone = 'slate';
                                $primaryFlowLabel = $tr('app.pending_primary', 'รอส่งครัว {count}', 'Pending {count}', ['count' => $pendingCount]);
                            } elseif ($servedAll) {
                                $primaryFlowTone = 'emerald';
                                $primaryFlowLabel = $tr('app.served_complete_primary', 'เสิร์ฟครบแล้ว', 'Served complete');
                            } elseif ($servedPartial) {
                                $primaryFlowTone = 'teal';
                                $primaryFlowLabel = $tr('app.served_partial_primary', 'เสิร์ฟบางส่วนแล้ว', 'Partially served');
                            }
                        }

                        if ($servedAll) {
                            $cardClass .= ' pos-table-card--served-complete';
                        } elseif ($servedPartial) {
                            $cardClass .= ' pos-table-card--served-partial';
                        }

                        if ($servedCount > 0) {
                            $servedProgressText = $servedAll
                                ? $tr('app.served_complete_signal', 'เสิร์ฟครบแล้ว {count}', 'Served all {count}', ['count' => $servedCount])
                                : $tr('app.served_partial_signal', 'เสิร์ฟแล้ว {served} / คงเหลือ {remaining}', 'Served {served} / Remaining {remaining}', ['served' => $servedCount, 'remaining' => $remainingCount]);

                            if (! empty($servedItemNames)) {
                                $servedTooltip = implode("
", $servedItemNames);
                                $servedProgressNote = $tr('app.served_items_hint', 'รายการที่เสิร์ฟแล้ว: {items}', 'Served items: {items}', ['items' => implode(', ', $servedItemNames)]);
                            } elseif ($servedAll) {
                                $servedProgressNote = $tr('app.served_complete_hint', 'ทุกรายการในรอบนี้เสิร์ฟครบแล้ว', 'All active items in this round have been served');
                            } else {
                                $servedProgressNote = $tr('app.served_progress_hint', 'ยังมีรายการที่ต้องติดตามต่อ', 'Some items are still in progress');
                            }

                            if ($servedAll) {
                                $servedProgressNoteClass .= ' pos-served-progress-note--complete';
                            }
                        }

                        if ($overviewNextFocus === '') {
                            $overviewNextFocus = $primaryFlowLabel;
                        }
                        $overviewNextFocusText = $overviewNextFocus !== ''
                            ? $overviewNextFocus
                            : $tr('app.open_order_exists', 'มีบิลเปิด', 'Open order');

                        if (! empty($servedItemNames)) {
                            $overviewServedNamesText = implode(', ', $servedItemNames);
                        } elseif ($servedCount > 0) {
                            $overviewServedNamesText = $servedAll
                                ? $tr('app.served_complete_hint', 'ทุกรายการในรอบนี้เสิร์ฟครบแล้ว', 'All active items in this round have been served')
                                : $tr('app.served_progress_hint', 'ยังมีรายการที่ต้องติดตามต่อ', 'Some items are still in progress');
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

                        <?php if ($hasOpenOrder): ?>
                            <div class="pos-table-hybrid-summary">
                                <div class="pos-table-primary-flow">
                                    <span class="pos-primary-flow-chip pos-primary-flow-chip--<?= esc($primaryFlowTone) ?>"><?= esc($primaryFlowLabel) ?></span>
                                    <?php if (! empty($flowCounts)): ?>
                                        <div class="pos-flow-counts">
                                            <?php foreach ($flowCounts as $flowCount): ?>
                                                <span class="pos-flow-count pos-flow-count--<?= esc((string) ($flowCount['tone'] ?? 'slate')) ?>"><strong><?= esc((string) ($flowCount['short'] ?? '')) ?></strong></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($servedCount > 0): ?>
                                    <div class="pos-served-progress<?= $servedAll ? ' pos-served-progress--complete' : '' ?>"<?= $servedTooltip !== '' ? ' title="' . esc($servedTooltip) . '"' : '' ?>>
                                        <div class="pos-served-progress-head">
                                            <span><?= esc($servedProgressLabel) ?></span>
                                            <span class="pos-served-progress-percent"><?= esc((string) $servedPercent) ?>%</span>
                                        </div>
                                        <div class="pos-served-progress-bar" aria-hidden="true">
                                            <span class="pos-served-progress-fill" style="width: <?= esc((string) max(0, min(100, $servedPercent))) ?>%;"></span>
                                        </div>
                                        <div class="pos-served-progress-meta">
                                            <span><?= esc($servedProgressText) ?></span>
                                            <span><?= esc($tr('app.total_items_short', 'รวม {count}', 'Total {count}', ['count' => max(0, $servedTotalCount)])) ?></span>
                                        </div>
                                        <?php if ($servedProgressNote !== ''): ?>
                                            <span class="<?= esc($servedProgressNoteClass) ?>"><?= esc($servedProgressNote) ?></span>
                                        <?php endif; ?>
                                        <span class="pos-served-progress-mobile-summary"><?= esc($tr('app.served_compact_summary', 'เสิร์ฟ {served}/{total}', 'Served {served}/{total}', ['served' => $servedCount, 'total' => max(0, $servedTotalCount)])) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

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