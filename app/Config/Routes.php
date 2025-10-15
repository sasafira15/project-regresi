<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */// Halaman login
$routes->get('/', 'AuthController::index');
$routes->get('/login', 'AuthController::index');
$routes->post('/login/process', 'AuthController::processLogin');
$routes->get('/logout', 'AuthController::logout');

// Rute untuk halaman setelah login (placeholder)
$routes->get('/admin/dashboard', 'AdminController::index');

$routes->group('admin', ['filter' => 'auth'], function ($routes) {
    $routes->get('dashboard', 'AdminController::index');
    $routes->post('save', 'AdminController::saveData'); // Untuk form manual
});

// Rute untuk User/Operator
$routes->group('user', ['filter' => 'auth'], function ($routes) {
    $routes->get('dashboard', 'UserController::index');
    $routes->post('upload', 'UserController::uploadFile'); // Untuk upload file
});