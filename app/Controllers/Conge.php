<?php

namespace App\Controllers;

use App\Models\DemandeCongeModel;
use App\Models\HistoriqueStatutCongeModel;
use App\Models\TypeCongeModel;
use App\Services\CongeService;

/**
 * Bloc 7 : congés et absences.
 * Workflow : demande → contrôle du solde → validation manager → validation RH.
 */
class Conge extends BaseController
{
    public function mes()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $salarie = $this->salarieCourant();
        if (! $salarie) {
            return $this->erreur('Aucune fiche salarié associée à votre compte.', 'dashboard');
        }

        $db = db_connect();
        $demandes = $db->table('demande_conge dc')
            ->select('dc.*, tc.libelle AS type, tc.couleur, sc.libelle AS statut')
            ->join('type_conge tc', 'tc.id = dc.type_conge_id')
            ->join('statut_conge sc', 'sc.id = dc.statut_id')
            ->where('dc.salarie_id', (int) $salarie['id'])
            ->orderBy('dc.date_demande', 'DESC')
            ->get()->getResultArray();

        return $this->vue('conges/mes', [
            'titre' => 'Mes congés',
            'active' => 'conges',
            'salarie' => $salarie,
            'demandes' => $demandes,
            'soldes' => (new CongeService())->soldes((int) $salarie['id'], (int) date('Y')),
        ]);
    }

    public function form()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $salarie = $this->salarieCourant();
        if (! $salarie) {
            return $this->erreur('Aucune fiche salarié.', 'dashboard');
        }

        return $this->vue('conges/form', [
            'titre' => 'Nouvelle demande de congé',
            'active' => 'conges',
            'types' => (new TypeCongeModel())->orderBy('id')->findAll(),
            'soldes' => (new CongeService())->soldes((int) $salarie['id'], (int) date('Y')),
        ]);
    }

    public function store()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $salarie = $this->salarieCourant();
        if (! $salarie) {
            return $this->erreur('Aucune fiche salarié.', 'dashboard');
        }

        $resultat = (new CongeService())->demander(
            (int) $salarie['id'],
            (int) $this->request->getPost('type_conge_id'),
            (string) $this->request->getPost('date_debut'),
            (string) $this->request->getPost('date_fin'),
            (string) $this->request->getPost('motif'),
            $this->request->getPost('justificatif') ?: null,
            (int) $this->utilisateur()['id'],
            (bool) $this->request->getPost('demi_journee')
        );

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'conges/creer');
        }

        return $this->ok($resultat['message'], 'conges/' . $resultat['id']);
    }

    public function show($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $db = db_connect();
        $demande = $db->table('demande_conge dc')
            ->select('dc.*, tc.libelle AS type, tc.couleur, tc.justificatif_requis, sc.libelle AS statut,
                      u.nom, u.prenom, s.matricule, s.manager_id, u.id AS utilisateur_id,
                      vm.nom AS manager_nom, vm.prenom AS manager_prenom,
                      vr.nom AS rh_nom, vr.prenom AS rh_prenom')
            ->join('type_conge tc', 'tc.id = dc.type_conge_id')
            ->join('statut_conge sc', 'sc.id = dc.statut_id')
            ->join('salarie s', 's.id = dc.salarie_id')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->join('utilisateur vm', 'vm.id = dc.valide_manager_par', 'left')
            ->join('utilisateur vr', 'vr.id = dc.traite_par', 'left')
            ->where('dc.id', (int) $id)
            ->get()->getRowArray();

        if (! $demande) {
            return $this->erreur('Demande introuvable.', 'conges');
        }

        $monSalarie = $this->salarieCourant();
        $estProprietaire = (int) $demande['utilisateur_id'] === (int) $this->utilisateur()['id'];
        $estManager = $this->role() === 'manager' && $monSalarie && (int) $demande['manager_id'] === (int) $monSalarie['id'];

        if (! $estProprietaire && ! $this->estStaff() && ! $estManager) {
            return $this->erreur('Accès refusé.', 'dashboard');
        }

        $historique = $db->table('historique_statut_conge h')
            ->select('h.*, sc.libelle, u.nom, u.prenom')
            ->join('statut_conge sc', 'sc.id = h.statut_id')
            ->join('utilisateur u', 'u.id = h.utilisateur_id', 'left')
            ->where('h.demande_conge_id', (int) $id)
            ->orderBy('h.id', 'ASC')
            ->get()->getResultArray();

        return $this->vue('conges/show', [
            'titre' => 'Demande de congé #' . $id,
            'active' => 'conges',
            'demande' => $demande,
            'historique' => $historique,
            'est_proprietaire' => $estProprietaire,
            'peut_manager' => ($estManager || $this->role() === 'dg') && $demande['statut'] === 'En attente',
            'peut_rh' => $this->estStaff() && $demande['statut'] === 'Validé manager',
            'peut_refuser' => ($this->estStaff() || $estManager) && in_array($demande['statut'], ['En attente', 'Validé manager'], true),
            'solde' => (new CongeService())->assurerSolde((int) $demande['salarie_id'], (int) $demande['type_conge_id'], (int) date('Y', strtotime($demande['date_debut']))),
        ]);
    }

    public function validation()
    {
        if ($redirection = $this->exigerRoles(['manager', 'rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $builder = $db->table('demande_conge dc')
            ->select('dc.*, tc.libelle AS type, sc.libelle AS statut, u.nom, u.prenom, s.matricule, s.manager_id')
            ->join('type_conge tc', 'tc.id = dc.type_conge_id')
            ->join('statut_conge sc', 'sc.id = dc.statut_id')
            ->join('salarie s', 's.id = dc.salarie_id')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->orderBy('dc.date_demande', 'ASC');

        $monSalarie = $this->salarieCourant();
        if ($this->role() === 'manager') {
            $builder->where('s.manager_id', (int) ($monSalarie['id'] ?? 0))->where('sc.libelle', 'En attente');
        } elseif ($this->role() === 'rh') {
            $builder->where('sc.libelle', 'Validé manager');
        } else {
            $builder->whereIn('sc.libelle', ['En attente', 'Validé manager']);
        }

        return $this->vue('conges/validation', [
            'titre' => 'Validation des congés',
            'active' => 'conges',
            'demandes' => $builder->get()->getResultArray(),
        ]);
    }

    public function validerManager($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $commentaire = trim((string) $this->request->getPost('commentaire'));
        $resultat = (new CongeService())->validerManager((int) $id, (int) $this->utilisateur()['id'], $this->role(), $commentaire);

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'conges/' . $id);
        }
        return $this->ok($resultat['message'], 'conges/' . $id);
    }

    public function validerRh($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $commentaire = trim((string) $this->request->getPost('commentaire'));
        $resultat = (new CongeService())->validerRh((int) $id, (int) $this->utilisateur()['id'], $this->role(), $commentaire);

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'conges/' . $id);
        }
        return $this->ok($resultat['message'], 'conges/' . $id);
    }

    public function refuser($id)
    {
        if ($redirection = $this->exigerRoles(['manager', 'rh', 'dg'])) {
            return $redirection;
        }

        $commentaire = trim((string) $this->request->getPost('commentaire'));
        $resultat = (new CongeService())->refuser((int) $id, (int) $this->utilisateur()['id'], $this->role(), $commentaire);

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'conges/' . $id);
        }
        return $this->ok($resultat['message'], 'conges/' . $id);
    }

    public function annuler($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $resultat = (new CongeService())->annuler((int) $id, (int) $this->utilisateur()['id']);
        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'conges/' . $id);
        }
        return $this->ok($resultat['message'], 'conges/mes');
    }

    public function calendrier()
    {
        if ($redirection = $this->exigerRoles(['manager', 'rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $mois = $this->request->getGet('mois') ?: date('Y-m');
        [$annee, $moisNum] = (new \App\Services\PaieService())->decouperPeriode($mois);
        $debut = sprintf('%04d-%02d-01', $annee, $moisNum);
        $fin = date('Y-m-t', strtotime($debut));

        $builder = $db->table('demande_conge dc')
            ->select('dc.*, tc.libelle AS type, tc.couleur, sc.libelle AS statut, u.nom, u.prenom, s.matricule, s.manager_id')
            ->join('type_conge tc', 'tc.id = dc.type_conge_id')
            ->join('statut_conge sc', 'sc.id = dc.statut_id')
            ->join('salarie s', 's.id = dc.salarie_id')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->where('dc.date_debut <=', $fin)
            ->where('dc.date_fin >=', $debut)
            ->orderBy('dc.date_debut', 'ASC');

        $monSalarie = $this->salarieCourant();
        if ($this->role() === 'manager' && $monSalarie) {
            $builder->where('s.manager_id', (int) $monSalarie['id']);
        }

        return $this->vue('conges/calendrier', [
            'titre' => 'Calendrier d’équipe',
            'active' => 'conges',
            'mois' => $mois,
            'demandes' => $builder->get()->getResultArray(),
        ]);
    }

    public function soldes()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $salaries = $db->table('salarie s')
            ->select('s.id, s.matricule, u.nom, u.prenom, d.libelle AS departement')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->join('departement d', 'd.id = s.departement_id')
            ->where('s.actif', 1)
            ->orderBy('u.nom')
            ->get()->getResultArray();

        $congeService = new CongeService();
        foreach ($salaries as &$salarie) {
            $salarie['soldes'] = $congeService->soldes((int) $salarie['id'], (int) date('Y'));
        }
        unset($salarie);

        return $this->vue('conges/soldes', [
            'titre' => 'Soldes de congés',
            'active' => 'conges',
            'salaries' => $salaries,
        ]);
    }
}
