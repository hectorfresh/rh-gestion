<?php

namespace App\Services;

use App\Models\AnnonceModel;
use App\Models\BesoinModel;
use App\Models\CandidatureModel;
use App\Models\CvExperienceModel;
use App\Models\CvFormationModel;
use App\Models\CvIdentiteModel;
use App\Models\CvLangueModel;
use App\Models\CvModel;
use App\Models\EntretienModel;
use App\Models\EvaluationCandidatModel;
use App\Models\HistoriqueStatutCandidatureModel;
use App\Models\PosteModel;
use App\Models\StatutCandidatureModel;
use App\Models\StatutEntretienModel;

/**
 * Blocs 1 à 3 : expression du besoin, recrutement et sélection.
 *
 * Ce service centralise toutes les règles du Bloc 2 :
 * - annonce créée uniquement depuis un besoin validé, une seule par besoin ;
 * - candidature unique par annonce et par candidat ;
 * - CV guidé (identité, formations, expériences, langues) ;
 * - suivi des statuts et historique ;
 * - entretiens, réponse du candidat et évaluation ;
 * - décision « Retenue » qui déclenche la proposition d'embauche.
 */
class RecrutementService
{
    /** Statuts modifiables manuellement par RH/DG. */
    private const STATUTS_GERES = [
        'Reçue',
        'Présélectionnée',
        'En attente entretien',
        'Retenue',
        'Non retenue',
    ];

