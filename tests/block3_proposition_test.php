<?php

/**
 * Test d'intégration du Bloc 3 — proposition d'embauche et confirmation.
 *
 * Usage : php tests/block3_proposition_test.php
 *
 * Le script crée une base isolée `rh_block3_test` à partir de base/init.sql et
 * base/data.sql, puis exécute les règles métier avec les vrais services.
 */

$root = dirname(__DIR__);
$dbName = 'rh_block3_test';
$keep = in_array('--keep', $argv, true);

$_ENV['database.default.database'] = $dbName;
$_SERVER['database.default.database'] = $dbName;
putenv('database.default.database=' . $dbName);
$_ENV['CI_ENVIRONMENT'] = 'development';
$_SERVER['CI_ENVIRONMENT'] = 'development';

// ------------------------------------------------------------
// 1. Base de test isolée
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
// 2. Démarrage de CodeIgniter 4
// ------------------------------------------------------------
define('ENVIRONMENT', 'development');
define('FCPATH', $root . '/public/');
require $root . '/app/Config/Paths.php';

$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

// ------------------------------------------------------------
// 3. Assertions et helpers
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

function statutCandidature(int $id): ?string
{
    $row = db_connect()->table('candidature c')
        ->select('sc.libelle')
        ->join('statut_candidature sc', 'sc.id = c.statut_id')
        ->where('c.id', $id)
        ->get()->getRowArray();

    return $row['libelle'] ?? null;
}

function chargerProposition(int $id): ?array
{
    return db_connect()->table('proposition_embauche')->where('id', $id)->get()->getRowArray() ?: null;
}

function elementsParCode(int $propositionId): array
{
    $resultat = [];
    foreach ((new App\Models\PropositionElementModel())->where('proposition_id', $propositionId)->findAll() as $element) {
        $resultat[$element['code']] = $element;
    }

    return $resultat;
}

function decisionFinance(int $elementId): ?array
{
    return db_connect()->table('workflow_validation')
        ->where('objet_type', 'proposition_element')
        ->where('objet_id', $elementId)
        ->where('etape', 'finance')
        ->get()->getRowArray() ?: null;
}

function repondreOui(App\Services\PropositionService $service, int $elementId, int $candidatId): array
{
    return $service->repondre($elementId, $candidatId, 'oui');
}

/**
 * Crée un besoin validé, une annonce, une candidature et la proposition
 * brouillon générée par le passage au statut « Retenue ».
 */
