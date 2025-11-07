<?php

namespace App\Controllers; // WAJIB: Namespace harus App\Controllers

use App\Controllers\BaseController; // WAJIB: Pastikan BaseController di-import
use App\Models\DataEnergyModel; 
use App\Models\UploadModel; 
use App\Models\MesinModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

use Phpml\Regression\SimpleLinearRegression;
use Phpml\Metric\Regression;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use CodeIgniter\I18n\Time; 

// WAJIB: Nama class harus sama persis dengan nama file (AdminController.php)
class AdminController extends BaseController 
{
    protected $session;

    public function __construct()
    {
        // Inisialisasi session
        $this->session = \Config\Services::session(); 
        // Tidak perlu memanggil parent::__construct() di CI4 kecuali ada kebutuhan khusus
    }

    /**
     * Menampilkan dashboard admin (route default: /admin/dashboard)
     */
    public function index()
    {
        // WAJIB: Pastikan method ini di-declare sebagai public
        $energyModel = new DataEnergyModel();
        $mesinModel = new MesinModel();
        
        $allEnergyData = $energyModel->orderBy('week_label', 'ASC')->findAll();
        $uniqueWeeks = $energyModel->distinct()->findColumn('week_label') ?? [];
        
        $chartData = [];
        foreach ($allEnergyData as $row) {
            // Pastikan data tidak null
            if ($row['total_produksi'] !== null && $row['energy_kwh'] !== null) {
                $chartData[] = [
                    'x' => (float)$row['total_produksi'], 
                    'y' => (float)$row['energy_kwh']
                ];
            }
        }
        
        // --- LOGIKA REGRESI DI SINI ---
        $slope = 0;
        $intercept = 0;
        $r_squared = 0;
        
        if (count($chartData) >= 2) {
            $samples = array_map(function($d) { return [$d['x']]; }, $chartData);
            $targets = array_map(function($d) { return $d['y']; }, $chartData);
            
            try {
                $regression = new SimpleLinearRegression();
                $regression->train($samples, $targets);
                
                $slope = $regression->getCoefficients()[0];
                $intercept = $regression->getCoefficients()[1];
                
                // Menghitung R-squared
                $predictions = $regression->predict($samples);
                $r_squared = Regression::rSquared($targets, $predictions);
                
            } catch (\Exception $e) {
                // Biarkan nilai tetap 0 jika terjadi error
                log_message('error', 'Error Regresi: ' . $e->getMessage());
            }
        }
        
        // Data untuk Garis Regresi
        $regressionLineData = [];
        if ($slope !== 0 || $intercept !== 0) {
            // Ambil min/max Produksi
            $minX = min(array_column($chartData, 'x'));
            $maxX = max(array_column($chartData, 'x'));

            // Tambahkan sedikit margin
            $minX = $minX - ($maxX - $minX) * 0.1;
            $maxX = $maxX + ($maxX - $minX) * 0.1;

            // Poin awal
            $regressionLineData[] = [
                'x' => round($minX),
                'y' => round(($slope * $minX) + $intercept)
            ];
            // Poin akhir
            $regressionLineData[] = [
                'x' => round($maxX),
                'y' => round(($slope * $maxX) + $intercept)
            ];
        }

        $data = [
            'username'              => session()->get('username'),
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
    
    // ... (Tambahkan method-method lain yang Anda perlukan)
    
    /**
     * Memproses data dari file upload berdasarkan upload_id (FUNGSI REVISI)
     */
    public function processUpload($uploadId)
    {
        // Pindahkan inisialisasi model ke dalam fungsi ini
        $uploadModel = new UploadModel();
        $dataEnergyModel = new DataEnergyModel();

        // 1. Ambil data upload
        $upload = $uploadModel->find($uploadId);

        if (!$upload || $upload['status'] !== 'pending') {
            return redirect()->back()->with('error', 'File tidak ditemukan atau sudah diproses.');
        }

        $filePath = WRITEPATH . 'uploads/' . $upload['filename'];
        $rows_processed = 0;

        try {
            // Cek apakah file benar-benar ada
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
            
            foreach ($sheetData as $row) {
                // ASUMSI URUTAN KOLOM EXCEL ANDA:
                // A: week_label, B: driver_m, C: driver_ton, D: total_produksi, 
                // E: energy_kwh, F: notes (Jika ada)

                $weekLabel = trim($row['A'] ?? '');
                
                // Sanitasi dan konversi nilai ke float, pastikan kolom yang kosong menjadi 0
                $driverM = (float)($row['B'] ?? 0);
                $driverTon = (float)($row['C'] ?? 0);
                $totalProduksi = (float)($row['D'] ?? 0);
                $energyKwh = (float)($row['E'] ?? 0); // Asumsi Energy_kwh di kolom E
                
                $notes = $row['F'] ?? ''; 

                if (!empty($weekLabel) && $energyKwh > 0) {
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
                throw new \Exception('Gagal memproses data. Terjadi kesalahan pada transaksi database.');
            } else {
                $dataEnergyModel->db->transCommit();
                
                // 3. Update status setelah semua data berhasil di-insert
                $uploadModel->update($uploadId, [
                    'status' => 'processed',
                    'processed_at' => Time::now()->toDateTimeString(),
                    'row_count' => $rows_processed 
                ]);
            }

            return redirect()->to('admin/uploads')->with('message', $rows_processed . ' baris data dari file ' . esc($upload['original_name']) . ' berhasil diproses.');

        } catch (\Exception $e) {
            // Rollback jika ada error
            if ($dataEnergyModel->db->transStatus() !== false) {
                 $dataEnergyModel->db->transRollback();
            }
            
            // Update status menjadi failed jika terjadi error
             $uploadModel->update($uploadId, [
                'status' => 'failed',
                'notes' => 'Error: ' . substr($e->getMessage(), 0, 250)
            ]);
            
            return redirect()->to('admin/uploads')->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }
    
    /**
     * Menampilkan daftar file upload
     */
    public function uploadsList()
    {
        $uploadModel = new UploadModel();
        $data = [
            'username' => session()->get('username'),
            'upload_data' => $uploadModel->orderBy('uploaded_at', 'DESC')->findAll(),
        ];
        return view('admin/uploads_list', $data);
    }
    
    /**
     * Menghapus file upload dan data energinya
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
            // 1. Hapus data energi yang terkait
            $dataEnergyModel->where('upload_id', $uploadId)->delete();

            // 2. Hapus record upload
            $uploadModel->delete($uploadId);

            // 3. Hapus file fisik (Opsional, tapi disarankan)
            $filePath = WRITEPATH . 'uploads/' . $upload['filename'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return redirect()->to('admin/uploads')->with('message', 'File ' . esc($upload['original_name']) . ' dan data terkait berhasil dihapus.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
    
    // Tambahkan method lain yang Anda butuhkan (misalnya: downloadFile, detailUpload)
}