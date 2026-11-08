<?php
namespace App\Models;
use CodeIgniter\Model;

class DemandeCongeModel extends Model
{
    protected $table = 'demande_conge';
    protected $allowedFields = [
        'utilisateur_id', 'type_conge_id', 'date_debut', 'date_fin', 
        'nombre_jours', 'motif', 'statut_id', 'date_demande', 
        'date_traitement', 'traite_par', 'commentaire_validation'
    ];
}