function creerScenario(
    int $numero,
    $db,
    App\Services\BesoinService $besoins,
    App\Services\RecrutementService $recrutement,
    array $rh,
    array $dg,
    int $deptId,
    int $posteId
): array {
    $email = 'bloc3.' . $numero . '@mail.com';
    $dateSouhaitee = date('Y-m-d', strtotime('+30 days'));

    $besoin = $besoins->creer([
        'poste_id'       => $posteId,
        'departement_id' => $deptId,
        'nombre'         => 1,
        'type_besoin'    => 'creation',
        'motif'          => 'Scénario Bloc 3 #' . $numero,
        'salaire_estime' => 600000,
        'date_souhaitee' => $dateSouhaitee,
    ], $dg);

    if (! $besoin['ok']) {
        throw new RuntimeException('Création du besoin impossible : ' . $besoin['message']);
    }

    $annonce = $recrutement->creerAnnonce((int) $besoin['id'], [
        'titre'       => 'Annonce Bloc 3 #' . $numero,
        'description' => 'Scénario de test de la proposition.',
        'profil'      => 'Profil de test.',
        'date_limite' => date('Y-m-d', strtotime('+45 days')),
    ], $rh);

    if (! $annonce['ok']) {
        throw new RuntimeException('Création de l’annonce impossible : ' . $annonce['message']);
    }

    $roleCandidat = (int) $db->table('role')->where('libelle', 'candidat')->get()->getRowArray()['id'];
    $db->table('utilisateur')->insert([
        'nom'       => 'Candidat' . $numero,
        'prenom'    => 'Bloc3',
        'email'     => $email,
        'telephone' => '03400001' . str_pad((string) $numero, 2, '0', STR_PAD_LEFT),
        'mdp'       => password_hash('1234', PASSWORD_DEFAULT),
        'role_id'   => $roleCandidat,
        'actif'     => 1,
    ]);
    $candidat = $db->table('utilisateur')->where('email', $email)->get()->getRowArray();

    $candidature = $recrutement->postuler((int) $annonce['id'], $candidat, [
        'titre_cv'  => 'CV Bloc 3 #' . $numero,
        'nom'       => 'Bloc3 Candidat' . $numero,
        'email'     => $email,
        'telephone' => $candidat['telephone'],
        'ville'     => 'Antananarivo',
        'langues'   => [['langue_id' => 1, 'niveau_id' => 2]],
    ]);

    if (! $candidature['ok']) {
        throw new RuntimeException('Candidature impossible : ' . $candidature['message']);
    }

    $retenue = $recrutement->changerStatutCandidature((int) $candidature['id'], 'Retenue', $rh, 'Scénario Bloc 3.');
    if (! $retenue['ok']) {
        throw new RuntimeException('Passage à Retenue impossible : ' . $retenue['message']);
    }

    $proposition = $db->table('proposition_embauche')
        ->where('candidature_id', (int) $candidature['id'])
        ->get()->getRowArray();

    if (! $proposition) {
        throw new RuntimeException('La proposition automatique n’a pas été créée.');
    }

    return [
        'candidat'     => $candidat,
        'candidature'  => $db->table('candidature')->where('id', (int) $candidature['id'])->get()->getRowArray(),
        'proposition'  => $proposition,
    ];
}

$db = db_connect();
$besoins = new App\Services\BesoinService();
$recrutement = new App\Services\RecrutementService();
$propositions = new App\Services\PropositionService();
$workflow = new App\Services\WorkflowService();

$rh = $db->table('utilisateur')->where('email', 'rh@mail.com')->get()->getRowArray();
$dg = $db->table('utilisateur')->where('email', 'dg@mail.com')->get()->getRowArray();
$deptInfo = (int) $db->table('departement')->where('code', 'INFO')->get()->getRowArray()['id'];
$posteJr = (int) $db->table('poste')->where('libelle', 'Développeur junior')->get()->getRowArray()['id'];

echo "\n=== Bloc 3 : proposition d’embauche et confirmation ===\n";

// ------------------------------------------------------------
// Cas 1 : tout OUI → contrat généré
// ------------------------------------------------------------
echo "\n-- Tout OUI : contrat généré --\n";

$scenario1 = creerScenario(1, $db, $besoins, $recrutement, $rh, $dg, $deptInfo, $posteJr);
$prop1 = $scenario1['proposition'];
$candidat1 = $scenario1['candidat'];
$elements1 = elementsParCode((int) $prop1['id']);

verifier($prop1['date_envoi'] === null, 'La proposition est créée en brouillon, sans envoi automatique au candidat.');
verifier((float) $elements1['salaire']['valeur_proposee'] === 600000.0, 'Le salaire pré-rempli est le milieu de la fourchette (400 000 – 800 000).');

$avantEnvoi = $propositions->repondre((int) $elements1['salaire']['id'], (int) $candidat1['id'], 'oui');
verifier(! $avantEnvoi['ok'], 'Le candidat ne peut pas répondre avant l’envoi de la proposition.');

$envoi1 = $propositions->envoyer((int) $prop1['id'], $rh);
verifier($envoi1['ok'], 'Le RH envoie la proposition au candidat.');
$prop1 = chargerProposition((int) $prop1['id']);
verifier($prop1['date_envoi'] !== null, 'La date d’envoi est enregistrée.');

foreach (elementsParCode((int) $prop1['id']) as $element) {
    $reponse = repondreOui($propositions, (int) $element['id'], (int) $candidat1['id']);
    if (! $reponse['ok']) {
        throw new RuntimeException('Réponse OUI impossible : ' . $reponse['message']);
    }
}

