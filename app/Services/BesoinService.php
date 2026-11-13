<?php

namespace App\Services;

use App\Models\BesoinModel;
use App\Models\PosteModel;
use App\Models\SalarieModel;
use App\Models\StatutBesoinModel;

/**
 * Bloc 1 — expression et validation du besoin de recrutement.
 *
 * Règles métier centralisées ici :
 *  - création par un manager (son département uniquement) ou par le DG ;
 *  - salaire estimé par défaut = salaire_base du poste, borné à la fourchette ;
 *  - soumission => statut « En attente RH » + étape finance automatique ;
 *  - RH puis DG décident, le DG tranche en dernier ;
 *  - besoin créé par le DG = acceptation d'office.
 */
class BesoinService
{
    private WorkflowService $workflow;
    private AuditService $audit;

    public function __construct()
    {
        $this->workflow = new WorkflowService();
        $this->audit    = new AuditService();
    }

    /**
     * Crée et soumet un besoin.
     *
     * @return array{ok:bool,message:string,id?:int}
     */
    public function creer(array $data, array $utilisateur): array
    {
        $role = $this->roleLibelle((int) ($utilisateur['role_id'] ?? 0));
        if (! in_array($role, ['manager', 'dg'], true)) {
            return ['ok' => false, 'message' => 'Seuls un manager ou le DG peuvent créer un besoin.'];
        }

        $poste = (new PosteModel())->find((int) ($data['poste_id'] ?? 0));
        if (! $poste || (int) $poste['actif'] !== 1) {
            return ['ok' => false, 'message' => 'Poste introuvable ou inactif.'];
        }

        $departementId = (int) ($data['departement_id'] ?? 0) ?: (int) $poste['departement_id'];
        if ($departementId !== (int) $poste['departement_id']) {
            return ['ok' => false, 'message' => 'Le poste choisi n’appartient pas au département sélectionné.'];
        }

        if ($role === 'manager') {
            $salarie = (new SalarieModel())
                ->where('utilisateur_id', (int) $utilisateur['id'])
                ->where('actif', 1)
                ->first();

            if (! $salarie) {
                return ['ok' => false, 'message' => 'Aucun dossier salarié actif n’est associé à votre compte.'];
            }
            if ((int) $salarie['departement_id'] !== $departementId) {
                return ['ok' => false, 'message' => 'Vous ne pouvez créer un besoin que pour votre propre département.'];
            }
        }

        $nombre = (int) ($data['nombre'] ?? 0);
        if ($nombre < 1) {
            return ['ok' => false, 'message' => 'Le nombre de postes doit être au moins égal à 1.'];
        }

        $salaireBase = (float) $poste['salaire_base'];
        $salaireMax  = (float) $poste['salaire_max'];
        $salaireSaisi = $data['salaire_estime'] ?? null;

        // Un champ vide (ou à 0) vaut le salaire de base du poste.
        $salaire = ($salaireSaisi === null || $salaireSaisi === '' || (float) $salaireSaisi === 0.0)
            ? $salaireBase
            : (float) $salaireSaisi;

        if ($salaire < 0) {
            return ['ok' => false, 'message' => 'Le salaire estimé ne peut pas être négatif.'];
        }

        if ($salaire < $salaireBase || $salaire > $salaireMax) {
            return [
                'ok'      => false,
                'message' => 'Le salaire estimé doit être compris dans la fourchette du poste ('
                    . number_format($salaireBase, 0, ',', ' ') . ' – '
                    . number_format($salaireMax, 0, ',', ' ') . ' Ar).',
            ];
        }

        $dateSouhaitee = trim((string) ($data['date_souhaitee'] ?? ''));
        if ($dateSouhaitee !== '') {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateSouhaitee);
            if (! $date || $date->format('Y-m-d') !== $dateSouhaitee) {
                return ['ok' => false, 'message' => 'La date souhaitée est invalide.'];
            }
            if ($dateSouhaitee < date('Y-m-d')) {
                return ['ok' => false, 'message' => 'La date souhaitée ne peut pas être dans le passé.'];
            }
        }

        $typeBesoin = in_array($data['type_besoin'] ?? '', ['creation', 'remplacement'], true)
            ? $data['type_besoin']
            : 'creation';

        $statut = (new StatutBesoinModel())->where('libelle', 'En attente RH')->first();
        if (! $statut) {
            return ['ok' => false, 'message' => 'Statut « En attente RH » manquant dans le référentiel.'];
        }

        $db = db_connect();
        $db->transBegin();

        try {
            $id = (int) (new BesoinModel())->insert([
                'poste_id'       => (int) $poste['id'],
                'departement_id' => $departementId,
                'demandeur_id'   => (int) $utilisateur['id'],
                'nombre'         => $nombre,
                'type_besoin'    => $typeBesoin,
                'motif'          => $this->nettoyer($data['motif'] ?? null, 255),
                'description'    => $this->nettoyer($data['description'] ?? null, 2000),
                'salaire_estime' => $salaire,
                'date_souhaitee' => $dateSouhaitee !== '' ? $dateSouhaitee : null,
                'statut_id'      => (int) $statut['id'],
                'date_creation'  => date('Y-m-d H:i:s'),
            ]);

            // Crée les étapes RH/DG, calcule la finance et positionne le statut.
            $this->workflow->initialiserBesoin($id, (int) $utilisateur['id'], $role);

            $this->audit->log((int) $utilisateur['id'], 'besoin.creer', 'besoin', $id, [
                'poste_id'       => (int) $poste['id'],
                'departement_id' => $departementId,
                'nombre'         => $nombre,
                'type_besoin'    => $typeBesoin,
                'salaire_estime' => $salaire,
            ]);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Échec de l’enregistrement du besoin.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();

            return ['ok' => false, 'message' => 'Impossible de créer le besoin : ' . $e->getMessage()];
        }

