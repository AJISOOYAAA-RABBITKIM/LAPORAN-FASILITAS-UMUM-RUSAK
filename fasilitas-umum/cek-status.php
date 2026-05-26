<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Laporan – LaporFasum</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php
require_once 'includes/config.php';

$laporan = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kode'])) {
    $kode = strtoupper(trim($_POST['kode']));
    $stmt = $pdo->prepare("
        SELECT l.*, k.nama as kategori_nama, k.ikon
        FROM laporan l JOIN kategori k ON l.kategori_id = k.id
        WHERE l.kode_laporan = ?
    ");
    $stmt->execute([$kode]);
    $laporan = $stmt->fetch();
    if (!$laporan) $error = "Kode laporan tidak ditemukan. Periksa kembali kode Anda.";
}
?>

<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo"><span class="logo-icon">📢</span><span>LaporFasum</span></a>
        <div class="nav-links">
            <a href="index.php">Beranda</a>
            <a href="daftar-laporan.php">Semua Laporan</a>
            <a href="peta.php">🗺️ Peta</a>
            <a href="admin/login.php" class="btn-admin">Admin</a>
        </div>
    </div>
</nav>

<section class="cek-page">
    <div class="cek-card">
        <h2>🔍 Cek Status Laporan</h2>
        <p>Masukkan kode laporan yang Anda terima saat mengirim laporan.</p>

        <form method="POST">
            <div class="form-group">
                <label>Kode Laporan</label>
                <input type="text" name="kode" placeholder="Contoh: RPT-20240001"
                       value="<?= htmlspecialchars($_POST['kode'] ?? '') ?>"
                       style="text-transform:uppercase; font-size:1.1rem; font-weight:600; letter-spacing:.05em;" required>
            </div>
            <button type="submit" class="btn-submit" style="width:100%; justify-content:center; margin-top:8px;">
                🔍 Cek Status
            </button>
        </form>

        <?php if ($error): ?>
        <div class="alert alert-error" style="margin-top:20px;"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($laporan): 
            $badge = statusBadge($laporan['status']);
            $prio  = prioritasBadge($laporan['prioritas']);
        ?>
        <div class="cek-result">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <span style="font-size:2.5rem;"><?= $laporan['ikon'] ?></span>
                <div>
                    <h3 style="font-size:1.1rem; font-weight:700;"><?= htmlspecialchars($laporan['judul']) ?></h3>
                    <div style="display:flex; gap:6px; margin-top:6px;">
                        <span class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                        <span class="badge <?= $prio['class'] ?>"><?= $prio['label'] ?></span>
                    </div>
                </div>
            </div>

            <div class="info-row"><span class="info-label">Kode:</span><span class="info-value" style="font-family:monospace; font-weight:700; color:#1a56db;"><?= $laporan['kode_laporan'] ?></span></div>
            <div class="info-row"><span class="info-label">Kategori:</span><span class="info-value"><?= htmlspecialchars($laporan['kategori_nama']) ?></span></div>
            <div class="info-row"><span class="info-label">Pelapor:</span><span class="info-value"><?= htmlspecialchars($laporan['nama_pelapor']) ?></span></div>
            <div class="info-row"><span class="info-label">Lokasi:</span><span class="info-value"><?= htmlspecialchars($laporan['lokasi']) ?></span></div>
            <div class="info-row"><span class="info-label">Tanggal:</span><span class="info-value"><?= date('d M Y, H:i', strtotime($laporan['created_at'])) ?> WIB</span></div>
            <div class="info-row"><span class="info-label">Deskripsi:</span><span class="info-value"><?= nl2br(htmlspecialchars($laporan['deskripsi'])) ?></span></div>

            <?php if ($laporan['catatan_admin']): ?>
            <div style="background:#eff6ff; border-radius:10px; padding:16px; margin-top:16px; border-left:4px solid #1a56db;">
                <p style="font-size:.82rem; font-weight:700; color:#1a56db; margin-bottom:6px;">📋 Catatan Admin:</p>
                <p style="font-size:.9rem;"><?= nl2br(htmlspecialchars($laporan['catatan_admin'])) ?></p>
            </div>
            <?php endif; ?>

            <?php if ($laporan['foto']): ?>
            <div style="margin-top:16px;">
                <p style="font-size:.82rem; font-weight:700; color:#64748b; margin-bottom:8px;">📷 Foto Bukti:</p>
                <img src="uploads/<?= htmlspecialchars($laporan['foto']) ?>" style="max-width:100%; border-radius:10px;" alt="Foto laporan">
            </div>
            <?php endif; ?>

            <!-- Timeline Status -->
            <div style="margin-top:24px;">
                <p style="font-size:.82rem; font-weight:700; color:#64748b; margin-bottom:12px;">📊 Timeline Status:</p>
                <div style="display:flex; align-items:center; gap:0;">
                    <?php
                    $steps = ['menunggu'=>'Menunggu', 'diproses'=>'Diproses', 'selesai'=>'Selesai'];
                    $current = $laporan['status'];
                    $order = ['menunggu'=>0,'diproses'=>1,'selesai'=>2,'ditolak'=>3];
                    $cur_order = $order[$current] ?? 0;
                    foreach($steps as $key => $label):
                        $step_order = $order[$key];
                        $done = $cur_order >= $step_order && $current !== 'ditolak';
                        $is_active = $key === $current;
                    ?>
                    <div style="display:flex; flex-direction:column; align-items:center; flex:1;">
                        <div style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700;
                            background:<?= $done ? '#10b981' : '#e2e8f0' ?>; color:<?= $done ? '#fff' : '#94a3b8' ?>;">
                            <?= $done ? '✓' : ($step_order+1) ?>
                        </div>
                        <span style="font-size:.75rem; font-weight:600; margin-top:6px; color:<?= $done ? '#10b981' : '#94a3b8' ?>;">
                            <?= $label ?>
                        </span>
                    </div>
                    <?php if($key !== 'selesai'): ?>
                    <div style="flex:1; height:2px; background:<?= $cur_order > $step_order && $current !== 'ditolak' ? '#10b981' : '#e2e8f0' ?>; margin-bottom:18px;"></div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($current === 'ditolak'): ?>
                <div style="margin-top:12px; background:#fee2e2; border-radius:8px; padding:10px 14px; font-size:.85rem; color:#991b1b;">
                    ❌ Laporan ini telah ditolak.
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<footer class="footer">
    <div class="footer-bottom"><p>© <?= date('Y') ?> LaporFasum. Dibuat untuk melayani warga.</p></div>
</footer>

</body>
</html>
