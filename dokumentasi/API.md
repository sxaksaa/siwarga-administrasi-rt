# Dokumentasi REST API

Base URL lokal:

```text
http://127.0.0.1:8000/api
```

Seluruh respons menggunakan JSON, kecuali respons foto KTP. Endpoint selain login membutuhkan header:

```http
Authorization: Bearer TOKEN_LOGIN
Accept: application/json
```

## Autentikasi

| Method | Endpoint | Keterangan |
|---|---|---|
| `POST` | `/login` | Login administrator dan memperoleh token. |
| `GET` | `/pengguna` | Mengambil pengguna yang sedang login. |
| `POST` | `/logout` | Menghapus token aktif. |

Payload login:

```json
{
  "email": "admin@gmail.com",
  "password": "admin"
}
```

## Penghuni

| Method | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/penghuni` | Daftar penghuni. Mendukung `cari`, `jenis_penghuni`, dan `per_page`. |
| `POST` | `/penghuni` | Menambah penghuni menggunakan `multipart/form-data`. |
| `GET` | `/penghuni/{id}` | Detail penghuni. |
| `PATCH` | `/penghuni/{id}` | Memperbarui penghuni. Untuk foto gunakan `POST` dengan `_method=PATCH`. |
| `GET` | `/penghuni/{id}/foto-ktp` | Membuka foto KTP privat setelah autentikasi. |

Field penghuni:

- `nama_lengkap`: teks, wajib; kapitalisasi setiap kata dan spasi ganda dirapikan otomatis.
- `foto_ktp`: JPG, PNG, atau WebP, maksimal 5 MB; wajib saat membuat data.
- `jenis_penghuni`: `tetap` atau `kontrak`.
- `nomor_telepon`: wajib berisi 10-15 digit; boleh memakai awalan `+`, spasi, tanda kurung, dan tanda hubung, maksimal 20 karakter.
- `sudah_menikah`: boolean.

## Rumah dan riwayat hunian

| Method | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/rumah` | Daftar rumah. Mendukung `cari`, `status`, dan `per_page`. |
| `POST` | `/rumah` | Menambah rumah. |
| `GET` | `/rumah/{id}` | Detail rumah beserta penghuni aktif dan riwayat. |
| `PATCH` | `/rumah/{id}` | Memperbarui rumah. |
| `POST` | `/rumah/{id}/hunian` | Menempatkan penghuni. |
| `PATCH` | `/rumah/{id}/hunian/{hunianId}/selesai` | Mengakhiri masa tinggal. |

Payload penempatan:

```json
{
  "penghuni_id": 1,
  "mulai_tinggal": "2026-07-01",
  "catatan": "Penghuni tetap"
}
```

Sistem menolak hunian aktif ganda, rentang historis yang tumpang tindih, dan tanggal masa depan. `selesai_tinggal` dianggap sebagai tanggal keluar, sehingga penghuni berikutnya boleh mulai pada tanggal yang sama.

## Tagihan

| Method | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/tagihan` | Daftar tagihan; filter `periode=YYYY-MM`, `status`, `rumah_id`, dan `per_page`. |
| `POST` | `/tagihan/buat-bulanan` | Membuat tagihan untuk seluruh rumah yang dihuni pada periode terkait. |

```json
{
  "periode": "2026-07",
  "jatuh_tempo": "2026-07-10"
}
```

Pembuatan tagihan bersifat idempoten: pemanggilan ulang tidak menggandakan kombinasi rumah, jenis iuran, dan periode.

## Pembayaran

| Method | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/pembayaran` | Riwayat pembayaran; filter `bulan`, `rumah_id`, dan `per_page`. |
| `GET` | `/pembayaran/opsi?rumah_id={id}` | Seluruh tagihan tertunggak dan calon pembayar yang sah untuk satu rumah. |
| `POST` | `/pembayaran` | Mencatat pelunasan satu atau beberapa tagihan. |

```json
{
  "rumah_id": 1,
  "penghuni_id": 1,
  "tanggal_bayar": "2026-07-05",
  "catatan": "Pembayaran iuran bulan Juli",
  "alokasi": [
    { "tagihan_id": 1, "nominal": 100000 },
    { "tagihan_id": 2, "nominal": 15000 }
  ]
}
```

Semua tagihan harus berasal dari rumah yang sama dan dibayar lunas sesuai sisa tagihannya. Pembayar harus merupakan penghuni aktif atau penghuni historis yang terkait dengan tagihan terpilih.

## Pengeluaran

| Method | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/pengeluaran` | Daftar pengeluaran; filter `bulan`, `kategori`, dan `per_page`. |
| `POST` | `/pengeluaran` | Menambah pengeluaran. |
| `GET` | `/pengeluaran/{id}` | Detail pengeluaran. |
| `PATCH` | `/pengeluaran/{id}` | Memperbarui pengeluaran. |
| `DELETE` | `/pengeluaran/{id}` | Soft delete pengeluaran. |

Field utama: `kategori`, `keterangan`, `nominal`, `tanggal_pengeluaran`, `rutin`, `bukti`, dan `catatan`.

## Laporan

| Method | Endpoint | Keterangan |
|---|---|---|
| `GET` | `/laporan/tahunan?tahun=2026` | Ringkasan 12 bulan, pemasukan, pengeluaran, selisih, dan saldo berjalan. |
| `GET` | `/laporan/bulanan?bulan=2026-07` | Saldo awal/akhir serta detail pembayaran dan pengeluaran bulan tertentu. |

## Status respons umum

- `200`: permintaan berhasil.
- `401`: belum login atau token tidak berlaku.
- `404`: data tidak ditemukan.
- `422`: validasi atau aturan bisnis gagal; detail berada pada objek `errors`.
- `500`: kesalahan internal server.

## Alur pengujian yang disarankan

1. Login dan simpan token.
2. Tambahkan penghuni.
3. Tempatkan penghuni ke rumah.
4. Buat tagihan bulanan.
5. Ambil opsi pembayaran satu rumah.
6. Catat pembayaran.
7. Tambahkan pengeluaran.
8. Periksa laporan bulanan dan tahunan.
