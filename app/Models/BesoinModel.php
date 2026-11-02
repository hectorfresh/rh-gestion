<?php
namespace App\Models;
use CodeIgniter\Model;


class BesoinModel extends Model
{
    protected $table = 'besoin';
    protected $allowedFields = ['id', 'poste_id', 'nombre', 'desce', 'statut_id', 'date_creation'];
}
