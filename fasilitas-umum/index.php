<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaporFasum – Sistem Pelaporan Fasilitas Umum</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php
require_once 'includes/config.php';

// Ambil kategori
$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();

// Statistik
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(status='menunggu') as menunggu,
        SUM(status='diproses') as diproses,
        SUM(status='selesai') as selesai
    FROM laporan
")->fetch();

$pesan = '';
$pesan_type = '';

// Proses Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_laporan'])) {
    $nama     = sanitize($_POST['nama_pelapor'] ?? '');
    $email    = sanitize($_POST['email_pelapor'] ?? '');
    $hp       = sanitize($_POST['no_hp'] ?? '');
    $kat_id   = (int)($_POST['kategori_id'] ?? 0);
    $judul    = sanitize($_POST['judul'] ?? '');
    $desc     = sanitize($_POST['deskripsi'] ?? '');
    $lokasi   = sanitize($_POST['lokasi'] ?? '');
    $prioritas= sanitize($_POST['prioritas'] ?? 'sedang');
    $lat      = !empty($_POST['latitude'])  ? (float)$_POST['latitude']  : null;
    $lng      = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

    if ($nama && $kat_id && $judul && $desc && $lokasi) {
        $foto_path = null;

        // Upload foto
        if (!empty($_FILES['foto']['name'])) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed) && $_FILES['foto']['size'] < 5 * 1024 * 1024) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $filename = uniqid('foto_') . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $filename)) {
                    $foto_path = $filename;
                }
            }
        }

        $kode = generateKodeLaporan($pdo);
        $stmt = $pdo->prepare("
            INSERT INTO laporan (kode_laporan, nama_pelapor, email_pelapor, no_hp, kategori_id, judul, deskripsi, lokasi, latitude, longitude, foto, prioritas)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$kode, $nama, $email, $hp, $kat_id, $judul, $desc, $lokasi, $lat, $lng, $foto_path, $prioritas]);

        $pesan = "✅ Laporan berhasil dikirim! Kode laporan Anda: <strong>{$kode}</strong>. Simpan kode ini untuk melacak status laporan.";
        $pesan_type = 'success';
    } else {
        $pesan = "❌ Harap lengkapi semua field yang wajib diisi.";
        $pesan_type = 'error';
    }
}
?>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">
            <span class="logo-icon">📢</span>
            <span>LaporFasum</span>
        </a>
        <div class="nav-links">
            <a href="cek-status.php">Cek Status</a>
            <a href="daftar-laporan.php">Semua Laporan</a>
            <a href="peta.php">🗺️ Peta</a>
            <a href="admin/login.php" class="btn-admin">Admin</a>
        </div>
    </div>
</nav>

<!-- HERO -->
<header class="hero">
    <div class="hero-bg"></div>
    <div class="hero-content">
        <div class="hero-badge">🏙️ Platform Warga Digital</div>
        <h1 class="hero-title">Laporkan Fasilitas<br><span class="accent">Umum Rusak</span></h1>
        <p class="hero-desc">Bersama kita jaga fasilitas publik. Laporkan kerusakan bangku taman, trotoar, drainase, halte, dan fasilitas umum lainnya agar segera ditangani.</p>
        <div class="hero-cta">
            <a href="#form-laporan" class="btn-primary">📝 Buat Laporan</a>
            <a href="cek-status.php" class="btn-outline">🔍 Cek Status</a>
        </div>
    </div>
    <div class="hero-visual">
        <div class="floating-card fc1">🪑 Bangku Rusak</div>
        <div class="floating-card fc2">🚶 Trotoar Berlubang</div>
        <div class="floating-card fc3">🌊 Drainase Tersumbat</div>
        <div class="floating-card fc4">🚌 Halte Rusak</div>
    </div>
</header>

<!-- STATISTIK -->
<section class="stats-bar">
    <div class="stats-container">
        <div class="stat-item">
            <span class="stat-num"><?= number_format($stats['total']) ?></span>
            <span class="stat-label">Total Laporan</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-num waiting"><?= number_format($stats['menunggu']) ?></span>
            <span class="stat-label">Menunggu</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-num processing"><?= number_format($stats['diproses']) ?></span>
            <span class="stat-label">Diproses</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
            <span class="stat-num done"><?= number_format($stats['selesai']) ?></span>
            <span class="stat-label">Selesai</span>
        </div>
    </div>
</section>

