Ringkasan singkat dan panduan cepat untuk pengembangan lokal dan kontribusi.

## 🐳 Docker Development (Recommended)

**Quick Start:**
```bash
# Start services
docker compose up -d

# Initialize database
docker compose exec app php artisan migrate --force

# Access app
open http://localhost:8000
```

📖 **Full Documentation:**
- [Docker Quick Start](./DOCKER_QUICKSTART_DEV.md) - Get started in 5 minutes
- [Docker Development Guide](./docs/DOCKER_DEV_DEPLOYMENT.md) - Complete guide
- [Docker Cheat Sheet](./docs/DOCKER_CHEATSHEET.md) - Common commands

---

## Ringkasan proyek
- Aplikasi booking tiket kereta berbasis Laravel 12 + Inertia + Vue 3.
- Fitur utama: manajemen trip/kereta, booking (TicketTrip), penumpang (Passenger), upload KTP, blacklist otomatis saat pembatalan.

## Teknologi utama
- PHP 8.2+ / Laravel 12
- Inertia.js + Vue 3
- PostgreSQL (DB), Redis (cache/session/queue optional)
- Vite + TailwindCSS untuk frontend

Konfigurasi paket ada di `composer.json` dan `package.json`.

## Struktur penting
- Backend: `app/` (Models, Http/Controllers, Actions, Requests)
- Frontend pages: `resources/js/pages`
- Database migrations: `database/migrations`
- Seeders: `database/seeders` (lihat `docs/SEEDER_DOCUMENTATION.md`)
- Dokumen desain & panduan: `docs/` (ERD, Migration Guide, Seeder docs, dll.)

## Cepat mulai (lokal)
1. Salin file env dan generate key:

```bash
cp .env.example .env
composer install
php artisan key:generate
```

2. Atur nilai DB & Redis pada `.env`, lalu migrasi dan seed (opsional):

```bash
php artisan migrate
php artisan db:seed # atau php artisan db:seed --class=BlacklistSeeder
php artisan storage:link
```

3. Jalankan dev server dan Vite (frontend):

```bash
npm install
npm run dev
# atau gunakan composer script: composer run-script dev
```

4. Jalankan worker/queue jika perlu:

```bash
php artisan queue:work
```

## Perintah penting
- Run tests: `composer test` (menjalankan `php artisan test` + pint lint)
- Format code: `composer run-script lint` / `npm run format`

## Environment notes
- `APP_MAINTENANCE_DRIVER` (lihat `.env`) menentukan bagaimana Laravel menyimpan status maintenance mode (mis. `file`, `cache`, `database`).
  - Untuk development `file` sudah memadai.
  - Untuk produksi terdistribusi gunakan store terpusat (mis. `redis` atau `database`) dan set `APP_MAINTENANCE_STORE` jika diperlukan.

## Database & ERD
Lihat diagram relasi dan penjelasan entitas di `docs/ERD.md` untuk detail tabel seperti `ticket_trips`, `passengers`, `blacklists`, `trains`, dan lainnya.

- ERD: [docs/ERD.md](docs/ERD.md)
- Migration guide: [docs/MIGRATION_GUIDE.md](docs/MIGRATION_GUIDE.md)
- Seeder docs: [docs/SEEDER_DOCUMENTATION.md](docs/SEEDER_DOCUMENTATION.md)

## Fitur penting dan catatan implementasi
- Blacklist: auto-insert saat `ticket_trips` berstatus `CANCELLED`. Cek validasi booking terhadap `blacklists.is_active = true`.
- Upload KTP: file paths disimpan (contoh di docs), gunakan `storage/app/public/ktp` untuk penyimpanan lokal dan `FILESYSTEM_DISK` sesuai `.env`.
- Passenger types: `DEWASA` vs `BAYI` — aturan validasi berbeda (bayi nullable NIK/KTP).

## Seeders & Test data
- Project menyediakan seeders untuk stasiun, schedule, blacklist, ticket trips, dan exceptions. Jalankan `php artisan db:seed` untuk data contoh.

## Contributing
- Ikuti konvensi kode: jalankan `composer lint` / `npm run lint` sebelum submit PR.
- Gunakan `php artisan make:` untuk scaffolding Laravel (migrations, controllers, requests) sesuai gaya proyek.

## Troubleshooting & Tips
- Jika mendapat error Vite manifest saat develop, jalankan `npm run dev` atau `npm run build` lalu `composer run-script post-update-cmd`.
- Pastikan `storage` writable dan `php artisan storage:link` dibuat untuk akses file upload.

## Referensi dokumen
- [docs/SUMMARY.md](docs/SUMMARY.md)
- [docs/ERD.md](docs/ERD.md)
- [docs/SEEDER_DOCUMENTATION.md](docs/SEEDER_DOCUMENTATION.md)
- [docs/MIGRATION_GUIDE.md](docs/MIGRATION_GUIDE.md)
- [docs/SOFT_DELETES_ANALYSIS.md](docs/SOFT_DELETES_ANALYSIS.md)

---
Generated from repository docs on 2026-02-17.
