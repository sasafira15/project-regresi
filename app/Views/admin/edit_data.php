<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<h1 class="h3 mb-4 text-gray-800">Edit Data Energi</h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Form Edit Data untuk Minggu: <?= esc($data['week_label']) ?></h6>
    </div>
    <div class="card-body">
        <form action="<?= site_url('admin/data/update/' . $data['id']) ?>" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="week_label" class="form-label">Label Minggu</label>
                <input type="text" class="form-control" id="week_label" name="week_label" value="<?= esc($data['week_label']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="total_produksi" class="form-label">Total Produksi</label>
                <input type="number" step="0.01" class="form-control" id="total_produksi" name="total_produksi" value="<?= esc($data['total_produksi']) ?>">
            </div>
            <div class="mb-3">
                <label for="energy_kwh" class="form-label">Energi (kWh)</label>
                <input type="number" step="0.01" class="form-control" id="energy_kwh" name="energy_kwh" value="<?= esc($data['energy_kwh']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Catatan</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?= esc($data['notes']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Data</button>
            <a href="<?= site_url('admin/dashboard') ?>" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>

<?= $this->endSection() ?>