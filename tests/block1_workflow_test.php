<?php

/**
 * Test d'intégration du Bloc 1 — expression et validation du besoin.
 *
 * Usage : php tests/block1_workflow_test.php
 *
 * Le script crée une base isolée `rh_block1_test` à partir de base/init.sql et
 * base/data.sql, puis exécute les cas métier avec les vrais services.
 */

$root = dirname(__DIR__);
$dbName = 'rh_block1_test';
$keep = in_array('--keep', $argv, true);

// Un environnement dédié, sans toucher à la base de démonstration.
$_ENV['database.default.database'] = $dbName;
$_SERVER['database.default.database'] = $dbName;
putenv('database.default.database=' . $dbName);
$_ENV['CI_ENVIRONMENT'] = 'development';
$_SERVER['CI_ENVIRONMENT'] = 'development';

// ------------------------------------------------------------
// 1. Préparation de la base de test avant le démarrage de CI4
// ------------------------------------------------------------
$mysqli = new mysqli('127.0.0.1', 'root', '');
if ($mysqli->connect_errno) {
    fwrite(STDERR, "Connexion MySQL impossible : {$mysqli->connect_error}\n");
    exit(1);
}

$init = file_get_contents($root . '/base/init.sql');
$data = file_get_contents($root . '/base/data.sql');

$init = str_replace(
    ['DROP DATABASE IF EXISTS rh;', 'CREATE DATABASE rh ', 'USE rh;'],
    ['DROP DATABASE IF EXISTS `' . $dbName . '`;', 'CREATE DATABASE `' . $dbName . '` ', 'USE `' . $dbName . '`;'],
    $init
);
$data = str_replace('USE rh;', 'USE `' . $dbName . '`;', $data);

foreach ([$init, $data] as $sql) {
    if (! $mysqli->multi_query($sql)) {
        fwrite(STDERR, "Erreur SQL pendant l'initialisation : {$mysqli->error}\n");
        exit(1);
    }
    while ($mysqli->more_results() && $mysqli->next_result()) {
        // Consomme tous les résultats.
    }
    if ($mysqli->errno) {
        fwrite(STDERR, "Erreur SQL pendant l'initialisation : {$mysqli->error}\n");
        exit(1);
    }
}

// ------------------------------------------------------------
// 2. Démarrage de CodeIgniter 4 en mode console
// ------------------------------------------------------------
define('ENVIRONMENT', 'development');
define('FCPATH', $root . '/public/');
require $root . '/app/Config/Paths.php';

$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

// ------------------------------------------------------------
// 3. Petites assertions
// ------------------------------------------------------------
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

function refuser(string $message): void
{
    global $echecs;
    $echecs++;
    echo "  [KO]   {$message}\n";
}

$db = db_connect();
$besoinService = new App\Services\BesoinService();
$workflow = new App\Services\WorkflowService();

$manager = $db->table('utilisateur')->where('email', 'manager@mail.com')->get()->getRowArray();
$rh      = $db->table('utilisateur')->where('email', 'rh@mail.com')->get()->getRowArray();
$dg      = $db->table('utilisateur')->where('email', 'dg@mail.com')->get()->getRowArray();

$managerId = (int) $manager['id'];
$rhId      = (int) $rh['id'];
$dgId      = (int) $dg['id'];

$deptInfo  = (int) $db->table('departement')->where('code', 'INFO')->get()->getRowArray()['id'];
$deptRh    = (int) $db->table('departement')->where('code', 'RH')->get()->getRowArray()['id'];
$posteJr   = (int) $db->table('poste')->where('libelle', 'Développeur junior')->get()->getRowArray()['id'];
$posteRh   = (int) $db->table('poste')->where('libelle', 'Responsable RH')->get()->getRowArray()['id'];
$annee     = (int) date('Y');
$dateLibre = date('Y-m-d', strtotime('+20 days'));

$statut = static function (string $libelle) use ($db): int {
    return (int) ($db->table('statut_besoin')->where('libelle', $libelle)->get()->getRowArray()['id'] ?? 0);
};

