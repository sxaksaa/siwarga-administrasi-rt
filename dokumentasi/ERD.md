# Entity Relationship Diagram (ERD)

Nama tabel, kolom, relasi, dan nilai status pada data utama menggunakan Bahasa Indonesia agar mudah dipahami oleh pengelola aplikasi.

```mermaid
erDiagram
    PENGHUNI {
        bigint id PK
        varchar nama_lengkap
        varchar foto_ktp_path
        enum jenis_penghuni "tetap atau kontrak"
        varchar nomor_telepon
        boolean sudah_menikah
    }
    RUMAH {
        bigint id PK
        varchar nomor_rumah UK
        varchar alamat
        text catatan
    }
    RIWAYAT_HUNIAN {
        bigint id PK
        bigint rumah_id FK
        bigint penghuni_id FK
        date mulai_tinggal
        date selesai_tinggal
        text catatan
    }
    JENIS_IURAN {
        bigint id PK
        varchar kode UK
        varchar nama
        decimal nominal_default
        boolean aktif
    }
    TAGIHAN {
        bigint id PK
        bigint rumah_id FK
        bigint penghuni_id FK
        bigint jenis_iuran_id FK
        date periode_tagihan
        decimal nominal
        decimal nominal_terbayar
        enum status "belum_lunas sebagian lunas"
        date jatuh_tempo
        varchar nama_penghuni_snapshot
        enum jenis_penghuni_snapshot
    }
    PEMBAYARAN {
        bigint id PK
        varchar nomor_bukti UK
        bigint rumah_id FK
        bigint penghuni_id FK
        datetime tanggal_bayar
        decimal total_bayar
        varchar nama_pembayar_snapshot
        varchar metode_pembayaran
    }
    ALOKASI_PEMBAYARAN {
        bigint id PK
        bigint pembayaran_id FK
        bigint tagihan_id FK
        decimal nominal
    }
    PENGELUARAN {
        bigint id PK
        varchar kategori
        varchar keterangan
        decimal nominal
        date tanggal_pengeluaran
        boolean rutin
        varchar bukti_path
    }

    RUMAH ||--o{ RIWAYAT_HUNIAN : memiliki
    PENGHUNI ||--o{ RIWAYAT_HUNIAN : menempati
    RUMAH ||--o{ TAGIHAN : ditagih
    PENGHUNI o|--o{ TAGIHAN : bertanggung_jawab
    JENIS_IURAN ||--o{ TAGIHAN : menentukan
    RUMAH ||--o{ PEMBAYARAN : menerima
    PENGHUNI o|--o{ PEMBAYARAN : membayar
    PEMBAYARAN ||--|{ ALOKASI_PEMBAYARAN : dialokasikan
    TAGIHAN ||--o{ ALOKASI_PEMBAYARAN : dilunasi
```

## Tabel teknis Laravel

Selain tabel bisnis di atas, Laravel memiliki tabel teknis seperti `users`, `personal_access_tokens`, `cache`, `jobs`, dan `migrations`. Nama tabel bawaan tersebut dipertahankan agar kompatibel dengan framework. Tabel teknis tidak termasuk dalam ERD bisnis karena tidak mewakili proses administrasi RT.

## Keputusan integritas data

- Status rumah dihitung dari ada atau tidaknya riwayat hunian aktif (`selesai_tinggal` kosong).
- Perpindahan penghuni menutup periode lama dan membuat periode baru; riwayat lama tidak ditimpa.
- Tagihan menyimpan snapshot nama dan jenis penghuni agar riwayat tetap benar setelah penghuni pindah atau datanya berubah.
- Satu rumah hanya memiliki satu tagihan untuk kombinasi jenis iuran dan periode yang sama.
- Satu pembayaran dapat dialokasikan ke banyak tagihan sehingga pembayaran satu tahun tetap tercatat per bulan.
- Pergantian penghuni di tengah bulan tidak mengubah penanggung jawab secara acak: penghuni pada hari pertama periode diprioritaskan, atau penghuni pertama pada bulan tersebut jika rumah sebelumnya kosong.
