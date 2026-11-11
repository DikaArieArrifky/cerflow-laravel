# SPESIFIKASI LOG PENELITIAN & ANALITIK PERILAKU SISWA (CERFLOW)

Dokumen ini merupakan panduan teknis perekaman dan analisis log interaksi pengguna (*user activity logs*) pada sistem pembelajaran CERFlow untuk kebutuhan skripsi dan penelitian pendidikan.

---

## 1. Prinsip Dasar Perekaman Log Penelitian
1. **Integritas Raw Data:** Tabel `user_logs` hanya mencatat peristiwa nyata yang dipicu langsung oleh siswa (*raw user actions*).
2. **Non-Destructive Analytics:** Seluruh metrik turunan (*derived features*) seperti jarak tempuh, durasi membaca, dan jumlah kartu unik dihitung secara dinamis oleh `ActivityAnalyticsService` tanpa mengubah atau menambahkan baris baru ke dalam raw log.
3. **Pencegahan Log Palsu (*False Logs Elimination*):**
   - Inisialisasi awal (*initial card rendering*) **TIDAK** menghasilkan log `move`.
   - Pemulihan sesi dari `sessionStorage` **TIDAK** menghasilkan log `move`.
   - Penataan ulang otomatis (*auto arrange*) menghasilkan tepat **1 event `auto_arrange`** dan **0 event `move`**.
   - Pelepasan kartu pada posisi semula (`from == to`) ditolak oleh sistem dan **TIDAK** dicatat sebagai perpindahan.
   - Perubahan koneksi yang menggantikan koneksi lama menghasilkan urutan: `disconnect` (koneksi lama) kemudian `connect` (koneksi baru).

---

## 2. Definisi Raw Action Types & Skema Payload

### 2.1 MOVE (Perpindahan Kartu)
Dicatat saat siswa menarik dan memindahkan kartu ke indeks urutan baru dalam kolomnya.

**Skema Raw Payload (`action_data`):**
```json
{
  "card": "e-4",
  "card_type": "evidence",
  "from": 3,
  "to": 1
}
```

- `card`: Identifier unik kartu (contoh: `c-1` untuk Claim triplet 1, `e-4` untuk Evidence triplet 4, `r-2` untuk Reasoning triplet 2).
- `card_type`: Kategori komponen ilmiah (`claim`, `evidence`, `reasoning`). Jika klien lawas tidak menyertakan field ini, sistem backend secara otomatis menginferensi dari awalan huruf (`c-` = claim, `e-` = evidence, `r-` = reasoning).
- `from`: Indeks baris awal (0-indexed).
- `to`: Indeks baris tujuan (0-indexed).

**Metrik Turunan (*Derived Features*):**
- **`move_count`:** Jumlah total event `action_type = 'move'`.
- **`total_move_distance`:** `SUM(abs(to - from))` untuk seluruh pergerakan dalam sesi.
- **`unique_moved_cards`:** Jumlah unik kartu (`COUNT(DISTINCT card)`) yang pernah dipindahkan siswa.
- **`claim_move_count`:** Jumlah pergerakan kartu tipe Claim.
- **`evidence_move_count`:** Jumlah pergerakan kartu tipe Evidence.
- **`reasoning_move_count`:** Jumlah pergerakan kartu tipe Reasoning.

---

### 2.2 CONNECT (Pembuatan Hubungan Argumentasi)
Dicatat saat siswa menarik garis hubungan antara kartu Claim ke Evidence atau Evidence ke Reasoning.

**Skema Raw Payload (`action_data`):**
```json
{
  "from": "c-1",
  "to": "e-1"
}
```

**Metrik Turunan:**
- **`connect_count`:** Jumlah total event `action_type = 'connect'`.

---

### 2.3 DISCONNECT (Pemutusan Hubungan Argumentasi)
Dicatat saat siswa mengklik garis koneksi untuk menghapusnya, atau saat sistem mengganti koneksi lama karena dibuatnya koneksi baru dari terminal yang sama.

**Skema Raw Payload (`action_data`):**
```json
{
  "from": "c-1",
  "to": "e-2"
}
```

**Metrik Turunan:**
- **`disconnect_count`:** Jumlah total event `action_type = 'disconnect'`.

---

### 2.4 AUTO ARRANGE (Penataan Otomatis Posisi Kartu)
Dicatat saat siswa menekan tombol "Auto Arrange" untuk menyelaraskan ketinggian kartu yang saling terhubung secara visual.

**Skema Raw Payload (`action_data`):**
```json
{}
```

**Metrik Turunan:**
- **`auto_arrange_count`:** Jumlah total penekanan tombol auto arrange.

---

