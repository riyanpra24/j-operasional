<?php

namespace App\Filters;

use App\Libraries\AccountSessionManager;
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
            $manager   = new AccountSessionManager();

            // Beri batas 2 jam pada sesi lama yang dibuat sebelum fitur ini tersedia.
            if ($expiresAt === 0) {
                $expiresAt = time() + self::LOGIN_LIFETIME_SECONDS;
                $session->set('auth_expires_at', $expiresAt);
            }

            if (time() >= $expiresAt) {
                $manager->release((int) $userId, (string) $session->get('auth_session_token'));
                $this->clearAuthentication();

                return $this->expiredResponse($request);
            }

            $token = (string) $session->get('auth_session_token');

            // Sesi lama yang sudah login sebelum migrasi mengambil kunci saat request pertama.
            if ($token === '') {
                $claim = $manager->acquire(
                    (int) $userId,
                    $expiresAt,
                    method_exists($request, 'getIPAddress') ? $request->getIPAddress() : null,
                    method_exists($request, 'getUserAgent') ? $request->getUserAgent()->getAgentString() : null,
                );

                if ($claim['status'] === 'acquired' && isset($claim['token'])) {
                    $token = $claim['token'];
                    $session->set('auth_session_token', $token);
                }
            }

            if ($token !== '' && $manager->validate((int) $userId, $token)) {
                return null;
            }

            $this->clearAuthentication();

            return $this->invalidSessionResponse($request);
        }

        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'success' => false,
                    'message' => 'Sesi login berakhir. Silakan login kembali.',
                ]);
        }

        return redirect()->to(site_url('/'))
            ->with('open_login_modal', true)
            ->with('login_error', 'Silakan login untuk mengakses sistem.');
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
                    'redirect_url' => site_url('/') . '?login=1',
                ]);
        }

        return redirect()->to(site_url('/'))
            ->with('open_login_modal', true)
            ->with('login_error', $message);
    }

    private function invalidSessionResponse(RequestInterface $request): ResponseInterface
    {
        $message = 'Sesi akun sudah tidak aktif atau akun sedang digunakan pada perangkat lain. Silakan login kembali.';

        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON([
                    'success'      => false,
                    'message'      => $message,
                    'redirect_url' => site_url('/') . '?login=1',
                ]);
        }

        return redirect()->to(site_url('/'))
            ->with('open_login_modal', true)
            ->with('login_error', $message);
    }

    private function clearAuthentication(): void
    {
        $session = session();

        $session->remove([
            'auth_user_id',
            'auth_username',
            'auth_display_name',
            'auth_role',
            'auth_expires_at',
            'auth_session_token',
            'auth_last_route',
            'is_logged_in',
        ]);
        $session->regenerate(true);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
