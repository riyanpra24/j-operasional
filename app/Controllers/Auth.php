<?php

namespace App\Controllers;

use App\Libraries\AccountSessionManager;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use Config\UserRoles;

class Auth extends BaseController
{
    private const LOGIN_LIFETIME_SECONDS = 7200;

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

        session()->regenerate(true);
        session()->set([
            'auth_user_id'      => (int) $user['id'],
            'auth_username'     => $user['username'],
            'auth_display_name' => $user['display_name'],
            'auth_role'         => $role,
            'auth_expires_at'   => $expiresAt,
            'auth_session_token' => $sessionLock['token'],
            'is_logged_in'      => true,
        ]);

        return redirect()->to(site_url('dashboard'))->with('success', 'Selamat datang, ' . $user['display_name'] . '.');
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
        session()->regenerate(true);
    }
}
