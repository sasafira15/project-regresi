<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-bar me-2"></i>Dashboard Analisis Energi
        </h1>
        <div class="d-none d-sm-inline-block">
            <small class="text-muted">
                <i class="far fa-clock me-1"></i>
                <?= date('d M Y, H:i') ?>
            </small>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Berhasil!</strong> <?= session()->getFlashdata('message') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Error!</strong> <?= session()->getFlashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Kolom Kiri: Daftar Mesin & Metrics -->
        <div class="col-lg-4">
            <!-- Card Daftar Mesin -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h6 class="m-0 font-weight-bold text-white">
                        <i class="fas fa-cogs me-2"></i>Daftar Mesin
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($list_mesin)): ?>
                        <div class="mb-3" style="max-height: 250px; overflow-y: auto;">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($list_mesin as $mesin): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-3 border-bottom">
                                    <span class="d-flex align-items-center">
                                        <div class="icon-circle bg-primary mr-2" style="width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-industry text-white" style="font-size: 14px;"></i>
                                        </div>
                                        <span class="font-weight-bold text-gray-700">
                                            <?= esc($mesin['nama_mesin']) ?>
                                        </span>
                                    </span>
                                    <a href="<?= site_url('admin/mesin/hapus/' . $mesin['id']) ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('Yakin ingin menghapus mesin ini?')"
                                       title="Hapus Mesin"
                                       style="border-radius: 50%; width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-inbox fa-3x text-gray-300"></i>
                            </div>
                            <p class="text-muted mb-0 font-weight-bold">Belum ada mesin terdaftar</p>
                            <small class="text-muted">Tambahkan mesin baru untuk memulai</small>
                        </div>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-primary btn-block mt-3 shadow-sm" data-toggle="modal" data-target="#tambahMesinModal">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Mesin Baru
                    </button>
                </div>
            </div>

            <!-- Metric Cards dengan Animasi -->
            <div class="card border-left-info shadow mb-4 animate__animated animate__fadeInLeft">
                <div class="card-body py-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                <i class="fas fa-chart-line me-1"></i>Slope
                            </div>
                            <div id="slope-value" class="h4 mb-0 font-weight-bold text-gray-800">-</div>
                            <small class="text-muted">Kemiringan garis regresi</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-left-success shadow mb-4 animate__animated animate__fadeInLeft" style="animation-delay: 0.1s;">
                <div class="card-body py-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                <i class="fas fa-plug me-1"></i>Baseload (kWh)
                            </div>
                            <div id="baseload-value" class="h4 mb-0 font-weight-bold text-gray-800">-</div>
                            <small class="text-muted">Konsumsi energi dasar</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-plug fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            

            <div class="card border-left-primary shadow mb-4 animate__animated animate__fadeInLeft" style="animation-delay: 0.2s;">
                <div class="card-body py-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                <i class="fas fa-check-circle me-1"></i>R² Score
                            </div>
                            <div id="r2-value" class="h4 mb-0 font-weight-bold text-gray-800">-</div>
                            <small class="text-muted">Akurasi model prediksi</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-download me-2"></i>Download Laporan Mingguan</h6>
            </div>
            <div class="card-body">
                <form action="<?= site_url('admin/laporan/download/mingguan') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="week_label_download">Pilih Minggu</label>
                        <select class="form-control" id="week_label_download" name="week_label" required>
                            <option value="">-- Pilih Minggu --</option>
                            <?php if (!empty($unique_weeks)): ?>
                                <?php foreach ($unique_weeks as $week): ?>
                                    <option value="<?= esc($week) ?>"><?= esc($week) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">
                        Download Laporan
                    </button>
                </form>
            </div>
        </div>
        </div>

        
        <!-- Kolom Kanan: Grafik -->
        <div class="col-lg-8">
            <div class="card shadow mb-4 animate__animated animate__fadeInRight">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h6 class="m-0 font-weight-bold text-white">
                        <i class="fas fa-chart-area me-2"></i>Grafik Regresi Linier
                    </h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle text-white" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Opsi Grafik:</div>
                            <a class="dropdown-item" href="#" onclick="downloadChart()">
                                <i class="fas fa-download fa-sm fa-fw mr-2 text-gray-400"></i>
                                Download Grafik
                            </a>
                            <a class="dropdown-item" href="#" onclick="resetZoom()">
                                <i class="fas fa-sync fa-sm fa-fw mr-2 text-gray-400"></i>
                                Reset Zoom
                            </a>
                        </div>
                        
                    </div>
                </div>
                <div class="card-body">
                    <div id="chart-container" class="chart-area" style="position: relative; height: 450px;">
                        <canvas id="regressionChart"></canvas>
                    </div>
                    <div id="chart-info" class="mt-3 p-3 bg-light rounded" style="display: none;">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="font-weight-bold text-primary">Total Data Points</div>
                                <div id="data-count" class="h5 mb-0">-</div>
                            </div>
                            <div class="col-md-4">
                                <div class="font-weight-bold text-success">Min Energi</div>
                                <div id="min-energy" class="h5 mb-0">-</div>
                            </div>
                            <div class="col-md-4">
                                <div class="font-weight-bold text-danger">Max Energi</div>
                                <div id="max-energy" class="h5 mb-0">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Mesin -->
