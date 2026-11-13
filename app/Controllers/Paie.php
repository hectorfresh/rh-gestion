<?php

namespace App\Controllers;

use App\Models\VariablePaieModel;
use App\Services\AuditService;
use App\Services\PaieService;

/**
 * Bloc 6/7 : éléments variables de paie et synthèse mensuelle.
 */
class Paie extends BaseController
{
    public function index()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $mois = $this->request->getGet('mois') ?: date('Y-m');
        [$annee, $moisNum] = (new PaieService())->decouperPeriode($mois);
        $departementId = $this->request->getGet('departement_id') ? (int) $this->request->getGet('departement_id') : null;

        $db = db_connect();
        $services = new PaieService();

        return $this->vue('paie/index', [
            'titre' => 'Paie variable',
            'active' => 'paie',
            'mois' => $mois,
            'departement_id' => $departementId,
            'lignes' => $services->calculerMois($mois, $departementId),
            'totaux' => $services->totalMois($mois, $departementId),
            'variables' => $db->table('variable_paie v')
                ->select("v.id, v.salarie_id, v.annee, v.mois, v.libelle, v.montant, v.type, v.created_at,
                          CONCAT(v.annee, '-', LPAD(v.mois, 2, '0')) AS periode,
                          u.nom, u.prenom, s.matricule")
                ->join('salarie s', 's.id = v.salarie_id')
                ->join('utilisateur u', 'u.id = s.utilisateur_id')
                ->where('v.annee', $annee)
                ->where('v.mois', $moisNum)
                ->orderBy('v.created_at', 'DESC')
                ->get()->getResultArray(),
            'salaries' => $db->table('salarie s')
                ->select('s.id, s.matricule, u.nom, u.prenom')
                ->join('utilisateur u', 'u.id = s.utilisateur_id')
                ->where('s.actif', 1)->orderBy('u.nom')->get()->getResultArray(),
            'departements' => $db->table('departement')->orderBy('libelle')->get()->getResultArray(),
        ]);
    }

    public function storeVariable()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $data = [
            'salarie_id' => (int) $this->request->getPost('salarie_id'),
            'libelle'    => trim((string) $this->request->getPost('libelle')),
            'montant'    => (float) $this->request->getPost('montant'),
            'type'       => $this->request->getPost('type') ?: 'prime',
            'created_by' => (int) $this->utilisateur()['id'],
        ];

        $periode = $this->request->getPost('mois') ?: date('Y-m');
        [$data['annee'], $data['mois']] = (new PaieService())->decouperPeriode($periode);

        if (! $data['salarie_id'] || $data['libelle'] === '' || $data['montant'] <= 0) {
            return $this->erreur('Salarié, libellé et montant positif obligatoires.', 'paie?mois=' . $periode);
        }

        (new VariablePaieModel())->insert($data);
        (new AuditService())->log((int) $this->utilisateur()['id'], 'paie.variable.creer', 'salarie', $data['salarie_id'], $data);

        return $this->ok('Élément variable ajouté.', 'paie?mois=' . $periode);
    }

    public function supprimerVariable($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $variable = (new VariablePaieModel())->find((int) $id);
        if (! $variable) {
            return $this->erreur('Élément introuvable.', 'paie');
        }
        (new VariablePaieModel())->delete((int) $id);
        $periode = $variable['annee'] . '-' . str_pad((string) $variable['mois'], 2, '0', STR_PAD_LEFT);
        return $this->ok('Élément supprimé.', 'paie?mois=' . $periode);
    }

    public function export()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $mois = $this->request->getGet('mois') ?: date('Y-m');
        $lignes = (new PaieService())->calculerMois($mois);

        $sortie = fopen('php://temp', 'r+');
        fputcsv($sortie, ['Matricule', 'Nom', 'Prénom', 'Département', 'Poste', 'Salaire fixe', 'Gains variables', 'Retenues', 'Brut', 'Net'], ';');
        foreach ($lignes as $ligne) {
            fputcsv($sortie, [
                $ligne['matricule'], $ligne['nom'], $ligne['prenom'], $ligne['departement'], $ligne['poste'],
                $ligne['salaire_fixe'], $ligne['gains_variables'], $ligne['retenues'], $ligne['brut'], $ligne['net'],
            ], ';');
        }
        rewind($sortie);
        $contenu = stream_get_contents($sortie);
        fclose($sortie);

        return $this->response->download('paie_' . $mois . '.csv', $contenu);
    }
}
