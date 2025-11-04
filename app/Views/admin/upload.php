<?php namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UploadModel;
use App\Models\DataEnergyModel; // Pastikan model ini ada untuk tabel data utama
use PhpOffice\PhpSpreadsheet\IOFactory; // Library untuk membaca Excel

// Pastikan library PHPSpreadsheet sudah terinstal: composer require phpoffice/phpspreadsheet

class Uploads extends BaseController
{
    protected $uploadModel;
    protected $dataEnergyModel;

    public function __construct()
    {
        // Panggil model yang diperlukan
        $this->uploadModel = new UploadModel();
        // Ganti DataEnergyModel jika nama model tabel utama Anda berbeda
        $this->dataEnergyModel = new DataEnergyModel(); 
    }

    // Fungsi utama: Menampilkan daftar semua file unggahan
    public function index()
    {
        // Mengambil semua data dari tabel tb_uploads
        $data['upload_data'] = $this->uploadModel->findAll();
        // Menampilkan view yang sudah Anda berikan sebelumnya
        return view('admin/uploads_list', $data); 
    }
    
    // ====================================================================
    // 🎯 FUNGSI INI MENGATASI STATUS 'PENDING'
    // Dipanggil saat admin mengklik URL: /admin/uploads/process/{id}
    // ====================================================================
    public function process($id)
    {
        $upload = $this->uploadModel->find($id);

        if (!$upload) {
            session()->setFlashdata('error', 'File unggahan tidak ditemukan.');
            return redirect()->to(site_url('admin/uploads'));
        }

        // Cek status, hanya pending yang bisa diproses
        if ($upload['status'] !== 'pending') {
            session()->setFlashdata('error', 'File ini sudah diproses atau gagal. Tidak dapat diproses ulang.');
            return redirect()->to(site_url('admin/uploads'));
        }

        try {
            $filePath = WRITEPATH . 'uploads/' . $upload['file_name'];

            if (!file_exists($filePath)) {
                throw new \Exception("File fisik tidak ditemukan di server: " . $upload['file_name']);
            }
            
            // 1. Membaca file Excel menggunakan PHPSpreadsheet
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $sheetData = $sheet->toArray(null, true, true, true);
            
            // Hilangkan baris header (Baris 1)
            unset($sheetData[1]); 
            
            $rows_processed = 0;
            
            // 2. Looping data dari Excel dan Menyimpan ke database utama
            foreach ($sheetData as $row) {
                // !!! SANGAT KRUSIAL: Sesuaikan pemetaan kolom ini dengan format Excel Anda !!!
                $dataToInsert = [
                    // 'nama_kolom_database' => $row['Kolom_Excel_A_B_C']
                    'tanggal' => $row['A'], 
                    'nilai_a' => $row['B'], 
                    'nilai_b' => $row['C'], 
                    'uploaded_file_id' => $id, 
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                // Simpan data ke tabel utama (DataEnergyModel)
                if (!empty($dataToInsert['tanggal']) && is_numeric($dataToInsert['nilai_a'])) {
                    $this->dataEnergyModel->insert($dataToInsert);
                    $rows_processed++;
                }
            }

            // 3. Update status menjadi 'processed' (Selesai)
            $this->uploadModel->update($id, [
                'status' => 'processed',
                'processed_at' => date('Y-m-d H:i:s'),
                'row_count' => $rows_processed
            ]);

            session()->setFlashdata('message', 'File ' . esc($upload['original_name']) . ' berhasil diproses. Sebanyak ' . $rows_processed . ' baris data telah diimpor.');
            
        } catch (\Exception $e) {
            // Jika ada error, ubah status menjadi 'failed'
            $this->uploadModel->update($id, [
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            log_message('error', 'Processing failed for file ID ' . $id . ': ' . $e->getMessage());
            session()->setFlashdata('error', 'Gagal memproses file. Detail: ' . $e->getMessage());
        }

        return redirect()->to(site_url('admin/uploads'));
    }

    // Metode untuk menghapus file dan data terkait
    public function delete($id)
    {
        // Hapus data terkait di tabel utama
        $this->dataEnergyModel->where('uploaded_file_id', $id)->delete();

        // Hapus file fisik
        $upload = $this->uploadModel->find($id);
        if ($upload) {
            $filePath = WRITEPATH . 'uploads/' . $upload['file_name'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Hapus entri dari tb_uploads
        $this->uploadModel->delete($id);

        session()->setFlashdata('message', 'File dan data terkait berhasil dihapus.');
        return redirect()->to(site_url('admin/uploads'));
    }

    // Tambahkan metode download jika diperlukan
    // public function download($id) { ... }
}