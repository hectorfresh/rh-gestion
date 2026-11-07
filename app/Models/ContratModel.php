<?php
namespace App\Models;
use CodeIgniter\Model;

class ContratModel extends Model
{
    protected $table = 'contrat';
    protected $allowedFields = [
        'candidature_id', 'type_contrat', 'date_debut', 'date_fin',
        'salaire_propose', 'salaire_negocie', 'heures_travail',
        'teletravail', 'periode_essai', 'heures_sup', 'mutuelle',
        'avantages', 'date_creation'
    ];
}