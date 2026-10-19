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

if ('serviceWorker' in navigator && 'Notification' in window) {
    navigator.serviceWorker.register('sw.js')
        .then(() => console.log('[Service Worker] Berhasil terdaftar'))
        .catch(err => console.error('[Service Worker Error]', err));

    // Minta izin notifikasi jika belum ditentukan
    if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
        Notification.requestPermission();
    }
}

// Set pengingat berkala setiap 10 detik untuk notifikasi deadline/reminder
const notifiedSet = new Set();
setInterval(() => {
    if (Notification.permission !== 'granted') return;
    const now = new Date().getTime();

    notifTasks.forEach(task => {
        if (task.notif_time && !task.is_completed && !notifiedSet.has(task.id)) {
            const timeToNotify = new Date(task.notif_time).getTime();
            // Jika waktu sekarang sudah melewati atau sama dengan waktu pengingat (dalam toleransi 2 menit)
            if (now >= timeToNotify && (now - timeToNotify) < 120000) {
                notifiedSet.add(task.id);
                if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                    navigator.serviceWorker.ready.then(reg => {
                        reg.showNotification("⏰ Pengingat Tugas: " + task.title, {
                            body: task.description || "Tugas ini memiliki jadwal pengingat sekarang.",
                            icon: 'https://cdn-icons-png.flaticon.com/512/1055/1055685.png',
                            badge: 'https://cdn-icons-png.flaticon.com/512/1055/1055685.png'
                        });
                    });
                } else {
                    new Notification("⏰ Pengingat Tugas: " + task.title, {
                        body: task.description || "Tugas ini memiliki jadwal pengingat sekarang.",
                        icon: 'https://cdn-icons-png.flaticon.com/512/1055/1055685.png'
                    });
                }
                announce("Notifikasi pengingat untuk: " + task.title);
            }
        }
    });
}, 10000);

// 4. INTERAKTIF DETAIL CARD (Dapat berganti saat item di list diklik tanpa refresh)
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