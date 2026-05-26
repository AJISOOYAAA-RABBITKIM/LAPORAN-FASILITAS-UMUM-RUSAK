<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan – Admin LaporFasum</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php
require_once '../includes/config.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: laporan.php'); exit; }

$stmt = $pdo->prepare("SELECT l.*, k.nama as kn, k.ikon FROM laporan l JOIN kategori k ON l.kategori_id=k.id WHERE l.id=?");
$stmt->execute([$id]);
$lap = $stmt->fetch();
if (!$lap) { header('Location: laporan.php'); exit; }

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status   = $_POST['status'] ?? $lap['status'];
    $prioritas= $_POST['prioritas'] ?? $lap['prioritas'];
    $catatan  = trim($_POST['catatan_admin'] ?? '');

    $pdo->prepare("UPDATE laporan SET status=?, prioritas=?, catatan_admin=? WHERE id=?")
        ->execute([$status, $prioritas, $catatan, $id]);
    $success = 'Data laporan berhasil diperbarui!';

    // Refresh
    $stmt->execute([$id]);
    $lap = $stmt->fetch();
}

$badge = statusBadge($lap['status']);
$prio  = prioritasBadge($lap['prioritas']);
?>

<div class="admin-sidebar">
    <div class="logo"><a href="dashboard.php">📢 LaporFasum</a></div>
    <nav class="sidebar-menu">
        <a href="dashboard.php"><span>📊</span> Dashboard</a>
        <a href="laporan.php" class="active"><span>📋</span> Kelola Laporan</a>
        <a href="kategori.php"><span>🏷️</span> Kategori</a>
        <a href="export.php"><span>📥</span> Export Data</a>
        <a href="logout.php"><span>🚪</span> Logout</a>
    </nav>
</div>

