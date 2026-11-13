<?php

namespace App\Controllers;

use App\Services\BesoinService;
use App\Services\RecrutementService;
use App\Services\WorkflowService;

/**
 * Bloc 1 : expression du besoin.
 *
 * Le manager crée et suit ses besoins ; RH et DG décident.
 * Le DG hérite de la file RH et tranche en dernier.
 */
class Besoin extends BaseController
{
    public function index()
    {
        if ($redirection = $this->exigerRoles(['manager', 'rh', 'dg'])) {
            return $redirection;
        }

        $service       = new BesoinService();
        $statutFiltre  = $this->request->getGet('statut');
        $demandeurId   = $this->role() === 'manager' ? (int) $this->utilisateur()['id'] : null;
        $db            = db_connect();

        $idAttenteRh = (int) ($db->table('statut_besoin')->where('libelle', 'En attente RH')->get()->getRowArray()['id'] ?? 0);
        $idAttenteDg = (int) ($db->table('statut_besoin')->where('libelle', 'En attente DG')->get()->getRowArray()['id'] ?? 0);

        return $this->vue('besoins/index', [
            'titre'         => $this->role() === 'manager' ? 'Mes besoins' : 'Besoins de recrutement',
            'active'        => 'besoins',
            'besoins'       => $service->lister($demandeurId, $statutFiltre),
            'statut_filtre' => $statutFiltre,
            'demandeur_id'  => $demandeurId,
            'nb_file_rh'    => in_array($this->role(), ['rh', 'dg'], true)
                ? $db->table('besoin')->where('statut_id', $idAttenteRh)->countAllResults()
                : 0,
            'nb_file_dg'    => $this->role() === 'dg'
                ? $db->table('besoin')->where('statut_id', $idAttenteDg)->countAllResults()
                : 0,
        ]);
    }

    /** File d'attente RH (accessible aussi au DG, qui hérite du rôle RH). */
    public function fileRH()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        return $this->vue('besoins/file_rh', [
            'titre'  => 'File de validation RH',
            'active' => 'besoins',
            'besoins'=> (new BesoinService())->fileRH(),
        ]);
    }

    /** File d'attente DG avec le comparatif RH / finance. */
    public function fileDG()
    {
        if ($redirection = $this->exigerRoles(['dg'])) {
            return $redirection;
        }

        return $this->vue('besoins/file_dg', [
            'titre'  => 'Décisions finales du DG',
            'active' => 'besoins',
            'besoins'=> (new BesoinService())->fileDG(),
        ]);
    }

    public function form()
    {
        if ($redirection = $this->exigerRoles(['manager', 'dg'])) {
            return $redirection;
        }

        $perimetre = (new BesoinService())->perimetreCreation($this->utilisateur());

        if ($this->role() === 'manager' && empty($perimetre['departements'])) {
            return $this->erreur('Aucun département actif n’est associé à votre dossier salarié.', 'dashboard');
        }

        return $this->vue('besoins/form', [
            'titre'          => 'Nouveau besoin',
            'active'         => 'besoins',
            'departements'   => $perimetre['departements'],
            'postes'         => $perimetre['postes'],
            'departement_id' => $perimetre['departement_id'],
        ]);
    }

    public function store()
    {
        if ($redirection = $this->exigerRoles(['manager', 'dg'])) {
            return $redirection;
        }

        $resultat = (new BesoinService())->creer([
            'poste_id'       => (int) $this->request->getPost('poste_id'),
            'departement_id' => (int) $this->request->getPost('departement_id'),
            'nombre'         => (int) $this->request->getPost('nombre'),
            'type_besoin'    => $this->request->getPost('type_besoin'),
            'motif'          => $this->request->getPost('motif'),
            'description'    => $this->request->getPost('description'),
            'salaire_estime' => $this->request->getPost('salaire_estime'),
            'date_souhaitee' => $this->request->getPost('date_souhaitee'),
        ], $this->utilisateur());

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'besoins/creer');
        }

        return $this->ok($resultat['message'], 'besoins/' . $resultat['id']);
    }

    public function show($id)
    {
        if ($redirection = $this->exigerRoles(['manager', 'rh', 'dg'])) {
            return $redirection;
        }

        $service = new BesoinService();
        $besoin  = $service->detail((int) $id);

        if (! $besoin) {
            return $this->erreur('Besoin introuvable.', 'besoins');
        }

        if ($this->role() === 'manager' && (int) $besoin['demandeur_id'] !== (int) $this->utilisateur()['id']) {
            return $this->erreur('Vous ne pouvez consulter que vos propres besoins.', 'besoins');
        }

        $workflow    = new WorkflowService();
        $utilisateur = $this->utilisateur();
        $db          = db_connect();
        $annonces    = $db->table('annonce')
            ->where('besoin_id', (int) $id)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        return $this->vue('besoins/show', [
            'titre'             => 'Besoin #' . $id,
            'active'            => 'besoins',
            'besoin'            => $besoin,
            'decisions'         => $besoin['decisions'],
            'finance'           => $besoin['finance'],
            'historique'        => $db->table('historique_statut_besoin h')
                ->select('h.*, sb.libelle, u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom')
                ->join('statut_besoin sb', 'sb.id = h.statut_id')
                ->join('utilisateur u', 'u.id = h.utilisateur_id', 'left')
                ->where('h.besoin_id', (int) $id)
                ->orderBy('h.id', 'ASC')
                ->get()->getResultArray(),
            'annonces'          => $annonces,
            'embauches'         => $db->table('candidature ca')
                ->join('annonce a', 'a.id = ca.annonce_id')
                ->join('statut_candidature sc', 'sc.id = ca.statut_id')
                ->where('a.besoin_id', (int) $id)
                ->whereIn('sc.libelle', ['Retenue', 'Embauchée'])
                ->countAllResults(),
            'peut_rh'           => $this->role() === 'rh'
                && $workflow->peutDecider((int) $id, 'rh', (int) $utilisateur['id']),
            'peut_dg'           => $this->role() === 'dg'
                && $workflow->peutDecider((int) $id, 'dg', (int) $utilisateur['id']),
            'peut_creer_annonce'=> $besoin['statut'] === 'Validé'
                && in_array($this->role(), ['rh', 'dg'], true)
                && empty($annonces),
        ]);
    }

    public function decider($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $decision    = (string) $this->request->getPost('decision');
        $commentaire = trim((string) $this->request->getPost('commentaire'));

        $resultat = (new WorkflowService())->deciderBesoin(
            (int) $id,
            (int) $this->utilisateur()['id'],
            $this->role(),
            $decision,
            $commentaire
        );

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'besoins/' . $id);
        }

        return $this->ok($resultat['message'], 'besoins/' . $id);
    }

    public function creerAnnonce($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $resultat = (new RecrutementService())->creerAnnonce((int) $id, [
            'titre'       => $this->request->getPost('titre'),
            'description' => $this->request->getPost('description'),
            'profil'      => $this->request->getPost('profil'),
            'date_limite' => $this->request->getPost('date_limite'),
        ], $this->utilisateur());

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'besoins/' . $id);
        }

        return $this->ok($resultat['message'], 'annonces/' . $resultat['id']);
    }
}