<div class="modal fade" id="tambahMesinModal" tabindex="-1" role="dialog" aria-labelledby="tambahMesinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white" id="tambahMesinModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Mesin Baru
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="<?= site_url('admin/mesin/tambah') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama_mesin" class="font-weight-bold">
                            <i class="fas fa-tag me-1"></i>Nama Mesin
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="nama_mesin" 
                               name="nama_mesin" 
                               placeholder="Contoh: Mesin Produksi A1" 
                               required
                               autocomplete="off">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Gunakan nama yang mudah diidentifikasi
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Simpan Mesin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('page_scripts') ?>
<script src="<?= base_url('assets/vendor/chart.js/Chart.min.js') ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/regression-js/2.0.1/regression.min.js"></script>
<script>
let regressionChart; // Global variable untuk chart

document.addEventListener("DOMContentLoaded", function() {
    // 1. Ambil data mentah dari PHP
    const rawData = JSON.parse('<?= $chart_data ?>');

    // 2. Lakukan perhitungan hanya jika data cukup
    if (rawData.length > 1) {
        // Ubah format data untuk library regression-js: [[x1, y1], [x2, y2]]
        const dataForRegression = rawData.map(p => [p.x, p.y]);

        // 3. Lakukan perhitungan regresi menggunakan regression-js
        const result = regression.linear(dataForRegression);
        const slope = result.equation[0];
        const baseload = result.equation[1];
        const r2 = result.r2;

        // 4. Update kartu statistik dengan hasil perhitungan dan animasi
        animateValue('slope-value', 0, slope, 1000, 4);
        animateValue('baseload-value', 0, baseload, 1000, 2);
        animateValue('r2-value', 0, r2, 1000, 4);

        // Update info tambahan
        const energyValues = rawData.map(p => p.y);
        document.getElementById('data-count').innerText = rawData.length;
        document.getElementById('min-energy').innerText = Math.min(...energyValues).toFixed(2) + ' kWh';
        document.getElementById('max-energy').innerText = Math.max(...energyValues).toFixed(2) + ' kWh';
        document.getElementById('chart-info').style.display = 'block';

        // 5. Siapkan data untuk Chart.js
        const regressionLineData = result.points.map(p => ({ x: p[0], y: p[1] }));

        // 6. Gambar grafik dengan styling yang lebih baik
        const ctx = document.getElementById('regressionChart').getContext('2d');
        regressionChart = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Data Asli (Produksi vs Energi)',
                    data: rawData,
                    backgroundColor: 'rgba(78, 115, 223, 0.6)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2
                }, {
                    label: 'Garis Regresi',
                    data: regressionLineData,
                    type: 'line',
                    fill: false,
                    borderColor: 'rgba(231, 74, 59, 1)',
                    borderWidth: 3,
                    pointRadius: 0,
                    borderDash: [10, 5],
                    tension: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 13,
                                family: "'Nunito', sans-serif"
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 15,
                        titleFont: {
                            size: 14,
                            family: "'Nunito', sans-serif"
                        },
                        bodyFont: {
                            size: 13,
                            family: "'Nunito', sans-serif"
                        },
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return ` Produksi: ${context.parsed.x.toFixed(0)} | Energi: ${context.parsed.y.toFixed(2)} kWh`;
                                } else {
                                    return ` Prediksi: ${context.parsed.y.toFixed(2)} kWh`;
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'linear',
                        position: 'bottom',
                        title: {
                            display: true,
                            text: 'Total Produksi',
                            font: {
                                size: 14,
                                weight: 'bold',
                                family: "'Nunito', sans-serif"
                            },
                            color: '#5a5c69'
                        },
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                family: "'Nunito', sans-serif"
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Energi (kWh)',
                            font: {
                                size: 14,
                                weight: 'bold',
                                family: "'Nunito', sans-serif"
                            },
                            color: '#5a5c69'
                        },
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                family: "'Nunito', sans-serif"
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    intersect: false
                }
            }
        });
    } else {
        // Tampilkan pesan jika data tidak cukup
        document.getElementById('chart-container').innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-chart-line fa-4x text-gray-300 mb-3"></i>
                <h5 class="text-gray-600">Belum Ada Data</h5>
                <p class="text-muted">Upload atau tambahkan data energi untuk melihat analisis regresi.</p>
            </div>
        `;
    }
});

// Fungsi animasi angka
function animateValue(id, start, end, duration, decimals) {
    const element = document.getElementById(id);
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    const timer = setInterval(function() {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        element.innerText = current.toFixed(decimals);
    }, 16);
}

// Fungsi download chart
function downloadChart() {
    if (regressionChart) {
        const link = document.createElement('a');
        link.download = 'grafik-regresi-energi.png';
        link.href = regressionChart.toBase64Image();
        link.click();
    }
}

// Fungsi reset zoom
function resetZoom() {
    if (regressionChart) {
        regressionChart.resetZoom();
    }
}
</script>
<?= $this->endSection() ?>