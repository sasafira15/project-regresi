<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DataEnergyModel; 
use App\Models\UploadModel; 
use App\Models\MesinModel;
use app\Controllers\AdminControllerphp;
use PhpOffice\PhpSpreadsheet\IOFactory; // Diperlukan untuk membaca Excel
use Phpml\Regression\SimpleLinearRegression; // Diperlukan untuk Regresi
use Phpml\Metric\Regression; // Diperlukan untuk menghitung R-squared
use CodeIgniter\I18n\Time; // Diperlukan untuk penanganan waktu

// Hapus use PhpOffice\PhpSpreadsheet\Spreadsheet; dan use PhpOffice\PhpSpreadsheet\Writer\Xlsx; 
// karena hanya IOFactory yang diperlukan untuk membaca file.

class AdminController extends BaseController 
{
    protected $session;

    public function __construct()
    {
        // Inisialisasi session
        $this->session = \Config\Services::session(); 
        // Muat helper yang mungkin diperlukan untuk form dan URL redirect
        helper(['form', 'url']); 
    }

    // ----------------------------------------------------------------------
    // DASHBOARD DAN REGRESI
    // ----------------------------------------------------------------------

    /**
     * Menampilkan dashboard utama admin dan menjalankan perhitungan regresi.
     * Mengganti index() menjadi dashboard() untuk mencegah error 404 pada rute /admin/dashboard
     */
    public function dashboard() 
    {
        $energyModel = new DataEnergyModel();
        $mesinModel = new MesinModel();
        
        $allEnergyData = $energyModel->orderBy('week_label', 'ASC')->findAll();
        $uniqueWeeks = $energyModel->distinct()->findColumn('week_label') ?? [];
        
        $chartData = [];
        foreach ($allEnergyData as $row) {
            $produksi = (float)($row['total_produksi'] ?? 0);
            $energy = (float)($row['energy_kwh'] ?? 0);

            // Hanya ambil data yang valid (produksi dan energi > 0)
            if ($produksi > 0 && $energy > 0) {
                $chartData[] = [
                    'x' => $produksi, 
                    'y' => $energy
                ];
            }
        }
        
        // --- LOGIKA REGRESI LINEAR (PHP-ML) ---
        $slope = 0.0;
        $intercept = 0.0;
        $r_squared = 0.0;
        $regressionLineData = [];
        
        // Pastikan ada data minimal 2 untuk training
        if (count($chartData) >= 2) {
            $samples = array_map(function($d) { return [$d['x']]; }, $chartData);
            $targets = array_map(function($d) { return $d['y']; }, $chartData);
            
            try {
                $regression = new SimpleLinearRegression();
                $regression->train($samples, $targets);
                
                $coefficients = $regression->getCoefficients();
                $slope = $coefficients[0] ?? 0.0; 
                $intercept = $coefficients[1] ?? 0.0; 
                
                $predictions = $regression->predict($samples);
                $r_squared = Regression::rSquared($targets, $predictions);
                
                // Menghitung poin garis regresi
                $xValues = array_column($chartData, 'x');
                $minX = min($xValues);
                $maxX = max($xValues);

                $rangeX = $maxX - $minX;
                $margin = $rangeX * 0.1;
                $minXWithMargin = $minX - $margin;
                $maxXWithMargin = $maxX + $margin;

                $regressionLineData[] = [
                    'x' => (float)round($minXWithMargin, 2),
                    'y' => (float)round(($slope * $minXWithMargin) + $intercept, 2)
                ];
                $regressionLineData[] = [
                    'x' => (float)round($maxXWithMargin, 2),
                    'y' => (float)round(($slope * $maxXWithMargin) + $intercept, 2)
                ];

            } catch (\Exception $e) {
                // Catat error regresi, tapi biarkan dashboard tetap termuat
                log_message('error', 'Error Regresi Linear: ' . $e->getMessage());
            }
        }
        
        $data = [
            'username'              => session()->get('username') ?? 'Admin Default', 
            'list_mesin'            => $mesinModel->findAll(),
            'chart_data'            => json_encode($chartData),
            'regression_line_data'  => json_encode($regressionLineData),
            'slope'                 => round($slope, 4),
            'baseload'              => round($intercept, 4),
            'r_squared'             => round($r_squared, 4),
            'unique_weeks'          => $uniqueWeeks, 
        ];

        return view('admin/dashboard', $data);
    }

    // ----------------------------------------------------------------------
    // MANAJEMEN UPLOAD FILE
    // ----------------------------------------------------------------------
    
