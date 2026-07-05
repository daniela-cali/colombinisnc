<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'DashboardController::index');

$routes->group('profilo', function ($routes) {
    $routes->get('/',              'ProfiloController::index');
    $routes->post('versione-vista', 'ProfiloController::versioneVista');
});

$routes->get('login', '\CodeIgniter\Shield\Controllers\LoginController::loginView', ['filter' => 'noauth']);

// Impostazioni
$routes->group('impostazioni', function ($routes) {
    $routes->get('/',    'Impostazioni\GeneraleController::index');
    $routes->post('salva', 'Impostazioni\GeneraleController::salva');

    $routes->get('parametri',        'Impostazioni\GeneraleController::parametri');
    $routes->post('parametri',       'Impostazioni\GeneraleController::salvaParametri');
    $routes->post('parametri/logo',  'Impostazioni\GeneraleController::cambiaLogo');

    $routes->group('tipi-intervento', function ($routes) {
        $routes->get('/',              'Impostazioni\TipiInterventoController::index');
        $routes->post('store',         'Impostazioni\TipiInterventoController::store');
        $routes->post('(:num)/update', 'Impostazioni\TipiInterventoController::update/$1');
        $routes->post('(:num)/delete', 'Impostazioni\TipiInterventoController::delete/$1');
    });

    $routes->group('categorie-articoli', function ($routes) {
        $routes->get('/',              'Impostazioni\CategorieArticoliController::index');
        $routes->post('store',         'Impostazioni\CategorieArticoliController::store');
        $routes->post('(:num)/update', 'Impostazioni\CategorieArticoliController::update/$1');
        $routes->post('(:num)/delete', 'Impostazioni\CategorieArticoliController::delete/$1');
    });

    $routes->group('utenti-app', function ($routes) {
        $routes->get('/',               'Impostazioni\UtentiController::utentiApp');
        $routes->get('nuovo',           'Impostazioni\UtentiController::creaUtenteApp');
        $routes->post('store',          'Impostazioni\UtentiController::storeUtenteApp');
        $routes->get('(:num)/edit',     'Impostazioni\UtentiController::editUtenteApp/$1');
        $routes->post('(:num)/update',  'Impostazioni\UtentiController::updateUtenteApp/$1');
        $routes->post('(:num)/delete',  'Impostazioni\UtentiController::deleteUtenteApp/$1');
    });
});

// Anagrafiche
$routes->group('anagrafiche', function ($routes) {
    $routes->group('personale', function ($routes) {
        $routes->get('/',              'Anagrafiche\PersonaleController::index');
        $routes->get('nuovo',          'Anagrafiche\PersonaleController::nuovo');
        $routes->post('store',         'Anagrafiche\PersonaleController::store');
        $routes->get('(:num)',         'Anagrafiche\PersonaleController::show/$1');
        $routes->get('(:num)/edit',    'Anagrafiche\PersonaleController::edit/$1');
        $routes->post('(:num)/update', 'Anagrafiche\PersonaleController::update/$1');
        $routes->post('(:num)/delete', 'Anagrafiche\PersonaleController::delete/$1');
        $routes->post('assenze/aggiungi',       'Anagrafiche\PersonaleController::aggiungiAssenza');
        $routes->post('assenze/(:num)/elimina', 'Anagrafiche\PersonaleController::eliminaAssenza/$1');
    });

    $routes->group('clienti', function ($routes) {
        $routes->get('/',              'Anagrafiche\ClientiController::index');
        $routes->get('nuovo',          'Anagrafiche\ClientiController::nuovo');
        $routes->post('store',         'Anagrafiche\ClientiController::store');
        $routes->get('(:num)',           'Anagrafiche\ClientiController::show/$1');
        $routes->get('(:num)/materiali','Anagrafiche\ClientiController::materiali/$1');
        $routes->get('(:num)/sospesi',  'Anagrafiche\ClientiController::sospesiJson/$1');
        $routes->get('(:num)/edit',    'Anagrafiche\ClientiController::edit/$1');
        $routes->post('(:num)/update', 'Anagrafiche\ClientiController::update/$1');
        $routes->post('(:num)/delete', 'Anagrafiche\ClientiController::delete/$1');
    });
});

