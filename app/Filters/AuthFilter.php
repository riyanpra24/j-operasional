<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session()->get('auth_user_id') !== null) {
            return null;
        }

        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'success' => false,
                    'message' => 'Sesi login berakhir. Silakan login kembali.',
                ]);
        }

        return redirect()->to(site_url('login'))->with('login_error', 'Silakan login untuk mengakses sistem.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
