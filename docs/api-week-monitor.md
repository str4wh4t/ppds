# API — Week Monitors (beban kerja per minggu)

Dokumen ini menjelaskan **dua** endpoint terkait tabel `week_monitors`:

| Endpoint | Route name | Fungsi singkat |
|----------|------------|----------------|
| `POST /api/v1/week-monitors` | `weekmonitor.index` | Daftar + filter + pagination (selaras UI web). |
| `POST /api/v1/week-monitors/by-week` | `weekmonitor.by-week` | **Satu** rekaman untuk pasangan `week_group_id` + pemilik user. |

**Controller:** `App\Http\Controllers\Api\V1\WeekMonitorController` (`index`, `byWeek`).

Base URL contoh: `http://localhost:8000` → path API: `http://localhost:8000/api/v1/...`.

Detail **list** ada di bagian **§1–§5**; detail **`by-week`** di **§6**.

---

## Scramble / dokumentasi OpenAPI (`/docs/api`)

Dokumentasi interaktif memakai **Dedoc Scramble**. Schema respons di UI diambil dari atribut PHP `#[\Dedoc\Scramble\Attributes\Response]` pada `App\Http\Controllers\Api\V1\WeekMonitorController`, dengan **tipe PHPStan yang disengaja dibuat ringkas** — pola yang sama dipakai di **`Api\V1\ActivityController`** (mis. `list`, `store`).

**Mengapa tidak seluruh field nested ada di schema?** Tipe `type: '...'` yang terlalu dalam (mis. `user` → `student_unit` bertingkat) atau sintaks yang rumit (`errors?:`, `array<string, …>`) bisa membuat Scramble gagal mem-parse sehingga muncul **“No schema defined”**. Oleh karena itu:

| Endpoint | Yang tampil di **schema OpenAPI** (ringkas) | Yang tetap ada di **JSON respons HTTP** |
|----------|---------------------------------------------|------------------------------------------|
| `POST /api/v1/week-monitors` | Item `data[]`: kolom utama **`WeekMonitor`** | Sama + objek **`user`** dan **`user.student_unit`** |
| `POST /api/v1/week-monitors/by-week` | Properti `data`: kolom utama **`WeekMonitor`** | Sama + **`user`** dan **`student_unit`** bersarang di bawah `user` |

**Sumber kebenaran untuk bentuk lengkap:** contoh JSON di dokumen ini (§3 dan §6) serta blok **`@response`** pada method `index` dan `byWeek` di controller.

---

## 1. Ringkasan (endpoint list)

- Filter mengikuti kebiasaan UI web: tahun, bulan, kategori jam kerja, pencarian nama (`fullname`), dan filter prodi lewat `units` (JSON string).
- **Role `student`**: data **selalu** untuk user dari token; parameter `user_id` diabaikan.
- **Role `system`**: boleh memilih user lain lewat `user_id` (opsional); jika tidak dikirim, dipakai user token.
- **Role lain** (bukan `student` dan bukan `system`): `targetUserId` = user token (sama seperti baris 83–85 di controller — tidak ada `user_id` khusus).

Query memuat relasi `user` dan `user.studentUnit`. Urutan: `year` ↑, `month` ↑, `week_month` ↑.

---

## 2. Request

### Headers

| Header | Wajib | Keterangan |
|--------|--------|------------|
| `Authorization` | Ya | `Bearer <access_token>` |
| `Accept` | Disarankan | `application/json` |
| `Content-Type` | Disarankan | `application/json` |

### Body (JSON)

Semua field **opsional** kecuali kebutuhan filter Anda.

| Field | Tipe | Validasi | Keterangan |
|-------|------|----------|------------|
| `user_id` | integer | `exists:users,id` | Hanya relevan untuk role **`system`**. Untuk `student` diabaikan. |
| `search` | string | max 255 | `LIKE` pada `users.fullname` (lewat relasi `user`). |
| `units` | string | — | String JSON array. Setiap elemen bisa string nama unit atau objek dengan key `name`. Diterjemahkan ke `whereIn` pada `student_unit.name`. |
| `yearSelected` | integer | — | Filter kolom `year`. |
| `monthIndexSelected` | integer | 1–12 | Filter kolom `month`. |
| `categoryWorkloadSelected` | integer | 1, 2, atau 3 | Filter `workload_hours`: **1** = &lt; 71, **2** = 71–80 (inklusif), **3** = &gt; 80. |
| `per_page` | integer | 1–50 | Default **10**. |

#### Contoh `units` (JSON string)

