<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DataEnergyModel; 

class AdminController extends BaseController
{
    public function __construct()
    {
        $this->session = \Config\Services::session();
    }

    // Menampilkan dashboard admin (form + tabel data)
    public function index()
    {
        $energyModel = new DataEnergyModel();
        
        // Menyiapkan data untuk dikirim ke view
        $data = [
            'username'    => $this->session->get('username'),
            // Ambil semua data energi untuk ditampilkan di tabel
            'energy_data' => $energyModel->findAll() 
        ];

        return view('admin/dashboard', $data);
    }

    // Menyimpan data dari form manual (dipindahkan dari UserController)
    public function saveData()
    {
        $energyModel = new DataEnergyModel();

        $dataToSave = [
            'week_label'     => $this->request->getPost('week_label'),
            'driver_m'       => $this->request->getPost('driver_m'),
            'driver_ton'     => $this->request->getPost('driver_ton'),
            'total_produksi' => $this->request->getPost('total_produksi'),
            'energy_kwh'     => $this->request->getPost('energy_kwh'),
            'notes'          => $this->request->getPost('notes'),
            'created_by'     => $this->session->get('user_id'),
        ];

        if ($energyModel->insert($dataToSave)) {
            return redirect()->to('/admin/dashboard')->with('message', 'Data berhasil disimpan manual!');
        } else {
            return redirect()->to('/admin/dashboard')->with('error', 'Gagal menyimpan data.');
        }
    }
}