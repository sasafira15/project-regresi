<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DataEnergyModel; 
use App\Models\UploadModel;
use App\Models\MesinModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

use Phpml\Regression\SimpleLinearRegression;
use Phpml\Metric\Regression;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
    $mesinModel = new MesinModel();

    $allEnergyData = $energyModel->orderBy('week_label', 'ASC')->findAll();
    $uniqueWeeks = $energyModel->distinct()->findColumn('week_label') ?? [];
    

    $chartData = [];
    foreach ($allEnergyData as $row) {
        // Pastikan data tidak null
        if ($row['total_produksi'] !== null && $row['energy_kwh'] !== null) {
            // Format yang dibutuhkan Chart.js: {x: ..., y: ...}
            $chartData[] = [
                'x' => (float)$row['total_produksi'], 
                'y' => (float)$row['energy_kwh']
            ];
        }
    }

    $data = [
        'username'    => session()->get('username'),
        'list_mesin'  => $mesinModel->findAll(),
        'chart_data'  => json_encode($chartData),
        'unique_weeks'  => $uniqueWeeks, // Kirim data sebagai JSON
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

     public function downloadLaporanMingguan()
    {
        $weekLabel = $this->request->getPost('week_label');
        if (empty($weekLabel)) {
            return redirect()->to('admin/dashboard')->with('error', 'Silakan pilih minggu terlebih dahulu.');
        }

        $energyModel = new DataEnergyModel();
        $listData = $energyModel->where('week_label', $weekLabel)->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'Minggu')->setCellValue('B1', 'Driver (M)')->setCellValue('C1', 'Driver (Ton)')->setCellValue('D1', 'Total Produksi')->setCellValue('E1', 'Energi (kWh)')->setCellValue('F1', 'Catatan')->setCellValue('G1', 'Dibuat Pada');
        
        $rowNumber = 2;
        foreach($listData as $data) {
            $sheet->setCellValue('A' . $rowNumber, $data['week_label']);
            $sheet->setCellValue('B' . $rowNumber, $data['driver_m']);
            $sheet->setCellValue('C' . $rowNumber, $data['driver_ton']);
            $sheet->setCellValue('D' . $rowNumber, $data['total_produksi']);
            $sheet->setCellValue('E' . $rowNumber, $data['energy_kwh']);
            $sheet->setCellValue('F' . $rowNumber, $data['notes']);
            $sheet->setCellValue('G' . $rowNumber, $data['created_at']);
            $rowNumber++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Laporan_Minggu_' . $weekLabel . '.xlsx';

        ob_start();
        $writer->save('php://output');
        $fileData = ob_get_contents();
        ob_end_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment;filename="' . $fileName . '"')
            ->setBody($fileData);
    }

     public function tambahMesin()
    {
        $mesinModel = new MesinModel();
        $namaMesin = $this->request->getPost('nama_mesin');

        if (!empty($namaMesin)) {
            $mesinModel->save(['nama_mesin' => $namaMesin]);
            return redirect()->to('admin/dashboard')->with('message', 'Mesin baru berhasil ditambahkan.');
        }
        return redirect()->to('admin/dashboard')->with('error', 'Nama mesin tidak boleh kosong.');
    }

    public function downloadLaporan()
{
    // 1. Ambil data dari database
    $energyModel = new DataEnergyModel();
    $listData = $energyModel->findAll();

    // 2. Buat objek spreadsheet baru
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // 3. Tulis baris header
    $sheet->setCellValue('A1', 'Minggu');
    $sheet->setCellValue('B1', 'Driver (M)');
    $sheet->setCellValue('C1', 'Driver (Ton)');
    $sheet->setCellValue('D1', 'Total Produksi');
    $sheet->setCellValue('E1', 'Energi (kWh)');
    $sheet->setCellValue('F1', 'Catatan');
    $sheet->setCellValue('G1', 'Dibuat Pada');

    // 4. Tulis data dari database ke baris-baris berikutnya
    $rowNumber = 2;
    foreach ($listData as $data) {
        $sheet->setCellValue('A' . $rowNumber, $data['week_label']);
        $sheet->setCellValue('B' . $rowNumber, $data['driver_m']);
        $sheet->setCellValue('C' . $rowNumber, $data['driver_ton']);
        $sheet->setCellValue('D' . $rowNumber, $data['total_produksi']);
        $sheet->setCellValue('E' . $rowNumber, $data['energy_kwh']);
        $sheet->setCellValue('F' . $rowNumber, $data['notes']);
        $sheet->setCellValue('G' . $rowNumber, $data['created_at']);
        $rowNumber++;
    }

    // 5. Siapkan writer dan nama file
    $writer = new Xlsx($spreadsheet);
    $fileName = 'Laporan_Data_Energi_Perminggu_' . date('Y-m-d') . '.xlsx';

    // 6. Tangkap output ke dalam variabel, jangan langsung kirim ke browser
    ob_start();
    $writer->save('php://output');
    $fileData = ob_get_contents();
    ob_end_clean();

    // 7. Gunakan Response object dari CodeIgniter untuk mengirim file
    return $this->response
        ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->setHeader('Content-Disposition', 'attachment;filename="' . $fileName . '"')
        ->setHeader('Cache-Control', 'max-age=0')
        ->setBody($fileData);
}

    // ==> FUNGSI BARU UNTUK HAPUS MESIN <==
    public function hapusMesin($id)
    {
        $mesinModel = new MesinModel();
        $mesinModel->delete($id);
        return redirect()->to('admin/dashboard')->with('message', 'Mesin berhasil dihapus.');
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
        $energyModel = new DataEnergyModel();
        $fileInfo = $uploadModel->find($uploadId);

          if ($fileInfo) {
        // 1. Hapus data 'anak' di tb_data_energy terlebih dahulu
        $energyModel->where('upload_id', $uploadId)->delete();

        // 2. Hapus file fisik dari server
        $filepath = WRITEPATH . 'uploads/' . $fileInfo['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // 3. Hapus data 'induk' dari tb_uploads
        $uploadModel->delete($uploadId);

        return redirect()->to('admin/uploads')->with('message', 'Data upload dan data energi terkait berhasil dihapus.');
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
