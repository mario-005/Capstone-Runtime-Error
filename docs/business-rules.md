# Business Rules

Dokumen ini adalah sumber utama aturan bisnis. API, UI, dan database harus mengikuti ID berikut.

## Stok dan alokasi
- **BR-01:** `physical_quantity` adalah jumlah fisik tercatat. `allocated_quantity` adalah jumlah yang dicadangkan untuk pesanan aktif. `available_quantity = physical_quantity - allocated_quantity` dan tidak boleh negatif.
- **BR-02:** Alokasi bukan pemakaian dan tidak mengurangi fisik. Bahan teralokasi tidak bebas untuk pesanan lain.
- **BR-03:** Alokasi dibuat saat pesanan dikonfirmasi atau melalui aksi eksplisit berwenang; perubahan hanya terhadap delta kebutuhan; alokasi dilepas saat dibatalkan atau kebutuhan berkurang, sepanjang belum dipakai.
- **BR-04:** Pemakaian aktual mengurangi stok fisik dan sisa alokasi dalam satu transaksi. Untuk setiap alokasi, `remaining_allocated_quantity = allocated_quantity - consumed_quantity - released_quantity`. Pemakaian `x` menambah `consumed_quantity` sebesar `x`, mengurangi agregat `physical_quantity` sebesar `x`, dan mengurangi agregat `allocated_quantity` sebesar bagian yang sebelumnya dicadangkan. Jika pemakaian melebihi sisa alokasi, transaksi ditolak kecuali override berizin mencatat alasan, audit, dan selisih sebagai pemakaian tanpa alokasi. Kuantitas yang sama tidak boleh mengurangi fisik dua kali.
- **BR-05:** Kekurangan pesanan mendatang dicatat sebagai `shortage_quantity`/kebutuhan pengadaan, bukan stok fisik negatif.
- **BR-06:** Penerimaan menambah fisik. Rusak, hilang, dan penyesuaian mengubah fisik melalui stock movement dengan alasan.

## Satuan dan resep
- **BR-07:** Konversi hanya diizinkan antar-satuan dalam dimensi yang sama dengan faktor aktif dan tervalidasi. Konversi lintas dimensi, misalnya berat ke volume, harus memakai faktor khusus bahan yang memiliki sumber/verifikator; tanpa faktor tersebut transaksi ditolak. Semua hasil stok disimpan dalam `base_unit_id` bahan.
- **BR-08:** Resep dapat bertipe `PER_PORTION` atau `PER_BATCH`. Batch memiliki `yield_quantity` dan satuan hasil; kebutuhan batch dihitung dari jumlah batch yang diperlukan.
- **BR-09:** Kebutuhan operasional dibulatkan ke presisi bahan. Kebutuhan pengadaan dapat dibulatkan lagi ke ukuran kemasan pembelian. Mode pembulatan, presisi, faktor konversi, nilai sebelum pembulatan, dan hasil pembulatan disimpan pada snapshot kebutuhan agar perhitungan dapat diaudit.
- **BR-10:** Pemakaian aktual boleh berbeda dari resep dan menyimpan kuantitas, satuan, serta alasan varians bila melewati ambang yang ditetapkan.
- **BR-11:** Pesanan menyimpan snapshot recipe version dan kebutuhan. Perubahan resep hanya berlaku untuk perhitungan baru.

## Pesanan dan status
- **BR-12:** Perubahan sebelum `IN_PREPARATION` menghitung ulang delta kebutuhan dan alokasi. Setelah produksi dimulai, perubahan hanya boleh dilakukan oleh pengguna berizin, wajib beralasan, tidak boleh menulis ulang histori pemakaian, dan harus memperbarui target item yang belum selesai.
- **BR-13:** Pembatalan dari `DRAFT` atau `CONFIRMED` melepas seluruh alokasi yang belum dipakai. Pembatalan dari `IN_PREPARATION` atau `PARTIALLY_FULFILLED` memerlukan permission khusus dan alasan; hanya sisa alokasi yang dilepas, sementara pemakaian dan hasil yang sudah dibuat tetap tercatat. Bahan matang tidak dikembalikan menjadi bahan mentah. Pesanan `READY` atau `HANDED_OVER` tidak dibatalkan melalui alur biasa dan memerlukan koreksi administratif terpisah.
- **BR-14:** Transisi status pesanan yang valid adalah `DRAFT -> CONFIRMED`; `CONFIRMED -> IN_PREPARATION`; `IN_PREPARATION -> PARTIALLY_FULFILLED` atau `READY`; `PARTIALLY_FULFILLED -> READY`; dan `READY -> HANDED_OVER`. `DRAFT`, `CONFIRMED`, `IN_PREPARATION`, atau `PARTIALLY_FULFILLED` dapat menuju `CANCELLED` sesuai BR-13. Status produksi terpisah: `NOT_STARTED -> IN_PROGRESS -> PARTIAL/COMPLETED`, dengan `CANCELLED` dari `NOT_STARTED/IN_PROGRESS` sesuai pembatalan pesanan.
- **BR-15:** `ready_at` adalah saat makanan siap diambil/diserahkan menurut definisi operasional; `handed_over_at` adalah saat benar-benar diserahkan. Ketepatan utama diukur terhadap `target_at` menggunakan `ready_at`, bukan `handed_over_at`.
- **BR-16:** Pesanan katering yang belum tersedia bahan tetap boleh disimpan bila kebijakan mengizinkan; kekurangan terlihat dan tidak boleh dipenuhi dengan stok negatif.

