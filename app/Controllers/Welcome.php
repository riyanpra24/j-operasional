<?php

namespace App\Controllers;

use Config\UserRoles;

class Welcome extends BaseController
{
    public function index(): string
    {
        $role = (string) session()->get('auth_role');
        return view('welcome/index', [
            'title' => 'Selamat Datang',
            'displayName' => (string) session()->get('auth_display_name'),
            'roleLabel' => UserRoles::label($role),
            'nextUrl' => site_url(UserRoles::defaultRoute($role)),
        ]);
    }
}
