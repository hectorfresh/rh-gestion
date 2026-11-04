<?php
namespace App\Models;
use CodeIgniter\Model;

class CandidatureModel extends Model{
    protected $table = 'candidature';
    protected $allowedFields = ['id', 'annonce_id', 'utilisateur_id', 'date_candidature'];
}



