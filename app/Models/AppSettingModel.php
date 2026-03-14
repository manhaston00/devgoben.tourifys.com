<?php

namespace App\Models;

use CodeIgniter\Model;

class AppSettingModel extends Model
{
    protected $table            = 'app_settings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'setting_key',
        'setting_value',
        'setting_group',
        'created_at',
        'updated_at',
    ];

    public function getMap(?string $group = null): array
    {
        $builder = $this->select('setting_key, setting_value, setting_group');

        if ($group !== null && $group !== '') {
            $builder->where('setting_group', $group);
        }

        $rows = $builder->findAll();
        $map  = [];

        foreach ($rows as $row) {
            $map[(string) ($row['setting_key'] ?? '')] = $this->decodeValue($row['setting_value'] ?? null);
        }

        return $map;
    }

    public function setValue(string $key, $value, string $group = 'general'): void
    {
        $existing = $this->where('setting_key', $key)->first();
        $payload  = [
            'setting_key'   => $key,
            'setting_value' => $this->encodeValue($value),
            'setting_group' => $group,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->update((int) $existing['id'], $payload);
            return;
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->insert($payload);
    }

    protected function encodeValue($value): ?string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    protected function decodeValue($value)
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        if (in_array(strtolower($trimmed), ['1', '0', 'true', 'false'], true)) {
            return in_array(strtolower($trimmed), ['1', 'true'], true) ? '1' : '0';
        }

        if (($trimmed[0] ?? '') === '{' || ($trimmed[0] ?? '') === '[') {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }
}
