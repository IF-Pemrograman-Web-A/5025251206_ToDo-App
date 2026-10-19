## Identitas

- Nama: Rexa Matutu Harsaputra 
- NRP: 5025251206
- Kelas: PWEB A

## Deskripsi
Arsitektur Modular PHP

Memisahkan struktur aplikasi menjadi beberapa berkas modular (config.php, menubar.php, content.php, process.php, dan index.php) untuk menerapkan prinsip penulisan kode yang rapi dan mudah dirawat.

config.php mengelola koneksi ke basis data MySQL, menubar.php menyajikan komponen navigasi, content.php menampilkan antarmuka utama, serta process.php menangani seluruh pemrosesan logika backend.

Sistem Navigasi Multi-Kategori (Personal & Shared)

Menambahkan komponen sidebar dengan dua menu utama: Personal (tugas individu) dan Shared (tugas organisasi/kelompok).

Filter data dilakukan secara dinamis menggunakan parameter HTTP GET pada URL (?type=personal dan ?type=shared).

Struktur Basis Data MySQL (data.sql)

Merancang skema tabel todos yang menyimpan entri data berupa ID, tipe tugas, judul, deskripsi, tenggat waktu (due date), jadwal notifikasi, direktori gambar, serta status penyelesaian.

Menyediakan berkas data.sql yang memuat struktur tabel beserta dummy data untuk kebutuhan pengujian sistem.

Sistem Manipulasi Data (CRUD)

Create: Menambahkan entri tugas baru ke dalam basis data melalui formulir input.

Read: Menampilkan daftar tugas secara dinamis dari basis data sesuai kategori yang dipilih.

Update: Memperbarui isi tugas serta mengubah status penyelesaian (In Progress / Completed).

Delete: Menghapus data tugas dari basis data secara permanen.


## Preview
### Dekstop
<img width="1906" height="1022" alt="Screenshot 2026-10-19 185812" src="https://github.com/user-attachments/assets/c31150d5-c548-44f1-a562-f962f97e798e" />

