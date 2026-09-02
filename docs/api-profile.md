# API — Profile (profil user terautentikasi)

Dokumen ini menjelaskan endpoint yang di aplikasi disebut **Profile**: data pengguna yang sedang login beserta role, permission, dan relasi unit/dosen.

| | |
|---|---|
| **Nama fitur (dokumentasi)** | Profile |
| **Method & path** | `GET /api/v1/user` |
| **Nama route Laravel** | `user` |
| **Autentikasi** | Laravel Sanctum (`auth:sanctum`) |
| **Controller** | `App\Http\Controllers\Api\V1\AuthController@profile` |

Handler ada di **`Api\V1\AuthController`** (satu file dengan login/logout), path mengikuti prefix **`/api/v1`** seperti endpoint API lain.

Base URL contoh: `http://localhost:8000` → penuh: `http://localhost:8000/api/v1/user`.

---

## 1. Ringkasan

Setelah client memperoleh token (misalnya dari `POST /api/v1/login`), panggil endpoint ini untuk mengisi state aplikasi: siapa user, role apa, permission apa yang boleh dipakai, unit prodi sebagai mahasiswa/admin/dosen, dan daftar nama permission untuk UI (hide/show menu).

Respons **bukan** sekadar objek `user` polos: body JSON memuat `user` (dengan relasi ter-load) dan `permission_names` (array string, sudah unik).

---

## 2. Request

### Headers

| Header | Wajib | Keterangan |
|--------|--------|------------|
| `Authorization` | Ya | `Bearer <plainTextToken>` — token dari Sanctum (`access_token` dari login). |
| `Accept` | Disarankan | `application/json` |

### Body

Tidak ada. Method `GET`.

---

## 3. Respons sukses (`200 OK`)

Content-Type: `application/json`

### Struktur root

| Field | Tipe | Keterangan |
|-------|------|------------|
| `user` | object | Model `User` yang ter-serialisasi ke JSON; relasi berikut di-**load** di server: `roles.permissions`, `dosenUnits`, `kaprodiUnits`, `dosbingStudents`, `adminUnits`, `studentUnit`. |
| `permission_names` | `string[]` | Daftar nama permission efektif (Spatie `getAllPermissions()`), unik, urut seperti koleksi Laravel. Berguna untuk logika client tanpa menelusuri `roles[].permissions[]`. |

### Field umum pada `user` (atribut langsung)

Field yang ikut tergantung kolom di database; umumnya mencakup antara lain:

- `id`, `username`, `fullname`, `identity`, `semester`, `email`
- `student_unit_id`, `dosbing_user_id`, `doswal_user_id` (jika ada di DB)
- `email_verified_at` (datetime ISO8601 jika ada)
- `created_at`, `updated_at`

**Tidak disertakan** di JSON (hidden di model): `password`, `remember_token`.

### Relasi pada `user` (jika ada data)

| Relasi | Keterangan singkat |
|--------|---------------------|
| `roles` | Role Spatie; tiap role dapat memuat `permissions`. |
| `dosen_units` | Unit tempat user berperan dosen (pivot `unit_users`, `role_as` dosen). |
| `kaprodi_units` | Unit yang `kaprodi_user_id`-nya mengarah ke user ini. |
| `dosbing_students` | Mahasiswa yang `dosbing_user_id`-nya user ini. |
| `admin_units` | Unit untuk role admin prodi (pivot). |
| `student_unit` | Objek `Unit` prodi mahasiswa (`student_unit_id`), jika di-set. |

Bentuk detail nested (misalnya field pada `Unit`) sama dengan serialisasi Eloquent standar.

---

## 4. Respons error

| HTTP | Kondisi | Body (umum) |
|------|---------|--------------|
| `401` | Token tidak ada, kedaluwarsa, atau tidak valid | `{ "message": "Unauthenticated." }` |

---

## 5. Contoh

### cURL

```bash
curl -s \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN_HERE" \
  "http://localhost:8000/api/v1/user"
```

### Contoh bentuk respons (disederhanakan)

```json
{
  "user": {
    "id": 1,
    "username": "mahasiswa01",
    "fullname": "Nama Lengkap",
    "email": "user@example.com",
    "roles": [
      {
        "name": "student",
        "permissions": []
      }
    ],
    "dosen_units": [],
    "kaprodi_units": [],
    "dosbing_students": [],
    "admin_units": [],
    "student_unit": null
  },
  "permission_names": [
    "logbook.view",
    "logbook.create"
  ]
}
```

Nilai aktual mengikuti data seed, role, dan permission di database Anda.

---

## 6. Integrasi client

1. Login → simpan `access_token`.
2. Panggil `GET /api/v1/user` dengan header Bearer → cache `user` + `permission_names` sesuai kebutuhan.
3. Gunakan `permission_names` atau `user.roles` untuk menyamakan perilaku dengan web (menu berbasis permission).

---

## 7. Lihat juga

- Login / logout API: `app/Http/Controllers/Api/V1/AuthController.php`, route di `routes/api.php`.
- Pola dokumentasi API umum: `docs/api-notes-template.md`.
- Aktivitas, check-in & check-out API (termasuk field **wajib** `photo` + koordinat): `docs/api-activity.md`.
- Week monitor — list (`weekmonitor.index`) & satu rekaman per minggu (`weekmonitor.by-week`): `docs/api-week-monitor.md`.
