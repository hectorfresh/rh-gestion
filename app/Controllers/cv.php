<?php
namespace App\controllers;
use App\Models\cvModel;
use App\Controllers\BaseController;

class cv extends BaseController
{
    public function getPosteAnnonce($idAnnonce){
        if(session()->get('user')==null){
            return redirect()->to('/login');
        }

        $db = \Config\Database::connect();
        $builder = $db->table('annonce');
        $builder->select('annonce.id as annonce_id, poste.libelle as titre');
        $builder->join('besoin', 'besoin.id=annonce.besoin_id');
        $builder->join('poste', 'besoin.poste_id=poste.id');
        $builder->where('annonce.id', $idAnnonce);

        $poste = $builder->get()->getRowArray();

        $data['poste'] = $poste;

        // Données pour les listes déroulantes (langues et niveaux)
        $data['langues'] = $db->table('langue')->get()->getResultArray();
        $data['niveaux'] = $db->table('niveau')->get()->getResultArray();

        return view('postule_cv', $data);
    }
    
    public function insert(){

     if(session()->get('user')==null){
            return redirect()->to('/login');
        }

    $utilisateur_id = $user['id'];

    $titre  = trim((string) $this->request->getPost('titre'));
    $descri = trim((string) $this->request->getPost('descri'));

    $Email     = trim((string) $this->request->getPost('email'));
    $telephone = trim((string) $this->request->getPost('telephone'));
    $adresse   = trim((string) $this->request->getPost('adresse'));
    $ville     = trim((string) $this->request->getPost('ville'));

    $diplomes       = (array) $this->request->getPost('diplome');
    $etablissements = (array) $this->request->getPost('etablissement');
    $form_debuts    = (array) $this->request->getPost('formation_debut');
    $form_fins      = (array) $this->request->getPost('formation_fin');

    $postes      = (array) $this->request->getPost('poste_exp');
    $entreprises = (array) $this->request->getPost('entreprise');
    $exp_debuts  = (array) $this->request->getPost('exp_debut');
    $exp_fins    = (array) $this->request->getPost('exp_fin');

    $langue_ids = (array) $this->request->getPost('langue_id');
    $niveau_ids = (array) $this->request->getPost('niveau_id');

    $annonce_cv = (int) $this->request->getPost('annonce_id');

    if ($titre === '' || $Email === '' || $telephone === '' || $annonce_cv <= 0) {
        return redirect()->back()->withInput()->with('error', 'Titre, email, téléphone et poste sont obligatoires.');
    }
    if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        return redirect()->back()->withInput()->with('error', 'Email invalide.');
    }

    $db = \Config\Database::connect();

    $statut = $db->query("SELECT id FROM statut_candidature WHERE libelle = 'En attente'")->getRowArray();
    if (!$statut) {
        return redirect()->back()->withInput()->with('error', "Statut initial 'En attente' introuvable en base.");
    }

    $db->transBegin();

    try {
        $req = sprintf("INSERT INTO cv(utilisateur_id, titre, descri, created_at) VALUES(%d, %s, %s, NOW())",
            $utilisateur_id, $db->escape($titre), $db->escape($descri));
        $db->query($req);
        $cv_id = $db->insertID();

        $req2 = sprintf("INSERT INTO cv_identite(cv_id, email, telephone, adresse, ville) VALUES(%d, %s, %s, %s, %s)",
            $cv_id, $db->escape($Email), $db->escape($telephone), $db->escape($adresse), $db->escape($ville));
        $db->query($req2);

        foreach ($diplomes as $i => $diplome) {
            $diplome = trim($diplome);
            if ($diplome === '') continue; 

            $etablissement = trim($etablissements[$i] ?? '');
            $date_debut = (($form_debuts[$i] ?? '') !== '') ? $form_debuts[$i] : null;
            $date_fin   = (($form_fins[$i] ?? '') !== '') ? $form_fins[$i] : null;

            $req3 = sprintf("INSERT INTO cv_formation(cv_id, diplome, etablissement, date_debut, date_fin) VALUES(%d, %s, %s, %s, %s)",
                $cv_id, $db->escape($diplome), $db->escape($etablissement), $db->escape($date_debut), $db->escape($date_fin));
            $db->query($req3);
        }

        foreach ($postes as $i => $poste) {
            $poste = trim($poste);
            if ($poste === '') continue;

            $entreprise = trim($entreprises[$i] ?? '');
            $date_debut = (($exp_debuts[$i] ?? '') !== '') ? $exp_debuts[$i] : null;
            $date_fin   = (($exp_fins[$i] ?? '') !== '') ? $exp_fins[$i] : null;

            $req4 = sprintf("INSERT INTO cv_experience(cv_id, poste, entreprise, date_debut, date_fin) VALUES(%d, %s, %s, %s, %s)",
                $cv_id, $db->escape($poste), $db->escape($entreprise), $db->escape($date_debut), $db->escape($date_fin));
            $db->query($req4);
        }

        foreach ($langue_ids as $i => $langue_id) {
            $niveau_id = $niveau_ids[$i] ?? '';
            if ($langue_id === '' || $niveau_id === '') continue;

            $req5 = sprintf("INSERT INTO cv_langue(cv_id, langue_id, niveau_id) VALUES(%d, %d, %d)",
                $cv_id, (int) $langue_id, (int) $niveau_id);
            $db->query($req5);
        }

        $req6 = sprintf("INSERT INTO candidature(annonce_id, cv_id, date_candidature, statut_id) VALUES(%d, %d, NOW(), %d)",
            $annonce_cv, $cv_id, $statut['id']);
        $db->query($req6);

        $db->transCommit();

    } catch (\Throwable $e) {
        $db->transRollback();
        return redirect()->back()->withInput()->with('error', "misy tsy mety avereno");
    }

    return redirect()->to(base_url('/'))->with('success', 'lasa le cv');
}



}