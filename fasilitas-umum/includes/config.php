<?php
// ============================================
// KONFIGURASI DATABASE
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Ganti dengan username MySQL Anda
define('DB_PASS', '');            // Ganti dengan password MySQL Anda
define('DB_NAME', 'fasilitas_umum');

define('SITE_NAME', 'LaporFasum');
define('SITE_URL', 'http://localhost/fasilitas-umum');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Koneksi Database
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['error' => 'Koneksi database gagal: ' . $e->getMessage()]));
}

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper Functions
function generateKodeLaporan($pdo) {
    $tahun = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM laporan WHERE YEAR(created_at) = $tahun");
    $row = $stmt->fetch();
    $nomor = str_pad($row['total'] + 1, 4, '0', STR_PAD_LEFT);
    return "RPT-{$tahun}{$nomor}";
}

function statusBadge($status) {
    $badges = [
        'menunggu' => ['label' => 'Menunggu', 'class' => 'status-waiting'],
        'diproses' => ['label' => 'Diproses', 'class' => 'status-process'],
        'selesai'  => ['label' => 'Selesai',  'class' => 'status-done'],
        'ditolak'  => ['label' => 'Ditolak',  'class' => 'status-rejected'],
    ];
    return $badges[$status] ?? ['label' => $status, 'class' => 'status-waiting'];
}

function prioritasBadge($prioritas) {
    $badges = [
        'rendah'  => ['label' => 'Rendah',  'class' => 'prio-low'],
        'sedang'  => ['label' => 'Sedang',  'class' => 'prio-medium'],
        'tinggi'  => ['label' => 'Tinggi',  'class' => 'prio-high'],
        'darurat' => ['label' => 'Darurat', 'class' => 'prio-urgent'],
    ];
    return $badges[$prioritas] ?? ['label' => $prioritas, 'class' => 'prio-medium'];
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}
?>
