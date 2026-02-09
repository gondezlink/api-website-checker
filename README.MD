# API Website Document Checker

API sederhana untuk upload & check dokumen (saat ini fokus PDF) dengan:
- PHP 8.2 + Slim 4
- PostgreSQL
- MinIO (S3 compatible)
- Swagger UI

## Cara Menjalankan

1. Buat file `.env` dari `.env.example`
2. Jalankan:

```bash
docker compose up -d --build

Akses:
API Swagger UI   →  http://localhost:8080/docs
MinIO Console    →  http://localhost:9000  (user: minioadmin, pass: miniosecretkey)

Buat bucket documents di MinIO console (manual pertama kali)

Endpoint Utama

POST /upload      → upload file (form-data key: document)
GET  /documents   → list dokumen yang sudah diupload

### Langkah selanjutnya untuk Anda

1. Buat folder `api-website-checker`
2. Buat subfolder `app/src`
3. Copy-paste semua isi file di atas ke file masing-masing
4. Di dalam folder `app/` jalankan sekali:
   ```bash
   composer install

(supaya composer.lock dan folder vendor muncul)

docker compose up -d --build