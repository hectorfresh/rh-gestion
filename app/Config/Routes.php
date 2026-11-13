<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ------------------------------------------------------------
// Public
// ------------------------------------------------------------
$routes->get('/', 'Annonce::index');
$routes->get('annonces', 'Annonce::index');
$routes->get('annonces/(:num)', 'Annonce::show/$1');
$routes->get('annonces/(:num)/postuler', 'Annonce::postulerForm/$1');
$routes->post('annonces/(:num)/postuler', 'Annonce::postuler/$1');

$routes->get('login', 'Auth::loginForm');
$routes->post('login', 'Auth::login');
$routes->get('inscription', 'Auth::inscriptionForm');
$routes->post('inscription', 'Auth::inscription');
$routes->post('logout', 'Auth::logout');

// API JSON de consultation.
$routes->get('api/annonces', 'Api::annonces');
$routes->get('api/annonces/(:num)', 'Api::annonce/$1');
$routes->get('api/departements', 'Api::departements');
$routes->get('api/postes', 'Api::postes');

// ------------------------------------------------------------
// Espace connecté
// ------------------------------------------------------------
$routes->get('dashboard', 'Dashboard::index');
$routes->get('notifications', 'Dashboard::notifications');

// Besoins — Bloc 1
$routes->get('besoins', 'Besoin::index');
$routes->get('besoins/creer', 'Besoin::form');
$routes->get('besoins/file-rh', 'Besoin::fileRH');
$routes->get('besoins/file-dg', 'Besoin::fileDG');
$routes->post('besoins', 'Besoin::store');
$routes->get('besoins/(:num)', 'Besoin::show/$1');
$routes->post('besoins/(:num)/decider', 'Besoin::decider/$1');
$routes->post('besoins/(:num)/annonce', 'Besoin::creerAnnonce/$1');

// Annonces (gestion RH/DG)
$routes->get('admin/annonces', 'Annonce::admin');
$routes->get('admin/annonces/creer', 'Annonce::form');
$routes->post('admin/annonces', 'Annonce::store');
$routes->get('admin/annonces/(:num)/modifier', 'Annonce::edit/$1');
$routes->post('admin/annonces/(:num)', 'Annonce::update/$1');
$routes->post('annonces/(:num)/cloturer', 'Annonce::cloturer/$1');

// Candidatures
$routes->get('candidatures', 'Candidature::index');
$routes->get('candidatures/mes', 'Candidature::mes');
$routes->get('candidatures/(:num)', 'Candidature::show/$1');
$routes->post('candidatures/(:num)/statut', 'Candidature::changerStatut/$1');
$routes->get('candidatures/(:num)/proposition', 'Proposition::form/$1');
$routes->post('candidatures/(:num)/proposition', 'Proposition::store/$1');

// Entretiens
$routes->get('entretiens', 'Entretien::index');
$routes->get('entretiens/mes', 'Entretien::mes');
$routes->post('candidatures/(:num)/entretien', 'Entretien::planifier/$1');
$routes->post('entretiens/(:num)/repondre', 'Entretien::repondre/$1');
$routes->post('entretiens/(:num)/evaluer', 'Entretien::evaluer/$1');

// Propositions
$routes->get('propositions', 'Proposition::index');
$routes->get('propositions/mes', 'Proposition::mes');
$routes->get('propositions/file-rh', 'Proposition::fileRH');
$routes->get('propositions/file-dg', 'Proposition::fileDG');
$routes->get('propositions/(:num)/modifier', 'Proposition::editer/$1');
$routes->post('propositions/(:num)/modifier', 'Proposition::mettreAJour/$1');
$routes->post('propositions/(:num)/envoyer', 'Proposition::envoyer/$1');
$routes->get('propositions/(:num)/confirmation', 'Proposition::confirmation/$1');
$routes->get('propositions/(:num)', 'Proposition::show/$1');
$routes->post('propositions/elements/(:num)/repondre', 'Proposition::repondre/$1');
$routes->post('propositions/elements/(:num)/decider', 'Proposition::decider/$1');
$routes->post('propositions/(:num)/confirmer', 'Proposition::confirmer/$1');
$routes->post('propositions/(:num)/renoncer', 'Proposition::renoncer/$1');