## Integritas dan kontrol
- **BR-17:** Penyesuaian stok hanya role berpermission, wajib alasan dan audit log.
- **BR-18:** Request mutasi transaksi menerima `Idempotency-Key`. Kombinasi actor, endpoint/scope, dan key harus unik. Request ulang dengan fingerprint sama mengembalikan respons tersimpan; key sama dengan fingerprint berbeda ditolak sebagai konflik dan tidak menjalankan mutasi.
- **BR-19:** Alokasi, pemakaian, pembatalan, dan perubahan kebutuhan memakai transaksi database dan row lock pada inventory item/alokasi terkait.
- **BR-20:** Perubahan penting menyimpan actor, waktu, aksi, entitas, dan ringkasan sebelum/sesudah; data audit tidak dihapus dari UI biasa.

## Integrasi WhatsApp dan GOWA

- **BR-21:** Hanya event GOWA bertipe `message` dengan signature HMAC-SHA-256 yang valid yang diproses. Secret disimpan di environment/secret store, tidak di repository atau log. Payload dibatasi ukurannya dan divalidasi terhadap kontrak versi GOWA yang dipin.
- **BR-22:** Isi pesan dikenali dengan menghapus spasi di awal/akhir dan membandingkan secara case-insensitive dengan nama menu aktif. Kecocokan harus tepat dan unik; satu event yang cocok berarti satu porsi. Pesan tambahan seperti `2 ayam geprek` atau `ayam geprek pedas` tidak diproses sampai format kuantitas/catatan ditetapkan.
- **BR-23:** Menu yang cocok wajib memiliki resep aktif dan seluruh kebutuhan satu porsi harus tersedia. Jika valid, pembuatan order `DIRECT` bersumber `WHATSAPP`, snapshot resep, alokasi, produksi `IN_PROGRESS`, material usage, stock movement, dan pengurangan stok dilakukan dalam satu transaksi. Jika satu langkah gagal atau bahan tidak cukup, seluruh transaksi dibatalkan sehingga tidak ada order parsial atau stok negatif.
- **BR-24:** Setiap event pesan diproses maksimal sekali berdasarkan ID pesan eksternal bersama `device_id`. Event duplikat mengacu pada order/hasil sebelumnya dan tidak membuat order, usage, atau movement tambahan. Pesan dari akun sendiri, event non-pesan, dan pesan grup bila belum diizinkan tidak mengubah data. Retry balasan GOWA tidak boleh mengulangi transaksi order.
- **BR-25:** Order WhatsApp yang berhasil masuk sebagai `IN_PREPARATION` dengan kuantitas item 1, `confirmed_at`/`started_at` dari waktu pemrosesan, dan `target_at` dari target layanan direct yang telah dikonfigurasi. Order menyimpan sumber serta event asal. Balasan keberhasilan hanya memuat nomor order dan nama menu; balasan gagal memberi alasan umum tanpa membocorkan resep, jumlah stok, atau data internal. Jika target layanan direct belum dikonfigurasi, event ditolak agar sistem tidak mengarang target waktu.

## Ilustrasi angka
Semua angka berikut ilustrasi, bukan data Silih Asih. Jika fisik 10 kg dan teralokasi 3 kg, tersedia 7 kg. Pesanan baru memerlukan 5 kg, sehingga alokasi baru dapat dibuat dan tersedia tersisa 2 kg. Jika pesanan baru memerlukan 8 kg, hanya 7 kg yang dapat dialokasikan dan kekurangan 1 kg dicatat; fisik tetap 10 kg.

Untuk resep 0,25 kg per porsi dan 7 porsi, kebutuhan adalah 1,75 kg. Jika ukuran pembelian 0,5 kg, kebutuhan pengadaan dibulatkan ke atas menjadi 2,0 kg, tetapi kebutuhan produksi tetap 1,75 kg. Jika batch menghasilkan 10 porsi dan pesanan 25 porsi, diperlukan 3 batch, dengan hasil rencana 30 porsi; sisa harus dicatat sesuai kebijakan.

Ilustrasi konsumsi sebagian: alokasi awal 5 kg, lalu pemakaian aktual 2 kg. Fisik dan agregat alokasi masing-masing berkurang 2 kg; pada alokasi, `consumed_quantity=2` dan sisa alokasi 3 kg. Jika pesanan kemudian dibatalkan, 3 kg dilepas tanpa menambah stok fisik, karena fisik 2 kg telah benar-benar dipakai.
