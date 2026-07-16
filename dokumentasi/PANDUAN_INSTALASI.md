# Panduan Instalasi Administrasi RT

Panduan ini menggunakan instalasi lokal tanpa Docker sesuai ketentuan Skill Fit Test.

## 1. Kebutuhan sistem

Pastikan komputer memiliki:

- PHP 8.2 atau lebih baru dengan ekstensi `bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, dan `zip`.
- Composer 2.
- MySQL 8.
- Node.js 20 atau lebih baru dan npm.
- Git.

Periksa instalasi:

```powershell
php --version
composer --version
node --version
npm --version
git --version
```

## 2. Mengambil source code

```powershell
git clone https://github.com/sxaksaa/siwarga-administrasi-rt.git administrasi-rt
cd administrasi-rt
```

## 3. Membuat database MySQL

Buka MySQL atau phpMyAdmin, kemudian buat database utama berikut:

```sql
CREATE DATABASE administrasi_rt
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Database `administrasi_rt_test` tidak diperlukan untuk menjalankan aplikasi. Database tersebut hanya dibuat jika ingin menjalankan pengujian backend pada langkah 7.

## 4. Memasang backend Laravel

Masuk ke folder backend:

```powershell
cd backend
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Buka `backend/.env`, lalu sesuaikan koneksi MySQL:

```dotenv
APP_NAME="Administrasi RT"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=administrasi_rt
DB_USERNAME=root
DB_PASSWORD=password_mysql
```

Ganti `password_mysql` dengan password MySQL pada komputer tersebut. Jangan mengunggah file `.env` ke Git.

Atur akun administrator awal pada file yang sama:

```dotenv
ADMIN_NAME="Administrator RT"
ADMIN_EMAIL=admin@administrasirt.test
ADMIN_PASSWORD=AdminRT123!
```

Untuk penggunaan selain demonstrasi, ganti password awal dengan password yang kuat.

Jalankan migration, seeder, dan tautan penyimpanan bukti pengeluaran:

```powershell
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear
```

Seeder membuat:

- 20 rumah dengan nomor `A-01` sampai `A-20`.
- Iuran Satpam sebesar Rp100.000.
- Iuran Kebersihan sebesar Rp15.000.
- Satu akun administrator.

Untuk mengisi data fiktif yang siap digunakan saat demonstrasi, jalankan secara opsional:

```powershell
php artisan db:seed --class=DemoDataSeeder
```

Perintah tersebut tidak dijalankan otomatis pada instalasi biasa dan tidak menggunakan identitas atau foto KTP asli.

Jalankan backend:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Biarkan terminal ini tetap terbuka.

## 5. Memasang frontend React

Buka terminal PowerShell baru dari folder utama proyek:

```powershell
cd frontend
npm install
Copy-Item .env.example .env
npm run dev -- --host=127.0.0.1 --port=5173
```

Isi `frontend/.env` harus mengarah ke backend:

```dotenv
VITE_API_URL=http://127.0.0.1:8000/api
```

Biarkan terminal frontend tetap terbuka.

## 6. Membuka aplikasi

Buka alamat berikut di browser:

```text
http://127.0.0.1:5173
```

Login menggunakan akun administrator yang diatur pada `backend/.env`. Nilai awalnya:

```text
Email    : admin@administrasirt.test
Password : AdminRT123!
```

## 7. Menjalankan pengujian backend (opsional)

Bagian ini tidak diperlukan untuk menjalankan aplikasi. Jika ingin menjalankan pengujian otomatis, buat database terpisah agar data aplikasi utama tidak terhapus:

```sql
CREATE DATABASE administrasi_rt_test
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Kemudian, dari folder `backend`:

```powershell
Copy-Item .env.testing.example .env.testing
php artisan key:generate --env=testing
```

File `.env.testing.example` sudah mengarah ke `administrasi_rt_test`. Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` pada `.env.testing` dengan akun MySQL di komputer yang digunakan.

Kemudian jalankan:

```powershell
php artisan test
php vendor\bin\pint --test
```

## 8. Membuat build frontend produksi

Dari folder `frontend`:

```powershell
npm run build
```

Hasil build berada di `frontend/dist`.

## 9. Urutan penggunaan awal

1. Login sebagai administrator.
2. Tambahkan atau periksa data penghuni.
3. Buka menu Rumah dan tempatkan penghuni pada rumah kosong.
4. Buka menu Tagihan dan buat tagihan untuk periode yang dipilih.
5. Buka menu Pembayaran untuk mencatat pelunasan iuran.
6. Catat biaya RT melalui menu Pengeluaran.
7. Periksa grafik dan detail transaksi melalui menu Laporan.

## 10. Pemecahan masalah

### `Access denied for user 'root'`

Periksa `DB_USERNAME` dan `DB_PASSWORD` di `backend/.env`, lalu jalankan:

```powershell
php artisan config:clear
```

### `Unknown database 'administrasi_rt'`

Buat database pada langkah 3 sebelum menjalankan migration.

### Port 8000 atau 5173 sudah digunakan

Hentikan proses lama dengan `Ctrl+C`, atau gunakan port lain. Jika port backend berubah, perbarui `VITE_API_URL` pada `frontend/.env`.

### Foto atau bukti tidak tampil

Jalankan:

```powershell
php artisan storage:link
```

Foto KTP disimpan secara privat dan hanya dapat dibuka melalui API setelah administrator login. Bukti pengeluaran menggunakan penyimpanan publik aplikasi.

### Perubahan `.env` belum terbaca

```powershell
php artisan optimize:clear
```
