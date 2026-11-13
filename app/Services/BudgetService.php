<?php

namespace App\Services;

use App\Models\BesoinModel;
use App\Models\BudgetDepartementModel;
use App\Models\WorkflowValidationModel;

/**
 * Calculs budgétaires et étape « finance » automatique.
 *
 * Masse salariale : contrats actifs des salariés actifs du département × 12.
 * Engagements      : postes acceptés non pourvus, valorisés au salaire estimé,
 *                    en remplaçant l'estimation d'un poste par le salaire des
 *                    propositions d'embauche en cours (sans double comptage).
 * Disponible       : budget annuel − masse salariale − engagements.
 */
class BudgetService
{
    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function montantBudget(int $departementId, int $annee): float
    {
        $row = (new BudgetDepartementModel())
            ->where('departement_id', $departementId)
            ->where('annee', $annee)
            ->first();

        return $row ? (float) $row['montant_annuel'] : 0.0;
    }

    public function masseSalariale(int $departementId): float
    {
        $sql = "SELECT COALESCE(SUM(c.salaire_mensuel), 0) AS total
                FROM salarie s
                JOIN contrat c ON c.salarie_id = s.id
                WHERE s.departement_id = ?
                  AND s.actif = 1
                  AND c.statut = 'Accepte'
                  AND c.date_debut <= CURDATE()
                  AND (c.date_fin IS NULL OR c.date_fin >= CURDATE())";

        $row = $this->db->query($sql, [$departementId])->getRowArray();

        return ((float) ($row['total'] ?? 0)) * 12;
    }

    /**
     * @param string|null $excludeType 'besoin' ou 'proposition' pour retirer l'élément évalué
     */
    public function engagements(int $departementId, int $annee, ?string $excludeType = null, ?int $excludeId = null): float
    {
        $total = 0.0;

        // Un élément de proposition peut être retiré pour évaluer un remplacement
        // (contre-proposition) sans le compter deux fois.
        $exclusion = '';
        if ($excludeType === 'proposition' && $excludeId !== null) {
            $exclusion = ' AND p.id <> ?';
        }

        // Besoins acceptés non pourvus : les postes déjà couverts par une
        // proposition en cours sont valorisés au salaire proposé, les autres
        // au salaire estimé du besoin.
        $sql = "SELECT b.id, b.nombre, b.salaire_estime,
                       (SELECT COUNT(DISTINCT ca.id)
                          FROM candidature ca
                          JOIN annonce a ON a.id = ca.annonce_id
                          JOIN statut_candidature sc ON sc.id = ca.statut_id
                         WHERE a.besoin_id = b.id AND sc.libelle = 'Embauchée') AS embauches,
                       (SELECT COUNT(*)
                          FROM proposition_embauche p
                          JOIN candidature ca ON ca.id = p.candidature_id
                          JOIN annonce a ON a.id = ca.annonce_id
                         WHERE a.besoin_id = b.id
                           AND YEAR(p.date_creation) = ?
                           AND p.statut IN ('en_cours', 'en_attente', 'confirmable')
                           {$exclusion}) AS nb_propositions,
                       (SELECT COALESCE(SUM(CAST(pe.valeur_proposee AS DECIMAL(14,2))), 0)
                          FROM proposition_embauche p
                          JOIN proposition_element pe ON pe.proposition_id = p.id AND pe.code = 'salaire'
                          JOIN candidature ca ON ca.id = p.candidature_id
                          JOIN annonce a ON a.id = ca.annonce_id
                         WHERE a.besoin_id = b.id
                           AND YEAR(p.date_creation) = ?
                           AND p.statut IN ('en_cours', 'en_attente', 'confirmable')
                           {$exclusion}) AS salaire_propositions
                  FROM besoin b
                  JOIN statut_besoin sb ON sb.id = b.statut_id
                 WHERE b.departement_id = ?
                   AND sb.libelle = 'Validé'
                   AND YEAR(b.date_creation) = ?";

        $queryParams = array_merge(
            [$annee],
            $excludeType === 'proposition' ? [$excludeId] : [],
            [$annee],
            $excludeType === 'proposition' ? [$excludeId] : [],
            [$departementId, $annee]
        );

        $besoins = $this->db->query($sql, $queryParams)->getResultArray();

        foreach ($besoins as $besoin) {
            if ($excludeType === 'besoin' && (int) $besoin['id'] === $excludeId) {
                continue;
            }

            $restant = max(
                0,
                (int) $besoin['nombre'] - (int) $besoin['embauches'] - (int) $besoin['nb_propositions']
            );
            $total += $restant * (float) $besoin['salaire_estime'] * 12;
            $total += (float) $besoin['salaire_propositions'] * 12;
        }

        return $total;
    }

