<?php

namespace App\Models;

use CodeIgniter\Model;

Class UserModel extends Model
{
    protected $table = 'tb_user';
    protected $primaryKey = 'id';
    protected $allowedFields    = ['nama_teknisi', 'username', 'password', 'role'];
}
    