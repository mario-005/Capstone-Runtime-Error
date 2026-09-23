# Traceability Matrix

ID mengacu ke [prd.md](prd.md), [business-rules.md](business-rules.md), [design.md](design.md), [db.md](db.md), [api.md](api.md), dan [testing.md](testing.md). Setiap perubahan requirement harus memperbarui satu baris ini dan artefak yang dirujuk.

| Kebutuhan | User story | Aturan bisnis | Halaman UI | Tabel utama | Endpoint API | Test case |
|---|---|---|---|---|---|---|
| FR-01 Autentikasi/RBAC | US-01 | BR-17, BR-20 | Login, navigasi berbasis peran | `users`, `roles`, `user_roles`, `audit_logs` | `/auth/login`, `/auth/logout`, `/me` | TC-01, TC-12 |
| FR-02 Master menu/bahan/satuan | US-02 | BR-07, BR-17 | Menu dan resep, Satuan dan konversi, Persediaan | `menus`, `ingredients`, `units`, `unit_conversions` | `/menus`, `/ingredients`, `/units`, `/unit-conversions` | TC-10, TC-12 |
| FR-03 Resep per porsi/batch | US-02, US-04 | BR-07, BR-08, BR-09, BR-10, BR-11 | Menu dan resep | `recipe_versions`, `recipe_items`, `unit_conversions` | `/menus/{id}/recipe-versions`, `/menus/{id}/recipe-versions/{version_id}/activate` | TC-02, TC-08, TC-10 |
| FR-04 Pesanan dan snapshot | US-03, US-08 | BR-11, BR-12, BR-13, BR-14, BR-15, BR-16 | Pesanan, Input pesanan, Katering | `orders`, `order_items`, `order_requirements`, `order_status_histories` | `/orders`, `/orders/{id}`, `/orders/{id}/cancel`, `/orders/{id}/hand-over` | TC-02, TC-05, TC-06, TC-07, TC-08, TC-14 |
| FR-05 Penerimaan/pergerakan stok | US-05 | BR-04, BR-06, BR-17 | Persediaan | `ingredients`, `stock_movements`, `audit_logs` | `/inventory/receipts`, `/inventory/waste`, `/inventory/adjustments`, `/inventory/movements` | TC-07, TC-09, TC-16 |
| FR-06 Kebutuhan/kekurangan | US-04 | BR-05, BR-07–BR-11, BR-16 | Input pesanan, panel shortage, Katering | `order_requirements`, `recipe_versions`, `recipe_items`, `unit_conversions` | `/orders`, `/orders/{id}/confirm`, `/orders/{id}/allocations/recalculate` | TC-02, TC-08, TC-10, TC-15 |
| FR-07 Alokasi/pelepasan | US-06, US-08 | BR-01, BR-02, BR-03, BR-04, BR-05, BR-13, BR-19 | Ringkasan alokasi, detail pesanan | `material_allocations`, `ingredients`, `order_requirements` | `/orders/{id}/confirm`, `/orders/{id}/allocations/recalculate`, `/orders/{id}/cancel` | TC-03, TC-04, TC-05, TC-06, TC-07, TC-15 |
| FR-08 Antrean/jadwal | US-07 | BR-15, BR-16 | Layar dapur, Katering | `orders`, `productions`, `order_requirements` | `/production/queue` | TC-13, TC-14, TC-15, UAT |
| FR-09 Progres produksi | US-07, US-08 | BR-04, BR-10, BR-12, BR-13, BR-14, BR-15 | Layar dapur | `productions`, `production_fulfillments`, `material_usages`, `production_status_histories` | `/orders/{id}/production/start`, `/orders/{id}/production/usage`, `/orders/{id}/production/fulfillments`, `/orders/{id}/production/ready` | TC-07, TC-09, TC-13, TC-14, TC-17 |
| FR-10 Dashboard/laporan | US-09, US-11 | BR-05, BR-15 | Dashboard, Laporan | `orders`, status histories, `stock_movements`, `order_requirements` | `/reports/operations` dan endpoint list | TC-14, TC-15, UAT/usability |
| FR-11 Audit | US-10 | BR-17, BR-20 | Audit log | `audit_logs`, status histories | `/audit-logs` | TC-05, TC-06, TC-07, TC-12, TC-16 |
| FR-12 Idempotensi/konkurensi | US-06, US-08 | BR-18, BR-19 | Dialog konflik dan retry aman | `idempotency_keys`, `lock_version`, tabel transaksi | seluruh endpoint mutasi transaksi | TC-04, TC-11, TC-13, TC-18 |

## Cakupan nonfungsional

| Kebutuhan | Implementasi/dokumen | Verifikasi |
|---|---|---|
| NFR-01 integritas stok | BR-01–06/18–19, transaksi dan row lock DB | TC-03/04/07/09/11/15/18 |
| NFR-02/03 autentikasi dan otorisasi | Sanctum session, Policy, permission middleware | TC-01/12 |
| NFR-04 audit | `audit_logs`, status histories | TC-05/06/07/16 |
| NFR-05 responsive | design responsive dan layar kecil | usability test |
| NFR-06 error aman | format error API, logging tanpa secret | feature/security test |
| NFR-07 backup | architecture dan milestone hardening | restore drill minggu 14 |
| NFR-08 waktu/timezone | `timestamptz`, ISO-8601 offset | TC-14 dan timezone test |
| NFR-09 testability | service/domain terpisah, unit dan feature test | CI test suite |

US-12 pembayaran tetap `Could` dan belum memiliki implementasi lengkap sampai V-12 disetujui. Target KPI numerik belum ditetapkan sampai baseline V-03/V-05/V-06 tersedia; ini gap validasi, bukan izin untuk mengarang angka.
