<?php
// config.php - Konfigurasi Database, File Handling, dan Helper System

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'todo_db');

// Konfigurasi File Handling
define('UPLOAD_DIR', __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('LOG_FILE', UPLOAD_DIR . 'activity_log.txt');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'doc', 'docx', 'zip']);

// Pastikan folder uploads tersedia (PHP Directory Handling)
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Inisialisasi Koneksi PDO
$pdo = null;
try {
    // Coba koneksi langsung ke database todo_db
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Jika database belum dibuat (SQLSTATE 1049 Unknown database), coba inisialisasi otomatis dari data.sql
    try {
        $rootDsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Jalankan skrip data.sql jika file tersedia
        $sqlFile = __DIR__ . DIRECTORY_SEPARATOR . 'data.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            $pdo->exec($sqlContent);
            log_activity('INIT_DB', 'Database dan tabel otomatis diinisialisasi dari data.sql');
        }
    } catch (PDOException $ex) {
        die("Koneksi Database Gagal: " . htmlspecialchars($ex->getMessage()) . "<br><small>Pastikan MySQL di XAMPP Control Panel sudah dinyalakan.</small>");
    }
}

// Helper: Flash Message untuk notifikasi feedback pengguna
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info'
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Helper: Sanitasi input
function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

// Helper: PHP File Handling untuk Activity Log (fopen, fwrite, fclose)
function log_activity($action, $details) {
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] [ACTION: $action] $details" . PHP_EOL;
    
    // File handling: Membuka file dalam mode append ('a')
    $fileHandle = @fopen(LOG_FILE, 'a');
    if ($fileHandle) {
        fwrite($fileHandle, $logLine);
        fclose($fileHandle);
    }
}

// Helper: Format tanggal ramah pengguna
function format_date($dateStr) {
    if (!$dateStr) return '-';
    $time = strtotime($dateStr);
    return date('d M Y', $time);
}

function format_datetime($datetimeStr) {
    if (!$datetimeStr) return 'None';
    $time = strtotime($datetimeStr);
    return date('d M Y H:i', $time);
}
