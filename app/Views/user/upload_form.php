<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<h1 class="h3 mb-4 text-gray-800">Dashboard Operator</h1>

<p class="mb-4">Selamat Datang, **<?= esc(session()->get('username')) ?>**! Silakan upload file data energi (Excel/CSV) di sini.</p>

<div class="card shadow mb-4 col-lg-8">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Form Upload File</h6>
    </div>
    <div class="card-body">
        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success" role="alert">
                <?= session()->getFlashdata('message') ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger" role="alert">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('user/upload') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="excel_file" class="form-label">Pilih File (format .xls, .xlsx, atau .csv)</label>
                <input class="form-control" type="file" id="excel_file" name="excel_file" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-upload fa-sm"></i> Upload File
            </button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>