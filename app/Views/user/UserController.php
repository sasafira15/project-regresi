<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UploadModel;
use App\Models\DataEnergyModel;
use App\Models\RegresiResultModel;
use PhpOffice\PhpSpreadsheet\IOFactory; // PENTING: Untuk membaca Excel/CSV

class UserController extends BaseController
{
    // Inisialisasi Models
    protected $uploadModel;
    protected $dataEnergyModel;
    protected $regresiResultModel;

    public function __construct()
    {
        $this->uploadModel = new UploadModel();
        $this->dataEnergyModel = new DataEnergyModel();
        $this->regresiResultModel = new RegresiResultModel();
    }

    /**
     * Method untuk memproses file upload, ekstraksi data, dan perhitungan regresi.
     * Mengimplementasikan penanganan error (try-catch) dan validasi data.
     */
    public function upload()
    {
        // 1. Ambil File dari Request
        $file = $this->request->getFile('excel_file');
        
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid atau terjadi kesalahan upload.');
        }

        // 2. Tentukan Direktori dan Pindahkan File (Langkah 2)
        $fileName = $file->getClientName();
        $originalName = pathinfo($fileName, PATHINFO_FILENAME);
        $fileExt = $file->getClientExtension();
        $newFileName = $file->getRandomName(); 
        $uploadPath = WRITEPATH . 'uploads/'; 

        // --- Pindahkan file ke penyimpanan permanen ---
        if (!$file->move($uploadPath, $newFileName)) {
            // Kegagalan memindahkan file (misal: izin folder salah) ditangani di luar try-catch
            return redirect()->back()->with('error', 'Gagal memindahkan file ke server. Cek izin folder uploads.');
        }

        $uploadedBy = session()->get('id'); 

        // Simpan metadata upload ke tb_uploads (Status Awal: pending)
        // Insert ini harus terjadi sebelum try-catch agar kita punya $uploadId untuk diupdate jika terjadi error
        $uploadData = [
            'filename' => $newFileName,
            'filepath' => $uploadPath . $newFileName,
            'original_name' => $originalName . '.' . $fileExt,
            'uploaded_by' => $uploadedBy,
            'status' => 'pending',
        ];
        $this->uploadModel->insert($uploadData);
        $uploadId = $this->uploadModel->getInsertID();

