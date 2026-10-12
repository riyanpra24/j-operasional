<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use Config\UserRoles;

class Landing extends BaseController
{
    public function index(): string|RedirectResponse
    {
        if (session()->get('auth_user_id') !== null) {
            $lastRoute = (string) session()->get('auth_last_route');

            $role = (string) session()->get('auth_role');
            $route = $lastRoute !== '' && $lastRoute !== 'dashboard'
                ? $lastRoute
                : UserRoles::defaultRoute($role);

            return redirect()->to(site_url($route));
        }

        return view('landing/index', [
            'title'      => 'Jamkrindo Kanwil Surabaya',
            'isLoggedIn' => false,
        ]);
    }
}
