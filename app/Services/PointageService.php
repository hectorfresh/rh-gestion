<?php

namespace App\Services;

use App\Models\AbsenceModel;
use App\Models\AnomaliePointageModel;
use App\Models\ContratModel;
use App\Models\DemandeCongeModel;
use App\Models\JourFerieModel;
use App\Models\PlanningSalarieModel;
use App\Models\PointageModel;
use App\Models\SalarieModel;
use App\Models\StatutCongeModel;
use App\Models\VariablePaieModel;

/**
 * Bloc 6 : temps de travail et pointage.
 */
class PointageService
{
    private AuditService $audit;

    public function __construct()
    {
        $this->audit = new AuditService();
    }

    public function horairesPrevus(int $salarieId, string $jour): array
    {
        $planning = (new PlanningSalarieModel())
            ->where('salarie_id', $salarieId)
            ->where('jour', $jour)
            ->first();

        if ($planning) {
            return [
                'debut' => $planning['heure_debut'] ?: '08:00:00',
                'fin'   => $planning['heure_fin'] ?: '17:00:00',
                'type'  => $planning['type'],
            ];
        }

        $contrat = (new ContratModel())
            ->where('salarie_id', $salarieId)
            ->where('statut', 'Accepte')
            ->orderBy('date_debut', 'DESC')
            ->first();

        return [
            'debut' => $contrat['heure_debut'] ?? '08:00:00',
            'fin'   => $contrat['heure_fin'] ?? '17:00:00',
            'type'  => 'travail',
        ];
    }

    public function estJourFerie(string $jour): bool
    {
        return (bool) (new JourFerieModel())->where('jour', $jour)->first();
    }

    public function estEnCongeValide(int $salarieId, string $jour): bool
    {
        $db = db_connect();
        $statut = (new StatutCongeModel())->where('libelle', 'Accepté')->first();
        if (! $statut) {
            return false;
        }

        $row = $db->table('demande_conge')
            ->where('salarie_id', $salarieId)
            ->where('statut_id', (int) $statut['id'])
            ->where('date_debut <=', $jour)
            ->where('date_fin >=', $jour)
            ->countAllResults();

        return $row > 0;
    }

    public function estJourTravaille(int $salarieId, string $jour): bool
    {
        if ($this->estJourFerie($jour) || $this->estEnCongeValide($salarieId, $jour)) {
            return false;
        }
        if ((int) date('N', strtotime($jour)) > 5) {
            return false;
        }

        $prevu = $this->horairesPrevus($salarieId, $jour);
        return $prevu['type'] === 'travail';
    }

    public function pointerEntree(int $salarieId, ?string $moment = null, int $utilisateurId = 0): array
    {
        $moment = $moment ?: date('Y-m-d H:i:s');
        $jour   = substr($moment, 0, 10);
        $prevu  = $this->horairesPrevus($salarieId, $jour);

        if (! $this->estJourTravaille($salarieId, $jour)) {
            $this->anomalieSansPointage($salarieId, $jour, 'jour_non_travaille', 'Pointage un jour non travaillé.');
            return ['ok' => false, 'message' => 'Ce jour n’est pas un jour travaillé.'];
        }

        $model = new PointageModel();
        $pointage = $model->where('salarie_id', $salarieId)->where('jour', $jour)->first();

        if ($pointage && $pointage['heure_entree']) {
            $this->anomalie((int) $pointage['id'], 'double_entree', 'Double pointage d’entrée.');
            return ['ok' => false, 'message' => 'L’entrée est déjà pointée aujourd’hui.'];
        }

        $limite = strtotime($jour . ' ' . $prevu['debut']) + 10 * 60;
        $retard = strtotime($moment) > $limite;

        if ($pointage) {
            $id = (int) $pointage['id'];
            $model->update($id, [
                'heure_entree' => $moment,
                'statut'       => $retard ? 'retard' : 'incomplet',
            ]);
        } else {
            $id = (int) $model->insert([
                'salarie_id'   => $salarieId,
                'jour'         => $jour,
                'heure_entree' => $moment,
                'statut'       => $retard ? 'retard' : 'incomplet',
            ]);
        }

        if ($retard) {
            $this->anomalie($id, 'retard',
                'Entrée à ' . date('H:i', strtotime($moment)) . ', prévue à ' . substr($prevu['debut'], 0, 5) . '.');
        }

        $this->audit->log($utilisateurId ?: null, 'pointage.entree', 'pointage', $id, ['salarie_id' => $salarieId]);

        return ['ok' => true, 'message' => $retard ? 'Entrée pointée (retard signalé).' : 'Entrée pointée.', 'id' => $id];
    }

