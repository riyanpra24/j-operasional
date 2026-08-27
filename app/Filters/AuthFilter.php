<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    private const LOGIN_LIFETIME_SECONDS = 7200;

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $userId  = $session->get('auth_user_id');

        if ($userId !== null) {
            $expiresAt = (int) $session->get('auth_expires_at');

            // Beri batas 2 jam pada sesi lama yang dibuat sebelum fitur ini tersedia.
            if ($expiresAt === 0) {
                $expiresAt = time() + self::LOGIN_LIFETIME_SECONDS;
                $session->set('auth_expires_at', $expiresAt);
            }

            if (time() < $expiresAt) {
                return null;
            }

            $session->remove([
                'auth_user_id',
                'auth_username',
                'auth_display_name',
                'auth_role',
                'auth_expires_at',
                'auth_last_route',
                'is_logged_in',
            ]);
            $session->regenerate(true);

            return $this->expiredResponse($request);
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

    private function expiredResponse(RequestInterface $request): ResponseInterface
    {
        $message = 'Sesi login telah berakhir setelah 2 jam. Silakan login kembali.';

        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'success'      => false,
                    'message'      => $message,
                    'redirect_url' => site_url('login'),
                ]);
        }

        return redirect()->to(site_url('login'))->with('login_error', $message);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
