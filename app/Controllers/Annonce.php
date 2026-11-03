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
        $idValideDg = $statutModel->where('libelle','Validé Par DG')->first()['id'];

        $db = \Config\Database::connect();
        $builder = $db->table('besoin');
        $builder->select('*');
        $builder->join('poste', 'besoin.poste_id=poste.id');
        $builder->where('besoin.statut_id', $idValideDg);
        $data['besoins'] = $builder->get()->getResult();
        
        return view('generation_annonces', $data);
    }


    public function generer()
    {
        $idBesoin = $this->request->getPost('besoin');
        $desce = $this->request->getPost('desce');

        $statutModel = new StatutBesoinModel();
        $besoinModel = new BesoinModel();
        $annonceModel = new AnnonceModel();
        $idCloture = $statutModel->where('libelle','Cloturé')->first()['id'];

        $db = \Config\Database::connect();
        $builder = $db->table('besoin');
        $builder->set('statut_id', $idCloture);
        $builder->update();

        $builder = $db->table('historique_statut_besoin');
        $builder->set('besoin_id', $idBesoin);
        $builder->set('statut_besoin_id', $idCloture);
        $builder->set('date_changement', date('Y-m-d H:i:s', time()));
        $builder->insert();
        
        $nombre = $besoinModel->where('id', $idBesoin)->first()['nombre'];

        for ($i=0; $i < $nombre; $i++) { 
            $annonceModel->insert([
                'besoin_id'=>$idBesoin, 
                'desce'=>$desce,
                'date_creation'=>date('Y-m-d H:i:s', time()), 
                'estCloture'=>0
            ]);
        }

        return "Annonce Créée";
    }


    public function liste()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('annonce');
        $builder->select('*');
        $builder->join('besoin', 'besoin.id=annonce.besoin_id');
        $builder->join('poste', 'besoin.poste_id=poste.id');
        $builder->where('estCloture', 0);

        $data['annonces'] = $builder->get()->getResult();



        return view('annonces', $data);
    }

}
