<?php
namespace App\Controllers;
use App\Controllers\BaseController;

class Entretien extends BaseController
{
    public function liste()
    {
        $user = session()->get('user');
        if ($user == null) {
            return redirect()->to(base_url('login'));
        }

        $db = \Config\Database::connect();

        $builder = $db->table('candidature');
        $builder->select('candidature.id,
                          utilisateur.nom AS nom,
                          poste.libelle AS poste,
                          statut_candidature.libelle AS statut');
        $builder->join('cv', 'candidature.cv_id = cv.id');
        $builder->join('utilisateur', 'cv.utilisateur_id = utilisateur.id');
        $builder->join('annonce', 'candidature.annonce_id = annonce.id');
        $builder->join('besoin', 'annonce.besoin_id = besoin.id');
        $builder->join('poste', 'besoin.poste_id = poste.id');
        $builder->join('statut_candidature', 'candidature.statut_id = statut_candidature.id');
        $builder->where('statut_candidature.libelle', 'Accepté');
        $builder->orderBy('candidature.date_candidature', 'DESC');

        $data['candidatures'] = $builder->get()->getResultArray();

        return view('entretiens', $data);
    }

    public function demander($id)
    {
        $user = session()->get('user');
        if ($user == null) {
            return redirect()->to(base_url('login'));
        }

        $db = \Config\Database::connect();

        $builder = $db->table('candidature');
        $builder->select('candidature.id, utilisateur.nom AS nom, poste.libelle AS poste');
        $builder->join('cv', 'candidature.cv_id = cv.id');
        $builder->join('utilisateur', 'cv.utilisateur_id = utilisateur.id');
        $builder->join('annonce', 'candidature.annonce_id = annonce.id');
        $builder->join('besoin', 'annonce.besoin_id = besoin.id');
        $builder->join('poste', 'besoin.poste_id = poste.id');
        $builder->where('candidature.id', (int) $id);

        $data['candidature'] = $builder->get()->getRowArray();
        if (!$data['candidature']) {
            return redirect()->to(base_url('entretiens'))->with('error', 'Candidature introuvable.');
        }

        return view('form_entretien', $data);
    }

    public function save()
    {
        $user = session()->get('user');
        if ($user == null) {
            return redirect()->to(base_url('login'));
        }

        $candidature_id = (int) $this->request->getPost('candidature_id');
        $date = $this->request->getPost('date_entretien');
        $lieu = trim((string) $this->request->getPost('lieu'));
        $commentaire = trim((string) $this->request->getPost('commentaire'));

        if ($candidature_id <= 0 || $date === '' || $lieu === '') {
            return redirect()->back()->withInput()->with('error', 'Date et lieu sont obligatoires.');
        }

        $db = \Config\Database::connect();

        $statut = $db->table('statut_entretien')->where('libelle', 'Proposé')->get()->getRowArray();
        if (!$statut) {
            return redirect()->back()->withInput()->with('error', "Statut initial introuvable.");
        }

        $db->table('entretien')->insert([
            'candidature_id' => $candidature_id,
            'date_entretien' => $date,
            'lieu' => $lieu,
            'commentaire' => $commentaire,
            'statut_id' => $statut['id'],
        ]);

        return redirect()->to(base_url('entretiens'))->with('success', 'Entretien demandé avec succès.');
    }

    public function mes()
    {
        $user = session()->get('user');
        if ($user == null) {
            return redirect()->to(base_url('login'));
        }

        $db = \Config\Database::connect();

        $builder = $db->table('entretien');
        $builder->select('entretien.id,
                          entretien.date_entretien,
                          entretien.lieu,
                          entretien.commentaire,
                          statut_entretien.libelle AS statut,
                          poste.libelle AS poste');

        $builder->join('candidature', 'entretien.candidature_id = candidature.id');

        $builder->join('cv', 'candidature.cv_id = cv.id');

        $builder->join('annonce', 'candidature.annonce_id = annonce.id');

        $builder->join('besoin', 'annonce.besoin_id = besoin.id');

        $builder->join('poste', 'besoin.poste_id = poste.id');

        $builder->join('statut_entretien', 'entretien.statut_id = statut_entretien.id');

        $builder->where('cv.utilisateur_id', $user['id']);

        $builder->orderBy('entretien.date_entretien', 'DESC');

        $data['entretiens'] = $builder->get()->getResultArray();

        return view('mes_entretiens', $data);
    }

    public function retour()
    {
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
                          poste.libelle AS poste,
                          entretien.date_entretien AS date_entretien');
        $builder->join('cv', 'candidature.cv_id = cv.id');

        $builder->join('utilisateur', 'cv.utilisateur_id = utilisateur.id');

        $builder->join('annonce', 'candidature.annonce_id = annonce.id');

        $builder->join('besoin', 'annonce.besoin_id = besoin.id');

        $builder->join('poste', 'besoin.poste_id = poste.id');

        $builder->join('statut_candidature', 'candidature.statut_id = statut_candidature.id');

        $builder->join('entretien', 'entretien.candidature_id = candidature.id');

        $builder->join('statut_entretien', 'entretien.statut_id = statut_entretien.id');

    
// ✅ APRÈS
$builder->where('statut_candidature.libelle', 'En attente entretien');

        $builder->where('statut_entretien.libelle', 'Accepté');

        $builder->orderBy('candidature.date_candidature', 'DESC');

        $data['candidatures'] = $builder->get()->getResultArray();

        return view('retour_entretien', $data);
    }


    public function embaucher($id)
    {
        return $this->finaliser($id, 'Embauché');
    }

    public function refuserRetour($id)
    {
        return $this->finaliser($id, 'Non retenu');
    }

    private function finaliser($id, $libelle)
    {
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

        $entretien = $db->table('entretien')->where('candidature_id', (int) $id)->get()->getRowArray();
        if ($entretien && strtotime($entretien['date_entretien']) > time()) {
            return redirect()->to(base_url('entretiens/retour'))->with('error', "L'entretien n'a pas encore eu lieu.");
        }

        $statut = $db->table('statut_candidature')->where('libelle', $libelle)->get()->getRowArray();
        if (!$statut) {
            return redirect()->back()->with('error', 'Statut introuvable.');
        }

        $db->table('candidature')
           ->where('id', (int) $id)
           ->update(['statut_id' => $statut['id']]);

        return redirect()->to(base_url('entretiens/retour'))->with('success', 'Retour enregistré.');
    }

    public function accepter($id)
    {
        return $this->changerStatut($id, 'Accepté');
    }

    public function refuser($id)
    {
        return $this->changerStatut($id, 'Refusé');
    }

    private function changerStatut($id, $libelle)
    {
        $user = session()->get('user');
        if ($user == null) {
            return redirect()->to(base_url('login'));
        }

        $db = \Config\Database::connect();

        $statut = $db->table('statut_entretien')->where('libelle', $libelle)->get()->getRowArray();
        if (!$statut) {
            return redirect()->back()->with('error', 'Statut introuvable.');
        }

        $entretien = $db->table('entretien')->where('id', (int) $id)->get()->getRowArray();

        $db->table('entretien')
           ->where('id', (int) $id)
           ->update(['statut_id' => $statut['id']]);

        if ($libelle === 'Accepté' && $entretien) {
 
$statutCand = $db->table('statut_candidature')
                 ->where('libelle', "En attente entretien")
                 ->get()->getRowArray();


                            if ($statutCand) {
                $db->table('candidature')
                   ->where('id', $entretien['candidature_id'])
                   ->update(['statut_id' => $statutCand['id']]);
            }
        }

        return redirect()->to(base_url('entretiens/mes'))->with('success', 'Reponse enregistree.');
    }
}