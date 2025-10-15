<?php
namespace App\Controllers;

class UserController extends BaseController
{
    public function index()
    {
        echo "<h1>Selamat Datang di Dashboard User!</h1>";
        echo '<a href="' . site_url('logout') . '">Logout</a>';
    }
}