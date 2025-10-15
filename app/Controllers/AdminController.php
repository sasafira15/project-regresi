<?php
namespace App\Controllers;

class AdminController extends BaseController
{
    public function index()
    {
        echo "<h1>Selamat Datang di Dashboard Admin!</h1>";

        echo '<a href="' . site_url('logout') . '">Logout</a>';
    }
}