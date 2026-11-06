<?php
namespace App\Controllers;
use App\Models\CandidatureModel;
use App\Models\AnnonceModel;
use App\Controllers\BaseController;

class Candidature extends BaseController
{
    // public function index() {
    //     if(session()->get('user')==null){
    //         return redirect()->to('/login');
    //     }
    //     else{
    //         return view('candidature');
    //     }
              
    // }public function liste()

    public function liste(){
    $user = session()->get('user');
    if ($user == null) {
        return redirect()->to(base_url('login'));
    }

    $roleModel = new \App\Models\RoleModel();
    $rh = $roleModel->where('libelle', 'rh')->first()['id'] ?? null;
    $dg = $roleModel->where('libelle', 'dg')->first()['id'] ?? null;

    if ($user['role_id'] != $rh && $user['role_id'] != $dg) {
        return redirect()->to(base_url('/'))->with('error', 'Accès refusé.');
    }

    $db = \Config\Database::connect();

$builder = $db->table('candidature');
$builder->select('candidature.id,
                  utilisateur.nom AS nom,
                  cv_identite.email AS email,
                  poste.libelle AS poste,
                  statut_candidature.libelle AS statut');

$builder->join('cv', 'candidature.cv_id = cv.id');

$builder->join('utilisateur', 'cv.utilisateur_id = utilisateur.id');

$builder->join('cv_identite', 'cv_identite.cv_id = cv.id');

$builder->join('annonce', 'candidature.annonce_id = annonce.id');

$builder->join('besoin', 'annonce.besoin_id = besoin.id');   

$builder->join('poste', 'besoin.poste_id = poste.id');      

$builder->join('statut_candidature', 'candidature.statut_id = statut_candidature.id');

$builder->orderBy('candidature.date_candidature', 'DESC');

$data['candidatures'] = $builder->get()->getResultArray();

return view('candidature', $data);
}

public function mesCandidatures()
{
    $user = session()->get('user');
    if ($user == null) {
        return redirect()->to(base_url('login'));
    }

    $db = \Config\Database::connect();

    $builder = $db->table('candidature');
    $builder->select('candidature.id,
                      poste.libelle AS poste,
                      statut_candidature.libelle AS statut,
                      candidature.date_candidature AS date_candidature,
                      annonce.id AS annonce_id');

    $builder->join('cv', 'candidature.cv_id = cv.id');

    $builder->join('annonce', 'candidature.annonce_id = annonce.id');

    $builder->join('besoin', 'annonce.besoin_id = besoin.id');

    $builder->join('poste', 'besoin.poste_id = poste.id');

    $builder->join('statut_candidature', 'candidature.statut_id = statut_candidature.id');

    $builder->where('cv.utilisateur_id', $user['id']);

    $builder->orderBy('candidature.date_candidature', 'DESC');

    $data['candidatures'] = $builder->get()->getResultArray();

    return view('mes_candidatures', $data);
}

public function fiche($id)
{
    $user = session()->get('user');
    if ($user == null) {
        return redirect()->to(base_url('login'));
    }

    $db = \Config\Database::connect();

    $builder = $db->table('candidature');
    $builder->select('candidature.id,
                      candidature.date_candidature,
                      candidature.cv_id,
                      candidature.annonce_id,
                      utilisateur.nom AS nom,
                      cv_identite.email AS email,
                      cv_identite.telephone AS telephone,
                      cv_identite.adresse AS adresse,
                      cv_identite.ville AS ville,
                      poste.libelle AS poste,
                      statut_candidature.libelle AS statut');
    $builder->join('cv', 'candidature.cv_id = cv.id');

    $builder->join('utilisateur', 'cv.utilisateur_id = utilisateur.id');

    $builder->join('cv_identite', 'cv_identite.cv_id = cv.id', 'left');

    $builder->join('annonce', 'candidature.annonce_id = annonce.id');

    $builder->join('besoin', 'annonce.besoin_id = besoin.id');

    $builder->join('poste', 'besoin.poste_id = poste.id');

    $builder->join('statut_candidature', 'candidature.statut_id = statut_candidature.id');
    
    $builder->where('candidature.id', (int) $id);

    $candidature = $builder->get()->getRowArray();
    if (!$candidature) {
        return redirect()->to(base_url('candidatures/liste'))->with('error', 'Candidature introuvable.');
    }

    // CV
    $cv = $db->table('cv')->where('id', $candidature['cv_id'])->get()->getRowArray();

    $formations = $db->table('cv_formation')
                     ->where('cv_id', $candidature['cv_id'])
                     ->get()->getResultArray();

    $experiences = $db->table('cv_experience')
                      ->where('cv_id', $candidature['cv_id'])
                      ->get()->getResultArray();

    $langues = $db->table('cv_langue')
                  ->select('langue.libelle AS langue, niveau.libelle AS niveau')
                  ->join('langue', 'langue.id = cv_langue.langue_id')
                  ->join('niveau', 'niveau.id = cv_langue.niveau_id')
                  ->where('cv_langue.cv_id', $candidature['cv_id'])
                  ->get()->getResultArray();

    $data['candidature'] = $candidature;
    $data['cv'] = $cv;
    $data['formations'] = $formations;
    $data['experiences'] = $experiences;
    $data['langues'] = $langues;

    return view('fiche_candidature', $data);
}


public function valider($id)
{
    $user = session()->get('user');
    if ($user == null) {
        return redirect()->to(base_url('login'));
    }

    $db = \Config\Database::connect();
    $roleModel = new \App\Models\RoleModel();
    $rh = $roleModel->where('libelle', 'rh')->first()['id'] ?? null;
    $dg = $roleModel->where('libelle', 'dg')->first()['id'] ?? null;

    if ($user['role_id'] != $rh && $user['role_id'] != $dg) {
        return redirect()->to(base_url('candidatures/liste'))->with('error', 'Accès refusé.');
    }

    return $this->changerStatut($id, 2);
}

public function refuser($id)
{
    $user = session()->get('user');
    if ($user == null) {
        return redirect()->to(base_url('login'));
    }

    $db = \Config\Database::connect();
    $roleModel = new \App\Models\RoleModel();
    $rh = $roleModel->where('libelle', 'rh')->first()['id'] ?? null;
    $dg = $roleModel->where('libelle', 'dg')->first()['id'] ?? null;

    if ($user['role_id'] != $rh && $user['role_id'] != $dg) {
        return redirect()->to(base_url('candidatures/liste'))->with('error', 'Accès refusé.');
    }

    return $this->changerStatut($id, 3);
}

private function changerStatut($id, int $idstatut){
    $user = session()->get('user');
    if ($user == null) {
        return redirect()->to(base_url('login'));
    }

    $db = \Config\Database::connect();

    $enAttente = $db->table('statut_candidature')->where('id', 1)->get()->getRowArray();
    $nouveau   = $db->table('statut_candidature')->where('id', $idstatut)->get()->getRowArray();


    $db->table('candidature')
       ->where('id', (int) $id)
       ->where('statut_id', $enAttente['id'])
       ->update(['statut_id' => $nouveau['id']]);

    return redirect()->to(base_url('candidatures/liste'))->with('success', 'Candidature mise à jour.');
}
}
