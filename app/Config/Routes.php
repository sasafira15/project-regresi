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
$routes->get('/user/dashboard', 'UserController::index');


