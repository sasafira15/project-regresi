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

// Rute untuk Admin
$routes->group('admin', ['filter' => 'auth'], function ($routes) {
    $routes->get('dashboard', 'AdminController::index');
    $routes->post('save', 'AdminController::saveData'); // Untuk form manual
    $routes->get('uploads', 'AdminController::uploadsList'); 
    $routes->get('uploads/process/(:num)', 'AdminController::processUpload/$1');
    $routes->get('uploads/delete/(:num)', 'AdminController::deleteUpload/$1');
    $routes->get('uploads/download/(:num)', 'AdminController::downloadFile/$1');
    $routes->get('data/create', 'AdminController::create');

    $routes->get('data/edit/(:num)', 'AdminController::edit/$1');
    $routes->post('data/update/(:num)', 'AdminController::update/$1');
    $routes->get('data/delete/(:num)', 'AdminController::delete/$1');
});



// Rute untuk User/Operator
$routes->group('user', ['filter' => 'auth'], function ($routes) {
    $routes->get('dashboard', 'UserController::index');
    $routes->post('upload', 'UserController::uploadFile'); // Untuk upload file
    $routes->get('uploads/process/(:num)', 'AdminController::processUpload/$1');
}); 

