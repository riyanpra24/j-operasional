<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use Config\UserRoles;

class Auth extends BaseController
{
    private const LOGIN_LIFETIME_SECONDS = 600;

    public function login(): string|RedirectResponse
    {
        if (session()->get('auth_user_id') !== null) {
            $expiresAt = (int) session()->get('auth_expires_at');

            if ($expiresAt > time()) {
                return redirect()->to(site_url('dashboard'));
            }

            $this->clearAuthentication();

            return view('auth/login', [
                'title'              => 'Login',
                'loginErrorOverride' => 'Sesi login telah berakhir setelah 10 menit. Silakan login kembali.',
            ]);
        }

        return view('auth/login', [
            'title' => 'Login',
        ]);
    }

    public function attempt(): RedirectResponse
    {
        $rules = [
            'username' => 'required|max_length[100]',
            'password' => 'required|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('login_error', 'Username dan password wajib diisi.');
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $user     = (new UserModel())->where('username', $username)->first();

        if ($user === null || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with('login_error', 'Username atau password tidak sesuai.');
        }

        $role = strtolower((string) ($user['role'] ?? ''));

        if (! UserRoles::isValid($role)) {
            return redirect()->back()->withInput()->with('login_error', 'Role akun tidak dikenali. Hubungi administrator.');
        }

        session()->regenerate(true);
        session()->set([
            'auth_user_id'      => (int) $user['id'],
            'auth_username'     => $user['username'],
            'auth_display_name' => $user['display_name'],
            'auth_role'         => $role,
            'auth_expires_at'   => time() + self::LOGIN_LIFETIME_SECONDS,
            'is_logged_in'      => true,
        ]);

        return redirect()->to(site_url('dashboard'))->with('success', 'Selamat datang, ' . $user['display_name'] . '.');
    }

    public function logout(): RedirectResponse
    {
        $this->clearAuthentication();

        return redirect()->to(site_url('login'))->with('logout_success', 'Anda berhasil keluar dari sistem.');
    }

    private function clearAuthentication(): void
    {
        session()->remove([
            'auth_user_id',
            'auth_username',
            'auth_display_name',
            'auth_role',
            'auth_expires_at',
            'auth_last_route',
            'is_logged_in',
        ]);
        session()->regenerate(true);
    }
}
