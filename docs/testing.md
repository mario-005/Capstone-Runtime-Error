# Testing Strategy

## Pendekatan berbasis risiko

Prioritas tertinggi adalah saldo/alokasi, konsumsi sebagian, status, snapshot resep, permission, idempotensi, serta konkurensi. Unit test memeriksa kalkulator kebutuhan, konversi, pembulatan, dan state machine. Feature/integration test memakai PostgreSQL dan memeriksa API beserta transaksi. UAT memeriksa kecocokan alur kerja, sedangkan usability test mengukur waktu dan kesalahan pengguna. Hasil simulasi tidak dianggap bukti dampak operasional nyata.

Setiap test otomatis harus memeriksa respons API, perubahan database, absence of unintended changes, movement/audit yang diwajibkan, dan invariant stok. Fixture menggunakan label data ilustratif, bukan data Silih Asih.

## Test case inti

| ID | Prasyarat | Langkah | Hasil yang diharapkan | Cakupan |
|---|---|---|---|---|
| TC-01 | User aktif dengan role; user tanpa role | Login sebagai keduanya, akses endpoint yang diizinkan dan dilarang | Session user aktif dibuat; user tanpa permission menerima 403; data tidak berubah | US-01, AC-09 |
| TC-02 | Menu dan resep aktif dengan konversi valid | Buat order direct dengan item dan target | Draft, item snapshot, recipe version, requirement, faktor konversi, dan pembulatan tersimpan; stok belum berubah | US-03/04, AC-01/02 |
| TC-03 | Fisik 10 kg, belum ada alokasi, kebutuhan 3 kg | Confirm order memakai idempotency key | Alokasi 3 kg; fisik tetap 10 kg; allocated 3 kg; available 7 kg | BR-01–03, AC-03 |
| TC-04 | Dua order masing-masing membutuhkan 7 kg dari fisik 10 kg | Jalankan dua confirm secara benar-benar paralel dan tunggu commit | Total alokasi maksimal 10 kg; request lain mendapat shortage atau 409 sesuai kebijakan; tidak ada lost update/negative balance | BR-19, AC-04 |
| TC-05 | Katering confirmed, alokasi 5 kg, belum dipakai | Ubah jumlah sehingga kebutuhan menjadi 3 kg, lalu menjadi 7 kg | Delta -2 kg dilepas; perubahan berikutnya mencoba tambahan 4 kg; tidak ada alokasi ganda; snapshot baru dan audit tercatat | BR-03/12 |
| TC-06 | Order confirmed dengan alokasi, produksi belum mulai | Batalkan dengan reason dan version benar | `CANCELLED`; seluruh alokasi belum terpakai dilepas; fisik tetap; history dan audit tercatat | BR-13/14, AC-06 |
| TC-07 | Alokasi 5 kg; produksi berjalan; 2 kg telah dipakai | Batalkan oleh user berizin; ulangi sebagai user tanpa izin | Pemakaian 2 kg/movement tetap; sisa 3 kg dilepas; bahan matang tidak dikembalikan; percobaan tanpa izin 403 dan tidak mengubah data | BR-04/13, AC-05/06 |
| TC-08 | Order A confirmed memakai resep v1 | Buat/aktifkan v2; baca A; buat order B | A tetap memakai requirement/faktor v1; B memakai v2 | BR-11, AC-07 |
| TC-09 | Requirement 3 kg, alokasi 3 kg, threshold varians tersedia | Catat usage 2,5 kg; lalu usage di atas sisa tanpa override | Movement aktual 2,5 kg; physical/allocation turun tepat sekali; varians terlihat; kelebihan tanpa override ditolak | BR-04/10 |
| TC-10 | Konversi kg↔g aktif; tidak ada faktor bahan untuk berat↔volume | Buat resep dalam gram; terima pembelian kg; coba resep volume untuk bahan berbasis berat | Konversi kompatibel benar; snapshot faktor disimpan; lintas dimensi tanpa faktor ditolak 422 | BR-07/09 |
| TC-11 | Endpoint usage dan key `K1` | Kirim body A dua kali dengan K1; kirim body B dengan K1 | Body A membuat satu usage/movement dan respons kedua sama; body B mendapat 409; tidak ada transaksi tambahan | BR-18, AC-10 |
| TC-12 | Kasir, dapur, dan inventory login | Kasir mencoba adjustment; dapur membaca audit; inventory melakukan aksi yang diizinkan | Dua aksi terlarang menerima 403 tanpa perubahan; aksi inventory sesuai permission berhasil | US-01, AC-09 |
| TC-13 | Order confirmed; produksi `NOT_STARTED` | Panggil ready langsung; panggil start dua kali dengan version lama | Ready ditolak 409/422; start pertama berhasil; request versi lama ditolak; histori tidak ganda | BR-14/19 |
| TC-14 | Semua item fulfilled; produksi berjalan | Tandai ready lalu hand-over pada waktu berbeda | `ready_at` dan `handed_over_at` terpisah; order/production/history konsisten; on-time memakai `ready_at` | BR-15, AC-08 |
| TC-15 | Katering membutuhkan 8 kg; hanya 5 kg tersedia | Confirm dengan `allow_shortage=true`, lalu catat receipt 3 kg dan recalculate | Alokasi awal 5 kg, shortage 3 kg, fisik tidak negatif; setelah receipt shortage menjadi 0 dan total alokasi 8 kg | BR-05/16 |
| TC-16 | Stok fisik tersedia; user inventory berizin | Catat waste beralasan; adjustment tanpa alasan; adjustment valid | Waste membuat movement OUT; tanpa alasan ditolak; adjustment valid mengubah saldo sekali dan memiliki actor/reason/audit | BR-06/17 |
| TC-17 | Order memiliki dua item, masing-masing quantity 5 | Catat fulfillment 3 pada item A; lalu sisa A dan seluruh B | Status menjadi `PARTIAL`/`PARTIALLY_FULFILLED`, lalu `COMPLETED`/`READY`; fulfilled per item tidak melebihi order quantity | BR-14 |
| TC-18 | Order version 2 sedang diedit dua user | User A menyimpan dengan version 2; user B mengirim perubahan berbeda dengan version 2 | A berhasil dan version bertambah; B mendapat 409 `STALE_VERSION` dan state terbaru | BR-19 |

