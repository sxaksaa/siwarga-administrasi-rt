# Aturan Bisnis

1. Rumah dinyatakan **dihuni** jika memiliki satu riwayat hunian aktif (`selesai_tinggal` masih kosong) dan **tidak dihuni** jika tidak memilikinya.
2. Satu rumah hanya boleh memiliki satu riwayat hunian aktif pada waktu yang sama.
3. Satu penghuni hanya boleh menjadi penghuni aktif pada satu rumah pada waktu yang sama.
4. Penghuni tetap dan kontrak hanya ditagih selama rumahnya dihuni pada periode terkait.
5. Iuran satpam bernilai awal Rp100.000 per bulan.
6. Iuran kebersihan bernilai awal Rp15.000 per bulan.
7. Nominal awal dapat dikelola tanpa mengubah nilai tagihan lama.
8. Pembayaran beberapa bulan dibuat sebagai satu transaksi dengan beberapa alokasi tagihan.
9. Status tagihan adalah belum lunas, dibayar sebagian, atau lunas dan ditentukan dari total alokasi pembayaran.
10. Penghapusan penghuni dan pengeluaran memakai soft delete agar jejak administrasi tidak hilang.
11. Jika penghuni berganti di tengah bulan, tagihan bulan tersebut menjadi tanggung jawab penghuni yang tinggal pada hari pertama periode.
12. Jika rumah masih kosong pada hari pertama lalu mulai dihuni pada pertengahan bulan, penghuni pertama pada bulan tersebut menjadi penanggung jawab tagihan.
13. `selesai_tinggal` adalah tanggal keluar dan tidak termasuk masa tinggal. Penghuni berikutnya boleh mulai pada tanggal yang sama tanpa dianggap bertumpang tindih.
