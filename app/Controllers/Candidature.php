<?php

namespace App\Controllers;

use App\Models\CandidatureModel;
use App\Models\PropositionElementModel;
use App\Models\PropositionModel;
use App\Services\RecrutementService;
use App\Services\WorkflowService;

/**
 * Bloc 2/3 : suivi des candidatures, entretiens et accès à la proposition.
 */
class Candidature extends BaseController
{
    public function index()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $builder = $db->table('candidature c')
            ->select('c.*, a.titre AS annonce, p.libelle AS poste, d.libelle AS departement,
                      u.nom, u.prenom, u.email, sc.libelle AS statut,
                      (SELECT COUNT(*) FROM entretien e WHERE e.candidature_id = c.id) AS nb_entretiens')
            ->join('annonce a', 'a.id = c.annonce_id')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->join('utilisateur u', 'u.id = c.utilisateur_id')
            ->join('statut_candidature sc', 'sc.id = c.statut_id');

        $statut    = trim((string) $this->request->getGet('statut'));
        $annonceId = (int) $this->request->getGet('annonce_id');
        $page      = max(1, (int) $this->request->getGet('page'));
        $parPage   = 10;

        if ($statut !== '') {
            $builder->where('sc.libelle', $statut);
        }
        if ($annonceId > 0) {
            $builder->where('c.annonce_id', $annonceId);
        }

        $total = $builder->countAllResults(false);
        $candidatures = $builder
            ->orderBy('c.date_candidature', 'DESC')
            ->limit($parPage, ($page - 1) * $parPage)
            ->get()->getResultArray();

        $annonces = $db->table('annonce a')
            ->select('a.id, a.titre, p.libelle AS poste')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->orderBy('a.date_publication', 'DESC')
            ->get()->getResultArray();

        return $this->vue('candidatures/index', [
            'titre'         => 'Candidatures',
            'active'        => 'candidatures',
            'candidatures'  => $candidatures,
            'statut_filtre' => $statut,
            'annonce_filtre'=> $annonceId,
            'annonces'      => $annonces,
            'page'          => $page,
            'par_page'      => $parPage,
            'total'         => $total,
            'nb_pages'      => max(1, (int) ceil($total / $parPage)),
            'statuts'       => ['Reçue', 'Présélectionnée', 'En attente entretien', 'Retenue', 'Non retenue', 'Embauchée', 'Désistée'],
        ]);
    }

    public function mes()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $db = db_connect();
        $candidatures = $db->table('candidature c')
            ->select('c.*, a.titre AS annonce, p.libelle AS poste, d.libelle AS departement, sc.libelle AS statut')
            ->join('annonce a', 'a.id = c.annonce_id')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->join('statut_candidature sc', 'sc.id = c.statut_id')
            ->where('c.utilisateur_id', (int) $this->utilisateur()['id'])
            ->orderBy('c.date_candidature', 'DESC')
            ->get()->getResultArray();

