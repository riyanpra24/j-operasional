<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class AccountSessionManager
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Mengambil kunci sesi eksklusif untuk satu akun.
     *
     * @return array{status: 'acquired'|'active'|'error', token?: string, expires_at?: int}
     */
    public function acquire(
        int $userId,
        int $expiresAt,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): array {
        try {
            $this->db->transBegin();

            // Kunci baris pengguna agar dua login bersamaan tidak bisa sama-sama berhasil.
            $usersTable = $this->db->escapeIdentifiers($this->db->prefixTable('users'));
            $user       = $this->db
                ->query("SELECT id FROM {$usersTable} WHERE id = ? FOR UPDATE", [$userId])
                ->getRowArray();

            if ($user === null) {
                $this->db->transRollback();

                return ['status' => 'error'];
            }

            $sessions = $this->db->table('user_sessions');
            $existing = $sessions->where('user_id', $userId)->get()->getRowArray();
            $now       = time();

            if ($existing !== null) {
                $existingExpiry = strtotime((string) $existing['expires_at']) ?: 0;

                if ($existingExpiry > $now) {
                    $this->db->transRollback();

                    return [
                        'status'     => 'active',
                        'expires_at' => $existingExpiry,
                    ];
                }

                $sessions->where('user_id', $userId)->delete();
            }

            $token     = bin2hex(random_bytes(32));
            $timestamp = date('Y-m-d H:i:s', $now);
            $inserted  = $this->db->table('user_sessions')->insert([
                'user_id'      => $userId,
                'token_hash'   => $this->hashToken($token),
                'ip_address'   => $ipAddress !== '' ? $ipAddress : null,
                'user_agent'   => $this->limitUserAgent($userAgent),
                'last_seen_at' => $timestamp,
                'expires_at'   => date('Y-m-d H:i:s', $expiresAt),
                'created_at'   => $timestamp,
                'updated_at'   => $timestamp,
            ]);

            if (! $inserted || ! $this->db->transStatus()) {
                throw new RuntimeException('Gagal menyimpan sesi akun aktif.');
            }

            $this->db->transCommit();

            return [
                'status' => 'acquired',
                'token'  => $token,
            ];
        } catch (Throwable $exception) {
            if ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }

            log_message('error', 'Gagal mengambil kunci sesi akun: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return ['status' => 'error'];
        }
    }

    public function validate(int $userId, string $token): bool
    {
        if ($userId <= 0 || $token === '') {
            return false;
        }

        try {
            $session = $this->db->table('user_sessions')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();

            if ($session === null) {
                return false;
            }

            $tokenHash = $this->hashToken($token);

            if (! hash_equals((string) $session['token_hash'], $tokenHash)) {
                return false;
            }

            if ((strtotime((string) $session['expires_at']) ?: 0) <= time()) {
                $this->db->table('user_sessions')
                    ->where('user_id', $userId)
                    ->where('token_hash', $tokenHash)
                    ->delete();

                return false;
            }

            $lastSeenAt = strtotime((string) $session['last_seen_at']) ?: 0;

            if ($lastSeenAt <= time() - 60) {
                $this->db->table('user_sessions')
                    ->where('user_id', $userId)
                    ->where('token_hash', $tokenHash)
                    ->update([
                        'last_seen_at' => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
            }

            return true;
        } catch (Throwable $exception) {
            log_message('error', 'Gagal memvalidasi sesi akun: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function release(int $userId, string $token): void
    {
        if ($userId <= 0 || $token === '') {
            return;
        }

        try {
            $this->db->table('user_sessions')
                ->where('user_id', $userId)
                ->where('token_hash', $this->hashToken($token))
                ->delete();
        } catch (Throwable $exception) {
            log_message('error', 'Gagal melepas kunci sesi akun: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Cabut seluruh sesi aktif milik satu akun dari sisi administrator.
     *
     * @return int|null Jumlah sesi yang dicabut, atau null ketika database gagal.
     */
    public function releaseAllForUser(int $userId): ?int
    {
        if ($userId <= 0) {
            return 0;
        }

        try {
            $deleted = $this->db->table('user_sessions')
                ->where('user_id', $userId)
                ->delete();

            return $deleted ? $this->db->affectedRows() : null;
        } catch (Throwable $exception) {
            log_message('error', 'Gagal mereset sesi akun: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function pruneExpired(): int
    {
        try {
            $deleted = $this->db->table('user_sessions')
                ->where('expires_at <=', date('Y-m-d H:i:s'))
                ->delete();

            return $deleted ? $this->db->affectedRows() : 0;
        } catch (Throwable $exception) {
            log_message('error', 'Gagal membersihkan sesi kedaluwarsa: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function limitUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        return mb_substr($userAgent, 0, 255);
    }
}