Array nama prodi:

```json
"[\"Neurologi\",\"Interna\"]"
```

Atau array objek (diambil field `name`):

```json
"[{\"name\":\"Neurologi\"},{\"name\":\"Interna\"}]"
```

---

## 3. Respons sukses (`200 OK`)

Body mengikuti **serialisasi paginator Laravel** (`LengthAwarePaginator`): selain array `data`, ada metadata halaman (`current_page`, `per_page`, `total`, `last_page`, `links`, `path`, dll.).

Schema di halaman **Scramble** untuk tiap elemen `data[]` biasanya hanya merinci **kolom `WeekMonitor`**; field `user` / `student_unit` tetap ikut di JSON (lihat contoh di bawah dan bagian **Scramble** di atas).

### Isi tiap item di `data`

Field mengikuti model `WeekMonitor` + relasi yang di-load. Antara lain:

| Field | Keterangan |
|-------|------------|
| `id` | ID `week_monitors` |
| `user_id` | Pemilik rekaman |
| `week_group_id` | Jika ada di DB |
| `year`, `month`, `week` | Komponen waktu |
| `week_month` | Indeks minggu dalam bulan (integer, sesuai logika `CreateActivityService`) |
| `workload` | Representasi durasi (string) |
| `workload_hours` | Jam agregat (integer), dipakai filter kategori |
| `workload_hours_not_allowed`, `workload_as_seconds` | Jika kolom ada |
| `user` | Objek user (ter-load) |
| `user.student_unit` | Unit prodi mahasiswa jika ada |

### Contoh bentuk (mendekati respons nyata)

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "user_id": 2,
      "week_group_id": 202614,
      "year": 2026,
      "month": 4,
      "week": 14,
      "week_month": 2,
      "workload": "72:30:00",
      "workload_hours": 72,
      "workload_hours_not_allowed": 0,
      "workload_as_seconds": 261000,
      "created_at": "2026-04-01T08:00:00.000000Z",
      "updated_at": "2026-04-08T10:15:00.000000Z",
      "user": {
        "id": 2,
        "username": "mhs01",
        "fullname": "Nama Mahasiswa",
        "identity": "1234567890",
        "semester": 4,
        "email": "mhs@example.test",
        "student_unit_id": 1,
        "dosbing_user_id": 10,
        "doswal_user_id": 11,
        "email_verified_at": null,
        "created_at": "2025-01-10T00:00:00.000000Z",
        "updated_at": "2026-04-01T00:00:00.000000Z",
        "student_unit": {
          "id": 1,
          "name": "Neurologi",
          "kaprodi_user_id": 5,
          "admin_user_id": 6,
          "guideline_document_path": "units/guideline.pdf",
          "created_at": "2024-01-01T00:00:00.000000Z",
          "updated_at": "2024-06-01T00:00:00.000000Z"
        }
      }
    }
  ],
  "first_page_url": "http://localhost:8000/api/v1/week-monitors?page=1",
  "from": 1,
  "last_page": 3,
  "last_page_url": "...",
  "links": [],
  "next_page_url": "...",
  "path": "http://localhost:8000/api/v1/week-monitors",
  "per_page": 10,
  "prev_page_url": null,
  "to": 10,
  "total": 25
}
```

Struktur pasti `links`/`path` mengikuti environment dan query string pagination Laravel.

---

## 4. Respons error

| HTTP | Kondisi | Body (umum) |
|------|---------|--------------|
| `401` | Token tidak valid / tidak ada | `{ "message": "Unauthenticated." }` |
| `422` | Validasi gagal | `{ "message": "...", "errors": { ... } }` |

---

## 5. Contoh cURL

```bash
curl -s -X POST "http://localhost:8000/api/v1/week-monitors" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -d '{
    "yearSelected": 2026,
    "monthIndexSelected": 4,
    "categoryWorkloadSelected": 2,
    "per_page": 15
  }'
