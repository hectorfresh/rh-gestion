<?php

namespace App\Controllers;

use App\Models\UtilisateurModel;
use App\Models\PosteModel;
use App\Models\BesoinModel;
use App\Models\BudgetModel;
use App\Models\RoleModel;

use App\Models\StatutBesoinModel;
use App\Models\HistoriqueStatutBesoinModel;

use App\Controllers\BaseController;

class Besoin extends BaseController
{
    public function form(): string
    {

        $model = new PosteModel();

        $data['postes'] = $model->findAll();



        return view('formulaire_besoin', $data);
    }

    
    public function insert()
    {
        $idPoste = $this->request->getPost('poste');
        $nombrePoste = $this->request->getPost('nombre');
        $description = $this->request->getPost('description');


        $statutModel = new StatutBesoinModel();

         $onlineUserRole= session()->get('user')['role_id'];
        
        $roleModel = new RoleModel();
        $dg = $roleModel->where('libelle', 'dg')->first()['id'];
        $rh = $roleModel->where('libelle', 'rh')->first()['id'];
        $manager = $roleModel->where('libelle', 'manager')->first()['id'];


        $db = \Config\Database::connect();

        
        if ($onlineUserRole==$manager) {
            $statut = $statutModel->where('libelle', 'Créé')->first();
            $req = sprintf("INSERT INTO besoin(poste_id, nombre, desce, statut_id, date_creation) VALUES(%d, %d, '%s', %d,  NOW())", 
            $idPoste, $nombrePoste, $description, $statut['id']);

            $query = $db->query($req);
            
            return "Creation de Besoin Reussie Par Manager! ";        
           
        }


        if ($onlineUserRole==$dg) {
            $statut = $statutModel->where('libelle', 'Validé Par DG')->first();
            $req = sprintf("INSERT INTO besoin(poste_id, nombre, desce, statut_id, date_creation) VALUES(%d, %d, '%s', %d,  NOW())", 
            $idPoste, $nombrePoste, $description, $statut['id']);

            $query = $db->query($req);
            
            return "Creation de Besoin Reussie Par DG ! ";        
           
        }



    }


    public function showValidationForm()
    {

        $model = new BesoinModel();

        $statutModel = new StatutBesoinModel();
        $posteModel = new PosteModel();

        
        $db = \Config\Database::connect();

        $onlineUserRole= session()->get('user')['role_id'];
        
        $roleModel = new RoleModel();
        $dg = $roleModel->where('libelle', 'dg')->first()['id'];
        $rh = $roleModel->where('libelle', 'rh')->first()['id'];


        if ($onlineUserRole==$rh) {
            $statut = $statutModel->where('libelle', 'Créé')->first()['id'];
            $query = $db->query("SELECT * FROM besoin b JOIN poste ON poste.id = b.poste_id WHERE b.statut_id = "  . $statut);
            $result = $query->getResultArray();
            $data['besoins'] = $result;
            return view("validation_besoin", $data);
        }


        if ($onlineUserRole==$dg) {
            $statut = $statutModel->findAll();
            $idValideDg = $statutModel->where('libelle', 'Validé Par DG')->first()['id'];
            $query = $db->query("SELECT * FROM besoin b JOIN poste ON poste.id = b.poste_id AND b.statut_id != " . $idValideDg);
            $result = $query->getResultArray();
            $data['besoins'] = $result;
            return view("validation_besoin", $data);
        }
        




    }

    public function fiche($id)
    {

        $db = \Config\Database::connect();

        $query = $db->query("SELECT * FROM besoin b JOIN poste ON poste.id = b.poste_id WHERE b.id = "  . $id);
        $result = $query->getRowArray();

        
        $data['besoins'] = $result;




        return view('fiche_besoin', $data);
    }


    public function valider($id)
    {
        $db = \Config\Database::connect();
        $besoinModel = new BesoinModel();
        $posteModel = new PosteModel();
        $roleModel = new RoleModel();
        $statutModel = new StatutBesoinModel();
        $budgetModel = new BudgetModel();

        $onlineUserRole= session()->get('user')['role_id'];

        $besoin = $besoinModel->where('id', $id)->first();

        $idPoste = $besoin['poste_id'];
        $nombre = $besoin['nombre'];
        $budget = $budgetModel->first()['valeur'];
        $poste = $posteModel->where('id', $idPoste)->first();
        $dg = $roleModel->where('libelle', 'dg')->first()['id'];
        $rh = $roleModel->where('libelle', 'rh')->first()['id'];


        $depensesBesoin = $poste['salaire_max'];
        $idValideRh = $statutModel->where('libelle', 'Validé Par RH')->first()['id'];
        $idValideDg = $statutModel->where('libelle', 'Validé Par DG')->first()['id'];





        if (($onlineUserRole!=null && $onlineUserRole==$rh ) && $besoin['statut_id']!=$idValideDg){

                if ($depensesBesoin * $nombre < $budget) {
                        $query = $db->query("UPDATE besoin SET statut_id = ". $idValideRh . " WHERE id = " . $id);

                        $req = sprintf("INSERT INTO historique_statut_besoin(besoin_id, statut_besoin_id, date_changement) 
                        VALUES (%d, %d, NOW() )", $id, $idValideRh);

                        $db->query($req);

                        return "Besoin Validé Par RH";


                }else{
                    $this->refuser($id);
                }
            
        }


        if ($onlineUserRole!=null && $onlineUserRole==$dg) {

                $query = $db->query("UPDATE besoin SET statut_id = ". $idValideDg . " WHERE id = " . $id);

                $req = sprintf("INSERT INTO historique_statut_besoin(besoin_id, statut_besoin_id, date_changement) 
                    VALUES (%d, %d, NOW() )", $id, $idValideDg);

                $db->query($req);

                return "Besoin Validé Par DG";


        }




    }






    public function refuser($id)
    {
        $db = \Config\Database::connect();

        $statutModel = new StatutBesoinModel();

        $statut = $statutModel->where('libelle', 'Refusé')->first();

        $query = $db->query("UPDATE besoin SET statut_id = ". $statut['id'] . " WHERE id = " . $id);

        $req = sprintf("INSERT INTO historique_statut_besoin(besoin_id, statut_besoin_id, date_changement) 
        VALUES (%d, %d, NOW() )", $id, $statut['id']);

        $db->query($req);

        return "Besoin Refusé";

    }

}
