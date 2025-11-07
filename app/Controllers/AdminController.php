?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DataEnergyModel; 
use App\Models\UploadModel; // Pastikan ini ada
use App\Models\MesinModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

use Phpml\Regression\SimpleLinearRegression;
use Phpml\Metric\Regression;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use CodeIgniter\I18n\Time; // WAJIB: Tambahkan library Time CodeIgniter

class AdminController extends BaseController
{
    public function __construct()
    {
        // Pertahankan hanya inisialisasi session jika Anda menggunakannya di semua method
        $this->session = \Config\Services::session(); 
        // Hapus inisialisasi model di sini untuk mengatasi error "Controller method not found: Index"
    }

    // Menampilkan dashboard admin (form + tabel data)
    public function index()
    {
        $energyModel = new DataEnergyModel();
        $mesinModel = new MesinModel();
        // ... (sisanya tidak berubah)
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
            'username'      => session()->get('username'),
            'list_mesin'    => $mesinModel->findAll(),
            'chart_data'    => json_encode($chartData),
            'unique_weeks'  => $uniqueWeeks, // Kirim data sebagai JSON
        ];

        return view('admin/dashboard', $data);
    }
    
    // ... (method saveData, downloadLaporanMingguan, dll.)

    // Memproses data dari file upload berdasarkan upload_id (FUNGSI REVISI)
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

            // Load file Excel menggunakan PHPSpreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            
            // Menggunakan toArray(null, true, true, true) untuk mendapatkan indeks A, B, C...
            $sheetData = $sheet->toArray(null, true, true, true); 
            
            // Hapus baris header (asumsi baris 1 adalah header)
            unset($sheetData[1]); 

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

                // Hanya proses baris jika week_label tidak kosong DAN energy_kwh > 0
                if (!empty($weekLabel) && $energyKwh > 0) {
                    $dataToInsert[] = [
                        // Pastikan kolom ini sesuai dengan field di DataEnergyModel (seharusnya upload_id)
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
                // Gunakan insertBatch untuk performa yang lebih baik
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
            
            // Tampilkan error ke pengguna
            return redirect()->to('admin/uploads')->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }
    // ... (method uploadsList, deleteUpload, dll.)
}