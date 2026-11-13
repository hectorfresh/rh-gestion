<?php

/**
 * Test d'intégration du Bloc 2 — annonces, candidatures et entretiens.
 *
 * Usage : php tests/block2_recrutement_test.php
 *
 * Le script crée une base isolée `rh_block2_test` à partir de base/init.sql et
 * base/data.sql, puis exécute les règles métier avec les vrais services.
 */

$root = dirname(__DIR__);
$dbName = 'rh_block2_test';
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
// 3. Assertions
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

$db = db_connect();
$besoins = new App\Services\BesoinService();
$recrutement = new App\Services\RecrutementService();

$manager    = $db->table('utilisateur')->where('email', 'manager@mail.com')->get()->getRowArray();
$rh         = $db->table('utilisateur')->where('email', 'rh@mail.com')->get()->getRowArray();
$dg         = $db->table('utilisateur')->where('email', 'dg@mail.com')->get()->getRowArray();
$candidat   = $db->table('utilisateur')->where('email', 'candidat@mail.com')->get()->getRowArray();

// Deuxième candidat pour vérifier l'unicité par utilisateur.
$db->table('utilisateur')->insert([
    'nom'      => 'Rakoto',
    'prenom'   => 'Miora',
    'email'    => 'candidat2@mail.com',
    'telephone'=> '0340000099',
    'mdp'      => password_hash('1234', PASSWORD_DEFAULT),
    'role_id'  => 5,
    'actif'    => 1,
]);
$candidat2 = $db->table('utilisateur')->where('email', 'candidat2@mail.com')->get()->getRowArray();

$deptInfo = (int) $db->table('departement')->where('code', 'INFO')->get()->getRowArray()['id'];
$posteJr  = (int) $db->table('poste')->where('libelle', 'Développeur junior')->get()->getRowArray()['id'];
$dateFuture = date('Y-m-d', strtotime('+30 days'));
$dateEntretien = date('Y-m-d H:i:s', strtotime('+10 days'));

echo "\n=== Bloc 2 : annonces, candidatures et entretiens ===\n";

// ------------------------------------------------------------
// Cas 0 : besoin validé nécessaire
// ------------------------------------------------------------
echo "\n-- Création d'annonce conditionnée au besoin validé --\n";

$creationBesoin = $besoins->creer([
    'poste_id'       => $posteJr,
    'departement_id' => $deptInfo,
    'nombre'         => 1,
    'type_besoin'    => 'creation',
    'motif'          => 'Test annonce Bloc 2',
    'salaire_estime' => 400000,
    'date_souhaitee' => $dateFuture,
], $dg);

verifier($creationBesoin['ok'], 'Un besoin validé est créé par le DG pour le scénario.');
$besoinId = (int) ($creationBesoin['id'] ?? 0);

verifier($db->table('besoin b')->join('statut_besoin sb', 'sb.id = b.statut_id')->where('b.id', $besoinId)->get()->getRowArray()['libelle'] === 'Validé', 'Le besoin support est bien au statut Validé.');

$resultat = $recrutement->creerAnnonce((int) ($db->table('besoin')->where('id', 2)->get()->getRowArray()['id']), [
    'titre' => 'Annonce interdite',
], $rh);
verifier(! $resultat['ok'], 'Une annonce ne peut pas être créée depuis un besoin non validé.');

$resultat = $recrutement->creerAnnonce($besoinId, [
    'titre' => 'Annonce manager interdite',
], $manager);
verifier(! $resultat['ok'], 'Un manager ne peut pas créer d’annonce.');

$resultat = $recrutement->creerAnnonce($besoinId, [
    'titre'       => 'Développeur junior PHP',
    'description' => 'Rejoignez l’équipe web.',
    'profil'      => 'PHP, MySQL, HTML/CSS.',
    'date_limite' => $dateFuture,
], $rh);
verifier($resultat['ok'], 'Le RH crée une annonce depuis un besoin validé.');
$annonceId = (int) ($resultat['id'] ?? 0);

$resultat = $recrutement->creerAnnonce($besoinId, [
    'titre' => 'Deuxième annonce',
], $dg);
verifier(! $resultat['ok'], 'Une seule annonce peut être créée par besoin.');

$resultat = $recrutement->creerAnnonce($besoinId + 999, [
    'titre' => 'Annonce fantôme',
], $rh);
verifier(! $resultat['ok'], 'Une annonce ne peut pas être créée pour un besoin inexistant.');

