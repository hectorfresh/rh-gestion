<?php

namespace App\Services;

use App\Models\ContratModel;
use App\Models\DocumentModel;
use App\Models\HistoriqueCarriereModel;
use App\Models\OnboardingModel;
use App\Models\OnboardingTacheModel;
use App\Models\PosteModel;
use App\Models\SalarieModel;
use App\Models\TypeDocumentModel;

/**
 * Bloc 4 : contrats successifs, renouvellement et onboarding.
 */
class ContratService
{
    private AuditService $audit;
    private NotificationService $notifications;
    private BudgetService $budget;
    private OnboardingService $onboarding;

    public function __construct()
    {
        $this->audit          = new AuditService();
        $this->notifications  = new NotificationService();
        $this->budget         = new BudgetService();
        $this->onboarding     = new OnboardingService();
    }

    /** Contrat actuellement actif pour un salarié. */
    public function contratActif(int $salarieId): ?array
    {
        $db = db_connect();
        $row = $db->table('contrat')
            ->where('salarie_id', $salarieId)
            ->where('statut', 'Accepte')
            ->groupStart()
                ->where('date_fin IS NULL', null, false)
                ->orWhere('date_fin >=', date('Y-m-d'))
            ->groupEnd()
            ->orderBy('date_debut', 'DESC')
            ->get()->getRowArray();

        return $row ?: null;
    }

    /**
     * @return array{ok:bool,message:string,id?:int}
     */
    public function creer(int $salarieId, array $data, array $utilisateur, ?int $ancienContratId = null, bool $remplacerActif = false): array
    {
        $salarie = (new SalarieModel())->find($salarieId);
        if (! $salarie) {
            return ['ok' => false, 'message' => 'Salarié introuvable.'];
        }

        $ancien = null;
        if ($ancienContratId) {
            $ancien = (new ContratModel())->find($ancienContratId);
            if (! $ancien || (int) $ancien['salarie_id'] !== $salarieId) {
                return ['ok' => false, 'message' => 'Contrat à renouveler introuvable.'];
            }
        } elseif ($remplacerActif) {
            $ancien = $this->contratActif($salarieId);
        }

        if ($ancien === null && ! $remplacerActif && ! $ancienContratId) {
            $actif = $this->contratActif($salarieId);
            if ($actif) {
                return ['ok' => false, 'message' => 'Ce salarié possède déjà un contrat actif. Clôturez-le ou utilisez le renouvellement.'];
            }
        }

        $posteId = (int) ($data['poste_id'] ?? $salarie['poste_id']);
        $poste   = (new PosteModel())->find($posteId);
        if (! $poste || (int) $poste['departement_id'] !== (int) ($data['departement_id'] ?? $salarie['departement_id'])) {
            return ['ok' => false, 'message' => 'Le poste ne correspond pas au département choisi.'];
        }

        $normalise = $this->normaliser($data, $poste);
        if (! $normalise['ok']) {
            return ['ok' => false, 'message' => $normalise['message']];
        }
        $v = $normalise['valeurs'];

        $db = db_connect();
        $db->transBegin();
        try {
            if ($ancien) {
                $dateFinAncien = $v['date_debut'];
                if ($dateFinAncien < $ancien['date_debut']) {
                    $dateFinAncien = $ancien['date_debut'];
                } else {
                    $precedent = date('Y-m-d', strtotime($v['date_debut'] . ' -1 day'));
                    if ($precedent >= $ancien['date_debut']) {
                        $dateFinAncien = $precedent;
                    }
                }
                $ok = (new ContratModel())->update((int) $ancien['id'], [
                    'statut'   => 'Remplacé',
                    'date_fin' => $dateFinAncien,
                ]);
                if (! $ok) {
                    throw new \RuntimeException('Impossible de clôturer l’ancien contrat.');
                }
            }

            $contratId = (int) (new ContratModel())->insert([
                'salarie_id'        => $salarieId,
                'candidature_id'    => $ancien ? null : $salarie['candidature_id'],
                'type_contrat'      => $v['type_contrat'],
                'date_debut'        => $v['date_debut'],
                'date_fin'          => $v['date_fin'],
                'salaire_mensuel'   => $v['salaire'],
                'regime_travail_id' => $v['regime_travail_id'],
                'heure_debut'       => $v['heure_debut'],
                'heure_fin'         => $v['heure_fin'],
                'periode_essai_mois'=> $v['periode_essai'],
                'statut'            => 'Accepte',
                'date_creation'     => date('Y-m-d H:i:s'),
                'date_reponse'      => date('Y-m-d H:i:s'),
            ]);
            if ($contratId <= 0) {
                throw new \RuntimeException('Insert contrat failed: ' . json_encode(db_connect()->error()));
            }

            (new SalarieModel())->update($salarieId, [
                'departement_id'    => (int) ($data['departement_id'] ?? $salarie['departement_id']),
                'poste_id'          => $posteId,
                'regime_travail_id' => $v['regime_travail_id'],
                'actif'             => 1,
            ]);

            (new \App\Models\AffectationModel())->insert([
                'salarie_id'     => $salarieId,
                'departement_id' => (int) ($data['departement_id'] ?? $salarie['departement_id']),
                'poste_id'       => $posteId,
                'date_debut'     => $v['date_debut'],
                'motif'          => $ancien ? 'Renouvellement / changement de contrat' : 'Contrat manuel',
            ]);

            (new HistoriqueCarriereModel())->insert([
                'salarie_id'             => $salarieId,
                'type_evenement'         => $ancien ? 'affectation' : 'autre',
                'date_evenement'         => $v['date_debut'],
                'ancien_poste_id'        => $ancien ? (int) $salarie['poste_id'] : null,
                'nouveau_poste_id'       => $posteId,
                'ancien_departement_id'  => $ancien ? (int) $salarie['departement_id'] : null,
                'nouveau_departement_id' => (int) ($data['departement_id'] ?? $salarie['departement_id']),
                'ancien_salaire'         => $ancien ? (float) $ancien['salaire_mensuel'] : null,
                'nouveau_salaire'        => $v['salaire'],
                'commentaire'            => $ancien ? 'Renouvellement / changement de contrat #' . $contratId : 'Création manuelle du contrat #' . $contratId,
                'created_by'             => (int) $utilisateur['id'],
            ]);

            $this->onboarding->initialiser($salarieId, $contratId, (int) ($salarie['manager_id'] ?? 0) ?: null);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Création contrat : ' . $e->getMessage());

            return ['ok' => false, 'message' => 'Impossible de créer le contrat.'];
        }

        if ($ancien && (float) $ancien['salaire_mensuel'] !== $v['salaire']) {
            $annee = (int) date('Y', strtotime($v['date_debut']));
            $this->budget->resume((int) ($data['departement_id'] ?? $salarie['departement_id']), $annee);
            $this->notifications->envoyerAuxRoles(['rh', 'dg'],
                'Budget recalculé suite au changement de salaire du salarié #' . $salarieId . '.',
                'contrats/' . $contratId);
        }

        $this->audit->log((int) $utilisateur['id'], $ancien ? 'contrat.renouveler' : 'contrat.creer', 'contrat', $contratId, [
            'salarie_id' => $salarieId,
            'ancien_contrat_id' => $ancien['id'] ?? null,
        ]);

        $this->genererPdf($contratId, true, (int) $utilisateur['id']);

        return ['ok' => true, 'message' => $ancien ? 'Contrat renouvelé avec succès.' : 'Contrat créé avec succès.', 'id' => $contratId];
    }

