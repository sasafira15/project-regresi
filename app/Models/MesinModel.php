<?php 
namespace App\Models;

use CodeIgniter\Model;

class MesinModel extends Model 
{
    protected $table = 'tb_mesin';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nama_mesin'];
}