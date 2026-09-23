# Validation Checklist

Status awal seluruh item: **Belum dikonfirmasi**. Tidak ada status `Dikonfirmasi` tanpa bukti wawancara, observasi, atau dokumen yang dicatat.

| ID | Pertanyaan/data | Fungsi ditanya | Dampak desain | Status |
|---|---|---|---|---|
| V-01 | Berapa pekerja dan fungsi yang dirangkap? | Pemilik | Role, permission, training | Belum dikonfirmasi |
| V-02 | Apakah direct dan katering berbagi dapur, alat, pekerja, stok? | Pemilik/dapur | Satu ledger vs lokasi/kapasitas | Belum dikonfirmasi |
| V-03 | Volume order, menu, dan puncak harian? | Pemilik/kasir | Pagination, performance, scope | Belum dikonfirmasi |
| V-04 | Bagaimana pencatatan order/stok saat ini? | Kasir/bahan | Migrasi, usability, baseline | Belum dikonfirmasi |
| V-05 | Masalah nyata dan frekuensinya? | Semua fungsi | Prioritas dan KPI | Belum dikonfirmasi |
| V-06 | Target waktu direct dan katering? | Pemilik/kasir | `target_at`, on-time metric | Belum dikonfirmasi |
| V-07 | Kapan order dianggap confirmed, siap, diserahkan? | Pemilik/kasir/dapur | State machine dan timestamps | Belum dikonfirmasi |
| V-08 | Kebijakan kekurangan, substitusi, parsial? | Pemilik/dapur | Shortage and partial flow | Belum dikonfirmasi |
| V-09 | Resep per porsi/batch dan yield? | Dapur/bahan | Formula dan rounding | Belum dikonfirmasi |
| V-10 | Unit pembelian, resep, dan faktor konversi? | Bahan/dapur | Unit model and validation | Belum dikonfirmasi |
| V-11 | Siapa boleh koreksi stok/cancel/change priority? | Pemilik | RBAC and audit | Belum dikonfirmasi |
| V-12 | Apakah pembayaran sederhana diperlukan? | Pemilik/kasir | `payment_status`, UI, scope | Belum dikonfirmasi |
| V-13 | Perangkat, internet, printer, backup, anggaran? | Pemilik | Deployment and offline need | Belum dikonfirmasi |
| V-14 | Retensi data dan arsip transaksi? | Pemilik | Archive and backup policy | Belum dikonfirmasi |
| V-15 | Apakah operasional hanya satu lokasi dan satu basis persediaan? | Pemilik/bahan | Perlu tidaknya lokasi gudang dan saldo per lokasi | Belum dikonfirmasi |
| V-16 | Apakah aplikasi hanya digunakan internal melalui browser web? | Pemilik/semua fungsi | Auth, perangkat, dan kebutuhan client lain | Belum dikonfirmasi |
| V-17 | Apakah jaringan tersedia dan stabil di kasir serta dapur? | Pemilik/semua fungsi | Polling, retry, dan kebutuhan offline | Belum dikonfirmasi |
| V-18 | Berapa ukuran kemasan pembelian, presisi, dan kebijakan pembulatan tiap bahan? | Bahan/dapur | Requirement, shortage, dan rencana pengadaan | Belum dikonfirmasi |

## Data lapangan yang diminta
Contoh menu dan resep, satuan/kemasan pembelian, daftar bahan, 1-2 minggu sampel order yang dianonimkan, catatan stok/movement, jadwal katering, waktu siap/serah, serta daftar alat dan kapasitas. Jangan menyalin data pribadi tanpa izin.

Dokumen operasional yang perlu diperiksa bila tersedia: buku/nota pesanan, jadwal katering, kartu atau catatan stok, nota penerimaan/pembelian, lembar resep, catatan bahan rusak/terbuang, daftar harga/menu, pembagian tugas, dan prosedur backup yang sekarang digunakan. Ketiadaan dokumen juga dicatat sebagai hasil validasi; daftar ini bukan pernyataan bahwa dokumen tersebut sudah tersedia.

## Pertanyaan wawancara per fungsi
Kasir: langkah menerima perubahan, target waktu, pelanggan menunggu, dan kebutuhan pembayaran. Dapur: urutan kerja, station, batch, pemakaian aktual, pembatalan setelah mulai, dan bottleneck. Bahan: penerimaan, waste, satuan/konversi, ukuran kemasan, dan stok opname. Pemilik: KPI, otorisasi, risiko, batas anggaran, retensi, lokasi, jaringan, dan keputusan sumber daya bersama.

## Bukti dan keputusan
Untuk setiap jawaban, simpan tanggal, fungsi narasumber, referensi bukti yang boleh dicatat, keputusan, dan dampak terhadap dokumen. Status hanya `Belum dikonfirmasi`, `Dikonfirmasi`, atau `Ditolak`. Jika jawaban ditolak, jelaskan alasan, lalu revisi PRD, rules, DB, API, testing, dan traceability yang terkena.
