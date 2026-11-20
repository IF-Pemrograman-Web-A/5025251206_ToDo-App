<?php
// index.php - Main Controller Modular Application
// Kriteria 2: Modular PHP (config.php, header.php, menubar.php, content.php, footer.php)

require_once __DIR__ . '/config.php';

// Tentukan Kategori Tampilan (Kriteria 1: Personal vs Shared)
$currentView = $_GET['view'] ?? 'personal';
if (!in_array($currentView, ['personal', 'shared'])) {
    $currentView = 'personal';
}

// Cek apakah sedang dalam mode edit (CRUD: UPDATE)
$editId = (int)($_GET['edit'] ?? 0);
$editingTask = null;
if ($editId > 0) {
    $editStmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
    $editStmt->execute([$editId]);
    $editingTask = $editStmt->fetch() ?: null;
}

// Ambil data tugas berdasarkan kategori yang dipilih (CRUD: READ)
try {
    $stmt = $pdo->prepare("
        SELECT * FROM todos 
        WHERE category = ? 
        ORDER BY is_completed ASC, due_date ASC, id DESC
    ");
    $stmt->execute([$currentView]);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    $tasks = [];
    set_flash('error', 'Gagal memuat data tugas: ' . $e->getMessage());
}

// Ambil semua tugas yang memiliki jadwal notifikasi untuk Service Worker Reminder
try {
    $notifStmt = $pdo->query("
        SELECT id, title, description, notif_time, is_completed 
        FROM todos 
        WHERE notif_time IS NOT NULL AND is_completed = 0
    ");
    $tasksForNotif = $notifStmt->fetchAll();
} catch (PDOException $e) {
    $tasksForNotif = [];
}

// 1. Memuat Header Modular
require_once __DIR__ . '/header.php';
?>

<!-- Container Utama dengan Struktur Modular -->
<div class="layout-container">
    <?php
    // 2. Memuat Menu Bar / Sidebar Modular (Kriteria 1 & 2)
    require_once __DIR__ . '/menubar.php';

    // 3. Memuat Konten Utama Modular (Kriteria 2 & 4)
    require_once __DIR__ . '/content.php';
    ?>
</div>

<?php
// 4. Memuat Footer Modular
require_once __DIR__ . '/footer.php';
?>
