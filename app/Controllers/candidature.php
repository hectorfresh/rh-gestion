<?php
namespace App\Controllers;
use App\Models\CandidatureModel;
use App\Models\AnnonceModel;
use App\Controllers\BaseController;

class Candidature extends BaseController
{
    public function form() {
        if(session()->get('user')==null){
            return redirect()->to('/login');
        }
        else{
            return view('postule_cv');
        }
              
    }
    
   
       
}