    public function pointerSortie(int $salarieId, ?string $moment = null, int $utilisateurId = 0): array
    {
        $moment = $moment ?: date('Y-m-d H:i:s');
        $jour   = substr($moment, 0, 10);
        $prevu  = $this->horairesPrevus($salarieId, $jour);

        $model = new PointageModel();
        $pointage = $model->where('salarie_id', $salarieId)->where('jour', $jour)->first();

        if (! $pointage || ! $pointage['heure_entree']) {
            $this->anomalieSansPointage($salarieId, $jour, 'entree_manquante', 'Sortie pointée sans entrée.');
            return ['ok' => false, 'message' => 'Pointez d’abord l’entrée.'];
        }
        if ($pointage['heure_sortie']) {
            $this->anomalie((int) $pointage['id'], 'double_entree', 'Double pointage de sortie.');
            return ['ok' => false, 'message' => 'La sortie est déjà pointée.'];
        }

        $minutes = max(0, (int) round((strtotime($moment) - strtotime($pointage['heure_entree'])) / 60));
        $finPrevue = strtotime($jour . ' ' . $prevu['fin']);
        $sup = max(0, (int) round((strtotime($moment) - $finPrevue) / 60));
        $anticipe = strtotime($moment) < $finPrevue - 10 * 60;

        $statut = 'present';
        if ($minutes < 60 || $minutes > 14 * 60) {
            $this->anomalie((int) $pointage['id'], 'duree_incoherente', 'Durée travaillée incohérente : ' . round($minutes / 60, 2) . ' h.');
        }

        $model->update((int) $pointage['id'], [
            'heure_sortie'        => $moment,
            'minutes_travaillees' => min(65535, $minutes),
            'minutes_sup'         => min(65535, $sup),
            'statut'              => $statut,
        ]);

        if ($anticipe) {
            $this->anomalie((int) $pointage['id'], 'sortie_anticipee',
                'Sortie à ' . date('H:i', strtotime($moment)) . ', prévue à ' . substr($prevu['fin'], 0, 5) . '.');
        }
        if ($sup > 30) {
            $this->anomalie((int) $pointage['id'], 'heures_sup', $sup . ' minute(s) supplémentaire(s).');
        }

        $this->audit->log($utilisateurId ?: null, 'pointage.sortie', 'pointage', (int) $pointage['id'], ['salarie_id' => $salarieId]);

        return ['ok' => true, 'message' => 'Sortie pointée : ' . round($minutes / 60, 2) . ' h travaillées.', 'minutes' => $minutes];
    }

    public function anomalie(int $pointageId, string $type, string $description): void
    {
        $existe = (new AnomaliePointageModel())
            ->where('pointage_id', $pointageId)
            ->where('type', $type)
            ->first();

        if ($existe) {
            return;
        }

        $pointage = (new PointageModel())->find($pointageId);

        (new AnomaliePointageModel())->insert([
            'salarie_id'  => (int) ($pointage['salarie_id'] ?? 0),
            'date'        => $pointage['jour'] ?? date('Y-m-d'),
            'pointage_id' => $pointageId,
            'type'        => $type,
            'description' => mb_substr($description, 0, 255),
            'resolue'     => 0,
        ]);
    }