### 2.5 FEEDBACK (Pemeriksaan Umpan Balik Visual)
Dicatat saat siswa mengaktifkan atau menonaktifkan sakelar *Visual Feedback* untuk memeriksa kebenaran koneksi sementara.

**Skema Raw Payload (`action_data`):**
```json
{
  "active": true
}
```

**Metrik Turunan:**
- **`feedback_count`:** Frekuensi siswa memanfaatkan fitur scaffolding bantuan evaluasi visual.

---

### 2.6 VIEW READING & READING CLOSE (Aktivitas Membaca Teks)
- `view_reading`: Dicatat saat siswa membuka jendela pop-up teks bacaan ilmiah.
- `reading_close`: Dicatat saat siswa menutup jendela pop-up teks bacaan.

**Skema Raw Payload (`action_data`):**
```json
{
  "map_id": 1
}
```

**Metrik Turunan:**
- **`reading_count`:** Jumlah pembukaan dialog bacaan (`COUNT(action_type = 'view_reading')`).
- **`reading_duration_seconds`:** Total waktu membaca teks dalam satuan detik, dihitung dengan memasangkan interval waktu antara `view_reading` dan `reading_close` yang bersesuaian secara kronologis:
  $$\text{duration} = \sum |t_{\text{reading\_close}} - t_{\text{view\_reading}}|$$
- **`has_incomplete_reading`:** Nilai boolean (`true`/`false`) yang menandakan apakah dialog bacaan pernah dibuka namun belum ditutup hingga akhir log. Durasi tidak lengkap tidak dihitung sebagai estimasi palsu.

---

### 2.7 COMPLETION TIME (Durasi Pengerjaan Keseluruhan)
Dihitung berdasarkan selisih waktu antara dimulainya sesi belajar dan waktu submisi akhir:
$$\text{completion\_time\_seconds} = |t_{\text{scores.submitted\_at}} - t_{\text{user\_sessions.start\_time}}|$$

Jika sesi belum dikumpulkan (`is_submitted = false`), nilainya adalah `null`.

---

## 3. Ringkasan Kamus Metrik Analisis Skripsi

| Variabel Analisis | Tipe Data | Sumber Data / Formula | Makna Indikator Perilaku Belajar |
|---|---|---|---|
| `total_events` | Integer | Total baris log pada sesi | Tingkat keaktifan umum siswa dalam bereksplorasi pada kanvas |
| `move_count` | Integer | Jumlah event `move` | Frekuensi reorganisasi urutan ide/klaim/bukti/penalaran |
| `total_move_distance` | Integer | `SUM(abs(to - from))` | Intensitas pergeseran tata letak pemikiran siswa |
| `unique_moved_cards` | Integer | `COUNT(DISTINCT card)` | Keragaman kartu yang ditinjau dan ditata oleh siswa |
| `claim_move_count` | Integer | `move` kartu Claim | Fokus pengurutan pada pernyataan klaim |
| `evidence_move_count` | Integer | `move` kartu Evidence | Fokus pengurutan pada bukti empiris |
| `reasoning_move_count` | Integer | `move` kartu Reasoning | Fokus pengurutan pada penjelasan logika penghubung |
| `connect_count` | Integer | Jumlah event `connect` | Pembentukan hipotesis keterhubungan antar komponen |
| `disconnect_count` | Integer | Jumlah event `disconnect` | Refleksi diri atau revisi kesalahan keterhubungan |
| `auto_arrange_count` | Integer | Jumlah event `auto_arrange` | Pemanfaatan alat bantu visual untuk memudahkan pembacaan alur |
| `feedback_count` | Integer | Jumlah event `feedback` | Tingkat ketergantungan atau pengecekan mandiri terhadap umpan balik |
| `reading_count` | Integer | Jumlah event `view_reading` | Kebutuhan siswa merujuk kembali ke sumber bacaan utama |
| `reading_duration_seconds`| Integer | Durasi kumulatif `view_reading` $\rightarrow$ `reading_close` | Alokasi waktu membaca pemahaman teks ilmiah |
| `completion_time_seconds` | Integer | Waktu submit - Waktu mulai | Kecepatan penuntasan tugas pemecahan masalah |
| `score` | Float | `(correct_connections / (triplet_count * 2)) * 100` | Ketepatan argumentasi ilmiah akhir siswa |

---

## 4. Panduan Ekspor Data Riset
Halaman **Log Aktivitas Admin** (`/admin/logs`) menyediakan tombol ekspor CSV/Excel berstandar penelitian ilmiah yang langsung memuat ke-15 kolom metrik di atas untuk setiap sesi pengerjaan siswa. Data hasil ekspor siap dianalisis menggunakan perangkat lunak statistik seperti SPSS, R, atau Python Pandas.