$besoin = static function (int $id) use ($db): ?array {
    return $db->table('besoin b')
        ->select('b.*, sb.libelle AS statut')
        ->join('statut_besoin sb', 'sb.id = b.statut_id')
        ->where('b.id', $id)
        ->get()->getRowArray();
};

$budgetInfo = $db->table('budget_departement')
    ->where('departement_id', $deptInfo)
    ->where('annee', $annee)
    ->get()->getRowArray();

$budgetInitial = (float) ($budgetInfo['montant_annuel'] ?? 0);

echo "\n=== Bloc 1 : règles de création et de validation du besoin ===\n";

// ------------------------------------------------------------
// Cas 0 : contrôles de portée et de fourchette
// ------------------------------------------------------------
echo "\n-- Portée manager et fourchette salariale --\n";

$resultat = $besoinService->creer([
    'poste_id'       => $posteRh,
    'departement_id' => $deptRh,
    'nombre'         => 1,
    'type_besoin'    => 'creation',
    'salaire_estime' => 1500000,
    'date_souhaitee' => $dateLibre,
], $manager);
verifier(! $resultat['ok'], 'Un manager ne peut pas créer un besoin dans un autre département.');

$resultat = $besoinService->creer([
    'poste_id'       => $posteJr,
    'departement_id' => $deptInfo,
    'nombre'         => 1,
    'type_besoin'    => 'creation',
    'salaire_estime' => 900000,
    'date_souhaitee' => $dateLibre,
], $manager);
verifier(! $resultat['ok'], 'Un salaire hors fourchette est refusé.');

// ------------------------------------------------------------
// Cas 1 : création manager, finance automatique à la soumission
// ------------------------------------------------------------
echo "\n-- Création manager + finance automatique --\n";

$budgetInfo = $db->table('budget_departement')
    ->where('departement_id', $deptInfo)
    ->where('annee', $annee)
    ->get()->getRowArray();

if (! $budgetInfo) {
    $db->table('budget_departement')->insert([
        'departement_id' => $deptInfo,
        'annee'          => $annee,
        'montant_annuel' => $budgetInitial,
    ]);
}

$resultat = $besoinService->creer([
    'poste_id'       => $posteJr,
    'departement_id' => $deptInfo,
    'nombre'         => 1,
    'type_besoin'    => 'creation',
    'motif'          => 'Test finance automatique',
    'salaire_estime' => '', // doit prendre salaire_base du poste
    'date_souhaitee' => $dateLibre,
], $manager);

verifier($resultat['ok'], 'Création d’un besoin manager.');
$idFinance = (int) ($resultat['id'] ?? 0);
$ligne = $besoin($idFinance);
verifier($ligne !== null && $ligne['statut'] === 'En attente RH', 'Le besoin soumis est au statut « En attente RH ».');
verifier((float) $ligne['salaire_estime'] === 400000.0, 'Le salaire estimé vide prend le salaire de base du poste.');

$decisions = $workflow->decisionsBesoin($idFinance);
verifier(($decisions['finance']['decision'] ?? null) === 'accepte', 'L’étape finance est calculée automatiquement à la soumission.');
verifier(! empty($decisions['finance']['details_json']), 'L’instantané finance est enregistré.');

// ------------------------------------------------------------
// Cas 2 : RH refuse + finance refuse + DG accepte = Validé
// ------------------------------------------------------------
echo "\n-- RH refuse + finance refuse + DG accepte --\n";

// Budget à zéro pour forcer le refus finance.
$db->table('budget_departement')
    ->where('departement_id', $deptInfo)
    ->where('annee', $annee)
    ->update(['montant_annuel' => 0]);

$resultat = $besoinService->creer([
    'poste_id'       => $posteJr,
    'departement_id' => $deptInfo,
    'nombre'         => 1,
    'type_besoin'    => 'creation',
    'motif'          => 'Test refus finance',
    'salaire_estime' => 400000,
    'date_souhaitee' => $dateLibre,
], $manager);
$idRefus = (int) ($resultat['id'] ?? 0);

