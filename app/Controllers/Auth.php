<?php

namespace App\Controllers;

use App\Models\UtilisateurModel;
use App\Controllers\BaseController;

class Auth extends BaseController
{
    public function index(): string
    {
     return view('formulaire_login');
    }

    public function login(){

    $model = new UtilisateurModel();

        $email = $this->request->getPost('email');
        $mdp = $this->request->getPost('mdp');

        $user = $model->where('email', $email)->first();

        
        if ($user!=null && $user['mdp']==$mdp) {
            session()->set('user', [
                'id'=>$user['id'], 
                'nom'=>$user['nom'], 
                'email'=>$user['email'], 
                'role_id'=>$user['role_id'] 
                ]
            );

            return redirect()->to('/');
            
        } else {
            return redirect()->to('/login');
        }
        
    }        


    public function logout(){
        session()->destroy();
        return redirect()->to('/login');
    }

    

}
