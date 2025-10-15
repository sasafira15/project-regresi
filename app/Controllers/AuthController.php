<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class AuthController extends BaseController
{
    public function __construct()
    {
        helper(['form', 'url']);
        $this->session = \Config\Services::session();
    }

    public function index()
    {
        // Tampilkan halaman login
        return view('auth/login');
    }

    public function processLogin()
{
    // Ambil data dari form
    $username = $this->request->getPost('username');
    $password = $this->request->getPost('password');

    // Validasi sederhana
    if (empty($username) || empty($password)) {
        return redirect()->back()->with('error', 'Username dan Password tidak boleh kosong!');
    }

    // Cari user di database
    $userModel = new UserModel();
    $user = $userModel->where('username', $username)->first();

    if ($user) {
        // User ditemukan, verifikasi password
        if (password_verify($password, $user['password_hash'])) {
            // Password cocok, buat session
            $sessionData = [
                'user_id'       => $user['id'],
                'username'      => $user['username'],
                'email'         => $user['email'],
                'role'          => $user['role'],
                'isLoggedIn'    => TRUE
            ];
            $this->session->set($sessionData);

            // Redirect berdasarkan role
            if ($user['role'] == 'admin') {
                return redirect()->to('/admin/dashboard');
            } else {
                return redirect()->to('/user/dashboard');
            }
        } else {
            // Password salah
            return redirect()->back()->with('error', 'Password yang Anda masukkan salah!');
        }
    } else {
        // User tidak ditemukan
        return redirect()->back()->with('error', 'Username tidak ditemukan!');
    }
}

    public function logout()
    {
        // Hapus session dan redirect ke halaman login
        $this->session->destroy();
        return redirect()->to('/login');
    }
}