// Abbonamenti
$routes->group('abbonamenti', function ($routes) {
    $routes->get('/',               'AbbonamentiController::index');
    $routes->get('nuovo',           'AbbonamentiController::nuovo');
    $routes->post('store',          'AbbonamentiController::store');
    $routes->get('(:num)',          'AbbonamentiController::show/$1');
    $routes->get('(:num)/edit',     'AbbonamentiController::edit/$1');
    $routes->post('(:num)/update',  'AbbonamentiController::update/$1');
    $routes->post('(:num)/stato',   'AbbonamentiController::cambiaStato/$1');
    $routes->get('(:num)/rinnova',  'AbbonamentiController::rinnova/$1');
});

// Cantieri
$routes->group('cantieri', function ($routes) {
    $routes->get('/',               'CantieriController::index');
    $routes->get('nuovo',           'CantieriController::nuovo');
    $routes->post('store',          'CantieriController::store');
    $routes->get('(:num)',          'CantieriController::show/$1');
    $routes->get('(:num)/edit',     'CantieriController::edit/$1');
    $routes->post('(:num)/update',  'CantieriController::update/$1');
    $routes->post('(:num)/stato',   'CantieriController::cambiaStato/$1');
    $routes->post('(:num)/delete',  'CantieriController::delete/$1');
    $routes->post('note/aggiungi',       'CantieriController::aggiungiNota');
    $routes->post('note/(:num)/elimina', 'CantieriController::eliminaNota/$1');
});

$routes->group('promemoria', function ($routes) {
    $routes->post('store',         'PromemoriaController::store');
    $routes->post('(:num)/update', 'PromemoriaController::update/$1');
    $routes->post('(:num)/delete', 'PromemoriaController::delete/$1');
    $routes->post('(:num)/dismiss','PromemoriaController::dismiss/$1');
});

// Operativo (interventi, futuri: cantieri, ecc.)
$routes->group('operativo', function ($routes) {
    $routes->group('interventi', function ($routes) {
        $routes->get('/',              'Operativo\InterventiController::index');
        $routes->get('nuovo',          'Operativo\InterventiController::nuovo');
        $routes->post('store',         'Operativo\InterventiController::store');
        $routes->get('(:num)',         'Operativo\InterventiController::show/$1');
        $routes->get('(:num)/edit',    'Operativo\InterventiController::edit/$1');
        $routes->post('(:num)/update', 'Operativo\InterventiController::update/$1');
        $routes->post('(:num)/chiudi',  'Operativo\InterventiController::chiudi/$1');
        $routes->post('(:num)/annulla', 'Operativo\InterventiController::annulla/$1');
        $routes->post('(:num)/delete', 'Operativo\InterventiController::delete/$1');
        $routes->post('(:num)/pianifica',              'Operativo\InterventiController::pianifica/$1');
        $routes->post('(:num)/annulla-pianificazione', 'Operativo\InterventiController::annullaPianificazione/$1');
        $routes->post('note/aggiungi',       'Operativo\InterventiController::aggiungiNota');
        $routes->post('note/(:num)/elimina', 'Operativo\InterventiController::eliminaNota/$1');
    });

    $routes->group('viaggi', function ($routes) {
        $routes->get('/',    'Operativo\ViaggioController::index');
        $routes->get('pdf',  'Operativo\ViaggioController::pdf');
    });

    $routes->group('calendario', function ($routes) {
        $routes->get('/',               'Operativo\CalendarioController::index');
        $routes->get('eventi',          'Operativo\CalendarioController::eventi');
        $routes->post('sposta',         'Operativo\CalendarioController::sposta');
        $routes->post('genera-viaggio', 'Operativo\CalendarioController::generaViaggio');
    });

    $routes->group('materiali', function ($routes) {
        $routes->post('store',         'Operativo\MaterialiController::store');
        $routes->post('(:num)/delete', 'Operativo\MaterialiController::delete/$1');
    });
});

// Magazzino
$routes->group('magazzino', function ($routes) {
    $routes->group('articoli', function ($routes) {
        $routes->get('/',              'Magazzino\ArticoliController::index');
        $routes->get('nuovo',          'Magazzino\ArticoliController::nuovo');
        $routes->post('store',         'Magazzino\ArticoliController::store');
        $routes->get('(:num)/edit',    'Magazzino\ArticoliController::edit/$1');
        $routes->post('(:num)/update', 'Magazzino\ArticoliController::update/$1');
        $routes->post('(:num)/delete', 'Magazzino\ArticoliController::delete/$1');
    });
});

service('auth')->routes($routes);
