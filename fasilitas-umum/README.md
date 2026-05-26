# 📢 LaporFasum – Sistem Pelaporan Fasilitas Umum

Platform digital untuk warga melaporkan kerusakan fasilitas umum seperti bangku taman rusak, trotoar berlubang, drainase tersumbat, halte rusak, dan lainnya.

---

## 🚀 Instalasi

### Prasyarat
- PHP 7.4 atau lebih baru
- MySQL 5.7 / MariaDB 10.3+
- Web server: Apache (XAMPP/Laragon) atau Nginx
- Ekstensi PHP: PDO, PDO_MySQL, fileinfo

### Langkah Instalasi

**1. Letakkan folder proyek:**
```
C:/xampp/htdocs/fasilitas-umum/    (XAMPP Windows)
/var/www/html/fasilitas-umum/      (Linux)
```

**2. Buat Database:**
- Buka phpMyAdmin → `http://localhost/phpmyadmin`
- Import file `database.sql`
- Atau jalankan manual:
```sql
mysql -u root -p < database.sql
```

**3. Konfigurasi koneksi:**
Edit file `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // username MySQL Anda
define('DB_PASS', '');           // password MySQL Anda
define('DB_NAME', 'fasilitas_umum');
define('SITE_URL', 'http://localhost/fasilitas-umum');
```

**4. Buat folder uploads:**
```
fasilitas-umum/uploads/   (harus bisa ditulis / writable)
```
Atau jalankan: `chmod 755 uploads/` (Linux)

**5. Akses aplikasi:**
- Beranda: `http://localhost/fasilitas-umum/`
- Admin: `http://localhost/fasilitas-umum/admin/login.php`

---

## 🔑 Akses Admin Default

| Field    | Value      |
|----------|------------|
| Username | `admin`    |
| Password | `admin123` |

> ⚠️ **Ganti password setelah login pertama!**

Untuk mengubah password, jalankan di MySQL:
```sql
UPDATE admin SET password = '$2y$10$[hash_baru]' WHERE username = 'admin';
```
Atau buat hash baru dengan PHP: `password_hash('passwordbaru', PASSWORD_DEFAULT)`

---

## 📁 Struktur File

```
fasilitas-umum/
├── index.php               # Halaman utama & form laporan
├── cek-status.php          # Cek status laporan (publik)
├── daftar-laporan.php      # Semua laporan (publik)
├── database.sql            # Schema & data awal database
├── includes/
│   └── config.php          # Konfigurasi DB & helper functions
├── assets/
│   ├── css/style.css       # Stylesheet utama
│   └── js/main.js          # JavaScript
├── admin/
│   ├── login.php           # Halaman login admin
│   ├── dashboard.php       # Dashboard admin
│   ├── laporan.php         # Kelola laporan
│   ├── detail.php          # Detail & update laporan
│   ├── kategori.php        # Kelola kategori
│   ├── export.php          # Export CSV
│   └── logout.php          # Logout
└── uploads/                # Folder foto (buat manual)
```

---

## ✨ Fitur

### Halaman Publik
- ✅ Form pelaporan dengan upload foto
- ✅ Cek status laporan dengan kode unik
- ✅ Daftar semua laporan dengan filter & pencarian
- ✅ Kategori fasilitas (Bangku, Trotoar, Drainase, Halte, dll)
- ✅ Statistik laporan real-time
- ✅ Tracking status visual (timeline)

### Panel Admin
- ✅ Login aman
- ✅ Dashboard dengan statistik & laporan terbaru
- ✅ Kelola laporan (filter, cari, update status)
- ✅ Update status: Menunggu → Diproses → Selesai / Ditolak
- ✅ Set prioritas: Rendah, Sedang, Tinggi, Darurat
- ✅ Tambah catatan/tindakan admin
- ✅ Kelola kategori fasilitas
- ✅ Export data ke CSV

### Database
- ✅ 4 tabel: `laporan`, `kategori`, `admin`
- ✅ Kode laporan unik otomatis (RPT-YYYYNNNN)
- ✅ Relasi kategori ke laporan

---

## 🛡️ Keamanan

- Query menggunakan **PDO Prepared Statements** (anti SQL Injection)
- Output di-encode dengan `htmlspecialchars()` (anti XSS)
- Password admin di-hash dengan `password_hash()` (bcrypt)
- Validasi tipe & ukuran file upload

---

## 🔧 Kustomisasi

**Menambah kategori baru:**
Masuk ke Admin → Kategori → Tambah Kategori

**Mengubah tampilan warna:**
Edit CSS variables di `assets/css/style.css`:
```css
:root {
    --primary: #1a56db;    /* Warna utama */
    --accent: #f59e0b;     /* Warna aksen */
}
```

**Menambah field laporan:**
1. Tambah kolom di tabel `laporan` (SQL: `ALTER TABLE laporan ADD COLUMN ...`)
2. Tambah input di `index.php`
3. Tambah kolom di query INSERT di `index.php`

---

## 📞 Teknologi

- **Backend:** PHP 7.4+ (PDO)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, Vanilla JS
- **Font:** Syne (display), Space Grotesk (body)
- **No framework** – ringan dan mudah dikustomisasi
