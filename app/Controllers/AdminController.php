<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DataEnergyModel; 
use App\Models\UploadModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

    $data = [
        'username'    => $this->session->get('username'),
        'energy_data' => $energyModel->findAll(),
    ];

    return view('admin/dashboard', $data);
}

    // Menyimpan data dari form manual
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

    public function create()
    {
        $data['username'] = $this->session->get('username');
        return view('admin/create_data', $data);
    }

    // Memproses data dari file upload berdasarkan upload_id
     public function processUpload($uploadId)
    {
        $uploadModel = new UploadModel();
        $fileInfo = $uploadModel->find($uploadId);

        if (!$fileInfo) {
            return redirect()->to('admin/uploads')->with('error', 'Informasi file tidak ditemukan.');
        }

        $filepath = WRITEPATH . 'uploads/' . $fileInfo['filename'];

        try {
            $spreadsheet = IOFactory::load($filepath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();


            $adminId = $this->session->get('user_id');
            $dataToInsert = [];

            foreach (array_slice($rows, 1) as $row) {
                if (empty(array_filter($row))) continue; 

                $dataToInsert[] = [
                    'upload_id'      => $uploadId,
                    'week_label'     => $row[0] ?? null,
                    'driver_m'       => $row[1] ?? null,
                    'driver_ton'     => $row[2] ?? null,
                    'total_produksi' => $row[3] ?? null,
                    'energy_kwh'     => $row[4] ?? null,
                    'notes'          => $row[5] ?? null,
                    'created_by'     => $adminId
                ];
            }

            if (!empty($dataToInsert)) {
                $energyModel = new DataEnergyModel();
                $energyModel->insertBatch($dataToInsert);
                return redirect()->to('admin/uploads')->with('message', 'File ' . esc($fileInfo['original_name']) . ' berhasil diproses.');
            } else {
                return redirect()->to('admin/uploads')->with('error', 'Tidak ada data valid di dalam file.');
            }

        } catch (\Exception $e) {
            return redirect()->to('admin/uploads')->with('error', 'Gagal membaca file: ' . $e->getMessage());
        }
    }

    public function uploadsList()
    {
        $uploadModel = new UploadModel();
        
        $data = [
            'username'    => $this->session->get('username'),
            'upload_data' => $uploadModel->findAll()
        ];
        
        return view('admin/uploads_list', $data);
    }

    public function deleteUpload($uploadId)
    {
        $uploadModel = new UploadModel();
        $fileInfo = $uploadModel->find($uploadId);

        if ($fileInfo) {
            // 1. Hapus file fisik dari server
            $filepath = WRITEPATH . 'uploads/' . $fileInfo['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            // 2. Hapus record dari database
            $uploadModel->delete($uploadId);

            return redirect()->to('admin/uploads')->with('message', 'Data upload berhasil dihapus.');
        }
        return redirect()->to('admin/uploads')->with('error', 'Data upload tidak ditemukan.');
    }

    public function downloadFile($uploadId)
    {
        $uploadModel = new UploadModel();
        $fileInfo = $uploadModel->find($uploadId);

        if ($fileInfo) {
            $filepath = WRITEPATH . 'uploads/' . $fileInfo['filename'];

            if (file_exists($filepath)) {
                // Gunakan helper download dari CodeIgniter
                // File akan diunduh dengan nama aslinya
                return $this->response->download($filepath, null)->setFileName($fileInfo['original_name']);
            }
        }

        
        
        return redirect()->to('admin/uploads')->with('error', 'File tidak ditemukan di server.');
    }

     public function edit($id)
    {
        $energyModel = new DataEnergyModel();
        $data = [
            'username' => $this->session->get('username'),
            'data'     => $energyModel->find($id)
        ];

        if (empty($data['data'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Data tidak ditemukan: ' . $id);
        }

        return view('admin/edit_data', $data);
    }

     public function update($id)
    {
        $energyModel = new DataEnergyModel();
        
        $dataToUpdate = [
            'week_label'     => $this->request->getPost('week_label'),
            'total_produksi' => $this->request->getPost('total_produksi'),
            'energy_kwh'     => $this->request->getPost('energy_kwh'),
            'notes'          => $this->request->getPost('notes'),
        ];

        $energyModel->update($id, $dataToUpdate);

        return redirect()->to('/admin/dashboard')->with('message', 'Data berhasil di-update.');
    }

     public function delete($id)
    {
        $energyModel = new DataEnergyModel();
        $energyModel->delete($id);

        return redirect()->to('/admin/dashboard')->with('message', 'Data berhasil dihapus.');
    }
}
