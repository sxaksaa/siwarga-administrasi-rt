# Hasil Uji Instalasi Bersih

Pengujian dilakukan pada 16 Juli 2026 dengan mengikuti `PANDUAN_INSTALASI.md` dari salinan baru repository GitHub:

```text
https://github.com/sxaksaa/siwarga-administrasi-rt.git
```

Clone awal menggunakan commit `9eb0c66`. Sebelum instalasi, clone tersebut dipastikan tidak memiliki `backend/vendor`, `frontend/node_modules`, `backend/.env`, maupun `frontend/.env`.

## Lingkungan pengujian

- Windows
- PHP 8.2.29
- Composer 2.8.11
- MySQL 8.0.30
- Node.js 24.11.1
- npm 11.6.2
- Git 2.55.0

## Hasil pengujian

| Tahap | Hasil |
|---|---|
| Clone repository dari GitHub | Lulus |
| `composer install` dari lockfile | Lulus |
| Pembuatan `.env` dan application key | Lulus |
| Migration dan seeder awal | Lulus |
| Pembuatan storage link | Lulus |
| `DemoDataSeeder` | Lulus |
| Pengujian backend | 25 tes lulus, 121 assertion |
| Laravel Pint | Lulus |
| Validasi `composer.json` dan lockfile | Lulus |
| `npm install` | Lulus, 0 kerentanan |
| Lint frontend | Lulus |
| Build produksi frontend | Lulus, 2.406 modul diproses |
| Menjalankan backend dan frontend | Lulus |
| Login, membaca pengguna aktif, dan logout | Lulus |

Database dan akun MySQL khusus verifikasi dibuat terpisah dari database pengembangan, kemudian dibersihkan setelah pengujian selesai.

## Temuan selama pengujian

Pengujian menemukan bahwa `frontend/.env` belum diabaikan Git. Pola tersebut kemudian ditambahkan ke `.gitignore` agar konfigurasi lokal dan nilai lingkungan tidak ikut terunggah.

## Kesimpulan

Panduan instalasi dapat digunakan pada salinan proyek yang benar-benar bersih. Backend, frontend, database, pengujian otomatis, build produksi, dan alur autentikasi dasar berhasil dijalankan.
