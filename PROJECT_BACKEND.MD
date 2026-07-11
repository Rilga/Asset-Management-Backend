# PROJECT BACKEND — Gambaran Lengkap

> Sistem Manajemen Aset Berbasis RAG (Retrieval-Augmented Generation)
> Skripsi / Tugas Akhir — Semester 8

---

## 1. Ringkasan Proyek

Backend ini adalah REST API yang dibangun dengan **Laravel 12** (PHP 8.2) untuk mendukung sistem manajemen aset industri. Fitur utamanya mencakup:

- Manajemen aset mesin beserta QR Code otomatis
- Pencatatan jam jalan (operating hours)
- Penugasan dan pelaporan pemeliharaan (maintenance)
- Pengajuan dan pencatatan perbaikan (repair)
- Sistem **RAG (Retrieval-Augmented Generation)** untuk chatbot berbasis dokumen aset
- Evaluasi kualitas retrieval vector search

---

## 2. Stack Teknologi

| Komponen | Teknologi |
|---|---|
| Framework | Laravel 12 (PHP 8.2) |
| Database | PostgreSQL (Neon DB — cloud) via SSL |
| Vector DB | pgvector extension (built-in PostgreSQL) |
| File Storage | Supabase Storage (untuk PDF manual book) |
| File Storage Lokal | Laravel `storage/public` (foto kondisi, foto bukti, QR code) |
| Embedding Service | Python AI microservice (HTTP di `PYTHON_AI_BASE_URL`) |
| Autentikasi | Laravel Sanctum (Personal Access Token) |
| QR Code | `simplesoftwareio/simple-qrcode` |
| PDF Parser | `smalot/pdfparser` |
| Testing | PestPHP |
| Container | Docker (PHP 8.2-Apache) |

---

## 3. Arsitektur Sistem

```
Frontend (Next.js :3000)
        │
        ▼ HTTP REST (Bearer Token)
Laravel Backend (:8000)
        │
        ├── PostgreSQL (Neon DB)
        │     └── pgvector (tabel knowledge_chunks, kolom embedding vector(768))
        │
        ├── Supabase Storage
        │     └── bucket: manual-books (PDF manual book aset)
        │
        └── Python AI Microservice (:5000)
              └── POST /api/v1/embed  — menghasilkan embedding 768 dimensi
```

---

## 4. Struktur Direktori Utama

```
BACKEND/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ApiAuthController.php          # Login, issue Sanctum token
│   │   │   ├── Api/
│   │   │   │   ├── Asset/
│   │   │   │   │   └── AssetController.php    # CRUD aset + QR code + auto-ingest
│   │   │   │   ├── Chat/
│   │   │   │   │   └── ChatbotController.php  # RAG chatbot
│   │   │   │   ├── Evaluation/
│   │   │   │   │   └── RetrievalEvaluationController.php
│   │   │   │   ├── Knowledge/
│   │   │   │   │   ├── IngestionController.php
│   │   │   │   │   ├── KnowledgeDocumentController.php
│   │   │   │   │   └── RetrievalLogController.php
│   │   │   │   ├── Mekanik/
│   │   │   │   │   ├── OperatingHourController.php
│   │   │   │   │   ├── MaintenanceReportController.php
│   │   │   │   │   ├── RepairRequestController.php
│   │   │   │   │   └── RepairReportController.php
│   │   │   │   └── Teknik/
│   │   │   │       ├── MekanikController.php
│   │   │   │       └── MaintenanceTaskController.php
│   │   ├── Middleware/
│   │   │   └── RoleMiddleware.php             # Cek role: teknik / mekanik
│   │   └── Requests/
│   │       └── Auth/LoginRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Asset.php
│   │   ├── OperatingHour.php
│   │   ├── MaintenanceTask.php
│   │   ├── MaintenanceReport.php
│   │   ├── RepairRequest.php
│   │   ├── RepairReport.php
│   │   ├── KnowledgeDocument.php
│   │   ├── KnowledgeChunk.php
│   │   ├── ChatHistory.php
│   │   ├── RetrievalLog.php
│   │   └── RetrievalEvaluation.php
│   └── Services/
│       ├── RAG/
│       │   ├── RagService.php                 # Orkestrasi pipeline RAG
│       │   ├── EmbeddingService.php           # Panggil Python AI untuk embedding
│       │   ├── VectorSearchService.php        # Cosine similarity via pgvector
│       │   ├── LlmService.php                 # Generate jawaban dari konteks
│       │   ├── ChunkingService.php            # Pecah teks jadi chunk (300 char, overlap 50)
│       │   ├── KnowledgeIngestionService.php  # Ingest dokumen ke knowledge base
│       │   └── PdfExtractionService.php       # Ekstrak teks dari PDF (via Supabase)
│       └── Storage/
│           └── SupabaseStorageService.php     # Upload/download/delete Supabase Storage
├── database/
│   ├── migrations/                            # 12 migration files
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── UserSeeder.php                     # 2 user awal: teknik_01, mekanik_01
├── routes/
│   ├── api.php                                # Semua endpoint REST API
│   └── web.php
├── config/
│   └── services.php                           # Konfigurasi Supabase + Python AI
├── Dockerfile
└── apache.conf
```

