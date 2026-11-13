<?php

namespace App\Services;

use App\Models\OnboardingModel;
use App\Models\OnboardingTacheModel;
use App\Models\SalarieModel;

class OnboardingService
{
    private NotificationService $notifications;

    public function __construct()
    {
        $this->notifications = new NotificationService();
    }

    public function initialiser(int $salarieId, int $contratId, ?int $responsableId = null): void
    {
        $onboardingModel = new OnboardingModel();
        $onboarding = $onboardingModel->where('salarie_id', $salarieId)->first();

        if (! $onboarding) {
            $onboardingId = (int) $onboardingModel->insert([
                'salarie_id' => $salarieId,
                'contrat_id' => $contratId,
                'statut'     => 'a_faire',
                'date_debut' => date('Y-m-d'),
            ]);
        } else {
            $onboardingId = (int) $onboarding['id'];
            $onboardingModel->update($onboardingId, [
                'contrat_id' => $contratId,
                'statut'     => $onboarding['statut'] === 'termine' ? 'termine' : 'a_faire',
            ]);
        }

        if ((new OnboardingTacheModel())->where('onboarding_id', $onboardingId)->countAllResults() === 0) {
            $echeances = [3, 3, 5, 7, 10, 15];
            $taches = [
                ['Signature du contrat', 'Faire signer le contrat de travail par le salarié.'],
                ['Création du compte et accès', 'Créer les accès applicatifs et messagerie.'],
                ['Remise du badge', 'Remettre le badge d’accès aux locaux.'],
                ['Remise du matériel', 'Remettre le poste de travail et les équipements.'],
                ['Formation d’intégration', 'Organiser la formation d’accueil et la présentation.'],
                ['Inscription à la paie', 'Intégrer le salarié dans la paie et les déclarations.'],
            ];

            foreach ($taches as $index => [$libelle, $description]) {
                (new OnboardingTacheModel())->insert([
                    'onboarding_id'  => $onboardingId,
                    'libelle'        => $libelle,
                    'description'    => $description,
                    'responsable_id' => $responsableId,
                    'date_echeance'  => date('Y-m-d', strtotime('+' . $echeances[$index] . ' days')),
                    'statut'         => 'a_faire',
                ]);
            }
        }
    }

    public function progression(int $salarieId): array
    {
        $onboarding = (new OnboardingModel())->where('salarie_id', $salarieId)->first();
        if (! $onboarding) {
            return ['onboarding' => null, 'taches' => [], 'total' => 0, 'terminees' => 0, 'pourcentage' => 0, 'en_retard' => []];
        }

        $taches = (new OnboardingTacheModel())
            ->where('onboarding_id', (int) $onboarding['id'])
            ->orderBy('date_echeance', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $terminees = 0;
        $retard = [];
        foreach ($taches as $tache) {
            if ($tache['statut'] === 'terminee') {
                $terminees++;
            } elseif (! empty($tache['date_echeance']) && $tache['date_echeance'] < date('Y-m-d')) {
                $retard[] = $tache;
            }
        }

        $total = count($taches);
        $pourcentage = $total > 0 ? (int) round($terminees / $total * 100) : 0;

        if ($pourcentage === 100 && $total > 0 && $onboarding['statut'] !== 'termine') {
            (new OnboardingModel())->update((int) $onboarding['id'], [
                'statut'    => 'termine',
                'date_fin'  => date('Y-m-d'),
            ]);
            $onboarding['statut'] = 'termine';
        } elseif ($pourcentage > 0 && $pourcentage < 100 && $onboarding['statut'] === 'a_faire') {
            (new OnboardingModel())->update((int) $onboarding['id'], ['statut' => 'en_cours']);
            $onboarding['statut'] = 'en_cours';
        }

        return [
            'onboarding'   => $onboarding,
            'taches'       => $taches,
            'total'        => $total,
            'terminees'    => $terminees,
            'pourcentage'  => $pourcentage,
            'en_retard'    => $retard,
        ];
    }

    public function mettreAJourTache(int $tacheId, string $statut, int $utilisateurId): array
    {
        if (! in_array($statut, ['a_faire', 'en_cours', 'terminee'], true)) {
            return ['ok' => false, 'message' => 'Statut de tâche invalide.'];
        }

        $tache = (new OnboardingTacheModel())->find($tacheId);
        if (! $tache) {
            return ['ok' => false, 'message' => 'Tâche introuvable.'];
        }

        (new OnboardingTacheModel())->update($tacheId, [
            'statut' => $statut,
        ]);

        $onboarding = (new OnboardingModel())->find((int) $tache['onboarding_id']);
        if ($onboarding) {
            $this->progression((int) $onboarding['salarie_id']);
        }

        $this->audit($utilisateurId, $tacheId, $statut);

        return ['ok' => true, 'message' => 'Tâche mise à jour.'];
    }

    public function notifierRetards(): void
    {
        $db = db_connect();
        $retards = $db->table('onboarding_tache ot')
            ->select('ot.*, o.salarie_id, s.nom, s.prenom')
            ->join('onboarding o', 'o.id = ot.onboarding_id')
            ->join('salarie s', 's.id = o.salarie_id')
            ->where('ot.statut !=', 'terminee')
            ->where('ot.date_echeance <', date('Y-m-d'))
            ->get()->getResultArray();

        if (empty($retards)) {
            return;
        }

        $this->notifications->envoyerAuxRoles(['rh'],
            count($retards) . ' tâche(s) d’onboarding sont en retard.',
            'onboarding');
    }

    private function audit(int $utilisateurId, int $tacheId, string $statut): void
    {
        (new \App\Services\AuditService())->log($utilisateurId, 'onboarding.tache.' . $statut, 'onboarding_tache', $tacheId);
    }
}
