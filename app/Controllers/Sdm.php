<?php

namespace App\Controllers;

class Sdm extends BaseController
{
    public function index(): string
    {
        return view('sdm/index', [
            'title' => 'SDM',
        ]);
    }
}
