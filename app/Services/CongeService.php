<?php

namespace App\Services;

use App\Models\DemandeCongeModel;
use App\Models\DroitCongeModel;
use App\Models\HistoriqueStatutCongeModel;
use App\Models\JourFerieModel;
use App\Models\SalarieModel;
use App\Models\SoldeCongeModel;
use App\Models\StatutCongeModel;
use App\Models\TypeCongeModel;
use DateTime;

/**
 * Gestion des congés.
 *
 * Règle de crédit : 2,5 jours de congé payé par mois écoulé depuis l'embauche,
 * plafonnés à 30 jours par an, sans report d'une année sur l'autre.
 * Workflow : demande → contrôle du solde → validation manager → validation RH.
 */
class CongeService
{
    private AuditService $audit;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->audit         = new AuditService();
        $this->notifications = new NotificationService();
    }

    /**
     * Crée ou met à jour le solde d'un salarié pour une année.
     * Le crédit est calculé, jamais saisi en dur.
     */
    public function assurerSolde(int $salarieId, int $typeCongeId, int $annee): array
    {
        $salarie = (new SalarieModel())->find($salarieId);
        $type    = (new TypeCongeModel())->find($typeCongeId);

        if (! $salarie || ! $type) {
            return ['jours_acquis' => 0.0, 'jours_pris' => 0.0, 'disponible' => 0.0];
        }

        $acquis = $this->calculerAcquis($salarie, $type, $annee);
        $dernierMois = $this->dernierMoisCredite($salarie, $annee);

        $model = new SoldeCongeModel();
        $solde = $model->where('salarie_id', $salarieId)
            ->where('type_conge_id', $typeCongeId)
            ->where('annee', $annee)
            ->first();

        if (! $solde) {
            $id = $model->insert([
                'salarie_id'           => $salarieId,
                'type_conge_id'        => $typeCongeId,
                'annee'                => $annee,
                'jours_acquis'         => $acquis,
                'jours_pris'           => 0,
                'dernier_mois_credite' => $dernierMois,
            ]);
            $solde = $model->find($id);
        } elseif ((float) $solde['jours_acquis'] !== (float) $acquis) {
            // Le crédit ne peut qu'augmenter au fil des mois ; on ne réécrit pas
            // un solde déjà consommé au-delà du crédit calculé.
            $nouveau = max((float) $solde['jours_acquis'], $acquis);
            $model->update($solde['id'], [
                'jours_acquis'         => $nouveau,
                'dernier_mois_credite' => max((int) $solde['dernier_mois_credite'], $dernierMois),
            ]);
            $solde['jours_acquis'] = $nouveau;
            $solde['dernier_mois_credite'] = max((int) $solde['dernier_mois_credite'], $dernierMois);
        }

        $solde['disponible'] = round((float) $solde['jours_acquis'] - (float) $solde['jours_pris'], 2);

        return $solde;
    }

    private function calculerAcquis(array $salarie, array $type, int $annee): float
    {
        $embauche   = new DateTime($salarie['date_embauche']);
        $debutAnnee = new DateTime($annee . '-01-01');
        $finAnnee   = new DateTime($annee . '-12-31');
        $aujourdhui = new DateTime('today');

        if ($embauche > $finAnnee || $debutAnnee > $aujourdhui) {
            return 0.0;
        }

        $droit = $this->droitPour($type, $embauche, $aujourdhui);
        $joursParMois = $droit ? (float) $droit['jours_par_mois'] : 0.0;
        $maximum      = $droit && $droit['jours_max_annuel'] !== null
            ? (float) $droit['jours_max_annuel']
            : ($type['jours_max_par_an'] !== null ? (float) $type['jours_max_par_an'] : null);

        $mois = $this->dernierMoisCredite($salarie, $annee);
        $acquis = $joursParMois * $mois;

        if ($maximum !== null) {
            $acquis = min($maximum, $acquis);
        }

        return round($acquis, 2);
    }

    /** Nombre de mois crédités dans l'année (0 à 12), sans report. */
    private function dernierMoisCredite(array $salarie, int $annee): int
    {
        $embauche   = new DateTime($salarie['date_embauche']);
        $debutAnnee = new DateTime($annee . '-01-01');
        $finAnnee   = new DateTime($annee . '-12-31');
        $aujourdhui = new DateTime('today');

        if ($embauche > $finAnnee || $debutAnnee > $aujourdhui) {
            return 0;
        }

        $debut = $embauche > $debutAnnee ? $embauche : $debutAnnee;
        $fin   = $finAnnee < $aujourdhui ? $finAnnee : $aujourdhui;

        if ($debut > $fin) {
            return 0;
        }

        $mois = ((int) $fin->format('Y') - (int) $debut->format('Y')) * 12
            + ((int) $fin->format('n') - (int) $debut->format('n'));

        if ((int) $fin->format('j') < (int) $debut->format('j')) {
            $mois--;
        }

        return max(0, min(12, $mois));
    }

    private function droitPour(array $type, DateTime $embauche, DateTime $aujourdhui): ?array
    {
        $anciennete = ((int) $aujourdhui->format('Y') - (int) $embauche->format('Y')) * 12
            + ((int) $aujourdhui->format('n') - (int) $embauche->format('n'));
        $anciennete = max(0, $anciennete);

        return (new DroitCongeModel())
            ->where('type_conge_id', (int) $type['id'])
            ->where('anciennete_min_mois <=', $anciennete)
            ->where('actif', 1)
            ->orderBy('anciennete_min_mois', 'DESC')
            ->first();
    }

    public function soldes(int $salarieId, int $annee): array
    {
        $types = (new TypeCongeModel())->orderBy('id', 'ASC')->findAll();
        $result = [];

        foreach ($types as $type) {
            $solde = $this->assurerSolde($salarieId, (int) $type['id'], $annee);
            $result[] = array_merge($type, $solde);
        }

        return $result;
    }

    public function disponible(int $salarieId, int $typeCongeId, int $annee): float
    {
        $solde = $this->assurerSolde($salarieId, $typeCongeId, $annee);
        return (float) $solde['disponible'];
    }

    /** Jours ouvrables : du lundi au vendredi, hors jours fériés. */
    public function joursOuvrables(string $debut, string $fin): float
    {
        $start = new DateTime($debut);
        $end   = new DateTime($fin);
        if ($end < $start) {
            return 0.0;
        }

        $feries = array_column((new JourFerieModel())->findAll(), 'jour');
        $feries = array_flip($feries);

        $jours = 0;
        $curseur = clone $start;
        while ($curseur <= $end) {
            $jourSemaine = (int) $curseur->format('N');
            $date = $curseur->format('Y-m-d');
            if ($jourSemaine <= 5 && ! isset($feries[$date])) {
                $jours++;
            }
            $curseur->modify('+1 day');
        }

        return (float) $jours;
    }

    /**
     * @return array{ok:bool,message:string,id?:int}
     */
    public function demander(int $salarieId, int $typeCongeId, string $debut, string $fin, string $motif, ?string $justificatif, int $utilisateurId, bool $demiJournee = false): array
    {
        $type = (new TypeCongeModel())->find($typeCongeId);
        $salarie = (new SalarieModel())->find($salarieId);

        if (! $type || ! $salarie) {
            return ['ok' => false, 'message' => 'Salarié ou type de congé introuvable.'];
        }

        if ($debut === '' || $fin === '' || $fin < $debut) {
            return ['ok' => false, 'message' => 'Les dates de congé sont invalides.'];
        }

        $jours = $this->joursOuvrables($debut, $fin);
        if ($demiJournee && $debut === $fin) {
            $jours = 0.5;
        }
        if ($jours <= 0) {
            return ['ok' => false, 'message' => 'La période ne contient aucun jour ouvrable.'];
        }

        if ($this->chevaucheDemande($salarieId, $debut, $fin)) {
            return ['ok' => false, 'message' => 'Cette demande chevauche une autre demande de congé.'];
        }
        if ($this->chevaucheAbsence($salarieId, $debut, $fin)) {
            return ['ok' => false, 'message' => 'Cette demande chevauche une absence existante.'];
        }

        $annee = (int) date('Y', strtotime($debut));
        if ((int) date('Y', strtotime($fin)) !== $annee) {
            return ['ok' => false, 'message' => 'Un congé ne peut pas chevaucher deux années civiles.'];
        }

        // Contrôle du solde pour les types soumis à quota.
        if ($type['jours_max_par_an'] !== null) {
            $disponible = $this->disponible($salarieId, $typeCongeId, $annee);
            if ($jours > $disponible) {
                return ['ok' => false, 'message' => 'Solde insuffisant : ' . $disponible . ' jour(s) disponible(s), ' . $jours . ' demandé(s).'];
            }
        }

        if ((int) $type['justificatif_requis'] === 1 && ! $justificatif) {
            return ['ok' => false, 'message' => 'Un justificatif est obligatoire pour ce type de congé.'];
        }

        $model = new DemandeCongeModel();
        $statut = $this->statut('En attente');

        $id = $model->insert([
            'salarie_id'     => $salarieId,
            'type_conge_id'  => $typeCongeId,
            'date_debut'     => $debut,
            'date_fin'       => $fin,
            'nombre_jours'   => $jours,
            'motif'          => $motif,
            'justificatif'   => $justificatif,
            'statut_id'      => $statut,
            'date_demande'   => date('Y-m-d H:i:s'),
        ]);

        $this->historiser((int) $id, $statut, $utilisateurId, 'Demande soumise.');

        // Notification au manager du salarié.
        $manager = $salarie['manager_id']
            ? (new SalarieModel())->find($salarie['manager_id'])
            : null;
        if ($manager) {
            $this->notifications->envoyer((int) $manager['utilisateur_id'],
                'Demande de congé #' . $id . ' à valider.', 'conges/' . $id);
        } else {
            $this->notifications->envoyerAuxRoles(['rh'],
                'Demande de congé #' . $id . ' à valider.', 'conges/' . $id);
        }

        $this->audit->log($utilisateurId, 'conge.demander', 'demande_conge', (int) $id, [
            'jours' => $jours, 'debut' => $debut, 'fin' => $fin,
        ]);

        return ['ok' => true, 'message' => 'Demande de congé enregistrée (' . $jours . ' jour(s)).', 'id' => (int) $id];
    }

    public function validerManager(int $demandeId, int $utilisateurId, string $role, string $commentaire = ''): array
    {
        $demande = (new DemandeCongeModel())->find($demandeId);
        if (! $demande) {
            return ['ok' => false, 'message' => 'Demande introuvable.'];
        }
        if ((int) $demande['statut_id'] !== $this->statut('En attente')) {
            return ['ok' => false, 'message' => 'Cette demande n’est pas en attente de validation manager.'];
        }

        $salarie  = (new SalarieModel())->find($demande['salarie_id']);
        $monProfil = (new SalarieModel())->where('utilisateur_id', $utilisateurId)->first();

        if ((int) $demande['salarie_id'] === (int) ($monProfil['id'] ?? 0)) {
            return ['ok' => false, 'message' => 'Vous ne pouvez pas valider votre propre demande.'];
        }

        $estManager = $role === 'dg'
            || ($role === 'manager' && $monProfil && (int) $salarie['manager_id'] === (int) $monProfil['id']);

        if (! $estManager) {
            return ['ok' => false, 'message' => 'Seul le manager du salarié (ou le DG) peut valider cette étape.'];
        }

        $statut = $this->statut('Validé manager');
        (new DemandeCongeModel())->update($demandeId, [
            'statut_id'          => $statut,
            'valide_manager_par' => $utilisateurId,
            'date_validation_manager' => date('Y-m-d H:i:s'),
            'commentaire_validation' => $commentaire,
        ]);
        $this->historiser($demandeId, $statut, $utilisateurId, $commentaire ?: 'Validation manager.');

        $this->notifications->envoyerAuxRoles(['rh'],
            'Demande de congé #' . $demandeId . ' validée par le manager, à traiter par le RH.', 'conges/' . $demandeId);
        $this->audit->log($utilisateurId, 'conge.manager.accepter', 'demande_conge', $demandeId);

        return ['ok' => true, 'message' => 'Validation manager enregistrée. La demande passe au RH.'];
    }

    public function validerRh(int $demandeId, int $utilisateurId, string $role, string $commentaire = ''): array
    {
        $demande = (new DemandeCongeModel())->find($demandeId);
        if (! $demande) {
            return ['ok' => false, 'message' => 'Demande introuvable.'];
        }
        if (! in_array($role, ['rh', 'dg'], true)) {
            return ['ok' => false, 'message' => 'Seul le RH (ou le DG) peut valider définitivement.'];
        }
        if ((int) $demande['statut_id'] !== $this->statut('Validé manager')) {
            return ['ok' => false, 'message' => 'La demande doit d’abord être validée par le manager.'];
        }

        $monProfil = (new SalarieModel())->where('utilisateur_id', $utilisateurId)->first();
        if ((int) $demande['salarie_id'] === (int) ($monProfil['id'] ?? 0)) {
            return ['ok' => false, 'message' => 'Vous ne pouvez pas valider votre propre demande.'];
        }

        $annee = (int) date('Y', strtotime($demande['date_debut']));
        $type  = (new TypeCongeModel())->find($demande['type_conge_id']);
        if (! $type) {
            return ['ok' => false, 'message' => 'Type de congé introuvable.'];
        }

        if ($type['jours_max_par_an'] !== null) {
            $disponible = $this->disponible((int) $demande['salarie_id'], (int) $demande['type_conge_id'], $annee);
            if ((float) $demande['nombre_jours'] > $disponible) {
                return ['ok' => false, 'message' => 'Solde insuffisant au moment de la validation finale.'];
            }
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $statut = $this->statut('Accepté');
            (new DemandeCongeModel())->update($demandeId, [
                'statut_id'       => $statut,
                'date_traitement' => date('Y-m-d H:i:s'),
                'traite_par'      => $utilisateurId,
                'commentaire_validation' => $commentaire,
            ]);

            if ($type['jours_max_par_an'] !== null) {
                $solde = $this->assurerSolde((int) $demande['salarie_id'], (int) $demande['type_conge_id'], $annee);
                (new SoldeCongeModel())->update($solde['id'], [
                    'jours_pris' => (float) $solde['jours_pris'] + (float) $demande['nombre_jours'],
                ]);
            }

            // Création d'une absence par jour ouvrable et mise à jour de la paie variable.
            $curseur = new \DateTime($demande['date_debut']);
            $fin = new \DateTime($demande['date_fin']);
            $feries = array_flip(array_column((new JourFerieModel())->findAll(), 'jour'));
            while ($curseur <= $fin) {
                $date = $curseur->format('Y-m-d');
                $jourSemaine = (int) $curseur->format('N');
                if ($jourSemaine <= 5 && ! isset($feries[$date])) {
                    (new \App\Models\AbsenceModel())->insert([
                        'salarie_id' => (int) $demande['salarie_id'],
                        'date_debut' => $date,
                        'date_fin'   => $date,
                        'type'       => 'autorisee',
                        'motif'      => 'Congé #' . $demandeId,
                        'justificatif' => $demande['justificatif'],
                    ]);
                }
                $curseur->modify('+1 day');
            }

            (new \App\Models\VariablePaieModel())->insert([
                'salarie_id' => (int) $demande['salarie_id'],
                'annee'      => $annee,
                'mois'       => (int) date('n', strtotime($demande['date_debut'])),
                'libelle'    => 'Congé #' . $demandeId,
                'montant'    => (float) $demande['nombre_jours'],
                'type'       => 'autre',
                'created_by' => $utilisateurId,
            ]);

            $this->historiser($demandeId, $statut, $utilisateurId, $commentaire ?: 'Validation finale RH.');
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Validation congé : ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Erreur lors de la validation finale.'];
        }

        $salarie = (new SalarieModel())->find($demande['salarie_id']);
        if ($salarie) {
            $this->notifications->envoyer((int) $salarie['utilisateur_id'],
                'Votre demande de congé #' . $demandeId . ' est acceptée.', 'conges/' . $demandeId);
        }
        $this->audit->log($utilisateurId, 'conge.rh.accepter', 'demande_conge', $demandeId);

        return ['ok' => true, 'message' => 'Demande de congé acceptée. Le solde a été mis à jour.'];
    }

    public function refuser(int $demandeId, int $utilisateurId, string $role, string $commentaire = ''): array
    {
        if (trim($commentaire) === '') {
            return ['ok' => false, 'message' => 'Un commentaire est obligatoire en cas de refus.'];
        }

        $demande = (new DemandeCongeModel())->find($demandeId);
        if (! $demande) {
            return ['ok' => false, 'message' => 'Demande introuvable.'];
        }
        if (! in_array($role, ['manager', 'rh', 'dg'], true)) {
            return ['ok' => false, 'message' => 'Rôle non autorisé.'];
        }

        $salarie   = (new SalarieModel())->find($demande['salarie_id']);
        $monProfil = (new SalarieModel())->where('utilisateur_id', $utilisateurId)->first();

        if ((int) $demande['salarie_id'] === (int) ($monProfil['id'] ?? 0)) {
            return ['ok' => false, 'message' => 'Vous ne pouvez pas refuser votre propre demande.'];
        }
        if ($role === 'manager' && (! $monProfil || (int) $salarie['manager_id'] !== (int) $monProfil['id'])) {
            return ['ok' => false, 'message' => 'Vous ne pouvez refuser que les demandes de votre équipe.'];
        }
        if (in_array((int) $demande['statut_id'], [$this->statut('Accepté'), $this->statut('Refusé'), $this->statut('Annulé')], true)) {
            return ['ok' => false, 'message' => 'Cette demande est déjà clôturée.'];
        }

        $statut = $this->statut('Refusé');
        (new DemandeCongeModel())->update($demandeId, [
            'statut_id'       => $statut,
            'date_traitement' => date('Y-m-d H:i:s'),
            'traite_par'      => $utilisateurId,
            'commentaire_validation' => $commentaire,
        ]);
        $this->historiser($demandeId, $statut, $utilisateurId, $commentaire ?: 'Demande refusée.');

        if ($salarie) {
            $this->notifications->envoyer((int) $salarie['utilisateur_id'],
                'Votre demande de congé #' . $demandeId . ' est refusée.', 'conges/' . $demandeId);
        }
        $this->audit->log($utilisateurId, 'conge.refuser', 'demande_conge', $demandeId);

        return ['ok' => true, 'message' => 'Demande de congé refusée.'];
    }

    public function annuler(int $demandeId, int $utilisateurId): array
    {
        $demande = (new DemandeCongeModel())->find($demandeId);
        $salarie = (new SalarieModel())->where('utilisateur_id', $utilisateurId)->first();

        if (! $demande || ! $salarie || (int) $demande['salarie_id'] !== (int) $salarie['id']) {
            return ['ok' => false, 'message' => 'Demande introuvable.'];
        }
        if (in_array((int) $demande['statut_id'], [$this->statut('Refusé'), $this->statut('Annulé')], true)) {
            return ['ok' => false, 'message' => 'Cette demande est déjà clôturée.'];
        }

        $db = db_connect();
        $db->transBegin();
        try {
            if ((int) $demande['statut_id'] === $this->statut('Accepté')) {
                $type = (new TypeCongeModel())->find($demande['type_conge_id']);
                if ($type && $type['jours_max_par_an'] !== null) {
                    $solde = $this->assurerSolde((int) $demande['salarie_id'], (int) $demande['type_conge_id'], (int) date('Y', strtotime($demande['date_debut'])));
                    (new SoldeCongeModel())->update($solde['id'], [
                        'jours_pris' => max(0, (float) $solde['jours_pris'] - (float) $demande['nombre_jours']),
                    ]);
                }

                (new \App\Models\AbsenceModel())->where('salarie_id', (int) $demande['salarie_id'])
                    ->where('motif', 'Congé #' . $demandeId)
                    ->delete();

                $annee = (int) date('Y', strtotime($demande['date_debut']));
                $mois = (int) date('n', strtotime($demande['date_debut']));
                $variable = (new \App\Models\VariablePaieModel())
                    ->where('salarie_id', (int) $demande['salarie_id'])
                    ->where('annee', $annee)
                    ->where('mois', $mois)
                    ->where('libelle', 'Congé #' . $demandeId)
                    ->first();
                if ($variable) {
                    (new \App\Models\VariablePaieModel())->delete((int) $variable['id']);
                }
            }

            $statut = $this->statut('Annulé');
            (new DemandeCongeModel())->update($demandeId, ['statut_id' => $statut]);
            $this->historiser($demandeId, $statut, $utilisateurId, 'Annulation par le salarié.');
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Annulation congé : ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Erreur lors de l’annulation.'];
        }

        $this->audit->log($utilisateurId, 'conge.annuler', 'demande_conge', $demandeId);

        $salarie = (new SalarieModel())->find($demande['salarie_id']);
        if ($salarie) {
            $this->notifications->envoyer((int) $salarie['utilisateur_id'],
                'Votre demande de congé #' . $demandeId . ' a été annulée.', 'conges/' . $demandeId);
        }

        return ['ok' => true, 'message' => 'Demande annulée.'];
    }

    private function chevaucheDemande(int $salarieId, string $debut, string $fin): bool
    {
        $db = db_connect();
        $nb = $db->table('demande_conge')
            ->where('salarie_id', $salarieId)
            ->whereNotIn('statut_id', [$this->statut('Refusé'), $this->statut('Annulé')])
            ->groupStart()
                ->where('date_debut <=', $fin)
                ->where('date_fin >=', $debut)
            ->groupEnd()
            ->countAllResults();

        return $nb > 0;
    }

    private function chevaucheAbsence(int $salarieId, string $debut, string $fin): bool
    {
        $db = db_connect();
        $nb = $db->table('absence')
            ->where('salarie_id', $salarieId)
            ->where('date_debut <=', $fin)
            ->where('date_fin >=', $debut)
            ->countAllResults();

        return $nb > 0;
    }

    private function statut(string $libelle): int
    {
        $row = (new StatutCongeModel())->where('libelle', $libelle)->first();
        return (int) ($row['id'] ?? 0);
    }

    private function historiser(int $demandeId, int $statutId, ?int $utilisateurId, ?string $commentaire): void
    {
        (new HistoriqueStatutCongeModel())->insert([
            'demande_conge_id'  => $demandeId,
            'statut_id'         => $statutId,
            'utilisateur_id'    => $utilisateurId,
            'commentaire'       => $commentaire,
            'date_changement'   => date('Y-m-d H:i:s'),
        ]);
    }
}
