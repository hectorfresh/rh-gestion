<?php

namespace App\Services;

use App\Models\BesoinModel;
use App\Models\HistoriqueStatutBesoinModel;
use App\Models\PropositionElementModel;
use App\Models\PropositionModel;
use App\Models\StatutBesoinModel;
use App\Models\WorkflowValidationModel;

/**
 * Moteur de workflow générique.
 *
 * Besoin : RH → finance (automatique) → DG.
 * Contre-proposition : RH → finance (automatique) → DG.
 *
 * La décision du DG l'emporte toujours. L'ordre des étapes est imposé.
 * Personne ne décide sur sa propre demande.
 */
class WorkflowService
{
    private BudgetService $budget;
    private AuditService $audit;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->budget        = new BudgetService();
        $this->audit         = new AuditService();
        $this->notifications = new NotificationService();
    }

    // ---------------------------------------------------------
    // BESOINS
    // ---------------------------------------------------------

    public function initialiserBesoin(int $besoinId, int $createurId, string $roleCreateur): void
    {
        $this->creerDecision('besoin', $besoinId, 'rh', 'en_attente', null, 'En attente de la décision RH.');
        $this->creerDecision('besoin', $besoinId, 'dg', 'en_attente', null, 'En attente de la décision du DG.');

        // Règle Bloc 1 : l'étape FINANCE est calculée automatiquement
        // dès la soumission du besoin, avec instantané des chiffres.
        $finance = $this->budget->financerBesoin($besoinId);
        $this->audit->log($createurId, 'workflow.finance.' . ($finance['accepte'] ? 'accepte' : 'refuse'),
            'besoin', $besoinId, [
                'automatique' => true,
                'details'     => $finance['snapshot'],
            ]);

        if ($roleCreateur === 'dg') {
            // Besoin créé par le DG : sa décision vaut acceptation d'office.
            // RH et finance restent calculés et tracés pour information.
            $rh = $this->getDecision('besoin', $besoinId, 'rh');
            if ($rh) {
                (new WorkflowValidationModel())->update((int) $rh['id'], [
                    'decision'      => 'accepte',
                    'valideur_id'   => $createurId,
                    'automatique'   => 1,
                    'commentaire'   => 'Information : besoin créé par le DG, validation RH non requise.',
                    'date_decision' => date('Y-m-d H:i:s'),
                ]);
                $this->audit->log($createurId, 'workflow.rh.accepte.auto', 'besoin', $besoinId,
                    ['origine' => 'creation_dg']);
            }

            $this->enregistrerDecision('besoin', $besoinId, 'dg', 'accepte', $createurId,
                'Acceptation d’office : besoin créé par le DG.', true);

            $statut = $this->statutBesoin('Validé');
            (new BesoinModel())->update($besoinId, [
                'statut_id'    => $statut,
                'date_cloture' => date('Y-m-d H:i:s'),
            ]);
            $this->historiserBesoin($besoinId, $statut, $createurId, 'Besoin créé et validé d’office par le DG.');
            $this->audit->log($createurId, 'workflow.dg.accepte.auto', 'besoin', $besoinId,
                ['origine' => 'creation_dg']);

            return;
        }

        $statut = $this->statutBesoin('En attente RH');
        (new BesoinModel())->update($besoinId, ['statut_id' => $statut]);
        $this->historiserBesoin($besoinId, $statut, $createurId, 'Besoin soumis, en attente de validation RH.');
        $this->audit->log($createurId, 'besoin.soumettre', 'besoin', $besoinId, [
            'statut'  => 'En attente RH',
            'finance' => $finance['accepte'] ? 'accepte' : 'refuse',
        ]);
        $this->notifications->envoyerAuxRoles(['rh'],
            'Nouveau besoin #' . $besoinId . ' à valider.', 'besoins/' . $besoinId);
    }

    /**
     * Décision RH ou DG sur un besoin.
     * @return array{ok:bool,message:string}
     */
    public function deciderBesoin(int $besoinId, int $utilisateurId, string $role, string $decision, string $commentaire = ''): array
    {
        $besoin = (new BesoinModel())->find($besoinId);
        if (! $besoin) {
            return ['ok' => false, 'message' => 'Besoin introuvable.'];
        }

        if (! in_array($decision, ['accepte', 'refuse'], true)) {
            return ['ok' => false, 'message' => 'Décision invalide.'];
        }

        if ((int) $besoin['demandeur_id'] === $utilisateurId) {
            return ['ok' => false, 'message' => 'Vous ne pouvez pas décider sur votre propre demande.'];
        }

        if ($role === 'rh' && $decision === 'refuse' && trim($commentaire) === '') {
            return ['ok' => false, 'message' => 'Un commentaire est obligatoire lorsque le RH refuse un besoin.'];
        }

        if ($role === 'rh') {
            $rh = $this->getDecision('besoin', $besoinId, 'rh');
            if (! $rh || $rh['decision'] !== 'en_attente') {
                return ['ok' => false, 'message' => 'L’étape RH est déjà traitée.'];
            }

            $this->enregistrerDecision('besoin', $besoinId, 'rh', $decision, $utilisateurId, $commentaire);

            // La finance est normalement calculée dès la soumission. On la
            // recalcule uniquement si elle manque (données anciennes).
            $finance = $this->getDecision('besoin', $besoinId, 'finance');
            if (! $finance || $finance['decision'] === 'en_attente') {
                $resultat = $this->budget->financerBesoin($besoinId);
                $finance  = $this->getDecision('besoin', $besoinId, 'finance');
                $this->audit->log($utilisateurId, 'workflow.finance.' . ($resultat['accepte'] ? 'accepte' : 'refuse'),
                    'besoin', $besoinId, ['automatique' => true, 'details' => $resultat['snapshot']]);
            }

            $statut = $this->statutBesoin('En attente DG');
            (new BesoinModel())->update($besoinId, ['statut_id' => $statut]);
            $commentaireHistorique = 'Décision RH : ' . ($decision === 'accepte' ? 'validé' : 'refusé')
                . '. Finance : ' . (($finance['decision'] ?? 'en_attente') === 'accepte' ? 'accepté' : 'refusé') . '.';
            if (trim($commentaire) !== '') {
                $commentaireHistorique .= ' ' . trim($commentaire);
            }
            $this->historiserBesoin($besoinId, $statut, $utilisateurId, $commentaireHistorique);

            $this->notifications->envoyerAuxRoles(['dg'],
                'Besoin #' . $besoinId . ' prêt pour la décision finale du DG.', 'besoins/' . $besoinId);
            $this->audit->log($utilisateurId, 'workflow.rh.' . $decision, 'besoin', $besoinId,
                ['finance' => $finance['decision'] ?? 'en_attente', 'commentaire' => $commentaire]);

            return ['ok' => true, 'message' => 'Décision RH enregistrée. Le DG doit trancher.'];
        }

        if ($role === 'dg') {
            $rh      = $this->getDecision('besoin', $besoinId, 'rh');
            $finance = $this->getDecision('besoin', $besoinId, 'finance');
            $dg      = $this->getDecision('besoin', $besoinId, 'dg');

            if (! $dg || $dg['decision'] !== 'en_attente') {
                return ['ok' => false, 'message' => 'La décision du DG est déjà enregistrée.'];
            }
            if (! $rh || $rh['decision'] === 'en_attente') {
                return ['ok' => false, 'message' => 'RH doit d’abord se prononcer.'];
            }
            if (! $finance || $finance['decision'] === 'en_attente') {
                return ['ok' => false, 'message' => 'Le calcul finance doit d’abord être effectué.'];
            }

            $this->enregistrerDecision('besoin', $besoinId, 'dg', $decision, $utilisateurId, $commentaire);

            // LA DÉCISION DU DG L'EMPORTE, quelles que soient les étapes RH et finance.
            $libelle = $decision === 'accepte' ? 'Validé' : 'Refusé';
            $statut  = $this->statutBesoin($libelle);
            (new BesoinModel())->update($besoinId, [
                'statut_id'    => $statut,
                'date_cloture' => date('Y-m-d H:i:s'),
            ]);
            $commentaireFinal = 'Décision finale DG : ' . $libelle;
            if (trim($commentaire) !== '') {
                $commentaireFinal .= ' — ' . trim($commentaire);
            }
            $this->historiserBesoin($besoinId, $statut, $utilisateurId, $commentaireFinal);

            $this->notifications->envoyer((int) $besoin['demandeur_id'],
                'Votre besoin #' . $besoinId . ' est ' . mb_strtolower($libelle) . ' par le DG.',
                'besoins/' . $besoinId);
            $this->audit->log($utilisateurId, 'workflow.dg.' . $decision, 'besoin', $besoinId,
                ['rh' => $rh['decision'], 'finance' => $finance['decision'], 'commentaire' => $commentaire]);

            return ['ok' => true, 'message' => 'Décision finale du DG enregistrée : ' . $libelle . '.'];
        }

        return ['ok' => false, 'message' => 'Rôle non autorisé pour cette décision.'];
    }

    public function peutDecider(int $besoinId, string $role, ?int $utilisateurId = null): bool
    {
        $besoin = (new BesoinModel())->find($besoinId);
        if (! $besoin) {
            return false;
        }
        if ($utilisateurId !== null && (int) $besoin['demandeur_id'] === $utilisateurId) {
            return false;
        }

        if ($role === 'rh') {
            $rh = $this->getDecision('besoin', $besoinId, 'rh');
            return $rh && $rh['decision'] === 'en_attente';
        }

        if ($role === 'dg') {
            $rh      = $this->getDecision('besoin', $besoinId, 'rh');
            $finance = $this->getDecision('besoin', $besoinId, 'finance');
            $dg      = $this->getDecision('besoin', $besoinId, 'dg');

            return $rh && $rh['decision'] !== 'en_attente'
                && $finance && $finance['decision'] !== 'en_attente'
                && $dg && $dg['decision'] === 'en_attente';
        }

        return false;
    }

    public function decisionsBesoin(int $besoinId): array
    {
        $rows = db_connect()->table('workflow_validation wv')
            ->select('wv.*, u.nom AS valideur_nom, u.prenom AS valideur_prenom, u.email AS valideur_email')
            ->join('utilisateur u', 'u.id = wv.valideur_id', 'left')
            ->where('wv.objet_type', 'besoin')
            ->where('wv.objet_id', $besoinId)
            ->orderBy('wv.id', 'ASC')
            ->get()
            ->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['etape']] = $row;
        }
        return $result;
    }

    // ---------------------------------------------------------
    // CONTRE-PROPOSITIONS (éléments de proposition)
    // ---------------------------------------------------------

    public function initialiserContreProposition(int $elementId, int $utilisateurId): void
    {
        $this->creerDecision('proposition_element', $elementId, 'rh', 'en_attente', null, 'Contre-proposition à examiner.');
        $this->creerDecision('proposition_element', $elementId, 'dg', 'en_attente', null, 'Décision finale en attente.');

        (new PropositionElementModel())->update($elementId, [
            'statut' => 'contre_proposition',
            'reponse' => 'non',
        ]);

        $this->notifications->envoyerAuxRoles(['rh'],
            'Nouvelle contre-proposition à examiner (élément #' . $elementId . ').', 'propositions');
        $this->audit->log($utilisateurId, 'contre_proposition.soumettre', 'proposition_element', $elementId);
    }

    /**
     * Décision RH ou DG sur une contre-proposition.
     * @return array{ok:bool,message:string}
     */
    public function deciderElement(int $elementId, int $utilisateurId, string $role, string $decision, string $commentaire = ''): array
    {
        $element = (new PropositionElementModel())->find($elementId);
        if (! $element) {
            return ['ok' => false, 'message' => 'Élément introuvable.'];
        }
        if (! in_array($decision, ['accepte', 'refuse'], true)) {
            return ['ok' => false, 'message' => 'Décision invalide.'];
        }

        $proposition = (new PropositionModel())->find((int) $element['proposition_id']);
        $candidature = $proposition
            ? db_connect()->table('candidature')->where('id', (int) $proposition['candidature_id'])->get()->getRowArray()
            : null;
        if ($candidature && (int) $candidature['utilisateur_id'] === $utilisateurId) {
            return ['ok' => false, 'message' => 'Vous ne pouvez pas décider sur votre propre demande.'];
        }
        if ($element['statut'] !== 'contre_proposition') {
            return ['ok' => false, 'message' => 'Cette contre-proposition n’est plus en attente.'];
        }

        $proposition = (new PropositionModel())->find((int) $element['proposition_id']);
        if (! $proposition || in_array($proposition['statut'], ['confirmee', 'abandonnee'], true)) {
            return ['ok' => false, 'message' => 'Cette proposition n’est plus modifiable.'];
        }
        if ($proposition['date_envoi'] === null) {
            return ['ok' => false, 'message' => 'Cette proposition n’a pas encore été envoyée.'];
        }

        if ($role === 'rh') {
            $rh = $this->getDecision('proposition_element', $elementId, 'rh');
            if (! $rh || $rh['decision'] !== 'en_attente') {
                return ['ok' => false, 'message' => 'L’étape RH est déjà traitée.'];
            }

            $this->enregistrerDecision('proposition_element', $elementId, 'rh', $decision, $utilisateurId, $commentaire);
            $finance = $this->budget->financerElement($elementId);

            $this->notifications->envoyerAuxRoles(['dg'],
                'Contre-proposition #' . $elementId . ' prête pour la décision du DG.', 'propositions');
            $this->audit->log($utilisateurId, 'contre_proposition.rh.' . $decision, 'proposition_element', $elementId,
                ['finance' => $finance['accepte'], 'details' => $finance['snapshot']]);

            return ['ok' => true, 'message' => 'Décision RH enregistrée, étape finance calculée.'];
        }

        if ($role === 'dg') {
            $rh      = $this->getDecision('proposition_element', $elementId, 'rh');
            $finance = $this->getDecision('proposition_element', $elementId, 'finance');
            $dg      = $this->getDecision('proposition_element', $elementId, 'dg');

            if (! $dg || $dg['decision'] !== 'en_attente') {
                return ['ok' => false, 'message' => 'La décision du DG est déjà enregistrée.'];
            }
            if (! $rh || $rh['decision'] === 'en_attente') {
                return ['ok' => false, 'message' => 'RH doit d’abord se prononcer.'];
            }
            if (! $finance || $finance['decision'] === 'en_attente') {
                return ['ok' => false, 'message' => 'Le calcul finance doit d’abord être effectué.'];
            }

            $this->enregistrerDecision('proposition_element', $elementId, 'dg', $decision, $utilisateurId, $commentaire);

            $nouveauStatut = $decision === 'accepte' ? 'contre_validee' : 'contre_refusee';
            (new PropositionElementModel())->update($elementId, [
                'statut' => $nouveauStatut,
                'valeur_finale' => $decision === 'accepte' ? $element['contre_valeur'] : null,
            ]);

            $element = (new PropositionElementModel())->find($elementId);
            $proposition = (new PropositionModel())->find($element['proposition_id']);
            $candidature = $proposition ? db_connect()->table('candidature')->where('id', $proposition['candidature_id'])->get()->getRowArray() : null;

            if ($candidature) {
                $this->notifications->envoyer((int) $candidature['utilisateur_id'],
                    'Votre contre-proposition a été ' . ($decision === 'accepte' ? 'acceptée' : 'refusée') . ' par le DG.',
                    'propositions/' . $element['proposition_id']);
            }

            $this->audit->log($utilisateurId, 'contre_proposition.dg.' . $decision, 'proposition_element', $elementId,
                ['rh' => $rh['decision'], 'finance' => $finance['decision']]);

            $this->majStatutProposition((int) $element['proposition_id']);

            return ['ok' => true, 'message' => 'Décision finale du DG enregistrée.'];
        }

        return ['ok' => false, 'message' => 'Rôle non autorisé.'];
    }

    public function peutDeciderElement(int $elementId, string $role): bool
    {
        $element = (new PropositionElementModel())->find($elementId);
        if (! $element || $element['statut'] !== 'contre_proposition') {
            return false;
        }
        $proposition = (new PropositionModel())->find((int) $element['proposition_id']);
        if (! $proposition || $proposition['date_envoi'] === null
            || in_array($proposition['statut'], ['confirmee', 'abandonnee'], true)) {
            return false;
        }

        if ($role === 'rh') {
            $rh = $this->getDecision('proposition_element', $elementId, 'rh');
            return $rh && $rh['decision'] === 'en_attente';
        }
        if ($role === 'dg') {
            $rh      = $this->getDecision('proposition_element', $elementId, 'rh');
            $finance = $this->getDecision('proposition_element', $elementId, 'finance');
            $dg      = $this->getDecision('proposition_element', $elementId, 'dg');
            return $rh && $rh['decision'] !== 'en_attente'
                && $finance && $finance['decision'] !== 'en_attente'
                && $dg && $dg['decision'] === 'en_attente';
        }
        return false;
    }

    public function decisionsElement(int $elementId): array
    {
        $rows = (new WorkflowValidationModel())
            ->where('objet_type', 'proposition_element')
            ->where('objet_id', $elementId)
            ->orderBy('id', 'ASC')
            ->findAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['etape']] = $row;
        }
        return $result;
    }

    /** La proposition devient confirmable quand tous les éléments sont réglés. */
    public function majStatutProposition(int $propositionId): string
    {
        $elements = (new PropositionElementModel())->where('proposition_id', $propositionId)->findAll();
        $proposition = (new PropositionModel())->find($propositionId);

        if (! $proposition || $proposition['statut'] === 'abandonnee') {
            return $proposition['statut'] ?? 'abandonnee';
        }

        // Un brouillon n'est pas encore soumis au candidat : il ne peut pas
        // devenir confirmable avant l'envoi.
        if ($proposition['date_envoi'] === null) {
            (new PropositionModel())->update($propositionId, ['statut' => 'en_cours']);

            return 'en_cours';
        }

        $enAttenteWorkflow = false;
        $enAttenteCandidat = false;
        $tousRegles = true;

        foreach ($elements as $element) {
            $regle = in_array($element['statut'], ['accepte', 'contre_validee'], true)
                || ($element['statut'] === 'contre_refusee' && $element['reponse'] === 'oui');

            if (! $regle) {
                $tousRegles = false;
                if ($element['statut'] === 'contre_proposition') {
                    $enAttenteWorkflow = true;
                } else {
                    $enAttenteCandidat = true;
                }
            }
        }

        if ($tousRegles) {
            $statut = 'confirmable';
        } elseif ($enAttenteWorkflow) {
            $statut = 'en_attente';
        } else {
            $statut = 'en_cours';
        }

        (new PropositionModel())->update($propositionId, ['statut' => $statut]);

        if ($statut === 'confirmable') {
            $candidature = db_connect()->table('candidature')->where('id', $proposition['candidature_id'])->get()->getRowArray();
            if ($candidature) {
                $this->notifications->envoyer((int) $candidature['utilisateur_id'],
                    'Tous les éléments de votre proposition sont acceptés. Vous pouvez confirmer.',
                    'propositions/' . $propositionId);
            }
        }

        return $statut;
    }

    // ---------------------------------------------------------
    // OUTILS
    // ---------------------------------------------------------

    private function creerDecision(string $type, int $objetId, string $etape, string $decision, ?int $decideurId, ?string $commentaire): void
    {
        $existant = $this->getDecision($type, $objetId, $etape);
        if ($existant) {
            return;
        }

        (new WorkflowValidationModel())->insert([
            'objet_type'  => $type,
            'objet_id'    => $objetId,
            'etape'       => $etape,
            'decision'    => $decision,
            'valideur_id' => $decideurId,
            'automatique' => 0,
            'commentaire' => $commentaire,
        ]);
    }

    private function enregistrerDecision(string $type, int $objetId, string $etape, string $decision, int $decideurId, ?string $commentaire, bool $automatique = false): void
    {
        $model = new WorkflowValidationModel();
        $row = $this->getDecision($type, $objetId, $etape);

        $data = [
            'decision'      => $decision,
            'valideur_id'   => $decideurId,
            'automatique'   => $automatique ? 1 : 0,
            'commentaire'   => $commentaire,
            'date_decision' => date('Y-m-d H:i:s'),
        ];

        if ($row) {
            $model->update($row['id'], $data);
        } else {
            $model->insert(array_merge($data, [
                'objet_type' => $type,
                'objet_id'   => $objetId,
                'etape'      => $etape,
            ]));
        }
    }

    private function getDecision(string $type, int $objetId, string $etape): ?array
    {
        return (new WorkflowValidationModel())
            ->where('objet_type', $type)
            ->where('objet_id', $objetId)
            ->where('etape', $etape)
            ->first();
    }

    private function statutBesoin(string $libelle): int
    {
        $row = (new StatutBesoinModel())->where('libelle', $libelle)->first();
        return (int) ($row['id'] ?? 0);
    }

    private function historiserBesoin(int $besoinId, int $statutId, ?int $utilisateurId, ?string $commentaire): void
    {
        (new HistoriqueStatutBesoinModel())->insert([
            'besoin_id'        => $besoinId,
            'statut_id'        => $statutId,
            'utilisateur_id'   => $utilisateurId,
            'commentaire'      => $commentaire === null ? null : mb_substr($commentaire, 0, 255),
            'date_changement'  => date('Y-m-d H:i:s'),
        ]);
    }
}