    public function anomalieSansPointage(int $salarieId, string $jour, string $type, string $description): void
    {
        $existe = (new AnomaliePointageModel())
            ->where('salarie_id', $salarieId)
            ->where('date', $jour)
            ->where('type', $type)
            ->first();

        if ($existe) {
            return;
        }

        (new AnomaliePointageModel())->insert([
            'salarie_id'  => $salarieId,
            'date'        => $jour,
            'pointage_id' => null,
            'type'        => $type,
            'description' => mb_substr($description, 0, 255),
            'resolue'     => 0,
        ]);
    }

    public function marquerAbsents(string $jour): int
    {
        if ((int) date('N', strtotime($jour)) > 5 || $this->estJourFerie($jour)) {
            return 0;
        }

        $db = db_connect();
        $salaries = $db->table('salarie s')
            ->select('s.id')
            ->where('s.actif', 1)
            ->where('s.date_embauche <=', $jour)
            ->groupStart()
                ->where('s.date_sortie', null)
                ->orWhere('s.date_sortie >=', $jour)
            ->groupEnd()
            ->get()
            ->getResultArray();

        $compteur = 0;
        foreach ($salaries as $salarie) {
            $salarieId = (int) $salarie['id'];
            if ($this->estEnCongeValide($salarieId, $jour)) {
                continue;
            }
            $existe = (new PointageModel())->where('salarie_id', $salarieId)->where('jour', $jour)->first();
            if ($existe) {
                continue;
            }
            (new AbsenceModel())->insert([
                'salarie_id' => $salarieId,
                'date_debut' => $jour,
                'date_fin'   => $jour,
                'type'       => 'autre',
                'motif'      => 'Absence à justifier',
            ]);
            $this->anomalieSansPointage($salarieId, $jour, 'entree_manquante', 'Absence totale sans pointage.');
            $compteur++;
        }

        return $compteur;
    }

    public function detecterAbsenceTotale(string $jour): int
    {
        return $this->marquerAbsents($jour);
    }

    public function feuilleMois(int $salarieId, string $mois): array
    {
        [$annee, $moisNum] = $this->decouperPeriode($mois);
        $debut = sprintf('%04d-%02d-01', $annee, $moisNum);
        $fin = date('Y-m-t', strtotime($debut));

        $pointages = (new PointageModel())
            ->where('salarie_id', $salarieId)
            ->where('jour >=', $debut)
            ->where('jour <=', $fin)
            ->orderBy('jour', 'ASC')
            ->findAll();

        $totaux = ['jours' => count($pointages), 'minutes' => 0, 'sup' => 0, 'retards' => 0];
        foreach ($pointages as $pointage) {
            $totaux['minutes'] += (int) $pointage['minutes_travaillees'];
            $totaux['sup'] += (int) $pointage['minutes_sup'];
            if ($pointage['statut'] === 'retard') {
                $totaux['retards']++;
            }
        }

        return ['pointages' => $pointages, 'totaux' => $totaux, 'mois' => $mois];
    }

    public function recapEquipe(int $managerId, string $mois): array
    {
        [$annee, $moisNum] = $this->decouperPeriode($mois);
        $debut = sprintf('%04d-%02d-01', $annee, $moisNum);
        $fin = date('Y-m-t', strtotime($debut));

        $db = db_connect();
        $salaries = $db->table('salarie s')
            ->select('s.id, s.matricule, u.nom, u.prenom')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->where('s.actif', 1)
            ->groupStart()
                ->where('s.manager_id', $managerId)
                ->orWhere('s.id', $managerId)
            ->groupEnd()
            ->orderBy('u.nom')
            ->get()->getResultArray();

        $lignes = [];
        foreach ($salaries as $salarie) {
            $feuille = $this->feuilleMois((int) $salarie['id'], $mois);
            $lignes[] = [
                'salarie' => $salarie,
                'totaux'  => $feuille['totaux'],
            ];
        }

        return $lignes;
    }

