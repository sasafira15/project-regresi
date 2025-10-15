<?php 

namespace App\Models;

use CodeIgniter\Model;

class UploadModel extends Model 
{
    protected $table = 'tb_uploads';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'filename', 
        'filepath', 
        'original_name', 
        'uploaded_by', 
        'row_count'
    ];
}