---

## 5. Autentikasi & Otorisasi

### Login

```
POST /api/auth/login
Body: { "username": "...", "password": "..." }
Response: { "access_token": "Bearer ...", "role": "teknik|mekanik" }
```

- Menggunakan **Laravel Sanctum** Personal Access Token
- Token lama dihapus saat login baru (satu token aktif per user)
- Semua endpoint (kecuali login dan QR detail publik) dilindungi `auth:sanctum`

### Role

| Role | Deskripsi |
|---|---|
| `teknik` | Supervisor/Admin — dapat mengelola aset, mekanik, tugas, knowledge, evaluasi |
| `mekanik` | Pelaksana lapangan — input jam jalan, buat laporan, ajukan perbaikan |

`RoleMiddleware` membaca `$user->role` dari database dan menolak akses dengan HTTP 403 jika role tidak sesuai.

---

## 6. Endpoint API Lengkap

### Publik (tanpa autentikasi)
| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/auth/login` | Login dan dapatkan token |
| GET | `/api/assets/{asset}/qr-detail` | Detail aset dari scan QR code |

### Khusus Teknik (`role:teknik`)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET/POST/PUT/DELETE | `/api/mekanik` | CRUD akun mekanik |
| POST | `/api/assets` | Tambah aset baru |
| PUT | `/api/assets/{id}` | Update aset |
| DELETE | `/api/assets/{id}` | Hapus aset |
| POST | `/api/assets/{asset}/qr-code` | Regenerate QR code |
| POST/PUT/DELETE | `/api/maintenance-tasks` | CRUD tugas pemeliharaan |
| GET | `/api/maintenance-due-assets` | Aset yang perlu maintenance |
| PATCH | `/api/repair-requests/{id}/verify` | Verifikasi pengajuan perbaikan |
| POST | `/api/ingestion/assets/{asset}/profile` | Ingest profil aset ke knowledge base |
| POST | `/api/ingestion/assets/{asset}/manual-book` | Ingest PDF manual book |
| POST | `/api/ingestion/maintenance-reports/{id}` | Ingest laporan pemeliharaan |
| POST | `/api/ingestion/repair-reports/{id}` | Ingest catatan perbaikan |
| DELETE | `/api/knowledge-documents/{id}` | Hapus knowledge document |
| DELETE | `/api/retrieval-logs/{id}` | Hapus retrieval log |
| GET/POST/DELETE | `/api/retrieval-evaluations` | Evaluasi kualitas retrieval |

### Khusus Mekanik (`role:mekanik`)
| Method | Endpoint | Keterangan |
|---|---|---|
| POST/PUT/DELETE | `/api/operating-hours` | CRUD jam jalan mesin |
| POST/PUT/DELETE | `/api/maintenance-reports` | CRUD laporan pemeliharaan |
| POST/PUT/DELETE | `/api/repair-requests` | CRUD pengajuan perbaikan |
| POST/PUT/DELETE | `/api/repair-reports` | CRUD catatan perbaikan |

### Bersama Teknik & Mekanik
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/user` | Data user yang sedang login |
| GET | `/api/assets` | Daftar semua aset |
| GET | `/api/assets/{id}` | Detail aset |
| GET | `/api/assets/{id}/knowledge-summary` | Ringkasan knowledge aset |
| GET | `/api/operating-hours` | Daftar jam jalan |
| GET | `/api/maintenance-tasks` | Daftar tugas pemeliharaan |
| PATCH | `/api/maintenance-tasks/{id}/status` | Update status tugas |
| GET | `/api/maintenance-reports` | Daftar laporan pemeliharaan |
| GET | `/api/repair-requests` | Daftar pengajuan perbaikan |
| GET | `/api/repair-reports` | Daftar catatan perbaikan |
| GET | `/api/knowledge-documents` | Daftar knowledge document |
| POST | `/api/chat/ask` | Tanya chatbot RAG |
| GET | `/api/chat/histories` | Riwayat chat |
| GET/DELETE | `/api/chat/histories/{id}` | Detail / hapus riwayat chat |
| GET | `/api/retrieval-logs` | Log retrieval vector search |

