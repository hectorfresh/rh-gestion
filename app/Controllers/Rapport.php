<?php

namespace App\Controllers;

use App\Services\BudgetService;
use App\Services\PaieService;

/**
 * Reporting : tableaux de bord et exports CSV.
 */
class Rapport extends BaseController
{
    public function index()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg', 'manager'])) {
            return $redirection;
        }

        $db = db_connect();
        $annee = (int) date('Y');
        $manager = $this->role() === 'manager' ? $this->salarieCourant() : null;
        if ($this->role() === 'manager' && ! $manager) {
            return $this->erreur('Aucune fiche salarié associée à votre compte.', 'dashboard');
        }

        $salariesBuilder = $db->table('salarie s')
            ->select('d.libelle, COUNT(*) AS total')
            ->join('departement d', 'd.id = s.departement_id')
            ->where('s.actif', 1)
            ->groupBy('d.id')
            ->orderBy('total', 'DESC');
        if ($manager) {
            $salariesBuilder->where('s.manager_id', (int) $manager['id']);
        }
        $data['salaries_par_departement'] = $salariesBuilder->get()->getResultArray();

        $contratsBuilder = $db->table('contrat c')
            ->select('c.type_contrat, COUNT(*) AS total')
            ->where('c.statut', 'Accepte')
            ->groupBy('c.type_contrat');
        if ($manager) {
            $contratsBuilder->join('salarie s', 's.id = c.salarie_id')
                ->where('s.manager_id', (int) $manager['id']);
        }
        $data['contrats_par_type'] = $contratsBuilder->get()->getResultArray();

        $candidaturesBuilder = $db->table('candidature c')
            ->select('sc.libelle, COUNT(*) AS total')
            ->join('statut_candidature sc', 'sc.id = c.statut_id')
            ->groupBy('c.statut_id');
        if ($manager) {
            $candidaturesBuilder->join('annonce a', 'a.id = c.annonce_id')
                ->join('besoin b', 'b.id = a.besoin_id')
                ->where('b.demandeur_id', (int) $this->utilisateur()['id']);
        }
        $data['candidatures_par_statut'] = $candidaturesBuilder->get()->getResultArray();

        $congesBuilder = $db->table('demande_conge dc')
            ->select('tc.libelle, COUNT(*) AS total, SUM(dc.nombre_jours) AS jours')
            ->join('type_conge tc', 'tc.id = dc.type_conge_id')
            ->join('statut_conge sc', 'sc.id = dc.statut_id')
            ->where('sc.libelle', 'Accepté')
            ->groupBy('dc.type_conge_id');
        if ($manager) {
            $congesBuilder->join('salarie s', 's.id = dc.salarie_id')
                ->where('s.manager_id', (int) $manager['id']);
        }
        $data['conges_par_type'] = $congesBuilder->get()->getResultArray();

        $data['budgets'] = [];
        if (! $manager) {
            $data['budgets'] = $db->table('departement d')
                ->select('d.id, d.libelle')
                ->orderBy('d.libelle')->get()->getResultArray();
            $budgetService = new BudgetService();
            foreach ($data['budgets'] as &$budget) {
                $budget['resume'] = $budgetService->resume((int) $budget['id'], $annee);
                $budget['effectifs'] = (int) $db->table('salarie')
                    ->where('departement_id', (int) $budget['id'])
                    ->where('actif', 1)
                    ->countAllResults();
            }
            unset($budget);
        }

        $data['paie'] = $manager
            ? ['salaries' => 0, 'brut' => 0, 'net' => 0]
            : (new PaieService())->totalMois(date('Y-m'));
        $data['annee'] = $annee;
        $data['titre'] = 'Rapports';
        $data['active'] = 'rapports';

        return $this->vue('rapports/index', $data);
    }

    public function export($type)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg', 'manager'])) {
            return $redirection;
        }

        $db = db_connect();
        $entetes = [];
        $lignes = [];
        $manager = $this->role() === 'manager' ? $this->salarieCourant() : null;
        if ($this->role() === 'manager' && ! $manager) {
            return $this->erreur('Aucune fiche salarié associée à votre compte.', 'dashboard');
        }

        if ($type === 'salaries') {
            $entetes = ['Matricule', 'Nom', 'Prénom', 'Email', 'Département', 'Poste', 'Embauche', 'Actif'];
            $builder = $db->table('salarie s')
                ->select('s.matricule, u.nom, u.prenom, u.email, d.libelle AS departement, p.libelle AS poste, s.date_embauche, s.actif')
                ->join('utilisateur u', 'u.id = s.utilisateur_id')
                ->join('departement d', 'd.id = s.departement_id')
                ->join('poste p', 'p.id = s.poste_id')
                ->orderBy('u.nom');
            if ($manager) {
                $builder->where('s.manager_id', (int) $manager['id']);
            }
            $rows = $builder->get()->getResultArray();
            foreach ($rows as $r) {
                $lignes[] = array_values($r);
            }
        } elseif ($type === 'candidatures') {
            $entetes = ['ID', 'Candidat', 'Email', 'Annonce', 'Poste', 'Statut', 'Date'];
            $builder = $db->table('candidature c')
                ->select('c.id, CONCAT(u.prenom, " ", u.nom) AS candidat, u.email, a.titre AS annonce,
                          p.libelle AS poste, sc.libelle AS statut, c.date_candidature')
                ->join('utilisateur u', 'u.id = c.utilisateur_id')
                ->join('annonce a', 'a.id = c.annonce_id')
                ->join('besoin b', 'b.id = a.besoin_id')
                ->join('poste p', 'p.id = b.poste_id')
                ->join('statut_candidature sc', 'sc.id = c.statut_id')
                ->orderBy('c.date_candidature', 'DESC');
            if ($manager) {
                $builder->where('b.demandeur_id', (int) $this->utilisateur()['id']);
            }
            $rows = $builder->get()->getResultArray();
            foreach ($rows as $r) {
                $lignes[] = array_values($r);
            }
        } elseif ($type === 'conges') {
            $entetes = ['ID', 'Salarié', 'Matricule', 'Type', 'Début', 'Fin', 'Jours', 'Statut'];
            $builder = $db->table('demande_conge dc')
                ->select('dc.id, CONCAT(u.prenom, " ", u.nom) AS salarie, s.matricule, tc.libelle AS type,
                          dc.date_debut, dc.date_fin, dc.nombre_jours, sc.libelle AS statut')
                ->join('salarie s', 's.id = dc.salarie_id')
                ->join('utilisateur u', 'u.id = s.utilisateur_id')
                ->join('type_conge tc', 'tc.id = dc.type_conge_id')
                ->join('statut_conge sc', 'sc.id = dc.statut_id')
                ->orderBy('dc.date_demande', 'DESC');
            if ($manager) {
                $builder->where('s.manager_id', (int) $manager['id']);
            }
            $rows = $builder->get()->getResultArray();
            foreach ($rows as $r) {
                $lignes[] = array_values($r);
            }
        } elseif ($type === 'budget') {
            $entetes = ['Département', 'Effectifs', 'Budget annuel', 'Masse salariale', 'Engagé', 'Disponible'];
            $annee = (int) ($this->request->getGet('annee') ?: date('Y'));
            $budgetService = new BudgetService();
            $departements = $db->table('departement')->where('actif', 1)->orderBy('libelle')->get()->getResultArray();
            foreach ($departements as $departement) {
                $resume = $budgetService->resume((int) $departement['id'], $annee);
                $effectifs = (int) $db->table('salarie')->where('departement_id', (int) $departement['id'])->where('actif', 1)->countAllResults();
                $lignes[] = [
                    $departement['libelle'],
                    $effectifs,
                    $resume['budget'],
                    $resume['masse'],
                    $resume['engagements'],
                    $resume['disponible'],
                ];
            }
        } elseif ($type === 'pointages') {
            $entetes = ['Jour', 'Salarié', 'Matricule', 'Entrée', 'Sortie', 'Minutes', 'Heures sup.', 'Statut'];
            $builder = $db->table('pointage p')
                ->select('p.jour, CONCAT(u.prenom, " ", u.nom) AS salarie, s.matricule, p.heure_entree,
                          p.heure_sortie, p.minutes_travaillees, p.minutes_sup, p.statut')
                ->join('salarie s', 's.id = p.salarie_id')
                ->join('utilisateur u', 'u.id = s.utilisateur_id')
                ->orderBy('p.jour', 'DESC')->limit(1000);
            if ($manager) {
                $builder->where('s.manager_id', (int) $manager['id']);
            }
            $rows = $builder->get()->getResultArray();
            foreach ($rows as $r) {
                $lignes[] = array_values($r);
            }
        } else {
            return $this->erreur('Export inconnu.', 'rapports');
        }

        $sortie = fopen('php://temp', 'r+');
        fputcsv($sortie, $entetes, ';');
        foreach ($lignes as $ligne) {
            fputcsv($sortie, $ligne, ';');
        }
        rewind($sortie);
        $contenu = stream_get_contents($sortie);
        fclose($sortie);

        return $this->response->download('export_' . $type . '_' . date('Ymd') . '.csv', $contenu);
    }
}
