<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Kelola File Unggahan</h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-file-upload mr-3"></i>File Unggahan dari Operator
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width: 35%;">Nama File</th>
                            <th class="text-center" style="width: 20%;">Tanggal Upload</th>
                            <th class="text-center" style="width: 15%;">Status</th>
                            <th class="text-center" style="width: 30%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($upload_data)): ?>
                            <?php foreach ($upload_data as $upload): ?>
                            <tr>
                                <td class="align-middle">
                                    <i class="fas fa-file-excel text-success mr-2"></i>
                                    <?= esc($upload['original_name']) ?>
                                </td>
                                <td class="align-middle text-center">
                                    <small><?= esc($upload['uploaded_at']) ?></small>
                                </td>
                                <td class="align-middle text-center">
                                    <?php if ($upload['status'] == 'processed'): ?>
                                        <span class="badge badge-primary px-3 py-2">
                                            <i class="fas fa-check-circle mr-1"></i>Selesai
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning px-3 py-2">
                                            <i class="fas fa-clock mr-1"></i>Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle text-center">
                                    <a href="<?= site_url('admin/uploads/download/' . $upload['id']) ?>" 
                                       class="btn btn-info btn-sm mr-2" 
                                       title="Lihat/Unduh File">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <?php if ($upload['status'] == 'pending'): ?>
                                        <!-- START: Perubahan menggunakan FORM untuk menghindari masalah klik -->
                                        <form method="post" action="<?= site_url('admin/uploads/process/' . $upload['id']) ?>" style="display:inline-block;">
                                            <!-- Tombol Proses sekarang menjadi SUBMIT button -->
                                            <button type="submit" 
                                                    class="btn btn-success btn-sm mr-2" 
                                                    title="Proses File dan Simpan Data"
                                                    onclick="return confirm('Apakah Anda yakin ingin memproses file ini? Data akan disimpan ke database utama.')">
                                                <i class="fas fa-cogs"></i>
                                            </button>
                                        </form>
                                        <!-- END: Perubahan menggunakan FORM -->
                                    <?php endif; ?>

                                    <a href="<?= site_url('admin/uploads/delete/' . $upload['id']) ?>" 
                                       class="btn btn-danger btn-sm" 
                                       title="Hapus" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus file dan semua data terkait?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    <h5>Belum ada file yang di-upload.</h5>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($upload_data)): ?>
                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Total: <?= count($upload_data) ?> file terunggah
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>