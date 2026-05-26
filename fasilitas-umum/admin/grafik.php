<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Pelaporan – Admin LaporFasum</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .chart-card {
            background: #fff; border-radius: 14px;
            padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
            margin-bottom: 24px;
        }
        .chart-card h3 {
            font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 800;
            color: #0f172a; margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .chart-full   { grid-column: 1 / -1; }
        .kpi-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 24px; }
        .kpi-card {
            background: #fff; border-radius: 14px; padding: 20px 22px;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
            border-top: 4px solid var(--kpi-color, #1a56db);
        }
        .kpi-num { font-family: 'Syne',sans-serif; font-size: 2rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .kpi-lbl { font-size: .82rem; color: #64748b; margin-top: 4px; font-weight: 500; }
        .kpi-sub { font-size: .75rem; color: #94a3b8; margin-top: 3px; }
        .period-btn {
            padding: 5px 14px; border-radius: 8px; border: 1.5px solid #e2e8f0;
            font-family: 'Space Grotesk',sans-serif; font-size: .82rem; font-weight: 600;
            cursor: pointer; background: #fff; color: #64748b; transition: all .2s;
        }
        .period-btn.active { background: #1a56db; color: #fff; border-color: #1a56db; }
    </style>
</head>
<body class="admin-body">
<?php
require_once '../includes/config.php';
requireAdmin();

// ─── KPI ───────────────────────────────────────────────
$kpi = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(status='menunggu') as menunggu,
        SUM(status='diproses') as diproses,
        SUM(status='selesai')  as selesai,
        SUM(status='ditolak')  as ditolak,
        SUM(prioritas='darurat') as darurat,
        ROUND(SUM(status='selesai')*100.0/NULLIF(COUNT(*),0),1) as pct_selesai,
        SUM(MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())) as bulan_ini
    FROM laporan
")->fetch();

// ─── Laporan per bulan (12 bulan terakhir) ─────────────
$per_bulan = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') as bln,
           COUNT(*) as total,
           SUM(status='selesai') as selesai
    FROM laporan
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY bln ORDER BY bln ASC
")->fetchAll();

// ─── Per kategori ──────────────────────────────────────
$per_kat = $pdo->query("
    SELECT k.nama, k.ikon, COUNT(l.id) as total
    FROM kategori k LEFT JOIN laporan l ON l.kategori_id=k.id
    GROUP BY k.id ORDER BY total DESC
")->fetchAll();

// ─── Per status ────────────────────────────────────────
$per_status = $pdo->query("
    SELECT status, COUNT(*) as total FROM laporan GROUP BY status
")->fetchAll();

// ─── Per prioritas ─────────────────────────────────────
$per_prio = $pdo->query("
    SELECT prioritas, COUNT(*) as total FROM laporan GROUP BY prioritas
    ORDER BY FIELD(prioritas,'darurat','tinggi','sedang','rendah')
")->fetchAll();

// ─── Tren harian 30 hari ───────────────────────────────
$tren_harian = $pdo->query("
    SELECT DATE(created_at) as tgl, COUNT(*) as total
    FROM laporan WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY tgl ORDER BY tgl ASC
")->fetchAll();

// ─── Rata-rata penyelesaian (hari) ─────────────────────
$avg_selesai = $pdo->query("
    SELECT ROUND(AVG(DATEDIFF(updated_at, created_at)),1) as avg_hari
    FROM laporan WHERE status='selesai'
")->fetchColumn();

// ─── JSON untuk Chart.js ───────────────────────────────
$js_bulan_labels  = json_encode(array_column($per_bulan, 'bln'));
$js_bulan_total   = json_encode(array_map('intval', array_column($per_bulan, 'total')));
$js_bulan_selesai = json_encode(array_map('intval', array_column($per_bulan, 'selesai')));

$js_kat_labels = json_encode(array_map(fn($k) => $k['ikon'].' '.$k['nama'], $per_kat));
$js_kat_total  = json_encode(array_map('intval', array_column($per_kat, 'total')));

$statusColors = ['menunggu'=>'#f59e0b','diproses'=>'#3b82f6','selesai'=>'#10b981','ditolak'=>'#ef4444'];
$js_status_labels = json_encode(array_map(fn($s) => ucfirst($s['status']), $per_status));
$js_status_total  = json_encode(array_map('intval', array_column($per_status, 'total')));
$js_status_colors = json_encode(array_map(fn($s) => $statusColors[$s['status']] ?? '#94a3b8', $per_status));

$prioColors = ['darurat'=>'#ef4444','tinggi'=>'#f97316','sedang'=>'#f59e0b','rendah'=>'#10b981'];
$js_prio_labels = json_encode(array_map(fn($p) => ucfirst($p['prioritas']), $per_prio));
$js_prio_total  = json_encode(array_map('intval', array_column($per_prio, 'total')));
$js_prio_colors = json_encode(array_map(fn($p) => $prioColors[$p['prioritas']] ?? '#94a3b8', $per_prio));

$js_harian_labels = json_encode(array_column($tren_harian, 'tgl'));
$js_harian_total  = json_encode(array_map('intval', array_column($tren_harian, 'total')));
?>

<!-- SIDEBAR -->
<div class="admin-sidebar">
    <div class="logo"><a href="dashboard.php">📢 LaporFasum</a><br><small style="color:rgba(255,255,255,.4);font-size:.75rem;">Admin Panel</small></div>
    <nav class="sidebar-menu">
        <a href="dashboard.php"><span>📊</span> Dashboard</a>
        <a href="laporan.php"><span>📋</span> Kelola Laporan</a>
        <a href="grafik.php" class="active"><span>📈</span> Grafik</a>
        <a href="kategori.php"><span>🏷️</span> Kategori</a>
        <a href="export.php"><span>📥</span> Export Data</a>
        <a href="logout.php"><span>🚪</span> Logout</a>
    </nav>
</div>

<!-- MAIN -->
<div class="admin-main">
    <div class="admin-topbar">
        <h1>📈 Grafik & Statistik Pelaporan</h1>
        <div class="admin-user">👤 <?= htmlspecialchars($_SESSION['admin_nama']) ?></div>
    </div>

    <div class="admin-content">

        <!-- KPI Cards -->
        <div class="kpi-row">
            <div class="kpi-card" style="--kpi-color:#1a56db;">
                <div class="kpi-num"><?= $kpi['total'] ?></div>
                <div class="kpi-lbl">📋 Total Laporan</div>
                <div class="kpi-sub">+<?= $kpi['bulan_ini'] ?> bulan ini</div>
            </div>
            <div class="kpi-card" style="--kpi-color:#10b981;">
                <div class="kpi-num"><?= $kpi['pct_selesai'] ?? 0 ?>%</div>
                <div class="kpi-lbl">✅ Tingkat Penyelesaian</div>
                <div class="kpi-sub"><?= $kpi['selesai'] ?> dari <?= $kpi['total'] ?> laporan</div>
            </div>
            <div class="kpi-card" style="--kpi-color:#f59e0b;">
                <div class="kpi-num"><?= $kpi['menunggu'] ?></div>
                <div class="kpi-lbl">⏳ Menunggu Tindakan</div>
                <div class="kpi-sub"><?= $kpi['diproses'] ?> sedang diproses</div>
            </div>
            <div class="kpi-card" style="--kpi-color:#ef4444;">
                <div class="kpi-num"><?= $kpi['darurat'] ?></div>
                <div class="kpi-lbl">🚨 Laporan Darurat</div>
                <div class="kpi-sub">Rata-rata selesai <?= $avg_selesai ?? '-' ?> hari</div>
            </div>
        </div>

        <!-- Tren Bulanan (full width) -->
        <div class="chart-card">
            <h3>📅 Tren Laporan 12 Bulan Terakhir</h3>
            <canvas id="chartBulanan" height="90"></canvas>
        </div>

        <!-- 2 kolom: kategori + status -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>🏷️ Laporan per Kategori</h3>
                <canvas id="chartKategori" height="200"></canvas>
            </div>
            <div class="chart-card">
                <h3>🔄 Distribusi Status</h3>
                <canvas id="chartStatus" height="200"></canvas>
            </div>
        </div>

        <!-- 2 kolom: prioritas + harian -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>⚡ Distribusi Prioritas</h3>
                <canvas id="chartPrioritas" height="200"></canvas>
            </div>
            <div class="chart-card">
                <h3>📆 Tren Harian 30 Hari Terakhir</h3>
                <canvas id="chartHarian" height="200"></canvas>
            </div>
        </div>

    </div><!-- /admin-content -->
</div><!-- /admin-main -->

<script>
Chart.defaults.font.family = "'Space Grotesk', sans-serif";
Chart.defaults.color = '#64748b';

// ── 1. Tren Bulanan ─────────────────────────────────────
new Chart(document.getElementById('chartBulanan'), {
    type: 'bar',
    data: {
        labels: <?= $js_bulan_labels ?>,
        datasets: [
            {
                label: 'Total Laporan',
                data: <?= $js_bulan_total ?>,
                backgroundColor: 'rgba(26,86,219,.15)',
                borderColor: '#1a56db',
                borderWidth: 2,
                borderRadius: 6,
                order: 2,
            },
            {
                label: 'Selesai',
                data: <?= $js_bulan_selesai ?>,
                type: 'line',
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,.1)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#10b981',
                pointRadius: 4,
                order: 1,
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: {
                title: labels => {
                    const d = new Date(labels[0] + '-01');
                    return d.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
                }
            }}
        },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
            x: { grid: { display: false },
                ticks: { callback: (_, i, arr) => {
                    const labels = <?= $js_bulan_labels ?>;
                    const d = new Date(labels[i] + '-01');
                    return d.toLocaleDateString('id-ID', { month: 'short', year: '2-digit' });
                }}
            }
        }
    }
});

// ── 2. Per Kategori ─────────────────────────────────────
new Chart(document.getElementById('chartKategori'), {
    type: 'bar',
    data: {
        labels: <?= $js_kat_labels ?>,
        datasets: [{
            label: 'Jumlah Laporan',
            data: <?= $js_kat_total ?>,
            backgroundColor: [
                '#3b82f6','#8b5cf6','#ec4899','#f59e0b',
                '#10b981','#06b6d4','#ef4444','#84cc16'
            ],
            borderRadius: 6,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
            y: { grid: { display: false } }
        }
    }
});

// ── 3. Status (Doughnut) ────────────────────────────────
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: <?= $js_status_labels ?>,
        datasets: [{
            data: <?= $js_status_total ?>,
            backgroundColor: <?= $js_status_colors ?>,
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 8,
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } },
            tooltip: { callbacks: {
                label: ctx => ` ${ctx.label}: ${ctx.raw} laporan`
            }}
        }
    }
});

// ── 4. Prioritas (Doughnut) ─────────────────────────────
new Chart(document.getElementById('chartPrioritas'), {
    type: 'doughnut',
    data: {
        labels: <?= $js_prio_labels ?>,
        datasets: [{
            data: <?= $js_prio_total ?>,
            backgroundColor: <?= $js_prio_colors ?>,
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 8,
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } },
        }
    }
});

// ── 5. Tren Harian (Line) ───────────────────────────────
new Chart(document.getElementById('chartHarian'), {
    type: 'line',
    data: {
        labels: <?= $js_harian_labels ?>,
        datasets: [{
            label: 'Laporan Masuk',
            data: <?= $js_harian_total ?>,
            borderColor: '#8b5cf6',
            backgroundColor: 'rgba(139,92,246,.1)',
            borderWidth: 2.5,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#8b5cf6',
            pointRadius: 3,
            pointHoverRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
            x: { grid: { display: false }, ticks: {
                maxTicksLimit: 10,
                callback: (_, i) => {
                    const labels = <?= $js_harian_labels ?>;
                    const d = new Date(labels[i]);
                    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
                }
            }}
        }
    }
});
</script>

</body>
</html>
