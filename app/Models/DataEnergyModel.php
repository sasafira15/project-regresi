<?php 

namespace App\Models;

use CodeIgniter\Model;

class DataEnergyModel extends Model 
{
    protected $table = 'tb_data_energy';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'uploaded_file_id', // DIGANTI: Menggunakan nama kolom yang konsisten dengan Uploads Controller
        'week_label', 
        'driver_m', 
        'driver_ton', 
        'total_produksi', 
        'energy_wh', 
        'energy_kwh', 
        'created_by', 
        'created_at', // Ditambahkan, karena digunakan di Controller
        'notes'
    ];
    
    // Aktifkan timestamp jika Anda ingin CI secara otomatis mengisi created_at/updated_at
    protected $useTimestamps = true; 
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at'; // Tambahkan kolom ini di DB jika diperlukan
}
