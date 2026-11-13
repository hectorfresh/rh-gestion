<?php

namespace App\Services;

use App\Models\AffectationModel;
use App\Models\AnnonceModel;
use App\Models\BesoinModel;
use App\Models\CandidatureModel;
use App\Models\ContratModel;
use App\Models\HistoriqueCarriereModel;
use App\Models\HistoriqueStatutCandidatureModel;
use App\Models\PropositionElementModel;
use App\Models\PropositionModel;
use App\Models\RegimeTravailModel;
use App\Models\SalarieModel;
use App\Models\StatutBesoinModel;
use App\Models\StatutCandidatureModel;
use App\Models\UtilisateurModel;

/**
 * Bloc 3 : proposition d'embauche avec questionnaire OUI/NON,
 * contre-proposition, confirmation et génération automatique du contrat.
 */
class PropositionService
{
    private const TYPES_CONTRAT = ['Essai', 'CDD', 'CDI', 'Stage', 'Alternance'];

    private WorkflowService $workflow;
    private CongeService $conges;
    private AuditService $audit;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->workflow      = new WorkflowService();
        $this->conges        = new CongeService();
        $this->audit         = new AuditService();
        $this->notifications = new NotificationService();
    }

    /**
     * Crée la proposition et ses éléments.
     *
     * Un brouillon (envoyer = false) est créé automatiquement lorsqu'une
     * candidature passe à « Retenue ». Le RH peut alors ajuster les valeurs
     * avant l'envoi au candidat.
     *
     * @return array{ok:bool,message:string,id?:int}
     */
    public function creer(int $candidatureId, array $data, array $utilisateur, bool $envoyer = true): array
    {
        if (! $this->estStaff((int) ($utilisateur['id'] ?? 0))) {
            return ['ok' => false, 'message' => 'Seuls le RH et le DG peuvent lancer une proposition.'];
        }

        $candidature = $this->infosCandidature($candidatureId);
        if (! $candidature) {
            return ['ok' => false, 'message' => 'Candidature introuvable.'];
        }
        if ($candidature['statut'] !== 'Retenue') {
            return ['ok' => false, 'message' => 'Seule une candidature au statut « Retenue » peut recevoir une proposition.'];
        }
        if ((new PropositionModel())->where('candidature_id', $candidatureId)->first()) {
            return ['ok' => false, 'message' => 'Une proposition existe déjà pour cette candidature.'];
        }

        $normalise = $this->normaliserDonnees($data, $candidature);
        if (! $normalise['ok']) {
            return ['ok' => false, 'message' => $normalise['message']];
        }
        $valeurs = $normalise['valeurs'];

        $db = db_connect();
        $db->transBegin();
        try {
            $propositionId = (int) (new PropositionModel())->insert([
                'candidature_id' => $candidatureId,
                'statut'         => 'en_cours',
                'created_by'     => (int) $utilisateur['id'],
                'date_creation'  => date('Y-m-d H:i:s'),
                'date_envoi'     => $envoyer ? date('Y-m-d H:i:s') : null,
            ]);

            $this->ecrireElements($propositionId, $valeurs);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Création proposition : ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Impossible de créer la proposition.'];
        }

        if ($envoyer) {
            $this->notifierEnvoi($candidature, $propositionId);
        }

        $this->audit->log((int) $utilisateur['id'], $envoyer ? 'proposition.creer.envoyer' : 'proposition.creer.brouillon',
            'proposition_embauche', $propositionId, [
                'candidature_id' => $candidatureId,
                'salaire'        => $valeurs['salaire'],
                'envoye'         => $envoyer,
            ]);

        return [
            'ok'      => true,
            'message' => $envoyer
                ? 'Proposition envoyée au candidat.'
                : 'Proposition préparée. Vérifiez les éléments puis envoyez-la au candidat.',
            'id'      => $propositionId,
        ];
    }

    /**
     * Met à jour une proposition encore en brouillon.
     * @return array{ok:bool,message:string,id?:int}
     */
    public function mettreAJour(int $propositionId, array $data, array $utilisateur, bool $envoyer = false): array
    {
        if (! $this->estStaff((int) ($utilisateur['id'] ?? 0))) {
            return ['ok' => false, 'message' => 'Seuls le RH et le DG peuvent modifier une proposition.'];
        }

        $proposition = (new PropositionModel())->find($propositionId);
        if (! $proposition) {
            return ['ok' => false, 'message' => 'Proposition introuvable.'];
        }
        if ($proposition['date_envoi'] !== null || $proposition['statut'] !== 'en_cours') {
            return ['ok' => false, 'message' => 'Seule une proposition en brouillon peut être modifiée.'];
        }

        $candidature = $this->infosCandidature((int) $proposition['candidature_id']);
        if (! $candidature) {
            return ['ok' => false, 'message' => 'Candidature introuvable.'];
        }

        $actuels = $this->valeursElements($propositionId);
        $donnees = array_merge($actuels, array_filter($data, static function ($valeur): bool {
            return $valeur !== null && $valeur !== '';
        }));

        $normalise = $this->normaliserDonnees($donnees, $candidature);
        if (! $normalise['ok']) {
            return ['ok' => false, 'message' => $normalise['message']];
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $elementModel = new PropositionElementModel();
            foreach ($normalise['valeurs'] as $code => $valeur) {
                $element = $elementModel->where('proposition_id', $propositionId)->where('code', $code)->first();
                if (! $element) {
                    continue;
                }
                $elementModel->update((int) $element['id'], [
                    'valeur_proposee' => $valeur,
                    'reponse'         => 'en_attente',
                    'contre_valeur'   => null,
                    'motif_candidat'  => null,
                    'statut'          => 'propose',
                    'valeur_finale'   => null,
                ]);
            }

            if ($envoyer) {
                (new PropositionModel())->update($propositionId, [
                    'date_envoi' => date('Y-m-d H:i:s'),
                    'statut'     => 'en_cours',
                ]);
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Mise à jour proposition : ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Impossible de mettre à jour la proposition.'];
        }

        $this->workflow->majStatutProposition($propositionId);
        $this->audit->log((int) $utilisateur['id'], $envoyer ? 'proposition.modifier.envoyer' : 'proposition.modifier',
            'proposition_embauche', $propositionId, ['envoye' => $envoyer]);

        if ($envoyer) {
            $this->notifierEnvoi($candidature, $propositionId);
        }

        return [
            'ok'      => true,
            'message' => $envoyer ? 'Proposition enregistrée et envoyée au candidat.' : 'Proposition mise à jour.',
            'id'      => $propositionId,
        ];
    }

    /**
     * Envoie au candidat une proposition préparée.
     * @return array{ok:bool,message:string}
     */
    public function envoyer(int $propositionId, array $utilisateur): array
    {
        if (! $this->estStaff((int) ($utilisateur['id'] ?? 0))) {
            return ['ok' => false, 'message' => 'Seuls le RH et le DG peuvent envoyer une proposition.'];
        }

        $proposition = (new PropositionModel())->find($propositionId);
        if (! $proposition) {
            return ['ok' => false, 'message' => 'Proposition introuvable.'];
        }
        if ($proposition['date_envoi'] !== null) {
            return ['ok' => false, 'message' => 'Cette proposition a déjà été envoyée.'];
        }
        if ($proposition['statut'] !== 'en_cours') {
            return ['ok' => false, 'message' => 'Cette proposition ne peut plus être envoyée.'];
        }

        $candidature = $this->infosCandidature((int) $proposition['candidature_id']);
        if (! $candidature) {
            return ['ok' => false, 'message' => 'Candidature introuvable.'];
        }
        if ($candidature['statut'] !== 'Retenue') {
            return ['ok' => false, 'message' => 'La candidature n’est plus au statut « Retenue ». L’envoi est impossible.'];
        }
        if ((new PropositionElementModel())->where('proposition_id', $propositionId)->countAllResults() < 5) {
            return ['ok' => false, 'message' => 'La proposition est incomplète.'];
        }

        (new PropositionModel())->update($propositionId, [
            'date_envoi' => date('Y-m-d H:i:s'),
            'statut'     => 'en_cours',
        ]);
        $this->notifierEnvoi($candidature, $propositionId);
        $this->audit->log((int) $utilisateur['id'], 'proposition.envoyer', 'proposition_embauche', $propositionId);

        return ['ok' => true, 'message' => 'Proposition envoyée au candidat.'];
    }

    /**
     * Réponse du candidat à un élément : OUI ou NON + contre-proposition.
     * @return array{ok:bool,message:string}
     */
    public function repondre(int $elementId, int $utilisateurId, string $reponse, ?string $valeur = null, ?string $motif = null): array
    {
        $element = (new PropositionElementModel())->find($elementId);
        if (! $element) {
            return ['ok' => false, 'message' => 'Élément introuvable.'];
        }
        if (! $this->estProprietaire((int) $element['proposition_id'], $utilisateurId)) {
            return ['ok' => false, 'message' => 'Cette proposition ne vous appartient pas.'];
        }

        $proposition = (new PropositionModel())->find($element['proposition_id']);
        if (! $proposition || $proposition['date_envoi'] === null) {
            return ['ok' => false, 'message' => 'Cette proposition n’a pas encore été envoyée.'];
        }
        if (in_array($proposition['statut'], ['confirmee', 'abandonnee'], true)) {
            return ['ok' => false, 'message' => 'Cette proposition n’est plus modifiable.'];
        }

        if ($reponse === 'oui') {
            if ($element['statut'] === 'contre_proposition') {
                return ['ok' => false, 'message' => 'Une contre-proposition est en cours d’examen.'];
            }
            if ($element['statut'] === 'contre_validee' || $element['statut'] === 'accepte') {
                return ['ok' => true, 'message' => 'Élément déjà accepté.'];
            }

            (new PropositionElementModel())->update($elementId, [
                'reponse'       => 'oui',
                'statut'        => 'accepte',
                'valeur_finale' => $element['valeur_proposee'],
            ]);

            $this->workflow->majStatutProposition((int) $element['proposition_id']);
            $this->audit->log($utilisateurId, 'proposition.element.accepter', 'proposition_element', $elementId);

            return ['ok' => true, 'message' => 'Élément accepté.'];
        }

        if ($reponse !== 'non') {
            return ['ok' => false, 'message' => 'Réponse invalide.'];
        }

        if ($element['statut'] !== 'propose' || $element['reponse'] !== 'en_attente') {
            return ['ok' => false, 'message' => 'Cet élément a déjà reçu une réponse.'];
        }

        $valeur = trim((string) $valeur);
        if ($valeur === '') {
            return ['ok' => false, 'message' => 'Saisissez votre contre-proposition.'];
        }
        $motif = trim((string) $motif);
        if ($motif === '') {
            return ['ok' => false, 'message' => 'Le motif de la contre-proposition est obligatoire.'];
        }
        if (($erreur = $this->validerValeurElement((string) $element['code'], $valeur)) !== null) {
            return ['ok' => false, 'message' => $erreur];
        }

        (new PropositionElementModel())->update($elementId, [
            'reponse'        => 'non',
            'contre_valeur'  => mb_substr($valeur, 0, 190),
            'motif_candidat' => mb_substr($motif, 0, 255),
            'statut'         => 'contre_proposition',
            'valeur_finale'  => null,
        ]);

        $this->workflow->initialiserContreProposition($elementId, $utilisateurId);
        $this->workflow->majStatutProposition((int) $element['proposition_id']);

        return ['ok' => true, 'message' => 'Contre-proposition envoyée. Circuit RH → finance → DG.'];
    }

    public function renoncer(int $propositionId, int $utilisateurId): array
    {
        $proposition = (new PropositionModel())->find($propositionId);
        if (! $proposition || ! $this->estProprietaire($propositionId, $utilisateurId)) {
            return ['ok' => false, 'message' => 'Proposition introuvable.'];
        }
        if ($proposition['statut'] === 'confirmee') {
            return ['ok' => false, 'message' => 'Cette proposition est déjà confirmée.'];
        }
        if ($proposition['statut'] === 'abandonnee') {
            return ['ok' => true, 'message' => 'Cette proposition est déjà abandonnée.'];
        }
        if ($proposition['date_envoi'] === null) {
            return ['ok' => false, 'message' => 'Cette proposition n’a pas encore été envoyée.'];
        }

        $statutDesiste = $this->statutCandidature('Désistée');
        $db = db_connect();
        $db->transBegin();
        try {
            (new PropositionModel())->update($propositionId, ['statut' => 'abandonnee']);
            (new CandidatureModel())->update((int) $proposition['candidature_id'], [
                'statut_id' => $statutDesiste,
            ]);
            (new HistoriqueStatutCandidatureModel())->insert([
                'candidature_id'  => (int) $proposition['candidature_id'],
                'statut_id'       => $statutDesiste,
                'utilisateur_id'  => $utilisateurId,
                'commentaire'     => 'Le candidat a renoncé à la proposition.',
                'date_changement' => date('Y-m-d H:i:s'),
            ]);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Renonciation proposition : ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Impossible d’enregistrer votre renonciation.'];
        }

        $this->notifications->envoyerAuxRoles(['rh'],
            'Le candidat a renoncé à la proposition #' . $propositionId . '.', 'propositions/' . $propositionId);
        $this->audit->log($utilisateurId, 'proposition.renoncer', 'proposition_embauche', $propositionId);

        return ['ok' => true, 'message' => 'Vous avez renoncé à la proposition.'];
    }

    /**
     * Confirmation finale : génère le contrat et l'embauche dans une transaction.
     * Le verrou FOR UPDATE sérialise les doubles clics.
     */
    public function confirmer(int $propositionId, int $utilisateurId): array
    {
        $db = db_connect();
        $db->transBegin();

        try {
            $proposition = $db->query(
                'SELECT * FROM proposition_embauche WHERE id = ? FOR UPDATE',
                [$propositionId]
            )->getRowArray();

            if (! $proposition || ! $this->estProprietaire($propositionId, $utilisateurId)) {
                throw new \DomainException('Proposition introuvable.');
            }
            if ($proposition['statut'] === 'confirmee') {
                throw new \DomainException('Cette proposition a déjà été confirmée.');
            }
            if ($proposition['statut'] !== 'confirmable') {
                throw new \DomainException('Tous les éléments doivent d’abord être acceptés.');
            }
            if ($proposition['date_envoi'] === null) {
                throw new \DomainException('Cette proposition n’a pas encore été envoyée.');
            }

            $dejaContrat = (new ContratModel())->where('candidature_id', (int) $proposition['candidature_id'])->first();
            if ($dejaContrat) {
                throw new \DomainException('Un contrat a déjà été généré pour cette candidature.');
            }

            $elements = (new PropositionElementModel())->where('proposition_id', $propositionId)->findAll();
            if (count($elements) !== 5) {
                throw new \DomainException('La proposition est incomplète et ne peut pas être confirmée.');
            }
            foreach ($elements as $element) {
                if (! $this->elementRegle($element)) {
                    throw new \DomainException('Tous les éléments doivent d’abord être acceptés.');
                }
            }

            $valeurs = [];
            foreach ($elements as $element) {
                $valeurs[$element['code']] = $this->valeurFinale($element);
            }

            $candidature = (new CandidatureModel())->find((int) $proposition['candidature_id']);
            if (! $candidature) {
                throw new \DomainException('Candidature introuvable.');
            }
            $statutCandidature = (new StatutCandidatureModel())->find((int) $candidature['statut_id']);
            if (! $statutCandidature || $statutCandidature['libelle'] !== 'Retenue') {
                throw new \DomainException('La candidature doit être au statut « Retenue » pour confirmer l’embauche.');
            }

            $annonce = (new AnnonceModel())->find((int) $candidature['annonce_id']);
            $besoin  = $annonce ? (new BesoinModel())->find((int) $annonce['besoin_id']) : null;
            $utilisateur = (new UtilisateurModel())->find($utilisateurId);

            if (! $annonce || ! $besoin || ! $utilisateur) {
                throw new \DomainException('Données de recrutement incomplètes.');
            }

            $salaire = (float) ($valeurs['salaire'] ?? 0);
            if ($salaire <= 0) {
                throw new \DomainException('Le salaire final est invalide.');
            }

            $type = (string) ($valeurs['type_contrat'] ?? 'CDI');
            if (! in_array($type, self::TYPES_CONTRAT, true)) {
                throw new \DomainException('Le type de contrat final est invalide.');
            }

            $debut = (string) ($valeurs['date_debut'] ?? date('Y-m-d'));
            if (! $this->dateValide($debut)) {
                throw new \DomainException('La date de début finale est invalide.');
            }

            $essai = (int) ($valeurs['periode_essai'] ?? 0);
            if ($essai < 0 || $essai > 12) {
                throw new \DomainException('La période d’essai finale est invalide.');
            }

            $horaires = (string) ($valeurs['horaires'] ?? '08:00 - 17:00');
            [$heureDebut, $heureFin] = $this->parserHoraires($horaires);
            if ($heureDebut === null || $heureFin === null) {
                throw new \DomainException('Les horaires finaux sont invalides.');
            }

            $regime = (new RegimeTravailModel())->orderBy('id', 'ASC')->first();
            if (! $regime) {
                throw new \DomainException('Aucun régime de travail configuré.');
            }
            $roleEmploye = $db->table('role')->where('libelle', 'employe')->get()->getRowArray();
            if (! $roleEmploye) {
                throw new \DomainException('Rôle employé introuvable.');
            }

            // Le manager de la nouvelle recrue est le responsable du besoin,
            // s'il est lui-même salarié.
            $manager = $db->table('salarie')
                ->where('utilisateur_id', (int) $besoin['demandeur_id'])
                ->where('actif', 1)
                ->get()->getRowArray();

            // 1. Créer ou réactiver la fiche salarié.
            $salarieModel = new SalarieModel();
            $salarie      = $salarieModel->where('utilisateur_id', $utilisateurId)->first();

            $identite = [
                'nom'               => $utilisateur['nom'],
                'prenom'            => $utilisateur['prenom'],
                'email'             => $utilisateur['email'],
                'telephone'         => $utilisateur['telephone'] ?? null,
                'departement_id'    => (int) $besoin['departement_id'],
                'poste_id'          => (int) $besoin['poste_id'],
                'manager_id'        => $manager ? (int) $manager['id'] : null,
                'regime_travail_id' => (int) $regime['id'],
                'date_embauche'     => $debut,
                'date_sortie'       => null,
                'actif'             => 1,
            ];

            if ($salarie) {
                $salarieId = (int) $salarie['id'];
                $salarieModel->update($salarieId, array_merge($identite, [
                    'candidature_id' => (int) $candidature['id'],
                ]));
            } else {
                $salarieId = (int) $salarieModel->insert(array_merge($identite, [
                    'utilisateur_id' => $utilisateurId,
                    'candidature_id' => (int) $candidature['id'],
                    'matricule'      => $this->genererMatricule($debut),
                ]));
            }

            // 2. L'utilisateur candidat devient employé.
            if ($roleEmploye) {
                (new UtilisateurModel())->update($utilisateurId, ['role_id' => (int) $roleEmploye['id']]);
            }

            // 3. Affectation et contrat avec les valeurs finales.
            (new AffectationModel())->insert([
                'salarie_id'     => $salarieId,
                'departement_id' => (int) $besoin['departement_id'],
                'poste_id'       => (int) $besoin['poste_id'],
                'manager_id'     => $manager ? (int) $manager['id'] : null,
                'date_debut'     => $debut,
                'motif'          => 'Embauche via proposition #' . $propositionId,
            ]);

            $dateFin = null;
            if ($type === 'CDD') {
                $dateFin = date('Y-m-d', strtotime($debut . ' +12 months'));
            } elseif ($type === 'Essai') {
                $dateFin = date('Y-m-d', strtotime($debut . ' +' . max(1, $essai) . ' months'));
            }

            $contratId = (int) (new ContratModel())->insert([
                'candidature_id'     => (int) $candidature['id'],
                'salarie_id'         => $salarieId,
                'type_contrat'       => $type,
                'date_debut'         => $debut,
                'date_fin'           => $dateFin,
                'salaire_mensuel'    => $salaire,
                'regime_travail_id'  => (int) $regime['id'],
                'heure_debut'        => $heureDebut,
                'heure_fin'          => $heureFin,
                'periode_essai_mois' => $essai,
                'statut'             => 'Accepte',
                'date_creation'      => date('Y-m-d H:i:s'),
                'date_reponse'       => date('Y-m-d H:i:s'),
            ]);

            // 4. Proposition confirmée, candidature embauchée.
            (new PropositionModel())->update($propositionId, [
                'statut'            => 'confirmee',
                'date_confirmation' => date('Y-m-d H:i:s'),
            ]);

            $statutEmbauche = $this->statutCandidature('Embauchée');
            (new CandidatureModel())->update((int) $candidature['id'], ['statut_id' => $statutEmbauche]);
            (new HistoriqueStatutCandidatureModel())->insert([
                'candidature_id'  => (int) $candidature['id'],
                'statut_id'       => $statutEmbauche,
                'utilisateur_id'  => $utilisateurId,
                'commentaire'     => 'Proposition confirmée, contrat généré.',
                'date_changement' => date('Y-m-d H:i:s'),
            ]);

            // 5. Onboarding et historique de carrière.
            (new OnboardingService())->initialiser($salarieId, $contratId, (int) ($besoin['demandeur_id'] ?? 0) ?: null);

            (new HistoriqueCarriereModel())->insert([
                'salarie_id'             => $salarieId,
                'type_evenement'         => 'embauche',
                'date_evenement'         => $debut,
                'nouveau_poste_id'       => (int) $besoin['poste_id'],
                'nouveau_departement_id' => (int) $besoin['departement_id'],
                'nouveau_salaire'        => $salaire,
                'commentaire'            => 'Embauche via proposition #' . $propositionId,
                'created_by'             => $utilisateurId,
            ]);

            // 6. Clôturer l'annonce et le besoin si toutes les places sont pourvues.
            $this->cloturerSiPourvu((int) $besoin['id']);

            // 7. Initialiser le solde de congés (crédit calculé par CongeService).
            $typePaye = $db->table('type_conge')->where('libelle', 'Congé payé')->get()->getRowArray();
            if (! $typePaye) {
                throw new \DomainException('Type de congé payé introuvable.');
            }
            $anneeCourante = (int) date('Y');
            $anneeEmbauche = (int) date('Y', strtotime($debut));
            $this->conges->assurerSolde($salarieId, (int) $typePaye['id'], $anneeCourante);
            if ($anneeEmbauche !== $anneeCourante) {
                $this->conges->assurerSolde($salarieId, (int) $typePaye['id'], $anneeEmbauche);
            }

            $db->transCommit();
        } catch (\DomainException $e) {
            $db->transRollback();

            return ['ok' => false, 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Confirmation proposition : ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Erreur lors de la génération du contrat.'];
        }

        $this->notifications->envoyerAuxRoles(['rh', 'dg'],
            'Embauche confirmée : ' . $utilisateur['prenom'] . ' ' . $utilisateur['nom'] . ' (contrat #' . $contratId . ').',
            'contrats/' . $contratId);
        $this->notifications->envoyer($utilisateurId,
            'Votre contrat est généré. Bienvenue dans l’équipe !', 'contrats/' . $contratId);
        $this->audit->log($utilisateurId, 'proposition.confirmer', 'proposition_embauche', $propositionId, [
            'salarie_id' => $salarieId, 'contrat_id' => $contratId,
        ]);

        (new ContratService())->genererPdf($contratId, true, $utilisateurId);

        return [
            'ok'         => true,
            'message'    => 'Proposition confirmée. Le contrat a été généré et l’embauche enregistrée.',
            'contrat_id' => $contratId,
        ];
    }

    /** @return array{ok:bool,message:string,id?:int} */
    public function changerStatutCandidature(int $candidatureId, string $libelle, array $utilisateur): array
    {
        $statut = $this->statutCandidature($libelle);
        if (! $statut) {
            return ['ok' => false, 'message' => 'Statut inconnu.'];
        }
        (new CandidatureModel())->update($candidatureId, ['statut_id' => $statut]);
        (new HistoriqueStatutCandidatureModel())->insert([
            'candidature_id'  => $candidatureId,
            'statut_id'       => $statut,
            'utilisateur_id'  => (int) $utilisateur['id'],
            'commentaire'     => $libelle,
            'date_changement' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'message' => 'Statut mis à jour.'];
    }

    /**
     * Valeurs par défaut / normalisées d'une proposition.
     * @return array{ok:bool,message:string,valeurs?:array<string,string>}
     */
    private function normaliserDonnees(array $data, array $candidature): array
    {
        $base = (float) $candidature['salaire_base'];
        $max  = (float) $candidature['salaire_max'];
        if ($max < $base) {
            $max = $base;
        }

        $salaire = isset($data['salaire']) && $data['salaire'] !== ''
            ? (float) $data['salaire']
            : round(($base + $max) / 2, 2);
        if ($salaire <= 0) {
            return ['ok' => false, 'message' => 'Le salaire proposé doit être positif.'];
        }
        if ($salaire < $base || $salaire > $max) {
            return ['ok' => false, 'message' => 'Le salaire proposé doit rester dans la fourchette du poste ('
                . number_format($base, 0, ',', ' ') . ' – ' . number_format($max, 0, ',', ' ') . ' Ar).'];
        }

        $type = trim((string) ($data['type_contrat'] ?? ''));
        if ($type === '') {
            $type = 'CDI';
        }
        if (! in_array($type, self::TYPES_CONTRAT, true)) {
            return ['ok' => false, 'message' => 'Type de contrat invalide.'];
        }

        $debut = (string) ($data['date_debut'] ?? '');
        if ($debut === '') {
            $debut = ! empty($candidature['date_souhaitee']) && $candidature['date_souhaitee'] >= date('Y-m-d')
                ? $candidature['date_souhaitee']
                : date('Y-m-d', strtotime('+1 month'));
        }
        if (! $this->dateValide($debut)) {
            return ['ok' => false, 'message' => 'La date de début est invalide.'];
        }

        $heureDebut = trim((string) ($data['heure_debut'] ?? ''));
        $heureFin   = trim((string) ($data['heure_fin'] ?? ''));
        if ($heureDebut === '') {
            $heureDebut = '08:00';
        }
        if ($heureFin === '') {
            $heureFin = '17:00';
        }
        if (isset($data['horaires']) && trim((string) $data['horaires']) !== '') {
            [$heureDebut, $heureFin] = $this->parserHoraires((string) $data['horaires']);
        }
        if (! $this->heureValide($heureDebut) || ! $this->heureValide($heureFin)) {
            return ['ok' => false, 'message' => 'Les horaires de travail sont invalides.'];
        }

        $essai = isset($data['periode_essai']) && $data['periode_essai'] !== ''
            ? (int) $data['periode_essai']
            : 3;
        if ($essai < 0 || $essai > 12) {
            return ['ok' => false, 'message' => 'La période d’essai doit être comprise entre 0 et 12 mois.'];
        }

        return ['ok' => true, 'valeurs' => [
            'salaire'       => (string) $salaire,
            'type_contrat'  => $type,
            'date_debut'    => $debut,
            'horaires'      => $heureDebut . ' - ' . $heureFin,
            'periode_essai' => (string) $essai,
        ]];
    }

    private function ecrireElements(int $propositionId, array $valeurs): void
    {
        $libelles = [
            'salaire'       => 'Salaire mensuel',
            'type_contrat'  => 'Type de contrat',
            'date_debut'    => 'Date de début',
            'horaires'      => 'Horaires de travail',
            'periode_essai' => 'Période d’essai (mois)',
        ];

        foreach ($libelles as $code => $libelle) {
            (new PropositionElementModel())->insert([
                'proposition_id'  => $propositionId,
                'code'            => $code,
                'libelle'         => $libelle,
                'valeur_proposee' => (string) $valeurs[$code],
                'reponse'         => 'en_attente',
                'statut'          => 'propose',
            ]);
        }
    }

    private function valeursElements(int $propositionId): array
    {
        $elements = (new PropositionElementModel())->where('proposition_id', $propositionId)->findAll();
        $valeurs  = [];
        foreach ($elements as $element) {
            if ($element['code'] === 'horaires') {
                [$debut, $fin] = $this->parserHoraires((string) $element['valeur_proposee']);
                $valeurs['heure_debut'] = $debut ?? '08:00';
                $valeurs['heure_fin']   = $fin ?? '17:00';
            } elseif ($element['code'] === 'salaire') {
                $valeurs['salaire'] = $element['valeur_proposee'];
            } else {
                $valeurs[$element['code']] = $element['valeur_proposee'];
            }
        }

        return $valeurs;
    }

    private function infosCandidature(int $candidatureId): ?array
    {
        $row = db_connect()->table('candidature c')
            ->select('c.*, sc.libelle AS statut, a.besoin_id, b.poste_id, b.departement_id,
                      b.salaire_estime, b.date_souhaitee, b.demandeur_id,
                      p.salaire_base, p.salaire_max, p.libelle AS poste')
            ->join('statut_candidature sc', 'sc.id = c.statut_id')
            ->join('annonce a', 'a.id = c.annonce_id')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->where('c.id', $candidatureId)
            ->get()->getRowArray();

        return $row ?: null;
    }

    private function notifierEnvoi(array $candidature, int $propositionId): void
    {
        $this->notifications->envoyer((int) $candidature['utilisateur_id'],
            'Une proposition d’embauche vous a été envoyée. Répondez à chaque élément.',
            'propositions/' . $propositionId);
    }

    private function validerValeurElement(string $code, string $valeur): ?string
    {
        switch ($code) {
            case 'salaire':
                if (! is_numeric($valeur) || (float) $valeur <= 0) {
                    return 'Le salaire contre-proposé doit être un montant positif.';
                }
                break;

            case 'type_contrat':
                if (! in_array($valeur, self::TYPES_CONTRAT, true)) {
                    return 'Le type de contrat contre-proposé est invalide.';
                }
                break;

            case 'date_debut':
                if (! $this->dateValide($valeur)) {
                    return 'La date de début contre-proposée est invalide.';
                }
                break;

            case 'horaires':
                [$debut, $fin] = $this->parserHoraires($valeur);
                if ($debut === null || $fin === null) {
                    return 'Les horaires contre-proposés doivent être au format HH:MM - HH:MM.';
                }
                break;

            case 'periode_essai':
                if (! is_numeric($valeur) || (int) $valeur < 0 || (int) $valeur > 12) {
                    return 'La période d’essai contre-proposée doit être comprise entre 0 et 12 mois.';
                }
                break;
        }

        return null;
    }

    private function elementRegle(array $element): bool
    {
        return in_array($element['statut'], ['accepte', 'contre_validee'], true)
            || ($element['statut'] === 'contre_refusee' && $element['reponse'] === 'oui');
    }

    private function dateValide(string $date): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        [$annee, $mois, $jour] = array_map('intval', explode('-', $date));

        return checkdate($mois, $jour, $annee);
    }

    private function heureValide(?string $heure): bool
    {
        if ($heure === null) {
            return false;
        }

        return (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', substr($heure, 0, 5));
    }

    private function valeurFinale(array $element): string
    {
        if ($element['statut'] === 'contre_validee' && $element['contre_valeur'] !== null) {
            return (string) $element['contre_valeur'];
        }
        if ($element['valeur_finale'] !== null && $element['valeur_finale'] !== '') {
            return (string) $element['valeur_finale'];
        }

        return (string) $element['valeur_proposee'];
    }

    /** @return array{0:?string,1:?string} */
    private function parserHoraires(string $horaires): array
    {
        if (preg_match('/(\d{1,2}:\d{2}).*?(\d{1,2}:\d{2})/', $horaires, $m)) {
            $debut = str_pad($m[1], 5, '0', STR_PAD_LEFT);
            $fin   = str_pad($m[2], 5, '0', STR_PAD_LEFT);
            if ($this->heureValide($debut) && $this->heureValide($fin)) {
                return [$debut, $fin];
            }
        }

        return [null, null];
    }

    private function genererMatricule(string $dateEmbauche): string
    {
        $annee  = date('Y', strtotime($dateEmbauche));
        $prefix = 'EMP-' . $annee . '-';
        $row = db_connect()->query(
            "SELECT MAX(CAST(SUBSTRING_INDEX(matricule, '-', -1) AS UNSIGNED)) AS max_num
             FROM salarie WHERE matricule LIKE ?",
            [$prefix . '%']
        )->getRowArray();
        $next = ((int) ($row['max_num'] ?? 0)) + 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function statutCandidature(string $libelle): int
    {
        $row = (new StatutCandidatureModel())->where('libelle', $libelle)->first();
        if (! $row) {
            throw new \RuntimeException('Statut de candidature introuvable : ' . $libelle);
        }

        return (int) $row['id'];
    }

    private function estStaff(int $utilisateurId): bool
    {
        if ($utilisateurId <= 0) {
            return false;
        }
        $row = db_connect()->table('utilisateur u')
            ->select('r.libelle AS role')
            ->join('role r', 'r.id = u.role_id')
            ->where('u.id', $utilisateurId)
            ->get()->getRowArray();

        return $row && in_array($row['role'], ['rh', 'dg'], true);
    }

    private function estProprietaire(int $propositionId, int $utilisateurId): bool
    {
        $row = db_connect()->table('proposition_embauche p')
            ->select('ca.utilisateur_id')
            ->join('candidature ca', 'ca.id = p.candidature_id')
            ->where('p.id', $propositionId)
            ->get()
            ->getRowArray();

        return $row && (int) $row['utilisateur_id'] === $utilisateurId;
    }

    private function cloturerSiPourvu(int $besoinId): void
    {
        $db = db_connect();
        $besoin = (new BesoinModel())->find($besoinId);
        if (! $besoin) {
            return;
        }

        $hired = $db->table('candidature ca')
            ->join('annonce a', 'a.id = ca.annonce_id')
            ->join('statut_candidature sc', 'sc.id = ca.statut_id')
            ->where('a.besoin_id', $besoinId)
            ->whereIn('sc.libelle', ['Retenue', 'Embauchée'])
            ->countAllResults();

        if ($hired >= (int) $besoin['nombre']) {
            $statut = (new StatutBesoinModel())->where('libelle', 'Pourvu')->first();
            if ($statut) {
                (new BesoinModel())->update($besoinId, [
                    'statut_id'    => (int) $statut['id'],
                    'date_cloture' => date('Y-m-d H:i:s'),
                ]);
            }
            (new AnnonceModel())->where('besoin_id', $besoinId)->set([
                'est_cloture'  => 1,
                'date_cloture' => date('Y-m-d H:i:s'),
            ])->update();
        }
    }
}
