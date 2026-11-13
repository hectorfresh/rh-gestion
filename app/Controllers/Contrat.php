<?php

namespace App\Controllers;

use App\Models\ContratModel;
use App\Models\SalarieModel;
use App\Services\AuditService;
use App\Services\ContratService;
use App\Services\OnboardingService;

/**
 * Bloc 4 : contrats successifs, renouvellement et onboarding.
 */
class Contrat extends BaseController
{
    public function index()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg', 'manager'])) {
            return $redirection;
        }

        $db = db_connect();
        $builder = $db->table('contrat c')
            ->select('c.*, u.nom, u.prenom, u.email, p.libelle AS poste, d.libelle AS departement, s.matricule')
            ->join('salarie s', 's.id = c.salarie_id', 'left')
            ->join('utilisateur u', 'u.id = s.utilisateur_id', 'left')
            ->join('poste p', 'p.id = s.poste_id', 'left')
            ->join('departement d', 'd.id = s.departement_id', 'left')
            ->orderBy('c.date_creation', 'DESC');

        if ($this->role() === 'manager') {
            $monSalarie = $this->salarieCourant();
            if ($monSalarie) {
                $builder->where('s.manager_id', (int) $monSalarie['id']);
            }
        }

        $contrats = $builder->get()->getResultArray();

        return $this->vue('contrats/index', [
            'titre' => 'Contrats',
            'active' => 'contrats',
            'contrats' => $contrats,
        ]);
    }

    public function mes()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $db = db_connect();
        $contrats = $db->table('contrat c')
            ->select('c.*, p.libelle AS poste, d.libelle AS departement, s.id AS salarie_id, s.matricule')
            ->join('salarie s', 's.id = c.salarie_id')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->join('poste p', 'p.id = s.poste_id')
            ->join('departement d', 'd.id = s.departement_id')
            ->where('u.id', (int) $this->utilisateur()['id'])
            ->orderBy('c.date_debut', 'DESC')
            ->get()->getResultArray();

        return $this->vue('contrats/mes', [
            'titre' => 'Mes contrats',
            'active' => 'contrats',
            'contrats' => $contrats,
        ]);
    }

    public function show($id)
    {
        $contrat = $this->chargeContrat((int) $id);
        if (! $contrat) {
            return $this->erreur('Contrat introuvable.', 'dashboard');
        }
        if (! $this->estProprietaireOuStaff($contrat)) {
            return $this->erreur('Accès refusé.', 'dashboard');
        }

        return $this->vue('contrats/show', [
            'titre' => 'Contrat #' . $id,
            'active' => 'contrats',
            'contrat' => $contrat,
            'staff' => $this->estStaff() || $this->role() === 'manager',
            'peut_rompre' => $this->estStaff(),
            'est_proprietaire' => (int) $contrat['utilisateur_id'] === (int) $this->utilisateur()['id'],
        ]);
    }

    public function pdf($id)
    {
        $contrat = $this->chargeContrat((int) $id);
        if (! $contrat || ! $this->estProprietaireOuStaff($contrat)) {
            return $this->erreur('Contrat introuvable ou accès refusé.', 'dashboard');
        }

        $service = new ContratService();
        $pdf = $service->genererPdf((int) $id, true, (int) $this->utilisateur()['id']);

        $fichier = WRITEPATH . 'uploads/documents/contrat_' . (int) $id . '.pdf';
        if (is_file($fichier)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="contrat_' . (int) $id . '.pdf"');
            header('Content-Length: ' . filesize($fichier));
            readfile($fichier);
            exit;
        }

        if (! $pdf) {
            helper('talentflow');
            return $this->vue('contrats/pdf', ['contrat' => $this->chargeContrat((int) $id)]);
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="contrat_' . (int) $id . '.pdf"');
        header('Content-Length: ' . filesize($fichier));
        readfile($fichier);
        exit;
    }

    public function rompre($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }
        $contrat = (new ContratModel())->find((int) $id);
        if (! $contrat) {
            return $this->erreur('Contrat introuvable.', 'contrats');
        }

        (new ContratModel())->update((int) $id, ['statut' => 'Rompu', 'date_fin' => date('Y-m-d')]);
        (new AuditService())->log((int) $this->utilisateur()['id'], 'contrat.rompre', 'contrat', (int) $id);

        return $this->ok('Contrat marqué comme rompu.', 'contrats');
    }

    public function salarie($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg', 'manager'])) {
            return $redirection;
        }

        $salarie = $this->chargeSalarie((int) $id);
        if (! $salarie) {
            return $this->erreur('Salarié introuvable.', 'salaries');
        }

        $service = new ContratService();
        $contrats = $service->frise((int) $id);
        $progression = (new OnboardingService())->progression((int) $id);

        return $this->vue('contrats/salarie', [
            'titre' => 'Contrats — ' . $salarie['prenom'] . ' ' . $salarie['nom'],
            'active' => 'contrats',
            'salarie' => $salarie,
            'contrats' => $contrats,
            'progression' => $progression,
        ]);
    }

    public function renouveler($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $ancien = (new ContratModel())->find((int) $id);
        if (! $ancien) {
            return $this->erreur('Contrat introuvable.', 'contrats');
        }

        $salarie = (new SalarieModel())->find((int) $ancien['salarie_id']);
        if (! $salarie) {
            return $this->erreur('Salarié introuvable.', 'contrats');
        }

        $db = db_connect();
        $poste = $db->table('poste')->where('id', (int) $salarie['poste_id'])->get()->getRowArray();
        $regimes = $db->table('regime_travail')->orderBy('libelle')->get()->getResultArray();
        $postes = $db->table('poste')->where('actif', 1)->orderBy('libelle')->get()->getResultArray();

        if ($this->request->getMethod() === 'post') {
            $service = new ContratService();
            $resultat = $service->renouveler((int) $id, [
                'type_contrat'      => $this->request->getPost('type_contrat'),
                'date_debut'        => $this->request->getPost('date_debut'),
                'date_fin'          => $this->request->getPost('date_fin'),
                'salaire'           => $this->request->getPost('salaire'),
                'poste_id'          => $this->request->getPost('poste_id'),
                'departement_id'    => $this->request->getPost('departement_id'),
                'regime_travail_id' => $this->request->getPost('regime_travail_id'),
                'heure_debut'       => $this->request->getPost('heure_debut'),
                'heure_fin'         => $this->request->getPost('heure_fin'),
                'periode_essai'     => $this->request->getPost('periode_essai'),
            ], $this->utilisateur());

            if (! $resultat['ok']) {
                return $this->erreur($resultat['message'], 'contrats/' . $id . '/renouveler');
            }

            return $this->ok($resultat['message'], 'contrats/' . $resultat['id']);
        }

        return $this->vue('contrats/renouveler', [
            'titre' => 'Renouveler le contrat #' . $id,
            'active' => 'contrats',
            'ancien' => $ancien,
            'salarie' => $salarie,
            'poste' => $poste,
            'regimes' => $regimes,
            'postes' => $postes,
            'departements' => $db->table('departement')->where('actif', 1)->orderBy('libelle')->get()->getResultArray(),
        ]);
    }

    public function echeance()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $service = new ContratService();
        $contrats = $service->echeances(30);

        return $this->vue('contrats/echeance', [
            'titre' => 'Contrats à échéance sous 30 jours',
            'active' => 'contrats',
            'contrats' => $contrats,
        ]);
    }

    public function onboarding($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $salarie = $this->chargeSalarie((int) $id);
        if (! $salarie) {
            return $this->erreur('Salarié introuvable.', 'salaries');
        }

        if (! $this->estStaff() && (int) $salarie['utilisateur_id'] !== (int) $this->utilisateur()['id']) {
            return $this->erreur('Accès refusé.', 'dashboard');
        }

        (new OnboardingService())->notifierRetards();

        $progression = (new OnboardingService())->progression((int) $id);

        return $this->vue('contrats/onboarding', [
            'titre' => 'Onboarding — ' . $salarie['prenom'] . ' ' . $salarie['nom'],
            'active' => 'contrats',
            'salarie' => $salarie,
            'progression' => $progression,
            'types' => (new \App\Models\TypeDocumentModel())->where('actif', 1)->orderBy('libelle')->findAll(),
        ]);
    }

    public function onboardingTache($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $tache = (new \App\Models\OnboardingTacheModel())->find((int) $id);
        if (! $tache) {
            return $this->erreur('Tâche introuvable.', 'dashboard');
        }

        $onboarding = (new \App\Models\OnboardingModel())->find((int) $tache['onboarding_id']);
        if (! $onboarding) {
            return $this->erreur('Onboarding introuvable.', 'dashboard');
        }

        $salarie = (new SalarieModel())->find((int) $onboarding['salarie_id']);
        if (! $salarie) {
            return $this->erreur('Salarié introuvable.', 'dashboard');
        }

        if (! $this->estStaff() && (int) $salarie['utilisateur_id'] !== (int) $this->utilisateur()['id']) {
            return $this->erreur('Accès refusé.', 'dashboard');
        }

        $statut = (string) $this->request->getPost('statut');
        $resultat = (new OnboardingService())->mettreAJourTache((int) $id, $statut, (int) $this->utilisateur()['id']);
        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'contrats/onboarding/' . $salarie['id']);
        }

        return $this->ok($resultat['message'], 'contrats/onboarding/' . $salarie['id']);
    }

    private function chargeContrat(int $id): ?array
    {
        $service = new ContratService();

        return $service->charger($id);
    }

    private function chargeSalarie(int $id): ?array
    {
        $db = db_connect();
        return $db->table('salarie s')
            ->select('s.*, u.nom, u.prenom, u.email, u.telephone, d.libelle AS departement,
                      p.libelle AS poste, p.salaire_base, p.salaire_max, r.libelle AS regime')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->join('departement d', 'd.id = s.departement_id')
            ->join('poste p', 'p.id = s.poste_id')
            ->join('regime_travail r', 'r.id = s.regime_travail_id')
            ->where('s.id', $id)
            ->get()->getRowArray();
    }

    private function estProprietaireOuStaff(array $contrat): bool
    {
        if ($this->estStaff()) {
            return true;
        }
        if ((int) $contrat['utilisateur_id'] === (int) $this->utilisateur()['id']) {
            return true;
        }
        if ($this->role() === 'manager') {
            $monSalarie = $this->salarieCourant();
            return $monSalarie && (int) $contrat['manager_id'] === (int) $monSalarie['id'];
        }

        return false;
    }
}
