<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UploadModel;
use App\Models\DataEnergyModel;
use App\Models\RegresiResultModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
     * Mengimplementasikan langkah 2, 3, 4, 5, dan 6.
     */
    public function upload()
    {
        // 1. Ambil File dari Request
        $file = $this->request->getFile('excel_file');
        
        // Cek apakah file valid dan tidak ada error
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid atau terjadi kesalahan.');
        }

        // 2. Tentukan Direktori dan Pindahkan File (Langkah 2)
        $fileName = $file->getClientName();
        $originalName = pathinfo($fileName, PATHINFO_FILENAME);
        $fileExt = $file->getClientExtension();
        $newFileName = $file->getRandomName(); // Nama unik untuk keamanan
        $uploadPath = WRITEPATH . 'uploads/'; // Direktori penyimpanan di server

        // Pindahkan file dari temp ke direktori penyimpanan
        $file->move($uploadPath, $newFileName);

        $uploadedBy = session()->get('id'); // Ambil ID pengguna dari sesi

        // Simpan metadata upload ke tb_uploads
        $uploadData = [
            'filename' => $newFileName,
            'filepath' => $uploadPath . $newFileName,
            'original_name' => $originalName . '.' . $fileExt,
            'uploaded_by' => $uploadedBy,
            'status' => 'pending',
            // 'row_count' akan diupdate setelah proses selesai
        ];
        $this->uploadModel->insert($uploadData);
        $uploadId = $this->uploadModel->getInsertID();

        // ----------------------------------------------------
        // LANGKAH 3: EKSTRAKSI DAN LANGKAH 4: PENYIMPANAN DATA
        // ----------------------------------------------------
        
        $reader = IOFactory::createReaderForFile($uploadPath . $newFileName);
        $spreadsheet = $reader->load($uploadPath . $newFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        $dataToInsert = [];
        $totalRows = 0;

        // Asumsi: Baris pertama adalah header, data dimulai dari baris ke-2 (indeks 1)
        if (count($rows) > 1) {
            foreach ($rows as $index => $row) {
                if ($index == 0) continue; // Lewati header

                // Asumsi Struktur Excel:
                // Kolom A (Index 0): Bulan/Periode (Variabel X)
                // Kolom B (Index 1): Konsumsi Energi (Variabel Y)
                
                // Lakukan validasi data di sini (misal: cek format bulan, pastikan numerik)
                
                $dataToInsert[] = [
                    'upload_id' => $uploadId, // FK ke tb_uploads
                    'bulan' => $row[0], 
                    'konsumsi_energi' => (float) $row[1], 
                    // Tambahkan kolom lain jika ada
                ];
                $totalRows++;
            }
        }

        if (!empty($dataToInsert)) {
            // Bulk insert data ke tb_data_energy
            $this->dataEnergyModel->insertBatch($dataToInsert);
            
            // Update row_count di tb_uploads
            $this->uploadModel->update($uploadId, ['row_count' => $totalRows]);

            // ----------------------------------------------------
            // LANGKAH 5: PERHITUNGAN REGRESI
            // ----------------------------------------------------
            
            // Ambil data yang baru saja diimpor untuk perhitungan
            $energyData = $this->dataEnergyModel->where('upload_id', $uploadId)->findAll();
            
            // Siapkan array X dan Y
            $X = array_column($energyData, 'bulan'); // Variabel Bebas (X)
            $Y = array_column($energyData, 'konsumsi_energi'); // Variabel Terikat (Y)

            // Jalankan fungsi regresi (Lihat fungsi di bawah)
            $regresiResult = $this->calculateLinearRegression($X, $Y);

            // ----------------------------------------------------
            // LANGKAH 6: PENYIMPANAN HASIL REGRESI
            // ----------------------------------------------------
            
            $regresiData = [
                'upload_id' => $uploadId,
                'slope_b1' => $regresiResult['slope'],
                'intercept_b0' => $regresiResult['intercept'],
                'rsquare' => $regresiResult['r_squared'],
                'status' => 'completed',
                // Tambahkan kolom hasil regresi lain jika ada
            ];
            $this->regresiResultModel->insert($regresiData);
            
            // Update status upload menjadi 'processed'
            $this->uploadModel->update($uploadId, ['status' => 'processed']);

            return redirect()->back()->with('message', "File {$fileName} berhasil diunggah, data diimpor ({$totalRows} baris), dan hasil regresi telah dihitung!");

        } else {
            // Jika tidak ada data ditemukan dalam file
            $this->uploadModel->update($uploadId, ['status' => 'failed']);
            return redirect()->back()->with('error', 'Gagal mengimpor data. File mungkin kosong atau format tidak sesuai.');
        }
    }

    /**
     * Fungsi sederhana untuk menghitung Regresi Linear Sederhana (Y = B0 + B1*X).
     */
    private function calculateLinearRegression($X, $Y)
    {
        $n = count($X);

        if ($n < 2) {
             return ['slope' => 0, 'intercept' => 0, 'r_squared' => 0];
        }

        $sumX = array_sum($X);
        $sumY = array_sum($Y);
        $sumXY = 0;
        $sumXX = 0;

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
            // Mencegah pembagian dengan nol (biasanya terjadi jika semua nilai X sama)
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

        return [
            'slope' => $B1,
            'intercept' => $B0,
            'r_squared' => $r_squared
        ];
    }
}