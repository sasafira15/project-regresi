<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UploadModel; 

class UserController extends BaseController
{
    public function __construct()
    {
        $this->session = \Config\Services::session();
    }

    // menampilkan halaman dengan form supload
    public function index()
    {
        $data['username'] = $this->session->get('username');
        return view('user/upload_form', $data);
    }

    // Memproses file upload
    public function uploadFile()
    {
        $validationRule = [
            'excel_file' => [
                'label' => 'Excel File',
                'rules' => 'uploaded[excel_file]'
                    . '|ext_in[excel_file,xls,xlsx,csv]' 
                    . '|max_size[excel_file,5000]', // Maksimal 5MB
            ],
        ];

        if (!$this->validate($validationRule)) {
            // Jika validasi gagal, kembali dengan error
            return redirect()->to('/user/dashboard')->with('error', $this->validator->getErrors()['excel_file']);
        }

        $file = $this->request->getFile('excel_file');
        if ($file->isValid() && !$file->hasMoved()) {
            // Pindahkan file ke folder 'writable/uploads'
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads', $newName);

            // Simpan info file ke database
            $uploadModel = new UploadModel();
            $uploadModel->save([
                'filename' => $newName,
                'filepath' => 'writable/uploads/' . $newName,
                'original_name' => $file->getClientName(),
                'uploaded_by' => $this->session->get('user_id'),
            ]);

            return redirect()->to('/user/dashboard')->with('message', 'File berhasil di-upload!');
        }

        return redirect()->to('/user/dashboard')->with('error', 'Gagal memindahkan file.');
    }
}