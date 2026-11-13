<?php

namespace App\Services;

use App\Models\AffectationModel;
use App\Models\DocumentModel;
use App\Models\HistoriqueCarriereModel;
use App\Models\SalarieModel;

class SalarieService
{
    private AuditService $audit;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->audit = new AuditService();
        $this->notifications = new NotificationService();
    }

    public function muter(int $salarieId, array $data, array $utilisateur): array
    {
        $salarie = (new SalarieModel())->find($salarieId);
        if (! $salarie) {
            return ['ok' => false, 'message' => 'Salarié introuvable.'];
        }

        $departementId = (int) ($data['departement_id'] ?? 0);
        $posteId = (int) ($data['poste_id'] ?? 0);
        $dateDebut = trim((string) ($data['date_debut'] ?? date('Y-m-d')));
        $motif = trim((string) ($data['motif'] ?? ''));

        $poste = (new \App\Models\PosteModel())->find($posteId);
        if (! $poste || (int) $poste['departement_id'] !== $departementId) {
            return ['ok' => false, 'message' => 'Le poste ne correspond pas au département choisi.'];
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut)) {
            return ['ok' => false, 'message' => 'Date d’effet invalide.'];
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $ancienne = $db->table('affectation')
                ->where('salarie_id', $salarieId)
                ->where('date_fin IS NULL', null, false)
                ->orderBy('date_debut', 'DESC')
                ->get()->getRowArray();

            if ($ancienne) {
                $dateFinAncienne = date('Y-m-d', strtotime($dateDebut . ' -1 day'));
                if ($dateFinAncienne < $ancienne['date_debut']) {
                    $dateFinAncienne = $ancienne['date_debut'];
                }
                (new AffectationModel())->update((int) $ancienne['id'], ['date_fin' => $dateFinAncienne]);
            }

            (new SalarieModel())->update($salarieId, [
                'departement_id' => $departementId,
                'poste_id'       => $posteId,
            ]);

            (new AffectationModel())->insert([
                'salarie_id'     => $salarieId,
                'departement_id' => $departementId,
                'poste_id'       => $posteId,
                'date_debut'     => $dateDebut,
                'motif'          => $motif ?: 'Changement d’affectation',
            ]);

            (new HistoriqueCarriereModel())->insert([
                'salarie_id'             => $salarieId,
                'type_evenement'         => 'affectation',
                'date_evenement'         => $dateDebut,
                'ancien_poste_id'        => $ancienne ? (int) $salarie['poste_id'] : null,
                'nouveau_poste_id'       => $posteId,
                'ancien_departement_id'  => $ancienne ? (int) $salarie['departement_id'] : null,
                'nouveau_departement_id' => $departementId,
                'ancien_salaire'         => null,
                'nouveau_salaire'        => null,
                'commentaire'            => $motif ?: 'Mutation / changement d’affectation',
                'created_by'             => (int) $utilisateur['id'],
            ]);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Mutation salarié : ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Erreur lors de la mutation.'];
        }

        $this->audit->log((int) $utilisateur['id'], 'salarie.muter', 'salarie', $salarieId, [
            'departement_id' => $departementId, 'poste_id' => $posteId,
        ]);

        return ['ok' => true, 'message' => 'Affectation enregistrée.'];
    }

    public function statutDocument(array $document): string
    {
        if (empty($document['date_expiration'])) {
            return 'Valide';
        }
        $expiration = strtotime($document['date_expiration']);
        $today = strtotime(date('Y-m-d'));
        $limite = strtotime('+30 days');

        if ($expiration < $today) {
            return 'Expiré';
        }
        if ($expiration <= $limite) {
            return 'Expire bientôt';
        }

        return 'Valide';
    }

    public function documentsExpirants(): array
    {
        $db = db_connect();
        $limite = date('Y-m-d', strtotime('+30 days'));

        return $db->table('document doc')
            ->select('doc.*, td.libelle AS type_document, s.id AS salarie_id, u.nom, u.prenom')
            ->join('type_document td', 'td.id = doc.type_document_id')
            ->join('salarie s', 's.id = doc.salarie_id')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->where('doc.date_expiration IS NOT NULL', null, false)
            ->where('doc.date_expiration <=', $limite)
            ->where('doc.date_expiration >=', date('Y-m-d'))
            ->orderBy('doc.date_expiration', 'ASC')
            ->get()->getResultArray();
    }

    public function notifierDocumentsExpirants(): void
    {
        $documents = $this->documentsExpirants();
        if (empty($documents)) {
            return;
        }

        $this->notifications->envoyerAuxRoles(['rh', 'dg'],
            count($documents) . ' document(s) expirent sous 30 jours.',
            'dossiers');
    }

    public function masseSalarialeParDepartement(int $annee): array
    {
        $db = db_connect();
        $budget = new BudgetService();

        $departements = $db->table('departement')->where('actif', 1)->orderBy('libelle')->get()->getResultArray();
        $lignes = [];
        foreach ($departements as $departement) {
            $resume = $budget->resume((int) $departement['id'], $annee);
            $effectifs = (int) $db->table('salarie')->where('departement_id', (int) $departement['id'])->where('actif', 1)->countAllResults();
            $lignes[] = [
                'departement' => $departement,
                'effectifs'   => $effectifs,
                'budget'      => $resume['budget'],
                'masse'       => $resume['masse'],
                'engagements' => $resume['engagements'],
                'disponible'  => $resume['disponible'],
                'alerte'      => $resume['disponible'] < 0,
            ];
        }

        return $lignes;
    }
}