<div class="admin-main">
    <div class="admin-topbar">
        <h1>📋 Detail Laporan</h1>
        <div style="display:flex; gap:12px; align-items:center;">
            <a href="laporan.php" style="color:#64748b; font-size:.88rem; text-decoration:none;">← Kembali</a>
            <div class="admin-user">👤 <?= htmlspecialchars($_SESSION['admin_nama']) ?></div>
        </div>
    </div>

    <div class="admin-content">
        <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom:20px;">✅ <?= $success ?></div>
        <?php endif; ?>

        <div class="detail-grid">
            <!-- Info Laporan -->
            <div class="detail-card">
                <div class="detail-header">
                    <div>
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                            <span style="font-size:2rem;"><?= $lap['ikon'] ?></span>
                            <h2 class="detail-title"><?= htmlspecialchars($lap['judul']) ?></h2>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <span class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                            <span class="badge <?= $prio['class'] ?>"><?= $prio['label'] ?></span>
                        </div>
                    </div>
                    <code style="font-size:.85rem; color:#1a56db; background:#eff6ff; padding:6px 12px; border-radius:8px;"><?= $lap['kode_laporan'] ?></code>
                </div>

                <div class="info-row"><span class="info-label">Kategori</span><span class="info-value"><?= htmlspecialchars($lap['kn']) ?></span></div>
                <div class="info-row"><span class="info-label">Pelapor</span><span class="info-value"><?= htmlspecialchars($lap['nama_pelapor']) ?></span></div>
                <?php if ($lap['email_pelapor']): ?>
                <div class="info-row"><span class="info-label">Email</span><span class="info-value"><a href="mailto:<?= $lap['email_pelapor'] ?>"><?= htmlspecialchars($lap['email_pelapor']) ?></a></span></div>
                <?php endif; ?>
                <?php if ($lap['no_hp']): ?>
                <div class="info-row"><span class="info-label">No. HP</span><span class="info-value"><?= htmlspecialchars($lap['no_hp']) ?></span></div>
                <?php endif; ?>
                <div class="info-row"><span class="info-label">Lokasi</span><span class="info-value">📍 <?= htmlspecialchars($lap['lokasi']) ?></span></div>
                <div class="info-row"><span class="info-label">Tanggal Lapor</span><span class="info-value"><?= date('d M Y, H:i', strtotime($lap['created_at'])) ?> WIB</span></div>
                <div class="info-row"><span class="info-label">Terakhir Update</span><span class="info-value"><?= date('d M Y, H:i', strtotime($lap['updated_at'])) ?> WIB</span></div>

                <div style="margin-top:20px;">
                    <p style="font-size:.82rem; font-weight:700; color:#64748b; margin-bottom:8px;">DESKRIPSI KERUSAKAN</p>
                    <div style="background:#f8fafc; border-radius:10px; padding:16px; font-size:.9rem; line-height:1.7; color:#374151;">
                        <?= nl2br(htmlspecialchars($lap['deskripsi'])) ?>
                    </div>
                </div>

                <?php if ($lap['foto']): ?>
                <div style="margin-top:20px;">
                    <p style="font-size:.82rem; font-weight:700; color:#64748b; margin-bottom:8px;">📷 FOTO BUKTI</p>
                    <img src="../uploads/<?= htmlspecialchars($lap['foto']) ?>" style="max-width:100%; max-height:400px; border-radius:12px; border:2px solid #e2e8f0;" alt="Foto laporan">
                </div>
                <?php endif; ?>

                <?php if ($lap['catatan_admin']): ?>
                <div style="margin-top:20px; background:#eff6ff; border-radius:10px; padding:16px; border-left:4px solid #1a56db;">
                    <p style="font-size:.82rem; font-weight:700; color:#1a56db; margin-bottom:6px;">📋 Catatan Admin Sebelumnya:</p>
                    <p style="font-size:.9rem;"><?= nl2br(htmlspecialchars($lap['catatan_admin'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Update Status -->
            <div>
                <div class="detail-card status-form">
                    <h3>⚙️ Update Status</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Status Laporan</label>
                            <select name="status" required>
                                <option value="menunggu" <?= $lap['status']==='menunggu'?'selected':'' ?>>⏳ Menunggu</option>
                                <option value="diproses" <?= $lap['status']==='diproses'?'selected':'' ?>>🔧 Diproses</option>
                                <option value="selesai"  <?= $lap['status']==='selesai'?'selected':'' ?>>✅ Selesai</option>
                                <option value="ditolak"  <?= $lap['status']==='ditolak'?'selected':'' ?>>❌ Ditolak</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Prioritas</label>
                            <select name="prioritas">
                                <option value="rendah"  <?= $lap['prioritas']==='rendah'?'selected':'' ?>>🟢 Rendah</option>
                                <option value="sedang"  <?= $lap['prioritas']==='sedang'?'selected':'' ?>>🟡 Sedang</option>
                                <option value="tinggi"  <?= $lap['prioritas']==='tinggi'?'selected':'' ?>>🟠 Tinggi</option>
                                <option value="darurat" <?= $lap['prioritas']==='darurat'?'selected':'' ?>>🔴 Darurat</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Catatan Admin</label>
                            <textarea name="catatan_admin" rows="5" placeholder="Tambahkan catatan atau tindakan yang sudah diambil..."><?= htmlspecialchars($lap['catatan_admin'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn-submit" style="width:100%; justify-content:center;">💾 Simpan Perubahan</button>
                    </form>
                </div>

                <!-- Quick Actions -->
                <div class="detail-card" style="margin-top:16px; padding:20px;">
                    <h3 style="font-size:.95rem; font-weight:700; margin-bottom:12px;">⚡ Aksi Cepat</h3>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <?php if ($lap['email_pelapor']): ?>
                        <a href="mailto:<?= $lap['email_pelapor'] ?>?subject=Update Laporan <?= $lap['kode_laporan'] ?>" 
                           style="background:#eff6ff; color:#1a56db; padding:10px 14px; border-radius:8px; text-decoration:none; font-size:.88rem; font-weight:600;">
                            📧 Kirim Email ke Pelapor
                        </a>
                        <?php endif; ?>
                        <a href="laporan.php?delete=<?= $lap['id'] ?>" onclick="return confirmDelete('Yakin hapus laporan ini secara permanen?')"
                           style="background:#fef2f2; color:#ef4444; padding:10px 14px; border-radius:8px; text-decoration:none; font-size:.88rem; font-weight:600;">
                            🗑️ Hapus Laporan
                        </a>
                        <a href="../cek-status.php" target="_blank"
                           style="background:#f0fdf4; color:#16a34a; padding:10px 14px; border-radius:8px; text-decoration:none; font-size:.88rem; font-weight:600;">
                            👁️ Lihat Tampilan Publik
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