```

---

## 6. Satu rekaman — `POST /api/v1/week-monitors/by-week`

| | |
|---|---|
| **Method & path** | `POST /api/v1/week-monitors/by-week` |
| **Nama route Laravel** | `weekmonitor.by-week` |
| **Autentikasi** | Laravel Sanctum (`auth:sanctum`) |

Mengambil **tepat satu** baris `week_monitors` yang cocok dengan `week_group_id` dan pemilik user (lihat aturan `user_id` di bawah). Cocok untuk layar detail minggu tertentu (nilai `week_group_id` sama dengan yang dipakai di agregasi activity / kalender, mis. gabungan ISO week year + ISO week number).

Di **Scramble**, schema untuk `data` biasanya hanya menampilkan **kolom `WeekMonitor`**; respons HTTP tetap menyertakan **`user`** dan **`student_unit`** (lihat contoh JSON di bawah dan bagian **Scramble** di awal dokumen).

### Headers

| Header | Wajib | Keterangan |
|--------|--------|------------|
| `Authorization` | Ya | `Bearer <access_token>` |
| `Accept` | Disarankan | `application/json` |
| `Content-Type` | Disarankan | `application/json` |

### Body (JSON)

| Field | Wajib | Tipe | Keterangan |
|--------|--------|------|------------|
| `week_group_id` | Ya | integer | Harus sama dengan kolom `week_group_id` di DB (contoh: `202615` = tahun ISO minggu 2026 + minggu ke-15). |
| `user_id` | Tergantung role | integer | `exists:users,id` bila dikirim. Lihat aturan di bawah. |

### Aturan `user_id` (wajib dibaca)

| Role pemanggil | Field `user_id` | `user_id` yang dipakai query |
|----------------|-----------------|------------------------------|
| **student** | **Tidak usah dikirim** (opsional; jika dikirim **diabaikan**) | Selalu **id user dari token** |
| **Bukan student** (`system`, `admin_fakultas`, `admin_prodi`, `dosen`, …) | **Wajib** | Nilai dari body |

Validasi: jika bukan student dan `user_id` kosong → **`422`** dengan pesan setara *"Field user_id wajib untuk role selain student."*

### Respons sukses (`200 OK`)

Satu objek dibungkus properti `data` (bukan paginator). Bentuk di bawah selaras dengan **`@response 200`** pada method `byWeek` di controller.

```json
{
  "data": {
    "id": 42,
    "user_id": 12,
    "week_group_id": 202615,
    "year": 2026,
    "month": 4,
    "week": 15,
    "week_month": 2,
    "workload": "40:15:30",
    "workload_hours": 40,
    "workload_hours_not_allowed": 2,
    "workload_as_seconds": 144930,
    "created_at": "2026-04-01T08:00:00.000000Z",
    "updated_at": "2026-04-08T16:45:00.000000Z",
    "user": {
      "id": 12,
      "username": "mhs01",
      "fullname": "Nama Mahasiswa",
      "identity": "1234567890",
      "semester": 4,
      "email": "mhs@example.test",
      "student_unit_id": 1,
      "dosbing_user_id": 10,
      "doswal_user_id": 11,
      "email_verified_at": null,
      "created_at": "2025-01-10T00:00:00.000000Z",
      "updated_at": "2026-04-01T00:00:00.000000Z",
      "student_unit": {
        "id": 1,
        "name": "Neurologi",
        "kaprodi_user_id": 5,
        "admin_user_id": 6,
        "guideline_document_path": "units/guideline.pdf",
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-06-01T00:00:00.000000Z"
      }
    }
  }
}
```

Field mengikuti model `WeekMonitor` + relasi `user` dan `user.studentUnit` yang di-`with()` di server (`password` user tidak ikut).

### Respons error (`by-week`)

| HTTP | Kondisi | Body (contoh) |
|------|---------|----------------|
| `401` | Tidak terautentikasi | `{ "message": "Unauthenticated." }` |
| `404` | Tidak ada baris untuk `user_id` + `week_group_id` | `{ "message": "Week monitor tidak ditemukan." }` |
| `422` | Validasi gagal (mis. `week_group_id` hilang, atau non-student tanpa `user_id`) | `{ "message": "...", "errors": { ... } }` |

### Contoh body request

**Student** (cukup `week_group_id`):

```json
{
  "week_group_id": 202615
}
```

**Bukan student** (`user_id` wajib — biasanya mahasiswa yang dimaksud):

```json
{
  "week_group_id": 202615,
  "user_id": 12
}
```

### Contoh cURL

**Student:**

```bash
curl -s -X POST "http://localhost:8000/api/v1/week-monitors/by-week" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -d '{"week_group_id":202615}'
```

**Bukan student:**

```bash
curl -s -X POST "http://localhost:8000/api/v1/week-monitors/by-week" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -d '{"week_group_id":202615,"user_id":12}'
```

---

## 7. Lihat juga

- Pola umum API (body JSON, status code): `docs/api-notes-template.md`
- Autentikasi & profil: `docs/api-profile.md`
- Implementasi: `app/Http/Controllers/Api/V1/WeekMonitorController.php`
