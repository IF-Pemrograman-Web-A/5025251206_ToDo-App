-- Database: todo_db
-- Structure and dummy data for Citadel Todo App (Tugas 4 PWEB)

CREATE DATABASE IF NOT EXISTS `todo_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `todo_db`;

-- Drop table if exists to allow clean re-import
DROP TABLE IF EXISTS `todos`;

-- Table structure for `todos`
CREATE TABLE `todos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `category` ENUM('personal', 'shared') NOT NULL DEFAULT 'personal',
  `due_date` DATE NOT NULL,
  `notif_time` DATETIME NULL,
  `file_path` VARCHAR(255) NULL,
  `file_name` VARCHAR(255) NULL,
  `file_size` INT NULL,
  `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dummy Data
INSERT INTO `todos` (`id`, `title`, `description`, `category`, `due_date`, `notif_time`, `file_path`, `file_name`, `file_size`, `is_completed`, `created_at`) VALUES
-- Personal Todos
(1, 'Menyelesaikan Tugas 4 PWEB (PHP & MySQL)', 'Melakukan migrasi sistem todo HTML/JS ke modular PHP, integrasi database MySQL, implementasi menu bar Personal & Shared, serta file handling.', 'personal', '2026-10-25', '2026-10-25 09:00:00', NULL, NULL, NULL, 0, '2026-10-19 08:00:00'),
(2, 'Review Materi Ujian Pemrograman Web', 'Mempelajari kembali konsep PHP OOP, PDO prepared statements, session handling, dan keamanan web (SQL Injection & XSS prevention).', 'personal', '2026-10-28', '2026-10-28 14:00:00', NULL, NULL, NULL, 0, '2026-10-19 09:30:00'),
(3, 'Membeli Buku Referensi Web Architecture & Kopi', 'Membeli perlengkapan referensi belajar dan persediaan kopi untuk sesi coding marathon tugas kuliah.', 'personal', '2026-10-20', NULL, NULL, NULL, NULL, 1, '2026-10-18 10:15:00'),

-- Shared / Organizational Todos
(4, 'Eksplorasi Ruang Utama Iron Citadel', 'Membagi tim ekspedisi ke dalam 3 sektor: pemetaan rute, inventarisasi artefak bersejarah, dan analisis pertahanan benteng citadel.', 'shared', '2026-10-30', '2026-10-30 10:00:00', NULL, NULL, NULL, 0, '2026-10-19 11:00:00'),
(5, 'Desain Arsitektur Database Organisasi', 'Diskusi bersama tim database mengenai relasi tabel personal vs shared, optimasi index pada kolom category dan due_date, serta strategi backup berkala.', 'shared', '2026-10-26', '2026-10-26 13:30:00', NULL, NULL, NULL, 0, '2026-10-19 12:45:00'),
(6, 'Audit Keamanan & File Handling Upload System', 'Melakukan security check terhadap upload directory, verifikasi validasi ekstensi file server-side, dan penanganan unlink file saat penghapusan task.', 'shared', '2026-10-22', NULL, NULL, NULL, NULL, 1, '2026-10-18 15:20:00');