    public function budgetDisponible(int $departementId, int $annee, ?string $excludeType = null, ?int $excludeId = null): float
    {
        return $this->montantBudget($departementId, $annee)
            - $this->masseSalariale($departementId)
            - $this->engagements($departementId, $annee, $excludeType, $excludeId);
    }

    /** Instantané complet destiné à l'audit et à la traçabilité. */
    public function instantane(int $departementId, int $annee, float $cout, ?string $excludeType = null, ?int $excludeId = null, array $extra = []): array
    {
        $budget        = $this->montantBudget($departementId, $annee);
        $masse         = $this->masseSalariale($departementId);
        $engagements   = $this->engagements($departementId, $annee, $excludeType, $excludeId);
        $disponible    = $budget - $masse - $engagements;

        return array_merge([
            'departement_id'    => $departementId,
            'annee'             => $annee,
            'budget_annuel'     => round($budget, 2),
            'masse_salariale'   => round($masse, 2),
            'engagements'       => round($engagements, 2),
            'cout'              => round($cout, 2),
            'budget_disponible' => round($disponible, 2),
            'reste_apres_cout'  => round($disponible - $cout, 2),
        ], $extra);
    }

    /**
     * Étape finance d'un besoin. Enregistre l'instantané dans workflow_validation.
     * @return array{accepte:bool,snapshot:array}
     */
    public function financerBesoin(int $besoinId): array
    {
        $besoin = (new BesoinModel())->find($besoinId);
        if (! $besoin) {
            return ['accepte' => false, 'snapshot' => ['erreur' => 'Besoin introuvable']];
        }

        $annee = (int) date('Y', strtotime($besoin['date_creation']));
        $cout  = (int) $besoin['nombre'] * (float) $besoin['salaire_estime'] * 12;

        $snapshot = $this->instantane(
            (int) $besoin['departement_id'],
            $annee,
            $cout,
            'besoin',
            $besoinId,
            [
                'nombre'          => (int) $besoin['nombre'],
                'salaire_estime'  => (float) $besoin['salaire_estime'],
                'formule'         => 'nombre × salaire_estime × 12',
            ]
        );

        $accepte = $cout <= $snapshot['budget_disponible'];
        $this->enregistrerDecisionFinance('besoin', $besoinId, $accepte, $snapshot,
            $accepte ? 'Coût compatible avec le budget disponible.' : 'Coût supérieur au budget disponible.');

        return ['accepte' => $accepte, 'snapshot' => $snapshot];
    }

