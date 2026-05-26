<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan – Admin LaporFasum</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<?php
require_once '../includes/config.php';
requireAdmin();

// Hapus laporan
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo->prepare("DELETE FROM laporan WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: laporan.php?msg=deleted'); exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$per = 15; $offset = ($page-1)*$per;
$status_f = $_GET['status'] ?? '';
$prio_f   = $_GET['prioritas'] ?? '';
$search   = trim($_GET['q'] ?? '');

$where = ['1=1']; $params = [];
if ($status_f) { $where[] = 'l.status = ?'; $params[] = $status_f; }
if ($prio_f)   { $where[] = 'l.prioritas = ?'; $params[] = $prio_f; }
if ($search)   { $where[] = '(l.judul LIKE ? OR l.kode_laporan LIKE ? OR l.nama_pelapor LIKE ?)'; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
$w = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM laporan l WHERE $w");
$total->execute($params); $total = $total->fetchColumn();
$pages = ceil($total/$per);

$stmt = $pdo->prepare("SELECT l.*, k.nama as kn, k.ikon FROM laporan l JOIN kategori k ON l.kategori_id=k.id WHERE $w ORDER BY l.created_at DESC LIMIT $per OFFSET $offset");
$stmt->execute($params);
$list = $stmt->fetchAll();
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
        <h1>📋 Kelola Laporan</h1>
        <div class="admin-user">👤 <?= htmlspecialchars($_SESSION['admin_nama']) ?></div>
    </div>

    <div class="admin-content">
        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-<?= $_GET['msg']==='deleted'?'error':'success' ?>" style="margin-bottom:16px;">
            <?= $_GET['msg']==='deleted' ? '🗑️ Laporan berhasil dihapus.' : '✅ Data berhasil diperbarui.' ?>
        </div>
        <?php endif; ?>

        <!-- Filter -->
        <form method="GET" class="filter-bar">
            <div class="form-group">
                <label>Cari</label>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Kode, judul, pelapor...">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="">Semua Status</option>
                    <option value="menunggu" <?= $status_f==='menunggu'?'sel':'' ?>>Menunggu</option>
                    <option value="diproses" <?= $status_f==='diproses'?'sel':'' ?>>Diproses</option>
                    <option value="selesai"  <?= $status_f==='selesai'?'sel':'' ?>>Selesai</option>
                    <option value="ditolak"  <?= $status_f==='ditolak'?'sel':'' ?>>Ditolak</option>
                </select>
            </div>
            <div class="form-group">
                <label>Prioritas</label>
                <select name="prioritas">
                    <option value="">Semua Prioritas</option>
                    <option value="darurat" <?= $prio_f==='darurat'?'selected':'' ?>>🔴 Darurat</option>
                    <option value="tinggi"  <?= $prio_f==='tinggi'?'selected':'' ?>>🟠 Tinggi</option>
                    <option value="sedang"  <?= $prio_f==='sedang'?'selected':'' ?>>🟡 Sedang</option>
                    <option value="rendah"  <?= $prio_f==='rendah'?'selected':'' ?>>🟢 Rendah</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">🔍 Filter</button>
            <a href="laporan.php" style="color:#64748b; font-size:.85rem; text-decoration:none; align-self:flex-end; padding-bottom:9px;">Reset</a>
        </form>

        <div class="admin-table-card">
            <div class="table-header">
                <h2>Total: <?= number_format($total) ?> laporan</h2>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Judul</th>
                        <th>Pelapor</th>
                        <th>Kategori</th>
                        <th>Prioritas</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list)): ?>
                    <tr><td colspan="8" style="text-align:center; color:#94a3b8; padding:40px;">Tidak ada laporan ditemukan.</td></tr>
                    <?php else: ?>
                    <?php foreach ($list as $l):
                        $b = statusBadge($l['status']);
                        $p = prioritasBadge($l['prioritas']);
                    ?>
                    <tr>
                        <td><code style="color:#1a56db; font-size:.78rem;"><?= $l['kode_laporan'] ?></code></td>
                        <td style="max-width:180px;" title="<?= htmlspecialchars($l['judul']) ?>"><?= htmlspecialchars(substr($l['judul'],0,30)) ?>...</td>
                        <td><?= htmlspecialchars($l['nama_pelapor']) ?></td>
                        <td><?= $l['ikon'] ?> <?= htmlspecialchars($l['kn']) ?></td>
                        <td><span class="badge <?= $p['class'] ?>"><?= $p['label'] ?></span></td>
                        <td><span class="badge <?= $b['class'] ?>"><?= $b['label'] ?></span></td>
                        <td><?= date('d/m/Y', strtotime($l['created_at'])) ?></td>
                        <td style="display:flex; gap:6px; align-items:center;">
                            <a href="detail.php?id=<?= $l['id'] ?>" style="color:#1a56db; font-size:.82rem; font-weight:600; text-decoration:none;">✏️ Detail</a>
                            <a href="laporan.php?delete=<?= $l['id'] ?>" onclick="return confirmDelete('Yakin hapus laporan <?= $l['kode_laporan'] ?>?')" style="color:#ef4444; font-size:.82rem; font-weight:600; text-decoration:none;">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($pages > 1): ?>
            <div class="pagination" style="padding:16px;">
                <?php
                $base = "?q=".urlencode($search)."&status=$status_f&prioritas=$prio_f";
                if ($page>1) echo "<a href='{$base}&page=".($page-1)."'>←</a>";
                for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++){
                    $a=$i==$page?' active':'';
                    echo "<a href='{$base}&page={$i}' class='{$a}'>{$i}</a>";
                }
                if($page<$pages) echo "<a href='{$base}&page=".($page+1)."'>→</a>";
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>
