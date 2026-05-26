<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data – Admin LaporFasum</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php
require_once '../includes/config.php';
requireAdmin();

// Download CSV
if (isset($_GET['download'])) {
    $status_f = $_GET['status'] ?? '';
    $where = '1=1'; $params = [];
    if ($status_f) { $where .= ' AND l.status = ?'; $params[] = $status_f; }

    $stmt = $pdo->prepare("SELECT l.kode_laporan, l.nama_pelapor, l.email_pelapor, l.no_hp, k.nama as kategori, l.judul, l.deskripsi, l.lokasi, l.status, l.prioritas, l.catatan_admin, l.created_at FROM laporan l JOIN kategori k ON l.kategori_id=k.id WHERE $where ORDER BY l.created_at DESC");
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-fasum-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
    fputcsv($out, ['Kode Laporan','Nama Pelapor','Email','No HP','Kategori','Judul','Deskripsi','Lokasi','Status','Prioritas','Catatan Admin','Tanggal Lapor']);
    foreach ($data as $row) {
        fputcsv($out, array_values($row));
    }
    fclose($out); exit;
}

$stats = $pdo->query("SELECT COUNT(*) as total, SUM(status='menunggu') as menunggu, SUM(status='diproses') as diproses, SUM(status='selesai') as selesai FROM laporan")->fetch();
?>

<div class="admin-sidebar">
    <div class="logo"><a href="dashboard.php">📢 LaporFasum</a></div>
    <nav class="sidebar-menu">
        <a href="dashboard.php"><span>📊</span> Dashboard</a>
        <a href="laporan.php"><span>📋</span> Kelola Laporan</a>
        <a href="grafik.php"><span>📈</span> Grafik</a>
        <a href="kategori.php"><span>🏷️</span> Kategori</a>
        <a href="export.php" class="active"><span>📥</span> Export Data</a>
        <a href="logout.php"><span>🚪</span> Logout</a>
    </nav>
</div>

<div class="admin-main">
    <div class="admin-topbar">
        <h1>📥 Export Data</h1>
        <div class="admin-user">👤 <?= htmlspecialchars($_SESSION['admin_nama']) ?></div>
    </div>

    <div class="admin-content">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; max-width:800px;">
            <div class="detail-card">
                <h3 style="font-family:'Syne',sans-serif; font-size:1.2rem; margin-bottom:20px;">📊 Export ke CSV</h3>
                <p style="color:#64748b; font-size:.9rem; margin-bottom:20px;">Download data laporan dalam format CSV untuk diolah di Excel atau Google Sheets.</p>

                <div style="display:flex; flex-direction:column; gap:12px;">
                    <a href="export.php?download=1" class="btn-submit" style="text-decoration:none; justify-content:center; text-align:center;">
                        📥 Export Semua Laporan (<?= $stats['total'] ?>)
                    </a>
                    <a href="export.php?download=1&status=menunggu" style="background:#fffbeb; color:#92400e; padding:12px 20px; border-radius:10px; font-weight:600; text-align:center; text-decoration:none;">
                        ⏳ Export Laporan Menunggu (<?= $stats['menunggu'] ?>)
                    </a>
                    <a href="export.php?download=1&status=diproses" style="background:#eff6ff; color:#1e40af; padding:12px 20px; border-radius:10px; font-weight:600; text-align:center; text-decoration:none;">
                        🔧 Export Laporan Diproses (<?= $stats['diproses'] ?>)
                    </a>
                    <a href="export.php?download=1&status=selesai" style="background:#f0fdf4; color:#166534; padding:12px 20px; border-radius:10px; font-weight:600; text-align:center; text-decoration:none;">
                        ✅ Export Laporan Selesai (<?= $stats['selesai'] ?>)
                    </a>
                </div>
            </div>

            <div class="detail-card">
                <h3 style="font-family:'Syne',sans-serif; font-size:1.2rem; margin-bottom:20px;">📋 Ringkasan Data</h3>
                <div class="info-row"><span class="info-label">Total Laporan</span><span class="info-value" style="font-weight:700; font-size:1.1rem;"><?= $stats['total'] ?></span></div>
                <div class="info-row"><span class="info-label">Menunggu</span><span class="info-value"><span class="badge status-waiting"><?= $stats['menunggu'] ?></span></span></div>
                <div class="info-row"><span class="info-label">Diproses</span><span class="info-value"><span class="badge status-process"><?= $stats['diproses'] ?></span></span></div>
                <div class="info-row"><span class="info-label">Selesai</span><span class="info-value"><span class="badge status-done"><?= $stats['selesai'] ?></span></span></div>
                <div style="margin-top:16px; background:#f8fafc; border-radius:10px; padding:14px; font-size:.85rem; color:#64748b;">
                    <strong>Format CSV</strong> kompatibel dengan:<br>
                    ✓ Microsoft Excel<br>
                    ✓ Google Sheets<br>
                    ✓ LibreOffice Calc
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
