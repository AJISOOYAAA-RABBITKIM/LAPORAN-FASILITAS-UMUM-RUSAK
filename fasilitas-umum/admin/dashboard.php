<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin – LaporFasum</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php
require_once '../includes/config.php';
requireAdmin();

// Stats
$stats = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(status='menunggu') as menunggu,
        SUM(status='diproses') as diproses,
        SUM(status='selesai') as selesai,
        SUM(status='ditolak') as ditolak,
        SUM(prioritas='darurat') as darurat
    FROM laporan
")->fetch();

// Laporan terbaru
$recent = $pdo->query("
    SELECT l.*, k.nama as kategori_nama, k.ikon
    FROM laporan l JOIN kategori k ON l.kategori_id = k.id
    ORDER BY l.created_at DESC LIMIT 8
")->fetchAll();

// Statistik per kategori
$per_kat = $pdo->query("
    SELECT k.nama, k.ikon, COUNT(l.id) as total
    FROM kategori k LEFT JOIN laporan l ON l.kategori_id = k.id
    GROUP BY k.id ORDER BY total DESC
")->fetchAll();
?>

<!-- SIDEBAR -->
<div class="admin-sidebar">
    <div class="logo"><a href="dashboard.php">📢 LaporFasum</a><br><small style="color:rgba(255,255,255,.4); font-size:.75rem;">Admin Panel</small></div>
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="active"><span>📊</span> Dashboard</a>
        <a href="laporan.php"><span>📋</span> Kelola Laporan</a>
        <a href="grafik.php"><span>📈</span> Grafik</a>
        <a href="kategori.php"><span>🏷️</span> Kategori</a>
        <a href="export.php"><span>📥</span> Export Data</a>
        <a href="logout.php" style="margin-top:auto; color:#ef4444;"><span>🚪</span> Logout</a>
    </nav>
</div>

<!-- MAIN -->
<div class="admin-main">
    <div class="admin-topbar">
        <h1>📊 Dashboard</h1>
        <div class="admin-user">
            <span>👤 <?= htmlspecialchars($_SESSION['admin_nama']) ?></span>
            <a href="logout.php" style="color:#ef4444; font-size:.85rem; text-decoration:none;">Logout</a>
        </div>
    </div>

    <div class="admin-content">
        <!-- Stats -->
        <div class="admin-stats">
            <div class="admin-stat-card">
                <div class="num"><?= $stats['total'] ?></div>
                <div class="lbl">📋 Total Laporan</div>
            </div>
            <div class="admin-stat-card warning">
                <div class="num"><?= $stats['menunggu'] ?></div>
                <div class="lbl">⏳ Menunggu</div>
            </div>
            <div class="admin-stat-card">
                <div class="num"><?= $stats['diproses'] ?></div>
                <div class="lbl">🔧 Diproses</div>
            </div>
            <div class="admin-stat-card success">
                <div class="num"><?= $stats['selesai'] ?></div>
                <div class="lbl">✅ Selesai</div>
            </div>
        </div>

        <?php if ($stats['darurat'] > 0): ?>
        <div class="alert alert-error" style="margin-bottom:24px;">
            🚨 Ada <strong><?= $stats['darurat'] ?></strong> laporan dengan prioritas DARURAT yang perlu segera ditangani!
            <a href="laporan.php?prioritas=darurat" style="color:#991b1b; font-weight:700; margin-left:8px;">Lihat →</a>
        </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:24px;">
            <!-- Laporan Terbaru -->
            <div class="admin-table-card">
                <div class="table-header">
                    <h2>🕐 Laporan Terbaru</h2>
                    <a href="laporan.php" style="font-size:.85rem; color:#1a56db; text-decoration:none;">Lihat Semua →</a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $lap):
                            $badge = statusBadge($lap['status']);
                        ?>
                        <tr>
                            <td><code style="font-size:.78rem; color:#1a56db;"><?= $lap['kode_laporan'] ?></code></td>
                            <td style="max-width:200px;">
                                <span title="<?= htmlspecialchars($lap['judul']) ?>">
                                    <?= htmlspecialchars(substr($lap['judul'], 0, 35)) ?><?= strlen($lap['judul']) > 35 ? '...' : '' ?>
                                </span>
                            </td>
                            <td><?= $lap['ikon'] ?> <?= htmlspecialchars($lap['kategori_nama']) ?></td>
                            <td><span class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span></td>
                            <td><?= date('d/m/Y', strtotime($lap['created_at'])) ?></td>
                            <td><a href="detail.php?id=<?= $lap['id'] ?>" style="color:#1a56db; font-size:.82rem; font-weight:600;">Detail</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Per Kategori -->
            <div class="admin-table-card">
                <div class="table-header"><h2>🏷️ Per Kategori</h2></div>
                <div style="padding:16px;">
                    <?php foreach ($per_kat as $k): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #e2e8f0;">
                        <span style="font-size:.9rem;"><?= $k['ikon'] ?> <?= htmlspecialchars($k['nama']) ?></span>
                        <span style="font-weight:700; color:#1a56db; font-size:.9rem;"><?= $k['total'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
