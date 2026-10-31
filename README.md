# KasMuda Desa

KasMuda Desa adalah aplikasi pencatatan kas keuangan muda-mudi desa. Aplikasi
ini terdiri dari REST API Laravel, frontend Vue.js, dan database MySQL. Pengguna
login menggunakan token Sanctum, kemudian dapat mengelola transaksi pemasukan
dan pengeluaran melalui halaman web atau Postman.

Repository:

<https://github.com/muhammadariefand/uts-pl-24-545740-sv-25748-v2>

## Fitur

- Login dan logout menggunakan Laravel Sanctum.
- CRUD transaksi kas.
- Validasi data transaksi dengan respons JSON.
- Frontend Vue.js dengan Axios dan route guard.
- Docker Compose untuk database, backend, dan frontend.
- Backend dijalankan menggunakan FrankenPHP.
- Frontend dibangun melalui multi-stage Dockerfile dan disajikan Nginx.
- Feature test Laravel.
- GitHub Actions untuk pengujian backend, build frontend, dan publikasi image
  ke GitHub Container Registry pada push ke `main`.
- Postman Collection untuk pengujian seluruh endpoint API.

## Arsitektur

```text
Browser / Postman
        |
        v
Frontend Vue.js + Nginx :80
        |
        | /api/
        v
Laravel API + FrankenPHP :8000
        |
        v
MySQL :3306
        |
        v
Named volume: mysql_data
```

Di dalam jaringan Docker Compose, backend menggunakan hostname `db` untuk
MySQL dan Nginx frontend meneruskan permintaan API ke hostname `backend`.
Browser mengakses aplikasi melalui `http://localhost`.

## Struktur repository

```text
.
├── backend/                  # Laravel REST API
│   ├── app/                  # Model, controller, dan resource
│   ├── database/             # Migration, factory, dan seeder
│   ├── routes/api.php        # Endpoint API
│   ├── Dockerfile            # Image Laravel + FrankenPHP
│   └── .env.example          # Contoh konfigurasi Laravel
├── frontend/                 # Vue.js application
│   ├── src/                  # Views, router, dan Axios service
│   ├── Dockerfile            # Multi-stage Node.js + Nginx
│   └── nginx.conf            # SPA fallback dan reverse proxy API
├── postman/                  # Postman Collection
├── .github/workflows/ci.yml  # CI dan deployment image
└── docker-compose.yml        # Orkestrasi seluruh service
```

## Prasyarat

Untuk menjalankan dengan Docker:

- Git
- Docker Desktop dengan Docker Compose v2

Untuk menjalankan tanpa Docker:

- PHP 8.5 atau kompatibel dengan `composer.json`
- Composer
- Node.js 22 dan npm
- MySQL atau SQLite untuk kebutuhan lokal

## Menjalankan dengan Docker Compose

1. Clone repository dan masuk ke folder proyek:

   ```powershell
   git clone https://github.com/muhammadariefand/uts-pl-24-545740-sv-25748-v2.git
   cd uts-pl-24-545740-sv-25748-v2
   ```

2. Buat file environment root dari contoh:

   ```powershell
   Copy-Item .env.example .env
   ```

   Isi `APP_KEY`, password MySQL, dan nilai konfigurasi lain sesuai kebutuhan.
   Jangan commit file `.env`.

3. Jalankan seluruh service:

   ```powershell
   docker compose up -d --build
   ```

4. Periksa status service:

   ```powershell
   docker compose ps
   ```

   Service `db` dan `backend` harus berstatus `healthy` atau `running`, dan
   frontend dapat dibuka melalui <http://localhost>.

5. Hentikan service tanpa menghapus data:

   ```powershell
   docker compose down
   ```

   Volume `mysql_data` sengaja tidak dihapus sehingga data tetap tersedia saat
   Compose dijalankan kembali. Jangan gunakan `docker compose down -v` jika
   ingin mempertahankan data.

### Akun demo

Seeder menyediakan akun demo berikut:

```text
Email:    admin@kasmuda.test
Password: password
```

Gunakan akun ini untuk login pertama kali setelah container selesai melakukan
migration dan seeding.

### Log FrankenPHP

Untuk memastikan backend dilayani FrankenPHP:

```powershell
docker compose logs backend
```

Log harus memuat informasi seperti `FrankenPHP started` dan `Caddy serving PHP
app on :8000`.

## Menjalankan backend secara lokal

```powershell
cd backend
Copy-Item .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Backend tersedia di `http://127.0.0.1:8000`.

Untuk menjalankan test:

```powershell
php artisan test
```

Test menggunakan SQLite in-memory sesuai konfigurasi `phpunit.xml`, sehingga
tidak memerlukan database produksi.

## Menjalankan frontend secara lokal

Buka terminal baru:

```powershell
cd frontend
npm ci
```

Buat `frontend/.env` jika diperlukan:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

