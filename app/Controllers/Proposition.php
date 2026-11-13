<?php

namespace App\Controllers;

use App\Models\PropositionElementModel;
use App\Models\PropositionModel;
use App\Services\PropositionService;
use App\Services\WorkflowService;

/**
 * Bloc 3 : proposition d'embauche, questionnaire OUI/NON,
 * contre-propositions, files RH/DG et confirmation.
 */
class Proposition extends BaseController
{
    public function index()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg', 'manager'])) {
            return $redirection;
        }

        $db = db_connect();
        $builder = $db->table('proposition_embauche p')
            ->select('p.*, u.nom, u.prenom, u.email, a.titre AS annonce, po.libelle AS poste,
                      (SELECT COUNT(*) FROM proposition_element pe WHERE pe.proposition_id = p.id) AS nb_elements,
                      (SELECT COUNT(*) FROM proposition_element pe WHERE pe.proposition_id = p.id
                        AND (pe.statut IN ("accepte","contre_validee")
                             OR (pe.statut = "contre_refusee" AND pe.reponse = "oui"))) AS nb_regles')
            ->join('candidature c', 'c.id = p.candidature_id')
            ->join('utilisateur u', 'u.id = c.utilisateur_id')
            ->join('annonce a', 'a.id = c.annonce_id')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste po', 'po.id = b.poste_id')
            ->orderBy('p.date_creation', 'DESC');

        if ($this->role() === 'manager') {
            $builder->where('b.demandeur_id', (int) $this->utilisateur()['id']);
        }

        $propositions = $builder->get()->getResultArray();

        return $this->vue('propositions/index', [
            'titre'        => 'Propositions d’embauche',
            'active'       => 'propositions',
            'propositions' => $propositions,
        ]);
    }

    public function mes()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $db = db_connect();
        $propositions = $db->table('proposition_embauche p')
            ->select('p.*, a.titre AS annonce, po.libelle AS poste, d.libelle AS departement')
            ->join('candidature c', 'c.id = p.candidature_id')
            ->join('annonce a', 'a.id = c.annonce_id')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste po', 'po.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->where('c.utilisateur_id', (int) $this->utilisateur()['id'])
            ->where('p.date_envoi IS NOT NULL', null, false)
            ->orderBy('p.date_creation', 'DESC')
            ->get()->getResultArray();

        return $this->vue('propositions/mes', [
            'titre'        => 'Mes propositions',
            'active'       => 'propositions',
            'propositions' => $propositions,
        ]);
    }

    /** File RH : brouillons à lancer et contre-propositions à examiner. */
    public function fileRH()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $brouillons = $db->table('proposition_embauche p')
            ->select('p.*, u.nom, u.prenom, a.titre AS annonce, po.libelle AS poste, d.libelle AS departement')
            ->join('candidature c', 'c.id = p.candidature_id')
            ->join('utilisateur u', 'u.id = c.utilisateur_id')
            ->join('annonce a', 'a.id = c.annonce_id')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste po', 'po.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->where('p.date_envoi IS NULL', null, false)
            ->where('p.statut', 'en_cours')
            ->orderBy('p.date_creation', 'ASC')
            ->get()->getResultArray();

        $contrePropositions = $this->requeteContrePropositions()
            ->where('rh.decision', 'en_attente')
            ->orderBy('pe.updated_at', 'ASC')
            ->get()->getResultArray();

        return $this->vue('propositions/file_rh', [
            'titre'             => 'File RH — propositions',
            'active'            => 'propositions',
            'brouillons'        => $brouillons,
            'contre_propositions' => $contrePropositions,
        ]);
    }

    /** File DG : contre-propositions avec comparatif budgétaire. */
    public function fileDG()
    {
        if ($redirection = $this->exigerRoles(['dg'])) {
            return $redirection;
        }

        $rows = $this->requeteContrePropositions()
            ->where('rh.decision !=', 'en_attente')
            ->where('fi.decision !=', 'en_attente')
            ->where('dg.decision', 'en_attente')
            ->orderBy('pe.updated_at', 'ASC')
            ->get()->getResultArray();

        foreach ($rows as &$row) {
            $row['finance'] = $row['finance_json'] ? json_decode($row['finance_json'], true) : null;
        }
        unset($row);

        return $this->vue('propositions/file_dg', [
            'titre' => 'File DG — contre-propositions',
            'active' => 'propositions',
            'contre_propositions' => $rows,
        ]);
    }

    public function form($candidatureId)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $candidature = $db->table('candidature c')
            ->select('c.*, sc.libelle AS statut, u.nom, u.prenom, a.titre AS annonce, p.libelle AS poste,
                      p.salaire_base, p.salaire_max, b.salaire_estime, b.date_souhaitee, b.id AS besoin_id')
            ->join('statut_candidature sc', 'sc.id = c.statut_id')
            ->join('utilisateur u', 'u.id = c.utilisateur_id')
            ->join('annonce a', 'a.id = c.annonce_id')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->where('c.id', (int) $candidatureId)
            ->get()->getRowArray();

        if (! $candidature) {
            return $this->erreur('Candidature introuvable.', 'candidatures');
        }
        if ($candidature['statut'] !== 'Retenue') {
            return $this->erreur('Seule une candidature retenue peut recevoir une proposition.', 'candidatures/' . $candidatureId);
        }

        if ($proposition = (new PropositionModel())->where('candidature_id', (int) $candidatureId)->first()) {
            $url = 'propositions/' . $proposition['id']
                . ($proposition['date_envoi'] === null ? '/modifier' : '');
            return redirect()->to(base_url($url));
        }

        $salaireDefaut = ((float) $candidature['salaire_base'] + (float) $candidature['salaire_max']) / 2;
        $dateDefaut = ! empty($candidature['date_souhaitee']) && $candidature['date_souhaitee'] >= date('Y-m-d')
            ? $candidature['date_souhaitee']
            : date('Y-m-d', strtotime('+1 month'));

        return $this->vue('propositions/form', [
            'titre'          => 'Proposition d’embauche',
            'active'         => 'propositions',
            'candidature'    => $candidature,
            'proposition'    => null,
            'elements'       => [],
            'salaire_defaut' => $salaireDefaut,
            'date_defaut'    => $dateDefaut,
            'mode'           => 'create',
        ]);
    }

    public function store($candidatureId)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $service = new PropositionService();
        $resultat = $service->creer((int) $candidatureId, $this->donneesPost(), $this->utilisateur());

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'candidatures/' . $candidatureId . '/proposition');
        }

        return $this->ok($resultat['message'], 'propositions/' . $resultat['id']);
    }

    public function editer($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $proposition = $this->propositionDetaillee((int) $id);
        if (! $proposition) {
            return $this->erreur('Proposition introuvable.', 'propositions');
        }
        if ($proposition['date_envoi'] !== null || $proposition['statut'] !== 'en_cours') {
            return $this->erreur('Seule une proposition en brouillon peut être modifiée.', 'propositions/' . $id);
        }

        $elements = [];
        foreach ((new PropositionElementModel())->where('proposition_id', (int) $id)->findAll() as $element) {
            $elements[$element['code']] = $element;
        }

        return $this->vue('propositions/form', [
            'titre'       => 'Modifier la proposition #' . $id,
            'active'      => 'propositions',
            'candidature' => $proposition,
            'proposition' => $proposition,
            'elements'    => $elements,
            'salaire_defaut' => $elements['salaire']['valeur_proposee'] ?? $proposition['salaire_base'],
            'date_defaut' => $elements['date_debut']['valeur_proposee'] ?? date('Y-m-d'),
            'mode'        => 'edit',
        ]);
    }

    public function mettreAJour($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $envoyer = (string) $this->request->getPost('envoyer') === '1';
        $service = new PropositionService();
        $resultat = $service->mettreAJour((int) $id, $this->donneesPost(), $this->utilisateur(), $envoyer);

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'propositions/' . $id . '/modifier');
        }

        return $this->ok($resultat['message'], 'propositions/' . $id);
    }

    public function envoyer($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $service = new PropositionService();
        $resultat = $service->envoyer((int) $id, $this->utilisateur());

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'propositions/' . $id);
        }

        return $this->ok($resultat['message'], 'propositions/' . $id);
    }

    public function show($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $proposition = $this->propositionDetaillee((int) $id);
        if (! $proposition) {
            return $this->erreur('Proposition introuvable.', 'propositions');
        }

        $estProprietaire = (int) $proposition['utilisateur_id'] === (int) $this->utilisateur()['id'];
        $estManagerAutorise = $this->role() === 'manager'
            && (int) $proposition['demandeur_id'] === (int) $this->utilisateur()['id'];

        if ($this->role() === 'manager' && ! $estManagerAutorise) {
            return $this->erreur('Vous ne pouvez consulter que les propositions liées à vos besoins.', 'propositions');
        }
        if (! $estProprietaire && ! $this->estStaff() && ! $estManagerAutorise) {
            return $this->erreur('Accès refusé.', 'dashboard');
        }
        if ($estProprietaire && $proposition['date_envoi'] === null) {
            return $this->erreur('Cette proposition n’a pas encore été envoyée.', 'dashboard');
        }

        $elements = $this->elementsAvecWorkflow((int) $id);
        $estBrouillon = $proposition['date_envoi'] === null;

        return $this->vue('propositions/show', [
            'titre'            => 'Proposition #' . $id,
            'active'           => 'propositions',
            'proposition'      => $proposition,
            'elements'         => $elements,
            'est_proprietaire' => $estProprietaire,
            'staff'            => $this->estStaff(),
            'est_brouillon'    => $estBrouillon,
            'peut_modifier'    => $this->estStaff() && $estBrouillon,
            'peut_confirmer'   => $estProprietaire && $proposition['statut'] === 'confirmable',
        ]);
    }

    /** Récapitulatif final avant confirmation. */
    public function confirmation($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $proposition = $this->propositionDetaillee((int) $id);
        if (! $proposition) {
            return $this->erreur('Proposition introuvable.', 'propositions');
        }
        if ((int) $proposition['utilisateur_id'] !== (int) $this->utilisateur()['id']) {
            return $this->erreur('Accès refusé.', 'dashboard');
        }
        if ($proposition['statut'] !== 'confirmable') {
            return $this->erreur('Tous les éléments doivent d’abord être acceptés.', 'propositions/' . $id);
        }

        $elements = $this->elementsAvecWorkflow((int) $id);

        return $this->vue('propositions/confirmation', [
            'titre'       => 'Confirmation de la proposition #' . $id,
            'active'      => 'propositions',
            'proposition' => $proposition,
            'elements'    => $elements,
        ]);
    }

    public function repondre($elementId)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $reponse = (string) $this->request->getPost('reponse');
        $valeur  = (string) $this->request->getPost('contre_valeur');
        $motif   = (string) $this->request->getPost('motif_candidat');
        $element = (new PropositionElementModel())->find((int) $elementId);

        $service = new PropositionService();
        $resultat = $service->repondre((int) $elementId, (int) $this->utilisateur()['id'], $reponse, $valeur, $motif);

        $url = $element ? 'propositions/' . $element['proposition_id'] : 'propositions/mes';
        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], $url);
        }

        return $this->ok($resultat['message'], $url);
    }

    public function confirmer($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $service = new PropositionService();
        $resultat = $service->confirmer((int) $id, (int) $this->utilisateur()['id']);

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'propositions/' . $id);
        }

        return $this->ok($resultat['message'], 'contrats/' . $resultat['contrat_id']);
    }

    public function renoncer($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $service = new PropositionService();
        $resultat = $service->renoncer((int) $id, (int) $this->utilisateur()['id']);

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'propositions/' . $id);
        }

        return $this->ok($resultat['message'], 'propositions/mes');
    }

    public function decider($elementId)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $element = (new PropositionElementModel())->find((int) $elementId);
        $url = $element ? 'propositions/' . $element['proposition_id'] : 'propositions';

        $service = new WorkflowService();
        $resultat = $service->deciderElement(
            (int) $elementId,
            (int) $this->utilisateur()['id'],
            $this->role(),
            (string) $this->request->getPost('decision'),
            (string) $this->request->getPost('commentaire')
        );

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], $url);
        }

        return $this->ok($resultat['message'], $url);
    }

    private function donneesPost(): array
    {
        return [
            'salaire'       => $this->request->getPost('salaire'),
            'type_contrat'  => $this->request->getPost('type_contrat'),
            'date_debut'    => $this->request->getPost('date_debut'),
            'heure_debut'   => $this->request->getPost('heure_debut'),
            'heure_fin'     => $this->request->getPost('heure_fin'),
            'periode_essai' => $this->request->getPost('periode_essai'),
        ];
    }

    private function propositionDetaillee(int $id): ?array
    {
        $row = db_connect()->table('proposition_embauche p')
            ->select('p.*, c.utilisateur_id, c.id AS candidature_id, u.nom, u.prenom, u.email,
                      a.titre AS annonce, po.libelle AS poste, po.salaire_base, po.salaire_max,
                      d.libelle AS departement, b.salaire_estime, b.demandeur_id')
            ->join('candidature c', 'c.id = p.candidature_id')
            ->join('utilisateur u', 'u.id = c.utilisateur_id')
            ->join('annonce a', 'a.id = c.annonce_id')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste po', 'po.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->where('p.id', $id)
            ->get()->getRowArray();

        return $row ?: null;
    }

    private function elementsAvecWorkflow(int $propositionId): array
    {
        $elements = (new PropositionElementModel())->where('proposition_id', $propositionId)->findAll();
        $workflow = new WorkflowService();

        foreach ($elements as &$element) {
            $element['decisions'] = $workflow->decisionsElement((int) $element['id']);
            foreach ($element['decisions'] as &$decision) {
                $decision['details_decode'] = $decision['details_json'] ? json_decode($decision['details_json'], true) : null;
            }
            unset($decision);
            $element['peut_rh'] = $this->role() === 'rh' && $workflow->peutDeciderElement((int) $element['id'], 'rh');
            $element['peut_dg'] = $this->role() === 'dg' && $workflow->peutDeciderElement((int) $element['id'], 'dg');
        }
        unset($element);

        return $elements;
    }

    private function requeteContrePropositions()
    {
        return db_connect()->table('proposition_element pe')
            ->select('pe.*, p.id AS proposition_id, p.candidature_id, p.statut AS proposition_statut,
                      c.utilisateur_id, u.nom, u.prenom, u.email, a.titre AS annonce,
                      po.libelle AS poste, po.salaire_base, po.salaire_max,
                      d.libelle AS departement, b.salaire_estime,
                      rh.decision AS rh_decision, rh.commentaire AS rh_commentaire,
                      fi.decision AS finance_decision, fi.commentaire AS finance_commentaire,
                      fi.details_json AS finance_json,
                      dg.decision AS dg_decision')
            ->join('proposition_embauche p', 'p.id = pe.proposition_id')
            ->join('candidature c', 'c.id = p.candidature_id')
            ->join('utilisateur u', 'u.id = c.utilisateur_id')
            ->join('annonce a', 'a.id = c.annonce_id')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste po', 'po.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->join('workflow_validation rh', "rh.objet_type = 'proposition_element' AND rh.objet_id = pe.id AND rh.etape = 'rh'", 'left')
            ->join('workflow_validation fi', "fi.objet_type = 'proposition_element' AND fi.objet_id = pe.id AND fi.etape = 'finance'", 'left')
            ->join('workflow_validation dg', "dg.objet_type = 'proposition_element' AND dg.objet_id = pe.id AND dg.etape = 'dg'", 'left')
            ->where('pe.statut', 'contre_proposition')
            ->whereIn('p.statut', ['en_cours', 'en_attente', 'confirmable']);
    }
}
