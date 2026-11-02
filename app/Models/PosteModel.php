<?php
namespace App\Models;
use CodeIgniter\Model;


class PosteModel extends Model
{
    protected $table = 'poste';
    protected $allowedFields = ['id', 'libelle', 'salaire_base', 'salaire_max'];
}
