<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Laporan – LaporFasum</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php
require_once 'includes/config.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

$status_filter = $_GET['status'] ?? '';
$kat_filter = (int)($_GET['kategori'] ?? 0);
$search = trim($_GET['q'] ?? '');

$where = ['1=1'];
$params = [];
if ($status_filter) { $where[] = 'l.status = ?'; $params[] = $status_filter; }
if ($kat_filter)     { $where[] = 'l.kategori_id = ?'; $params[] = $kat_filter; }
if ($search)         { $where[] = '(l.judul LIKE ? OR l.lokasi LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$where_sql = implode(' AND ', $where);

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM laporan l WHERE $where_sql");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

$stmt = $pdo->prepare("
    SELECT l.*, k.nama as kategori_nama, k.ikon
    FROM laporan l JOIN kategori k ON l.kategori_id = k.id
    WHERE $where_sql ORDER BY l.created_at DESC LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$laporan_list = $stmt->fetchAll();

$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();
?>

<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo"><span class="logo-icon">📢</span><span>LaporFasum</span></a>
        <div class="nav-links">
            <a href="index.php">Beranda</a>
            <a href="cek-status.php">Cek Status</a>
            <a href="admin/login.php" class="btn-admin">Admin</a>
        </div>
    </div>
</nav>

<div style="max-width:1200px; margin:40px auto; padding:0 24px;">
    <div class="section-header" style="text-align:left; margin-bottom:24px;">
        <h2>📋 Semua Laporan</h2>
        <p>Total <?= number_format($total) ?> laporan ditemukan</p>
    </div>

    <!-- Filter -->
    <form method="GET" class="filter-bar">
        <div class="form-group">
            <label>Cari</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Judul atau lokasi...">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="">Semua Status</option>
                <option value="menunggu" <?= $status_filter==='menunggu'?'selected':'' ?>>Menunggu</option>
                <option value="diproses" <?= $status_filter==='diproses'?'selected':'' ?>>Diproses</option>
                <option value="selesai"  <?= $status_filter==='selesai'?'selected':'' ?>>Selesai</option>
                <option value="ditolak"  <?= $status_filter==='ditolak'?'selected':'' ?>>Ditolak</option>
            </select>
        </div>
        <div class="form-group">
            <label>Kategori</label>
            <select name="kategori">
                <option value="">Semua Kategori</option>
                <?php foreach ($kategori_list as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $kat_filter==$k['id']?'selected':'' ?>><?= $k['ikon'] ?> <?= htmlspecialchars($k['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-filter">🔍 Filter</button>
        <a href="daftar-laporan.php" style="color:#64748b; text-decoration:none; font-size:.85rem; align-self:flex-end; padding-bottom:9px;">Reset</a>
    </form>

    <!-- Grid Laporan -->
    <?php if (empty($laporan_list)): ?>
    <div style="text-align:center; padding:60px; color:#64748b;">
        <span style="font-size:3rem;">🔍</span>
        <p style="margin-top:12px; font-size:1.1rem;">Tidak ada laporan ditemukan.</p>
    </div>
    <?php else: ?>
    <div class="laporan-grid">
        <?php foreach ($laporan_list as $lap):
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

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php
        $base_url = '?q=' . urlencode($search) . '&status=' . urlencode($status_filter) . '&kategori=' . $kat_filter;
        if ($page > 1) echo "<a href='{$base_url}&page=" . ($page-1) . "'>←</a>";
        for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++) {
            $active = $i == $page ? ' active' : '';
            echo "<a href='{$base_url}&page={$i}' class='{$active}'>{$i}</a>";
        }
        if ($page < $total_pages) echo "<a href='{$base_url}&page=" . ($page+1) . "'>→</a>";
        ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<footer class="footer" style="margin-top:60px;">
    <div class="footer-bottom"><p>© <?= date('Y') ?> LaporFasum.</p></div>
</footer>

</body>
</html>