$prop1 = chargerProposition((int) $prop1['id']);
verifier($prop1['statut'] === 'confirmable', 'Tous les éléments acceptés rendent la proposition confirmable.');

$confirmation1 = $propositions->confirmer((int) $prop1['id'], (int) $candidat1['id']);
verifier($confirmation1['ok'], 'La confirmation génère le contrat.');

$contrat1 = $db->table('contrat')->where('candidature_id', (int) $scenario1['candidature']['id'])->get()->getRowArray();
verifier($contrat1 !== null && (float) $contrat1['salaire_mensuel'] === 600000.0, 'Le contrat reprend le salaire final accepté.');
verifier($contrat1 !== null && $contrat1['type_contrat'] === 'CDI', 'Le contrat reprend le type de contrat final.');

$salarie1 = $db->table('salarie')->where('utilisateur_id', (int) $candidat1['id'])->get()->getRowArray();
verifier($salarie1 !== null, 'Une fiche salarié est créée.');
verifier($salarie1 !== null && preg_match('/^EMP-\d{4}-0001$/', $salarie1['matricule']) === 1, 'Le matricule suit le format EMP-AAAA-0001.');
verifier($salarie1 !== null && $salarie1['candidature_id'] === $scenario1['candidature']['id'], 'La fiche salarié est reliée à la candidature.');

$roleEmploye = $db->table('utilisateur u')
    ->select('r.libelle AS role')
    ->join('role r', 'r.id = u.role_id')
    ->where('u.id', (int) $candidat1['id'])
    ->get()->getRowArray();
verifier(($roleEmploye['role'] ?? '') === 'employe', 'Le rôle du candidat devient employé.');
verifier($db->table('affectation')->where('salarie_id', (int) $salarie1['id'])->countAllResults() === 1, 'Une affectation initiale est créée.');
verifier($db->table('historique_carriere')->where('salarie_id', (int) $salarie1['id'])->where('type_evenement', 'embauche')->countAllResults() === 1, 'La ligne historique « embauche » est créée.');

$onboarding = $db->table('onboarding')->where('salarie_id', (int) $salarie1['id'])->get()->getRowArray();
verifier($onboarding !== null, 'Un onboarding est créé.');
verifier($onboarding !== null && $db->table('onboarding_tache')->where('onboarding_id', (int) $onboarding['id'])->countAllResults() >= 3, 'Les tâches standard d’onboarding sont créées.');
verifier(statutCandidature((int) $scenario1['candidature']['id']) === 'Embauchée', 'La candidature passe à « Embauchée ».');
verifier($db->table('solde_conge')->where('salarie_id', (int) $salarie1['id'])->where('annee', (int) date('Y'))->countAllResults() === 1, 'Le crédit initial de congés est initialisé.');

// ------------------------------------------------------------
// Cas 2 : un NON → aucun contrat + ajustement RH du brouillon
// ------------------------------------------------------------
echo "\n-- Un NON : aucun contrat --\n";

$scenario2 = creerScenario(2, $db, $besoins, $recrutement, $rh, $dg, $deptInfo, $posteJr);
$prop2 = $scenario2['proposition'];
$candidat2 = $scenario2['candidat'];

$horsFourchette = $propositions->mettreAJour((int) $prop2['id'], ['salaire' => 900000], $rh, false);
verifier(! $horsFourchette['ok'], 'Le RH ne peut pas ajuster le salaire au-delà de la fourchette.');
$ajustement = $propositions->mettreAJour((int) $prop2['id'], ['salaire' => 650000], $rh, true);
verifier($ajustement['ok'], 'Le RH ajuste le salaire dans la fourchette et envoie la proposition.');
$elements2 = elementsParCode((int) $prop2['id']);
verifier((float) $elements2['salaire']['valeur_proposee'] === 650000.0, 'La valeur ajustée par le RH est conservée avant l’envoi.');

