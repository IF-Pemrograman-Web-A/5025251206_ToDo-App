// script.js - Client-Side Enhancements (Theme, Service Worker, Dynamic Details Card, Accessibility)

// Elemen DOM
const themeToggleBtn = document.getElementById('theme-toggle');
const announcer = document.getElementById('a11y-announcer');
const detailCard = document.getElementById('task-detail-card');

// 1. TEMA GELAP / TERANG (Local Storage User Preference)
function initTheme() {
    const savedTheme = localStorage.getItem('citadel_theme_preference');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        updateThemeToggleIcon(true);
    } else {
        updateThemeToggleIcon(false);
    }
}

function updateThemeToggleIcon(isDark) {
    if (!themeToggleBtn) return;
    const iconSpan = themeToggleBtn.querySelector('.theme-icon');
    const textSpan = themeToggleBtn.querySelector('.theme-text');
    if (iconSpan) iconSpan.textContent = isDark ? '☀️' : '🌙';
    if (textSpan) textSpan.textContent = isDark ? 'Mode Terang' : 'Mode Gelap';
}

if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('citadel_theme_preference', isDark ? 'dark' : 'light');
        updateThemeToggleIcon(isDark);
        announce(isDark ? "Mode gelap diaktifkan" : "Mode terang diaktifkan");
    });
}
initTheme();

// 2. ACCESSIBILITY ANNOUNCER
function announce(message) {
    if (!announcer) return;
    announcer.textContent = message;
    setTimeout(() => { announcer.textContent = ''; }, 3000);
}

// 3. SERVICE WORKER & NOTIFIKASI PENGINGAT
let notifTasks = [];
try {
    const notifScript = document.getElementById('todos-data');
    if (notifScript) {
        notifTasks = JSON.parse(notifScript.textContent || '[]');
    }
} catch (e) {
    console.warn("Gagal parse notifTasks:", e);
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js', { scope: './' })
            .then((registration) => {
                console.log('[Service Worker] Berhasil terdaftar dengan scope:', registration.scope);
                
                // Cek update berkala
                registration.onupdatefound = () => {
                    const installingWorker = registration.installing;
                    if (installingWorker) {
                        installingWorker.onstatechange = () => {
                            if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                console.log('[Service Worker] Konten baru tersedia; silakan segarkan halaman.');
                            }
                        };
                    }
                };
            })
            .catch((err) => console.error('[Service Worker Error]', err));
    });

    // Minta izin notifikasi jika didukung dan belum ditentukan
    if ('Notification' in window && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
        Notification.requestPermission();
    }
}

// 4. PWA INSTALL BUTTON HANDLER (Kriteria 4)
let deferredInstallPrompt = null;
const installBtns = [
    document.getElementById('install-btn'),
    document.getElementById('sidebar-install-btn')
].filter(Boolean);

window.addEventListener('beforeinstallprompt', (e) => {
    // Mencegah mini-infobar default muncul secara otomatis
    e.preventDefault();
    deferredInstallPrompt = e;
    console.log('[PWA] Event beforeinstallprompt tertangkap.');

    // Tampilkan tombol instal jika belum dalam mode standalone
    if (!isAppInstalled()) {
        installBtns.forEach(btn => { btn.hidden = false; });
        announce('Aplikasi Todo siap diinstal ke perangkat Anda.');
    }
});

installBtns.forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!deferredInstallPrompt) {
            console.warn('[PWA] Prompt instal belum siap atau sudah dijalankan.');
            return;
        }
        // Munculkan dialog prompt instalasi browser bawaan
        deferredInstallPrompt.prompt();
        const { outcome } = await deferredInstallPrompt.userChoice;
        console.log(`[PWA] Pilihan instalasi pengguna: ${outcome}`);

        if (outcome === 'accepted') {
            announce('Instalasi aplikasi disetujui!');
        } else {
            announce('Instalasi aplikasi dibatalkan.');
        }
        deferredInstallPrompt = null;
        installBtns.forEach(b => { b.hidden = true; });
    });
});

// Deteksi jika aplikasi berhasil diinstal
window.addEventListener('appinstalled', () => {
    console.log('[PWA] Citadel Todo berhasil diinstal ke perangkat.');
    installBtns.forEach(btn => { btn.hidden = true; });
    announce('Citadel Todo berhasil diinstal ke sistem perangkat Anda!');
});

// Cek apakah aplikasi sudah berjalan dalam mode standalone (terinstal)
function isAppInstalled() {
    return window.matchMedia('(display-mode: standalone)').matches || 
           window.navigator.standalone === true;
}

if (isAppInstalled()) {
    installBtns.forEach(btn => { btn.hidden = true; });
}

