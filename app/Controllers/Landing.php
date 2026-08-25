<?php

namespace App\Controllers;

class Landing extends BaseController
{
    public function index(): string
    {
        return view('landing/index', [
            'title'      => 'J-Operasional',
            'isLoggedIn' => session()->get('auth_user_id') !== null,
        ]);
    }
}
