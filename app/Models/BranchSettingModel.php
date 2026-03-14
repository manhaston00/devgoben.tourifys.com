<?php

namespace App\Models;

use CodeIgniter\Model;

class BranchSettingModel extends Model
{
    protected $table            = 'branch_settings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'branch_id',
        'setting_key',
        'setting_value',
        'created_at',
        'updated_at',
    ];

    public function getMap(int $branchId): array
    {
        if ($branchId <= 0) {
            return [];
        }

        $rows = $this->where('branch_id', $branchId)->findAll();
        $map  = [];

        foreach ($rows as $row) {
            $map[(string) ($row['setting_key'] ?? '')] = $this->decodeValue($row['setting_value'] ?? null);
        }

        return $map;
    }

    public function setValue(int $branchId, string $key, $value): void
    {
        $existing = $this->where('branch_id', $branchId)->where('setting_key', $key)->first();
        $payload  = [
            'branch_id'      => $branchId,
            'setting_key'    => $key,
            'setting_value'  => $this->encodeValue($value),
            'updated_at'     => date('Y-m-d H:i:s'),
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
