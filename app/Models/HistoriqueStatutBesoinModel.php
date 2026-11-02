<?php
namespace App\Models;
use CodeIgniter\Model;


class HistoriqueStatutBesoinModel extends Model
{
    protected $table = 'historique_statut_besoin';
    protected $allowedFields = ['id', 'besoin_id', 'statut_besoin_id', 'date_changement'];
}
