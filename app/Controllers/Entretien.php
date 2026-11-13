<?php

namespace App\Controllers;

use App\Models\EntretienModel;
use App\Services\RecrutementService;

/**
 * Bloc 2 : planification, convocation et évaluation des entretiens.
 */
class Entretien extends BaseController
{
    public function index()
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $db = db_connect();
        $entretiens = $db->table('entretien e')
            ->select('e.*, se.libelle AS statut, u.nom, u.prenom, u.email,
                      a.titre AS annonce, p.libelle AS poste, d.libelle AS departement,
                      i.nom AS intervieweur_nom, i.prenom AS intervieweur_prenom')
            ->join('statut_entretien se', 'se.id = e.statut_id')
            ->join('candidature c', 'c.id = e.candidature_id')
            ->join('utilisateur u', 'u.id = c.utilisateur_id')
            ->join('annonce a', 'a.id = c.annonce_id')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->join('utilisateur i', 'i.id = e.intervieweur_id', 'left')
            ->orderBy('e.date_entretien', 'DESC')
            ->get()->getResultArray();

        return $this->vue('entretiens/index', [
            'titre'      => 'Entretiens',
            'active'     => 'entretiens',
            'entretiens' => $entretiens,
        ]);
    }

    public function mes()
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $db = db_connect();
        $entretiens = $db->table('entretien e')
            ->select('e.*, se.libelle AS statut, a.titre AS annonce, p.libelle AS poste, d.libelle AS departement,
                      i.nom AS intervieweur_nom, i.prenom AS intervieweur_prenom')
            ->join('statut_entretien se', 'se.id = e.statut_id')
            ->join('candidature c', 'c.id = e.candidature_id')
            ->join('annonce a', 'a.id = c.annonce_id')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->join('utilisateur i', 'i.id = e.intervieweur_id', 'left')
            ->where('c.utilisateur_id', (int) $this->utilisateur()['id'])
            ->orderBy('e.date_entretien', 'DESC')
            ->get()->getResultArray();

        return $this->vue('entretiens/mes', [
            'titre'      => 'Mes entretiens',
            'active'     => 'entretiens',
            'entretiens' => $entretiens,
        ]);
    }

    public function planifier($candidatureId)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $service = new RecrutementService();
        $resultat = $service->planifierEntretien((int) $candidatureId, [
            'type'           => $this->request->getPost('type') ?: 'RH',
            'date_entretien' => $this->request->getPost('date_entretien'),
            'lieu'           => $this->request->getPost('lieu'),
            'commentaire'    => $this->request->getPost('commentaire'),
            'intervieweur_id'=> $this->request->getPost('intervieweur_id'),
        ], $this->utilisateur());

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'candidatures/' . $candidatureId);
        }

        return $this->ok($resultat['message'], 'candidatures/' . $candidatureId);
    }

    public function repondre($id)
    {
        if ($redirection = $this->exigerConnexion()) {
            return $redirection;
        }

        $reponse = (string) $this->request->getPost('reponse');
        $motif   = trim((string) $this->request->getPost('motif'));

        $resultat = (new RecrutementService())->repondreEntretien(
            (int) $id,
            $this->utilisateur(),
            $reponse,
            $motif !== '' ? $motif : null
        );

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], 'entretiens/mes');
        }

        return $this->ok($resultat['message'], 'entretiens/mes');
    }

    public function evaluer($id)
    {
        if ($redirection = $this->exigerRoles(['rh', 'dg'])) {
            return $redirection;
        }

        $entretien = (new EntretienModel())->find((int) $id);
        if (! $entretien) {
            return $this->erreur('Entretien introuvable.', 'entretiens');
        }

        $resultat = (new RecrutementService())->evaluerEntretien((int) $id, [
            'note'        => $this->request->getPost('note'),
            'commentaire' => $this->request->getPost('commentaire'),
            'decision'    => $this->request->getPost('decision') ?: 'reserve',
        ], $this->utilisateur());

        $url = 'candidatures/' . $entretien['candidature_id'];

        if (! $resultat['ok']) {
            return $this->erreur($resultat['message'], $url);
        }

        return $this->ok($resultat['message'], $url);
    }
}
