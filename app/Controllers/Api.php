<?php

namespace App\Controllers;

use App\Models\AnnonceModel;

/**
 * API JSON légère, en lecture seule, pour la consultation publique.
 */
class Api extends BaseController
{
    public function annonces()
    {
        $db = db_connect();
        $annonces = $db->table('annonce a')
            ->select('a.id, a.titre, a.description, a.profil, a.date_publication, a.date_limite,
                      p.libelle AS poste, d.libelle AS departement, b.nombre, b.salaire_estime,
                      p.salaire_base, p.salaire_max')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->where('a.est_cloture', 0)
            ->orderBy('a.date_publication', 'DESC')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'ok' => true,
            'total' => count($annonces),
            'data' => $annonces,
        ]);
    }

    public function annonce($id)
    {
        $db = db_connect();
        $annonce = $db->table('annonce a')
            ->select('a.id, a.titre, a.description, a.profil, a.date_publication, a.date_limite,
                      p.libelle AS poste, d.libelle AS departement, b.nombre, b.salaire_estime,
                      p.salaire_base, p.salaire_max')
            ->join('besoin b', 'b.id = a.besoin_id')
            ->join('poste p', 'p.id = b.poste_id')
            ->join('departement d', 'd.id = b.departement_id')
            ->where('a.id', (int) $id)
            ->get()->getRowArray();

        if (! $annonce) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Annonce introuvable.',
            ]);
        }

        return $this->response->setJSON(['ok' => true, 'data' => $annonce]);
    }

    public function departements()
    {
        $rows = db_connect()->table('departement')
            ->select('id, code, libelle, description')
            ->where('actif', 1)
            ->orderBy('libelle')
            ->get()->getResultArray();

        return $this->response->setJSON(['ok' => true, 'data' => $rows]);
    }

    public function postes()
    {
        $rows = db_connect()->table('poste p')
            ->select('p.id, p.libelle, p.salaire_base, p.salaire_max, d.libelle AS departement')
            ->join('departement d', 'd.id = p.departement_id')
            ->where('p.actif', 1)
            ->orderBy('p.libelle')
            ->get()->getResultArray();

        return $this->response->setJSON(['ok' => true, 'data' => $rows]);
    }
}
