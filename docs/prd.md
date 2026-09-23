# Product Requirements Document

## 1. Status dan latar belakang
Status dokumen: usulan untuk divalidasi. Fakta yang diketahui hanya: Silih Asih melayani pelanggan langsung dan katering, makanan langsung dimasak setelah dipesan, dan aplikasi dimaksudkan menghubungkan pesanan, bahan, serta dapur.

### Pernyataan masalah yang perlu divalidasi
Informasi pesanan, kebutuhan bahan, dan pekerjaan dapur berpotensi tidak berada pada satu tampilan sehingga pemilik sulit memantau kesiapan, kekurangan, dan ketepatan pemenuhan. Pernyataan ini bukan klaim hasil wawancara; frekuensi dan dampaknya harus diukur.

## 2. Tujuan dan non-goals
Tujuan: mencatat dua jenis pesanan; menghitung kebutuhan dan kekurangan bahan; memisahkan stok fisik, alokasi, dan tersedia; membantu antrean; mencatat waktu siap dan diserahkan; serta menyediakan laporan operasional.

Non-goals MVP: akuntansi lengkap, penggajian, payment gateway, integrasi aplikasi pengantaran, aplikasi pelanggan, optimasi otomatis, prediksi AI, dan multi-cabang. Pembayaran sederhana hanya masuk bila diperlukan dan tidak disebut akuntansi.

## 3. Stakeholder dan persona
| Persona fungsi | Kebutuhan | Batasan yang belum dikonfirmasi |
|---|---|---|
| Pemilik/admin | Melihat laporan, mengatur master data, hak akses, dan koreksi penting | Profil, KPI, dan frekuensi review |
| Penerima pesanan/kasir | Mencatat pesanan, janji waktu, perubahan, dan penyerahan | Perangkat, metode pencatatan saat ini |
| Petugas dapur | Melihat antrean, memulai/menyelesaikan produksi, mencatat aktual | Pembagian station dan kapasitas alat |
| Pengelola bahan/pembelian | Mencatat penerimaan, pemakaian, rusak, dan penyesuaian | Sumber pemasok dan pola pembelian |

Satu orang dapat memegang beberapa fungsi. Persona tidak menyatakan profil narasumber.

## 4. User journey
### Pesanan langsung
Kasir memasukkan pelanggan, item, catatan, dan waktu target -> sistem memvalidasi resep dan kebutuhan -> pesanan dikonfirmasi -> bahan dialokasikan bila dipilih aturan alokasi -> dapur melihat antrean -> produksi dimulai dan selesai -> makanan ditandai siap -> kasir menandai diserahkan.

### Katering
Kasir memasukkan tanggal/jam target, item, jumlah, catatan, dan status konfirmasi -> sistem menghitung kebutuhan dan kekurangan -> bahan yang tersedia dialokasikan -> kekurangan ditampilkan sebagai kebutuhan pengadaan -> dapur menyusun jadwal -> produksi berjalan -> siap -> diserahkan. Perubahan mengikuti [business-rules.md](business-rules.md).

## 5. User stories
| ID | User story | Prioritas |
|---|---|---|
| US-01 | Sebagai pengguna, saya ingin login sesuai peran agar akses terbatas. | Must |
| US-02 | Sebagai admin, saya ingin mengelola menu, bahan, satuan, dan resep. | Must |
| US-03 | Sebagai kasir, saya ingin membuat pesanan langsung/katering dengan item dan waktu target. | Must |
| US-04 | Sebagai sistem, saya ingin menghitung kebutuhan dan kekurangan bahan dari snapshot resep. | Must |
| US-05 | Sebagai pengelola bahan, saya ingin mencatat penerimaan, pemakaian, rusak, dan penyesuaian. | Must |
| US-06 | Sebagai pengguna berwenang, saya ingin mengalokasikan dan melepas bahan tanpa mengurangi fisik. | Must |
| US-07 | Sebagai dapur, saya ingin melihat antrean dan mencatat progres serta waktu siap. | Must |
| US-08 | Sebagai kasir, saya ingin mencatat penyerahan dan perubahan/pembatalan sesuai status. | Must |
| US-09 | Sebagai pemilik, saya ingin melihat dashboard dan laporan. | Should |
| US-10 | Sebagai admin, saya ingin melihat audit log transaksi penting. | Should |
| US-11 | Sebagai pengguna, saya ingin mencari, memfilter, dan pagination data. | Should |
| US-12 | Sebagai pengguna, saya ingin mencatat pembayaran sederhana bila kebutuhan dikonfirmasi. | Could |

