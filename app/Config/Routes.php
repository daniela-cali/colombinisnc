<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'DashboardController::index');

$routes->get('login', '\CodeIgniter\Shield\Controllers\LoginController::loginView', ['filter' => 'noauth']);

// Impostazioni
$routes->group('impostazioni', function ($routes) {
    $routes->get('/',    'Impostazioni\GeneraleController::index');
    $routes->post('salva', 'Impostazioni\GeneraleController::salva');

    $routes->group('utenti-app', function ($routes) {
        $routes->get('/',               'Impostazioni\UtentiController::utentiApp');
        $routes->get('nuovo',           'Impostazioni\UtentiController::creaUtenteApp');
        $routes->post('store',          'Impostazioni\UtentiController::storeUtenteApp');
        $routes->get('(:num)/edit',     'Impostazioni\UtentiController::editUtenteApp/$1');
        $routes->post('(:num)/update',  'Impostazioni\UtentiController::updateUtenteApp/$1');
        $routes->post('(:num)/delete',  'Impostazioni\UtentiController::deleteUtenteApp/$1');
    });
});

service('auth')->routes($routes);
