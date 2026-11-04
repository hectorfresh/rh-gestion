<?php

namespace App\Controllers;

use App\Models\UtilisateurModel;
use App\Controllers\BaseController;
use App\Models\RoleModel;

class Auth extends BaseController
{
    public function index(): string
    {
     return view('formulaire_login');
    }
    public function creerCompteForm() : string {
        return view('create_compte');
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

    public function insertUtilisateur()
    {
        $nom      =  $this->request->getPost('nom');
        $email    =  $this->request->getPost('email');
        $password =  $this->request->getPost('password');
        $role_id  = 4;

        if ($nom === '' || $email === '' || $password === '') {
            return redirect()->to('create_compte');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Email invalide.');
        }

        $utilisateurModel = new UtilisateurModel();
        $roleModel        = new RoleModel();

        if (!$roleModel->find($role_id)) {
            return redirect()->back()->withInput()->with('error', 'Rôle par défaut introuvable.');
        }

        if ($utilisateurModel->where('email', $email)->first()) {
            return redirect()->back()->withInput()->with('error', 'Cet email est déjà utilisé.');
        }

        $utilisateurModel->insert([
            'nom'     => $nom,
            'email'   => $email,
            'mdp'     => $password,
            'role_id' => $role_id,
        ]);

        return redirect()->to(base_url('login'))->with('success', 'Compte créé, vous pouvez vous connecter.');
    }

    

}
