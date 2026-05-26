<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin – LaporFasum</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php
require_once '../includes/config.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_nama'] = $admin['nama'];
        header('Location: dashboard.php'); exit;
    } else {
        $error = 'Username atau password salah.';
    }
}
?>

<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <span>📢</span>
            <h1>LaporFasum</h1>
            <p>Panel Admin – Masuk untuk mengelola laporan</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn-login" style="margin-top:8px;">🔐 Masuk</button>
        </form>

        <p style="text-align:center; margin-top:20px; font-size:.85rem; color:#94a3b8;">
            <a href="../index.php" style="color:#1a56db;">← Kembali ke Beranda</a>
        </p>
</div>
</body>
</html>
