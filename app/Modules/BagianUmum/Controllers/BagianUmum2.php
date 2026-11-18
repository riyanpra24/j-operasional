<?php

namespace App\Modules\BagianUmum\Controllers;

use App\Controllers\BaseController;

class BagianUmum2 extends BaseController
{
    public function index(): string
    {
        return view('modules/bagian_umum/bagian_umum_2/index', [
            'title' => 'Bagian Umum 2',
        ]);
    }
}