---

## 7. Skema Database

### Tabel Utama

| Tabel | Deskripsi |
|---|---|
| `users` | User dengan kolom `username`, `role` (teknik/mekanik), `no_telp` |
| `assets` | Data aset mesin: kategori, nomor_peralatan, nama_mesin, area_mesin, merek, tahun_pembelian, maintenance_interval_hours, foto_kondisi, manual_book, qr_code_path |
| `operating_hours` | Jam jalan mesin per tanggal (asset_id, user_id, tanggal, jam_jalan) |
| `maintenance_tasks` | Tugas pemeliharaan: asset_id, assigned_by, assigned_to, tanggal_tugas, status (assigned/in_progress/submitted/completed/cancelled) |
| `maintenance_reports` | Laporan pemeliharaan: terhubung ke maintenance_task, asset, mechanic, operating_hour; menyimpan catatan dan bukti foto |
| `repair_requests` | Pengajuan perbaikan oleh mekanik: kondisi_perbaikan (ringan/sedang/berat), status_verifikasi (pending/approved/rejected) |
| `repair_reports` | Catatan perbaikan setelah pengajuan disetujui: catatan_temuan, cara_perbaikan |
| `knowledge_documents` | Dokumen sumber knowledge: source_type (asset/manual_book/maintenance_report/repair_report), status (pending/processed/failed) |
| `knowledge_chunks` | Potongan teks dari knowledge document; kolom `embedding vector(768)` untuk pgvector |
| `chat_histories` | Riwayat percakapan chatbot: question, answer, retrieved_context (JSON) |
| `retrieval_logs` | Log detail setiap retrieval: query, top_k_results (JSON), retrieval_time_ms |
| `retrieval_evaluations` | Hasil evaluasi: context_precision, context_recall, retrieval_time_ms |

### Catatan Penting Database
- `knowledge_chunks.embedding` adalah kolom `vector(768)` — membutuhkan PostgreSQL extension **pgvector**
- Index dibuat dengan `ivfflat (embedding vector_cosine_ops) WITH (lists = 100)` untuk cosine similarity search
- `knowledge_chunks` cascade delete saat `knowledge_documents` dihapus
- Database yang digunakan: **Neon DB** (cloud PostgreSQL, host di AWS ap-southeast-1)

---

## 8. Pipeline RAG

Alur ketika user mengirim pertanyaan ke `POST /api/chat/ask`:

```
1. ChatbotController::ask()
        │
        ▼
2. RagService::ask(question, assetId, topK)
        │
        ├── EmbeddingService::embedQuery(question)
        │     └── HTTP POST ke Python AI /api/v1/embed
        │           → array embedding 768 dimensi
        │
        ├── VectorSearchService::search(queryEmbedding, assetId, topK)
        │     └── SQL: SELECT ... FROM knowledge_chunks
        │               WHERE embedding IS NOT NULL [AND asset_id = ?]
        │               ORDER BY embedding <=> ?::vector   ← cosine distance
        │               LIMIT topK
        │           → array { chunk_text, distance, score, metadata }
        │
        └── LlmService::generateAnswer(question, contexts)
              └── Saat ini: menggabungkan 3 chunk teratas sebagai jawaban teks
                  (placeholder — belum terhubung ke LLM eksternal)
        │
        ▼
3. Simpan ChatHistory + RetrievalLog ke database
4. Return { answer, contexts, retrieval_time_ms, response_time_ms }
```

### Pipeline Ingestion (Memasukkan Dokumen ke Knowledge Base)

```
KnowledgeIngestionService::ingest*(...)
        │
        ├── Bangun teks dari sumber (profil aset / PDF / laporan)
        │     └── PdfExtractionService: download PDF dari Supabase → parse dengan smalot/pdfparser
        │
        ├── ChunkingService::chunk(text, maxLength=300, overlap=50)
        │     → array potongan teks
        │
        ├── EmbeddingService::embedTexts(chunks)
        │     → array embedding per chunk
        │
        └── INSERT INTO knowledge_chunks (embedding::vector) per chunk
              via raw SQL DB::statement()
```

**Auto-ingest** terjadi otomatis saat:
- Aset dibuat atau diperbarui (`AssetController`)
- Laporan pemeliharaan dibuat atau diperbarui (`MaintenanceReportController`)
- Catatan perbaikan dibuat atau diperbarui (`RepairReportController`)

---

## 9. Evaluasi Retrieval

`RetrievalEvaluationController::evaluate()` menjalankan retrieval dan menghitung:

