# MIGRATION REPORT: CERFlow PHP Native → Laravel 11

## 1. Ringkasan Migrasi
Migrasi aplikasi media pembelajaran dan riset **CERFlow** (Claim, Evidence, Reasoning) dari arsitektur PHP Native ke Laravel 11.57.0 (PHP 8.3) telah berhasil diselesaikan secara penuh (Phase 1 s.d. Phase 9) dengan mempertahankan 100% perilaku sistem (behavior compatibility), integritas data penelitian, format log interaksi pengguna, tata letak antarmuka, serta keamanan berbasis sesi dan CSRF token.

- **Source of Truth:** Folder `webCerflow` (PHP Native) dan basis data `cerflow` tetap berada dalam kondisi **READ-ONLY** tanpa modifikasi apa pun.
- **Target Application:** `cerflow-laravel` berjalan mandiri di atas basis data `cer_flow_laravel` menggunakan MySQL.
- **Hasil Pengujian Otomatis:** **98 tests passed**, **417 assertions**, **0 failed**.

---

## 2. Struktur Laravel Final
```
cerflow-laravel/
├── app/
│   ├── Console/Commands/
│   │   └── ImportLegacyDataCommand.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── LogController.php
│   │   │   │   ├── MapController.php
│   │   │   │   ├── PreviewController.php
│   │   │   │   ├── ReportController.php
│   │   │   │   ├── StudioController.php
│   │   │   │   └── UserController.php
│   │   │   ├── Api/
│   │   │   │   ├── AIController.php
│   │   │   │   ├── LogController.php
│   │   │   │   ├── MapController.php
│   │   │   │   ├── ScoreController.php
│   │   │   │   └── SessionController.php
│   │   │   ├── Student/
│   │   │   │   ├── HistoryController.php
│   │   │   │   └── LearningController.php
│   │   │   ├── AuthController.php
│   │   │   └── MapViewController.php
│   │   ├── Middleware/
│   │   │   └── RoleMiddleware.php
│   │   └── Requests/
│   │       ├── StoreActivityLogRequest.php
│   │       ├── StoreCerMapRequest.php
│   │       ├── StoreScoreRequest.php
│   │       └── StoreUserSessionRequest.php
│   ├── Models/
│   │   ├── CerMap.php
│   │   ├── Score.php
│   │   ├── Setting.php
│   │   ├── Triplet.php
│   │   ├── User.php
│   │   ├── UserLog.php
│   │   └── UserSession.php
│   └── Services/
│       ├── ActivityAnalyticsService.php
│       ├── ActivityLogService.php
│       ├── AIService.php
│       ├── CerMapService.php
│       └── ScoringService.php
├── database/migrations/
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 0001_01_01_000001_create_cer_maps_table.php
│   ├── 0001_01_01_000002_create_triplets_table.php
│   ├── 0001_01_01_000003_create_user_sessions_table.php
│   ├── 0001_01_01_000004_create_user_logs_table.php
│   ├── 0001_01_01_000005_create_scores_table.php
│   └── 0001_01_01_000006_create_settings_table.php
├── public/js/
│   ├── admin.js
│   ├── api-client.js
│   ├── dialog-utils.js
│   └── siswa.js
├── resources/views/
│   ├── admin/
│   │   ├── log-detail.blade.php
│   │   ├── logs.blade.php
│   │   ├── maps.blade.php
│   │   ├── preview-map.blade.php
│   │   ├── report.blade.php
│   │   ├── settings.blade.php
│   │   ├── studio.blade.php
│   │   └── users.blade.php
│   ├── auth/login.blade.php
│   ├── components/navbar.blade.php
│   ├── layouts/app.blade.php
│   ├── shared/view-map.blade.php
│   └── student/
│       ├── history.blade.php
│       ├── index.blade.php
│       └── learning.blade.php
└── routes/
    └── web.php
```

---

## 3. Mapping PHP Native → Laravel

| File / Modul PHP Native | Target Laravel 11 | Deskripsi & Catatan |
|---|---|---|
| `login.php`, `auth_check.php` | `AuthController.php`, `RoleMiddleware.php` | Otentikasi sesi standar Laravel & proteksi role (`guru`, `siswa`). |
| `users.php` | `Admin/UserController.php`, `views/admin/users.blade.php` | User Management, CRUD, CSV Template & Import dengan validasi. |
| `maps.php` | `Admin/MapController.php`, `views/admin/maps.blade.php` | Content Hub materi CER dengan statistik triplet dan status modul. |
| `preview_map.php` | `Admin/PreviewController.php`, `views/admin/preview-map.blade.php` | Tampilan kunci jawaban eksklusif guru tanpa mencatat log siswa. |
| `admin.php` | `Admin/StudioController.php`, `views/admin/studio.blade.php` | CER Studio: editor interaktif pembuatan materi, klaim, bukti, penalaran. |
| `report.php` | `Admin/ReportController.php`, `views/admin/report.blade.php` | Analisis hasil nilai dan skor siswa per modul dengan filter dan ekspor. |
| `logs.php` | `Admin/LogController.php`, `views/admin/logs.blade.php` | Pemantauan aktivitas belajar dan ringkasan metrik penelitian per sesi. |
| *(Baru di Laravel)* | `views/admin/log-detail.blade.php` | Analisis detail pergerakan kartu dan timeline kronologis raw events. |
| `view_map.php` | `MapViewController.php`, `views/shared/view-map.blade.php` | Rekonstruksi visual diagram koneksi hasil pengumpulan siswa. |
| `siswa.php` | `Student/LearningController.php`, `views/student/learning.blade.php` | Kanvas rekonstruksi argumentasi interaktif CER tiga kolom siswa. |
| `api.php` | `Api/MapController`, `SessionController`, `LogController`, `ScoreController`, `AIController` | RESTful API terstandar dengan validasi FormRequest dan CSRF token. |
| `assets/js/admin.js` | `public/js/admin.js`, `public/js/api-client.js` | Helper client API dengan CSRF token dan pengelolaan state studio. |
| `assets/js/siswa.js` | `public/js/siswa.js` | Integrasi SortableJS, rendering kurva koneksi SVG, dan penataan otomatis. |

