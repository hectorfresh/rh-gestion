<?php
namespace App\Models;
use CodeIgniter\Model;
class cvModel extends Model{
    protected $table = 'cv';
    protected $allowedFields = ['id', 'utilisateur_id', 'titre', 'descri', 'created_at'];
}
?>