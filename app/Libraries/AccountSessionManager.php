<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class AccountSessionManager
{
    private const HEARTBEAT_TOUCH_INTERVAL_SECONDS = 30;
    private const STALE_SESSION_SECONDS = 120;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Mengambil kunci sesi eksklusif untuk satu akun.
     *
     * @return array{status: 'acquired'|'active'|'error', token?: string, expires_at?: int, ip_address?: string|null, user_agent?: string|null, last_seen_at?: string|null}
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
                $existingLastSeen = strtotime((string) ($existing['last_seen_at'] ?? '')) ?: 0;

                if ($existingExpiry > $now && $existingLastSeen > $now - self::STALE_SESSION_SECONDS) {
                    $this->db->transRollback();

                    return [
                        'status'     => 'active',
                        'expires_at' => $existingExpiry,
                        'ip_address' => $existing['ip_address'] ?? null,
                        'user_agent' => $existing['user_agent'] ?? null,
                        'last_seen_at' => $existing['last_seen_at'] ?? null,
                    ];
                }

                $this->endUsage($userId, (string) ($existing['last_seen_at'] ?? date('Y-m-d H:i:s', $now)));
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

            $this->startUsage($userId, $timestamp);

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

    /**
     * Mengganti sesi aktif admin secara atomik setelah PIN takeover diverifikasi.
     *
     * @return array{status: 'acquired'|'error', token?: string}
     */
    public function takeOver(
        int $userId,
        int $expiresAt,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): array {
        try {
            $this->db->transBegin();

            $usersTable = $this->db->escapeIdentifiers($this->db->prefixTable('users'));
            $user = $this->db
                ->query("SELECT id FROM {$usersTable} WHERE id = ? FOR UPDATE", [$userId])
                ->getRowArray();

            if ($user === null) {
                $this->db->transRollback();

                return ['status' => 'error'];
            }

            $existing = $this->db->table('user_sessions')->where('user_id', $userId)->get()->getRowArray();
            if ($existing !== null) {
                $this->endUsage($userId, date('Y-m-d H:i:s'));
            }
            $this->db->table('user_sessions')->where('user_id', $userId)->delete();

            $token = bin2hex(random_bytes(32));
            $timestamp = date('Y-m-d H:i:s');
            $inserted = $this->db->table('user_sessions')->insert([
                'user_id' => $userId,
                'token_hash' => $this->hashToken($token),
                'ip_address' => $ipAddress !== '' ? $ipAddress : null,
                'user_agent' => $this->limitUserAgent($userAgent),
                'last_seen_at' => $timestamp,
                'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            if (! $inserted || ! $this->db->transStatus()) {
                throw new RuntimeException('Gagal mengganti sesi admin aktif.');
            }

            $this->startUsage($userId, $timestamp);

            $this->db->transCommit();

            return [
                'status' => 'acquired',
                'token' => $token,
            ];
        } catch (Throwable $exception) {
            if ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }

            log_message('error', 'Gagal mengambil alih sesi admin: {message}', [
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
                $this->endUsage($userId, (string) $session['last_seen_at']);
                $this->db->table('user_sessions')
                    ->where('user_id', $userId)
                    ->where('token_hash', $tokenHash)
                    ->delete();

                return false;
            }

            $lastSeenAt = strtotime((string) $session['last_seen_at']) ?: 0;

            $now = time();
            if ($lastSeenAt <= $now - self::HEARTBEAT_TOUCH_INTERVAL_SECONDS) {
                $timestamp = date('Y-m-d H:i:s', $now);
                $this->db->table('user_sessions')
                    ->where('user_id', $userId)
                    ->where('token_hash', $tokenHash)
                    ->update([
                        'last_seen_at' => $timestamp,
                        'updated_at'   => $timestamp,
                    ]);
                $this->touchUsage($userId, $timestamp);
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
            $session = $this->db->table('user_sessions')
                ->where('user_id', $userId)
                ->where('token_hash', $this->hashToken($token))
                ->get()
                ->getRowArray();
            if ($session !== null) {
                $this->endUsage($userId, date('Y-m-d H:i:s'));
            }

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
            $sessions = $this->db->table('user_sessions')
                ->where('user_id', $userId)
                ->get()
                ->getResultArray();
            if ($sessions !== []) {
                $this->endUsage($userId, date('Y-m-d H:i:s'));
            }

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
            $expiredSessions = $this->db->table('user_sessions')
                ->groupStart()
                    ->where('expires_at <=', date('Y-m-d H:i:s'))
                    ->orWhere('last_seen_at <=', date('Y-m-d H:i:s', time() - self::STALE_SESSION_SECONDS))
                ->groupEnd()
                ->get()
                ->getResultArray();
            foreach ($expiredSessions as $session) {
                $this->endUsage((int) $session['user_id'], (string) $session['last_seen_at']);
            }
            $deleted = $this->db->table('user_sessions')
                ->groupStart()
                    ->where('expires_at <=', date('Y-m-d H:i:s'))
                    ->orWhere('last_seen_at <=', date('Y-m-d H:i:s', time() - self::STALE_SESSION_SECONDS))
                ->groupEnd()
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

    private function startUsage(int $userId, string $timestamp): void
    {
        if (! $this->db->tableExists('user_session_usage_logs')) {
            return;
        }

        $this->db->table('user_session_usage_logs')
            ->where('user_id', $userId)
            ->where('ended_at IS NULL', null, false)
            ->update(['ended_at' => $timestamp, 'updated_at' => $timestamp]);
        $this->db->table('user_session_usage_logs')->insert([
            'user_id'      => $userId,
            'started_at'   => $timestamp,
            'last_seen_at' => $timestamp,
            'ended_at'     => null,
            'created_at'   => $timestamp,
            'updated_at'   => $timestamp,
        ]);
    }

    private function touchUsage(int $userId, string $timestamp): void
    {
        if (! $this->db->tableExists('user_session_usage_logs')) {
            return;
        }

        $this->db->table('user_session_usage_logs')
            ->where('user_id', $userId)
            ->where('ended_at IS NULL', null, false)
            ->update(['last_seen_at' => $timestamp, 'updated_at' => $timestamp]);
    }

    private function endUsage(int $userId, string $timestamp): void
    {
        if (! $this->db->tableExists('user_session_usage_logs')) {
            return;
        }

        $this->db->table('user_session_usage_logs')
            ->where('user_id', $userId)
            ->where('ended_at IS NULL', null, false)
            ->update(['last_seen_at' => $timestamp, 'ended_at' => $timestamp, 'updated_at' => $timestamp]);
    }

    private function limitUserAgent(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        return mb_substr($userAgent, 0, 255);
    }
}