    /**
     * Menangani upload file Excel dari form.
     * Method ini tidak ada di kode Anda sebelumnya dan diperlukan.
     * Dipetakan ke rute POST /admin/upload
     */
    public function uploadFile()
    {
        $uploadModel = new UploadModel();
        
        if (!$this->request->is('post')) {
             return redirect()->back()->with('error', 'Metode tidak valid.');
        }

        $file = $this->request->getFile('file_excel'); // Sesuaikan dengan nama input file Anda

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'Gagal mengunggah file. Pastikan file valid.');
        }

        $allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
        if (!in_array($file->getClientMimeType(), $allowedTypes)) {
            return redirect()->back()->with('error', 'Hanya file Excel (.xlsx atau .xls) yang diizinkan.');
        }
        
        $originalName = $file->getName();
        $newName = $file->getRandomName();
        
        // Pindahkan file ke direktori writeable/uploads
        $file->move(WRITEPATH . 'uploads', $newName);
        
        // Simpan data record upload ke database
        $adminId = $this->session->get('user_id') ?? 1; 
        
        $data = [
            'original_name' => $originalName,
            'filename'      => $newName,
            'status'        => 'pending', 
            'uploaded_by'   => $adminId,
            'uploaded_at'   => Time::now()->toDateTimeString(),
        ];
        
        $uploadModel->insert($data);

        return redirect()->to('admin/uploads')->with('message', 'File ' . esc($originalName) . ' berhasil diunggah. Siap untuk diproses.');
    }


    /**
     * Memproses data dari file upload berdasarkan upload_id (dari Excel ke DB).
     * Dipetakan ke rute /admin/process/(:num)
     */
    public function processUpload($uploadId)
    {
        $uploadModel = new UploadModel();
        $dataEnergyModel = new DataEnergyModel();

        $upload = $uploadModel->find($uploadId);

        if (!$upload || $upload['status'] !== 'pending') {
            return redirect()->to('admin/uploads')->with('error', 'File tidak ditemukan atau sudah diproses/gagal.');
        }

        $filePath = WRITEPATH . 'uploads/' . $upload['filename'];
        $rows_processed = 0;

        try {
            if (!file_exists($filePath)) {
                throw new \Exception("File unggahan tidak ditemukan di server.");
            }

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $sheetData = $sheet->toArray(null, true, true, true); 
            
            unset($sheetData[1]); // Hapus header
            $dataEnergyModel->db->transBegin(); // Mulai transaksi DB

            $adminId = $this->session->get('user_id') ?? 1; 
            $dataToInsert = [];
            
            // ASUMSI KOLOM EXCEL: A: week_label, B: driver_m, C: driver_ton, D: total_produksi, E: energy_kwh, F: notes 
            foreach ($sheetData as $row) {
                $weekLabel = trim($row['A'] ?? '');
                
                $driverM = (float)($row['B'] ?? 0);
                $driverTon = (float)($row['C'] ?? 0);
                $totalProduksi = (float)($row['D'] ?? 0);
                $energyKwh = (float)($row['E'] ?? 0); 
                $notes = $row['F'] ?? ''; 

                if (!empty($weekLabel) && ($totalProduksi > 0 || $energyKwh > 0)) {
                    $dataToInsert[] = [
                        'upload_id' => $uploadId, 
                        'week_label' => $weekLabel,
                        'driver_m' => $driverM,
                        'driver_ton' => $driverTon,
                        'total_produksi' => $totalProduksi,
                        'energy_kwh' => $energyKwh, 
                        'notes' => $notes,
                        'created_by' => $adminId, 
                        'created_at' => Time::now()->toDateTimeString(),
                    ];
                    $rows_processed++;
                }
            }

            if (!empty($dataToInsert)) {
                $dataEnergyModel->insertBatch($dataToInsert);
            }

            if ($dataEnergyModel->db->transStatus() === false) {
                $dataEnergyModel->db->transRollback();
                throw new \Exception('Gagal memproses data. Kesalahan pada transaksi database.');
            } else {
                $dataEnergyModel->db->transCommit();
                
                $uploadModel->update($uploadId, [
                    'status' => 'processed',
                    'processed_at' => Time::now()->toDateTimeString(),
                    'row_count' => $rows_processed 
                ]);
            }

            return redirect()->to('admin/uploads')->with('message', $rows_processed . ' baris data dari file ' . esc($upload['original_name']) . ' berhasil diproses.');

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if ($dataEnergyModel->db->transStatus() !== false) {
                 $dataEnergyModel->db->transRollback();
            }
            
             $uploadModel->update($uploadId, [
                 'status' => 'failed',
                 'notes' => 'Error: ' . substr($errorMessage, 0, 250)
            ]);
            
            log_message('error', 'Gagal memproses file upload ID ' . $uploadId . ': ' . $errorMessage);
            
            return redirect()->to('admin/uploads')->with('error', 'Gagal memproses file: ' . $errorMessage);
        }
    }
    
    /**
     * Menampilkan daftar file upload
     * Dipetakan ke rute /admin/uploads
     */
    public function uploadsList()
    {
        $uploadModel = new UploadModel();
        $data = [
            'username' => session()->get('username') ?? 'Admin Default',
            'upload_data' => $uploadModel->orderBy('uploaded_at', 'DESC')->findAll(),
        ];
        return view('admin/uploads_list', $data);
    }
    
    /**
     * Menghapus file upload, data energinya, dan file fisiknya
     * Dipetakan ke rute /admin/delete/(:num)
     */
    public function deleteUpload($uploadId)
    {
        $uploadModel = new UploadModel();
        $dataEnergyModel = new DataEnergyModel();

        $upload = $uploadModel->find($uploadId);

        if (!$upload) {
            return redirect()->back()->with('error', 'File upload tidak ditemukan.');
        }

        try {
            $uploadModel->db->transBegin();
            
            // 1. Hapus data energi yang terkait
            $dataEnergyModel->where('upload_id', $uploadId)->delete();

            // 2. Hapus record upload
            $uploadModel->delete($uploadId);

            if ($uploadModel->db->transStatus() === false) {
                 $uploadModel->db->transRollback();
                 throw new \Exception('Kesalahan database saat menghapus record.');
            } else {
                 $uploadModel->db->transCommit();
            }
            
            // 3. Hapus file fisik
            $filePath = WRITEPATH . 'uploads/' . $upload['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return redirect()->to('admin/uploads')->with('message', 'File ' . esc($upload['original_name']) . ' dan data terkait berhasil dihapus.');

        } catch (\Exception $e) {
            if ($uploadModel->db->transStatus() !== false) {
                 $uploadModel->db->transRollback();
            }
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}