        // ------------------------------------------------------------------
        // PENANGANAN ERROR DIMULAI: Melindungi Langkah 3-6 (Proses Berat)
        // ------------------------------------------------------------------
        try {
            // LANGKAH 3: EKSTRAKSI DAN LANGKAH 4: PENYIMPANAN DATA
            
            $reader = IOFactory::createReaderForFile($uploadPath . $newFileName);
            $spreadsheet = $reader->load($uploadPath . $newFileName);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            $dataToInsert = [];
            $totalRows = 0;

            if (count($rows) > 1) {
                foreach ($rows as $index => $row) {
                    if ($index == 0) continue; // Lewati header

                    // --- VALIDASI DATA LEBIH KUAT ---
                    // Pastikan Konsumsi Energi (Y) adalah float
                    $konsumsi = filter_var($row[1], FILTER_VALIDATE_FLOAT);
                    // Pastikan Bulan/Periode (X) adalah float/integer untuk perhitungan regresi
                    $bulan = filter_var($row[0], FILTER_VALIDATE_FLOAT); 

                    // Cek validitas Konsumsi Energi
                    if ($konsumsi === false || $konsumsi === null || $konsumsi < 0) {
                        throw new \Exception("Data Konsumsi Energi pada baris Excel ke-" . ($index + 1) . " tidak valid (harus angka positif).");
                    }
                    
                    // Cek validitas Bulan/Periode
                    if ($bulan === false || $bulan === null) {
                        throw new \Exception("Data Bulan/Periode pada baris Excel ke-" . ($index + 1) . " tidak valid (harus angka).");
                    }
                    
                    $dataToInsert[] = [
                        'upload_id' => $uploadId, 
                        'bulan' => $bulan, 
                        'konsumsi_energi' => $konsumsi, 
                    ];
                    $totalRows++;
                }
            }

            if (!empty($dataToInsert)) {
                // Bulk insert data ke tb_data_energy
                $this->dataEnergyModel->insertBatch($dataToInsert);
                
                // Update row_count di tb_uploads
                $this->uploadModel->update($uploadId, ['row_count' => $totalRows]);

                // LANGKAH 5: PERHITUNGAN REGRESI
                $energyData = $this->dataEnergyModel->where('upload_id', $uploadId)->findAll();
                
                $X = array_column($energyData, 'bulan'); 
                $Y = array_column($energyData, 'konsumsi_energi'); 

                $regresiResult = $this->calculateLinearRegression($X, $Y);

                // LANGKAH 6: PENYIMPANAN HASIL REGRESI
                $regresiData = [
                    'upload_id' => $uploadId,
                    'slope_b1' => $regresiResult['slope'],
                    'intercept_b0' => $regresiResult['intercept'],
                    'rsquare' => $regresiResult['r_squared'],
                    'status' => 'completed',
                ];
                $this->regresiResultModel->insert($regresiData);
                
                // FINAL: Update status upload menjadi 'processed'
                $this->uploadModel->update($uploadId, ['status' => 'processed']);

                return redirect()->back()->with('message', "File {$fileName} berhasil diunggah, data diimpor ({$totalRows} baris), dan hasil regresi telah dihitung!");

            } else {
                // Jika tidak ada data ditemukan dalam file
                throw new \Exception('Gagal mengimpor data. File mungkin kosong atau hanya berisi header.');
            }
        
        } catch (\Exception $e) {
            // 🚨 JIKA TERJADI ERROR DI DALAM BLOK TRY
            
            // 1. Update status di database menjadi 'failed'
            // Memastikan status upload tidak terjebak di 'pending'
            $this->uploadModel->update($uploadId, ['status' => 'failed']);
            
            // 2. Log error untuk debugging
            log_message('error', 'Upload/Processing Error for ID ' . $uploadId . ': ' . $e->getMessage());

            // 3. Kembalikan ke halaman sebelumnya dengan pesan error
            return redirect()->back()->with('error', 'Pemrosesan gagal: ' . $e->getMessage());
        }
    }

    /**
     * Fungsi sederhana untuk menghitung Regresi Linear Sederhana (Y = B0 + B1*X).
     */
    private function calculateLinearRegression($X, $Y)
    {
        $n = count($X);

        if ($n < 2) {
            // Minimal 2 titik data untuk regresi
            return ['slope' => 0, 'intercept' => 0, 'r_squared' => 0];
        }

        $sumX = array_sum($X);
        $sumY = array_sum($Y);
        $sumXY = 0;
        $sumXX = 0;

        // Hitung sumXY dan sumXX
        for ($i = 0; $i < $n; $i++) {
            $sumXY += ($X[$i] * $Y[$i]);
            $sumXX += ($X[$i] * $X[$i]);
        }

        $meanX = $sumX / $n;
        $meanY = $sumY / $n;
        
        // Menghitung Slope (B1)
        $numeratorB1 = ($n * $sumXY) - ($sumX * $sumY);
        $denominatorB1 = ($n * $sumXX) - ($sumX * $sumX);
        
        if ($denominatorB1 == 0) {
            // Mencegah pembagian dengan nol
            $B1 = 0; 
        } else {
            $B1 = $numeratorB1 / $denominatorB1;
        }

        // Menghitung Intercept (B0)
        $B0 = $meanY - ($B1 * $meanX);

        // Menghitung R-squared (Koefisien Determinasi)
        $SSR = 0; // Sum of Squares Regression
        $SST = 0; // Total Sum of Squares

        foreach ($X as $i => $x) {
            $y_pred = $B0 + $B1 * $x;
            $SSR += pow($y_pred - $meanY, 2);
            $SST += pow($Y[$i] - $meanY, 2);
        }

        $r_squared = ($SST == 0) ? 0 : $SSR / $SST;

        // Batasi nilai R-squared antara 0 dan 1 (untuk menangani floating point error kecil)
        $r_squared = max(0, min(1, $r_squared));

        return [
            'slope' => $B1,
            'intercept' => $B0,
            'r_squared' => $r_squared
        ];
    }
}