        return $this->vue('candidatures/mes', [
            'titre' => 'Mes candidatures',
            'active' => 'candidatures',
            'candidatures' => $candidatures,
        ]);
    }

    public function show($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $db = db_connect();
        $candidature = $db->table('candidature c')
            ->select('c.*, a.titre AS annonce, a.id AS annonce_id, a.description AS annonce_description,
                      p.libelle AS poste, p.salaire_base, p.salaire_max, d.libelle AS departement,
                      u.id AS utilisateur_id, u.nom, u.prenom, u.email, u.telephone, sc.libelle AS statut,
                      b.id AS besoin_id, b.nombre, b.salaire_estime, b.demandeur_id')
            ->join('annonce a', 'a.id = c.annonce_id')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->join('utilisateur u', 'u.id = c.utilisateur_id')
            ->join('statut_candidature sc', 'sc.id = c.statut_id')
            ->where('c.id', (int) $id)
            ->get()->getRowArray();

        if (! $candidature) {
            return $this->erreur('Candidature introuvable.', 'candidatures');
        }

        $estProprietaire = (int) $candidature['utilisateur_id'] === (int) $this->utilisateur()['id'];
        if ($this->role() === 'manager' && (int) $candidature['demandeur_id'] !== (int) $this->utilisateur()['id']) {
            return $this->erreur('Vous ne pouvez consulter que les candidatures liées à vos besoins.', 'candidatures');
        }
        if (! $estProprietaire && ! in_array($this->role(), ['rh', 'dg', 'manager'], true)) {
            return $this->erreur('Accès refusé.', 'dashboard');
        }

        $cv = (new \App\Models\CvModel())->find($candidature['cv_id']);
        $identite = $cv ? (new \App\Models\CvIdentiteModel())->where('cv_id', $cv['id'])->first() : null;
        $formations = $cv ? (new \App\Models\CvFormationModel())->where('cv_id', $cv['id'])->findAll() : [];
        $experiences = $cv ? (new \App\Models\CvExperienceModel())->where('cv_id', $cv['id'])->findAll() : [];
        $langues = $cv ? $db->table('cv_langue cl')
            ->select('cl.*, l.libelle AS langue, n.libelle AS niveau')
            ->join('langue l', 'l.id = cl.langue_id')
            ->join('niveau n', 'n.id = cl.niveau_id')
            ->where('cl.cv_id', (int) $cv['id'])
            ->get()->getResultArray() : [];

        $entretiens = $db->table('entretien e')
            ->select('e.*, se.libelle AS statut, u.nom AS intervieweur_nom, u.prenom AS intervieweur_prenom')
            ->join('statut_entretien se', 'se.id = e.statut_id')
            ->join('utilisateur u', 'u.id = e.intervieweur_id', 'left')
            ->where('e.candidature_id', (int) $id)
            ->orderBy('e.date_entretien', 'DESC')
            ->get()->getResultArray();

        $historique = $db->table('historique_statut_candidature h')
            ->select('h.*, sc.libelle AS statut, u.nom, u.prenom')
            ->join('statut_candidature sc', 'sc.id = h.statut_id')
            ->join('utilisateur u', 'u.id = h.utilisateur_id', 'left')
            ->where('h.candidature_id', (int) $id)
            ->orderBy('h.date_changement', 'ASC')
            ->orderBy('h.id', 'ASC')
            ->get()->getResultArray();

        $intervieweurs = $db->table('utilisateur u')
            ->select('u.id, u.nom, u.prenom, r.libelle AS role')
            ->join('role r', 'r.id = u.role_id')
            ->whereIn('r.libelle', ['rh', 'dg', 'manager'])
            ->where('u.actif', 1)
            ->orderBy('u.nom')
            ->get()->getResultArray();

        $evaluations = (new \App\Models\EvaluationCandidatModel())->where('candidature_id', (int) $id)->orderBy('id', 'DESC')->findAll();

        $proposition = (new PropositionModel())->where('candidature_id', (int) $id)->first();
        $elements = [];
        $decisions = [];
        if ($proposition) {
            $elements = (new PropositionElementModel())->where('proposition_id', $proposition['id'])->findAll();
            $workflow = new WorkflowService();
            foreach ($elements as &$element) {
                $element['decisions'] = $workflow->decisionsElement((int) $element['id']);
                foreach ($element['decisions'] as &$d) {
                    $d['details_decode'] = $d['details_json'] ? json_decode($d['details_json'], true) : null;
                }
                unset($d);
            }
            unset($element);
        }

        $contrat = (new \App\Models\ContratModel())->where('candidature_id', (int) $id)->orderBy('id', 'DESC')->first();

        return $this->vue('candidatures/show', [
            'titre' => 'Candidature #' . $id,
            'active' => 'candidatures',
            'candidature' => $candidature,
            'cv' => $cv,
            'identite' => $identite,
            'formations' => $formations,
            'experiences' => $experiences,
            'langues' => $langues,
            'entretiens' => $entretiens,
            'historique' => $historique,
            'intervieweurs' => $intervieweurs,
            'evaluations' => $evaluations,
            'proposition' => $proposition,
            'elements' => $elements,
            'contrat' => $contrat,
            'est_proprietaire' => $estProprietaire,
            'staff' => $this->estStaff(),
            'workflow' => new WorkflowService(),
        ]);
    }

    public function changerStatut($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $statut = trim((string) $this->request->getPost('statut'));
        $commentaire = trim((string) $this->request->getPost('commentaire'));

        $service = new RecrutementService();
        $resultat = $service->changerStatutCandidature((int) $id, $statut, $this->utilisateur(), $commentaire);

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'candidatures/' . $id);
        }

        return $this->ok($resultat['message'], 'candidatures/' . $id);
    }
}
