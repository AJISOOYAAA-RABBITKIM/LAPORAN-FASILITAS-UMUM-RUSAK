<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta Pelaporan – LaporFasum</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        #map { height: calc(100vh - 130px); width: 100%; }

        .map-legend {
            position: absolute; bottom: 40px; right: 12px; z-index: 1000;
            background: #fff; border-radius: 12px; padding: 14px 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.15);
            font-family: 'Space Grotesk', sans-serif; font-size: .82rem;
            min-width: 160px;
        }
        .map-legend h4 { font-size: .85rem; font-weight: 700; margin-bottom: 10px; color: #0f172a; }
        .legend-item { display: flex; align-items: center; gap: 8px; margin-bottom: 7px; }
        .legend-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; }

        .map-filter-bar {
            background: #fff; border-bottom: 1px solid #e2e8f0;
            padding: 10px 16px; display: flex; gap: 10px; align-items: center;
            flex-wrap: wrap; font-family: 'Space Grotesk', sans-serif;
        }
        .map-filter-bar label { font-size: .8rem; font-weight: 600; color: #64748b; }
        .map-filter-bar select {
            border: 1.5px solid #e2e8f0; border-radius: 8px;
            padding: 6px 10px; font-family: 'Space Grotesk', sans-serif;
            font-size: .85rem; color: #0f172a; cursor: pointer;
        }
        .map-filter-bar select:focus { outline: none; border-color: #1a56db; }
        .map-count {
            margin-left: auto; font-size: .82rem; color: #64748b;
            background: #f1f5f9; padding: 5px 12px; border-radius: 20px;
        }

        /* Custom popup */
        .leaflet-popup-content-wrapper {
            border-radius: 12px !important;
            box-shadow: 0 8px 24px rgba(0,0,0,.15) !important;
            font-family: 'Space Grotesk', sans-serif !important;
        }
        .popup-ikon { font-size: 1.8rem; text-align: center; }
        .popup-judul { font-weight: 700; font-size: .95rem; margin: 6px 0 4px; color: #0f172a; }
        .popup-lokasi { font-size: .8rem; color: #64748b; margin-bottom: 8px; }
        .popup-badges { display: flex; gap: 5px; flex-wrap: wrap; }
        .popup-badge {
            font-size: .72rem; font-weight: 700; padding: 2px 8px;
            border-radius: 20px;
        }
        .popup-kode { font-size: .75rem; color: #1a56db; font-family: monospace; margin-top: 8px; font-weight: 600; }
        .popup-foto { width: 100%; border-radius: 8px; margin-top: 8px; max-height: 120px; object-fit: cover; }

        .map-wrapper { position: relative; }
    </style>
</head>
<body>

<?php
require_once 'includes/config.php';

// Ambil semua laporan yang punya koordinat
$stmt_map = $pdo->query("
    SELECT l.id, l.kode_laporan, l.judul, l.lokasi, l.status, l.prioritas,
           l.latitude, l.longitude, l.foto, l.created_at,
           k.nama as kategori_nama, k.ikon
    FROM laporan l
    JOIN kategori k ON l.kategori_id = k.id
    ORDER BY l.created_at DESC
");
$semua = $stmt_map->fetchAll();

// Laporan dengan koordinat
$dengan_koordinat = array_filter($semua, fn($r) => $r['latitude'] && $r['longitude']);

// Statistik per status
$stats = $pdo->query("SELECT status, COUNT(*) as total FROM laporan GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

// Kategori untuk filter
$kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama")->fetchAll();

// Encode untuk JavaScript
$map_data = json_encode(array_values($dengan_koordinat));
?>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo"><span class="logo-icon">📢</span><span>LaporFasum</span></a>
        <div class="nav-links">
            <a href="index.php">Beranda</a>
            <a href="cek-status.php">Cek Status</a>
            <a href="daftar-laporan.php">Semua Laporan</a>
            <a href="admin/login.php" class="btn-admin">Admin</a>
        </div>
    </div>
</nav>

<!-- FILTER BAR -->
<div class="map-filter-bar">
    <span style="font-size:.9rem; font-weight:700; color:#0f172a;">🗺️ Peta Pelaporan</span>
    <div style="display:flex;align-items:center;gap:6px;">
        <label>Status:</label>
        <select id="filter-status" onchange="filterMarkers()">
            <option value="">Semua</option>
            <option value="menunggu">⏳ Menunggu</option>
            <option value="diproses">🔧 Diproses</option>
            <option value="selesai">✅ Selesai</option>
            <option value="ditolak">❌ Ditolak</option>
        </select>
    </div>
    <div style="display:flex;align-items:center;gap:6px;">
        <label>Kategori:</label>
        <select id="filter-kategori" onchange="filterMarkers()">
            <option value="">Semua</option>
            <?php foreach ($kategori_list as $k): ?>
            <option value="<?= htmlspecialchars($k['nama']) ?>"><?= $k['ikon'] ?> <?= htmlspecialchars($k['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <span class="map-count" id="map-count">
        📍 <?= count($dengan_koordinat) ?> dari <?= count($semua) ?> laporan memiliki koordinat
    </span>
</div>

<!-- MAP -->
<div class="map-wrapper">
    <div id="map"></div>

    <!-- Legend -->
    <div class="map-legend">
        <h4>🎯 Keterangan</h4>
        <div class="legend-item"><div class="legend-dot" style="background:#f59e0b;"></div><span>Menunggu</span></div>
        <div class="legend-item"><div class="legend-dot" style="background:#3b82f6;"></div><span>Diproses</span></div>
        <div class="legend-item"><div class="legend-dot" style="background:#10b981;"></div><span>Selesai</span></div>
        <div class="legend-item"><div class="legend-dot" style="background:#ef4444;"></div><span>Ditolak</span></div>
        <hr style="border:none;border-top:1px solid #e2e8f0;margin:8px 0;">
        <div style="font-size:.75rem;color:#64748b;">Klik pin untuk detail</div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Data dari PHP
const allData = <?= $map_data ?>;

// Warna berdasarkan status
const statusColor = {
    menunggu: '#f59e0b',
    diproses: '#3b82f6',
    selesai:  '#10b981',
    ditolak:  '#ef4444',
};
const statusLabel = {
    menunggu: 'Menunggu',
    diproses: 'Diproses',
    selesai:  'Selesai',
    ditolak:  'Ditolak',
};
const prioLabel = {
    rendah:  { label: 'Rendah',  bg: '#f0fdf4', color: '#166534' },
    sedang:  { label: 'Sedang',  bg: '#fffbeb', color: '#92400e' },
    tinggi:  { label: 'Tinggi',  bg: '#fff7ed', color: '#9a3412' },
    darurat: { label: 'Darurat', bg: '#fef2f2', color: '#991b1b' },
};

// Init peta — default center Indonesia
const map = L.map('map').setView([-2.5, 118], 5);

// Tile layer OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/">OpenStreetMap</a>',
    maxZoom: 19
}).addTo(map);

// Buat custom icon
function makeIcon(status) {
    const color = statusColor[status] || '#64748b';
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
        <path d="M16 0C7.16 0 0 7.16 0 16c0 10 16 26 16 26S32 26 32 16C32 7.16 24.84 0 16 0z" fill="${color}" stroke="#fff" stroke-width="2"/>
        <circle cx="16" cy="16" r="7" fill="#fff" opacity=".9"/>
    </svg>`;
    return L.divIcon({
        html: svg,
        iconSize: [32, 42],
        iconAnchor: [16, 42],
        popupAnchor: [0, -44],
        className: ''
    });
}

// Simpan semua marker
let markers = [];

function renderMarkers(data) {
    // Hapus marker lama
    markers.forEach(m => map.removeLayer(m));
    markers = [];

    if (data.length === 0) return;

    const bounds = [];

    data.forEach(item => {
        const lat = parseFloat(item.latitude);
        const lng = parseFloat(item.longitude);
        if (!lat || !lng) return;

        const prio = prioLabel[item.prioritas] || prioLabel.sedang;
        const sLabel = statusLabel[item.status] || item.status;
        const sColor = statusColor[item.status] || '#64748b';

        const foto = item.foto
            ? `<img src="uploads/${item.foto}" class="popup-foto" alt="foto"/>`
            : '';

        const popup = `
            <div style="max-width:220px;">
                <div class="popup-ikon">${item.ikon}</div>
                <div class="popup-judul">${item.judul}</div>
                <div class="popup-lokasi">📍 ${item.lokasi}</div>
                <div class="popup-badges">
                    <span class="popup-badge" style="background:${sColor}22;color:${sColor};">${sLabel}</span>
                    <span class="popup-badge" style="background:${prio.bg};color:${prio.color};">${prio.label}</span>
                    <span class="popup-badge" style="background:#f1f5f9;color:#475569;">${item.kategori_nama}</span>
                </div>
                ${foto}
                <div class="popup-kode">${item.kode_laporan}</div>
            </div>`;

        const marker = L.marker([lat, lng], { icon: makeIcon(item.status) })
            .bindPopup(popup)
            .addTo(map);

        marker._data = item;
        markers.push(marker);
        bounds.push([lat, lng]);
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
    }

    document.getElementById('map-count').textContent =
        `📍 ${data.length} laporan ditampilkan`;
}

// Filter markers
function filterMarkers() {
    const fStatus   = document.getElementById('filter-status').value;
    const fKategori = document.getElementById('filter-kategori').value;

    const filtered = allData.filter(item => {
        const okStatus   = !fStatus   || item.status === fStatus;
        const okKategori = !fKategori || item.kategori_nama === fKategori;
        return okStatus && okKategori;
    });

    renderMarkers(filtered);
}

// Render pertama kali
renderMarkers(allData);

// Jika tidak ada data koordinat, tampilkan pesan
if (allData.length === 0) {
    const info = L.control({ position: 'topright' });
    info.onAdd = () => {
        const div = L.DomUtil.create('div');
        div.innerHTML = `<div style="background:#fff;padding:14px 18px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.15);font-family:'Space Grotesk',sans-serif;font-size:.85rem;color:#64748b;max-width:240px;">
            ℹ️ Belum ada laporan dengan koordinat GPS.<br><br>
            Saat mengisi laporan, klik tombol <strong>📍 GPS</strong> agar lokasi muncul di peta.
        </div>`;
        return div;
    };
    info.addTo(map);
}
</script>

</body>
</html>
