<?php

namespace App\Modules\BagianUmum\Controllers;

use App\Controllers\BaseController;

class NonBelanjaModal extends BaseController
{
    public function index(): string
    {
        return view('modules/bagian_umum/non_belanja_modal/index', [
            'title' => 'Non Belanja Modal',
        ]);
    }
}
