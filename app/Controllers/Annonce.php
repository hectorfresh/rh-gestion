<?php

namespace App\Controllers;

use App\Models\BesoinModel;

use App\Models\AnnonceModel;

use App\Models\StatutBesoinModel;
use App\Models\HistoriqueStatutBesoinModel;

use App\Controllers\BaseController;

class Annonce extends BaseController
{
public function genererForm(): string
{
    $statutModel = new StatutBesoinModel();
    $idValideDg  = $statutModel->where('libelle', 'Validé Par DG')->first()['id'];

    $db = \Config\Database::connect();
    $builder = $db->table('besoin');
    $builder->select('besoin.id AS id, poste.libelle AS libelle');   // ✅ on précise l'origine
    $builder->join('poste', 'besoin.poste_id = poste.id');
    $builder->where('besoin.statut_id', $idValideDg);
    $data['besoins'] = $builder->get()->getResult();

    return view('generation_annonces', $data);
}

public function generer()
{
    $idBesoin = (int) $this->request->getPost('besoin');
    $desce    = trim((string) $this->request->getPost('desce'));

    // --- Validation : ID de besoin obligatoire ---
    if ($idBesoin <= 0) {
        return redirect()->back()->withInput()
            ->with('error', 'Veuillez sélectionner un besoin valide.');
    }

    // --- Validation : le besoin doit exister ---
    $besoinModel = new BesoinModel();
    $besoin = $besoinModel->find($idBesoin);
    if (!$besoin) {
        return redirect()->back()->withInput()
            ->with('error', 'Ce besoin est introuvable.');
    }

    // --- Récupération du statut "Cloturé" ---
    $statutModel = new StatutBesoinModel();
    $statutCloture = $statutModel->where('libelle', 'Cloturé')->first();
    if (!$statutCloture) {
        return redirect()->back()->withInput()
            ->with('error', "Statut 'Cloturé' introuvable en base.");
    }
    $idCloture = $statutCloture['id'];

    $annonceModel = new AnnonceModel();
    $db = \Config\Database::connect();

    // --- Transaction pour tout ou rien ---
    $db->transBegin();

    try {
        // ✅ FIX Bug 1 : WHERE ciblé sur CE besoin
        $db->table('besoin')
           ->where('id', $idBesoin)
           ->update(['statut_id' => $idCloture]);

        // ✅ FIX Bug 2 : ID validé, insertion sécurisée
        $db->table('historique_statut_besoin')->insert([
            'besoin_id'        => $idBesoin,
            'statut_besoin_id' => $idCloture,
            'date_changement'  => date('Y-m-d H:i:s'),
        ]);

        // --- Création des annonces (une par poste demandé) ---
        $nombre = (int) $besoin['nombre'];
        for ($i = 0; $i < $nombre; $i++) {
            $annonceModel->insert([
                'besoin_id'     => $idBesoin,
                'desce'         => $desce,
                'date_creation' => date('Y-m-d H:i:s'),
                'estCloture'    => 0,
            ]);
        }

        $db->transCommit();

    } catch (\Throwable $e) {
        $db->transRollback();
        log_message('error', 'Erreur génération annonce : ' . $e->getMessage());
        return redirect()->back()->withInput()
            ->with('error', "Erreur lors de la génération de l'annonce. Réessayez.");
    }

    return redirect()->to(base_url('annonces'))
        ->with('success', 'Annonce(s) créée(s) avec succès.');
}

    public function liste()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('annonce');
        $builder->select('annonce.id as id, poste.libelle as libelle, poste.salaire_base as salaire_base');
        $builder->join('besoin', 'besoin.id=annonce.besoin_id');
        $builder->join('poste', 'besoin.poste_id=poste.id');
        $builder->groupStart()
                ->where('annonce.estCloture', 0)
                ->orWhere('annonce.estCloture IS NULL')
                ->groupEnd();
        $data['annonces'] = $builder->get()->getResult();
        


        $data['applied'] = [];
        $user = session()->get('user');
        if ($user !== null) {
            $ab = $db->table('candidature');
            $ab->select('candidature.annonce_id');
            $ab->join('cv', 'cv.id = candidature.cv_id');
            $ab->where('cv.utilisateur_id', $user['id']);
            foreach ($ab->get()->getResultArray() as $row) {
                $data['applied'][$row['annonce_id']] = true;
            }
        }

        return view('annonces', $data);
    }

    

}
