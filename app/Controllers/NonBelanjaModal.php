<?php

namespace App\Controllers;

class NonBelanjaModal extends BaseController
{
    public function index(): string
    {
        return view('non_belanja_modal/index', [
            'title' => 'Non Belanja Modal',
        ]);
    }
}
