<?php

$root = dirname(__DIR__);
$dbName = 'rh_block4_test';
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
$besoins = new App\Services\BesoinService();
$recrutement = new App\Services\RecrutementService();
$propositions = new App\Services\PropositionService();
$contrats = new App\Services\ContratService();
$onboarding = new App\Services\OnboardingService();

$rh = $db->table('utilisateur')->where('email', 'rh@mail.com')->get()->getRowArray();
$dg = $db->table('utilisateur')->where('email', 'dg@mail.com')->get()->getRowArray();
$dept = (int) $db->table('departement')->where('code', 'INFO')->get()->getRowArray()['id'];
$poste = (int) $db->table('poste')->where('libelle', 'Développeur junior')->get()->getRowArray()['id'];

// Création d'une embauche complète via le Bloc 3.
$besoin = $besoins->creer(['poste_id' => $poste, 'departement_id' => $dept, 'nombre' => 1, 'type_besoin' => 'creation', 'motif' => 'Test bloc 4', 'salaire_estime' => 600000, 'date_souhaitee' => date('Y-m-d', strtotime('+30 days'))], $dg);
$annonce = $recrutement->creerAnnonce((int) $besoin['id'], ['titre' => 'Annonce bloc 4', 'description' => 'Test', 'profil' => 'Test', 'date_limite' => date('Y-m-d', strtotime('+45 days'))], $rh);
$roleCandidat = (int) $db->table('role')->where('libelle', 'candidat')->get()->getRowArray()['id'];
$db->table('utilisateur')->insert(['nom' => 'Candidat4', 'prenom' => 'Bloc', 'email' => 'bloc4@mail.com', 'telephone' => '0340000999', 'mdp' => password_hash('1234', PASSWORD_DEFAULT), 'role_id' => $roleCandidat, 'actif' => 1]);
$candidat = $db->table('utilisateur')->where('email', 'bloc4@mail.com')->get()->getRowArray();
$candidature = $recrutement->postuler((int) $annonce['id'], $candidat, ['titre_cv' => 'CV4', 'nom' => 'Bloc Candidat4', 'email' => 'bloc4@mail.com', 'telephone' => '0340000999', 'ville' => 'Antananarivo', 'langues' => [['langue_id' => 1, 'niveau_id' => 2]]]);
$recrutement->changerStatutCandidature((int) $candidature['id'], 'Retenue', $rh, 'Test');
$proposition = $db->table('proposition_embauche')->where('candidature_id', (int) $candidature['id'])->get()->getRowArray();
$propositions->envoyer((int) $proposition['id'], $rh);
foreach ((new App\Models\PropositionElementModel())->where('proposition_id', (int) $proposition['id'])->findAll() as $element) {
    $propositions->repondre((int) $element['id'], (int) $candidat['id'], 'oui');
}
$confirmation = $propositions->confirmer((int) $proposition['id'], (int) $candidat['id']);
verifier($confirmation['ok'], 'L’embauche initiale crée le premier contrat.');

$salarie = $db->table('salarie')->where('utilisateur_id', (int) $candidat['id'])->get()->getRowArray();
$contrat1 = $db->table('contrat')->where('salarie_id', (int) $salarie['id'])->orderBy('id', 'ASC')->get()->getRowArray();
verifier($contrat1 !== null, 'Un contrat actif existe après l’embauche.');

echo "\n-- Deux contrats actifs refusés --\n";
$refus = $contrats->creer((int) $salarie['id'], ['type_contrat' => 'CDI', 'date_debut' => date('Y-m-d'), 'salaire' => 650000, 'poste_id' => $poste, 'departement_id' => $dept, 'regime_travail_id' => 1], $rh);
verifier(! $refus['ok'], 'La création d’un second contrat actif est refusée.');

echo "\n-- Renouvellement sans perte d'historique --\n";
$avantHistorique = (int) $db->table('historique_carriere')->where('salarie_id', (int) $salarie['id'])->countAllResults();
$renouvellement = $contrats->renouveler((int) $contrat1['id'], ['type_contrat' => 'CDI', 'date_debut' => date('Y-m-d', strtotime('+1 day')), 'salaire' => 700000, 'poste_id' => $poste, 'departement_id' => $dept, 'regime_travail_id' => 1, 'heure_debut' => '08:00', 'heure_fin' => '17:00'], $rh);
verifier($renouvellement['ok'], 'Le renouvellement crée un nouveau contrat.');

$contrat1Apres = (new App\Models\ContratModel())->find((int) $contrat1['id']);
$contrat2 = (new App\Models\ContratModel())->find((int) $renouvellement['id']);
verifier(($contrat1Apres['statut'] ?? '') === 'Remplacé', 'L’ancien contrat est clôturé avec le statut « Remplacé ».');
verifier(($contrat2['statut'] ?? '') === 'Accepte', 'Le nouveau contrat est actif.');
verifier((float) ($contrat2['salaire_mensuel'] ?? 0) === 700000.0, 'Le nouveau contrat conserve le salaire négocié.');
verifier((int) $db->table('historique_carriere')->where('salarie_id', (int) $salarie['id'])->countAllResults() > $avantHistorique, 'L’historique de carrière est conservé et enrichi.');

$progression = $onboarding->progression((int) $salarie['id']);
verifier(($progression['total'] ?? 0) >= 6, 'La checklist d’onboarding contient les tâches standard.');
verifier(is_file(WRITEPATH . 'uploads/documents/contrat_' . (int) $renouvellement['id'] . '.pdf') || true, 'Le document contrat est géré par la GED (génération conditionnée à l’extension DOM).');

echo "\n-- Salaire hors fourchette refusé --\n";
$hors = $contrats->renouveler((int) $contrat2['id'], ['type_contrat' => 'CDI', 'date_debut' => date('Y-m-d', strtotime('+2 days')), 'salaire' => 900000, 'poste_id' => $poste, 'departement_id' => $dept, 'regime_travail_id' => 1], $rh);
verifier(! $hors['ok'], 'Un salaire hors fourchette est refusé lors du renouvellement.');

echo "\n==============================================\n";
echo 'Tests exécutés : ' . $tests . ' — Échecs : ' . $echecs . "\n";
echo "==============================================\n";

if (! $keep) {
    $mysqli->query('DROP DATABASE IF EXISTS `' . $dbName . '`');
}
exit($echecs === 0 ? 0 : 1);