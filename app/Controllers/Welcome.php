<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use Config\UserRoles;

class Welcome extends BaseController
{
    public function index(): RedirectResponse
    {
        $role = (string) session()->get('auth_role');

        return redirect()->to(site_url(UserRoles::defaultRoute($role)));
    }
}
