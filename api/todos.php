<?php
/**
 * Citadel Todo RESTful API [E06: Portal to All Dimensions]
 * 
 * Endpoints:
 * - GET    /api/todos.php          : Mendapatkan semua todos (bisa difilter ?category=personal|shared)
 * - GET    /api/todos.php?id={id}  : Mendapatkan detail todo spesifik
 * - POST   /api/todos.php          : Membuat todo baru (Return 201 Created)
 * - PUT    /api/todos.php?id={id}  : Memperbarui data todo (Return 200 OK)
 * - PATCH  /api/todos.php?id={id}  : Memperbarui sebagian field / toggle status todo (Return 200 OK)
 * - DELETE /api/todos.php?id={id}  : Menghapus todo (Return 200 OK)
 * 
 * Authentication:
 * - Header: 'X-API-KEY: citadel-secret-key-2026'
 *   atau 'Authorization: Bearer citadel-secret-key-2026'
 *   atau Query Param: '?api_key=citadel-secret-key-2026'
 */

// Header Response REST API & CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-KEY");

// Tangani Preflight Request CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Helper function untuk mengirim respon JSON standar
function send_json_response($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// -------------------------------------------------------------
// 1. AUTENTIKASI API KEY (Kriteria 4)
// -------------------------------------------------------------
define('VALID_API_KEY', 'citadel-secret-key-2026');

function check_authentication() {
    $providedKey = null;

    // 1. Cek dari Header X-API-KEY
    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        $providedKey = trim($_SERVER['HTTP_X_API_KEY']);
    }
    // 2. Cek dari Header Authorization: Bearer <key>
    elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(\S+)/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $providedKey = trim($matches[1]);
        }
    }
    // 3. Fallback Apache headers jika HTTP_AUTHORIZATION di-strip oleh server
    elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (!empty($headers['X-API-KEY'])) {
            $providedKey = trim($headers['X-API-KEY']);
        } elseif (!empty($headers['x-api-key'])) {
            $providedKey = trim($headers['x-api-key']);
        } elseif (!empty($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(\S+)/i', $headers['Authorization'], $matches)) {
                $providedKey = trim($matches[1]);
            }
        }
    }
    // 4. Cek dari Query Param (?api_key=...)
    elseif (!empty($_GET['api_key'])) {
        $providedKey = trim($_GET['api_key']);
    }

    // Validasi API Key
    if (empty($providedKey)) {
        send_json_response(401, [
            'status'  => 'error',
            'code'    => 401,
            'error'   => 'Unauthorized',
            'message' => "Autentikasi gagal. Harap sertakan header 'X-API-KEY: citadel-secret-key-2026' atau 'Authorization: Bearer citadel-secret-key-2026'."
        ]);
    }

    if ($providedKey !== VALID_API_KEY) {
        send_json_response(401, [
            'status'  => 'error',
            'code'    => 401,
            'error'   => 'Invalid API Key',
            'message' => 'API Key yang Anda masukkan salah atau tidak valid.'
        ]);
    }
}

// Jalankan autentikasi terlebih dahulu
check_authentication();

// -------------------------------------------------------------
// 2. INISIALISASI DATABASE & CONFIG
// -------------------------------------------------------------
define('IS_API', true);

try {
    require_once __DIR__ . '/../config.php';
} catch (PDOException $e) {
    send_json_response(500, [
        'status'  => 'error',
        'code'    => 500,
        'error'   => 'Internal Server Error',
        'message' => 'Koneksi ke database gagal. Pastikan MySQL di XAMPP Control Panel sudah dinyalakan.',
        'details' => $e->getMessage()
    ]);
}

// -------------------------------------------------------------
// 2. PARSING REQUEST & PENENTUAN ID
// -------------------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'];

// Baca data JSON body jika ada
$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput, true) ?? [];

// Jika tidak ada JSON body, gunakan form data ($_POST)
if (empty($body) && !empty($_POST)) {
    $body = $_POST;
}

// Ambil ID dari Query String (?id=...) atau dari PATH_INFO (/api/todos.php/5)
$id = null;
if (!empty($_GET['id'])) {
    $id = $_GET['id'];
} elseif (!empty($_SERVER['PATH_INFO'])) {
    $pathParts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
    if (!empty($pathParts[0]) && is_numeric($pathParts[0])) {
        $id = (int)$pathParts[0];
    }
}

