// LaporFasum – Main JS

// Pilih kategori dari kartu
function selectKategori(id, nama) {
    const sel = document.getElementById('select-kategori');
    if (sel) {
        sel.value = id;
        // Scroll ke form
        document.getElementById('form-laporan')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Highlight select
        sel.style.borderColor = '#1a56db';
        setTimeout(() => sel.style.borderColor = '', 2000);
    }
}

// Preview foto
function previewFoto(input) {
    const preview = document.getElementById('foto-preview');
    const text = document.getElementById('file-text');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (text) text.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Auto-hide alerts after 8s
document.addEventListener('DOMContentLoaded', () => {
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity .5s';
            setTimeout(() => alert.remove(), 500);
        }, 8000);
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const target = document.querySelector(a.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});

// Confirm delete
function confirmDelete(msg = 'Yakin ingin menghapus data ini?') {
    return confirm(msg);
}

// GPS Otomatis
function deteksiGPS() {
    const btn    = document.getElementById('btn-gps');
    const status = document.getElementById('gps-status');
    const latEl  = document.getElementById('input-lat');
    const lngEl  = document.getElementById('input-lng');
    const lokasiEl = document.getElementById('input-lokasi');

    if (!navigator.geolocation) {
        status.textContent = '❌ Browser tidak mendukung GPS.';
        status.style.color = '#ef4444';
        return;
    }

    btn.textContent = '⏳ Mendeteksi...';
    btn.disabled = true;
    status.textContent = 'Sedang mengambil koordinat GPS...';
    status.style.color = '#64748b';

    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            const lat = pos.coords.latitude.toFixed(6);
            const lng = pos.coords.longitude.toFixed(6);
            latEl.value = lat;
            lngEl.value = lng;

            status.textContent = `✅ Koordinat: ${lat}, ${lng} — Mencari alamat...`;
            status.style.color = '#10b981';

            // Reverse geocoding via Nominatim (gratis, tanpa API key)
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`);
                const data = await res.json();
                if (data && data.display_name) {
                    lokasiEl.value = data.display_name;
                    status.textContent = `✅ Lokasi terdeteksi (${lat}, ${lng})`;
                }
            } catch(e) {
                // Tetap isi koordinat meski reverse geocoding gagal
                status.textContent = `✅ Koordinat GPS: ${lat}, ${lng}`;
            }

            btn.textContent = '✅ GPS';
            btn.style.background = '#10b981';
            btn.disabled = false;
        },
        (err) => {
            const pesanError = {
                1: 'Izin lokasi ditolak. Aktifkan akses lokasi di browser.',
                2: 'Posisi tidak tersedia. Pastikan GPS aktif.',
                3: 'Waktu habis. Coba lagi.',
            };
            status.textContent = '❌ ' + (pesanError[err.code] || 'GPS gagal.');
            status.style.color = '#ef4444';
            btn.textContent = '📍 GPS';
            btn.disabled = false;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}