---

## 4. Models & Database Eloquent
1. **User (`users`):** Menampung data pengguna (`username`, `namalengkap`, `password`, `role`, `gemini_api_key`). Password menggunakan mutator `Hash::make()` dengan perlindungan pencegahan double-hashing.
2. **CerMap (`cer_maps`):** Materi ajar CER (`title`, `reading_text`, `allow_feedback`, `allow_reading`). Memiliki relasi `hasMany` ke `Triplet` dan `UserSession`.
3. **Triplet (`triplets`):** Komponen inti argumentasi ilmiah (`claim`, `evidence`, `reasoning`). Berelasi `belongsTo` ke `CerMap`.
4. **UserSession (`user_sessions`):** Sesi pengerjaan siswa (`user_id`, `map_id`, `start_time`, `is_submitted`).
5. **UserLog (`user_logs`):** Perekaman raw event penelitian (`session_id`, `action_type`, `action_data`, `created_at`). `action_data` di-cast secara otomatis ke PHP array.
6. **Score (`scores`):** Hasil akhir penilaian (`user_id`, `map_id`, `session_id`, `score`, `submitted_at`, `map_data`). `map_data` menyimpan snapshot visual rekonstruksi siswa.
7. **Setting (`settings`):** Konfigurasi global sistem (`setting_key`, `setting_value`).

---

## 5. Controllers
- **`AuthController`:** Mengelola alur login dan logout berbasis sesi, mencegah session fixation melalui `regenerate()`.
- **`Admin\UserController`:** Mengelola CRUD user, validasi form, import CSV dalam database transaction, dan download format template CSV.
- **`Admin\MapController`:** Mengelola Content Hub materi dengan eager loading count `triplets`.
- **`Admin\PreviewController`:** Menyajikan kanvas kunci jawaban modul untuk guru.
- **`Admin\StudioController`:** Mengelola ruang kerja penyuntingan CER Map dan teks bacaan.
- **`Admin\ReportController`:** Menyajikan rekapitulasi skor siswa dengan relasi lengkap tanpa masalah N+1 query.
- **`Admin\LogController`:** Menyajikan ringkasan analitik sesi interaksi serta visualisasi mendalam (timeline & movement analysis).
- **`Student\LearningController`:** Menyajikan pemilihan topik materi dan kanvas pengerjaan rekonstruksi CER bagi siswa.
- **`Student\HistoryController`:** Menyajikan riwayat pengerjaan mandiri siswa yang telah dikumpulkan.
- **`MapViewController`:** Menampilkan hasil akhir rekonstruksi siswa dengan proteksi IDOR ketat.
- **`Api\AIController`:** Melayani permintaan live Gemini AI extraction dan pengujian kredensial API key.

---

## 6. Services Layer
- **`CerMapService`:** Menangani atomic save/update pada modul materi dan rekonsiliasi triplet (insert new, update existing, delete removed).
- **`ActivityLogService`:** Memvalidasi hak kepemilikan sesi, status sesi belum submit, memverifikasi pergerakan kartu (`from != to`), serta menginferensi jenis kartu (`claim`, `evidence`, `reasoning`).
- **`ScoringService`:** Sumber kebenaran tunggal (*Single Source of Truth*) perhitungan nilai akhir siswa berdasarkan kecocokan triplet di sisi server.
- **`ActivityAnalyticsService`:** Menghitung seluruh metrik turunan penelitian secara dinamis tanpa merusak integritas raw data log (durasi bacaan, jarak pergerakan, kartu unik, timeline, durasi pengerjaan).
- **`AIService`:** Integrasi HTTP Client Laravel dengan Google Gemini API (Flash Lite / Flash 3.6 fallback) untuk ekstraksi CER otomatis.

---

