<?php
namespace App\Models;
use CodeIgniter\Model;

class SoldeCongeModel extends Model
{
    protected $table = 'solde_conge';
    protected $allowedFields = ['utilisateur_id', 'type_conge_id', 'annee', 'jours_acquis', 'jours_pris'];
}