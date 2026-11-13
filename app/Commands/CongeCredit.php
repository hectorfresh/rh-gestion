<?php

namespace App\Commands;

use App\Models\SalarieModel;
use App\Models\TypeCongeModel;
use App\Services\CongeService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CongeCredit extends BaseCommand
{
    protected $group = 'conges';
    protected $name = 'conges:crediter';
    protected $description = 'Crédite les congés payés (2,5 jours par mois écoulé).';

    public function run(array $params)
    {
        $conges = new CongeService();
        $salaries = (new SalarieModel())->where('actif', 1)->findAll();
        $types = (new TypeCongeModel())->findAll();
        $annee = (int) date('Y');
        $nb = 0;

        foreach ($salaries as $salarie) {
            foreach ($types as $type) {
                $conges->assurerSolde((int) $salarie['id'], (int) $type['id'], $annee);
                $nb++;
            }
        }

        CLI::write('Crédit de congés effectué pour ' . count($salaries) . ' salarié(s).', 'green');
    }
}