## 7. Routes Architecture
Seluruh endpoint dilindungi oleh middleware sesi web Laravel, pencegahan pembajakan sesi, dan CSRF protection:
- **Guest:** `/login` (GET/POST)
- **Shared Auth:** `/logout`, `/view-map/{score}` (dengan pengecekan hak milik siswa/guru)
- **Role Guru (`/admin/*`):** `/admin/dashboard`, `/admin/studio`, `/admin/maps`, `/admin/preview/{map}`, `/admin/users`, `/admin/report`, `/admin/logs`, `/admin/logs/{session}`, `/admin/settings`
- **Role Siswa (`/student/*`):** `/student`, `/student/history`
- **REST API (`/api/*`):**
  - Guru: `POST /api/maps`, `GET /api/maps/{map}`, `DELETE /api/maps/{map}`, `POST /api/ai/extract`, `POST /api/ai/test`
  - Siswa: `POST /api/sessions`, `POST /api/logs`, `POST /api/scores`
  - Bersama: `GET /api/maps`, `GET /api/maps/{map}/triplets`

---

## 8. Authentication & Role Authorization
- Otentikasi menggunakan guard sesi bawaan Laravel (`auth`).
- `RoleMiddleware` secara ketat memisahkan akses hak istimewa antara guru dan siswa. Siswa yang mencoba mengakses route admin akan menerima respons `403 Forbidden`.
- Proteksi IDOR menjamin siswa hanya dapat mencatat log pada sesinya sendiri, mengumpulkan nilai pada sesinya sendiri, dan melihat rekonstruksi peta miliknya sendiri.

---

## 9. Alur Rekonstruksi Interaktif Siswa (CER Canvas)
1. Siswa memilih materi di `/student`.
2. Sistem membuat `user_sessions` baru via `POST /api/sessions`.
3. Komponen kartu Claim, Evidence, dan Reasoning dimuat dan diacak urutan awalnya.
4. Siswa menghubungkan kartu (`c-*` → `e-*` dan `e-*` → `r-*`).
5. Setiap tindakan koneksi, pemutusan koneksi, pengurutan kartu, pembukaan dialog bacaan, dan pengaktifan visual feedback direkam secara presisi ke `POST /api/logs`.
6. Saat submit, `POST /api/scores` mengirimkan data koneksi yang langsung diverifikasi dan dinilai oleh `ScoringService` di server.

---

## 10. Research Logging & Movement Analytics
- **Move Log Compatibility:** Hanya pergerakan nyata (`from != to`) yang dicatat. Peristiwa awal render, auto arrange, atau resize layar tidak menghasilkan log perpindahan kartu palsu.
- **Reading Duration:** Dihitung secara objektif dengan memasangkan event pembukaan dialog (`view_reading`) dan penutupan dialog (`reading_close`). Sesi dengan dialog yang tidak ditutup ditandai dengan flag `has_incomplete_reading`.
- **Movement Distance:** Dihitung dari `abs(to - from)` untuk mengukur besarnya reorganisasi posisi pemikiran siswa.

---

## 11. Integrasi Gemini AI
- Implementasi penuh di `AIService` menggunakan `Illuminate\Support\Facades\Http`.
- Kredensial API Key diutamakan dari akun guru yang sedang login, dengan fallback ke pengaturan global aplikasi dan konfigurasi environment.
- Fitur AI Extractor pada CER Studio memungkinkan guru menempelkan artikel ilmiah dan secara otomatis mengekstrak pasangan Claim, Evidence, dan Reasoning ke dalam bentuk triplet siap ajar.

---

## 12. Migrasi Data Warisan (Legacy Importer)
Artisan command `php artisan cerflow:import-legacy`:
- Terhubung ke basis data `cerflow` dalam mode **READ-ONLY**.
- Mendukung mode `--dry-run` untuk inspeksi jumlah baris tanpa risiko penulisan.
- Mode `--execute --force` memigrasikan tabel dengan menjaga keutuhan Foreign Key:
  - `users`: 5 baris
  - `cer_maps`: 4 baris
  - `triplets`: 12 baris
  - `user_sessions`: 42 baris
  - `user_logs`: 155 baris
  - `scores`: 7 baris
  - `settings`: 1 baris
- Integritas Foreign Key terverifikasi 100% tanpa adanya orphaned records. Hash password bcrypt lama tetap dapat digunakan untuk login tanpa perlu rehash.

---

## 13. Hasil Uji Otomatis & Regression
Pengujian menyeluruh dijalankan menggunakan PHPUnit/Pest:
- Total Test Files: 11
- Total Tests: **98 Passed**
- Total Assertions: **417 Assertions**
- Total Failed: **0**
- Execution Duration: **~4 detik**

---

## 14. Keterbatasan & Rekomendasi Masa Depan
1. **Frontend Assets:** CDN TailwindCSS, SortableJS, dan SheetJS saat ini dipertahankan demi menjaga kompatibilitas tampilan 100% dengan PHP native. Di masa mendatang, pustaka ini dapat dibundel secara lokal menggunakan Vite.
2. **Paging Aktivitas:** Untuk institusi dengan puluhan ribu sesi, disarankan penambahan query pagination (`paginate(25)`) pada halaman LogController dan ReportController.
