-- ============================================
-- DATABASE: Sistem Pelaporan Fasilitas Umum
-- ============================================

CREATE DATABASE IF NOT EXISTS fasilitas_umum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fasilitas_umum;

-- Tabel Kategori Fasilitas
CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    ikon VARCHAR(50) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Laporan
CREATE TABLE IF NOT EXISTS laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_laporan VARCHAR(20) UNIQUE NOT NULL,
    nama_pelapor VARCHAR(150) NOT NULL,
    email_pelapor VARCHAR(150),
    no_hp VARCHAR(20),
    kategori_id INT NOT NULL,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT NOT NULL,
    lokasi VARCHAR(255) NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    foto VARCHAR(255),
    status ENUM('menunggu','diproses','selesai','ditolak') DEFAULT 'menunggu',
    prioritas ENUM('rendah','sedang','tinggi','darurat') DEFAULT 'sedang',
    catatan_admin TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id)
);

-- Tabel Admin
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Data Awal Kategori
INSERT INTO kategori (nama, ikon, deskripsi) VALUES
('Bangku Taman', '🪑', 'Kerusakan bangku di taman kota atau fasilitas publik'),
('Trotoar', '🚶', 'Kerusakan jalan pejalan kaki atau trotoar'),
('Drainase', '🌊', 'Saluran air tersumbat atau rusak'),
('Halte Bus', '🚌', 'Kerusakan halte atau tempat pemberhentian angkutan umum'),
('Lampu Jalan', '💡', 'Lampu penerangan jalan mati atau rusak'),
('Taman Bermain', '🎡', 'Kerusakan fasilitas taman bermain anak'),
('Jembatan', '🌉', 'Kerusakan pada jembatan pejalan kaki atau kendaraan'),
('Tempat Sampah', '🗑️', 'Tempat sampah rusak atau tidak tersedia');

-- Data Admin Default (password: admin123)
INSERT INTO admin (username, password, nama, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@fasilitasumum.id');

-- Data Contoh Laporan
INSERT INTO laporan (kode_laporan, nama_pelapor, email_pelapor, no_hp, kategori_id, judul, deskripsi, lokasi, status, prioritas) VALUES
('RPT-20240001', 'Budi Santoso', 'budi@email.com', '081234567890', 1, 'Bangku Taman Alun-Alun Rusak', 'Bangku taman di sudut barat alun-alun patah pada bagian sandaran dan sudah tidak layak digunakan oleh pengunjung.', 'Alun-Alun Utara, Kota', 'menunggu', 'sedang'),
('RPT-20240002', 'Siti Rahayu', 'siti@email.com', '082345678901', 2, 'Trotoar Jl. Merdeka Berlubang', 'Terdapat lubang besar pada trotoar di depan toko ABC yang berbahaya bagi pejalan kaki, terutama lansia dan anak-anak.', 'Jl. Merdeka No. 45', 'diproses', 'tinggi'),
('RPT-20240003', 'Ahmad Fauzi', NULL, '083456789012', 3, 'Drainase Jl. Pahlawan Tersumbat', 'Saluran air di sepanjang Jl. Pahlawan tersumbat sampah sehingga menyebabkan genangan air saat hujan.', 'Jl. Pahlawan, RT 03/RW 05', 'selesai', 'tinggi'),
('RPT-20240004', 'Dewi Lestari', 'dewi@email.com', '084567890123', 4, 'Atap Halte Roboh', 'Atap halte bus di depan pasar rusak parah dan berpotensi membahayakan penumpang yang menunggu.', 'Halte Pasar Besar', 'menunggu', 'darurat');

-- Update data contoh dengan koordinat GPS (Jakarta)
UPDATE laporan SET latitude = -6.1744, longitude = 106.8227 WHERE kode_laporan = 'RPT-20240001';
UPDATE laporan SET latitude = -6.2088, longitude = 106.8456 WHERE kode_laporan = 'RPT-20240002';
UPDATE laporan SET latitude = -6.1865, longitude = 106.7341 WHERE kode_laporan = 'RPT-20240003';
UPDATE laporan SET latitude = -6.2297, longitude = 106.6897 WHERE kode_laporan = 'RPT-20240004';
