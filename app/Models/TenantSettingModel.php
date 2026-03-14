<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantSettingModel extends Model
{
    protected $table            = 'tenant_settings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'tenant_id',
        'setting_key',
        'setting_value',
        'created_at',
        'updated_at',
    ];

    public function getMap(int $tenantId): array
    {
        if ($tenantId <= 0 || ! $this->db->tableExists($this->table)) {
            return [];
        }

        $rows = $this->where('tenant_id', $tenantId)->findAll();
        $map  = [];

        foreach ($rows as $row) {
            $map[(string) ($row['setting_key'] ?? '')] = $this->decodeValue($row['setting_value'] ?? null);
        }

        return $map;
    }

    public function setValue(int $tenantId, string $key, $value): void
    {
        if ($tenantId <= 0 || ! $this->db->tableExists($this->table)) {
            return;
        }

        $existing = $this->where('tenant_id', $tenantId)->where('setting_key', $key)->first();
        $payload  = [
            'tenant_id'      => $tenantId,
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
