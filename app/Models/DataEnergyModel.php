<?php 

namespace App\Models;

use CodeIgniter\Model;

class DataEnergyModel extends Model 
{
    protected $table = 'tb_data_energy';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'upload_id', 
        'week_label', 
        'driver_m', 
        'driver_ton', 
        'total_produksi', 
        'energy_wh', 
        'energy_kwh', 
        'created_by', 
        'notes'
    ];
}