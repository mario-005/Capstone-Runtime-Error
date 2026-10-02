# Sistem Pengelolaan Produksi dan Bahan Baku Silih Asih

Fondasi aplikasi capstone untuk menghubungkan pesanan langsung dan katering dengan kebutuhan bahan, persediaan, serta pekerjaan dapur.

## Stack

- PHP 8.4 dan Laravel 13
- React 19, Tailwind CSS 4, dan Vite 8
- SQLite 3
- Docker Compose

Versi persis PHP package berada di `composer.lock`; versi JavaScript berada di `package-lock.json`.

## Menjalankan dengan Docker

Salin `.env.example` menjadi `.env`, lalu buat application key:

```powershell
Copy-Item .env.example .env
docker-compose run --rm app php artisan key:generate
```

Bangun dan jalankan seluruh service:

```powershell
docker-compose up --build
```

Instalasi Docker Compose modern juga dapat memakai `docker compose` sebagai pengganti `docker-compose`. Aplikasi tersedia di `http://localhost:8000` dan Vite dev server di port `5173`. Migration dijalankan ketika service aplikasi dimulai. Data SQLite disimpan di `database/database.sqlite` pada volume proyek.

## Menjalankan tanpa Docker

Pastikan ekstensi PHP `pdo_sqlite` aktif dan file `database/database.sqlite` tersedia, lalu jalankan:

```powershell
composer install
npm.cmd install
php artisan key:generate
php artisan migrate
composer run dev
```

Pada instalasi pertama, file database dapat dibuat dengan `New-Item database/database.sqlite -ItemType File` sebelum menjalankan migration.

Pada shell yang tidak memblokir script npm, `npm install` dapat digunakan menggantikan `npm.cmd install`.

## Verifikasi

```powershell
npm.cmd run build
php artisan test --compact
php vendor/bin/pint --format agent
docker-compose config
```

Dokumentasi analisis dan rancangan tersedia di [docs/README.md](docs/README.md).