- **Context Precision** = jumlah hasil relevan / total hasil yang dikembalikan
- **Context Recall** = 1 jika ada hasil relevan, 0 jika tidak

Relevansi ditentukan berdasarkan `source_type` dan opsional `source_id` pada metadata chunk.

---

## 10. Konfigurasi Environment (`.env`)

| Variabel | Nilai / Keterangan |
|---|---|
| `APP_URL` | `http://localhost:8000` |
| `FRONTEND_URL` | `http://localhost:3000` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | Neon DB (AWS ap-southeast-1) |
| `DB_DATABASE` | `neondb` |
| `DB_SSLMODE` | `require` |
| `SUPABASE_URL` | URL project Supabase |
| `SUPABASE_SERVICE_ROLE_KEY` | Service role key Supabase |
| `SUPABASE_BUCKET` | `manual-books` |
| `PYTHON_AI_BASE_URL` | `http://localhost:5000` |
| `SANCTUM_STATEFUL_DOMAINS` | `localhost,localhost:3000,...` |

---

## 11. Cara Menjalankan (Development)

### Prasyarat
- PHP 8.2+
- Composer
- PostgreSQL dengan extension **pgvector** (atau gunakan Neon DB cloud)
- Python AI microservice berjalan di port 5000

### Langkah

```bash
# 1. Clone dan masuk ke direktori
cd BACKEND

# 2. Install dependensi
composer install

# 3. Salin dan sesuaikan environment
cp .env.example .env
# Edit .env: isi DB_*, SUPABASE_*, PYTHON_AI_BASE_URL

# 4. Generate app key
php artisan key:generate

# 5. Jalankan migrasi + seeder
php artisan migrate
php artisan db:seed --class=UserSeeder

# 6. Buat symlink storage
php artisan storage:link

# 7. Jalankan server
php artisan serve
```

Server akan berjalan di `http://localhost:8000`.

### Akun Default (dari UserSeeder)

| Username | Password | Role |
|---|---|---|
| `teknik_01` | `password123` | teknik |
| `mekanik_01` | `password123` | mekanik |

---

## 12. Docker

```bash
# Build image
docker build -t backend-assetmanagement .

# Jalankan container
docker run -p 80:80 --env-file .env backend-assetmanagement
```

Dockerfile menggunakan `php:8.2-apache` dengan extension: `pdo`, `pdo_pgsql`, `pgsql`, `zip`, `mbstring`.

---

## 13. Testing

```bash
# Jalankan semua test
php artisan test

# Atau dengan Pest langsung
./vendor/bin/pest
```

Test tersedia di `tests/Feature/Auth/` untuk autentikasi.

---

## 14. Relasi Antar Model (Ringkasan)

```
User
 ├── hasMany MaintenanceTask (assigned_by, assigned_to)
 ├── hasMany MaintenanceReport (mechanic_id)
 ├── hasMany RepairRequest (mechanic_id, verified_by)
 ├── hasMany RepairReport (mechanic_id)
 ├── hasMany OperatingHour
 ├── hasMany ChatHistory
 └── hasMany RetrievalLog

Asset
 ├── hasMany OperatingHour
 ├── hasMany MaintenanceTask
 ├── hasMany MaintenanceReport
 ├── hasMany RepairRequest
 ├── hasMany RepairReport
 ├── hasMany KnowledgeDocument
 ├── hasMany KnowledgeChunk
 └── hasMany ChatHistory

MaintenanceTask → hasOne MaintenanceReport
RepairRequest   → hasOne RepairReport
KnowledgeDocument → hasMany KnowledgeChunk
ChatHistory       → hasMany RetrievalLog
```

---

## 15. Catatan Khusus Pengembangan

- **LlmService** saat ini adalah placeholder: jawaban diambil langsung dari teks chunk, belum memanggil LLM eksternal (misal: OpenAI, Groq, dsb.). Integrasi LLM sesungguhnya ada di Python AI microservice atau perlu ditambahkan.
- **CORS** dikonfigurasi untuk mengizinkan origin dari `FRONTEND_URL` (`localhost:3000`).
- **QR Code** dibuat dalam format SVG dan disimpan di `storage/public/assets/qrcodes/`. URL QR code dapat diakses publik tanpa login.
- **Foto kondisi dan bukti foto** disimpan di storage lokal Laravel (`storage/app/public`), bukan Supabase.
- **Manual book (PDF)** satu-satunya file yang disimpan di Supabase Storage karena diperlukan untuk proses ekstraksi teks server-side.
- Ingestion yang gagal **tidak membatalkan** pembuatan aset/laporan (error di-log dan dilaporkan secara background via `report()`).
