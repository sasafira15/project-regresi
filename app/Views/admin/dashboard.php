<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<h1 class="h3 mb-4 text-gray-800">Dashboard Admin</h1>

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

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Form Input Data Manual</h6>
            </div>
            <div class="card-body">
                <form action="<?= site_url('admin/save') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="week_label" class="form-label">Label Minggu (Contoh: W1, W2)</label>
                        <input type="text" class="form-control" id="week_label" name="week_label" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="driver_m" class="form-label">Driver (M)</label>
                            <input type="number" step="0.01" class="form-control" id="driver_m" name="driver_m">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="driver_ton" class="form-label">Driver (Ton)</label>
                            <input type="number" step="0.01" class="form-control" id="driver_ton" name="driver_ton">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="total_produksi" class="form-label">Total Produksi</label>
                        <input type="number" step="0.01" class="form-control" id="total_produksi" name="total_produksi">
                    </div>
                    <div class="mb-3">
                        <label for="energy_kwh" class="form-label">Energi (kWh)</label>
                        <input type="number" step="0.01" class="form-control" id="energy_kwh" name="energy_kwh" required>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Data Energi Tersimpan</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Minggu</th>
                                <th>Energi (kWh)</th>
                                <th>Total Produksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($energy_data)): ?>
                                <?php foreach ($energy_data as $data): ?>
                                <tr>
                                    <td><?= esc($data['week_label']) ?></td>
                                    <td><?= esc($data['energy_kwh']) ?></td>
                                    <td><?= esc($data['total_produksi']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">Belum ada data.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>