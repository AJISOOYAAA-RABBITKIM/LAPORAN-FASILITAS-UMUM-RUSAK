<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori – Admin LaporFasum</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php
require_once '../includes/config.php';
requireAdmin();

$msg = '';

// Tambah kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $nama = sanitize($_POST['nama'] ?? '');
    $ikon = sanitize($_POST['ikon'] ?? '📌');
    $desc = sanitize($_POST['deskripsi'] ?? '');
    if ($nama) {
        $pdo->prepare("INSERT INTO kategori (nama, ikon, deskripsi) VALUES (?,?,?)")->execute([$nama, $ikon, $desc]);
        $msg = 'Kategori berhasil ditambahkan!';
    }
}

// Hapus kategori
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM kategori WHERE id = ?")->execute([(int)$_GET['delete']]);
    $msg = 'Kategori berhasil dihapus.';
}

$list = $pdo->query("SELECT k.*, COUNT(l.id) as total_laporan FROM kategori k LEFT JOIN laporan l ON l.kategori_id=k.id GROUP BY k.id ORDER BY k.nama")->fetchAll();
?>

<div class="admin-sidebar">
    <div class="logo"><a href="dashboard.php">📢 LaporFasum</a></div>
    <nav class="sidebar-menu">
        <a href="dashboard.php"><span>📊</span> Dashboard</a>
        <a href="laporan.php"><span>📋</span> Kelola Laporan</a>
        <a href="grafik.php"><span>📈</span> Grafik</a>
        <a href="kategori.php" class="active"><span>🏷️</span> Kategori</a>
        <a href="export.php"><span>📥</span> Export Data</a>
        <a href="logout.php"><span>🚪</span> Logout</a>
    </nav>
</div>

<div class="admin-main">
    <div class="admin-topbar">
        <h1>🏷️ Kelola Kategori</h1>
        <div class="admin-user">👤 <?= htmlspecialchars($_SESSION['admin_nama']) ?></div>
    </div>

    <div class="admin-content">
        <?php if ($msg): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">✅ <?= $msg ?></div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:24px;">
            <!-- List Kategori -->
            <div class="admin-table-card">
                <div class="table-header"><h2>Daftar Kategori (<?= count($list) ?>)</h2></div>
                <table class="admin-table">
                    <thead><tr><th>Ikon</th><th>Nama</th><th>Deskripsi</th><th>Laporan</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($list as $k): ?>
                        <tr>
                            <td style="font-size:1.5rem;"><?= $k['ikon'] ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($k['nama']) ?></td>
                            <td style="font-size:.82rem; color:#64748b; max-width:200px;"><?= htmlspecialchars(substr($k['deskripsi'],0,60)) ?>...</td>
                            <td><span style="font-weight:700; color:#1a56db;"><?= $k['total_laporan'] ?></span></td>
                            <td>
                                <?php if ($k['total_laporan'] == 0): ?>
                                <a href="kategori.php?delete=<?= $k['id'] ?>" onclick="return confirm('Hapus kategori ini?')" style="color:#ef4444; font-size:.82rem; font-weight:600; text-decoration:none;">🗑️ Hapus</a>
                                <?php else: ?>
                                <span style="color:#94a3b8; font-size:.78rem;">Ada laporan</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tambah Kategori -->
            <div class="detail-card">
                <h3 style="font-size:1rem; font-weight:700; margin-bottom:16px;">➕ Tambah Kategori</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Ikon (Emoji)</label>
                        <input type="text" name="ikon" placeholder="📌" maxlength="4" style="font-size:1.4rem;" value="📌">
                    </div>
                    <div class="form-group">
                        <label>Nama Kategori <span class="required">*</span></label>
                        <input type="text" name="nama" placeholder="Contoh: Lampu Taman" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" rows="3" placeholder="Deskripsi singkat kategori ini..."></textarea>
                    </div>
                    <button type="submit" name="tambah" class="btn-submit" style="width:100%; justify-content:center;">➕ Tambah</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
