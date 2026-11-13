<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Modèle de base commun à tous les modèles de l'application.
 * Les contrôleurs restent minces : la logique métier vit dans app/Services.
 */
abstract class BaseModel extends Model
{
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $protectFields = false;
}
