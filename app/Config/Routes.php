<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\Utilisateur;
use App\Controllers\Auth;
use App\Controllers\Test;
use App\Controllers\Besoin;
use App\Controllers\Annonce;
use App\Filters\RoleFilter;
/**
 * @var RouteCollection $routes
 */

$routes->get('/utilisateurs', 'Utilisateur::index');
//$routes->get('/', [Test::class, 'index']);
$routes->get('/accueil', 'Dashboard::index');

$routes->get('/', 'Annonce::liste');


$routes->get('/login', [Auth::class, 'index']);
$routes->post('/login', [Auth::class, 'login']);
$routes->get('/logout', [Auth::class, 'logout']);
$routes->get('/create_compte', [Auth::class, 'creerCompteForm']);
$routes->post('/create_account', [Auth::class, 'insertUtilisateur']);




$routes->get('/besoins', [Besoin::class, 'form']);
$routes->post('/besoins/creer', [Besoin::class, 'insert']);

$routes->get('/besoins/(:num)/', 'Besoin::fiche/$1');


    $routes->get('/besoins/valider', [Besoin::class, 'showValidationForm']);
    $routes->get('/besoins/valider/(:num)', 'Besoin::valider/$1');
    $routes->get('/besoins/refuser/(:num)', 'Besoin::refuser/$1');

$routes->get('/annonces', 'Annonce::genererForm');
// $routes->get('/annonces/liste', 'Annonce::liste');
$routes->post('/annonces/creer', 'Annonce::generer');
$routes->get('/postule/(:num)', 'cv::getPosteAnnonce/$1');
$routes->post('/postuler', 'cv::insert');
$routes->get('/candidatures/(:num)', 'Candidature::fiche/$1');
// $routes->get('/candidatures/valider/(:num)', 'Candidature::valider/$1');
// $routes->get('/candidatures/refuser/(:num)', 'Candidature::refuser/$1');
$routes->get('/candidatures/liste', 'candidature::liste');

// $routes->get('candidatures', 'candidature::liste');
$routes->post('candidatures/valider/(:num)', 'candidature::valider/$1');
$routes->post('candidatures/refuser/(:num)', 'candidature::refuser/$1');







