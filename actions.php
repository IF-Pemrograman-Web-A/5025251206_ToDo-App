<?php
// actions.php - Controller untuk memproses operasi CRUD dan PHP File Handling

require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$currentView = $_GET['view'] ?? $_POST['view'] ?? 'personal';

if (!in_array($currentView, ['personal', 'shared', 'all'])) {
    $currentView = 'personal';
}

switch ($action) {
    // -------------------------------------------------------------
    // CREATE TASK (CRUD: CREATE + PHP FILE UPLOAD HANDLING)
    // -------------------------------------------------------------
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?view=$currentView");
            exit;
        }

        $title = trim($_POST['task_name'] ?? '');
        $description = trim($_POST['task_description'] ?? '');
        $category = trim($_POST['task_category'] ?? 'personal');
        $dueDate = trim($_POST['task_deadline'] ?? '');
        $notifTime = !empty($_POST['task_notif']) ? $_POST['task_notif'] : null;

        if (empty($title) || empty($dueDate)) {
            set_flash('error', 'Nama tugas dan batas waktu (due date) wajib diisi.');
            header("Location: index.php?view=$currentView");
            exit;
        }

        if (!in_array($category, ['personal', 'shared'])) {
            $category = 'personal';
        }

        // --- PHP FILE HANDLING: Upload File / Gambar ---
        $filePath = null;
        $fileName = null;
        $fileSize = null;

        if (isset($_FILES['task_file']) && $_FILES['task_file']['error'] === UPLOAD_ERR_OK) {
            $uploadedTmp = $_FILES['task_file']['tmp_name'];
            $origName = basename($_FILES['task_file']['name']);
            $size = $_FILES['task_file']['size'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if ($size > MAX_FILE_SIZE) {
                set_flash('error', 'Ukuran file terlalu besar. Maksimum 5MB.');
                header("Location: index.php?view=$currentView");
                exit;
            }

            if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                set_flash('error', 'Format file tidak diizinkan. Gunakan gambar, PDF, dokumen teks, atau zip.');
                header("Location: index.php?view=$currentView");
                exit;
            }

            // Generate nama file unik agar tidak bentrok
            $uniqueName = 'task_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destPath = UPLOAD_DIR . $uniqueName;

            // PHP File Handling: Memindahkan file dari temp ke folder uploads
            if (move_uploaded_file($uploadedTmp, $destPath)) {
                $filePath = $uniqueName;
                $fileName = $origName;
                $fileSize = $size;
                log_activity('UPLOAD_FILE', "File '$origName' berhasil disimpan sebagai '$uniqueName'");
            } else {
                set_flash('error', 'Gagal mengunggah file lampiran.');
                header("Location: index.php?view=$currentView");
                exit;
            }
        }

        // Simpan ke Database MySQL menggunakan PDO Prepared Statements
        try {
            $stmt = $pdo->prepare("
                INSERT INTO todos (title, description, category, due_date, notif_time, file_path, file_name, file_size, is_completed)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$title, $description, $category, $dueDate, $notifTime, $filePath, $fileName, $fileSize]);

            log_activity('CREATE_TASK', "Tugas '$title' (kategori: $category) berhasil ditambahkan.");
            set_flash('success', "Tugas '{$title}' berhasil ditambahkan ke kategori {$category}!");
        } catch (PDOException $e) {
            log_activity('ERROR', "Gagal menambah tugas: " . $e->getMessage());
            set_flash('error', "Terjadi kesalahan saat menyimpan tugas: " . $e->getMessage());
        }

        header("Location: index.php?view=$category");
        exit;

    // -------------------------------------------------------------
    // UPDATE TASK (CRUD: UPDATE + PHP FILE HANDLING)
    // -------------------------------------------------------------
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php?view=$currentView");
            exit;
        }

        $id = (int)($_POST['task_id'] ?? 0);
        $title = trim($_POST['task_name'] ?? '');
        $description = trim($_POST['task_description'] ?? '');
        $category = trim($_POST['task_category'] ?? 'personal');
        $dueDate = trim($_POST['task_deadline'] ?? '');
        $notifTime = !empty($_POST['task_notif']) ? $_POST['task_notif'] : null;
        $removeFile = isset($_POST['remove_existing_file']) && $_POST['remove_existing_file'] == '1';

        if ($id <= 0 || empty($title) || empty($dueDate)) {
            set_flash('error', 'Data tidak valid untuk memperbarui tugas.');
            header("Location: index.php?view=$currentView");
            exit;
        }

        if (!in_array($category, ['personal', 'shared'])) {
            $category = 'personal';
        }

        // Ambil data lama dari DB
        $stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
        $stmt->execute([$id]);
        $currentTask = $stmt->fetch();

        if (!$currentTask) {
            set_flash('error', 'Tugas tidak ditemukan.');
            header("Location: index.php?view=$currentView");
            exit;
        }

        $filePath = $currentTask['file_path'];
        $fileName = $currentTask['file_name'];
        $fileSize = $currentTask['file_size'];

        // Jika user meminta hapus file lama (PHP File Handling: unlink)
        if ($removeFile && !empty($filePath)) {
            $targetPath = UPLOAD_DIR . $filePath;
            if (file_exists($targetPath)) {
                unlink($targetPath); // Menghapus file fisik di server
                log_activity('DELETE_FILE', "File lampiran lama '{$filePath}' dihapus dari server.");
            }
            $filePath = null;
            $fileName = null;
            $fileSize = null;
        }

        // Jika ada unggahan file baru yang menggantikan file lama
        if (isset($_FILES['task_file']) && $_FILES['task_file']['error'] === UPLOAD_ERR_OK) {
            $uploadedTmp = $_FILES['task_file']['tmp_name'];
            $origName = basename($_FILES['task_file']['name']);
            $size = $_FILES['task_file']['size'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if ($size > MAX_FILE_SIZE) {
                set_flash('error', 'Ukuran file baru terlalu besar. Maksimum 5MB.');
                header("Location: index.php?view=$currentView&edit=$id");
                exit;
            }

            if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                set_flash('error', 'Format file tidak diizinkan.');
                header("Location: index.php?view=$currentView&edit=$id");
                exit;
            }

            // Hapus file lama jika ada
            if (!empty($filePath) && file_exists(UPLOAD_DIR . $filePath)) {
                unlink(UPLOAD_DIR . $filePath);
            }

            $uniqueName = 'task_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destPath = UPLOAD_DIR . $uniqueName;

            if (move_uploaded_file($uploadedTmp, $destPath)) {
                $filePath = $uniqueName;
                $fileName = $origName;
                $fileSize = $size;
                log_activity('REPLACE_FILE', "File baru '$origName' berhasil menggantikan file lama.");
            }
        }

        // Update database
        try {
            $stmt = $pdo->prepare("
                UPDATE todos 
                SET title = ?, description = ?, category = ?, due_date = ?, notif_time = ?, file_path = ?, file_name = ?, file_size = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $category, $dueDate, $notifTime, $filePath, $fileName, $fileSize, $id]);

            log_activity('UPDATE_TASK', "Tugas ID $id '{$title}' berhasil diperbarui.");
            set_flash('success', "Tugas '{$title}' berhasil diperbarui!");
        } catch (PDOException $e) {
            log_activity('ERROR', "Gagal update tugas ID $id: " . $e->getMessage());
            set_flash('error', "Gagal memperbarui tugas: " . $e->getMessage());
        }

        header("Location: index.php?view=$category");
        exit;

    // -------------------------------------------------------------
    // DELETE TASK (CRUD: DELETE + PHP FILE DELETION HANDLING)
    // -------------------------------------------------------------
    case 'delete':
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

        if ($id > 0) {
            // Ambil data file terkait
            $stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch();

            if ($task) {
                // PHP File Handling: Hapus file fisik jika ada
                if (!empty($task['file_path'])) {
                    $fileToDelete = UPLOAD_DIR . $task['file_path'];
                    if (file_exists($fileToDelete)) {
                        unlink($fileToDelete); // Menghapus file dari sistem operasi
                        log_activity('DELETE_FILE', "File '{$task['file_path']}' berhasil dihapus dari server saat task dihapus.");
                    }
                }

                // Hapus data dari database
                $delStmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
                $delStmt->execute([$id]);

                log_activity('DELETE_TASK', "Tugas '{$task['title']}' (ID: $id) berhasil dihapus.");
                set_flash('success', "Tugas '{$task['title']}' berhasil dihapus.");
            } else {
                set_flash('error', 'Tugas tidak ditemukan.');
            }
        }

        header("Location: index.php?view=$currentView");
        exit;

    // -------------------------------------------------------------
    // TOGGLE STATUS COMPLETED (CRUD: UPDATE STATUS)
    // -------------------------------------------------------------
    case 'toggle':
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT id, title, is_completed FROM todos WHERE id = ?");
            $stmt->execute([$id]);
            $task = $stmt->fetch();

            if ($task) {
                $newStatus = $task['is_completed'] ? 0 : 1;
                $updateStmt = $pdo->prepare("UPDATE todos SET is_completed = ? WHERE id = ?");
                $updateStmt->execute([$newStatus, $id]);

                $statusLabel = $newStatus ? 'Selesai (Completed)' : 'Belum Selesai (Pending)';
                log_activity('TOGGLE_STATUS', "Tugas '{$task['title']}' diubah menjadi {$statusLabel}.");
                set_flash('info', "Status tugas '{$task['title']}' diubah menjadi: {$statusLabel}.");
            }
        }

        header("Location: index.php?view=$currentView");
        exit;

    // -------------------------------------------------------------
    // RESET DUMMY DATA (UTILITY DARI data.sql)
    // -------------------------------------------------------------
    case 'reset_dummy':
        $sqlFile = __DIR__ . DIRECTORY_SEPARATOR . 'data.sql';
        if (file_exists($sqlFile)) {
            try {
                $sqlContent = file_get_contents($sqlFile);
                $pdo->exec($sqlContent);
                log_activity('RESET_DUMMY', "Database di-reset kembali sesuai dummy data dari data.sql.");
                set_flash('success', "Database berhasil di-reset kembali ke data awal dari data.sql!");
            } catch (PDOException $e) {
                set_flash('error', "Gagal me-reset database: " . $e->getMessage());
            }
        } else {
            set_flash('error', 'File data.sql tidak ditemukan.');
        }

        header("Location: index.php?view=$currentView");
        exit;

    default:
        header("Location: index.php?view=$currentView");
        exit;
}
