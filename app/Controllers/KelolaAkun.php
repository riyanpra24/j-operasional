<?php

namespace App\Controllers;

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

        $counts = ['total' => $this->model->countAll()];

        foreach (array_keys(UserRoles::LABELS) as $roleName) {
            $counts[$roleName] = $this->model->where('role', $roleName)->countAllResults();
        }

        return view('kelola_akun/index', [
            'title'   => 'Kelola Akun',
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
                'created_at'   => $this->formatDate($user['created_at']),
                'updated_at'   => $this->formatDate($user['updated_at']),
            ],
        ]);
    }

    public function store(): RedirectResponse
    {
        $data   = $this->payload();
        $errors = $this->validateAccount($data, null, true);

        if ($errors !== []) {
            return $this->formError('create', $data, $errors);
        }

        $id = $this->model->insert([
            'username'      => $data['username'],
            'display_name'  => $data['display_name'],
            'role'          => $data['role'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
        ], true);

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

        if (! $this->model->delete($id)) {
            return redirect()->to(site_url('kelola-akun'))->with('error', 'Akun gagal dihapus dari database.');
        }

        return redirect()->to(site_url('kelola-akun'))->with('success', "Akun {$user['username']} berhasil dihapus dari database.");
    }

    private function payload(): array
    {
        return [
            'username'     => strtolower(trim((string) $this->request->getPost('username'))),
            'display_name' => trim((string) $this->request->getPost('display_name')),
            'role'         => strtolower(trim((string) $this->request->getPost('role'))),
            'password'     => (string) $this->request->getPost('password'),
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

        return $errors;
    }

    private function formError(string $modal, array $data, array $errors, ?int $id = null): RedirectResponse
    {
        unset($data['password']);

        return redirect()->to(site_url('kelola-akun'))->with([
            'errors'            => $errors,
            'account_modal'     => $modal,
            'account_form_data' => $data,
            'account_edit_id'   => $id,
        ]);
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
}
