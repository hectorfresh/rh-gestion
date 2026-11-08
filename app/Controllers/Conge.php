<?php
namespace App\Controllers;

use App\Controllers\BaseController;

class Conge extends BaseController
{
    private function checkRH()
    {
        $user = session()->get('user');
        if ($user == null) return redirect()->to(base_url('login'));

        $roleModel = new \App\Models\RoleModel();
        $rh = $roleModel->where('libelle', 'rh')->first()['id'] ?? null;
        $dg = $roleModel->where('libelle', 'dg')->first()['id'] ?? null;

        if ($user['role_id'] != $rh && $user['role_id'] != $dg) {
            return redirect()->to(base_url('/'))->with('error', 'Accès refusé.');
        }
        return null;
    }

    /** Côté Employé : Voir ses soldes et son historique */
    public function mes()
    {
        $user = session()->get('user');
        if ($user == null) return redirect()->to(base_url('login'));

        $db = \Config\Database::connect();
        $annee = date('Y');

        // 1. Récupérer les soldes
        $builder = $db->table('solde_conge');
        $builder->select('solde_conge.*, type_conge.libelle, type_conge.couleur');
        $builder->join('type_conge', 'solde_conge.type_conge_id = type_conge.id');
        $builder->where('solde_conge.utilisateur_id', $user['id']);
        $builder->where('solde_conge.annee', $annee);
        $data['soldes'] = $builder->get()->getResultArray();

        // 2. Récupérer l'historique des demandes
        $builder2 = $db->table('demande_conge');
        $builder2->select('demande_conge.*, type_conge.libelle, type_conge.couleur, statut_conge.libelle AS statut_libelle');
        $builder2->join('type_conge', 'demande_conge.type_conge_id = type_conge.id');
        $builder2->join('statut_conge', 'demande_conge.statut_id = statut_conge.id');
        $builder2->where('demande_conge.utilisateur_id', $user['id']);
        $builder2->orderBy('demande_conge.date_demande', 'DESC');
        $data['demandes'] = $builder2->get()->getResultArray();

        $data['annee'] = $annee;

        return view('mes_conges', $data);
    }

    /** Formulaire de demande */
    public function formulaire()
    {
        $user = session()->get('user');
        if ($user == null) return redirect()->to(base_url('login'));

        $db = \Config\Database::connect();
        $data['types'] = $db->table('type_conge')->get()->getResultArray();

        return view('form_conge', $data);
    }

    /** Enregistrer la demande */
    public function save()
    {
        $user = session()->get('user');
        if ($user == null) return redirect()->to(base_url('login'));

        $type_id    = (int) $this->request->getPost('type_conge_id');
        $date_debut = $this->request->getPost('date_debut');
        $date_fin   = $this->request->getPost('date_fin');
        $motif      = trim((string) $this->request->getPost('motif'));

        // 1. Validations basiques
        if ($type_id <= 0 || empty($date_debut) || empty($date_fin)) {
            return redirect()->back()->withInput()->with('error', 'Tous les champs obligatoires doivent être remplis.');
        }

        if (strtotime($date_debut) > strtotime($date_fin)) {
            return redirect()->back()->withInput()->with('error', 'La date de fin doit être après la date de début.');
        }

        if (strtotime($date_debut) < strtotime(date('Y-m-d'))) {
            return redirect()->back()->withInput()->with('error', 'On ne peut pas demander un congé dans le passé.');
        }

        // 2. Calcul du nombre de jours (calendaires)
        $nombre_jours = (strtotime($date_fin) - strtotime($date_debut)) / 86400 + 1;

        $db = \Config\Database::connect();

        // 3. Vérifier le chevauchement
        $chevauchement = $db->table('demande_conge')
            ->where('utilisateur_id', $user['id'])
            ->whereIn('statut_id', [1, 2]) // En attente ou Accepté
            ->groupStart()
                ->where('date_debut <=', $date_fin)
                ->where('date_fin >=', $date_debut)
            ->groupEnd()
            ->countAllResults();

        if ($chevauchement > 0) {
            return redirect()->back()->withInput()->with('error', 'Vous avez déjà un congé sur cette période.');
        }

        // 4. Vérifier le solde
        $annee = date('Y');
        $solde = $db->table('solde_conge')
            ->where('utilisateur_id', $user['id'])
            ->where('type_conge_id', $type_id)
            ->where('annee', $annee)
            ->get()->getRowArray();

        if (!$solde) {
            return redirect()->back()->withInput()->with('error', 'Aucun solde configuré pour ce type de congé.');
        }

        $disponible = $solde['jours_acquis'] - $solde['jours_pris'];
        if ($nombre_jours > $disponible) {
            return redirect()->back()->withInput()->with('error', "Solde insuffisant. Vous avez $disponible jours restants.");
        }

        // 5. Enregistrer
        $statutAttente = $db->table('statut_conge')->where('libelle', 'En attente')->get()->getRowArray();

        $db->table('demande_conge')->insert([
            'utilisateur_id' => $user['id'],
            'type_conge_id'  => $type_id,
            'date_debut'     => $date_debut,
            'date_fin'       => $date_fin,
            'nombre_jours'   => $nombre_jours,
            'motif'          => $motif,
            'statut_id'      => $statutAttente['id'],
            'date_demande'   => date('Y-m-d H:i:s')
        ]);

        return redirect()->to(base_url('conges/mes'))->with('success', "Demande de $nombre_jours jours envoyée avec succès.");
    }

