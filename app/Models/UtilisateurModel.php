<?php
namespace App\Models;
use CodeIgniter\Model;


class UtilisateurModel extends Model
{
    protected $table = 'utilisateur';
    protected $allowedFields = ['nom', 'email', 'mdp', 'role_id'];
}
