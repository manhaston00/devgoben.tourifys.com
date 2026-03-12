<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ApplyTenantPrefixToUsernames extends Migration
{
    protected function normalizeTenantCode(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = preg_replace('/_+/', '_', (string) $value);
        $value = trim((string) $value, '_');

        return $value;
    }

    protected function normalizeBaseUsername(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9._-]+/', '', $value);

        return $value;
    }

    protected function makeLoginUsername(?string $tenantCode, ?string $baseUsername): string
    {
        $tenantCode   = $this->normalizeTenantCode($tenantCode);
        $baseUsername = $this->normalizeBaseUsername($baseUsername);

        if ($baseUsername === '') {
            return '';
        }

        if ($tenantCode === '') {
            return $baseUsername;
        }

        $prefix = $tenantCode . '_';

        if (strpos($baseUsername, $prefix) === 0) {
            return $baseUsername;
        }

        return $prefix . $baseUsername;
    }

    protected function usernameExists(string $username, int $ignoreId = 0): bool
    {
        $builder = $this->db->table('users')
            ->where('username', $username)
            ->where('deleted_at', null);

        if ($ignoreId > 0) {
            $builder->where('id !=', $ignoreId);
        }

        return (bool) $builder->countAllResults();
    }

    protected function ensureUniqueUsername(string $username, int $userId): string
    {
        if (! $this->usernameExists($username, $userId)) {
            return $username;
        }

        $i = 2;
        while (true) {
            $candidate = $username . '_' . $i;
            if (! $this->usernameExists($candidate, $userId)) {
                return $candidate;
            }
            $i++;
        }
    }

    protected function hasIndex(string $table, string $indexName): bool
    {
        $rows = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName])->getResultArray();
        return ! empty($rows);
    }

    public function up()
    {
        if (! $this->db->tableExists('users') || ! $this->db->tableExists('tenants')) {
            return;
        }

        $rows = $this->db->table('users u')
            ->select('u.id, u.username, u.tenant_id, u.deleted_at, r.role_name, t.tenant_code')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->join('tenants t', 't.id = u.tenant_id', 'left')
            ->where('u.deleted_at', null)
            ->orderBy('u.id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $userId   = (int) ($row['id'] ?? 0);
            $username = trim((string) ($row['username'] ?? ''));
            $roleName = strtolower(trim((string) ($row['role_name'] ?? '')));
            $tenantId = (int) ($row['tenant_id'] ?? 0);

            if ($userId <= 0 || $username === '') {
                continue;
            }

            // super admin คงแบบเดิม
            if ($roleName === 'super_admin' || $tenantId <= 0) {
                $normalized = $this->normalizeBaseUsername($username);
                if ($normalized !== '' && $normalized !== $username) {
                    $normalized = $this->ensureUniqueUsername($normalized, $userId);

                    $this->db->table('users')
                        ->where('id', $userId)
                        ->update(['username' => $normalized]);
                }
                continue;
            }

            $tenantCode = trim((string) ($row['tenant_code'] ?? ''));
            $target     = $this->makeLoginUsername($tenantCode, $username);

            if ($target === '') {
                continue;
            }

            $target = $this->ensureUniqueUsername($target, $userId);

            if ($target !== $username) {
                $this->db->table('users')
                    ->where('id', $userId)
                    ->update(['username' => $target]);
            }
        }

        if (! $this->hasIndex('users', 'uniq_users_username')) {
            try {
                $this->db->query('ALTER TABLE `users` ADD UNIQUE KEY `uniq_users_username` (`username`)');
            } catch (\Throwable $e) {
                // ignore if index already exists under another name or engine rejects duplicate
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('users') && $this->hasIndex('users', 'uniq_users_username')) {
            try {
                $this->db->query('ALTER TABLE `users` DROP INDEX `uniq_users_username`');
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
}