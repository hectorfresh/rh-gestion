<?php

namespace App\Controllers;

use App\Models\Utilisateur\UtilisateurModel;
use App\Controllers\BaseController;

class Test extends BaseController
{
    public function index(): string
    {
     return view('test');
    }

 

    

}
