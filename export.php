<?php
// export.php - Demonstrasi PHP File Handling (fopen, fputcsv, fwrite, fclose, readfile)
// Mengekspor daftar tugas ke file format CSV atau TXT Report

require_once __DIR__ . '/config.php';

$type = $_GET['type'] ?? 'csv';
$view = $_GET['view'] ?? 'all';

// Query data sesuai filter
$sql = "SELECT id, title, description, category, due_date, notif_time, file_name, is_completed, created_at FROM todos";
$params = [];

if (in_array($view, ['personal', 'shared'])) {
    $sql .= " WHERE category = ?";
    $params[] = $view;
}
$sql .= " ORDER BY category ASC, is_completed ASC, due_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Pastikan subfolder reports ada (PHP Directory Handling)
$reportsDir = UPLOAD_DIR . 'reports' . DIRECTORY_SEPARATOR;
if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0755, true);
}

$dateStamp = date('Ymd_His');

if ($type === 'csv') {
    // -------------------------------------------------------------
    // EXPORT TO CSV MENGGUNAKAN PHP FILE HANDLING (fopen, fputcsv, fclose)
    // -------------------------------------------------------------
    $fileName = "citadel_todos_{$view}_{$dateStamp}.csv";
    $filePath = $reportsDir . $fileName;

    // Buka file untuk penulisan (mode 'w')
    $fileHandle = fopen($filePath, 'w');
    if ($fileHandle === false) {
        die("Gagal membuat file export di server.");
    }

    // Tulis UTF-8 BOM agar Excel menampilkan aksen karakter dengan benar
    fprintf($fileHandle, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header Kolom CSV
    fputcsv($fileHandle, ['ID', 'Task Name', 'Category', 'Status', 'Due Date', 'Notification Time', 'Attachment', 'Description', 'Created At']);

    // Loop data dan tulis setiap baris dengan fputcsv()
    foreach ($tasks as $row) {
        $status = $row['is_completed'] ? 'Completed' : 'Pending';
        fputcsv($fileHandle, [
            $row['id'],
            $row['title'],
            ucfirst($row['category']),
            $status,
            $row['due_date'],
            $row['notif_time'] ?? '-',
            $row['file_name'] ?? 'None',
            $row['description'],
            $row['created_at']
        ]);
    }

    // Tutup file stream
    fclose($fileHandle);

    // Catat ke log file handling
    log_activity('EXPORT_CSV', "Berhasil mengekspor " . count($tasks) . " tugas ke file CSV ($fileName)");

    // Kirim file ke browser untuk diunduh langsung
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;

} elseif ($type === 'txt') {
    // -------------------------------------------------------------
    // EXPORT TO TXT REPORT MENGGUNAKAN PHP FILE HANDLING (fopen, fwrite, fclose)
    // -------------------------------------------------------------
    $fileName = "citadel_todos_report_{$view}_{$dateStamp}.txt";
    $filePath = $reportsDir . $fileName;

    $fileHandle = fopen($filePath, 'w');
    if ($fileHandle === false) {
        die("Gagal membuat file report.");
    }

    $separator = str_repeat("=", 75) . PHP_EOL;
    $subSeparator = str_repeat("-", 75) . PHP_EOL;

    fwrite($fileHandle, $separator);
    fwrite($fileHandle, "  CITADEL TODO LIST - SUMMARY REPORT (" . strtoupper($view) . ")" . PHP_EOL);
    fwrite($fileHandle, "  Generated at: " . date('d F Y, H:i:s') . PHP_EOL);
    fwrite($fileHandle, "  Total Tasks : " . count($tasks) . PHP_EOL);
    fwrite($fileHandle, $separator . PHP_EOL);

    foreach ($tasks as $index => $row) {
        $num = $index + 1;
        $status = $row['is_completed'] ? '[COMPLETED]' : '[IN PROGRESS]';
        $txt = "[$num] {$status} {$row['title']}" . PHP_EOL;
        $txt .= "    Category     : " . ucfirst($row['category']) . PHP_EOL;
        $txt .= "    Due Date     : " . $row['due_date'] . PHP_EOL;
        $txt .= "    Notification : " . ($row['notif_time'] ?? 'None') . PHP_EOL;
        $txt .= "    Attachment   : " . ($row['file_name'] ?? 'None') . PHP_EOL;
        $txt .= "    Description  : " . $row['description'] . PHP_EOL;
        $txt .= $subSeparator;
        fwrite($fileHandle, $txt);
    }

    fclose($fileHandle);

    log_activity('EXPORT_TXT', "Berhasil membuat file text report ($fileName)");

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;

} elseif ($type === 'log') {
    // Tampilkan isi log file jika diminta
    if (file_exists(LOG_FILE)) {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: inline; filename="activity_log.txt"');
        readfile(LOG_FILE);
        exit;
    } else {
        echo "Log file belum tersedia.";
        exit;
    }
}
