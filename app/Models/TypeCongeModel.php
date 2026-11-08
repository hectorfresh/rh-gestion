<?php
namespace App\Models;
use CodeIgniter\Model;

class TypeCongeModel extends Model
{
    protected $table = 'type_conge';
    protected $allowedFields = ['libelle', 'jours_max_par_an', 'justificatif_requis', 'couleur'];
}