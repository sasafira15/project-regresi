<?php $this->extend('layouts/main') ?>

<?php $this->section('content') ?>

<h1 class="h3 mb-4 text-gray-800">Tambah Data Energi Manual</h1>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('message') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-edit me-2"></i>Form Input Data Manual
                </h6>
            </div>
            <div class="card-body">
                <form action="<?= site_url('admin/save') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="form-group mb-3">
                        <label for="week_label" class="form-label font-weight-bold">Label Minggu</label>
                        <input type="text" class="form-control" id="week_label" name="week_label" placeholder="Contoh: W1, W2, W3" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="driver_m" class="form-label font-weight-bold">Driver (M)</label>
                            <input type="number" step="0.01" class="form-control" id="driver_m" name="driver_m" placeholder="0.00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="driver_ton" class="form-label font-weight-bold">Driver (Ton)</label>
                            <input type="number" step="0.01" class="form-control" id="driver_ton" name="driver_ton" placeholder="0.00">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="total_produksi" class="form-label font-weight-bold">Total Produksi</label>
                        <input type="number" step="0.01" class="form-control" id="total_produksi" name="total_produksi" placeholder="0.00">
                    </div>

                    <div class="form-group mb-3">
                        <label for="energy_kwh" class="form-label font-weight-bold">Energi (kWh) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" id="energy_kwh" name="energy_kwh" placeholder="0.00" required>
                    </div>

                    <div class="form-group mb-4">
                        <label for="notes" class="form-label font-weight-bold">Catatan</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Tambahkan keterangan tambahan (opsional)"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                    <a href="<?= site_url('admin/dashboard') ?>" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>

    </div>
</div>

<?php $this->endSection() ?>