    public function consoliderMois(int $salarieId, string $mois, int $utilisateurId): array
    {
        [$annee, $moisNum] = $this->decouperPeriode($mois);
        $feuille = $this->feuilleMois($salarieId, $mois);
        $totaux = $feuille['totaux'];

        $congesPayes = 0;
        $sansSolde = 0;
        $db = db_connect();
        $conges = $db->table('demande_conge dc')
            ->select('dc.nombre_jours, tc.libelle')
            ->join('type_conge tc', 'tc.id = dc.type_conge_id')
            ->join('statut_conge sc', 'sc.id = dc.statut_id')
            ->where('dc.salarie_id', $salarieId)
            ->where('sc.libelle', 'Accepté')
            ->where('dc.date_debut <=', $fin)
            ->where('dc.date_fin >=', $debut)
            ->get()->getResultArray();
        foreach ($conges as $conge) {
            if (str_contains(mb_strtolower((string) $conge['libelle']), 'payé')) {
                $congesPayes += (float) $conge['nombre_jours'];
            } else {
                $sansSolde += (float) $conge['nombre_jours'];
            }
        }

        $absences = (int) $db->table('absence')
            ->where('salarie_id', $salarieId)
            ->where('date_debut >=', sprintf('%04d-%02d-01', $annee, $moisNum))
            ->where('date_debut <=', date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $annee, $moisNum))))
            ->countAllResults();

        $indicateurs = [
            'Heures normales' => ['montant' => round(($totaux['minutes'] - $totaux['sup']) / 60, 2), 'type' => 'autre'],
            'Heures supplémentaires' => ['montant' => round($totaux['sup'] / 60, 2), 'type' => 'heure_sup'],
            'Retards (heures)' => ['montant' => round(-$totaux['retards'] * 10 / 60, 2), 'type' => 'retenue'],
            'Jours de congé payé' => ['montant' => round($congesPayes, 2), 'type' => 'autre'],
            'Jours sans solde' => ['montant' => round(-$sansSolde, 2), 'type' => 'retenue'],
            'Absences' => ['montant' => round(-$absences, 2), 'type' => 'retenue'],
        ];

        foreach ($indicateurs as $libelle => $info) {
            $row = (new VariablePaieModel())
                ->where('salarie_id', $salarieId)
                ->where('annee', $annee)
                ->where('mois', $moisNum)
                ->where('libelle', $libelle)
                ->first();

            if ((float) $info['montant'] == 0.0) {
                if ($row) {
                    (new VariablePaieModel())->delete((int) $row['id']);
                }
                continue;
            }

            $data = [
                'salarie_id' => $salarieId,
                'annee'      => $annee,
                'mois'       => $moisNum,
                'libelle'    => $libelle,
                'montant'    => $info['montant'],
                'type'       => $info['type'],
                'created_by' => $utilisateurId,
            ];

            if ($row) {
                (new VariablePaieModel())->update((int) $row['id'], $data);
            } else {
                (new VariablePaieModel())->insert($data);
            }
        }

        $this->audit->log($utilisateurId, 'pointage.consolider', 'salarie', $salarieId, ['mois' => $mois]);

        return ['ok' => true, 'message' => 'Mois consolidé.'];
    }

    public function saisirManuel(int $salarieId, string $jour, ?string $entree, ?string $sortie, int $utilisateurId): array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $jour)) {
            return ['ok' => false, 'message' => 'Jour invalide.'];
        }

        $entree = $entree ? trim($entree) : null;
        $sortie = $sortie ? trim($sortie) : null;
        if (! $entree && ! $sortie) {
            return ['ok' => false, 'message' => 'Saisissez au moins une heure.'];
        }

        $prevu = $this->horairesPrevus($salarieId, $jour);
        $model = new PointageModel();
        $pointage = $model->where('salarie_id', $salarieId)->where('jour', $jour)->first();

        $heureEntree = $entree ? $jour . ' ' . (strlen($entree) === 5 ? $entree . ':00' : $entree) : ($pointage['heure_entree'] ?? null);
        $heureSortie = $sortie ? $jour . ' ' . (strlen($sortie) === 5 ? $sortie . ':00' : $sortie) : ($pointage['heure_sortie'] ?? null);

        if ($heureSortie && ! $heureEntree) {
            return ['ok' => false, 'message' => 'Une sortie nécessite une entrée.'];
        }

        $statut = 'present';
        $minutes = 0;
        $sup = 0;
        $retard = false;
        $anticipe = false;

        if ($heureEntree) {
            $limite = strtotime($jour . ' ' . $prevu['debut']) + 10 * 60;
            $retard = strtotime($heureEntree) > $limite;
            if ($retard) {
                $statut = 'retard';
            }
        }

        if ($heureEntree && $heureSortie) {
            $minutes = max(0, (int) round((strtotime($heureSortie) - strtotime($heureEntree)) / 60));
            $finPrevue = strtotime($jour . ' ' . $prevu['fin']);
            $sup = max(0, (int) round((strtotime($heureSortie) - $finPrevue) / 60));
            $anticipe = strtotime($heureSortie) < $finPrevue - 10 * 60;

            if ($minutes < 60 || $minutes > 14 * 60) {
                if ($pointage) {
                    $this->anomalie((int) $pointage['id'], 'duree_incoherente', 'Durée travaillée incohérente : ' . round($minutes / 60, 2) . ' h.');
                }
            }
        } elseif ($heureEntree && ! $heureSortie) {
            $statut = $retard ? 'retard' : 'incomplet';
        }

        $data = [
            'heure_entree' => $heureEntree,
            'heure_sortie' => $heureSortie,
            'minutes_travaillees' => min(65535, $minutes),
            'minutes_sup' => min(65535, $sup),
            'statut' => $statut,
            'commentaire' => 'Saisie manuelle RH/DG.',
        ];

        if ($pointage) {
            $model->update((int) $pointage['id'], $data);
            $id = (int) $pointage['id'];
        } else {
            $id = (int) $model->insert(array_merge($data, [
                'salarie_id' => $salarieId,
                'jour' => $jour,
            ]));
        }

        if ($retard && $id) {
            $this->anomalie($id, 'retard', 'Entrée à ' . date('H:i', strtotime($heureEntree)) . ', prévue à ' . substr($prevu['debut'], 0, 5) . '.');
        }
        if ($anticipe && $id) {
            $this->anomalie($id, 'sortie_anticipee', 'Sortie à ' . date('H:i', strtotime($heureSortie)) . ', prévue à ' . substr($prevu['fin'], 0, 5) . '.');
        }
        if ($sup > 30 && $id) {
            $this->anomalie($id, 'heures_sup', $sup . ' minute(s) supplémentaire(s).');
        }

        $this->audit->log($utilisateurId, 'pointage.saisie_manuelle', 'pointage', $id, ['salarie_id' => $salarieId, 'jour' => $jour]);

        return ['ok' => true, 'message' => 'Pointage enregistré manuellement.', 'id' => $id];
    }

    public function compterAnomaliesNonResolues(?int $salarieId = null, string $role = '', ?int $managerId = null): int
    {
        $db = db_connect();
        $builder = $db->table('anomalie_pointage')->where('resolue', 0);

        if ($salarieId) {
            $builder->where('salarie_id', $salarieId);
        } elseif ($role === 'manager' && $managerId) {
            $builder->join('salarie s', 's.id = anomalie_pointage.salarie_id')
                ->where('s.manager_id', $managerId);
        }

        return (int) $builder->countAllResults();
    }

    /** @return array{0:int,1:int} */
    public function decouperPeriode(string $mois): array
    {
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $mois, $m)) {
            return [(int) $m[1], max(1, min(12, (int) $m[2]))];
        }

        return [(int) date('Y'), (int) date('n')];
    }
}