// -------------------------------------------------------------
// 3. ROUTING RESTFUL API BERDASARKAN METHOD (Kriteria 1, 2, 3)
// -------------------------------------------------------------
try {
    switch ($method) {
        // =========================================================
        // GET: Mengambil satu atau semua todo
        // =========================================================
        case 'GET':
            if ($id !== null) {
                // GET DETAIL TODO BY ID
                if (!is_numeric($id) || (int)$id <= 0) {
                    send_json_response(400, [
                        'status'  => 'error',
                        'code'    => 400,
                        'error'   => 'Bad Request',
                        'message' => "Parameter ID harus berupa angka bilangan bulat positif. Diberikan: '$id'"
                    ]);
                }

                $stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
                $stmt->execute([(int)$id]);
                $todo = $stmt->fetch();

                if (!$todo) {
                    send_json_response(404, [
                        'status'  => 'error',
                        'code'    => 404,
                        'error'   => 'Not Found',
                        'message' => "Todo dengan ID $id tidak ditemukan di dimensi ini."
                    ]);
                }

                send_json_response(200, [
                    'status'  => 'success',
                    'code'    => 200,
                    'message' => 'Detail todo berhasil ditemukan.',
                    'data'    => $todo
                ]);
            } else {
                // GET ALL TODOS (dengan filter opsional category & is_completed)
                $category = $_GET['category'] ?? null;
                $isCompleted = isset($_GET['is_completed']) ? (int)$_GET['is_completed'] : null;

                $query = "SELECT * FROM todos WHERE 1=1";
                $params = [];

                if ($category !== null && in_array(strtolower($category), ['personal', 'shared'])) {
                    $query .= " AND category = ?";
                    $params[] = strtolower($category);
                }

                if ($isCompleted !== null && in_array($isCompleted, [0, 1])) {
                    $query .= " AND is_completed = ?";
                    $params[] = $isCompleted;
                }

                $query .= " ORDER BY is_completed ASC, due_date ASC, id DESC";

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $todos = $stmt->fetchAll();

                send_json_response(200, [
                    'status'  => 'success',
                    'code'    => 200,
                    'message' => 'Daftar todos berhasil diambil.',
                    'count'   => count($todos),
                    'filters' => [
                        'category'     => $category ?: 'all',
                        'is_completed' => $isCompleted !== null ? $isCompleted : 'all'
                    ],
                    'data'    => $todos
                ]);
            }
            break;

        // =========================================================
        // POST: Membuat todo baru (Return 201 Created)
        // =========================================================
        case 'POST':
            $title       = trim($body['title'] ?? '');
            $description = trim($body['description'] ?? '');
            $category    = strtolower(trim($body['category'] ?? 'personal'));
            $dueDate     = trim($body['due_date'] ?? '');
            $notifTime   = !empty($body['notif_time']) ? trim($body['notif_time']) : null;

            // Validasi Input (Kriteria 3: Wrong response code 400 Bad Request)
            $errors = [];
            if (empty($title)) {
                $errors[] = "Field 'title' wajib diisi dan tidak boleh kosong.";
            }
            if (empty($dueDate)) {
                $errors[] = "Field 'due_date' wajib diisi.";
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) || !strtotime($dueDate)) {
                $errors[] = "Format 'due_date' tidak valid. Gunakan format YYYY-MM-DD (contoh: 2026-12-31).";
            }

            if (!in_array($category, ['personal', 'shared'])) {
                $errors[] = "Field 'category' harus bernilai 'personal' atau 'shared'.";
            }

            if ($notifTime !== null && !strtotime($notifTime)) {
                $errors[] = "Format 'notif_time' tidak valid. Gunakan format datetime (contoh: 2026-12-31 09:00:00).";
            }

            if (!empty($errors)) {
                send_json_response(400, [
                    'status'  => 'error',
                    'code'    => 400,
                    'error'   => 'Bad Request',
                    'message' => 'Validasi data todo gagal.',
                    'details' => $errors
                ]);
            }

            // Simpan ke database
            $stmt = $pdo->prepare("
                INSERT INTO todos (title, description, category, due_date, notif_time, is_completed)
                VALUES (?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$title, $description, $category, $dueDate, $notifTime]);
            $newId = (int)$pdo->lastInsertId();

            // Ambil record yang baru dibuat
            $newStmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
            $newStmt->execute([$newId]);
            $createdTodo = $newStmt->fetch();

            log_activity('API_CREATE_TASK', "API POST membuat tugas ID $newId '$title'");

            send_json_response(201, [
                'status'  => 'success',
                'code'    => 201,
                'message' => 'Todo baru berhasil dibuat.',
                'data'    => $createdTodo
            ]);
            break;

        // =========================================================
        // PUT / PATCH: Memperbarui data todo (Return 200 OK)
        // =========================================================
        case 'PUT':
        case 'PATCH':
            if ($id === null || !is_numeric($id) || (int)$id <= 0) {
                send_json_response(400, [
                    'status'  => 'error',
                    'code'    => 400,
                    'error'   => 'Bad Request',
                    'message' => "Parameter ID todo wajib disertakan dalam URL untuk melakukan pembaruan (contoh: ?id=1)."
                ]);
            }

            // Cek apakah todo ada di database
            $checkStmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
            $checkStmt->execute([(int)$id]);
            $existing = $checkStmt->fetch();

            if (!$existing) {
                send_json_response(404, [
                    'status'  => 'error',
                    'code'    => 404,
                    'error'   => 'Not Found',
                    'message' => "Gagal memperbarui: Todo dengan ID $id tidak ditemukan."
                ]);
            }

            // Siapkan field yang akan diupdate
            $title       = array_key_exists('title', $body) ? trim($body['title']) : $existing['title'];
            $description = array_key_exists('description', $body) ? trim($body['description']) : $existing['description'];
            $category    = array_key_exists('category', $body) ? strtolower(trim($body['category'])) : $existing['category'];
            $dueDate     = array_key_exists('due_date', $body) ? trim($body['due_date']) : $existing['due_date'];
            $notifTime   = array_key_exists('notif_time', $body) ? trim($body['notif_time']) : $existing['notif_time'];
            $isCompleted = array_key_exists('is_completed', $body) ? (int)$body['is_completed'] : (int)$existing['is_completed'];

            // Validasi field yang diperbarui
            $errors = [];
            if (empty($title)) {
                $errors[] = "Field 'title' tidak boleh kosong.";
            }
            if (empty($dueDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) || !strtotime($dueDate)) {
                $errors[] = "Format 'due_date' harus valid (YYYY-MM-DD).";
            }
            if (!in_array($category, ['personal', 'shared'])) {
                $errors[] = "Field 'category' harus bernilai 'personal' atau 'shared'.";
            }
            if (!in_array($isCompleted, [0, 1])) {
                $errors[] = "Field 'is_completed' harus bernilai 0 (pending) atau 1 (selesai).";
            }

            if (!empty($errors)) {
                send_json_response(400, [
                    'status'  => 'error',
                    'code'    => 400,
                    'error'   => 'Bad Request',
                    'message' => 'Validasi pembaruan data todo gagal.',
                    'details' => $errors
                ]);
            }

            $updateStmt = $pdo->prepare("
                UPDATE todos 
                SET title = ?, description = ?, category = ?, due_date = ?, notif_time = ?, is_completed = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$title, $description, $category, $dueDate, $notifTime, $isCompleted, (int)$id]);

            // Ambil record setelah diupdate
            $resStmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
            $resStmt->execute([(int)$id]);
            $updatedTodo = $resStmt->fetch();

            log_activity('API_UPDATE_TASK', "API $method memperbarui tugas ID $id '$title'");

            send_json_response(200, [
                'status'  => 'success',
                'code'    => 200,
                'message' => "Todo dengan ID $id berhasil diperbarui.",
                'data'    => $updatedTodo
            ]);
            break;

        // =========================================================
        // DELETE: Menghapus todo (Return 200 OK)
        // =========================================================
        case 'DELETE':
            if ($id === null || !is_numeric($id) || (int)$id <= 0) {
                send_json_response(400, [
                    'status'  => 'error',
                    'code'    => 400,
                    'error'   => 'Bad Request',
                    'message' => "Parameter ID todo wajib disertakan dalam URL untuk melakukan penghapusan (contoh: ?id=1)."
                ]);
            }

            $stmt = $pdo->prepare("SELECT * FROM todos WHERE id = ?");
            $stmt->execute([(int)$id]);
            $todoToDelete = $stmt->fetch();

            if (!$todoToDelete) {
                send_json_response(404, [
                    'status'  => 'error',
                    'code'    => 404,
                    'error'   => 'Not Found',
                    'message' => "Gagal menghapus: Todo dengan ID $id tidak ditemukan."
                ]);
            }

            // Hapus file lampiran jika ada
            if (!empty($todoToDelete['file_path'])) {
                $targetFile = UPLOAD_DIR . $todoToDelete['file_path'];
                if (file_exists($targetFile)) {
                    @unlink($targetFile);
                }
            }

            $delStmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
            $delStmt->execute([(int)$id]);

            log_activity('API_DELETE_TASK', "API DELETE menghapus tugas ID $id '{$todoToDelete['title']}'");

            send_json_response(200, [
                'status'     => 'success',
                'code'       => 200,
                'message'    => "Todo dengan ID $id ('{$todoToDelete['title']}') berhasil dihapus.",
                'deleted_id' => (int)$id
            ]);
            break;

        // =========================================================
        // Method Tidak Didukung (Kriteria 3: 405 Method Not Allowed)
        // =========================================================
        default:
            send_json_response(405, [
                'status'  => 'error',
                'code'    => 405,
                'error'   => 'Method Not Allowed',
                'message' => "Metode HTTP '$method' tidak diizinkan pada endpoint ini. Metode yang didukung: GET, POST, PUT, PATCH, DELETE."
            ]);
            break;
    }
} catch (PDOException $e) {
    // Database Exception (Kriteria 3: 500 Internal Server Error)
    log_activity('API_ERROR', "Database error pada API: " . $e->getMessage());
    send_json_response(500, [
        'status'  => 'error',
        'code'    => 500,
        'error'   => 'Internal Server Error',
        'message' => 'Terjadi kesalahan sistem saat mengakses basis data portal.',
        'details' => $e->getMessage()
    ]);
}
