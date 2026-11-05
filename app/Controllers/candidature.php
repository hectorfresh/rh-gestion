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


public function valider($id)
{
    return $this->changerStatut($id, 2);
}

public function refuser($id)
{
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
