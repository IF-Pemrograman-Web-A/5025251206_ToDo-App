<?php
// menubar.php - Komponen Menu Bar / Side Bar Modular
// Kriteria 1: Memiliki 2 menu utama: "Personal" dan "Shared"

// Hitung jumlah tugas untuk masing-masing kategori
$countPersonal = 0;
$countShared = 0;
try {
    $countStmt = $pdo->query("SELECT category, COUNT(*) as total FROM todos WHERE is_completed = 0 GROUP BY category");
    while ($row = $countStmt->fetch()) {
        if ($row['category'] === 'personal') $countPersonal = $row['total'];
        if ($row['category'] === 'shared') $countShared = $row['total'];
    }
} catch (PDOException $e) {
    // Abaikan jika query gagal pada inisialisasi awal
}
?>

<aside class="sidebar" role="navigation" aria-label="Menu Utama Todo App">
    <div class="sidebar-header">
        <h2 class="sidebar-title">Kategori</h2>
    </div>

    <!-- Dua Menu Utama Sesuai Kriteria 1: Personal dan Shared -->
    <nav class="nav-menu">
        <a href="index.php?view=personal" 
           class="nav-item <?= ($currentView === 'personal') ? 'active' : ''; ?>"
           aria-current="<?= ($currentView === 'personal') ? 'page' : 'false'; ?>"
           id="menu-personal">
            <span class="nav-icon">👤</span>
            <span class="nav-label">Personal</span>
            <span class="badge" title="<?= $countPersonal; ?> tugas personal aktif"><?= $countPersonal; ?></span>
        </a>

        <a href="index.php?view=shared" 
           class="nav-item <?= ($currentView === 'shared') ? 'active' : ''; ?>"
           aria-current="<?= ($currentView === 'shared') ? 'page' : 'false'; ?>"
           id="menu-shared">
            <span class="nav-icon">👥</span>
            <span class="nav-label">Shared</span>
            <span class="badge badge-shared" title="<?= $countShared; ?> tugas organisasi/shared aktif"><?= $countShared; ?></span>
        </a>
    </nav>

    <!-- PWA & Offline Abyss Section (Kriteria 1-4) -->
    <div class="sidebar-section">
        <h3 class="section-title">PWA & Offline Abyss</h3>
        <p class="section-desc">Instalasi aplikasi & akses offline tanpa sinyal</p>
        <div class="tool-links">
            <button id="sidebar-install-btn" class="tool-btn btn-sidebar-install" type="button" hidden>
                <span class="tool-icon">📲</span>
                <span>Instal Aplikasi PWA</span>
            </button>
            <a href="offline.html" class="tool-btn" title="Halaman Fallback Offline Abyss">
                <span class="tool-icon">⚡</span>
                <span>Simulasi Offline Page</span>
            </a>
        </div>
    </div>
</aside>