foreach ($elements2 as $code => $element) {
    if ($code === 'salaire') {
        continue;
    }
    $reponse = repondreOui($propositions, (int) $element['id'], (int) $candidat2['id']);
    if (! $reponse['ok']) {
        throw new RuntimeException('Réponse OUI impossible : ' . $reponse['message']);
    }
}
$non2 = $propositions->repondre((int) $elements2['salaire']['id'], (int) $candidat2['id'], 'non', '750000', 'Je demande un meilleur salaire.');
verifier($non2['ok'], 'Le candidat peut refuser un élément et envoyer une contre-proposition motivée.');

$prop2 = chargerProposition((int) $prop2['id']);
verifier($prop2['statut'] !== 'confirmable', 'Un NON non tranché empêche la proposition d’être confirmable.');
$confirmation2 = $propositions->confirmer((int) $prop2['id'], (int) $candidat2['id']);
verifier(! $confirmation2['ok'], 'La confirmation est refusée tant que tous les éléments ne sont pas acceptés.');
verifier($db->table('contrat')->where('candidature_id', (int) $scenario2['candidature']['id'])->countAllResults() === 0, 'Aucun contrat n’est généré après un NON.');

// ------------------------------------------------------------
// Cas 3 : DG accepte malgré RH et finance négatifs
// ------------------------------------------------------------
echo "\n-- Contre-proposition acceptée par le DG malgré RH et finance négatifs --\n";

$scenario3 = creerScenario(3, $db, $besoins, $recrutement, $rh, $dg, $deptInfo, $posteJr);
$prop3 = $scenario3['proposition'];
$candidat3 = $scenario3['candidat'];
$propositions->envoyer((int) $prop3['id'], $rh);
$elements3 = elementsParCode((int) $prop3['id']);

foreach ($elements3 as $code => $element) {
    if ($code === 'salaire') {
        continue;
    }
    repondreOui($propositions, (int) $element['id'], (int) $candidat3['id']);
}
$contre3 = $propositions->repondre((int) $elements3['salaire']['id'], (int) $candidat3['id'], 'non', '900000', 'Mon expérience justifie ce salaire.');
verifier($contre3['ok'], 'Le candidat propose 900 000 Ar, hors fourchette du poste.');

$rh3 = $workflow->deciderElement((int) $elements3['salaire']['id'], (int) $rh['id'], 'rh', 'refuse', 'Hors fourchette.');
verifier($rh3['ok'], 'Le RH refuse la contre-proposition.');
$finance3 = decisionFinance((int) $elements3['salaire']['id']);
verifier(($finance3['decision'] ?? '') === 'refuse', 'La finance refuse automatiquement la contre-proposition hors fourchette.');

$dg3 = $workflow->deciderElement((int) $elements3['salaire']['id'], (int) $dg['id'], 'dg', 'accepte', 'Décision finale : acceptation malgré les avis.');
verifier($dg3['ok'], 'Le DG accepte malgré le RH et la finance négatifs.');

$prop3 = chargerProposition((int) $prop3['id']);
$elements3 = elementsParCode((int) $prop3['id']);
verifier($elements3['salaire']['statut'] === 'contre_validee', 'La contre-proposition devient validée.');
verifier((float) $elements3['salaire']['valeur_finale'] === 900000.0, 'L’élément prend la valeur du candidat.');
verifier($prop3['statut'] === 'confirmable', 'La proposition est confirmable malgré les avis négatifs.');

$confirmation3 = $propositions->confirmer((int) $prop3['id'], (int) $candidat3['id']);
verifier($confirmation3['ok'], 'La confirmation aboutit avec la valeur validée par le DG.');
$contrat3 = $db->table('contrat')->where('candidature_id', (int) $scenario3['candidature']['id'])->get()->getRowArray();
verifier($contrat3 !== null && (float) $contrat3['salaire_mensuel'] === 900000.0, 'Le contrat reprend la valeur de la contre-proposition validée.');

// ------------------------------------------------------------
// Cas 4 : DG refuse malgré RH et finance positifs
// ------------------------------------------------------------
echo "\n-- Contre-proposition refusée par le DG malgré RH et finance positifs --\n";

