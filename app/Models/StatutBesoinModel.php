<?php
namespace App\Models;
use CodeIgniter\Model;


class StatutBesoinModel extends Model
{
    protected $table = 'statut_besoin';
    protected $allowedFields = ['id', 'libelle'];
}
