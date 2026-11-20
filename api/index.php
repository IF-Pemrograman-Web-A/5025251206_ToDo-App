<?php
/**
 * Citadel Todo RESTful API Documentation & Portal Entry [E06]
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

echo json_encode([
    'name'        => 'Citadel Todo RESTful API [E06: Portal to All Dimensions]',
    'version'     => '1.0.0',
    'status'      => 'active',
    'base_url'    => 'http://localhost/sekolah_sj/PWEB/api/todos.php',
    'auth' => [
        'type'        => 'API Key',
        'header_name' => 'X-API-KEY',
        'key_sample'  => 'citadel-secret-key-2026',
        'bearer_opt'  => 'Authorization: Bearer citadel-secret-key-2026',
        'query_param' => '?api_key=citadel-secret-key-2026'
    ],
    'endpoints' => [
        [
            'method'      => 'GET',
            'endpoint'    => '/api/todos.php',
            'description' => 'Mendapatkan seluruh daftar tugas (mendukung ?category=personal|shared dan ?is_completed=0|1)',
            'success_code'=> 200
        ],
        [
            'method'      => 'GET',
            'endpoint'    => '/api/todos.php?id={id}',
            'description' => 'Mendapatkan detail 1 tugas berdasarkan ID',
            'success_code'=> 200,
            'error_codes' => [400, 401, 404]
        ],
        [
            'method'      => 'POST',
            'endpoint'    => '/api/todos.php',
            'description' => 'Membuat tugas baru (body JSON: title, description, category, due_date)',
            'success_code'=> 201,
            'error_codes' => [400, 401, 500]
        ],
        [
            'method'      => 'PUT/PATCH',
            'endpoint'    => '/api/todos.php?id={id}',
            'description' => 'Memperbarui data tugas atau toggle status selesai',
            'success_code'=> 200,
            'error_codes' => [400, 401, 404, 500]
        ],
        [
            'method'      => 'DELETE',
            'endpoint'    => '/api/todos.php?id={id}',
            'description' => 'Menghapus tugas berdasarkan ID beserta file lampiran fisiknya',
            'success_code'=> 200,
            'error_codes' => [400, 401, 404, 500]
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