$decisions = $workflow->decisionsBesoin($idRefus);
verifier(($decisions['finance']['decision'] ?? null) === 'refuse', 'La finance refuse automatiquement faute de budget.');

$resultat = $workflow->deciderBesoin($idRefus, $rhId, 'rh', 'refuse', '');
verifier(! $resultat['ok'], 'Le refus RH sans commentaire est refusé par le service.');

$resultat = $workflow->deciderBesoin($idRefus, $rhId, 'rh', 'refuse', 'Refus RH motivé : priorité budgétaire.');
verifier($resultat['ok'], 'Le refus RH avec commentaire est enregistré.');
verifier($besoin($idRefus)['statut'] === 'En attente DG', 'Après RH, le besoin passe en « En attente DG ».');

$resultat = $workflow->deciderBesoin($idRefus, $dgId, 'dg', 'accepte', 'Le DG passe outre les avis RH et finance.');
verifier($resultat['ok'], 'Le DG peut accepter malgré les refus RH et finance.');
verifier($besoin($idRefus)['statut'] === 'Validé', 'La décision DG l’emporte : statut « Validé ».');

// Restaure le budget initial pour les scénarios suivants.
$db->table('budget_departement')
    ->where('departement_id', $deptInfo)
    ->where('annee', $annee)
    ->update(['montant_annuel' => $budgetInitial]);

// ------------------------------------------------------------
// Cas 3 : RH accepte + finance accepte + DG refuse = Refusé
// ------------------------------------------------------------
echo "\n-- RH accepte + finance accepte + DG refuse --\n";

$resultat = $besoinService->creer([
    'poste_id'       => $posteJr,
    'departement_id' => $deptInfo,
    'nombre'         => 1,
    'type_besoin'    => 'creation',
    'motif'          => 'Test refus DG',
    'salaire_estime' => 400000,
    'date_souhaitee' => $dateLibre,
], $manager);
$idRefusDg = (int) ($resultat['id'] ?? 0);

$decisions = $workflow->decisionsBesoin($idRefusDg);
verifier(($decisions['finance']['decision'] ?? null) === 'accepte', 'La finance accepte avec un budget suffisant.');

$workflow->deciderBesoin($idRefusDg, $rhId, 'rh', 'accepte', 'Avis RH favorable.');
$resultat = $workflow->deciderBesoin($idRefusDg, $dgId, 'dg', 'refuse', 'Le DG refuse malgré les avis favorables.');
verifier($resultat['ok'], 'Le refus final du DG est enregistré.');
verifier($besoin($idRefusDg)['statut'] === 'Refusé', 'La décision DG l’emporte : statut « Refusé ».');

// ------------------------------------------------------------
// Cas 4 : tout accepté = Validé
// ------------------------------------------------------------
echo "\n-- Tout accepté --\n";

$resultat = $besoinService->creer([
    'poste_id'       => $posteJr,
    'departement_id' => $deptInfo,
    'nombre'         => 1,
    'type_besoin'    => 'creation',
    'motif'          => 'Test validation complète',
    'salaire_estime' => 400000,
    'date_souhaitee' => $dateLibre,
], $manager);
$idValide = (int) ($resultat['id'] ?? 0);

verifier(($workflow->decisionsBesoin($idValide)['finance']['decision'] ?? null) === 'accepte', 'Finance favorable.');
$workflow->deciderBesoin($idValide, $rhId, 'rh', 'accepte', 'Avis RH favorable.');
$resultat = $workflow->deciderBesoin($idValide, $dgId, 'dg', 'accepte', 'Validation finale.');
verifier($resultat['ok'], 'Le DG valide le besoin.');
verifier($besoin($idValide)['statut'] === 'Validé', 'Le besoin est validé.');

// ------------------------------------------------------------
// Cas 5 : besoin créé par le DG = acceptation d'office
// ------------------------------------------------------------
echo "\n-- Besoin créé par le DG --\n";

