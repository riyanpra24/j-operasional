<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\UserRoles;

class RoleAccessFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $role = (string) session()->get('auth_role');

        if ($role === 'admin') {
            return null;
        }

        $path = trim($request->getUri()->getPath(), '/');
        $path = preg_replace('#^index\.php/?#', '', $path) ?? $path;

        if ($path === '' || $path === 'dashboard' || $path === 'logout') {
            return null;
        }

        $allowedPrefixes = UserRoles::MODULE_PREFIXES[$role] ?? [];

        foreach ($allowedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return null;
            }
        }

        $message = 'Akun Anda tidak memiliki izin untuk membuka halaman tersebut.';

        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                ->setJSON([
                    'success' => false,
                    'message' => $message,
                ]);
        }

        return redirect()->to(site_url('dashboard'))->with('error', $message);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
