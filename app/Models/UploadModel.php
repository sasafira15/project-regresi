<?php

namespace App\Models;

use CodeIgniter\Model;

class UploadModel extends Model
{
    protected $table            = 'tb_uploads';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'original_name', 
        'filename', 
        'status', 
        'row_count', 
        'notes', 
        'uploaded_by', 
        'processed_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'uploaded_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}