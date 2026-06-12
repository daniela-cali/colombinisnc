<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Dashboard::index');

$routes->get('login', '\CodeIgniter\Shield\Controllers\LoginController::loginView', ['filter' => 'noauth']);

service('auth')->routes($routes);