        return [
            'ok'      => true,
            'message' => $role === 'dg'
                ? 'Besoin créé et validé d’office par le DG. RH et finance ont été calculés pour information.'
                : 'Besoin soumis au circuit de validation. Statut : En attente RH.',
            'id'      => $id,
        ];
    }

    /**
     * Périmètre de création : départements et postes autorisés pour l'utilisateur.
     *
     * @return array{departements:array,postes:array,departement_id:?int,role:string}
     */
    public function perimetreCreation(array $utilisateur): array
    {
        $db   = db_connect();
        $role = $this->roleLibelle((int) ($utilisateur['role_id'] ?? 0));
        $departementId = null;

        $departements = $db->table('departement')
            ->where('actif', 1)
            ->orderBy('libelle')
            ->get()
            ->getResultArray();

        if ($role === 'manager') {
            $salarie = (new SalarieModel())
                ->where('utilisateur_id', (int) $utilisateur['id'])
                ->where('actif', 1)
                ->first();

            $departementId = $salarie ? (int) $salarie['departement_id'] : null;
            $departements  = array_values(array_filter(
                $departements,
                static fn (array $d): bool => (int) $d['id'] === $departementId
            ));
        }

        $postes = $db->table('poste')
            ->where('actif', 1)
            ->orderBy('libelle')
            ->get()
            ->getResultArray();

        if ($role === 'manager') {
            $postes = array_values(array_filter(
                $postes,
                static fn (array $p): bool => (int) $p['departement_id'] === $departementId
            ));
        }

        return [
            'departements'   => $departements,
            'postes'         => $postes,
            'departement_id' => $departementId,
            'role'           => $role,
        ];
    }

    /**
     * Liste les besoins. Un manager ne voit que ses propres demandes.
     */
    public function lister(?int $demandeurId = null, ?string $statutFiltre = null): array
    {
        $builder = $this->requeteBase()
            ->orderBy('b.date_creation', 'DESC');

        if ($demandeurId !== null) {
            $builder->where('b.demandeur_id', $demandeurId);
        }
        if ($statutFiltre) {
            $builder->where('sb.libelle', $statutFiltre);
        }

        return $this->enrichir($builder->get()->getResultArray());
    }

    /** File d'attente RH : besoins en attente de la décision RH. */
    public function fileRH(): array
    {
        return $this->enrichir(
            $this->requeteBase()
                ->where('sb.libelle', 'En attente RH')
                ->orderBy('b.date_creation', 'ASC')
                ->get()->getResultArray()
        );
    }

    /** File d'attente DG : besoins prêts pour la décision finale. */
    public function fileDG(): array
    {
        return $this->enrichir(
            $this->requeteBase()
                ->where('sb.libelle', 'En attente DG')
                ->orderBy('b.date_creation', 'ASC')
                ->get()->getResultArray()
        );
    }

    /** Détail complet d'un besoin (contrôle de portée fait par le contrôleur). */
    public function detail(int $id): ?array
    {
        $besoin = $this->requeteBase()
            ->where('b.id', $id)
            ->get()
            ->getRowArray();

        if (! $besoin) {
            return null;
        }

        $lignes = $this->enrichir([$besoin]);

        return $lignes[0];
    }

    private function requeteBase()
    {
        return db_connect()->table('besoin b')
            ->select('b.*, p.libelle AS poste, p.salaire_base, p.salaire_max,
                      d.libelle AS departement, d.code AS departement_code,
                      sb.libelle AS statut,
                      u.nom AS demandeur_nom, u.prenom AS demandeur_prenom, u.email AS demandeur_email')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->join('statut_besoin sb', 'sb.id = b.statut_id')
            ->join('utilisateur u', 'u.id = b.demandeur_id');
    }

    /** Ajoute les étapes workflow et la finance décodée à chaque besoin. */
    private function enrichir(array $besoins): array
    {
        foreach ($besoins as &$besoin) {
            $decisions = $this->workflow->decisionsBesoin((int) $besoin['id']);

            foreach ($decisions as &$decision) {
                $decision['details_decode'] = ! empty($decision['details_json'])
                    ? json_decode((string) $decision['details_json'], true)
                    : null;
            }
            unset($decision);

            $finance = $decisions['finance']['details_decode'] ?? null;

            $besoin['decisions'] = $decisions;
            $besoin['rh']        = $decisions['rh'] ?? null;
            $besoin['finance']   = $finance;
            $besoin['dg']        = $decisions['dg'] ?? null;
            $besoin['cout']      = (int) $besoin['nombre'] * (float) $besoin['salaire_estime'] * 12;
        }
        unset($besoin);

        return $besoins;
    }

    private function roleLibelle(int $roleId): string
    {
        $role = db_connect()->table('role')->where('id', $roleId)->get()->getRowArray();

        return (string) ($role['libelle'] ?? '');
    }

    private function nettoyer(?string $valeur, int $longueur): ?string
    {
        $valeur = trim((string) $valeur);

        return $valeur === '' ? null : mb_substr($valeur, 0, $longueur);
    }
}
