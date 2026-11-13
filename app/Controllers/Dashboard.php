<?php

namespace App\Controllers;

use App\Models\AnnonceModel;
use App\Models\BesoinModel;
use App\Models\CandidatureModel;
use App\Models\ContratModel;
use App\Models\DemandeCongeModel;
use App\Models\SalarieModel;
use App\Services\BudgetService;
use App\Services\CongeService;
use App\Services\NotificationService;

class Dashboard extends BaseController
{
    public function index()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $user = $this->utilisateur();
        $role = $this->role();
        $db   = db_connect();
        $data = ['titre' => 'Tableau de bord', 'active' => 'dashboard'];

        $data['nb_annonces'] = (new AnnonceModel())->where('est_cloture', 0)->countAllResults();

        if ($role === 'candidat') {
            $data['mes_candidatures'] = (new CandidatureModel())->where('utilisateur_id', (int) $user['id'])->countAllResults();
            $data['mes_propositions'] = $db->table('proposition_embauche p')
                ->join('candidature c', 'c.id = p.candidature_id')
                ->where('c.utilisateur_id', (int) $user['id'])
                ->countAllResults();
            $data['dernieres_candidatures'] = $db->table('candidature c')
                ->select('c.*, a.titre, sc.libelle AS statut')
                ->join('annonce a', 'a.id = c.annonce_id')
                ->join('statut_candidature sc', 'sc.id = c.statut_id')
                ->where('c.utilisateur_id', (int) $user['id'])
                ->orderBy('c.date_candidature', 'DESC')
                ->limit(5)
                ->get()->getResultArray();
        }

        if (in_array($role, ['employe', 'manager', 'rh', 'dg'], true)) {
            $salarie = $this->salarieCourant();
            if ($salarie) {
                $annee = (int) date('Y');
                $typePaye = $db->table('type_conge')->where('libelle', 'Congé payé')->get()->getRowArray();
                $data['solde_conge'] = $typePaye
                    ? (new CongeService())->assurerSolde((int) $salarie['id'], (int) $typePaye['id'], $annee)
                    : ['jours_acquis' => 0, 'jours_pris' => 0, 'disponible' => 0];
                $data['salarie'] = $salarie;
                $data['mon_pointage'] = $db->table('pointage')
                    ->where('salarie_id', $salarie['id'])
                    ->where('jour', date('Y-m-d'))
                    ->get()->getRowArray();
            }
            $data['mes_conges'] = $salarie
                ? (new DemandeCongeModel())->where('salarie_id', $salarie['id'])->countAllResults()
                : 0;
        }

        if ($role === 'manager') {
            $salarie = $this->salarieCourant();
            $data['besoins_manager'] = (new BesoinModel())->where('demandeur_id', (int) $user['id'])->countAllResults();
            $data['conges_manager'] = $salarie
                ? $db->table('demande_conge dc')
                    ->join('salarie s', 's.id = dc.salarie_id')
                    ->join('statut_conge sc', 'sc.id = dc.statut_id')
                    ->where('s.manager_id', (int) $salarie['id'])
                    ->where('sc.libelle', 'En attente')
                    ->countAllResults()
                : 0;
        }

        if (in_array($role, ['rh', 'dg'], true)) {
            $idAttenteRh = (int) ($db->table('statut_besoin')->where('libelle', 'En attente RH')->get()->getRowArray()['id'] ?? 0);
            $idAttenteDg = (int) ($db->table('statut_besoin')->where('libelle', 'En attente DG')->get()->getRowArray()['id'] ?? 0);
            $idCongeAttente = (int) ($db->table('statut_conge')->where('libelle', 'En attente')->get()->getRowArray()['id'] ?? 0);
            $idCongeManager = (int) ($db->table('statut_conge')->where('libelle', 'Validé manager')->get()->getRowArray()['id'] ?? 0);

            $data['besoins_rh'] = (new BesoinModel())->where('statut_id', $idAttenteRh)->countAllResults();
            $data['besoins_dg'] = (new BesoinModel())->where('statut_id', $idAttenteDg)->countAllResults();
            $data['candidatures_nouvelles'] = $db->table('candidature c')
                ->join('statut_candidature sc', 'sc.id = c.statut_id')
                ->where('sc.libelle', 'Reçue')->countAllResults();
            $data['salaries_actifs'] = (new SalarieModel())->where('actif', 1)->countAllResults();
            $data['contrats_actifs'] = (new ContratModel())->where('statut', 'Accepte')->countAllResults();
            $data['conges_rh'] = (new DemandeCongeModel())->where('statut_id', $idCongeManager)->countAllResults();
            $data['conges_manager'] = (new DemandeCongeModel())->where('statut_id', $idCongeAttente)->countAllResults();
            $data['budgets'] = $db->table('departement d')
                ->select('d.id, d.libelle, b.montant_annuel')
                ->join('budget_departement b', 'b.departement_id = d.id AND b.annee = ' . (int) date('Y'), 'left')
                ->orderBy('d.libelle')
                ->get()->getResultArray();
            $budgetService = new BudgetService();
            foreach ($data['budgets'] as &$budget) {
                $budget['resume'] = $budgetService->resume((int) $budget['id'], (int) date('Y'));
            }
            unset($budget);
        }

        $data['notifications_recentes'] = (new NotificationService())->dernieres((int) $user['id'], 8);

        return $this->vue('dashboard/index', $data);
    }

    public function notifications()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }
        $model = new \App\Models\NotificationModel();
        $user = $this->utilisateur();
        $model->where('utilisateur_id', (int) $user['id'])->set(['lu' => 1])->update();
        $data = [
            'titre' => 'Notifications',
            'active' => 'notifications',
            'liste' => $model->where('utilisateur_id', (int) $user['id'])->orderBy('created_at', 'DESC')->findAll(100),
        ];
        return $this->vue('dashboard/notifications', $data);
    }
}
