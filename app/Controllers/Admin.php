<?php

namespace App\Controllers;

use App\Models\BudgetDepartementModel;
use App\Models\DroitCongeModel;
use App\Models\DepartementModel;
use App\Models\JourFerieModel;
use App\Models\PosteModel;
use App\Models\TypeCongeModel;
use App\Services\AuditService;
use App\Services\BudgetService;

/**
 * Administration des référentiels : départements, postes, budgets, congés.
 */
class Admin extends BaseController
{
    public function index()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $annee = (int) ($this->request->getGet('annee') ?: date('Y'));

        return $this->vue('admin/index', [
            'titre' => 'Administration',
            'active' => 'admin',
            'annee' => $annee,
            'departements' => (new DepartementModel())->orderBy('libelle')->findAll(),
            'postes' => $db->table('poste p')
                ->select('p.*, d.libelle AS departement')
                ->join('departement d', 'd.id = p.departement_id')
                ->orderBy('p.libelle')->get()->getResultArray(),
            'budgets' => $db->table('budget_departement b')
                ->select('b.*, d.libelle AS departement')
                ->join('departement d', 'd.id = b.departement_id')
                ->where('b.annee', $annee)
                ->orderBy('d.libelle')->get()->getResultArray(),
            'types_conge' => (new TypeCongeModel())->orderBy('id')->findAll(),
            'jours_feries' => (new JourFerieModel())
                ->where('jour >=', $annee . '-01-01')
                ->where('jour <=', $annee . '-12-31')
                ->orderBy('jour')
                ->findAll(),
        ]);
    }

    public function storeDepartement()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $code = strtoupper(trim((string) $this->request->getPost('code')));
        $libelle = trim((string) $this->request->getPost('libelle'));

        if ($code === '' || $libelle === '') {
            return $this->erreur('Code et libellé obligatoires.', 'admin');
        }

        $model = new DepartementModel();
        if ($model->where('code', $code)->first()) {
            return $this->erreur('Ce code de département existe déjà.', 'admin');
        }

        $id = $model->insert([
            'code' => $code,
            'libelle' => $libelle,
            'description' => $this->request->getPost('description'),
        ]);
        (new AuditService())->log((int) $this->utilisateur()['id'], 'admin.departement.creer', 'departement', (int) $id);

        return $this->ok('Département créé.', 'admin');
    }

    public function storePoste()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $data = [
            'departement_id' => (int) $this->request->getPost('departement_id'),
            'libelle'        => trim((string) $this->request->getPost('libelle')),
            'salaire_base'   => (float) $this->request->getPost('salaire_base'),
            'salaire_max'    => (float) $this->request->getPost('salaire_max'),
            'actif'          => 1,
        ];

        if (! $data['departement_id'] || $data['libelle'] === '' || $data['salaire_base'] <= 0 || $data['salaire_max'] < $data['salaire_base']) {
            return $this->erreur('Données de poste invalides (max ≥ base).', 'admin');
        }

        $id = (new PosteModel())->insert($data);
        (new AuditService())->log((int) $this->utilisateur()['id'], 'admin.poste.creer', 'poste', (int) $id, $data);

        return $this->ok('Poste créé.', 'admin');
    }

    /** Écran dédié : budget / masse salariale / engagé / disponible par département. */
    public function budgets()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $annee = (int) ($this->request->getGet('annee') ?: date('Y'));

        return $this->vue('admin/budgets', [
            'titre'         => 'Budgets par département',
            'active'        => 'admin',
            'annee'         => $annee,
            'departements'  => (new DepartementModel())->where('actif', 1)->orderBy('libelle')->findAll(),
            'lignes'        => (new BudgetService())->tableauDepartements($annee),
        ]);
    }

    public function storeBudget()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $departementId = (int) $this->request->getPost('departement_id');
        $annee = (int) $this->request->getPost('annee');
        $montant = (float) $this->request->getPost('montant_annuel');

        if (! $departementId || $annee < 2000 || $montant < 0) {
            return $this->erreur('Budget invalide.', 'admin/budgets?annee=' . $annee);
        }

        $model = new BudgetDepartementModel();
        $existant = $model->where('departement_id', $departementId)->where('annee', $annee)->first();

        if ($existant) {
            $model->update((int) $existant['id'], ['montant_annuel' => $montant]);
            $budgetId = (int) $existant['id'];
        } else {
            $budgetId = (int) $model->insert([
                'departement_id' => $departementId,
                'annee'          => $annee,
                'montant_annuel' => $montant,
            ]);
        }

        (new AuditService())->log((int) $this->utilisateur()['id'], 'admin.budget.enregistrer', 'budget_departement', $budgetId, [
            'departement_id' => $departementId, 'annee' => $annee, 'montant' => $montant,
        ]);

        return $this->ok('Budget enregistré.', 'admin/budgets?annee=' . $annee);
    }

    public function storeTypeConge()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $libelle = trim((string) $this->request->getPost('libelle'));
        if ($libelle === '') {
            return $this->erreur('Libellé obligatoire.', 'admin');
        }

        (new TypeCongeModel())->insert([
            'libelle'             => $libelle,
            'jours_max_par_an'    => $this->request->getPost('jours_max_par_an') !== '' ? (float) $this->request->getPost('jours_max_par_an') : null,
            'justificatif_requis' => (int) $this->request->getPost('justificatif_requis') === 1 ? 1 : 0,
            'couleur'             => $this->request->getPost('couleur') ?: '#3498db',
        ]);

        return $this->ok('Type de congé créé.', 'admin');
    }

    public function storeJourFerie()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $jour = $this->request->getPost('jour');
        $libelle = trim((string) $this->request->getPost('libelle'));
        if (! $jour || $libelle === '') {
            return $this->erreur('Date et libellé obligatoires.', 'admin');
        }

        $model = new JourFerieModel();
        if ($model->where('jour', $jour)->first()) {
            return $this->erreur('Ce jour férié existe déjà.', 'admin');
        }
        $model->insert(['jour' => $jour, 'libelle' => $libelle]);

        return $this->ok('Jour férié ajouté.', 'admin?annee=' . date('Y', strtotime($jour)));
    }

    public function typesConge()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $types = (new TypeCongeModel())->orderBy('id')->findAll();
        foreach ($types as &$type) {
            $type['droits'] = (new DroitCongeModel())->where('type_conge_id', (int) $type['id'])->orderBy('anciennete_min_mois')->findAll();
        }
        unset($type);

        return $this->vue('admin/types_conge', [
            'titre' => 'Types et droits de congé',
            'active' => 'admin',
            'types' => $types,
        ]);
    }

    public function storeDroitConge()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $typeCongeId = (int) $this->request->getPost('type_conge_id');
        $anciennete = (int) $this->request->getPost('anciennete_min_mois');
        $joursParMois = (float) $this->request->getPost('jours_par_mois');
        $joursMax = $this->request->getPost('jours_max_annuel');

        (new DroitCongeModel())->insert([
            'type_conge_id' => $typeCongeId,
            'anciennete_min_mois' => $anciennete,
            'jours_par_mois' => $joursParMois,
            'jours_max_annuel' => $joursMax !== '' ? (float) $joursMax : null,
            'actif' => 1,
        ]);

        (new AuditService())->log((int) $this->utilisateur()['id'], 'admin.droit_conge.creer', 'droit_conge', $typeCongeId);

        return $this->ok('Droit de congé enregistré.', 'admin/types-conge');
    }

    public function audit()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $builder = $db->table('audit_log a')
            ->select('a.*, u.nom, u.prenom, u.email')
            ->join('utilisateur u', 'u.id = a.utilisateur_id', 'left')
            ->orderBy('a.created_at', 'DESC')
            ->limit(1000);

        $utilisateurId = (int) $this->request->getGet('utilisateur_id');
        if ($utilisateurId > 0) {
            $builder->where('a.utilisateur_id', $utilisateurId);
        }
        $action = trim((string) $this->request->getGet('action'));
        if ($action !== '') {
            $builder->like('a.action', $action);
        }
        $entite = trim((string) $this->request->getGet('entite'));
        if ($entite !== '') {
            $builder->where('a.entite', $entite);
        }
        $dateDebut = trim((string) $this->request->getGet('date_debut'));
        if ($dateDebut !== '') {
            $builder->where('a.created_at >=', $dateDebut . ' 00:00:00');
        }
        $dateFin = trim((string) $this->request->getGet('date_fin'));
        if ($dateFin !== '') {
            $builder->where('a.created_at <=', $dateFin . ' 23:59:59');
        }

        $utilisateurs = $db->table('utilisateur')->select('id, nom, prenom, email')->orderBy('nom')->get()->getResultArray();

        (new AuditService())->log((int) $this->utilisateur()['id'], 'audit.consulter', 'audit_log', null, [
            'utilisateur_id' => $utilisateurId, 'action' => $action, 'entite' => $entite,
        ]);

        return $this->vue('admin/audit', [
            'titre' => 'Journal d’audit',
            'active' => 'audit',
            'logs' => $builder->get()->getResultArray(),
            'utilisateurs' => $utilisateurs,
            'utilisateur_id' => $utilisateurId,
            'action' => $action,
            'entite' => $entite,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
        ]);
    }
}
