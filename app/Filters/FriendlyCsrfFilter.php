<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;

class FriendlyCsrfFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        try {
            service('security')->verify($request);
        } catch (SecurityException) {
            $message = 'Sesi keamanan formulir telah diperbarui. Silakan coba kirim kembali.';

            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                    ->setJSON([
                        'success' => false,
                        'message' => $message,
                        'errors' => [$message],
                        'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()],
                        'csrfToken' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                    ]);
            }

            return redirect()->to(site_url('/'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
