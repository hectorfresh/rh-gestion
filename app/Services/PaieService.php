<?php

namespace App\Services;

/**
 * Bloc 6/7 : synthèse de paie variable et calculs simples.
 * Le salaire fixe vient du contrat actif ; les primes/retenues viennent de variable_paie.
 */
class PaieService
{
    public function calculerMois(string $mois, ?int $departementId = null): array
    {
        $db = db_connect();

        [$annee, $moisNum] = $this->decouperPeriode($mois);

        $builder = $db->table('salarie s')
            ->select('s.id AS salarie_id, s.matricule, u.nom, u.prenom,
                      d.libelle AS departement, p.libelle AS poste,
                      c.salaire_mensuel,
                      COALESCE((SELECT SUM(v.montant) FROM variable_paie v
                                 WHERE v.salarie_id = s.id AND v.annee = ' . $annee . ' AND v.mois = ' . $moisNum . '
                                   AND v.type IN ("prime","heure_sup","autre")), 0) AS gains_variables,
                      COALESCE((SELECT SUM(v.montant) FROM variable_paie v
                                 WHERE v.salarie_id = s.id AND v.annee = ' . $annee . ' AND v.mois = ' . $moisNum . '
                                   AND v.type = "retenue"), 0) AS retenues')
            ->join('utilisateur u', 'u.id = s.utilisateur_id')
            ->join('departement d', 'd.id = s.departement_id')
            ->join('poste p', 'p.id = s.poste_id')
            ->join('contrat c', 'c.salarie_id = s.id AND c.statut = "Accepte"', 'left')
            ->where('s.actif', 1);

        if ($departementId !== null) {
            $builder->where('s.departement_id', $departementId);
        }

        $lignes = $builder->groupBy('s.id')->orderBy('u.nom', 'ASC')->get()->getResultArray();

        foreach ($lignes as &$ligne) {
            $fixe = (float) ($ligne['salaire_mensuel'] ?? 0);
            $gains = (float) $ligne['gains_variables'];
            $retenues = (float) $ligne['retenues'];
            $ligne['salaire_fixe'] = $fixe;
            $ligne['brut'] = round($fixe + $gains, 2);
            $ligne['net'] = round($fixe + $gains - $retenues, 2);
        }
        unset($ligne);

        return $lignes;
    }

    public function totalMois(string $mois, ?int $departementId = null): array
    {
        $lignes = $this->calculerMois($mois, $departementId);
        $total = ['brut' => 0.0, 'net' => 0.0, 'salaries' => count($lignes)];
        foreach ($lignes as $ligne) {
            $total['brut'] += $ligne['brut'];
            $total['net'] += $ligne['net'];
        }
        return $total;
    }

    /** @return array{0:int,1:int} */
    public function decouperPeriode(string $mois): array
    {
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $mois, $m)) {
            $annee = (int) $m[1];
            $moisNum = max(1, min(12, (int) $m[2]));
            return [$annee, $moisNum];
        }

        return [(int) date('Y'), (int) date('n')];
    }
}