// 5. DETEKSI STATUS JARINGAN (ONLINE / OFFLINE ABYSS)
const networkStatusBadge = document.getElementById('network-status');

function updateNetworkStatus() {
    if (!networkStatusBadge) return;
    const text = networkStatusBadge.querySelector('.status-text');

    if (navigator.onLine) {
        networkStatusBadge.classList.remove('offline');
        networkStatusBadge.classList.add('online');
        if (text) text.textContent = 'Online';
    } else {
        networkStatusBadge.classList.remove('online');
        networkStatusBadge.classList.add('offline');
        if (text) text.textContent = 'Offline Abyss';
        announce('Koneksi terputus. Mode Offline Abyss aktif dengan cache.');
    }
}

window.addEventListener('online', () => {
    updateNetworkStatus();
    announce('Koneksi internet terhubung kembali.');
});
window.addEventListener('offline', updateNetworkStatus);
// Inisialisasi status jaringan saat pertama kali dimuat
updateNetworkStatus();

// Set pengingat berkala setiap 10 detik untuk notifikasi deadline/reminder
const notifiedSet = new Set();
setInterval(() => {
    if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return;
    const now = new Date().getTime();

    notifTasks.forEach(task => {
        if (task.notif_time && !task.is_completed && !notifiedSet.has(task.id)) {
            const timeToNotify = new Date(task.notif_time).getTime();
            // Jika waktu sekarang sudah melewati atau sama dengan waktu pengingat (toleransi 2 menit)
            if (now >= timeToNotify && (now - timeToNotify) < 120000) {
                notifiedSet.add(task.id);
                if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                    navigator.serviceWorker.ready.then(reg => {
                        reg.showNotification("⏰ Pengingat Tugas: " + task.title, {
                            body: task.description || "Tugas ini memiliki jadwal pengingat sekarang.",
                            icon: 'icons/icon-192x192.png',
                            badge: 'icons/icon-192x192.png'
                        });
                    });
                } else {
                    new Notification("⏰ Pengingat Tugas: " + task.title, {
                        body: task.description || "Tugas ini memiliki jadwal pengingat sekarang.",
                        icon: 'icons/icon-192x192.png'
                    });
                }
                announce("Notifikasi pengingat untuk: " + task.title);
            }
        }
    });
}, 10000);

// 6. INTERAKTIF DETAIL CARD (Dapat berganti saat item di list diklik tanpa refresh)
function showTaskDetail(task) {
    if (!detailCard || !task) return;

    const isDone = Boolean(Number(task.is_completed));
    const statusClass = isDone ? 'status-completed' : 'status-pending';
    const statusText = isDone ? '✅ Completed' : '⏳ In Progress';
    const categoryClass = task.category === 'shared' ? 'tag-shared' : 'tag-personal';
    const categoryText = task.category === 'shared' ? 'Shared' : 'Personal';

    let fileHtml = '<p class="no-attachment"><em>Tidak ada lampiran file.</em></p>';
    if (task.file_path) {
        const ext = task.file_path.split('.').pop().toLowerCase();
        const isImg = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
        const fileName = task.file_name || task.file_path;

        fileHtml = `
            <div class="attachment-preview-box">
                <strong>📎 File Lampiran:</strong>
                ${isImg ? `
                    <div class="img-wrapper">
                        <img src="uploads/${escapeHtml(task.file_path)}" alt="Lampiran ${escapeHtml(task.title)}" class="todo-image-preview">
                    </div>
                ` : ''}
                <div class="file-download-bar">
                    <span class="file-name-label">📄 ${escapeHtml(fileName)}</span>
                    <a href="uploads/${escapeHtml(task.file_path)}" download="${escapeHtml(fileName)}" class="btn-download" target="_blank">
                        ⬇️ Unduh File
                    </a>
                </div>
            </div>
        `;
    }

    const notifFormatted = task.notif_time ? task.notif_time.replace('T', ' ') : 'None';

    detailCard.innerHTML = `
        <div class="detail-badge-group">
            <span class="tag tag-category ${categoryClass}">${categoryText}</span>
            <span class="badge-status ${statusClass}">${statusText}</span>
        </div>
        <h3 class="detail-title">${escapeHtml(task.title)}</h3>
        <div class="detail-info-grid">
            <p><strong>📅 Batas Waktu:</strong> <span>${escapeHtml(task.due_date)}</span></p>
            <p><strong>⏰ Waktu Notifikasi:</strong> <span>${escapeHtml(notifFormatted)}</span></p>
        </div>
        <div class="detail-desc-box">
            <strong>Deskripsi:</strong>
            <p style="white-space: pre-line;">${escapeHtml(task.description)}</p>
        </div>
        <div>
            ${fileHtml}
        </div>
    `;

    announce(`Menampilkan rincian untuk tugas ${task.title}`);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}