<!-- KATEGORI -->
<section class="kategori-section">
    <div class="section-container">
        <div class="section-header">
            <h2>Jenis Fasilitas</h2>
            <p>Yang bisa Anda laporkan</p>
        </div>
        <div class="kategori-grid">
            <?php foreach ($kategori_list as $kat): ?>
            <div class="kat-card" onclick="selectKategori(<?= $kat['id'] ?>, '<?= htmlspecialchars($kat['nama']) ?>')">
                <span class="kat-icon"><?= $kat['ikon'] ?></span>
                <span class="kat-nama"><?= htmlspecialchars($kat['nama']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FORM LAPORAN -->
<section class="form-section" id="form-laporan">
    <div class="section-container">
        <div class="section-header">
            <h2>📝 Buat Laporan</h2>
            <p>Isi formulir berikut dengan informasi yang lengkap dan akurat</p>
        </div>

        <?php if ($pesan): ?>
        <div class="alert alert-<?= $pesan_type ?>"><?= $pesan ?></div>
        <?php endif; ?>

        <form class="report-form" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <!-- Kolom Kiri: Data Pelapor -->
                <div class="form-group-section">
                    <h3 class="form-section-title">👤 Data Pelapor</h3>
                    <div class="form-group">
                        <label>Nama Lengkap <span class="required">*</span></label>
                        <input type="text" name="nama_pelapor" placeholder="Masukkan nama Anda" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email_pelapor" placeholder="email@contoh.com">
                        </div>
                        <div class="form-group">
                            <label>No. HP</label>
                            <input type="tel" name="no_hp" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Detail Laporan -->
                <div class="form-group-section">
                    <h3 class="form-section-title">🔧 Detail Kerusakan</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Kategori Fasilitas <span class="required">*</span></label>
                            <select name="kategori_id" id="select-kategori" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($kategori_list as $kat): ?>
                                <option value="<?= $kat['id'] ?>"><?= $kat['ikon'] ?> <?= htmlspecialchars($kat['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Prioritas</label>
                            <select name="prioritas">
                                <option value="rendah">🟢 Rendah</option>
                                <option value="sedang" selected>🟡 Sedang</option>
                                <option value="tinggi">🟠 Tinggi</option>
                                <option value="darurat">🔴 Darurat</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Judul Laporan <span class="required">*</span></label>
                        <input type="text" name="judul" placeholder="Contoh: Bangku Taman Alun-Alun Patah" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Deskripsi Kerusakan <span class="required">*</span></label>
                <textarea name="deskripsi" rows="4" placeholder="Deskripsikan kondisi kerusakan secara detail..." required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Lokasi Kerusakan <span class="required">*</span></label>
                    <div style="display:flex;gap:8px;align-items:flex-start;">
                        <input type="text" name="lokasi" id="input-lokasi" placeholder="Contoh: Jl. Merdeka No. 10, depan kantor pos" required style="flex:1;">
                        <button type="button" onclick="deteksiGPS()" id="btn-gps" title="Deteksi lokasi otomatis" style="background:#1a56db;color:#fff;border:none;border-radius:10px;padding:11px 14px;cursor:pointer;font-size:1rem;white-space:nowrap;flex-shrink:0;">📍 GPS</button>
                    </div>
                    <small id="gps-status" style="font-size:.78rem;margin-top:4px;display:block;color:#64748b;"></small>
                    <input type="hidden" name="latitude"  id="input-lat">
                    <input type="hidden" name="longitude" id="input-lng">
                </div>
                <div class="form-group">
                    <label>Foto Bukti (Maks. 5MB)</label>
                    <div class="file-upload-area" id="file-area">
                        <input type="file" name="foto" id="foto-input" accept="image/*" onchange="previewFoto(this)">
                        <div class="file-upload-text" id="file-text">
                            <span class="file-icon">📷</span>
                            <span>Klik atau drag foto ke sini</span>
                        </div>
                        <img id="foto-preview" src="" style="display:none;" alt="Preview">
                    </div>
                </div>
            </div>

            <div class="form-submit">
                <button type="submit" name="kirim_laporan" class="btn-submit">
                    <span>📤 Kirim Laporan</span>
                </button>
            </div>
        </form>
    </div>
</section>

<!-- LAPORAN TERBARU -->
<section class="recent-section">
    <div class="section-container">
        <div class="section-header">
            <h2>🕐 Laporan Terbaru</h2>
            <a href="daftar-laporan.php" class="see-all">Lihat Semua →</a>
        </div>
        <?php
        $recent = $pdo->query("
            SELECT l.*, k.nama as kategori_nama, k.ikon
            FROM laporan l
            JOIN kategori k ON l.kategori_id = k.id
            ORDER BY l.created_at DESC LIMIT 6
        ")->fetchAll();
        ?>
        <div class="laporan-grid">
            <?php foreach ($recent as $lap): 
                $badge = statusBadge($lap['status']);
                $prio  = prioritasBadge($lap['prioritas']);
            ?>
            <div class="laporan-card">
                <div class="laporan-header">
                    <span class="laporan-ikon"><?= $lap['ikon'] ?></span>
                    <div class="laporan-badges">
                        <span class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                        <span class="badge <?= $prio['class'] ?>"><?= $prio['label'] ?></span>
                    </div>
                </div>
                <h4 class="laporan-judul"><?= htmlspecialchars($lap['judul']) ?></h4>
                <p class="laporan-lokasi">📍 <?= htmlspecialchars($lap['lokasi']) ?></p>
                <p class="laporan-desc"><?= htmlspecialchars(substr($lap['deskripsi'], 0, 100)) ?>...</p>
                <div class="laporan-footer">
                    <span class="laporan-kode"><?= $lap['kode_laporan'] ?></span>
                    <span class="laporan-date"><?= date('d M Y', strtotime($lap['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-brand">
            <span class="logo-icon">📢</span>
            <strong>LaporFasum</strong>
            <p>Platform pelaporan fasilitas umum untuk warga kota yang lebih baik.</p>
        </div>
        <div class="footer-links">
            <a href="index.php">Beranda</a>
            <a href="cek-status.php">Cek Status</a>
            <a href="daftar-laporan.php">Semua Laporan</a>
            <a href="peta.php">🗺️ Peta</a>
            <a href="admin/login.php">Admin</a>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© <?= date('Y') ?> LaporFasum. Dibuat untuk melayani warga.</p>
    </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