## Pengujian transaksi dan integrasi

TC-04 wajib memakai dua koneksi database, barrier sebelum lock, dan commit yang benar-benar overlap; menjalankan dua request secara berurutan tidak memenuhi test konkurensi. Test memverifikasi invariant setelah commit: `physical_quantity >= 0`, `allocated_quantity >= 0`, `physical_quantity - allocated_quantity >= 0`, serta `consumed_quantity + released_quantity <= allocated_quantity`.

Feature test end-to-end minimal: order → recipe snapshot → requirement → allocation → production → usage → fulfillment → ready → hand-over. Jalur pembatalan diuji terpisah sebelum dan sesudah pemakaian. Retry deadlock dibatasi dan hanya dilakukan dengan idempotency key.

## UAT

UAT melibatkan fungsi pemilik/admin, penerima pesanan, dapur, serta pengelola bahan setelah peran aktual dikonfirmasi. Setiap sesi mencatat skenario, perangkat, fungsi peserta, hasil pass/fail, masalah, keputusan, dan bukti yang boleh disimpan. Skenario UAT mencakup direct, katering dengan shortage, perubahan, pembatalan, pemakaian aktual, koreksi stok, antrean, dan laporan. UAT tidak dijalankan pada production tanpa backup dan persetujuan.

## Usability

Ukur waktu dan jumlah kesalahan untuk membuat order, menemukan shortage, memulai produksi, mencatat pemakaian, dan mengoreksi kesalahan. Uji desktop kasir serta layar kecil yang realistis. Observasi terminologi, keterbacaan status, target sentuh, keyboard, kontras, dan pemulihan setelah error jaringan. Target numerik baru ditentukan setelah baseline/perangkat V-03/V-13 tersedia.

## Pengukuran ketepatan

Simpan `target_at`, `confirmed_at`, `started_at`, `ready_at`, dan `handed_over_at`. Definisi usulan: on-time ready bila `ready_at <= target_at`; keterlambatan penyerahan dilaporkan terpisah. Ambil baseline manual dan periode pilot yang sebanding. Laporkan volume, jenis order, kekurangan tenaga/alat, dan perubahan permintaan agar hasil tidak keliru dianggap kausal. Demo atau data simulasi hanya membuktikan fungsi sistem, bukan perbaikan operasional nyata.