    /** Annuler une demande en attente */
    public function annuler($id)
    {
        $user = session()->get('user');
        if ($user == null) return redirect()->to(base_url('login'));

        $db = \Config\Database::connect();
        $demande = $db->table('demande_conge')->where('id', $id)->where('utilisateur_id', $user['id'])->get()->getRowArray();

        $statutAttente = $db->table('statut_conge')->where('libelle', 'En attente')->get()->getRowArray();
        $statutAnnule  = $db->table('statut_conge')->where('libelle', 'Annulé')->get()->getRowArray();

        if ($demande && $demande['statut_id'] == $statutAttente['id']) {
            $db->table('demande_conge')->where('id', $id)->update([
                'statut_id' => $statutAnnule['id'],
                'date_traitement' => date('Y-m-d H:i:s')
            ]);
            return redirect()->to(base_url('conges/mes'))->with('success', 'Demande annulée.');
        }

        return redirect()->to(base_url('conges/mes'))->with('error', 'Impossible d\'annuler cette demande.');
    }

    /** Côté RH : Voir les demandes à valider */
    public function validation()
    {
        if ($redir = $this->checkRH()) return $redir;

        $db = \Config\Database::connect();
        $builder = $db->table('demande_conge');
        $builder->select('demande_conge.*, utilisateur.nom, type_conge.libelle AS type_libelle, statut_conge.libelle AS statut_libelle');
        $builder->join('utilisateur', 'demande_conge.utilisateur_id = utilisateur.id');
        $builder->join('type_conge', 'demande_conge.type_conge_id = type_conge.id');
        $builder->join('statut_conge', 'demande_conge.statut_id = statut_conge.id');
        $builder->where('statut_conge.libelle', 'En attente');
        $builder->orderBy('demande_conge.date_demande', 'ASC');

        $data['demandes'] = $builder->get()->getResultArray();

        return view('validation_conges', $data);
    }

    /** RH : Accepter la demande */
    public function accepter($id)
    {
        if ($redir = $this->checkRH()) return $redir;
        $user = session()->get('user');
        $db = \Config\Database::connect();

        $demande = $db->table('demande_conge')->where('id', $id)->get()->getRowArray();
        if (!$demande) return redirect()->back()->with('error', 'Demande introuvable.');

        $statutAttente = $db->table('statut_conge')->where('libelle', 'En attente')->get()->getRowArray();
        $statutAccepte = $db->table('statut_conge')->where('libelle', 'Accepté')->get()->getRowArray();

        if ($demande['statut_id'] != $statutAttente['id']) return redirect()->back()->with('error', 'Demande déjà traitée.');

        $db->transBegin();
        try {
            // 1. Accepter la demande
            $db->table('demande_conge')->where('id', $id)->update([
                'statut_id' => $statutAccepte['id'],
                'date_traitement' => date('Y-m-d H:i:s'),
                'traite_par' => $user['id']
            ]);

            // 2. Déduire du solde
            $annee = date('Y', strtotime($demande['date_debut']));
            $solde = $db->table('solde_conge')
                ->where('utilisateur_id', $demande['utilisateur_id'])
                ->where('type_conge_id', $demande['type_conge_id'])
                ->where('annee', $annee)->get()->getRowArray();

            if ($solde) {
                $db->table('solde_conge')->where('id', $solde['id'])->update([
                    'jours_pris' => $solde['jours_pris'] + $demande['nombre_jours']
                ]);
            }

            $db->transCommit();
            return redirect()->to(base_url('conges/validation'))->with('success', 'Congé accepté et solde mis à jour.');

        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /** RH : Refuser la demande */
    public function refuser($id)
    {
        if ($redir = $this->checkRH()) return $redir;
        $user = session()->get('user');
        $db = \Config\Database::connect();

        $statutAttente = $db->table('statut_conge')->where('libelle', 'En attente')->get()->getRowArray();
        $statutRefuse  = $db->table('statut_conge')->where('libelle', 'Refusé')->get()->getRowArray();

        $db->table('demande_conge')
            ->where('id', $id)
            ->where('statut_id', $statutAttente['id'])
            ->update([
                'statut_id' => $statutRefuse['id'],
                'date_traitement' => date('Y-m-d H:i:s'),
                'traite_par' => $user['id']
            ]);

        return redirect()->to(base_url('conges/validation'))->with('success', 'Demande refusée.');
    }
}