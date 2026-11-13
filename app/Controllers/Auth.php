<?php

namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\UtilisateurModel;
use App\Services\AuditService;

/**
 * Authentification par session, mots de passe hachés, CSRF activé.
 */
class Auth extends BaseController
{
    public function loginForm()
    {
        if ($this->utilisateur()) {
            return redirect()->to(base_url('dashboard'));
        }
        return view('auth/login');
    }

    public function login()
    {
        $email = trim((string) $this->request->getPost('email'));
        $mdp   = (string) $this->request->getPost('mdp');

        if ($email === '' || $mdp === '') {
            return redirect()->back()->withInput()->with('error', 'Email et mot de passe obligatoires.');
        }

        $model = new UtilisateurModel();
        $user  = $model->where('email', $email)->first();

        if (! $user || (int) $user['actif'] !== 1 || ! password_verify($mdp, $user['mdp'])) {
            return redirect()->back()->withInput()->with('error', 'Identifiants incorrects.');
        }

        $role = (new RoleModel())->find((int) $user['role_id']);

        session()->regenerate();
        session()->set('user', [
            'id'      => (int) $user['id'],
            'nom'     => $user['nom'],
            'prenom'  => $user['prenom'],
            'email'     => $user['email'],
            'telephone' => $user['telephone'] ?? null,
            'role_id'   => (int) $user['role_id'],
            'role'      => $role['libelle'] ?? 'candidat',
        ]);

        // Permissions chargées une seule fois à la connexion.
        session()->set('permissions', (new \App\Services\PermissionService())->charger((int) $user['role_id']));

        $model->update((int) $user['id'], ['derniere_connexion' => date('Y-m-d H:i:s')]);
        (new AuditService())->log((int) $user['id'], 'auth.login', 'utilisateur', (int) $user['id']);

        return redirect()->to(base_url('dashboard'))->with('success', 'Bienvenue ' . $user['prenom'] . ' !');
    }

    public function inscriptionForm()
    {
        if ($this->utilisateur()) {
            return redirect()->to(base_url('dashboard'));
        }
        return view('auth/inscription');
    }

    public function inscription()
    {
        $nom      = trim((string) $this->request->getPost('nom'));
        $prenom   = trim((string) $this->request->getPost('prenom'));
        $email    = trim((string) $this->request->getPost('email'));
        $telephone = trim((string) $this->request->getPost('telephone'));
        $mdp      = (string) $this->request->getPost('mdp');
        $mdp2     = (string) $this->request->getPost('mdp2');

        if ($nom === '' || $email === '' || $mdp === '') {
            return redirect()->back()->withInput()->with('error', 'Nom, email et mot de passe sont obligatoires.');
        }
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Adresse email invalide.');
        }
        if (strlen($mdp) < 4) {
            return redirect()->back()->withInput()->with('error', 'Le mot de passe doit contenir au moins 4 caractères.');
        }
        if ($mdp !== $mdp2) {
            return redirect()->back()->withInput()->with('error', 'Les mots de passe ne correspondent pas.');
        }

        $model = new UtilisateurModel();
        if ($model->where('email', $email)->first()) {
            return redirect()->back()->withInput()->with('error', 'Cette adresse email est déjà utilisée.');
        }

        $roleCandidat = (new RoleModel())->where('libelle', 'candidat')->first();

        $id = $model->insert([
            'nom'       => $nom,
            'prenom'    => $prenom,
            'email'     => $email,
            'telephone' => $telephone ?: null,
            'mdp'       => password_hash($mdp, PASSWORD_DEFAULT),
            'role_id'   => (int) $roleCandidat['id'],
            'actif'     => 1,
        ]);

        (new AuditService())->log((int) $id, 'auth.inscription', 'utilisateur', (int) $id);

        return redirect()->to(base_url('login'))->with('success', 'Compte créé. Vous pouvez maintenant vous connecter.');
    }

    public function logout()
    {
        $user = $this->utilisateur();
        if ($user) {
            (new AuditService())->log((int) $user['id'], 'auth.logout', 'utilisateur', (int) $user['id']);
        }
        session()->destroy();
        return redirect()->to(base_url('login'))->with('success', 'Vous êtes déconnecté.');
    }
}
