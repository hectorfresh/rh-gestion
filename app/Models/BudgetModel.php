<?php
namespace App\Models;
use CodeIgniter\Model;


class BudgetModel extends Model
{
    protected $table = 'budget';
    protected $allowedFields = ['id', 'valeur'];
}
