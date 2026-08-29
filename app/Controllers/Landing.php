<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class Landing extends BaseController
{
    public function index(): string|RedirectResponse
    {
        $isLoggedIn = session()->get('auth_user_id') !== null;
        $showLanding = $this->request->getGet('view') === 'landing';

        if ($isLoggedIn && ! $showLanding) {
            $lastRoute = (string) session()->get('auth_last_route');

            return redirect()->to(site_url($lastRoute !== '' ? $lastRoute : 'dashboard'));
        }

        return view('landing/index', [
            'title'      => 'Jamkrindo Kanwil Surabaya',
            'isLoggedIn' => $isLoggedIn,
        ]);
    }
}
