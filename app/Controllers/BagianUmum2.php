<?php

namespace App\Controllers;

class BagianUmum2 extends BaseController
{
    public function index(): string
    {
        return view('bagian_umum_2/index', [
            'title' => 'Bagian Umum 2',
        ]);
    }
}
