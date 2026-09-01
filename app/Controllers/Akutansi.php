<?php

namespace App\Controllers;

class Akutansi extends BaseController
{
    public function index(): string
    {
        return view('akutansi/index', [
            'title' => 'Akutansi',
        ]);
    }
}
