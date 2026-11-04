<?php
namespace App\Controllers;
use App\Models\CandidatureModel;
use App\Models\AnnonceModel;
use App\Controllers\BaseController;

class Candidature extends BaseController
{
    public function form() : string {
        return view('candidature');        
    }
    
   
       
}