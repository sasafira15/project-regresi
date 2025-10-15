<?php
// 1. Muat autoloader Composer secara manual
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Coba panggil class yang error
use Phpml\Regression\SimpleLinearRegression;

echo "<h1>Tes Composer Autoloader</h1>";

if (class_exists(SimpleLinearRegression::class)) {
    echo "<p style='color:green; font-size: 18px;'><strong>SUKSES!</strong> Class SimpleLinearRegression berhasil ditemukan oleh autoloader.</p>";
    echo "<p>Ini berarti masalahnya ada di konfigurasi CodeIgniter (kemungkinan besar file `app/Config/Autoload.php`).</p>";
} else {
    echo "<p style='color:red; font-size: 18px;'><strong>GAGAL!</strong> Class SimpleLinearRegression TIDAK ditemukan.</p>";
    echo "<p>Ini berarti masalahnya ada pada instalasi Composer Anda, struktur folder, atau file `composer.json`.</p>";
}