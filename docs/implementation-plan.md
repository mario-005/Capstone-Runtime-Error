# Implementation Plan

Rencana minggu 5-14 berikut dapat disesuaikan setelah validasi lapangan. Dependensi utama: aturan dan data master harus disepakati sebelum migrasi final.

| Minggu | Fokus | Hasil dapat diperiksa |
|---|---|---|
| 5 | Validasi proses, scope, glossary, environment | checklist wawancara, decision log, Docker skeleton |
| 6 | Auth, role, layout, migration dasar | login dan policy test |
| 7 | Menu, bahan, satuan, resep/version | CRUD master dan unit tests |
| 8 | Order direct/katering dan snapshot | create/detail order, validation tests |
| 9 | Requirement, shortage, allocation | service transaksi, TC-03/04 |
| 10 | Receipt, usage, waste, adjustment | ledger, permission, TC-07/09/16 |
| 11 | Queue, production, ready/hand-over | layar dapur dan state tests |
| 12 | Dashboard, laporan, audit, idempotency | report queries, audit, TC-11/18 |
| 13 | Integrasi, UAT, usability, concurrency | defect log dan hasil UAT |
| 14 | Hardening, backup/restore, dokumentasi, demo | release candidate dan sign-off scope |

## Dependensi dan milestone

Alur dependensi utama: validasi istilah/aturan → auth dan schema dasar → satuan/konversi → bahan/menu/resep → order dan snapshot → requirement/shortage → allocation → movement/usage → fulfillment dan status produksi → laporan/audit → UAT dan hardening. Modul setelah tanda panah tidak dikunci sebelum kontrak modul sebelumnya lolos review, tetapi frontend mock dan test fixture dapat dikerjakan paralel.

| Milestone | Target | Exit criteria |
|---|---:|---|
| M1 Fondasi tervalidasi | Akhir minggu 6 | keputusan kritis dicatat; login/policy/schema dasar dapat diuji |
| M2 Order-to-allocation | Akhir minggu 9 | order direct/katering menghasilkan snapshot, requirement, shortage, dan allocation konsisten |
| M3 Operasi dapur dan stok | Akhir minggu 11 | receipt-to-usage dan order-to-ready lulus integration/concurrency test |
| M4 Observability dan UAT | Akhir minggu 13 | laporan/audit tersedia; UAT empat fungsi selesai dan defect diprioritaskan |
| M5 Release candidate | Akhir minggu 14 | test/build lulus; restore drill berhasil; scope dan known issues ditandatangani |

## Backlog prioritas
P0: auth/RBAC, schema, satuan/konversi, recipe snapshot, order, requirement, allocation, movement, antrean produksi, fulfillment, status, audit, idempotency, dan tests stok. P1: dashboard, laporan, search/filter, responsive polish, dan backup automation. P2: pembayaran sederhana, export, notifikasi/queue background, serta offline; hanya setelah kebutuhan terbukti.

## Definition of Done
Fitur memiliki acceptance criteria dan test; policy diuji; migration reversible atau strategi rollback jelas; error/loading/empty state tersedia; audit dan idempotency diterapkan pada mutasi; tidak ada secret; lint/test/build lulus; dokumentasi endpoint dan traceability diperbarui; UAT terkait disetujui.

## Pembagian kerja tanpa nama
Analis/PM: validasi, PRD, rules, UAT. Backend: domain, migration, API, concurrency. Frontend: halaman, komponen, responsive state. QA: test matrix, automation, usability. DevOps/Integrator: Docker, CI, backup, environment. Satu anggota dapat merangkap fungsi setelah kapasitas aktual dikonfirmasi.

## Risiko pengerjaan
Data resep terlambat -> gunakan fixture berlabel dan lock keputusan; scope melebar -> jaga P0; concurrent test sulit -> siapkan test harness; perangkat lapangan terbatas -> uji layar kecil; rule pembayaran belum jelas -> tetap out of scope.
