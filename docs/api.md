# REST API Contract

## Konvensi

Base URL usulan `/api/v1`. Payload memakai JSON UTF-8, nama field `snake_case`, ID UUID, dan timestamp ISO-8601 dengan offset. Respons sukses tunggal berbentuk `{ "data": {...} }`; daftar berbentuk `{ "data": [...], "meta": { "page": 1, "per_page": 20, "total": 50 } }`.

Error berbentuk:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Data tidak valid.",
    "fields": { "items.0.quantity": ["Harus lebih dari 0."] }
  },
  "request_id": "uuid"
}
```

Kode umum: 200/201/204 sukses, 400 format salah, 401 belum login, 403 tidak berhak, 404 tidak ditemukan, 409 konflik status/stok/versi/idempotensi, 422 validasi bisnis/input, dan 429 rate limit. Detail internal tidak dikirim ke client.

Daftar menerima `page`, `per_page` (1–100), `q`, `sort`, dan filter yang diizinkan. Waktu difilter dengan `from` dan `to`. Nilai filter dan sort di-whitelist; input lain menghasilkan 422.

## Autentikasi dan otorisasi

Aplikasi browser milik sendiri memakai Laravel Sanctum stateful session cookie dan CSRF. Login membuat session; client mengambil CSRF cookie sesuai konfigurasi Sanctum sebelum mutasi. Semua endpoint selain CSRF/login membutuhkan user aktif. Otorisasi tetap diperiksa server-side.

| Grup endpoint | OWNER_ADMIN | ORDER_CLERK | KITCHEN | INVENTORY |
|---|---:|---:|---:|---:|
| Profil sendiri | R | R | R | R |
| Menu/resep/satuan | CRUD | R | R | R; C/U konversi jika diberi permission |
| Pesanan | CRUD | C/R/U; cancel sesuai permission | R | R |
| Produksi/fulfillment | CRUD | R | C/R/U | R |
| Pemakaian bahan | CRUD | R | C/R/U | C/R/U |
| Penerimaan/waste | CRUD | R | R | C/R/U |
| Adjustment | CRUD | - | - | C jika permission khusus |
| Laporan | R | R terbatas | R antrean sendiri | R persediaan |
| Audit log | R | - | - | - |

C/R/U/D berarti create/read/update/delete. Penghapusan master yang sudah dipakai direalisasikan sebagai deaktivasi, bukan physical delete. Matriks adalah usulan dan menunggu V-01/V-11.

## Schema respons utama

`Order` menggunakan field database: `id`, `order_number`, `order_type`, `customer_name`, `target_at`, `order_status`, `payment_status`, `notes`, `confirmed_at`, `ready_at`, `handed_over_at`, `cancelled_at`, `lock_version`, `items`, `requirements`, `allocations`, dan `production`. `payment_status` dihilangkan bila pembayaran tidak masuk scope.

`InventoryBalance` berisi `ingredient_id`, `ingredient_name`, `base_unit_id`, `physical_quantity`, `allocated_quantity`, serta nilai turunan `available_quantity`. Nilai turunan tidak disimpan sebagai sumber kebenaran.

Resource master memakai nama field yang sama dengan [db.md](db.md). Password hash, fingerprint, dan data internal tidak pernah dikirim.

## Endpoint autentikasi dan master

| Method dan endpoint | Akses/fungsi | Request utama | Response | Kode khusus |
|---|---|---|---|---|
| `POST /auth/login` | Publik; login | `email`, `password` | user dan roles | 401, 422, 429 |
| `POST /auth/logout` | Login; hapus session | tanpa body | tanpa body | 204 |
| `GET /me` | Login; profil aktif | - | user dan roles | 401 |
| `GET/POST /menus` | Sesuai matriks; list/buat | POST: `code`, `name`, `active` | `Menu` | 201, 403, 422 |
| `GET/PATCH /menus/{id}` | Detail/ubah | field yang berubah, `lock_version` bila diterapkan | `Menu` | 403, 404, 409, 422 |
| `GET/POST /ingredients` | List/buat bahan | POST: `code`, `name`, `base_unit_id`, opsi purchase unit/pack | `Ingredient` | 201, 403, 422 |
| `GET/PATCH /ingredients/{id}` | Detail/ubah master | field master; saldo tidak boleh diubah lewat endpoint ini | `Ingredient` | 403, 404, 422 |
| `GET/POST /units` | List/buat satuan | POST: `code`, `name`, `dimension`, `decimal_places` | `Unit` | 201, 403, 422 |
| `PATCH /units/{id}` | Ubah/deaktivasi | field master | `Unit` | 403, 404, 422 |
| `GET/POST /unit-conversions` | List/buat faktor | POST: `ingredient_id?`, `from_unit_id`, `to_unit_id`, `factor`, `source_note` | `UnitConversion` | 201, 403, 422 |
| `PATCH /unit-conversions/{id}` | Ubah/deaktivasi faktor | faktor/sumber/status | versi terbaru | 403, 404, 422 |
| `GET/POST /menus/{id}/recipe-versions` | List/buat versi | POST: `recipe_type`, `yield_quantity`, `yield_unit_id`, `items[]` | `RecipeVersion` | 201, 403, 404, 422 |
| `POST /menus/{id}/recipe-versions/{version_id}/activate` | Aktifkan versi | `reason` | versi aktif | 403, 404, 409 |

Resep hanya dapat diaktifkan bila seluruh item memiliki konversi valid ke base unit bahan. Mengubah resep dilakukan dengan versi baru; versi yang sudah digunakan transaksi tidak diedit.

## Endpoint pesanan dan produksi

| Method dan endpoint | Fungsi | Request utama | Response | Kode khusus |
|---|---|---|---|---|
| `GET /orders` | Cari/list pesanan | filter `q`, `order_type`, `order_status`, `from`, `to` | daftar ringkas `Order` | 422 |
| `POST /orders` | Buat draft | `order_type`, `customer_name?`, `target_at`, `notes?`, `items[]` | `Order` beserta requirement snapshot | 201, 422 |
| `GET /orders/{id}` | Detail lengkap | - | `Order` | 404 |
| `PATCH /orders/{id}` | Ubah target/item yang diizinkan | field perubahan, `lock_version`, `reason` bila produksi berjalan | `Order` terbaru | 403, 409, 422 |
| `POST /orders/{id}/confirm` | Konfirmasi dan alokasi | `lock_version`, `allow_shortage` | order, requirement, allocation, shortage | 409, 422 |
| `POST /orders/{id}/allocations/recalculate` | Alokasikan shortage setelah receipt/perubahan | `lock_version` | allocation dan shortage terbaru | 409, 422 |
| `POST /orders/{id}/cancel` | Batalkan sesuai BR-13 | `lock_version`, `reason` | order, allocation released, usage retained | 403, 409, 422 |
| `POST /orders/{id}/production/start` | Mulai produksi | `lock_version` | `Production` dan order terbaru | 409 |
| `POST /orders/{id}/production/usage` | Catat pemakaian aktual | `lock_version`, `ingredient_id`, `source_quantity`, `source_unit_id`, `reason?` | usage, movement, balances | 201, 409, 422 |
| `POST /orders/{id}/production/fulfillments` | Catat item selesai/parsial | `lock_version`, `order_item_id`, `quantity`, `reason?` | fulfillment, item, production status | 201, 409, 422 |
| `POST /orders/{id}/production/ready` | Tandai seluruh pesanan siap | `lock_version` | order dan production terbaru | 409, 422 |
| `POST /orders/{id}/hand-over` | Tandai diserahkan | `lock_version` | order terbaru | 409 |
| `GET /production/queue` | Antrean direct/katering | `from`, `to`, `order_type`, `production_status` | order terurut dan konflik shortage | 422 |

Contoh pembuatan order:

```json
{
  "order_type": "CATERING",
  "customer_name": "Contoh pelanggan",
  "target_at": "2026-10-10T11:30:00+07:00",
  "notes": "Contoh, bukan data operasional",
  "items": [{ "menu_id": "uuid", "quantity": 25, "notes": null }]
}
```

`confirm`, `cancel`, `usage`, `fulfillments`, `ready`, dan `hand-over` wajib mengikuti transisi BR-12–BR-19. `ready` ditolak apabila jumlah fulfilled belum memenuhi seluruh item. Pembatalan setelah mulai produksi mempertahankan usage/fulfillment dan hanya melepas sisa alokasi.

## Endpoint persediaan, laporan, dan audit

| Method dan endpoint | Fungsi | Request/filter | Response | Kode khusus |
|---|---|---|---|---|
| `GET /inventory/balances` | Saldo bahan | `q`, `availability`, pagination | `InventoryBalance[]` | 422 |
| `GET /inventory/movements` | Ledger | ingredient, type, date, pagination | movement[] | 422 |
| `POST /inventory/receipts` | Penerimaan pembelian sederhana | `ingredient_id`, `source_quantity`, `source_unit_id`, `reference_number?`, `reason?` | movement dan balance | 201, 409, 422 |
| `POST /inventory/waste` | Rusak/terbuang | `ingredient_id`, `source_quantity`, `source_unit_id`, `reason` | movement dan balance | 201, 409, 422 |
| `POST /inventory/adjustments` | Koreksi stock opname | `ingredient_id`, `direction`, `source_quantity`, `source_unit_id`, `reason` | movement, balance, audit | 201, 403, 409, 422 |
| `GET /reports/operations` | KPI operasional | `from`, `to`, `order_type` | agregat dan definisi metrik | 422 |
| `GET /audit-logs` | Audit read-only | actor, action, entity, date, pagination | audit metadata[] | 403, 422 |

Penerimaan, waste, dan adjustment bukan update langsung terhadap `physical_quantity`; endpoint membuat movement dan mengubah saldo dalam satu transaksi.

## Idempotensi, konflik, dan batas transaksi

Header `Idempotency-Key` wajib pada `confirm`, allocation recalculation, cancel, usage, fulfillment, receipt, waste, dan adjustment. Scope dibentuk dari method serta route template. Kombinasi user, scope, dan key unik. Fingerprint dihitung dari body yang telah dinormalisasi.

- Key baru: simpan status `PROCESSING`, jalankan transaksi, lalu simpan respons.
- Key dan fingerprint sama: kembalikan respons tersimpan dengan status yang sama.
- Key sama tetapi fingerprint berbeda: 409 `IDEMPOTENCY_KEY_REUSED`.
- Request lain masih memproses key tersebut: 409 `REQUEST_IN_PROGRESS` dan client boleh retry terkontrol.

Mutasi menerima `lock_version`. Versi usang menghasilkan 409 `STALE_VERSION` beserta resource versi terbaru. Stok/alokasi tidak cukup menghasilkan 409 `INVENTORY_CONFLICT` atau, ketika `allow_shortage=true` dan kebijakan mengizinkan, respons sukses dengan shortage eksplisit.

Confirm/reallocation, usage, fulfillment, cancel, receipt, waste, dan adjustment masing-masing selesai dalam satu transaksi database dengan row lock sebagaimana [db.md](db.md). Side effect eksternal tidak dijalankan di tengah transaksi.
