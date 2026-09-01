<?php

namespace App\Controllers;

use App\Libraries\AccountSessionManager;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\UserRoles;

class KelolaAkun extends BaseController
{
    private UserModel $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    public function index(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $role    = trim((string) $this->request->getGet('role'));
        $perPage = (int) $this->request->getGet('per_page');

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        if ($keyword !== '') {
            $this->model->groupStart()
                ->like('username', $keyword)
                ->orLike('display_name', $keyword)
                ->groupEnd();
        }

        if (UserRoles::isValid($role)) {
            $this->model->where('role', $role);
        } else {
            $role = '';
        }

        $counts = ['total' => (new UserModel())->countAllResults()];

        foreach (array_keys(UserRoles::LABELS) as $roleName) {
            $counts[$roleName] = $this->model->where('role', $roleName)->countAllResults();
        }

        return view('kelola_akun/index', [
            'title'   => 'Add Account',
            'users'   => $this->model->orderBy('created_at', 'ASC')->orderBy('id', 'ASC')->paginate($perPage, 'users'),
            'pager'   => $this->model->pager,
            'filters' => compact('keyword', 'role', 'perPage'),
            'counts'  => $counts,
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $user = $this->findOrFail($id);

        return $this->response->setJSON([
            'success' => true,
            'user'    => [
                'id'           => (int) $user['id'],
                'username'     => $user['username'],
                'display_name' => $user['display_name'],
                'role'         => $user['role'],
                'role_label'   => $this->roleLabel($user['role']),
                'admin_pin_status' => ! empty($user['admin_login_pin_hash']) ? 'Sudah diatur' : 'Belum diatur',
                'created_at'   => $this->formatDate($user['created_at']),
                'updated_at'   => $this->formatDate($user['updated_at']),
            ],
        ]);
    }

    public function sessions(): string
    {
        $keyword = trim((string) $this->request->getGet('q'));
        $role    = trim((string) $this->request->getGet('role'));
        $manager = new AccountSessionManager();
        $manager->pruneExpired();

        $builder = db_connect()->table('user_sessions AS user_sessions')
            ->select([
                'user_sessions.user_id',
                'user_sessions.ip_address',
                'user_sessions.user_agent',
                'user_sessions.last_seen_at',
                'user_sessions.expires_at',
                'user_sessions.created_at AS session_started_at',
                'users.username',
                'users.display_name',
                'users.role',
            ])
            ->join('users', 'users.id = user_sessions.user_id', 'inner')
            ->where('user_sessions.expires_at >', date('Y-m-d H:i:s'));

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('users.username', $keyword)
                ->orLike('users.display_name', $keyword)
                ->orLike('user_sessions.ip_address', $keyword)
                ->groupEnd();
        }

        if (UserRoles::isValid($role)) {
            $builder->where('users.role', $role);
        } else {
            $role = '';
        }

        $activeSessions = $builder
            ->orderBy('user_sessions.last_seen_at', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($activeSessions as &$activeSession) {
            $activeSession['device_label'] = $this->deviceLabel((string) $activeSession['user_agent']);
        }
        unset($activeSession);

        $totalUsers  = $this->model->countAllResults();
        $activeCount = db_connect()->table('user_sessions')
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->countAllResults();

        return view('kelola_akun/sessions', [
            'title'          => 'Session Account',
            'sessions'       => $activeSessions,
            'filters'        => compact('keyword', 'role'),
            'totalUsers'     => $totalUsers,
            'activeCount'    => $activeCount,
            'availableCount' => max(0, $totalUsers - $activeCount),
            'currentUserId'  => (int) session()->get('auth_user_id'),
        ]);
    }

    public function resetSession(int $id): RedirectResponse
    {
        $user = $this->findOrFail($id);

        if ((int) session()->get('auth_user_id') === $id) {
            return redirect()->to(site_url('kelola-akun/session-account'))
                ->with('error', 'Sesi admin yang sedang digunakan tidak dapat direset. Gunakan menu logout untuk keluar.');
        }

        $released = (new AccountSessionManager())->releaseAllForUser($id);

        if ($released === null) {
            return redirect()->to(site_url('kelola-akun/session-account'))
                ->with('error', "Sesi akun {$user['username']} gagal direset.");
        }

        if ($released === 0) {
            return redirect()->to(site_url('kelola-akun/session-account'))
                ->with('success', "Akun {$user['username']} sudah tidak memiliki sesi aktif.");
        }

        return redirect()->to(site_url('kelola-akun/session-account'))
            ->with('success', "Sesi akun {$user['username']} berhasil direset. Perangkat terkait akan keluar otomatis.");
    }

    public function store(): RedirectResponse
    {
        $data   = $this->payload();
        $errors = $this->validateAccount($data, null, true);

        if ($errors !== []) {
            return $this->formError('create', $data, $errors);
        }

        $insert = [
            'username'      => $data['username'],
            'display_name'  => $data['display_name'],
            'role'          => $data['role'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'admin_login_pin_hash' => $data['role'] === 'admin'
                ? password_hash($data['admin_login_pin'], PASSWORD_DEFAULT)
                : null,
        ];
        $id = $this->model->insert($insert, true);

        if ($id === false) {
            return $this->formError('create', $data, $this->model->errors() ?: ['account' => 'Akun gagal disimpan.']);
        }

        return redirect()->to(site_url('kelola-akun'))->with('success', "Akun {$data['username']} berhasil dibuat.");
    }

    public function update(int $id): RedirectResponse
    {
        $user   = $this->findOrFail($id);
        $data   = $this->payload();
        $errors = $this->validateAccount($data, $id, false);

        if ((int) session()->get('auth_user_id') === $id && $data['role'] !== 'admin') {
            $errors['role'] = 'Role akun yang sedang digunakan tidak dapat diturunkan.';
        }

        if ($user['role'] === 'admin' && $data['role'] !== 'admin' && $this->adminCount() <= 1) {
            $errors['role'] = 'Admin terakhir tidak dapat diubah menjadi role lain.';
        }

        $changesAdminCredential = $user['role'] === 'admin'
            && ($data['password'] !== '' || $data['admin_login_pin'] !== '');
        if ($changesAdminCredential) {
            if ($data['current_password'] === '' || ! password_verify($data['current_password'], (string) $user['password_hash'])) {
                $errors['current_password'] = 'Password lama tidak sesuai.';
            }

            if (! empty($user['admin_login_pin_hash'])
                && ($data['current_admin_login_pin'] === ''
                    || ! password_verify($data['current_admin_login_pin'], (string) $user['admin_login_pin_hash']))) {
                $errors['current_admin_login_pin'] = 'PIN lama tidak sesuai.';
            }
        }

        if ($errors !== []) {
            return $this->formError('edit', $data, $errors, $id);
        }

        $update = [
            'username'     => $data['username'],
            'display_name' => $data['display_name'],
            'role'         => $data['role'],
        ];

        if ($data['password'] !== '') {
            $update['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if ($data['role'] !== 'admin') {
            $update['admin_login_pin_hash'] = null;
        } elseif ($data['admin_login_pin'] !== '') {
            $update['admin_login_pin_hash'] = password_hash($data['admin_login_pin'], PASSWORD_DEFAULT);
        }

        if (! $this->model->update($id, $update)) {
            return $this->formError('edit', $data, $this->model->errors() ?: ['account' => 'Perubahan akun gagal disimpan.'], $id);
        }

        if ((int) session()->get('auth_user_id') === $id) {
            session()->set([
                'auth_username'     => $data['username'],
                'auth_display_name' => $data['display_name'],
                'auth_role'         => $data['role'],
            ]);
        }

        return redirect()->to(site_url('kelola-akun'))->with('success', "Akun {$data['username']} berhasil diperbarui.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = $this->findOrFail($id);

        if ((int) session()->get('auth_user_id') === $id) {
            return redirect()->to(site_url('kelola-akun'))->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        }

        if ($user['role'] === 'admin' && $this->adminCount() <= 1) {
            return redirect()->to(site_url('kelola-akun'))->with('error', 'Admin terakhir tidak dapat dihapus.');
        }

        if (! $this->deleteRecord($this->model, 'users', $id)) {
            return redirect()->to(site_url('kelola-akun'))->with('error', 'Akun gagal dihapus.');
        }

        (new AccountSessionManager())->releaseAllForUser($id);

        return redirect()->to(site_url('kelola-akun'))->with('success', "Akun {$user['username']} berhasil dihapus permanen.");
    }

    private function payload(): array
    {
        return [
            'username'     => strtolower(trim((string) $this->request->getPost('username'))),
            'display_name' => trim((string) $this->request->getPost('display_name')),
            'role'         => strtolower(trim((string) $this->request->getPost('role'))),
            'password'     => (string) $this->request->getPost('password'),
            'admin_login_pin' => trim((string) $this->request->getPost('admin_login_pin')),
            'current_password' => (string) $this->request->getPost('current_password'),
            'current_admin_login_pin' => trim((string) $this->request->getPost('current_admin_login_pin')),
        ];
    }

    private function validateAccount(array $data, ?int $id, bool $passwordRequired): array
    {
        $passwordRule = $passwordRequired
            ? 'required|min_length[8]|max_length[255]'
            : 'permit_empty|min_length[8]|max_length[255]';
        $validation = service('validation');
        $validation->setRules([
            'username'     => [
                'label' => 'Username',
                'rules' => 'required|min_length[3]|max_length[100]|regex_match[/^[a-zA-Z0-9._-]+$/]',
                'errors' => ['regex_match' => 'Username hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.'],
            ],
            'display_name' => 'required|max_length[150]',
            'role'         => 'required|in_list[' . implode(',', array_keys(UserRoles::LABELS)) . ']',
            'password'     => $passwordRule,
            'admin_login_pin' => [
                'label' => 'PIN login admin',
                'rules' => 'permit_empty|regex_match[/^[0-9]{6}$/]',
                'errors' => ['regex_match' => 'PIN login admin harus terdiri dari tepat 6 angka.'],
            ],
            'current_password' => 'permit_empty|max_length[255]',
            'current_admin_login_pin' => [
                'label' => 'PIN lama',
                'rules' => 'permit_empty|regex_match[/^[0-9]{6}$/]',
                'errors' => ['regex_match' => 'PIN lama harus terdiri dari tepat 6 angka.'],
            ],
        ]);

        $validation->run($data);
        $errors = $validation->getErrors();
        $query  = $this->model->where('username', $data['username']);

        if ($id !== null) {
            $query->where('id !=', $id);
        }

        if ($data['username'] !== '' && $query->first() !== null) {
            $errors['username'] = 'Username sudah digunakan oleh akun lain.';
        }

        if ($data['role'] === 'admin') {
            $hasExistingPin = false;
            if ($id !== null) {
                $existing = $this->model->find($id);
                $hasExistingPin = $existing !== null && ! empty($existing['admin_login_pin_hash']);
            }
            if ($data['admin_login_pin'] === '' && ! $hasExistingPin) {
                $errors['admin_login_pin'] = 'PIN login admin wajib diatur untuk mengeluarkan sesi dari perangkat lain.';
            }
        }

        return $errors;
    }

    private function formError(string $modal, array $data, array $errors, ?int $id = null): RedirectResponse
    {
        unset($data['password'], $data['admin_login_pin'], $data['current_password'], $data['current_admin_login_pin']);
        if ($id !== null) {
            $existing = $this->model->find($id);
            $data['has_admin_login_pin'] = $existing !== null && ! empty($existing['admin_login_pin_hash']);
            $data['original_role'] = $existing['role'] ?? $data['role'];
        }

        return redirect()->to(site_url('kelola-akun'))
            ->with('errors', $errors)
            ->with('account_modal', $modal)
            ->with('account_form_data', $data)
            ->with('account_edit_id', $id);
    }

    private function adminCount(): int
    {
        return $this->model->where('role', 'admin')->countAllResults();
    }

    private function findOrFail(int $id): array
    {
        $user = $this->model->find($id);

        if ($user === null) {
            throw PageNotFoundException::forPageNotFound('Akun tidak ditemukan.');
        }

        return $user;
    }

    private function roleLabel(string $role): string
    {
        return UserRoles::label($role);
    }

    private function formatDate(?string $date): string
    {
        return $date ? date('d-m-Y H:i', strtotime($date)) . ' WIB' : '-';
    }

    private function deviceLabel(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/')     => 'Microsoft Edge',
            str_contains($userAgent, 'OPR/')     => 'Opera',
            str_contains($userAgent, 'Chrome/')  => 'Google Chrome',
            str_contains($userAgent, 'Firefox/') => 'Mozilla Firefox',
            str_contains($userAgent, 'Safari/')  => 'Safari',
            default                              => 'Browser tidak dikenal',
        };
        $platform = match (true) {
            str_contains($userAgent, 'Windows')             => 'Windows',
            str_contains($userAgent, 'Android')             => 'Android',
            str_contains($userAgent, 'iPhone'),
            str_contains($userAgent, 'iPad')                => 'iOS',
            str_contains($userAgent, 'Macintosh')           => 'macOS',
            str_contains($userAgent, 'Linux')               => 'Linux',
            default                                         => 'Perangkat tidak dikenal',
        };

        return $browser . ' · ' . $platform;
    }
}
