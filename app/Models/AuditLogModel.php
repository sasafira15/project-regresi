<?php 

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model 
{
    protected $table = 'tb_audit_log';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 
        'action', 
        'entity', 
        'entity_id', 
        'details'
    ];
}