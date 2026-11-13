<?php

$root = dirname(__DIR__);
$dbName = 'rh_block7_test';
$keep = in_array('--keep', $argv, true);

$_ENV['database.default.database'] = $dbName;
$_SERVER['database.default.database'] = $dbName;
putenv('database.default.database=' . $dbName);
$_ENV['CI_ENVIRONMENT'] = 'development';
$_SERVER['CI_ENVIRONMENT'] = 'development';

$mysqli = new mysqli('127.0.0.1', 'root', '');
if ($mysqli->connect_errno) {
    fwrite(STDERR, "Connexion MySQL impossible : {$mysqli->connect_error}\n");
    exit(1);
}

$init = file_get_contents($root . '/base/init.sql');
$data = file_get_contents($root . '/base/data.sql');
$init = str_replace(['DROP DATABASE IF EXISTS rh;', 'CREATE DATABASE rh ', 'USE rh;'], ['DROP DATABASE IF EXISTS `' . $dbName . '`;', 'CREATE DATABASE `' . $dbName . '` ', 'USE `' . $dbName . '`;'], $init);
$data = str_replace('USE rh;', 'USE `' . $dbName . '`;', $data);

foreach ([$init, $data] as $sql) {
    if (! $mysqli->multi_query($sql)) {
        fwrite(STDERR, "Erreur SQL : {$mysqli->error}\n");
        exit(1);
    }
    while ($mysqli->more_results() && $mysqli->next_result()) {
    }
    if ($mysqli->errno) {
        fwrite(STDERR, "Erreur SQL : {$mysqli->error}\n");
        exit(1);
    }
}

define('ENVIRONMENT', 'development');
define('FCPATH', $root . '/public/');
require $root . '/app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$tests = 0;
$echecs = 0;
function verifier(bool $condition, string $message): void
{
    global $tests, $echecs;
    $tests++;
    if ($condition) {
        echo "  [OK]   {$message}\n";
        return;
    }
    $echecs++;
    echo "  [KO]   {$message}\n";
}

$db = db_connect();
$conges = new App\Services\CongeService();

echo "\n-- Acquisition 2,5 jours par mois --\n";
$roleEmploye = (int) $db->table('role')->where('libelle', 'employe')->get()->getRowArray()['id'];
$db->table('utilisateur')->insert(['nom' => 'Test', 'prenom' => 'Conge', 'email' => 'conge7@mail.com', 'telephone' => '0340000777', 'mdp' => password_hash('1234', PASSWORD_DEFAULT), 'role_id' => $roleEmploye, 'actif' => 1]);
$utilisateur = $db->table('utilisateur')->where('email', 'conge7@mail.com')->get()->getRowArray();
$salarieId = (int) (new App\Models\SalarieModel())->insert([
    'utilisateur_id' => (int) $utilisateur['id'],
    'matricule' => 'EMP-2026-0099',
    'nom' => 'Test',
    'prenom' => 'Conge',
    'email' => 'conge7@mail.com',
    'telephone' => '0340000777',
    'departement_id' => 3,
    'poste_id' => 5,
    'regime_travail_id' => 1,
    'date_embauche' => '2026-03-15',
    'actif' => 1,
]);
$solde = $conges->assurerSolde($salarieId, 1, 2026);
verifier((float) $solde['jours_acquis'] === 17.5, 'Embauche le 15 mars : solde correct en novembre (17,5 j).');

echo "\n-- Chevauchement refusé --\n";
$demande1 = $conges->demander(4, 1, '2026-06-01', '2026-06-05', 'Congé 1', null, 4);
verifier($demande1['ok'], 'Première demande enregistrée.');
$chevauchement = $conges->demander(4, 1, '2026-06-03', '2026-06-07', 'Congé chevauchant', null, 4);
verifier(! $chevauchement['ok'], 'Une demande chevauchante est refusée.');

echo "\n-- Solde insuffisant refusé --\n";
$insuffisant = $conges->demander(4, 1, '2026-07-01', '2026-08-07', 'Trop de jours', null, 4);
verifier(! $insuffisant['ok'], 'Une demande supérieure au solde est refusée.');

echo "\n-- Annulation restaure solde et absences --\n";
$demande2 = $conges->demander(4, 1, '2026-08-03', '2026-08-04', 'Congé à annuler', null, 4);
verifier($demande2['ok'], 'Demande de 2 jours enregistrée.');
$validationManager = $conges->validerManager((int) $demande2['id'], 3, 'manager', 'OK');
verifier($validationManager['ok'], 'Validation manager enregistrée.');
$validationRh = $conges->validerRh((int) $demande2['id'], 2, 'rh', 'OK');
verifier($validationRh['ok'], 'Validation RH accepte la demande.');

$absences = (int) $db->table('absence')->where('salarie_id', 4)->where('motif', 'Congé #' . $demande2['id'])->countAllResults();
verifier($absences === 2, 'Deux absences sont créées à l’acceptation.');
$soldeAvant = $conges->assurerSolde(4, 1, 2026);
verifier((float) $soldeAvant['jours_pris'] >= 2, 'Le solde est décrémenté.');

$annulation = $conges->annuler((int) $demande2['id'], 4);
verifier($annulation['ok'], 'La demande acceptée peut être annulée.');
$absencesApres = (int) $db->table('absence')->where('salarie_id', 4)->where('motif', 'Congé #' . $demande2['id'])->countAllResults();
verifier($absencesApres === 0, 'Les absences sont supprimées à l’annulation.');
$soldeApres = $conges->assurerSolde(4, 1, 2026);
verifier((float) $soldeApres['jours_pris'] === 0.0, 'Le solde est restauré à l’annulation.');

echo "\n==============================================\n";
echo 'Tests exécutés : ' . $tests . ' — Échecs : ' . $echecs . "\n";
echo "==============================================\n";

if (! $keep) {
    $mysqli->query('DROP DATABASE IF EXISTS `' . $dbName . '`');
}
exit($echecs === 0 ? 0 : 1);