// Contrats
$routes->get('contrats', 'Contrat::index');
$routes->get('contrats/mes', 'Contrat::mes');
$routes->get('contrats/echeance', 'Contrat::echeance');
$routes->get('contrats/salarie/(:num)', 'Contrat::salarie/$1');
$routes->get('contrats/onboarding/(:num)', 'Contrat::onboarding/$1');
$routes->post('contrats/onboarding/taches/(:num)', 'Contrat::onboardingTache/$1');
$routes->get('contrats/(:num)/renouveler', 'Contrat::renouveler/$1');
$routes->post('contrats/(:num)/renouveler', 'Contrat::renouveler/$1');
$routes->get('contrats/(:num)', 'Contrat::show/$1');
$routes->get('contrats/(:num)/pdf', 'Contrat::pdf/$1');
$routes->post('contrats/(:num)/rompre', 'Contrat::rompre/$1');

// Salariés et GED
$routes->get('salaries', 'Salarie::index');
$routes->get('salaries/(:num)', 'Salarie::show/$1');
$routes->get('salaries/(:num)/modifier', 'Salarie::modifier/$1');
$routes->post('salaries/(:num)/modifier', 'Salarie::modifier/$1');
$routes->post('salaries/(:num)/affectation', 'Salarie::affecter/$1');
$routes->get('dossiers', 'Salarie::dossiers');
$routes->get('dossiers/(:num)', 'Salarie::dossier/$1');
$routes->post('dossiers/(:num)/documents', 'Salarie::upload/$1');
$routes->get('documents/(:num)/download', 'Salarie::download/$1');
$routes->post('documents/(:num)/supprimer', 'Salarie::supprimerDocument/$1');

// Temps, pointage, planning, absences
$routes->get('pointages/mes', 'Pointage::mes');
$routes->get('pointages/feuille', 'Pointage::feuille');
$routes->get('pointages/equipe', 'Pointage::equipe');
$routes->post('pointages/calcul', 'Pointage::calculer');
$routes->post('pointages/entree', 'Pointage::entree');
$routes->post('pointages/sortie', 'Pointage::sortie');
$routes->post('pointages/saisir', 'Pointage::saisir');
$routes->get('pointages', 'Pointage::index');
$routes->get('pointages/anomalies', 'Pointage::anomalies');
$routes->post('pointages/anomalies/(:num)/resoudre', 'Pointage::resoudreAnomalie/$1');
$routes->post('pointages/absents', 'Pointage::marquerAbsents');
$routes->get('planning', 'Pointage::planning');
$routes->post('planning', 'Pointage::storePlanning');
$routes->get('absences', 'Pointage::absences');
$routes->post('absences', 'Pointage::storeAbsence');

// Congés
$routes->get('conges/mes', 'Conge::mes');
$routes->get('conges/creer', 'Conge::form');
$routes->post('conges', 'Conge::store');
$routes->get('conges/validation', 'Conge::validation');
$routes->get('conges/calendrier', 'Conge::calendrier');
$routes->get('conges/soldes', 'Conge::soldes');
$routes->get('conges/(:num)', 'Conge::show/$1');
$routes->post('conges/(:num)/manager', 'Conge::validerManager/$1');
$routes->post('conges/(:num)/rh', 'Conge::validerRh/$1');
$routes->post('conges/(:num)/refuser', 'Conge::refuser/$1');
$routes->post('conges/(:num)/annuler', 'Conge::annuler/$1');

// Paie variable
$routes->get('paie', 'Paie::index');
$routes->post('paie/variables', 'Paie::storeVariable');
$routes->post('paie/variables/(:num)/supprimer', 'Paie::supprimerVariable/$1');
$routes->get('paie/export', 'Paie::export');

// Rapports
$routes->get('rapports', 'Rapport::index');
$routes->get('rapports/export/(:segment)', 'Rapport::export/$1');

// Administration
$routes->get('admin', 'Admin::index');
$routes->get('admin/budgets', 'Admin::budgets');
$routes->post('admin/departements', 'Admin::storeDepartement');
$routes->post('admin/postes', 'Admin::storePoste');
$routes->post('admin/budgets', 'Admin::storeBudget');
$routes->get('admin/types-conge', 'Admin::typesConge');
$routes->post('admin/types-conge', 'Admin::storeTypeConge');
$routes->post('admin/droits-conge', 'Admin::storeDroitConge');
$routes->post('admin/jours-feries', 'Admin::storeJourFerie');
$routes->get('audit', 'Admin::audit');