$resultat = $recrutement->modifierAnnonce($annonceId, [
    'titre'       => 'Développeur junior PHP / MySQL',
    'date_limite' => $dateFuture,
], $manager);
verifier(! $resultat['ok'], 'Un manager ne peut pas modifier une annonce.');

// ------------------------------------------------------------
// Cas 1 : candidature unique + CV guidé
// ------------------------------------------------------------
echo "\n-- Candidature et CV guidé --\n";

$donneesCv = [
    'titre_cv'       => 'CV de Sarah — Bloc 2',
    'description_cv' => 'Développeuse junior.',
    'nom'            => 'Sarah Andria',
    'email'          => 'candidat@mail.com',
    'telephone'      => '0340000005',
    'adresse'        => 'Lot II A 12',
    'ville'          => 'Antananarivo',
    'formations'     => [
        ['diplome' => 'Licence informatique', 'etablissement' => 'Université d’Antananarivo', 'date_debut' => '2021-09-01', 'date_fin' => '2024-07-31'],
    ],
    'experiences'    => [
        ['poste' => 'Stagiaire développeuse', 'entreprise' => 'TechMada', 'date_debut' => '2024-02-01', 'date_fin' => '2024-08-31', 'description' => 'Modules PHP / MySQL.'],
    ],
    'langues'        => [
        ['langue_id' => 1, 'niveau_id' => 3],
        ['langue_id' => 2, 'niveau_id' => 2],
        ['langue_id' => 3, 'niveau_id' => 4],
    ],
    'lettre_motivation' => 'Je souhaite rejoindre votre équipe.',
];

$resultat = $recrutement->postuler($annonceId, $candidat, $donneesCv);
verifier($resultat['ok'], 'Le candidat dépose sa première candidature.');
$candidatureId = (int) ($resultat['id'] ?? 0);
verifier(statutCandidature($candidatureId) === 'Reçue', 'La candidature démarre au statut « Reçue ».');

$cvId = (int) ($db->table('candidature')->where('id', $candidatureId)->get()->getRowArray()['cv_id'] ?? 0);
verifier($db->table('cv_langue')->where('cv_id', $cvId)->countAllResults() === 3, 'Les langues du CV guidé sont enregistrées.');
verifier($db->table('cv_formation')->where('cv_id', $cvId)->countAllResults() === 1, 'La formation du CV guidé est enregistrée.');
verifier($db->table('cv_experience')->where('cv_id', $cvId)->countAllResults() === 1, 'L’expérience du CV guidé est enregistrée.');
verifier($db->table('historique_statut_candidature')->where('candidature_id', $candidatureId)->countAllResults() >= 1, 'L’historique de statut est initialisé.');

$resultat = $recrutement->postuler($annonceId, $candidat, $donneesCv);
verifier(! $resultat['ok'], 'La double candidature du même utilisateur est refusée.');

$cvReutilise = $recrutement->postuler($annonceId, $candidat2, [
    'cv_id'             => $cvId,
    'lettre_motivation' => 'Je réutilise le CV de Sarah.',
]);
verifier(! $cvReutilise['ok'], 'Un candidat ne peut pas réutiliser le CV d’un autre utilisateur.');

$resultat = $recrutement->postuler($annonceId, $candidat2, [
    'titre_cv'  => 'CV de Miora',
    'nom'       => 'Miora Rakoto',
    'email'     => 'candidat2@mail.com',
    'langues'   => [['langue_id' => 1, 'niveau_id' => 2]],
]);
verifier($resultat['ok'], 'Un second candidat peut postuler à la même annonce.');
$candidature2Id = (int) ($resultat['id'] ?? 0);

// ------------------------------------------------------------
// Cas 2 : entretien, réponse et évaluation
// ------------------------------------------------------------
echo "\n-- Entretien et décision finale --\n";

$resultat = $recrutement->changerStatutCandidature($candidatureId, 'Présélectionnée', $rh, 'Profil conforme.');
verifier($resultat['ok'] && statutCandidature($candidatureId) === 'Présélectionnée', 'Le RH présélectionne la candidature.');

$resultat = $recrutement->changerStatutCandidature($candidatureId, 'Retenue', $candidat, 'Auto-décision interdite.');
verifier(! $resultat['ok'], 'Un candidat ne peut pas changer lui-même le statut de sa candidature.');

$resultat = $recrutement->planifierEntretien($candidatureId, [
    'type'            => 'Technique',
    'date_entretien'  => $dateEntretien,
    'lieu'            => 'Salle informatique',
    'intervieweur_id' => (int) $rh['id'],
    'commentaire'     => 'Entretien technique.',
], $rh);
verifier($resultat['ok'], 'Le RH planifie un entretien.');
$entretienId = (int) ($resultat['id'] ?? 0);
verifier(statutCandidature($candidatureId) === 'En attente entretien', 'La candidature passe à « En attente entretien ».');