$resultat = $besoinService->creer([
    'poste_id'       => $posteRh,
    'departement_id' => $deptRh,
    'nombre'         => 1,
    'type_besoin'    => 'creation',
    'motif'          => 'Test création DG',
    'salaire_estime' => 1500000,
    'date_souhaitee' => $dateLibre,
], $dg);
verifier($resultat['ok'], 'Le DG peut créer un besoin dans n’importe quel département.');
$idDg = (int) ($resultat['id'] ?? 0);
verifier($besoin($idDg)['statut'] === 'Validé', 'Le besoin créé par le DG est validé d’office.');

$decisionsDg = $workflow->decisionsBesoin($idDg);
verifier((int) ($decisionsDg['rh']['automatique'] ?? 0) === 1, 'L’avis RH est enregistré automatiquement pour information.');
verifier((int) ($decisionsDg['dg']['automatique'] ?? 0) === 1, 'La décision DG est enregistrée comme acceptation d’office.');

// ------------------------------------------------------------
// Cas 6 : portée de lecture manager
// ------------------------------------------------------------
echo "\n-- Portée de lecture --\n";

$listeManager = $besoinService->lister($managerId);
$tousAuManager = true;
foreach ($listeManager as $ligneManager) {
    if ((int) $ligneManager['demandeur_id'] !== $managerId) {
        $tousAuManager = false;
    }
}
verifier($tousAuManager, 'Un manager ne voit que ses propres besoins.');

// ------------------------------------------------------------
// Cas 7 : interdiction de décider sur sa propre demande
// ------------------------------------------------------------
echo "\n-- Interdiction de décider sur sa propre demande --\n";

$db->table('besoin')->insert([
    'poste_id'       => $posteJr,
    'departement_id' => $deptInfo,
    'demandeur_id'   => $dgId,
    'nombre'         => 1,
    'type_besoin'    => 'creation',
    'motif'          => 'Test décision sur sa propre demande',
    'salaire_estime' => 400000,
    'date_souhaitee' => $dateLibre,
    'statut_id'      => $statut('En attente DG'),
    'date_creation'  => date('Y-m-d H:i:s'),
]);
$idPropre = (int) $db->insertID();

$db->table('workflow_validation')->insertBatch([
    [
        'objet_type' => 'besoin', 'objet_id' => $idPropre, 'etape' => 'rh',
        'decision' => 'accepte', 'valideur_id' => $rhId, 'automatique' => 0,
        'commentaire' => 'Avis RH.', 'details_json' => null, 'date_decision' => date('Y-m-d H:i:s'),
    ],
    [
        'objet_type' => 'besoin', 'objet_id' => $idPropre, 'etape' => 'finance',
        'decision' => 'accepte', 'valideur_id' => null, 'automatique' => 1,
        'commentaire' => 'Finance.', 'details_json' => null, 'date_decision' => date('Y-m-d H:i:s'),
    ],
    [
        'objet_type' => 'besoin', 'objet_id' => $idPropre, 'etape' => 'dg',
        'decision' => 'en_attente', 'valideur_id' => null, 'automatique' => 0,
        'commentaire' => 'En attente.', 'details_json' => null, 'date_decision' => null,
    ],
]);

$resultat = $workflow->deciderBesoin($idPropre, $dgId, 'dg', 'accepte', 'Tentative interdite.');
verifier(! $resultat['ok'], 'Un utilisateur ne peut pas décider sur sa propre demande.');

// ------------------------------------------------------------
// Nettoyage optionnel
// ------------------------------------------------------------
$db->close();
if (! $keep) {
    $mysqli->query('DROP DATABASE IF EXISTS `' . $dbName . '`');
}
$mysqli->close();

echo "\n==============================================\n";
echo "Tests exécutés : {$tests} — Échecs : {$echecs}\n";
echo "==============================================\n";

exit($echecs === 0 ? 0 : 1);