Jalankan server pengembangan:

```powershell
npm run dev
```

Validasi build produksi:

```powershell
npm run build
```

Alamat API dibaca dari `VITE_API_BASE_URL`, bukan ditulis langsung di dalam
komponen Vue. Pada Docker Compose, frontend menggunakan `/api` dan Nginx
meneruskannya ke service backend.

## Endpoint API

Base URL lokal:

```text
http://127.0.0.1:8000/api
```

Semua endpoint yang diberi tanda `Token` memerlukan header:

```http
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
```

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| POST | `/login` | Tidak | Mendapatkan token |
| POST | `/logout` | Token | Menghapus token aktif |
| GET | `/transactions` | Token | Menampilkan semua transaksi |
| POST | `/transactions` | Token | Menambah transaksi |
| GET | `/transactions/{id}` | Token | Menampilkan satu transaksi |
| PUT/PATCH | `/transactions/{id}` | Token | Mengubah transaksi |
| DELETE | `/transactions/{id}` | Token | Menghapus transaksi |

Contoh body untuk membuat transaksi:

```json
{
  "type": "income",
  "amount": 150000,
  "description": "Iuran kegiatan desa",
  "transaction_date": "2026-01-15"
}
```

Respons penting:

- `200 OK`: permintaan berhasil.
- `201 Created`: data berhasil dibuat.
- `401 Unauthorized`: token tidak ada atau tidak valid.
- `404 Not Found`: data tidak ditemukan.
- `422 Unprocessable Entity`: validasi gagal.

## Postman

Collection tersedia di:

[`postman/Kas Muda Mudi.postman_collection.json`](postman/Kas%20Muda%20Mudi.postman_collection.json)

Collection mencakup login, logout, serta operasi CRUD transaksi. Import file
tersebut ke Postman, jalankan Login terlebih dahulu, lalu gunakan token yang
dihasilkan untuk request protected lainnya.

Collection online:

<https://go.postman.co/collection/52889252-7febd085-753f-415c-b9cd-b7069746d3c0?source=collection_link>

## CI/CD GitHub Actions

Workflow berada di
[`.github/workflows/ci.yml`](.github/workflows/ci.yml). Workflow menjalankan:

1. **Backend tests**: setup PHP, install Composer, menyiapkan Laravel, dan
   menjalankan `php artisan test`.
2. **Frontend build**: setup Node.js, menjalankan `npm ci`, dan menjalankan
   `npm run build`.
3. **Deploy images to GHCR**: hanya pada push ke branch `main`; image backend
   dan frontend diberi tag commit SHA, bukan `latest`.

Pull Request dan push ke `dev` hanya menjalankan pemeriksaan backend dan
frontend. Deployment tidak dijalankan pada event tersebut.

Image yang dipublikasikan menggunakan pola:

```text
ghcr.io/muhammadariefand/uts-pl-24-545740-sv-25748-v2/backend:<commit-sha>
ghcr.io/muhammadariefand/uts-pl-24-545740-sv-25748-v2/frontend:<commit-sha>
```

Nilai rahasia seperti `APP_KEY` dan password database harus disimpan di
GitHub Secrets atau file `.env` lokal, bukan di repository.

## Alur branch

Pengembangan mengikuti alur:

```text
feature/* -> dev -> main
```

Perubahan diajukan melalui Pull Request. Branch `main` dan `dev` sebaiknya
dilindungi dengan kewajiban Pull Request dan status check CI sebelum merge.

## Troubleshooting

### Container database unhealthy

Periksa log database:

```powershell
docker compose logs db
```

Pastikan Docker Desktop berjalan, password pada `.env` terisi, dan gunakan
`mysql:8.0.36` sesuai konfigurasi Compose. Jika ini adalah instalasi baru,
tunggu healthcheck selesai sebelum memeriksa backend.

### Backend gagal terhubung ke database

Pastikan backend menggunakan:

```env
DB_HOST=db
DB_PORT=3306
```

`db` adalah nama service Compose, bukan `localhost` atau alamat IP laptop.

### Build frontend gagal karena `nginx.conf` tidak ditemukan

Pastikan file `frontend/nginx.conf` berada di dalam build context frontend
dan tidak dikecualikan oleh `frontend/.dockerignore`.

### Image FrankenPHP tidak ditemukan

Gunakan tag versi yang tersedia dan jelas, yaitu:

```text
dunglas/frankenphp:1.9-php8.5-bookworm
```

Hindari tag `latest` dan pastikan Docker Desktop dapat mengakses Docker Hub.

## Keamanan

- Jangan commit `.env`, token, `APP_KEY`, atau password.
- Gunakan `.env.example` hanya sebagai template tanpa nilai rahasia.
- Ganti password demo untuk penggunaan nyata.
- Batasi akses database dan GHCR sesuai kebutuhan.