    public function renouveler(int $ancienContratId, array $data, array $utilisateur): array
    {
        $ancien = (new ContratModel())->find($ancienContratId);
        if (! $ancien) {
            return ['ok' => false, 'message' => 'Contrat introuvable.'];
        }

        return $this->creer((int) $ancien['salarie_id'], $data, $utilisateur, $ancienContratId, true);
    }

    public function echeances(int $jours = 30): array
    {
        $db = db_connect();
        $fin = date('Y-m-d', strtotime('+' . $jours . ' days'));

        return $db->table('contrat c')
            ->select('c.*, s.matricule, u.nom, u.prenom, p.libelle AS poste, d.libelle AS departement')
            ->join('salarie s', 's.id = c.salarie_id')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->join('poste p', 'p.id = s.poste_id')
            ->join('departement d', 'd.id = s.departement_id')
            ->where('c.statut', 'Accepte')
            ->where('c.date_fin IS NOT NULL', null, false)
            ->where('c.date_fin >=', date('Y-m-d'))
            ->where('c.date_fin <=', $fin)
            ->orderBy('c.date_fin', 'ASC')
            ->get()->getResultArray();
    }

    public function charger(int $id): ?array
    {
        $db = db_connect();
        $row = $db->table('contrat c')
            ->select('c.*, s.matricule, s.date_embauche, s.utilisateur_id, s.manager_id, u.nom, u.prenom, u.email,
                      p.libelle AS poste, p.salaire_base, p.salaire_max, d.libelle AS departement,
                      r.libelle AS regime, r.heures_hebdomadaires')
            ->join('salarie s', 's.id = c.salarie_id', 'left')
            ->join('utilisateur u', 'u.id = s.utilisateur_id', 'left')
            ->join('poste p', 'p.id = s.poste_id', 'left')
            ->join('departement d', 'd.id = s.departement_id', 'left')
            ->join('regime_travail r', 'r.id = c.regime_travail_id', 'left')
            ->where('c.id', $id)
            ->get()->getRowArray();

        return $row ?: null;
    }

    public function genererPdf(int $contratId, bool $sauvegarder = false, ?int $uploadedBy = null): ?string
    {
        if (! extension_loaded('dom') || ! class_exists('Dompdf\\Dompdf')) {
            return null;
        }

        $contrat = $this->charger($contratId);
        if (! $contrat) {
            return null;
        }

        helper('talentflow');
        $html = view('contrats/pdf', ['contrat' => $contrat]);
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdf = $dompdf->output();

        if ($sauvegarder) {
            $dossier = WRITEPATH . 'uploads/documents/';
            if (! is_dir($dossier)) {
                mkdir($dossier, 0775, true);
            }
            $nomFichier = 'contrat_' . $contratId . '.pdf';
            file_put_contents($dossier . $nomFichier, $pdf);

            $type = (new TypeDocumentModel())->where('libelle', 'Contrat de travail')->first();
            if ($type) {
                $existant = (new DocumentModel())
                    ->where('salarie_id', (int) $contrat['salarie_id'])
                    ->where('type_document_id', (int) $type['id'])
                    ->where('nom_fichier', $nomFichier)
                    ->first();
                if (! $existant) {
                    (new DocumentModel())->insert([
                        'salarie_id'       => (int) $contrat['salarie_id'],
                        'type_document_id' => (int) $type['id'],
                        'nom_original'     => 'contrat_' . $contratId . '.pdf',
                        'nom_fichier'      => $nomFichier,
                        'chemin'           => 'uploads/documents/' . $nomFichier,
                        'mime'             => 'application/pdf',
                        'taille'           => strlen($pdf),
                        'date_emission'    => $contrat['date_debut'],
                        'uploaded_by'      => $uploadedBy,
                    ]);
                }
            }
        }

        return $pdf;
    }

    public function frise(int $salarieId): array
    {
        return (new ContratModel())
            ->where('salarie_id', $salarieId)
            ->orderBy('date_debut', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    private function normaliser(array $data, array $poste): array
    {
        helper('talentflow');
        $base  = (float) $poste['salaire_base'];
        $max   = (float) $poste['salaire_max'];
        if ($max < $base) {
            $max = $base;
        }

        $salaire = isset($data['salaire']) && $data['salaire'] !== ''
            ? (float) $data['salaire']
            : $base;
        if ($salaire <= 0 || $salaire < $base || $salaire > $max) {
            return ['ok' => false, 'message' => 'Le salaire doit être compris dans la fourchette du poste (' . tf_money($base) . ' – ' . tf_money($max) . ').'];
        }

        $type = trim((string) ($data['type_contrat'] ?? 'CDI'));
        if ($type === '') {
            $type = 'CDI';
        }
        if (! in_array($type, ['Essai', 'CDD', 'CDI', 'Stage', 'Alternance'], true)) {
            return ['ok' => false, 'message' => 'Type de contrat invalide.'];
        }

        $debut = trim((string) ($data['date_debut'] ?? ''));
        if ($debut === '') {
            $debut = date('Y-m-d');
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $debut) || ! checkdate((int) substr($debut, 5, 2), (int) substr($debut, 8, 2), (int) substr($debut, 0, 4))) {
            return ['ok' => false, 'message' => 'Date de début invalide.'];
        }

        $fin = trim((string) ($data['date_fin'] ?? ''));
        if ($fin !== '' && (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin) || ! checkdate((int) substr($fin, 5, 2), (int) substr($fin, 8, 2), (int) substr($fin, 0, 4)))) {
            return ['ok' => false, 'message' => 'Date de fin invalide.'];
        }
        if ($fin !== '' && $fin < $debut) {
            return ['ok' => false, 'message' => 'La date de fin doit être postérieure à la date de début.'];
        }

        $heureDebut = trim((string) ($data['heure_debut'] ?? '08:00'));
        $heureFin   = trim((string) ($data['heure_fin'] ?? '17:00'));
        if ($heureDebut === '') {
            $heureDebut = '08:00';
        }
        if ($heureFin === '') {
            $heureFin = '17:00';
        }
        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $heureDebut) || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $heureFin)) {
            return ['ok' => false, 'message' => 'Horaires invalides.'];
        }

        $essai = isset($data['periode_essai']) && $data['periode_essai'] !== ''
            ? (int) $data['periode_essai']
            : 0;
        if ($essai < 0 || $essai > 12) {
            return ['ok' => false, 'message' => 'Période d’essai invalide.'];
        }

        $regimeId = (int) ($data['regime_travail_id'] ?? 1);
        if ($regimeId <= 0) {
            $regimeId = 1;
        }

        return ['ok' => true, 'valeurs' => [
            'salaire'          => $salaire,
            'type_contrat'     => $type,
            'date_debut'       => $debut,
            'date_fin'         => $fin !== '' ? $fin : null,
            'heure_debut'      => $heureDebut,
            'heure_fin'        => $heureFin,
            'periode_essai'    => $essai,
            'regime_travail_id'=> $regimeId,
        ]];
    }
}
