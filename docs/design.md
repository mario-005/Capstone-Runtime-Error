# Product Design

## Prinsip
Antarmuka dipakai saat pekerjaan berlangsung: utamakan target waktu, status, jumlah, dan tindakan utama; gunakan istilah dapur yang disepakati; minimalkan input berulang; tampilkan peringatan sebelum keputusan yang tidak mudah dibatalkan; dan jangan menyamarkan kekurangan sebagai stok negatif.

## Navigasi dan halaman
Navigasi utama usulan: Dashboard; Pesanan (Daftar, Buat, Katering); Dapur; Persediaan (Saldo, Movement, Penerimaan); Master (Menu/Resep, Bahan, Satuan/Konversi); Laporan; dan Audit. Item disembunyikan bila role tidak memiliki akses, tetapi server tetap menjadi sumber otorisasi.

| Halaman | Isi dan tindakan | Akses utama |
|---|---|---|
| Login | Username/email, password, error umum, session timeout | Semua |
| Dashboard | Pesanan hari ini, terlambat, siap, kekurangan, stok kritis; filter tanggal | Admin, kasir, dapur, bahan |
| Pesanan | Daftar dengan tipe/status/target, cari dan filter; buka, ubah, batal | Admin, kasir |
| Input pesanan | Tipe, pelanggan, item, jumlah, catatan, target; hitung kebutuhan sebelum konfirmasi | Admin, kasir |
| Layar dapur | Antrean terurut, detail item, tombol mulai, pemakaian, selesai/siap | Dapur |
| Katering | Kalender/list target, kebutuhan, kekurangan, alokasi, perubahan | Admin, kasir, dapur |
| Persediaan | Saldo fisik/alokasi/tersedia, movement, terima, rusak, koreksi | Admin, bahan |
| Satuan dan konversi | Daftar satuan/dimensi, faktor umum atau khusus bahan, sumber verifikasi, aktif/nonaktif; buat/ubah sesuai permission | Admin, bahan berizin |
| Menu dan resep | Menu aktif, versi resep, tipe resep, komponen, hasil batch; buat versi, validasi konversi, aktifkan versi | Admin |
| Laporan | Ketepatan siap, waktu proses, pemakaian, kekurangan, koreksi | Admin |
| Audit log | Filter actor, aksi, entitas, waktu; read-only | Admin |

## Komponen reusable
App shell, role-aware navigation, status badge, date/time picker, item quantity editor, shortage panel, allocation summary, confirmation dialog, reason field, table dengan pagination, toast, inline validation, dan error boundary. Tombol destruktif selalu menyebut objek dan konsekuensinya.

## Responsive dan aksesibilitas
Desktop cocok untuk meja kasir; layar kecil memakai tabel menjadi kartu/baris padat dan tindakan utama tetap terlihat. Semua input memiliki label, fokus keyboard, urutan tab logis, kontras memadai, pesan error terkait field, target sentuh yang cukup, dan warna tidak menjadi satu-satunya penanda status.

## State dan feedback
Loading memakai skeleton pada daftar dan disabled state pada submit; empty state menjelaskan filter atau langkah berikutnya; validasi memeriksa angka positif, target waktu, menu aktif, satuan kompatibel, dan permission; error jaringan menawarkan retry dengan idempotency key yang sama; sukses menampilkan nomor pesanan dan status terbaru. Konflik versi menampilkan data terbaru dan meminta pengguna meninjau ulang. Batal setelah produksi dimulai menjelaskan bahwa pemakaian tidak dikembalikan; pembatalan dan koreksi stok mewajibkan dialog konfirmasi serta alasan.

## Pencarian, filter, pagination
Semua daftar transaksi mendukung pencarian nomor/nama, filter status dan tanggal, sort target waktu, serta pagination server-side. Filter ditampilkan sebagai chip yang dapat dihapus dan kondisi kosong menyebut filter aktif. Detail pesanan menampilkan riwayat status, kebutuhan, alokasi, dan pemakaian.
