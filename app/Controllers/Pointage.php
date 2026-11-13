<?php

namespace App\Controllers;

use App\Models\AbsenceModel;
use App\Models\PlanningSalarieModel;
use App\Models\SalarieModel;
use App\Services\PointageService;

/**
 * Bloc 6 : temps de travail et pointage.
 */
class Pointage extends BaseController
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
        $pointages = (new \App\Models\PointageModel())
            ->where('salarie_id', (int) $salarie['id'])
            ->orderBy('jour', 'DESC')
            ->findAll(30);

        $data = [
            'titre' => 'Mon pointage',
            'active' => 'pointage',
            'salarie' => $salarie,
            'pointages' => $pointages,
            'aujourdhui' => (new \App\Models\PointageModel())
                ->where('salarie_id', (int) $salarie['id'])
                ->where('jour', date('Y-m-d'))
                ->first(),
            'planning' => (new PlanningSalarieModel())
                ->where('salarie_id', (int) $salarie['id'])
                ->where('jour >=', date('Y-m-d', strtotime('monday this week')))
                ->where('jour <=', date('Y-m-d', strtotime('sunday this week')))
                ->orderBy('jour', 'ASC')
                ->findAll(),
        ];

        return $this->vue('pointages/mes', $data);
    }

    public function entree()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }
        $salarie = $this->salarieCourant();
        if (! $salarie) {
            return $this->erreur('Aucune fiche salarié.', 'dashboard');
        }

        $resultat = (new PointageService())->pointerEntree((int) $salarie['id'], null, (int) $this->utilisateur()['id']);
        return $resultat['ok'] ? $this->ok($resultat['message'], 'pointages/mes') : $this->erreur($resultat['message'], 'pointages/mes');
    }

    public function sortie()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }
        $salarie = $this->salarieCourant();
        if (! $salarie) {
            return $this->erreur('Aucune fiche salarié.', 'dashboard');
        }

        $resultat = (new PointageService())->pointerSortie((int) $salarie['id'], null, (int) $this->utilisateur()['id']);
        return $resultat['ok'] ? $this->ok($resultat['message'], 'pointages/mes') : $this->erreur($resultat['message'], 'pointages/mes');
    }

    public function index()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg', 'manager'])) {
            return $redirection;
        }

        $jour = $this->request->getGet('jour') ?: date('Y-m-d');
        $db = db_connect();

        $builder = $db->table('salarie s')
            ->select('s.id, s.matricule, u.nom, u.prenom, p.libelle AS poste, d.libelle AS departement,
                      pt.heure_entree, pt.heure_sortie, pt.minutes_travaillees, pt.minutes_sup, pt.statut, pt.id AS pointage_id,
                      (SELECT COUNT(*) FROM anomalie_pointage ap WHERE ap.pointage_id = pt.id AND ap.resolue = 0) AS nb_anomalies')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->join('poste p', 'p.id = s.poste_id')
            ->join('departement d', 'd.id = s.departement_id')
            ->join('pointage pt', 'pt.salarie_id = s.id AND pt.jour = ' . $db->escape($jour), 'left')
            ->where('s.actif', 1);

        if ($this->role() === 'manager') {
            $monSalarie = $this->salarieCourant();
            if ($monSalarie) {
                $builder->where('s.manager_id', (int) $monSalarie['id']);
            }
        }

        $pointages = $builder->orderBy('u.nom')->get()->getResultArray();

        return $this->vue('pointages/index', [
            'titre' => 'Pointages',
            'active' => 'pointage',
            'pointages' => $pointages,
            'jour' => $jour,
        ]);
    }

    public function anomalies()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg', 'manager'])) {
            return $redirection;
        }

        $db = db_connect();
        $builder = $db->table('anomalie_pointage ap')
            ->select('ap.*, ap.date AS jour, u.nom, u.prenom, s.matricule')
            ->join('salarie s', 's.id = ap.salarie_id')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->orderBy('ap.resolue', 'ASC')
            ->orderBy('ap.created_at', 'DESC');

        if ($this->role() === 'manager') {
            $monSalarie = $this->salarieCourant();
            if ($monSalarie) {
                $builder->where('s.manager_id', (int) $monSalarie['id']);
            }
        }

        $anomalies = $builder->get()->getResultArray();

        return $this->vue('pointages/anomalies', [
            'titre' => 'Anomalies de pointage',
            'active' => 'pointage',
            'anomalies' => $anomalies,
        ]);
    }

    public function resoudreAnomalie($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }
        (new \App\Models\AnomaliePointageModel())->update((int) $id, ['resolue' => 1]);
        return $this->ok('Anomalie marquée comme résolue.', 'pointages/anomalies');
    }

    public function marquerAbsents()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }
        $jour = $this->request->getPost('jour') ?: date('Y-m-d');
        $nombre = (new PointageService())->marquerAbsents($jour);
        return $this->ok($nombre . ' absence(s) détectée(s) le ' . tf_date($jour) . '.', 'pointages?jour=' . $jour);
    }

    public function feuille()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $salarie = $this->salarieCourant();
        if (! $salarie) {
            return $this->erreur('Aucune fiche salarié associée à votre compte.', 'dashboard');
        }

        $mois = $this->request->getGet('mois') ?: date('Y-m');
        $feuille = (new PointageService())->feuilleMois((int) $salarie['id'], $mois);

        return $this->vue('pointages/feuille', [
            'titre' => 'Feuille de temps mensuelle',
            'active' => 'pointage',
            'salarie' => $salarie,
            'mois' => $mois,
            'feuille' => $feuille,
        ]);
    }

    public function equipe()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg', 'manager'])) {
            return $redirection;
        }

        $mois = $this->request->getGet('mois') ?: date('Y-m');
        $salarie = $this->salarieCourant();
        $managerId = $this->role() === 'manager' && $salarie ? (int) $salarie['id'] : 0;

        $recap = [];
        if ($managerId > 0) {
            $recap = (new PointageService())->recapEquipe($managerId, $mois);
        } elseif ($this->estStaff()) {
            $db = db_connect();
            $salaries = $db->table('salarie s')
                ->select('s.id, s.matricule, u.nom, u.prenom')
                ->join('utilisateur u', 'u.id = s.utilisateur_id')
                ->where('s.actif', 1)
                ->orderBy('u.nom')
                ->get()->getResultArray();
            foreach ($salaries as $item) {
                $feuille = (new PointageService())->feuilleMois((int) $item['id'], $mois);
                $recap[] = ['salarie' => $item, 'totaux' => $feuille['totaux']];
            }
        }

        return $this->vue('pointages/equipe', [
            'titre' => 'Récapitulatif d’équipe',
            'active' => 'pointage',
            'mois' => $mois,
            'recap' => $recap,
        ]);
    }

    public function calculer()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $mois = $this->request->getPost('mois') ?: date('Y-m');
        $salarieId = (int) $this->request->getPost('salarie_id');
        if ($salarieId <= 0) {
            $db = db_connect();
            $ids = $db->table('salarie')->select('id')->where('actif', 1)->get()->getResultArray();
            foreach ($ids as $row) {
                (new PointageService())->consoliderMois((int) $row['id'], $mois, (int) $this->utilisateur()['id']);
            }
        } else {
            (new PointageService())->consoliderMois($salarieId, $mois, (int) $this->utilisateur()['id']);
        }

        return $this->ok('Mois consolidé dans la paie variable.', 'pointages/equipe?mois=' . $mois);
    }

    public function planning()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $db = db_connect();
        $salarie = $this->salarieCourant();

        $builder = $db->table('planning_salarie ps')
            ->select('ps.*, u.nom, u.prenom, s.matricule')
            ->join('salarie s', 's.id = ps.salarie_id')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->orderBy('ps.jour', 'DESC')
            ->limit(100);

        if ($this->role() === 'manager' && $salarie) {
            $builder->groupStart()->where('s.manager_id', (int) $salarie['id'])->orWhere('ps.salarie_id', (int) $salarie['id'])->groupEnd();
        } elseif (! $this->estStaff() && $salarie) {
            $builder->where('ps.salarie_id', (int) $salarie['id']);
        }

        if ($this->role() === 'manager' && $salarie) {
            $salaries = $db->table('salarie s')->select('s.id, s.matricule, u.nom, u.prenom')
                ->join('utilisateur u', 'u.id = s.utilisateur_id')
                ->where('s.actif', 1)
                ->groupStart()->where('s.manager_id', (int) $salarie['id'])->orWhere('s.id', (int) $salarie['id'])->groupEnd()
                ->orderBy('u.nom')->get()->getResultArray();
        } else {
            $salaries = $this->estStaff()
                ? $db->table('salarie s')->select('s.id, s.matricule, u.nom, u.prenom')->join('utilisateur u', 'u.id = s.utilisateur_id')->where('s.actif', 1)->orderBy('u.nom')->get()->getResultArray()
                : [];
        }

        return $this->vue('pointages/planning', [
            'titre' => 'Planning de travail',
            'active' => 'temps',
            'planning' => $builder->get()->getResultArray(),
            'salaries' => $salaries,
            'staff' => $this->estStaff() || $this->role() === 'manager',
        ]);
    }

    public function storePlanning()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg', 'manager'])) {
            return $redirection;
        }

        $salarieId = (int) $this->request->getPost('salarie_id');
        $jour = $this->request->getPost('jour');
        $type = $this->request->getPost('type') ?: 'travail';

        if (! $salarieId || ! $jour) {
            return $this->erreur('Salarié et date obligatoires.', 'planning');
        }
        if ($this->role() === 'manager' && ! $this->estDansMonEquipe($salarieId)) {
            return $this->erreur('Vous ne pouvez planifier que votre équipe.', 'planning');
        }

        $model = new PlanningSalarieModel();
        $existant = $model->where('salarie_id', $salarieId)->where('jour', $jour)->first();

        $data = [
            'salarie_id'  => $salarieId,
            'jour'        => $jour,
            'heure_debut' => $type === 'travail' ? ($this->request->getPost('heure_debut') ?: '08:00') : null,
            'heure_fin'   => $type === 'travail' ? ($this->request->getPost('heure_fin') ?: '17:00') : null,
            'type'        => $type,
            'commentaire' => $this->request->getPost('commentaire'),
        ];

        if ($existant) {
            $model->update((int) $existant['id'], $data);
        } else {
            $model->insert($data);
        }

        return $this->ok('Planning enregistré.', 'planning');
    }

    public function absences()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg', 'manager'])) {
            return $redirection;
        }

        $db = db_connect();
        $builder = $db->table('absence a')
            ->select('a.*, u.nom, u.prenom, s.matricule')
            ->join('salarie s', 's.id = a.salarie_id')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->orderBy('a.date_debut', 'DESC');

        $monSalarie = $this->salarieCourant();
        if ($this->role() === 'manager' && $monSalarie) {
            $builder->where('s.manager_id', (int) $monSalarie['id']);
        }
        $absences = $builder->get()->getResultArray();

        $salariesBuilder = $db->table('salarie s')
            ->select('s.id, s.matricule, u.nom, u.prenom')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->where('s.actif', 1);
        if ($this->role() === 'manager' && $monSalarie) {
            $salariesBuilder->where('s.manager_id', (int) $monSalarie['id']);
        }
        $salaries = $salariesBuilder->orderBy('u.nom')->get()->getResultArray();

        return $this->vue('pointages/absences', [
            'titre' => 'Absences',
            'active' => 'temps',
            'absences' => $absences,
            'salaries' => $salaries,
        ]);
    }

    public function storeAbsence()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg', 'manager'])) {
            return $redirection;
        }

        $data = [
            'salarie_id'  => (int) $this->request->getPost('salarie_id'),
            'date_debut'  => $this->request->getPost('date_debut'),
            'date_fin'    => $this->request->getPost('date_fin') ?: $this->request->getPost('date_debut'),
            'type'        => $this->request->getPost('type') ?: 'autre',
            'motif'       => $this->request->getPost('motif'),
            'justificatif'=> $this->request->getPost('justificatif'),
            'justifie'    => (int) $this->request->getPost('justifie') === 1 || trim((string) $this->request->getPost('justificatif')) !== '' ? 1 : 0,
        ];

        if (! $data['salarie_id'] || ! $data['date_debut'] || $data['date_fin'] < $data['date_debut']) {
            return $this->erreur('Données d’absence invalides.', 'absences');
        }
        if ($this->role() === 'manager' && ! $this->estDansMonEquipe((int) $data['salarie_id'])) {
            return $this->erreur('Vous ne pouvez saisir que les absences de votre équipe.', 'absences');
        }

        (new AbsenceModel())->insert($data);
        return $this->ok('Absence enregistrée.', 'absences');
    }

    private function estDansMonEquipe(int $salarieId): bool
    {
        $moi = $this->salarieCourant();
        if (! $moi) {
            return false;
        }
        $salarie = (new SalarieModel())->find($salarieId);
        return $salarie && (int) $salarie['manager_id'] === (int) $moi['id'];
    }
}