    /**
     * Étape finance d'une contre-proposition (élément de proposition).
     * Contrôle la fourchette du poste et le budget du département.
     */
    public function financerElement(int $elementId): array
    {
        $sql = "SELECT pe.*, p.id AS proposition_id, p.candidature_id,
                       b.departement_id, b.date_creation AS besoin_date, b.poste_id,
                       po.salaire_base, po.salaire_max, po.libelle AS poste_libelle
                  FROM proposition_element pe
                  JOIN proposition_embauche p ON p.id = pe.proposition_id
                  JOIN candidature ca ON ca.id = p.candidature_id
                  JOIN annonce a ON a.id = ca.annonce_id
                  JOIN besoin b ON b.id = a.besoin_id
                  JOIN poste po ON po.id = b.poste_id
                 WHERE pe.id = ?";
        $element = $this->db->query($sql, [$elementId])->getRowArray();

        if (! $element) {
            return ['accepte' => false, 'snapshot' => ['erreur' => 'Élément introuvable']];
        }

        $annee   = (int) date('Y', strtotime($element['besoin_date']));
        $valeur  = (float) $element['contre_valeur'];

        if ($element['code'] !== 'salaire') {
            $snapshot = $this->instantane((int) $element['departement_id'], $annee, 0,
                'proposition', (int) $element['proposition_id'],
                ['element' => $element['code'], 'valeur' => $element['contre_valeur']]);

            $this->enregistrerDecisionFinance('proposition_element', $elementId, true, $snapshot,
                'Élément non salarial : pas d’impact budgétaire direct.');

            return ['accepte' => true, 'snapshot' => $snapshot];
        }

        $cout = $valeur * 12;
        $snapshot = $this->instantane(
            (int) $element['departement_id'],
            $annee,
            $cout,
            null,
            null,
            [
                'salaire_contre_propose' => $valeur,
                'salaire_base'           => (float) $element['salaire_base'],
                'salaire_max'            => (float) $element['salaire_max'],
                'poste'                  => $element['poste_libelle'],
                'formule'                => 'salaire contre-proposé × 12',
            ]
        );

        // Le poste est déjà engagé par la proposition en cours : le disponible
        // doit être augmenté de son salaire actuel avant de comparer le nouveau coût.
        $salaireActuel = (float) $element['valeur_proposee'] * 12;
        $disponibleAvecPoste = $snapshot['budget_disponible'] + $salaireActuel;
        $snapshot['proposition_actuelle'] = round($salaireActuel, 2);
        $snapshot['budget_disponible_avec_poste'] = round($disponibleAvecPoste, 2);
        $snapshot['reste_apres_cout'] = round($disponibleAvecPoste - $cout, 2);

        $dansFourchette = $valeur >= (float) $element['salaire_base']
            && $valeur <= (float) $element['salaire_max'];
        $budgetSuffisant = $cout <= $disponibleAvecPoste;
        $snapshot['dans_fourchette'] = $dansFourchette;
        $snapshot['budget_suffisant'] = $budgetSuffisant;
        $accepte = $dansFourchette && $budgetSuffisant;

        $commentaire = ! $dansFourchette
            ? 'Salaire hors de la fourchette du poste.'
            : ($accepte ? 'Contre-proposition compatible avec le budget.' : 'Contre-proposition supérieure au budget disponible.');

        $this->enregistrerDecisionFinance('proposition_element', $elementId, $accepte, $snapshot, $commentaire);

        return ['accepte' => $accepte, 'snapshot' => $snapshot];
    }

    private function enregistrerDecisionFinance(string $typeObjet, int $objetId, bool $accepte, array $snapshot, string $commentaire): void
    {
        $model = new WorkflowValidationModel();
        $row = $model->where('objet_type', $typeObjet)
            ->where('objet_id', $objetId)
            ->where('etape', 'finance')
            ->first();

        $data = [
            'objet_type'    => $typeObjet,
            'objet_id'      => $objetId,
            'etape'         => 'finance',
            'decision'      => $accepte ? 'accepte' : 'refuse',
            'valideur_id'   => null,
            'automatique'   => 1,
            'commentaire'   => $commentaire,
            'details_json'  => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            'date_decision' => date('Y-m-d H:i:s'),
        ];

        if ($row) {
            $model->update($row['id'], $data);
        } else {
            $model->insert($data);
        }
    }

    /** Résumé pour le tableau de bord / reporting. */
    public function resume(int $departementId, int $annee): array
    {
        $budget      = $this->montantBudget($departementId, $annee);
        $masse       = $this->masseSalariale($departementId);
        $engagements = $this->engagements($departementId, $annee);

        return [
            'budget'      => $budget,
            'masse'       => $masse,
            'engagements' => $engagements,
            'disponible'  => $budget - $masse - $engagements,
        ];
    }

    /**
     * Tableau « budget / masse salariale / engagé / disponible » par département.
     * Utilisé par l'écran d'administration des budgets.
     */
    public function tableauDepartements(int $annee): array
    {
        $departements = $this->db->table('departement')
            ->where('actif', 1)
            ->orderBy('libelle')
            ->get()
            ->getResultArray();

        $lignes = [];
        foreach ($departements as $departement) {
            $resume = $this->resume((int) $departement['id'], $annee);
            $lignes[] = array_merge($departement, [
                'budget'      => $resume['budget'],
                'masse'       => $resume['masse'],
                'engagements' => $resume['engagements'],
                'disponible'  => $resume['disponible'],
                'alerte'      => $resume['disponible'] < 0,
            ]);
        }

        return $lignes;
    }
}