$entretien = $db->table('entretien')->where('id', $entretienId)->get()->getRowArray();
verifier(($entretien['type'] ?? '') === 'Technique' && (int) ($entretien['intervieweur_id'] ?? 0) === (int) $rh['id'], 'Le type et l’intervieweur sont enregistrés.');

$resultat = $recrutement->repondreEntretien($entretienId, $candidat, 'accepte');
verifier($resultat['ok'], 'Le candidat confirme sa présence à l’entretien.');
$entretien = $db->table('entretien')->where('id', $entretienId)->get()->getRowArray();
verifier(($entretien['reponse_candidat'] ?? '') === 'accepte', 'La réponse du candidat est conservée.');

$resultat = $recrutement->repondreEntretien($entretienId, $candidat2, 'accepte');
verifier(! $resultat['ok'], 'Un autre candidat ne peut pas répondre à cette convocation.');

$resultat = $recrutement->evaluerEntretien($entretienId, [
    'note'        => 16.5,
    'commentaire' => 'Très bon entretien technique.',
    'decision'    => 'retenue',
], $rh);
verifier($resultat['ok'], 'Le RH évalue l’entretien et retient le candidat.');
verifier(statutCandidature($candidatureId) === 'Retenue', 'Le statut final de la candidature est « Retenue ».');

$evaluation = $db->table('evaluation_candidat')->where('entretien_id', $entretienId)->get()->getRowArray();
verifier($evaluation !== null && (float) $evaluation['note'] === 16.5 && $evaluation['decision'] === 'retenu', 'L’évaluation contient la note, l’appréciation et la décision.');

$proposition = $db->table('proposition_embauche')->where('candidature_id', $candidatureId)->get()->getRowArray();
verifier($proposition !== null, 'La décision « Retenue » déclenche la proposition d’embauche.');

$annonce = $db->table('annonce')->where('id', $annonceId)->get()->getRowArray();
$besoin = $db->table('besoin b')->join('statut_besoin sb', 'sb.id = b.statut_id')->where('b.id', $besoinId)->get()->getRowArray();
verifier((int) ($annonce['est_cloture'] ?? 0) === 1, 'L’annonce est clôturée automatiquement quand tous les postes sont pourvus.');
verifier(($besoin['libelle'] ?? '') === 'Pourvu', 'Le besoin passe au statut « Pourvu ».');

$resultat = $recrutement->postuler($annonceId, $candidat2, [
    'cv_id' => (int) ($db->table('candidature')->where('id', $candidature2Id)->get()->getRowArray()['cv_id'] ?? 0),
]);
verifier(! $resultat['ok'], 'Une annonce clôturée n’accepte plus de candidature.');

$resultat = $recrutement->postuler($annonceId, $candidat2, []);
verifier(! $resultat['ok'], 'Une annonce clôturée refuse même une nouvelle candidature.');

// ------------------------------------------------------------
// Cas 3 : clôture manuelle
// ------------------------------------------------------------
echo "\n-- Clôture manuelle --\n";

$creationBesoin2 = $besoins->creer([
    'poste_id'       => $posteJr,
    'departement_id' => $deptInfo,
    'nombre'         => 2,
    'type_besoin'    => 'creation',
    'motif'          => 'Test clôture manuelle',
    'salaire_estime' => 400000,
    'date_souhaitee' => $dateFuture,
], $dg);
$besoin2Id = (int) ($creationBesoin2['id'] ?? 0);
$annonce2 = $recrutement->creerAnnonce($besoin2Id, ['titre' => 'Annonce à clôturer'], $dg);

verifier($annonce2['ok'], 'Une seconde annonce est créée pour tester la clôture manuelle.');
$resultat = $recrutement->cloturerAnnonce((int) $annonce2['id'], $dg);
verifier($resultat['ok'], 'Le DG peut clôturer manuellement une annonce.');
verifier((int) $db->table('annonce')->where('id', (int) $annonce2['id'])->get()->getRowArray()['est_cloture'] === 1, 'La clôture manuelle est enregistrée.');

echo "\n==============================================\n";
echo 'Tests exécutés : ' . $tests . ' — Échecs : ' . $echecs . "\n";
echo "==============================================\n";

if (! $keep) {
    $mysqli->query('DROP DATABASE IF EXISTS `' . $dbName . '`');
}

exit($echecs === 0 ? 0 : 1);
