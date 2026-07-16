# SiWarga - Administrasi RT

Aplikasi full-stack untuk mengelola penghuni, riwayat hunian rumah, tagihan iuran, pembayaran, pengeluaran, dan laporan keuangan RT.

## Struktur proyek

- `backend/` - REST API Laravel dan autentikasi Laravel Sanctum.
- `frontend/` - aplikasi React dengan Vite.
- `dokumentasi/` - ERD, keputusan teknis, panduan instalasi, dan screenshot fitur.

## Teknologi

- PHP 8.2 dan Laravel 12
- React 19 dan Vite 8
- MySQL 8

## Menjalankan proyek

Panduan instalasi lengkap tersedia di [`dokumentasi/PANDUAN_INSTALASI.md`](dokumentasi/PANDUAN_INSTALASI.md).

Dokumentasi endpoint tersedia di [`dokumentasi/API.md`](dokumentasi/API.md).

Rangkuman screenshot setiap fitur tersedia di [`dokumentasi/SCREENSHOTS.md`](dokumentasi/SCREENSHOTS.md).

ERD tersedia dalam tiga format:

- [`dokumentasi/ERD.png`](dokumentasi/ERD.png) untuk dilihat atau dikumpulkan.
- [`dokumentasi/ERD.drawio`](dokumentasi/ERD.drawio) untuk diedit melalui draw.io/diagrams.net.
- [`dokumentasi/ERD.md`](dokumentasi/ERD.md) untuk dokumentasi Mermaid di repository.

Secara ringkas, backend dijalankan dari folder `backend` dengan `php artisan serve`, sedangkan frontend dijalankan dari folder `frontend` dengan `npm run dev`.
