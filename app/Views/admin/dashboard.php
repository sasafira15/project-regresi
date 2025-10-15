<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<h1 class="h3 mb-4 text-gray-800">Dashboard Admin</h1>

<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('message') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="row">

    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Grafik Regresi Linier</h6>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <p class="text-muted">Area untuk grafik akan ditampilkan di sini.</p>
                    </div>
            </div>
        </div>
    </div>

    <div class="col-12">
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
                                <th>Total Produksi</th>
                                <th>Energi (kWh)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($energy_data)): ?>
                                <?php foreach ($energy_data as $data): ?>
                                <tr>
                                    <td><?= esc($data['week_label']) ?></td>
                                    <td><?= esc($data['total_produksi']) ?></td>
                                    <td><?= esc($data['energy_kwh']) ?></td>
                                    <td class="text-center">
                                        <a href="<?= site_url('admin/data/edit/' . $data['id']) ?>" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= site_url('admin/data/delete/' . $data['id']) ?>" class="btn btn-danger btn-sm" title="Hapus" onclick="return confirm('Apakah Anda yakin?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Belum ada data.</td>
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