## 6. Kebutuhan fungsional
FR-01 autentikasi dan RBAC; FR-02 master menu/bahan/satuan; FR-03 resep per porsi/batch; FR-04 pesanan dengan snapshot item dan resep; FR-05 penerimaan dan pergerakan stok; FR-06 perhitungan kebutuhan/kekurangan; FR-07 alokasi dan pelepasan; FR-08 antrean/jadwal; FR-09 progres produksi; FR-10 laporan; FR-11 audit; FR-12 idempotensi dan konkurensi. Detail aturan ada di [business-rules.md](business-rules.md).

## 7. Kebutuhan nonfungsional
NFR-01 integritas stok melalui transaksi dan locking; NFR-02 seluruh endpoint terlindung autentikasi; NFR-03 otorisasi server-side; NFR-04 audit perubahan penting; NFR-05 respons halaman operasional tetap dapat digunakan pada perangkat layar kecil; NFR-06 error dapat dipahami pengguna tanpa membocorkan secret; NFR-07 backup PostgreSQL teruji; NFR-08 timestamp tersimpan konsisten dan timezone dikonfigurasi; NFR-09 dapat diuji otomatis pada aturan stok dan status.

## 8. Acceptance criteria inti
- AC-01 Pesanan menyimpan `order_type`, item, jumlah, target waktu, dan status terpisah dari status produksi.
- AC-02 Kebutuhan dihitung dari snapshot resep dan tidak mengubah stok fisik.
- AC-03 Bahan teralokasi tidak dihitung sebagai `available_quantity`.
- AC-04 Dua alokasi bersamaan tidak dapat mengalokasikan unit yang sama melebihi stok.
- AC-05 Konsumsi sebagian mengurangi alokasi dan stok fisik tepat sekali.
- AC-06 Pembatalan setelah bahan terpakai tidak mengembalikan bahan mentah secara otomatis.
- AC-07 Resep baru tidak mengubah snapshot pesanan lama.
- AC-08 Status `READY` berbeda dari `HANDED_OVER`, dengan timestamp masing-masing.
- AC-09 Pengguna tanpa permission menerima 403 dan tidak ada perubahan data.
- AC-10 request dengan `Idempotency-Key` yang sama tidak menggandakan transaksi; body berbeda dengan key yang sama ditolak.
- AC-11 Konversi satuan hanya dilakukan dengan dimensi/faktor valid dan faktor yang dipakai tersimpan dalam snapshot.
- AC-12 Fulfillment parsial menyimpan jumlah selesai per item dan tidak boleh melebihi jumlah pesanan.

## 9. Indikator keberhasilan dan baseline
Indikator usulan: persentase pesanan siap sebelum target; median waktu konfirmasi-ke-siap; jumlah kekurangan bahan terdeteksi sebelum produksi; selisih stok sistem dan fisik; jumlah koreksi; dan waktu pencatatan pesanan. Baseline belum tersedia. Ambil sampel transaksi sebelum pilot, definisikan periode, dan bandingkan simulasi sistem dengan kejadian nyata; jangan menyimpulkan dampak kausal dari demo.

## 10. Prioritas dan roadmap
Must: US-01 sampai US-08 dan US-11 untuk alur inti. Should: US-09, US-10. Could: US-12. Won't MVP: semua non-goals. Pengembangan berikutnya setelah bukti kebutuhan: pembayaran, offline terbatas, laporan lebih kaya, integrasi, atau multi-cabang.

## 11. Risiko, dependensi, asumsi
Risiko: data resep tidak akurat, pemakaian aktual berbeda, konflik akses, perangkat terbatas, dan aturan prioritas belum disepakati. Dependensi: validasi pemilik, daftar menu/bahan/resep, target waktu, dan keputusan sumber daya bersama. Asumsi sementara dicatat di [validation-checklist.md](validation-checklist.md), bukan dianggap fakta.