    private WorkflowService $workflow;
    private AuditService $audit;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->workflow      = new WorkflowService();
        $this->audit         = new AuditService();
        $this->notifications = new NotificationService();
    }

    /**
     * Conservé pour compatibilité : la création vit désormais dans BesoinService.
     */
    public function creerBesoin(array $data, array $utilisateur): array
    {
        return (new BesoinService())->creer($data, $utilisateur);
    }

    // ------------------------------------------------------------
    // Annonces
    // ------------------------------------------------------------

    public function creerAnnonce(int $besoinId, array $data, array $utilisateur): array
    {
        if (! $this->estStaff($utilisateur)) {
            return ['ok' => false, 'message' => 'Seuls le RH et le DG peuvent publier une annonce.'];
        }

        $besoin = db_connect()->table('besoin b')
            ->select('b.*, sb.libelle AS statut, p.libelle AS poste')
            ->join('statut_besoin sb', 'sb.id = b.statut_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->where('b.id', $besoinId)
            ->get()->getRowArray();

        if (! $besoin) {
            return ['ok' => false, 'message' => 'Besoin introuvable.'];
        }
        if ($besoin['statut'] !== 'Validé') {
            return ['ok' => false, 'message' => 'Seul un besoin validé par le DG peut donner lieu à une annonce.'];
        }

        if ((new AnnonceModel())->where('besoin_id', $besoinId)->first()) {
            return ['ok' => false, 'message' => 'Une annonce existe déjà pour ce besoin. Modifiez-la plutôt que d’en créer une seconde.'];
        }

        $titre = trim((string) ($data['titre'] ?? ''));
        if ($titre === '') {
            $titre = 'Recrutement : ' . $besoin['poste'];
        }
        if (mb_strlen($titre) > 190) {
            return ['ok' => false, 'message' => 'Le titre de l’annonce est trop long (190 caractères maximum).'];
        }

        $dateLimite = $this->normaliserDate($data['date_limite'] ?? null);
        if ($dateLimite === false) {
            return ['ok' => false, 'message' => 'La date limite est invalide.'];
        }
        if ($dateLimite !== null && $dateLimite < date('Y-m-d')) {
            return ['ok' => false, 'message' => 'La date limite ne peut pas être passée.'];
        }

        $id = (new AnnonceModel())->insert([
            'besoin_id'        => $besoinId,
            'titre'            => $titre,
            'description'      => $this->texteOuNull($data['description'] ?? $besoin['description']),
            'profil'           => $this->texteOuNull($data['profil'] ?? null),
            'date_publication' => date('Y-m-d H:i:s'),
            'date_limite'      => $dateLimite,
            'est_cloture'      => 0,
            'date_cloture'     => null,
            'createur_id'      => (int) $utilisateur['id'],
        ]);

        $this->audit->log((int) $utilisateur['id'], 'annonce.creer', 'annonce', (int) $id, ['besoin_id' => $besoinId]);
        $this->notifications->envoyerAuxRoles(['candidat'], 'Nouvelle annonce publiée : ' . $titre, 'annonces/' . $id);

        return ['ok' => true, 'message' => 'Annonce publiée.', 'id' => (int) $id];
    }

    public function modifierAnnonce(int $annonceId, array $data, array $utilisateur): array
    {
        if (! $this->estStaff($utilisateur)) {
            return ['ok' => false, 'message' => 'Seuls le RH et le DG peuvent modifier une annonce.'];
        }

        $annonce = (new AnnonceModel())->find($annonceId);
        if (! $annonce) {
            return ['ok' => false, 'message' => 'Annonce introuvable.'];
        }

        $titre = trim((string) ($data['titre'] ?? ''));
        if ($titre === '') {
            return ['ok' => false, 'message' => 'Le titre de l’annonce est obligatoire.'];
        }
        if (mb_strlen($titre) > 190) {
            return ['ok' => false, 'message' => 'Le titre de l’annonce est trop long (190 caractères maximum).'];
        }

        $dateLimite = $this->normaliserDate($data['date_limite'] ?? null);
        if ($dateLimite === false) {
            return ['ok' => false, 'message' => 'La date limite est invalide.'];
        }
        if ($dateLimite !== null && $dateLimite < date('Y-m-d')) {
            return ['ok' => false, 'message' => 'La date limite ne peut pas être passée.'];
        }

        (new AnnonceModel())->update($annonceId, [
            'titre'       => $titre,
            'description' => $this->texteOuNull($data['description'] ?? null),
            'profil'      => $this->texteOuNull($data['profil'] ?? null),
            'date_limite' => $dateLimite,
        ]);

        $this->audit->log((int) $utilisateur['id'], 'annonce.modifier', 'annonce', $annonceId);

        return ['ok' => true, 'message' => 'Annonce mise à jour.', 'id' => $annonceId];
    }

    public function cloturerAnnonce(int $annonceId, array $utilisateur): array
    {
        if (! $this->estStaff($utilisateur)) {
            return ['ok' => false, 'message' => 'Seuls le RH et le DG peuvent clôturer une annonce.'];
        }

        $annonce = (new AnnonceModel())->find($annonceId);
        if (! $annonce) {
            return ['ok' => false, 'message' => 'Annonce introuvable.'];
        }
        if ((int) $annonce['est_cloture'] === 1) {
            return ['ok' => false, 'message' => 'Cette annonce est déjà clôturée.'];
        }

        (new AnnonceModel())->update($annonceId, [
            'est_cloture'  => 1,
            'date_cloture' => date('Y-m-d H:i:s'),
        ]);
        $this->audit->log((int) $utilisateur['id'], 'annonce.cloturer', 'annonce', $annonceId, ['manuel' => true]);

        return ['ok' => true, 'message' => 'Annonce clôturée.'];
    }

    // ------------------------------------------------------------
    // Candidatures
    // ------------------------------------------------------------

    /**
     * Candidature d'un utilisateur. Le CV peut être réutilisé ou créé
     * en suivant les quatre étapes du formulaire guidé.
     */
    public function postuler(int $annonceId, array $utilisateur, array $data): array
    {
        if (! $this->estCandidat($utilisateur)) {
            return ['ok' => false, 'message' => 'Seuls les comptes candidats peuvent postuler.'];
        }

        $annonce = db_connect()->table('annonce a')
            ->select('a.*, b.nombre, b.statut_id, sb.libelle AS statut_besoin')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('statut_besoin sb', 'sb.id = b.statut_id')
            ->where('a.id', $annonceId)
            ->get()->getRowArray();

        if (! $annonce || (int) $annonce['est_cloture'] === 1) {
            return ['ok' => false, 'message' => 'Cette annonce n’est plus disponible.'];
        }
        if (! empty($annonce['date_limite']) && $annonce['date_limite'] < date('Y-m-d')) {
            return ['ok' => false, 'message' => 'La date limite de candidature est dépassée.'];
        }
        if ($annonce['statut_besoin'] !== 'Validé') {
            return ['ok' => false, 'message' => 'Cette annonce n’accepte plus de candidatures.'];
        }

        if ((new CandidatureModel())
            ->where('annonce_id', $annonceId)
            ->where('utilisateur_id', (int) $utilisateur['id'])
            ->first()) {
            return ['ok' => false, 'message' => 'Vous avez déjà postulé à cette annonce.'];
        }

        $statut = (new StatutCandidatureModel())->where('libelle', 'Reçue')->first();
        if (! $statut) {
            return ['ok' => false, 'message' => 'Statut de candidature « Reçue » manquant.'];
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $cvId = $this->enregistrerCv((int) $utilisateur['id'], $data, $db);
            if (is_array($cvId)) {
                $db->transRollback();

                return $cvId;
            }

            $candidatureId = (int) (new CandidatureModel())->insert([
                'annonce_id'        => $annonceId,
                'cv_id'             => $cvId,
                'utilisateur_id'    => (int) $utilisateur['id'],
                'date_candidature'  => date('Y-m-d H:i:s'),
                'statut_id'         => (int) $statut['id'],
                'lettre_motivation' => $this->texteOuNull($data['lettre_motivation'] ?? null),
            ]);

            $this->historiserCandidature(
                $candidatureId,
                (int) $statut['id'],
                (int) $utilisateur['id'],
                'Candidature déposée.'
            );

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Candidature : ' . $e->getMessage());

            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'duplicate')) {
                return ['ok' => false, 'message' => 'Vous avez déjà postulé à cette annonce.'];
            }

            return ['ok' => false, 'message' => 'Impossible d’enregistrer la candidature.'];
        }

        $this->notifications->envoyerAuxRoles(
            ['rh'],
            'Nouvelle candidature #' . $candidatureId . ' reçue.',
            'candidatures/' . $candidatureId
        );
        $this->audit->log((int) $utilisateur['id'], 'candidature.postuler', 'candidature', $candidatureId, [
            'annonce_id' => $annonceId,
            'cv_id'      => $cvId,
        ]);

        return ['ok' => true, 'message' => 'Candidature envoyée.', 'id' => $candidatureId];
    }

    public function changerStatutCandidature(int $candidatureId, string $libelleStatut, array $utilisateur, string $commentaire = ''): array
    {
        if (! $this->estStaff($utilisateur)) {
            return ['ok' => false, 'message' => 'Seuls le RH et le DG peuvent modifier le statut d’une candidature.'];
        }

        if (! in_array($libelleStatut, self::STATUTS_GERES, true)) {
            return ['ok' => false, 'message' => 'Statut de candidature non autorisé.'];
        }

        $candidature = (new CandidatureModel())->find($candidatureId);
        $statut      = (new StatutCandidatureModel())->where('libelle', $libelleStatut)->first();

        if (! $candidature || ! $statut) {
            return ['ok' => false, 'message' => 'Candidature ou statut introuvable.'];
        }

        (new CandidatureModel())->update($candidatureId, ['statut_id' => (int) $statut['id']]);
        $this->historiserCandidature(
            $candidatureId,
            (int) $statut['id'],
            (int) $utilisateur['id'],
            $commentaire !== '' ? $commentaire : $libelleStatut
        );

        $this->audit->log((int) $utilisateur['id'], 'candidature.statut', 'candidature', $candidatureId, [
            'statut' => $libelleStatut,
        ]);

        $message = 'Statut de la candidature mis à jour.';
        if ($libelleStatut === 'Retenue') {
            $proposition = $this->creerPropositionAutomatique($candidatureId, $utilisateur);
            if (! empty($proposition['ok'])) {
                $message .= ' Une proposition d’embauche a été préparée automatiquement : vérifiez-la avant de l’envoyer.';
            } elseif (! empty($proposition['message'])) {
                $message .= ' Proposition non créée : ' . $proposition['message'];
            }
            $this->cloturerSiPourvu((int) $candidature['annonce_id'], (int) $utilisateur['id']);
        }

        $this->notifications->envoyer(
            (int) $candidature['utilisateur_id'],
            'Votre candidature #' . $candidatureId . ' est maintenant : ' . $libelleStatut . '.',
            'candidatures/' . $candidatureId
        );

        return ['ok' => true, 'message' => $message];
    }

    // ------------------------------------------------------------
    // Entretiens
    // ------------------------------------------------------------

    public function planifierEntretien(int $candidatureId, array $data, array $utilisateur): array
    {
        if (! $this->estStaff($utilisateur)) {
            return ['ok' => false, 'message' => 'Seuls le RH et le DG peuvent planifier un entretien.'];
        }

        $candidature = db_connect()->table('candidature c')
            ->select('c.*, sc.libelle AS statut')
            ->join('statut_candidature sc', 'sc.id = c.statut_id')
            ->where('c.id', $candidatureId)
            ->get()->getRowArray();

        if (! $candidature) {
            return ['ok' => false, 'message' => 'Candidature introuvable.'];
        }
        if (in_array($candidature['statut'], ['Non retenue', 'Embauchée', 'Désistée'], true)) {
            return ['ok' => false, 'message' => 'Cette candidature ne peut plus recevoir d’entretien.'];
        }

        $date = trim((string) ($data['date_entretien'] ?? ''));
        $lieu = trim((string) ($data['lieu'] ?? ''));
        if ($date === '' || $lieu === '') {
            return ['ok' => false, 'message' => 'La date et le lieu de l’entretien sont obligatoires.'];
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return ['ok' => false, 'message' => 'La date de l’entretien est invalide.'];
        }
        if ($timestamp < time()) {
            return ['ok' => false, 'message' => 'L’entretien doit être planifié dans le futur.'];
        }

        $type = trim((string) ($data['type'] ?? 'RH')) ?: 'RH';
        if (mb_strlen($type) > 50) {
            return ['ok' => false, 'message' => 'Le type d’entretien est trop long.'];
        }

        $intervieweurId = (int) ($data['intervieweur_id'] ?? $utilisateur['id']);
        $intervieweur = db_connect()->table('utilisateur u')
            ->select('u.*, r.libelle AS role')
            ->join('role r', 'r.id = u.role_id')
            ->where('u.id', $intervieweurId)
            ->where('u.actif', 1)
            ->get()->getRowArray();
        if (! $intervieweur || ! in_array($intervieweur['role'], ['rh', 'dg', 'manager'], true)) {
            return ['ok' => false, 'message' => 'L’intervieweur choisi est invalide.'];
        }

        $statut = (new StatutEntretienModel())->where('libelle', 'Planifié')->first();
        if (! $statut) {
            return ['ok' => false, 'message' => 'Statut d’entretien « Planifié » manquant.'];
        }

        $id = (int) (new EntretienModel())->insert([
            'candidature_id'   => $candidatureId,
            'type'             => $type,
            'date_entretien'   => date('Y-m-d H:i:s', $timestamp),
            'lieu'             => mb_substr($lieu, 0, 190),
            'commentaire'      => $this->texteOuNull($data['commentaire'] ?? null),
            'statut_id'        => (int) $statut['id'],
            'intervieweur_id'  => $intervieweurId,
            'reponse_candidat' => 'en_attente',
        ]);

        $this->changerStatutCandidature(
            $candidatureId,
            'En attente entretien',
            $utilisateur,
            'Entretien de type ' . $type . ' planifié.'
        );

        $this->notifications->envoyer(
            (int) $candidature['utilisateur_id'],
            'Convocation à un entretien ' . $type . ' le ' . date('d/m/Y H:i', $timestamp) . ' — ' . $lieu . '.',
            'entretiens/mes'
        );
        if ($intervieweurId !== (int) $utilisateur['id']) {
            $this->notifications->envoyer(
                $intervieweurId,
                'Un entretien vous est assigné le ' . date('d/m/Y H:i', $timestamp) . ' (candidature #' . $candidatureId . ').',
                'candidatures/' . $candidatureId
            );
        }

        $this->audit->log((int) $utilisateur['id'], 'entretien.planifier', 'entretien', $id, [
            'candidature_id' => $candidatureId,
            'intervieweur_id'=> $intervieweurId,
            'type'           => $type,
        ]);

        return ['ok' => true, 'message' => 'Entretien planifié et candidat convoqué.', 'id' => $id];
    }

    public function repondreEntretien(int $entretienId, array $utilisateur, string $reponse, ?string $motif = null): array
    {
        $entretien = db_connect()->table('entretien e')
            ->select('e.*, c.utilisateur_id, c.id AS candidature_id, c.statut_id AS candidature_statut_id')
            ->join('candidature c', 'c.id = e.candidature_id')
            ->where('e.id', $entretienId)
            ->get()->getRowArray();

        if (! $entretien) {
            return ['ok' => false, 'message' => 'Entretien introuvable.'];
        }
        if ((int) $entretien['utilisateur_id'] !== (int) $utilisateur['id']) {
            return ['ok' => false, 'message' => 'Cet entretien ne vous appartient pas.'];
        }
        if ($entretien['reponse_candidat'] !== 'en_attente') {
            return ['ok' => false, 'message' => 'Vous avez déjà répondu à cette convocation.'];
        }
        if (! in_array($reponse, ['accepte', 'refuse'], true)) {
            return ['ok' => false, 'message' => 'Réponse invalide.'];
        }

        $donnees = [
            'reponse_candidat' => $reponse,
            'motif_reponse'    => $this->texteOuNull($motif),
            'date_reponse'     => date('Y-m-d H:i:s'),
        ];

        if ($reponse === 'refuse') {
            $statutAnnule = (new StatutEntretienModel())->where('libelle', 'Annulé')->first();
            if ($statutAnnule) {
                $donnees['statut_id'] = (int) $statutAnnule['id'];
            }
            $statutPreselection = (new StatutCandidatureModel())->where('libelle', 'Présélectionnée')->first();
            if ($statutPreselection) {
                (new CandidatureModel())->update((int) $entretien['candidature_id'], [
                    'statut_id' => (int) $statutPreselection['id'],
                ]);
                $this->historiserCandidature(
                    (int) $entretien['candidature_id'],
                    (int) $statutPreselection['id'],
                    (int) $utilisateur['id'],
                    'Convocation refusée par le candidat.'
                );
            }
        }

        (new EntretienModel())->update($entretienId, $donnees);

        $this->notifications->envoyerAuxRoles(
            ['rh'],
            'Le candidat a ' . ($reponse === 'accepte' ? 'accepté' : 'refusé') . ' l’entretien #' . $entretienId . '.',
            'candidatures/' . $entretien['candidature_id']
        );
        if (! empty($entretien['intervieweur_id'])) {
            $this->notifications->envoyer(
                (int) $entretien['intervieweur_id'],
                'Réponse du candidat à l’entretien #' . $entretienId . ' : ' . ($reponse === 'accepte' ? 'accepté' : 'refusé') . '.',
                'candidatures/' . $entretien['candidature_id']
            );
        }
        $this->audit->log((int) $utilisateur['id'], 'entretien.repondre', 'entretien', $entretienId, [
            'reponse' => $reponse,
        ]);

        return [
            'ok'      => true,
            'message' => $reponse === 'accepte'
                ? 'Votre présence à l’entretien est confirmée.'
                : 'Votre refus a été enregistré. Le RH vous recontactera.',
        ];
    }

    public function evaluerEntretien(int $entretienId, array $data, array $utilisateur): array
    {
        if (! $this->estStaff($utilisateur) && $this->roleLibelle($utilisateur) !== 'manager') {
            return ['ok' => false, 'message' => 'Vous n’êtes pas autorisé à évaluer cet entretien.'];
        }

        $entretien = (new EntretienModel())->find($entretienId);
        if (! $entretien) {
            return ['ok' => false, 'message' => 'Entretien introuvable.'];
        }
        if ((new EvaluationCandidatModel())->where('entretien_id', $entretienId)->first()) {
            return ['ok' => false, 'message' => 'Cet entretien a déjà été évalué.'];
        }

        $note = (float) ($data['note'] ?? -1);
        if ($note < 0 || $note > 20) {
            return ['ok' => false, 'message' => 'La note doit être comprise entre 0 et 20.'];
        }

        $decision = (string) ($data['decision'] ?? '');
        if (! in_array($decision, ['retenue', 'refusee', 'reserve'], true)) {
            return ['ok' => false, 'message' => 'La décision d’évaluation est invalide.'];
        }

        $statut = (new StatutEntretienModel())->where('libelle', 'Réalisé')->first();
        if (! $statut) {
            return ['ok' => false, 'message' => 'Statut d’entretien « Réalisé » manquant.'];
        }

        (new EntretienModel())->update($entretienId, [
            'note'        => $note,
            'commentaire' => $this->texteOuNull($data['commentaire'] ?? null),
            'statut_id'   => (int) $statut['id'],
        ]);

        $decisionEvaluation = match ($decision) {
            'retenue' => 'retenu',
            'refusee' => 'refuse',
            default   => 'reserve',
        };

        (new EvaluationCandidatModel())->insert([
            'candidature_id' => (int) $entretien['candidature_id'],
            'entretien_id'   => $entretienId,
            'note'           => $note,
            'appreciation'   => $this->texteOuNull($data['commentaire'] ?? null),
            'decision'       => $decisionEvaluation,
            'evaluateur_id'  => (int) $utilisateur['id'],
        ]);

        if ($decision === 'retenue') {
            $this->changerStatutCandidature(
                (int) $entretien['candidature_id'],
                'Retenue',
                $utilisateur,
                'Candidat retenu après entretien.'
            );
        } elseif ($decision === 'refusee') {
            $this->changerStatutCandidature(
                (int) $entretien['candidature_id'],
                'Non retenue',
                $utilisateur,
                'Candidat non retenu après entretien.'
            );
        }

        $this->notifications->envoyerAuxRoles(
            ['rh'],
            'Entretien #' . $entretienId . ' évalué : ' . $note . '/20.',
            'candidatures/' . $entretien['candidature_id']
        );
        $this->audit->log((int) $utilisateur['id'], 'entretien.evaluer', 'entretien', $entretienId, [
            'note'     => $note,
            'decision' => $decisionEvaluation,
        ]);

        return ['ok' => true, 'message' => 'Évaluation enregistrée.'];
    }

    // ------------------------------------------------------------
    // CV
    // ------------------------------------------------------------

    /**
     * @return int|array{ok:false,message:string}
     */
    private function enregistrerCv(int $utilisateurId, array $data, $db)
    {
        $cvId = (int) ($data['cv_id'] ?? 0);
        if ($cvId > 0) {
            $cv = (new CvModel())->where('id', $cvId)->where('utilisateur_id', $utilisateurId)->first();

            return $cv ? $cvId : ['ok' => false, 'message' => 'CV introuvable.'];
        }

        $titre = trim((string) ($data['titre_cv'] ?? ''));
        if ($titre === '') {
            return ['ok' => false, 'message' => 'Le titre du CV est obligatoire.'];
        }

        $cvId = (int) (new CvModel())->insert([
            'utilisateur_id' => $utilisateurId,
            'titre'          => mb_substr($titre, 0, 150),
            'descri'         => $this->texteOuNull($data['description_cv'] ?? null),
        ]);

        (new CvIdentiteModel())->insert([
            'cv_id'     => $cvId,
            'nom'       => trim((string) ($data['nom'] ?? '')),
            'telephone' => $this->texteOuNull($data['telephone'] ?? null),
            'adresse'   => $this->texteOuNull($data['adresse'] ?? null),
            'ville'     => $this->texteOuNull($data['ville'] ?? null),
            'email'     => $this->texteOuNull($data['email'] ?? null),
        ]);

        foreach ($this->lignes($data['formations'] ?? [], ['diplome', 'etablissement']) as $formation) {
            (new CvFormationModel())->insert([
                'cv_id'         => $cvId,
                'diplome'       => $this->texteOuNull($formation['diplome'] ?? null),
                'etablissement' => $this->texteOuNull($formation['etablissement'] ?? null),
                'date_debut'    => $this->normaliserDate($formation['date_debut'] ?? null) ?: null,
                'date_fin'      => $this->normaliserDate($formation['date_fin'] ?? null) ?: null,
            ]);
        }

        // Compatibilité avec l'ancien formulaire plat.
        if (empty($data['formations']) && (! empty($data['diplome']) || ! empty($data['etablissement']))) {
            (new CvFormationModel())->insert([
                'cv_id'         => $cvId,
                'diplome'       => $this->texteOuNull($data['diplome']),
                'etablissement' => $this->texteOuNull($data['etablissement']),
            ]);
        }

        foreach ($this->lignes($data['experiences'] ?? [], ['poste', 'entreprise', 'description']) as $experience) {
            (new CvExperienceModel())->insert([
                'cv_id'       => $cvId,
                'poste'       => $this->texteOuNull($experience['poste'] ?? null),
                'entreprise'  => $this->texteOuNull($experience['entreprise'] ?? null),
                'date_debut'  => $this->normaliserDate($experience['date_debut'] ?? null) ?: null,
                'date_fin'    => $this->normaliserDate($experience['date_fin'] ?? null) ?: null,
                'description' => $this->texteOuNull($experience['description'] ?? null),
            ]);
        }

        // Compatibilité avec l'ancien formulaire plat.
        if (empty($data['experiences']) && (! empty($data['experience_poste']) || ! empty($data['experience_entreprise']))) {
            (new CvExperienceModel())->insert([
                'cv_id'       => $cvId,
                'poste'       => $this->texteOuNull($data['experience_poste']),
                'entreprise'  => $this->texteOuNull($data['experience_entreprise']),
                'description' => $this->texteOuNull($data['experience_description'] ?? null),
            ]);
        }

        $languesVues = [];
        foreach ($this->lignes($data['langues'] ?? [], ['langue_id', 'niveau_id']) as $langue) {
            $langueId = (int) ($langue['langue_id'] ?? 0);
            $niveauId = (int) ($langue['niveau_id'] ?? 0);
            if ($langueId <= 0 || $niveauId <= 0 || isset($languesVues[$langueId])) {
                continue;
            }

            $existe = $db->table('langue')->where('id', $langueId)->countAllResults() > 0
                && $db->table('niveau')->where('id', $niveauId)->countAllResults() > 0;
            if (! $existe) {
                continue;
            }

            (new CvLangueModel())->insert([
                'cv_id'     => $cvId,
                'langue_id' => $langueId,
                'niveau_id' => $niveauId,
            ]);
            $languesVues[$langueId] = true;
        }

        return $cvId;
    }

    // ------------------------------------------------------------
    // Proposition automatique et clôture
    // ------------------------------------------------------------

    /** @return array{ok:bool,message:string,id?:int} */
    private function creerPropositionAutomatique(int $candidatureId, array $utilisateur): array
    {
        if ((new \App\Models\PropositionModel())->where('candidature_id', $candidatureId)->first()) {
            return ['ok' => true, 'message' => 'Une proposition existe déjà.'];
        }

        // La proposition est préparée en brouillon : le RH vérifie les valeurs
        // pré-remplies et l'envoie au candidat depuis l'écran de proposition.
        return (new PropositionService())->creer($candidatureId, [], $utilisateur, false);
    }

    private function cloturerSiPourvu(int $annonceId, int $utilisateurId): bool
    {
        $db = db_connect();
        $annonce = (new AnnonceModel())->find($annonceId);
        if (! $annonce || (int) $annonce['est_cloture'] === 1) {
            return false;
        }

        $besoin = (new BesoinModel())->find((int) $annonce['besoin_id']);
        if (! $besoin) {
            return false;
        }

        $pourvus = $db->table('candidature c')
            ->join('statut_candidature sc', 'sc.id = c.statut_id')
            ->where('c.annonce_id', $annonceId)
            ->whereIn('sc.libelle', ['Retenue', 'Embauchée'])
            ->countAllResults();

        if ($pourvus < (int) $besoin['nombre']) {
            return false;
        }

        (new AnnonceModel())->update($annonceId, [
            'est_cloture'  => 1,
            'date_cloture' => date('Y-m-d H:i:s'),
        ]);

        $statutPourvu = db_connect()->table('statut_besoin')->where('libelle', 'Pourvu')->get()->getRowArray();
        if ($statutPourvu) {
            (new BesoinModel())->update((int) $besoin['id'], [
                'statut_id'    => (int) $statutPourvu['id'],
                'date_cloture' => date('Y-m-d H:i:s'),
            ]);
            db_connect()->table('historique_statut_besoin')->insert([
                'besoin_id'      => (int) $besoin['id'],
                'statut_id'      => (int) $statutPourvu['id'],
                'utilisateur_id' => $utilisateurId,
                'commentaire'    => 'Tous les postes ont été pourvus.',
                'date_changement'=> date('Y-m-d H:i:s'),
            ]);
        }

        $this->audit->log($utilisateurId, 'annonce.cloture_auto', 'annonce', $annonceId, [
            'besoin_id' => (int) $besoin['id'],
            'pourvus'   => $pourvus,
            'nombre'    => (int) $besoin['nombre'],
        ]);
        $this->notifications->envoyerAuxRoles(
            ['rh', 'dg'],
            'Annonce #' . $annonceId . ' clôturée automatiquement : tous les postes sont pourvus.',
            'annonces/' . $annonceId
        );

        return true;
    }

    // ------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------

    private function historiserCandidature(int $candidatureId, int $statutId, ?int $utilisateurId, ?string $commentaire): void
    {
        (new HistoriqueStatutCandidatureModel())->insert([
            'candidature_id'  => $candidatureId,
            'statut_id'       => $statutId,
            'utilisateur_id'  => $utilisateurId,
            'commentaire'     => $commentaire,
            'date_changement' => date('Y-m-d H:i:s'),
        ]);
    }

    private function roleLibelle(array $utilisateur): string
    {
        if (! empty($utilisateur['role'])) {
            return (string) $utilisateur['role'];
        }

        $row = db_connect()->table('role')->where('id', (int) ($utilisateur['role_id'] ?? 0))->get()->getRowArray();

        return (string) ($row['libelle'] ?? '');
    }

    private function estStaff(array $utilisateur): bool
    {
        return in_array($this->roleLibelle($utilisateur), ['rh', 'dg'], true);
    }

    private function estCandidat(array $utilisateur): bool
    {
        return $this->roleLibelle($utilisateur) === 'candidat';
    }

    /** Retourne une date normalisée, null si vide, false si invalide. */
    private function normaliserDate(?string $date)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        if (! $dt || $dt->format('Y-m-d') !== $date) {
            return false;
        }

        return $date;
    }

    private function texteOuNull($texte): ?string
    {
        $texte = trim((string) $texte);

        return $texte === '' ? null : $texte;
    }

    /**
     * Filtre les lignes vides d'un formulaire répétable.
     * @param mixed $lignes
     * @param list<string> $champs
     * @return array<int,array<string,mixed>>
     */
    private function lignes($lignes, array $champs): array
    {
        if (! is_array($lignes)) {
            return [];
        }

        $resultat = [];
        foreach ($lignes as $ligne) {
            if (! is_array($ligne)) {
                continue;
            }
            foreach ($champs as $champ) {
                if (! empty($ligne[$champ])) {
                    $resultat[] = $ligne;
                    break;
                }
            }
        }

        return $resultat;
    }
}
