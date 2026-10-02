# Sistem Pengelolaan Produksi dan Bahan Baku Silih Asih

## Ringkasan
Dokumentasi ini menjadi acuan awal untuk aplikasi internal yang menghubungkan pesanan langsung dan katering dengan kebutuhan bahan, alokasi persediaan, antrean dapur, produksi, dan laporan operasional. Sistem juga direncanakan terhubung ke WhatsApp melalui GOWA agar chat berisi nama menu, misalnya `ayam geprek`, otomatis dicatat sebagai pesanan dan mengurangi bahan sesuai resep. Nama aplikasi dan rancangan di sini masih dapat berubah setelah validasi lapangan.

## Status epistemik
- **Fakta:** Silih Asih adalah warung makan dekat kampus; makanan pelanggan langsung dimasak setelah dipesan; tersedia layanan katering; tujuan aplikasi adalah menghubungkan pesanan, bahan, dan dapur.
- **Usulan desain:** isi dokumen, alur, skema data, endpoint, dan prioritas MVP.
- **Asumsi sementara:** satu lokasi dan satu basis persediaan; operasi internal memakai web, sedangkan pesanan satu porsi dapat masuk melalui WhatsApp; jaringan tersedia di area kerja; peran dapat dirangkap satu orang.
- **Belum dikonfirmasi:** volume, menu, pekerja, alat, pencatatan saat ini, masalah dan frekuensinya, anggaran, perangkat, target waktu layanan, serta kebutuhan pembayaran.

## Daftar dokumen dan urutan membaca
1. [PRD](prd.md) - tujuan, pengguna, kebutuhan, dan batas MVP.
2. [Business Process](business-process.md) - alur kerja usulan dan tanggung jawab.
3. [Business Rules](business-rules.md) - sumber utama aturan stok, pesanan, dan status.
4. [Design](design.md) - layar dan interaksi.
5. [Database](db.md) - model data SQLite.
6. [API](api.md) - kontrak REST.
7. [Architecture](architecture.md) - pembagian modul dan deployment.
8. [Testing](testing.md) - strategi dan test case.
9. [Implementation Plan](implementation-plan.md) - tahapan delivery minggu ke-5 sampai ke-14.
10. [Validation Checklist](validation-checklist.md) - hal yang harus dibuktikan di lapangan.
11. [Traceability](traceability.md) - jejak kebutuhan sampai test case.

## Keputusan desain sementara
Laravel sebagai backend monolith modular dengan REST API, React dan Tailwind CSS sebagai frontend, SQLite sebagai database, dan Docker Compose untuk development. Fondasi repository saat ini memakai Laravel 13.34, Sanctum 4.3, PHP 8.4, React 19.3, Tailwind CSS 4.3, Vite 8.3, dan SQLite 3. Versi persis terkunci di `composer.lock` dan `package-lock.json`. Untuk aplikasi web milik sendiri, autentikasi memakai session cookie Laravel Sanctum dengan CSRF; token personal hanya ditambahkan bila ada client non-browser yang benar-benar dibutuhkan.

Keputusan yang menunggu validasi: penggunaan sumber daya bersama, pembayaran sederhana, aturan prioritas, perangkat di dapur, kebutuhan offline, dan target waktu pemenuhan.

## Integrasi WhatsApp yang direncanakan

[GOWA](https://github.com/aldinokemal/go-whatsapp-web-multidevice) bertindak sebagai gateway WhatsApp. GOWA meneruskan event pesan masuk ke webhook Laravel. Laravel memvalidasi signature webhook, menormalisasi teks, lalu mencocokkannya secara case-insensitive dengan nama menu aktif. Pesan `ayam geprek`, misalnya, berarti pesanan langsung satu porsi untuk menu Ayam Geprek. Bila resep aktif dan seluruh bahan cukup, sistem dalam satu transaksi membuat pesanan beserta snapshot resep, memulai produksi, mencatat pemakaian sesuai kebutuhan resep, dan mengurangi stok fisik. Event duplikat tidak boleh membuat pesanan atau mengurangi bahan dua kali.

Jika nama menu tidak cocok, resep belum aktif, atau bahan tidak cukup, pesanan tidak dibuat dan stok tidak berubah; pengirim menerima alasan umum. Format jumlah lebih dari satu, catatan tambahan, pesan grup, serta perubahan/pembatalan lewat WhatsApp harus divalidasi sebelum dimasukkan ke scope.

GOWA adalah proyek tidak resmi yang tidak berafiliasi dengan WhatsApp. Versi container/API wajib dipin, risiko pemblokiran akun dan perubahan payload harus diterima pemilik, dan penggunaan production harus dibandingkan dengan WhatsApp Business Platform resmi.

## Kesiapan implementasi
Dokumentasi ini siap menjadi baseline analisis dan estimasi, tetapi **belum siap menjadi spesifikasi final tanpa validasi pemilik**. Tanda `Belum dikonfirmasi` pada [validation-checklist.md](validation-checklist.md) harus diselesaikan sebelum skema dan status dikunci.