$scenario4 = creerScenario(4, $db, $besoins, $recrutement, $rh, $dg, $deptInfo, $posteJr);
$prop4 = $scenario4['proposition'];
$candidat4 = $scenario4['candidat'];
$propositions->envoyer((int) $prop4['id'], $rh);
$elements4 = elementsParCode((int) $prop4['id']);

foreach ($elements4 as $code => $element) {
    if ($code === 'salaire') {
        continue;
    }
    repondreOui($propositions, (int) $element['id'], (int) $candidat4['id']);
}
$contre4 = $propositions->repondre((int) $elements4['salaire']['id'], (int) $candidat4['id'], 'non', '700000', 'Je souhaite 700 000 Ar.');
verifier($contre4['ok'], 'Le candidat propose 700 000 Ar, dans la fourchette.');

$rh4 = $workflow->deciderElement((int) $elements4['salaire']['id'], (int) $rh['id'], 'rh', 'accepte', 'Compatible.');
verifier($rh4['ok'], 'Le RH accepte la contre-proposition.');
$finance4 = decisionFinance((int) $elements4['salaire']['id']);
verifier(($finance4['decision'] ?? '') === 'accepte', 'La finance accepte la contre-proposition.');

$dg4 = $workflow->deciderElement((int) $elements4['salaire']['id'], (int) $dg['id'], 'dg', 'refuse', 'Décision finale : refus.');
verifier($dg4['ok'], 'Le DG refuse malgré le RH et la finance positifs.');

$prop4 = chargerProposition((int) $prop4['id']);
$elements4 = elementsParCode((int) $prop4['id']);
verifier($elements4['salaire']['statut'] === 'contre_refusee', 'La contre-proposition est refusée.');
verifier($prop4['statut'] !== 'confirmable', 'La proposition n’est pas confirmable après le refus du DG.');
verifier(! $propositions->confirmer((int) $prop4['id'], (int) $candidat4['id'])['ok'], 'La confirmation est refusée.');
verifier($db->table('contrat')->where('candidature_id', (int) $scenario4['candidature']['id'])->countAllResults() === 0, 'Aucun contrat n’est généré.');

$acceptation4 = repondreOui($propositions, (int) $elements4['salaire']['id'], (int) $candidat4['id']);
verifier($acceptation4['ok'], 'Après refus, le candidat peut accepter l’offre initiale.');
$prop4 = chargerProposition((int) $prop4['id']);
verifier($prop4['statut'] === 'confirmable', 'L’acceptation de l’offre initiale rend la proposition confirmable.');

// ------------------------------------------------------------
// Cas 5 : double clic sur Confirmer → un seul contrat
// ------------------------------------------------------------
echo "\n-- Double clic sur Confirmer --\n";

$scenario5 = creerScenario(5, $db, $besoins, $recrutement, $rh, $dg, $deptInfo, $posteJr);
$prop5 = $scenario5['proposition'];
$candidat5 = $scenario5['candidat'];
$propositions->envoyer((int) $prop5['id'], $rh);
foreach (elementsParCode((int) $prop5['id']) as $element) {
    repondreOui($propositions, (int) $element['id'], (int) $candidat5['id']);
}

$premierClic = $propositions->confirmer((int) $prop5['id'], (int) $candidat5['id']);
$secondClic = $propositions->confirmer((int) $prop5['id'], (int) $candidat5['id']);
verifier($premierClic['ok'], 'Le premier clic confirme la proposition.');
verifier(! $secondClic['ok'], 'Le second clic est refusé.');
verifier($db->table('contrat')->where('candidature_id', (int) $scenario5['candidature']['id'])->countAllResults() === 1, 'Un seul contrat est généré pour la candidature.');

echo "\n==============================================\n";
echo 'Tests exécutés : ' . $tests . ' — Échecs : ' . $echecs . "\n";
echo "==============================================\n";

if (! $keep) {
    $mysqli->query('DROP DATABASE IF EXISTS `' . $dbName . '`');
}

exit($echecs === 0 ? 0 : 1);
