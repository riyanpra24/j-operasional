<?php

namespace App\Controllers;

use App\Libraries\AccountSessionManager;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use Config\UserRoles;

class Auth extends BaseController
{
    private const LOGIN_LIFETIME_SECONDS = 7200;
    private const ADMIN_TAKEOVER_LIFETIME_SECONDS = 300;
    private const ADMIN_TAKEOVER_MAX_ATTEMPTS = 5;

    public function login(): RedirectResponse
    {
        if (session()->get('auth_user_id') !== null) {
            $expiresAt = (int) session()->get('auth_expires_at');

            if ($expiresAt > time()) {
                return redirect()->to(site_url('dashboard'));
            }

            $this->clearAuthentication();

            return redirect()->to(site_url('/'))
                ->with('open_login_modal', true)
                ->with('login_error', 'Sesi login telah berakhir setelah 2 jam. Silakan login kembali.');
        }

        return redirect()->to(site_url('/'))->with('open_login_modal', true);
    }

    public function attempt(): RedirectResponse
    {
        $rules = [
            'username' => 'required|max_length[100]',
            'password' => 'required|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('open_login_modal', true)
                ->with('login_error', 'Username dan password wajib diisi.');
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $user     = (new UserModel())->where('username', $username)->first();

        if ($user === null || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()
                ->withInput()
                ->with('open_login_modal', true)
                ->with('login_error', 'Username atau password tidak sesuai.');
        }

        $role = strtolower((string) ($user['role'] ?? ''));

        if (! UserRoles::isValid($role)) {
            return redirect()->back()
                ->withInput()
                ->with('open_login_modal', true)
                ->with('login_error', 'Role akun tidak dikenali. Hubungi administrator.');
        }

        $expiresAt    = time() + self::LOGIN_LIFETIME_SECONDS;
        $sessionLock = (new AccountSessionManager())->acquire(
            (int) $user['id'],
            $expiresAt,
            $this->request->getIPAddress(),
            $this->request->getUserAgent()->getAgentString(),
        );

        if ($sessionLock['status'] === 'active') {
            if ($role === 'admin') {
                if (empty($user['admin_login_pin_hash'])) {
                    $this->clearPendingAdminTakeover();

                    return redirect()->back()
                        ->withInput()
                        ->with('open_login_modal', true)
                        ->with('login_error', 'Akun admin sedang digunakan di perangkat lain, tetapi PIN takeover belum diatur. Atur PIN pada menu Kelola Akun dari sesi admin yang masih aktif.');
                }

                $this->prepareAdminTakeover($user, $sessionLock);

                return redirect()->back()
                    ->with('open_admin_takeover_modal', true);
            }

            return redirect()->back()
                ->withInput()
                ->with('open_login_modal', true)
                ->with('login_error', 'Akun sedang digunakan di perangkat lain. Silakan logout dari perangkat tersebut atau tunggu sesi berakhir.');
        }

        if ($sessionLock['status'] !== 'acquired' || ! isset($sessionLock['token'])) {
            return redirect()->back()
                ->withInput()
                ->with('open_login_modal', true)
                ->with('login_error', 'Login belum dapat diproses. Silakan coba kembali.');
        }

        $this->establishAuthentication($user, $role, $expiresAt, $sessionLock['token']);

        return redirect()->to(site_url('dashboard'))->with('success', 'Selamat datang, ' . $user['display_name'] . '.');
    }

    public function takeoverAdminSession(): RedirectResponse
    {
        $session = session();
        $userId = (int) $session->get('admin_takeover_user_id');
        $challengeExpiresAt = (int) $session->get('admin_takeover_expires_at');
        $attempts = (int) $session->get('admin_takeover_attempts');

        if ($userId <= 0 || $challengeExpiresAt <= time()) {
            $this->clearPendingAdminTakeover();

            return redirect()->to(site_url('/'))
                ->with('open_login_modal', true)
                ->with('login_error', 'Permintaan pengambilalihan sesi telah berakhir. Silakan login kembali.');
        }

        $user = (new UserModel())->find($userId);
        if ($user === null || strtolower((string) $user['role']) !== 'admin' || empty($user['admin_login_pin_hash'])) {
            $this->clearPendingAdminTakeover();

            return redirect()->to(site_url('/'))
                ->with('open_login_modal', true)
                ->with('login_error', 'Pengambilalihan sesi admin tidak dapat diproses.');
        }

        $throttleKey = 'admin-takeover-' . hash('sha256', $userId . '|' . $this->request->getIPAddress());
        if (! service('throttler')->check($throttleKey, self::ADMIN_TAKEOVER_MAX_ATTEMPTS, self::ADMIN_TAKEOVER_LIFETIME_SECONDS)) {
            $this->clearPendingAdminTakeover();

            return redirect()->to(site_url('/'))
                ->with('open_login_modal', true)
                ->with('login_error', 'Percobaan PIN takeover terlalu banyak. Tunggu 5 menit sebelum mencoba kembali.');
        }

        $pin = trim((string) $this->request->getPost('admin_login_pin'));
        if (! preg_match('/^\d{6}$/', $pin) || ! password_verify($pin, (string) $user['admin_login_pin_hash'])) {
            $attempts++;
            $session->set('admin_takeover_attempts', $attempts);

            log_message('warning', 'PIN takeover admin salah untuk user ID {userId} dari IP {ip}.', [
                'userId' => $userId,
                'ip' => $this->request->getIPAddress(),
            ]);

            if ($attempts >= self::ADMIN_TAKEOVER_MAX_ATTEMPTS) {
                $this->clearPendingAdminTakeover();

                return redirect()->to(site_url('/'))
                    ->with('open_login_modal', true)
                    ->with('login_error', 'Percobaan PIN takeover telah mencapai batas. Silakan login kembali.');
            }

            $remainingAttempts = self::ADMIN_TAKEOVER_MAX_ATTEMPTS - $attempts;

            return redirect()->to(site_url('/'))
                ->with('open_admin_takeover_modal', true)
                ->with('admin_takeover_error', "PIN tidak sesuai. Tersisa {$remainingAttempts} percobaan.");
        }

        $expiresAt = time() + self::LOGIN_LIFETIME_SECONDS;
        $takeover = (new AccountSessionManager())->takeOver(
            $userId,
            $expiresAt,
            $this->request->getIPAddress(),
            $this->request->getUserAgent()->getAgentString(),
        );

        if ($takeover['status'] !== 'acquired' || ! isset($takeover['token'])) {
            return redirect()->to(site_url('/'))
                ->with('open_admin_takeover_modal', true)
                ->with('admin_takeover_error', 'Sesi perangkat lain belum berhasil dikeluarkan. Silakan coba kembali.');
        }

        $this->clearPendingAdminTakeover();
        $this->establishAuthentication($user, 'admin', $expiresAt, $takeover['token']);

        log_message('notice', 'Sesi admin user ID {userId} diambil alih dari perangkat baru dengan PIN.', [
            'userId' => $userId,
        ]);

        return redirect()->to(site_url('dashboard'))
            ->with('success', 'Perangkat lain berhasil dikeluarkan. Anda sekarang login sebagai ' . $user['display_name'] . '.');
    }

    public function logout(): RedirectResponse
    {
        $this->clearAuthentication();

        return redirect()->to(site_url('/'))
            ->with('open_login_modal', true)
            ->with('logout_success', 'Anda berhasil keluar dari sistem.');
    }

    private function clearAuthentication(): void
    {
        $userId = (int) session()->get('auth_user_id');
        $token  = (string) session()->get('auth_session_token');

        if ($userId > 0 && $token !== '') {
            (new AccountSessionManager())->release($userId, $token);
        }

        session()->remove([
            'auth_user_id',
            'auth_username',
            'auth_display_name',
            'auth_role',
            'auth_expires_at',
            'auth_session_token',
            'auth_last_route',
            'is_logged_in',
        ]);
        $this->clearPendingAdminTakeover();
        session()->regenerate(true);
    }

    private function prepareAdminTakeover(array $user, array $activeSession): void
    {
        session()->set([
            'admin_takeover_user_id' => (int) $user['id'],
            'admin_takeover_username' => (string) $user['username'],
            'admin_takeover_display_name' => (string) $user['display_name'],
            'admin_takeover_expires_at' => time() + self::ADMIN_TAKEOVER_LIFETIME_SECONDS,
            'admin_takeover_attempts' => 0,
            'admin_takeover_ip' => (string) ($activeSession['ip_address'] ?? ''),
            'admin_takeover_device' => $this->deviceLabel((string) ($activeSession['user_agent'] ?? '')),
            'admin_takeover_last_seen_at' => (string) ($activeSession['last_seen_at'] ?? ''),
        ]);
    }

    private function establishAuthentication(array $user, string $role, int $expiresAt, string $token): void
    {
        $this->clearPendingAdminTakeover();
        session()->regenerate(true);
        session()->set([
            'auth_user_id' => (int) $user['id'],
            'auth_username' => $user['username'],
            'auth_display_name' => $user['display_name'],
            'auth_role' => $role,
            'auth_expires_at' => $expiresAt,
            'auth_session_token' => $token,
            'is_logged_in' => true,
        ]);
    }

    private function clearPendingAdminTakeover(): void
    {
        session()->remove([
            'admin_takeover_user_id',
            'admin_takeover_username',
            'admin_takeover_display_name',
            'admin_takeover_expires_at',
            'admin_takeover_attempts',
            'admin_takeover_ip',
            'admin_takeover_device',
            'admin_takeover_last_seen_at',
        ]);
    }

    private function deviceLabel(string $userAgent): string
    {
        if ($userAgent === '') {
            return 'Perangkat tidak dikenal';
        }

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Microsoft Edge',
            str_contains($userAgent, 'OPR/') => 'Opera',
            str_contains($userAgent, 'Chrome/') => 'Google Chrome',
            str_contains($userAgent, 'Firefox/') => 'Mozilla Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Browser tidak dikenal',
        };
        $platform = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Perangkat tidak dikenal',
        };

        return $browser . ' · ' . $platform;
    }
}
