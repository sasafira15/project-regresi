<?php 

namespace App\Models;

use CodeIgniter\Model;

class RegresiResultModel extends Model 
{
    protected $table = 'tb_regresi_result';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'slope', 
        'baseload', 
        'r2', 
        'n_points', 
        'x_field', 
        'y_field', 
        'data_from_upload_id', 
        'created_by'
    ];
}