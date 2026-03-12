<?php

if (!function_exists('app_setting')) {
    function app_setting(string $key, $default = null)
    {
        static $settings = [];

        if (empty($settings)) {
            $db = \Config\Database::connect();
            $table = $db->tableExists('app_settings') ? 'app_settings' : 'settings';

            $rows = $db->table($table)->get()->getResultArray();

            foreach ($rows as $row) {
                $value = $row['setting_value'] ?? null;

                if (is_string($value)) {
                    $trimmed = ltrim($value);

                    if ($trimmed !== '' && (strpos($trimmed, '{') === 0 || strpos($trimmed, '[') === 0)) {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $value = $decoded;
                        }
                    }
                }

                $settings[$row['setting_key']] = $value;
            }
        }

        return $settings[$key] ?? $default;
    }
}

if (!function_exists('lfield')) {
    function lfield(array $row, string $baseField, string $fallback = ''): string
    {
        $locale = service('request')->getLocale();
        $locale = in_array($locale, ['th', 'en'], true) ? $locale : 'th';

        $localeField = $baseField . '_' . $locale;
        $thField     = $baseField . '_th';
        $enField     = $baseField . '_en';

        if (!empty($row[$localeField]) && !is_array($row[$localeField])) {
            return (string) $row[$localeField];
        }

        if (!empty($row[$thField]) && !is_array($row[$thField])) {
            return (string) $row[$thField];
        }

        if (!empty($row[$enField]) && !is_array($row[$enField])) {
            return (string) $row[$enField];
        }

        if (isset($row[$baseField]) && $row[$baseField] !== '' && !is_array($row[$baseField])) {
            return (string) $row[$baseField];
        }

        return $fallback;
    }
}
