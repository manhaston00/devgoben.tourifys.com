<?php

if (!function_exists('current_locale')) {
    function current_locale()
    {
        try {
            $locale = service('request')->getLocale();
            if (!empty($locale)) {
                return strtolower($locale);
            }
        } catch (\Throwable $e) {
        } catch (\Exception $e) {
        }

        $sessionLocale = session()->get('site_locale');
        if (!empty($sessionLocale)) {
            return strtolower($sessionLocale);
        }

        $legacyLocale = session()->get('language');
        if (!empty($legacyLocale)) {
            return strtolower($legacyLocale);
        }

        return 'th';
    }
}

if (!function_exists('is_en')) {
    function is_en()
    {
        return current_locale() === 'en';
    }
}

if (!function_exists('dt_lang')) {
    function dt_lang()
    {
        if (is_en()) {
            return array(
                'processing'     => 'Processing...',
                'search'         => 'Search:',
                'lengthMenu'     => 'Show _MENU_ entries',
                'info'           => 'Showing _START_ to _END_ of _TOTAL_ entries',
                'infoEmpty'      => 'Showing 0 to 0 of 0 entries',
                'infoFiltered'   => '(filtered from _MAX_ total entries)',
                'infoPostFix'    => '',
                'loadingRecords' => 'Loading...',
                'zeroRecords'    => 'No matching records found',
                'emptyTable'     => 'No data available in table',
                'paginate'       => array(
                    'first'    => 'First',
                    'previous' => 'Previous',
                    'next'     => 'Next',
                    'last'     => 'Last',
                ),
                'aria'           => array(
                    'sortAscending'  => ': activate to sort column ascending',
                    'sortDescending' => ': activate to sort column descending',
                ),
            );
        }

        return array(
            'processing'     => 'กำลังประมวลผล...',
            'search'         => 'ค้นหา:',
            'lengthMenu'     => 'แสดง _MENU_ รายการ',
            'info'           => 'แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ',
            'infoEmpty'      => 'แสดง 0 ถึง 0 จาก 0 รายการ',
            'infoFiltered'   => '(กรองจากทั้งหมด _MAX_ รายการ)',
            'infoPostFix'    => '',
            'loadingRecords' => 'กำลังโหลด...',
            'zeroRecords'    => 'ไม่พบข้อมูลที่ค้นหา',
            'emptyTable'     => 'ไม่มีข้อมูลในตาราง',
            'paginate'       => array(
                'first'    => 'หน้าแรก',
                'previous' => 'ก่อนหน้า',
                'next'     => 'ถัดไป',
                'last'     => 'หน้าสุดท้าย',
            ),
            'aria'           => array(
                'sortAscending'  => ': เปิดใช้งานเพื่อเรียงคอลัมน์จากน้อยไปมาก',
                'sortDescending' => ': เปิดใช้งานเพื่อเรียงคอลัมน์จากมากไปน้อย',
            ),
        );
    }
}

if (!function_exists('active_text')) {
    function active_text($status)
    {
        $status = (string) $status;

        if ($status === '1' || $status === 'active' || $status === 'enabled') {
            return lang('app.active');
        }

        return lang('app.inactive');
    }
}

if (!function_exists('status_text')) {
    function status_text($status)
    {
        $status = strtolower(trim((string) $status));

        if ($status === 'available') {
            return lang('app.available');
        }
        if ($status === 'occupied') {
            return lang('app.occupied');
        }
        if ($status === 'reserved') {
            return lang('app.reserved');
        }
        if ($status === 'cleaning') {
            return lang('app.cleaning');
        }
        if ($status === 'disabled') {
            return lang('app.disabled');
        }
        if ($status === 'pending') {
            return lang('app.pending');
        }
        if ($status === 'confirmed') {
            return lang('app.confirmed');
        }
        if ($status === 'cancelled') {
            return lang('app.cancelled');
        }
        if ($status === 'completed') {
            return lang('app.completed');
        }
        if ($status === 'no_show') {
            return lang('app.no_show');
        }
        if ($status === 'checked_in') {
            return lang('app.checked_in');
        }
        if ($status === 'seated') {
            return lang('app.seated');
        }

        return $status !== '' ? ucfirst($status) : '-';
    }
}

if (!function_exists('lfield')) {
    function lfield($row, $baseField, $default = '')
    {
        if (!is_array($row)) {
            return $default;
        }

        $locale = current_locale();

        $fieldTh = $baseField . '_th';
        $fieldEn = $baseField . '_en';

        $value = '';

        if ($locale === 'en') {
            if (isset($row[$fieldEn]) && $row[$fieldEn] !== null && $row[$fieldEn] !== '') {
                $value = $row[$fieldEn];
            } elseif (isset($row[$fieldTh]) && $row[$fieldTh] !== null && $row[$fieldTh] !== '') {
                $value = $row[$fieldTh];
            } elseif (isset($row[$baseField]) && $row[$baseField] !== null && $row[$baseField] !== '') {
                $value = $row[$baseField];
            }
        } else {
            if (isset($row[$fieldTh]) && $row[$fieldTh] !== null && $row[$fieldTh] !== '') {
                $value = $row[$fieldTh];
            } elseif (isset($row[$fieldEn]) && $row[$fieldEn] !== null && $row[$fieldEn] !== '') {
                $value = $row[$fieldEn];
            } elseif (isset($row[$baseField]) && $row[$baseField] !== null && $row[$baseField] !== '') {
                $value = $row[$baseField];
            }
        }

        return $value !== '' ? $value : $default;
    }
}
if (!function_exists('reservation_status_badge')) {
    function reservation_status_badge($status)
    {
        $status = strtolower(trim((string) $status));

        $class = 'secondary';
        $text  = $status !== '' ? ucfirst($status) : '-';

        if ($status === 'pending') {
            $class = 'warning';
            $text  = lang('app.pending');
        } elseif ($status === 'confirmed') {
            $class = 'info';
            $text  = lang('app.confirmed');
        } elseif ($status === 'checked_in') {
            $class = 'primary';
            $text  = lang('app.checked_in');
        } elseif ($status === 'seated') {
            $class = 'success';
            $text  = lang('app.seated');
        } elseif ($status === 'completed') {
            $class = 'dark';
            $text  = lang('app.completed');
        } elseif ($status === 'cancelled') {
            $class = 'danger';
            $text  = lang('app.cancelled');
        } elseif ($status === 'no_show') {
            $class = 'secondary';
            $text  = lang('app.no_show');
        }

        return '<span class="badge bg-' . $class . '">' . esc($text) . '</span>';
    }
}