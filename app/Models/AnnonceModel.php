<?php
namespace App\Models;
use CodeIgniter\Model;


class AnnonceModel extends Model
{
    protected $table = 'annonce';
    protected $allowedFields = ['id', 'besoin_id', 'date_creation', 'desce', 'estCloture'];

     
}
