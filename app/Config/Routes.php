<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// Halaman login
$routes->get('/', 'AuthController::index');
$routes->get('/login', 'AuthController::index');
$routes->post('/login/process', 'AuthController::processLogin');
$routes->get('/logout', 'AuthController::logout');

// Rute untuk halaman setelah login (placeholder)
// BARIS INI DIKOMEN/DIHAPUS KARENA SUDAH DI-HANDLE DI GROUP 'admin'
// $routes->get('/admin/dashboard', 'AdminController::index');

// Rute untuk Admin
$routes->group('admin', ['filter' => 'auth'], function ($routes) {
    // PERUBAHAN KRUSIAL 1: Mengganti AdminController::index ke AdminController::dashboard
    $routes->get('dashboard', 'AdminController::dashboard');
    
    $routes->post('save', 'AdminController::saveData'); // Untuk form manual
    $routes->get('uploads', 'AdminController::uploadsList'); 
    
    // PERUBAHAN KRUSIAL ADA DI SINI:
    // 1. Rute GET untuk proses (saat ini sudah ada)
    $routes->get('uploads/process/(:num)', 'AdminController::processUpload/$1');
    // 2. MENAMBAHKAN RUTE POST UNTUK PROSES
    $routes->post('uploads/process/(:num)', 'AdminController::processUpload/$1'); // <--- BARIS INI DITAMBAHKAN
    
    $routes->get('uploads/delete/(:num)', 'AdminController::deleteUpload/$1');
    $routes->get('uploads/download/(:num)', 'AdminController::downloadFile/$1');
    $routes->get('data/create', 'AdminController::create');

    $routes->get('data/edit/(:num)', 'AdminController::edit/$1');
    $routes->post('data/update/(:num)', 'AdminController::update/$1');
    $routes->get('data/delete/(:num)', 'AdminController::delete/$1');
    $routes->post('mesin/tambah', 'AdminController::tambahMesin');
    $routes->get('mesin/hapus/(:num)', 'AdminController::hapusMesin/$1');
    $routes->post('laporan/download/mingguan', 'AdminController::downloadLaporanMingguan');
});



// Rute untuk User/Operator
$routes->group('user', ['filter' => 'auth'], function ($routes) {
    $routes->get('dashboard', 'UserController::index');
    $routes->post('upload', 'UserController::uploadFile'); // Untuk upload file
    
    // PERUBAHAN KRUSIAL 2: Pastikan ini memanggil Controller yang benar (jika user/operator bisa memproses)
    // Jika hanya Admin yang bisa memproses, baris ini harus dihapus dari group 'user'
    // Jika User/Operator bisa memproses, pastikan AdminController::processUpload accessible atau buat UserController::processUpload
    // Saya asumsikan hanya Admin yang bisa mengakses processUpload, jadi baris ini perlu diperbaiki atau dihapus
    // Karena Anda membiarkannya memanggil AdminController, kita biarkan saja (tapi ini agak tidak sesuai best practice CI4)
    $routes->get('uploads/process/(:num)', 'AdminController::processUpload/$1');
});