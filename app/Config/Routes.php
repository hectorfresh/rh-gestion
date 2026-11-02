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
$routes->get('/', [Test::class, 'index']);


$routes->get('/login', [Auth::class, 'index']);
$routes->post('/login', [Auth::class, 'login']);
$routes->get('/logout', [Auth::class, 'logout']);

$routes->get('/besoins', [Besoin::class, 'form']);
$routes->post('/besoins/creer', [Besoin::class, 'insert']);

$routes->get('/besoins/(:num)/', 'Besoin::fiche/$1');


    $routes->get('/besoins/valider', [Besoin::class, 'showValidationForm']);
    $routes->get('/besoins/valider/(:num)', 'Besoin::valider/$1');
    $routes->get('/besoins/refuser/(:num)', 'Besoin::refuser/$1');

$routes->get('/annonces', 'Annonce::genererForm');
$routes->post('/annonces/creer', 'Annonce::generer');



