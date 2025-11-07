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
use CodeIgniter\I18n\Time; // WAJIB: Tambahkan library Time CodeIgniter

class AdminController extends BaseController
{
    public function __construct()
    {
        $this->session = \Config\Services::session();
        // Inisialisasi model di constructor agar bisa diakses di semua method, termasuk processUpload
        $this->uploadModel = new UploadModel();
        $this->dataEnergyModel = new DataEnergyModel();
    }
    
    // ... (method index, saveData, downloadLaporanMingguan, tambahMesin, downloadLaporan, hapusMesin, create, uploadsList, deleteUpload, downloadFile, edit, update, delete, lainnya di sini)

    // Memproses data dari file upload berdasarkan upload_id
    public function processUpload($uploadId)
    {
        // 1. Ambil data upload
        $upload = $this->uploadModel->find($uploadId);

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

            $this->dataEnergyModel->db->transBegin(); // Mulai transaksi DB

            $adminId = $this->session->get('user_id') ?? 1; 

            $dataToInsert = [];
            
            foreach ($sheetData as $row) {
                // ASUMSI URUTAN KOLOM EXCEL ANDA:
                // A: week_label, B: driver_m, C: driver_ton, D: total_produksi, 
                // E: energy_kwh (Revisi: jika energy_wh tidak ada, Energy_kwh harusnya di kolom E)
                // F: notes (Jika ada 6 kolom)
                
                // Pastikan indeks kolom sesuai dengan file Excel Anda.
                // Jika Energy_kwh ada di kolom E dan tidak ada kolom Energy_wh, 
                // Anda hanya perlu menyesuaikan pemetaan di sini.

                $weekLabel = trim($row['A'] ?? '');
                
                // Sanitasi dan konversi nilai ke float, pastikan kolom yang kosong menjadi 0
                $driverM = (float)($row['B'] ?? 0);
                $driverTon = (float)($row['C'] ?? 0);
                $totalProduksi = (float)($row['D'] ?? 0);
                $energyKwh = (float)($row['E'] ?? 0); // Asumsi Energy_kwh di kolom E
                
                // Jika kolom F di Excel Anda adalah NOTES, gunakan indeks F.
                $notes = $row['F'] ?? ''; 

                // Hanya proses baris jika week_label tidak kosong DAN energy_kwh > 0
                if (!empty($weekLabel) && $energyKwh > 0) {
                    $dataToInsert[] = [
                        'uploaded_file_id' => $uploadId, 
                        'week_label' => $weekLabel,
                        'driver_m' => $driverM,
                        'driver_ton' => $driverTon,
                        'total_produksi' => $totalProduksi,
                        // 'energy_wh' tetap NULL/0 jika kolomnya tidak ada di Excel
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
                $this->dataEnergyModel->insertBatch($dataToInsert);
            }


            if ($this->dataEnergyModel->db->transStatus() === false) {
                $this->dataEnergyModel->db->transRollback();
                throw new \Exception('Gagal memproses data. Terjadi kesalahan pada transaksi database.');
            } else {
                $this->dataEnergyModel->db->transCommit();
                
                // 3. Update status setelah semua data berhasil di-insert
                $this->uploadModel->update($uploadId, [
                    'status' => 'processed',
                    'processed_at' => Time::now()->toDateTimeString(),
                    'row_count' => $rows_processed 
                ]);
            }

            return redirect()->to('admin/uploads')->with('message', $rows_processed . ' baris data dari file ' . esc($upload['original_name']) . ' berhasil diproses.');

        } catch (\Exception $e) {
            // Rollback jika ada error
            if ($this->dataEnergyModel->db->transStatus() !== false) {
                 $this->dataEnergyModel->db->transRollback();
            }
            
            // Update status menjadi failed jika terjadi error
             $this->uploadModel->update($uploadId, [
                'status' => 'failed',
                'notes' => 'Error: ' . substr($e->getMessage(), 0, 250)
            ]);
            
            // Tampilkan error ke pengguna
            return redirect()->to('admin/uploads')->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }
}
