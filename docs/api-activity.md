# API Activity

## Authentication

### Login

- **Method**: `POST`
- **URL**: `/api/v1/login`
- **Route Name**: `auth.login`

Request body:

```json
{
  "username": "mahasiswa01",
  "password": "secret123",
  "device_name": "mobile-app"
}
```

Contoh sukses (`201`):

```json
{
  "message": "Login successful",
  "token_type": "Bearer",
  "access_token": "1|plain_text_token",
  "user": {
    "id": 1,
    "username": "mahasiswa01",
    "fullname": "Mahasiswa Satu"
  }
}
```

Gunakan token pada header:

- `Authorization: Bearer <access_token>`

### Logout

- **Method**: `POST`
- **URL**: `/api/v1/logout`
- **Route Name**: `auth.logout`
- **Auth**: wajib Bearer token

Contoh sukses (`200`):

```json
{
  "message": "Logout successful"
}
```

## Master Data

### List Stases

- **Method**: `POST`
- **URL**: `/api/v1/stases`
- **Route Name**: `stase.index`
- **Auth**: wajib Bearer token

Request body (JSON):

```json
{
  "search": "igd"
}
```

### List Locations by Stase

- **Method**: `POST`
- **URL**: `/api/v1/stases/locations`
- **Route Name**: `stase.locations`
- **Auth**: wajib Bearer token

Request body (JSON):
```json
{
  "stase_id": 1
}
```

### List Stases by Unit (unit_stases)

- **Method**: `POST`
- **URL**: `/api/v1/stases/by-unit`
- **Route Name**: `stase.by-unit`
- **Auth**: wajib Bearer token

Mengembalikan stase yang **diikat ke unit** user lewat tabel `unit_stases` (bukan seluruh stase global). Tiap item memuat metadata pivot (`id` = `unit_stases.id`, `is_mandatory`, dll.) dan objek `stase` beserta `locations`.

Request body (JSON):

```json
{
  "search": "igd",
  "unit_id": 2
}
```

Catatan:

- **Role `student`:** unit diambil dari `student_unit_id` pada user token; field `unit_id` di body **diabaikan**.
- **Role lain:** gunakan `unit_id` bila perlu, atau biarkan kosong sehingga sistem memakai `student_unit_id`, atau unit tempat user menjadi kaprodi. Tanpa akses ke unit yang diminta → `403`.
- Untuk **create activity** (`type=nonjaga`) dan **check-in** (`type=nonjaga`), pilih `stase_id` dari respons ini agar selaras dengan konfigurasi unit (sama seperti form web yang memfilter stase per `student_unit_id`).

### List Locations

- **Method**: `POST`
- **URL**: `/api/v1/locations`
- **Route Name**: `location.index`
- **Auth**: wajib Bearer token

Request body (JSON):

```json
{
  "search": "kariadi"
}
```

### List Dosens

- **Method**: `POST`
- **URL**: `/api/v1/dosens`
- **Route Name**: `dosen.index`
- **Auth**: wajib Bearer token

Request body (JSON):

```json
{
  "search": "dr."
}
```

## List Activities

- **Method**: `POST`
- **URL**: `/api/v1/activities/list`
- **Route Name**: `activity.list`
- **Auth**: wajib Bearer token

Request body (JSON):

```json
{
  "user_id": 15,
  "search": "jaga",
  "units": ["Kedokteran", "Keperawatan"],
  "per_page": 10,
  "date": "2026-04-18"
}
```

Contoh lain — hanya `year` / `year` + `month` (tanpa `date`):

```json
{
  "year": 2026,
  "month": 4,
  "per_page": 10
}
```

Contoh filter harian **eksplisit** (ketiga parameter konsisten, sama hasilnya dengan hanya mengirim `date`):

```json
{
  "year": 2026,
  "month": 4,
  "date": "2026-04-18",
  "search": "IGD",
  "per_page": 10
}
```

Catatan:

- Untuk role `student`, `user_id` akan diabaikan dan data selalu mengikuti user pada token.
- **`search`** — opsional; filter substring pada **nama aktivitas** (`name`). Dapat dipakai bersamaan dengan filter tanggal dan `units`.
- Filter tanggal (opsional; semua mengacu ke **`start_date`**):
  - Hanya **`year`** → seluruh tahun tersebut.
  - Hanya **`month`** → bulan tersebut di **semua tahun** (mis. setiap bulan April).
  - Hanya **`date`** (`Y-m-d`) → tepat satu hari kalender.
  - **`year` + `month`** (tanpa `date`) → bulan kalender tersebut di tahun tersebut (mis. April 2026, seluruh bulan).
  - **`date` + `year` dan/atau `month`** → **`year`** / **`month`** harus **sama** dengan komponen tanggal pada `date`; jika tidak → **422**. Query memakai **`whereDate`** (satu hari).
- Filter tanggal, `search`, dan `units` saling **AND** (sempitkan hasil bersamaan).

## List Activities by Week Group

- **Method**: `POST`
- **URL**: `/api/v1/activities/by-week-group`
- **Route Name**: `activity.by-week-group`
- **Auth**: wajib Bearer token
- **Content-Type**: `application/json`

Request body (JSON):

```json
{
  "week_group_id": 202614,
  "search": "jaga",
  "per_page": 10,
  "user_id": 15
}
```

Catatan:

- **`week_group_id`** wajib — sama konsepnya dengan kolom `activities.week_group_id` / `week_monitors.week_group_id` (ISO week year + minggu, mis. `202614`).
- **`user_id`** — opsional; untuk role **`system`** sama seperti list (filter user lain). Role **`student`** mengabaikan `user_id`.
- **`search`** — opsional; substring pada **nama aktivitas**.
- Respons sama bentuknya dengan list terpaginasi (`data`, `current_page`, `per_page`, `total`, `last_page`, dll.).

## Get Activity (by ID)

- **Method**: `GET`
- **URL**: `/api/v1/activities/{activity}`
- **Route Name**: `activity.show-api`
- **Auth**: wajib Bearer token

`{activity}` = ID activity (numerik). Response **`200`** berbentuk `{ "data": { ... } }` dengan relasi yang sama seperti list (`user`, `user.studentUnit`, `unitStase`, `stase`, `staseLocation`, `location`, `dosenUser`).

Catatan:

- Hanya **pemilik** activity (user pada token) atau role **`system`** yang dapat membaca. Selain itu → **`403`**.
- Activity dengan `is_generated = 1` tidak termasuk global scope list umum; jika ID tidak ditemukan → **`404`**.

## Update Activity

- **Method**: `PUT`
- **URL**: `/api/v1/activities/{activity}`
- **Route Name**: `activity.update-api`
- **Auth**: wajib Bearer token

`{activity}` = ID activity (numerik). Body JSON mengikuti form update web: nama, deskripsi, jenis (`type`), tanggal & jam (`date`, `start_time`, `finish_time`), stase & lokasi (untuk `type` `nonjaga`), dan dosen (`dosen_user_id`, boleh `null`).

```json
{
  "name": "Jaga malam IGD",
  "type": "nonjaga",
  "date": "2026-04-15",
  "start_time": "08:00",
  "finish_time": "12:30",
  "description": "Visit pasien rawat inap",
  "stase_id": 3,
  "location_id": 7,
  "dosen_user_id": 12
}
```

Untuk `type` `jaga`, `stase_id` dan `location_id` boleh diabaikan / dikosongkan (validasi mengikuti `ActivityUpdateRequest`).

Perilaku penting:

- Hanya **pemilik** activity atau role **`system`** yang boleh mengubah; selain itu → **`403`**.
- Activity dengan **`is_overdue_checkout`** tidak boleh diubah → **`403`** (setelah percobaan sebelumnya mungkin sudah menyamakan flag; lihat poin berikut).
- Activity **check-in terbuka** (mulai = selesai, `time_spend` nol) yang sudah **24 jam atau lebih** (`diffInHours` ≥ 24) sejak `start_date` walau flag belum `true`: update ditolak dengan **`422`** (biasanya field `date`) dan `is_overdue_checkout` diset **`true`**; role **`system`** tidak terkena aturan ini.
- User yang **bukan** role `student` (mis. `system` pada API) setelah update berhasil: `is_overdue_checkout` diset **`true`/`false`** mengikuti apakah state baru masih “terbuka & ≥24 jam” seperti definisi di atas (bukan hanya mahasiswa pemilik).
- **Jam mulai / selesai**: `finish_time` tidak boleh **sebelum** `start_time` (nilai string `H:i` atau `24:00`); **sama** (`08:00`–`08:00`) diperbolehkan → durasi nol, `time_spend` `00:00:00`.
- **Bentrok waktu**: tidak boleh overlap dengan activity lain milik **user pemilik** pada tanggal yang sama (selain record yang sedang diubah); pesan validasi pada `start_time` / `finish_time` seperti di web.
- **Beban kerja mingguan** (`WeekMonitor`): jika durasi jam berubah dan total jam minggu melebihi batas sementara activity bermasalah (`is_allowed = 0`), server mengembalikan **`422`** dengan pesan `Workload exceeded` (selaras logika web).
- **`stase_id` + `location_id`**: untuk `nonjaga`, lokasi harus termasuk di pivot stase–lokasi; kombinasi `UnitStase` harus ada untuk unit prodi pemilik. Jika tidak ditemukan → **`422`** (pesan stase/lokasi tidak valid).
- **Tidak diubah lewat endpoint ini**: koordinat GPS (`latitude` / `longitude`) dan path foto check-in / check-out — tetap seperti sebelum update.

Response **`200`**: `{ "message": "Activity updated successfully", "data": { ... } }` dengan relasi yang sama seperti **Get Activity** (`user`, `unitStase`, `stase`, `location`, `dosenUser`, dll.).

## Create Activity

- **Method**: `POST`
- **URL**: `/api/v1/activities`
- **Auth**: `Bearer token` (Sanctum)
- **Route Name**: `activity.store`

Header wajib:

- `Authorization: Bearer <access_token>`
- Di halaman Scramble, klik tombol **Authorizations**, isi `Bearer <access_token>`, lalu jalankan `Try It`.

Request body (sesuai input form pada `EntryActivity.vue`):

```json
{
  "name": "Jaga malam IGD",
  "type": "nonjaga",
  "date": "2026-04-15",
  "start_time": "08:00",
  "finish_time": "12:30",
  "description": "Visit pasien rawat inap",
  "stase_id": 3,
  "location_id": 7,
  "dosen_user_id": 12,
  "latitude": -7.050549,
  "longitude": 110.393465
}
```

Catatan:

- `stase_id` dan `location_id` wajib jika `type=nonjaga`.
- Untuk memilih stase yang valid bagi prodi user, gunakan master data **`POST /api/v1/stases/by-unit`** (bagian *List Stases by Unit* di Master Data); `POST /api/v1/stases` mengembalikan daftar stase global tanpa filter unit.
- **Jam mulai / selesai**: `finish_time` tidak boleh **sebelum** `start_time`; **sama** diperbolehkan (durasi nol, `time_spend` `00:00:00`).
- `finish_time` mendukung format `H:i` dan `24:00`.
- `latitude` dan `longitude` bersifat opsional.
- Jika salah satu diisi, pasangannya wajib diisi.
- `created_via` diisi otomatis oleh sistem (`web`/`api`/`system`), tidak perlu dikirim dari client.
- `device_info` hanya diisi otomatis jika record dibuat via API; jika via web nilainya `null`.
- Validasi bentrok waktu tetap berlaku sama seperti form web.

Contoh sukses (`201`):

```json
{
  "message": "Activity created successfully",
  "data": {
    "id": 123,
    "name": "Jaga malam IGD",
    "type": "nonjaga",
    "start_date": "2026-04-15T08:00:00.000000Z",
    "end_date": "2026-04-15T12:30:00.000000Z",
    "time_spend": "04:30:00"
  }
}
```

Contoh gagal validasi/proses (`422`):

```json
{
  "message": "Workload exceeded"
}
```

## Activity Check-in

- **Method**: `POST`
- **URL**: `/api/v1/activities/checkin`
- **Auth**: `Bearer token` (Sanctum)
- **Route Name**: `activity.checkin`
- **Content-Type**: `multipart/form-data` (wajib ada file `photo`)

### Field wajib & opsional

| Field | Wajib | Keterangan |
| --- | --- | --- |
| `name` | Ya | String, max 255 |
| `type` | Ya | `jaga` atau `nonjaga` |
| `start_at` | Ya | Datetime `Y-m-d H:i:s` |
| `description` | Ya | String |
| `stase_id` | Jika `type=nonjaga` | Integer, harus ada di `stases` |
| `location_id` | Jika `type=nonjaga` | Integer, harus cocok stase–lokasi |
| `dosen_user_id` | Tidak | Integer user dosen |
| `latitude` | **Ya** | Angka, -90 … 90 |
| `longitude` | **Ya** | Angka, -180 … 180 |
| `photo` | **Ya** | File gambar: jpg, jpeg, png, webp; max **500** KB |

**Perbedaan dengan web / form Inertia:** pembuatan activity dari browser memakai route dan validasi lain; field foto & koordinat di sana tetap opsional. **Hanya** endpoint check-in API ini yang mewajibkan `photo`, `latitude`, dan `longitude`.

Request body (`multipart/form-data`):

```text
name=Jaga IGD Shift Pagi
type=nonjaga
start_at=2026-04-16 08:00:00
description=Check-in jaga di IGD
stase_id=3
location_id=7
dosen_user_id=12
latitude=-7.050549
longitude=110.393465
photo=@/path/to/checkin-photo.jpg
```

Catatan:

- Untuk `type=nonjaga`, ambil `stase_id` (dan kemudian `location_id` via `POST /api/v1/stases/locations`) dari stase yang diizinkan unit Anda; daftar per unit: **`POST /api/v1/stases/by-unit`** (bagian *List Stases by Unit* di Master Data).
- `finish_at` belum dikirim saat check-in.
- Sistem menyimpan sementara `end_date = start_date` dan `time_spend = 00:00:00`.
- Satu user tidak bisa check-in activity baru jika masih ada activity yang belum checkout dan belum **≥24 jam** terbuka. Activity terbuka yang sudah `is_overdue_checkout = true` tidak ikut dalam penilaian itu (hanya baris `is_overdue_checkout = false` yang dicek untuk batas 24 jam dan pembaruan flag).
- Activity open yang sudah **≥24 jam** otomatis ditandai `is_overdue_checkout = true`.
- Koordinat check-in disimpan di kolom `latitude` dan `longitude` pada record activity.
- File foto disimpan di disk `public` dengan pola **`activities/checkin/{tahun}/...`** — tahun diambil dari **`start_at`** (kalender tahun check-in).
- **Upload foto di production:** batas body di **nginx** (`client_max_body_size`) dan **PHP** (`upload_max_filesize`, `post_max_size`) harus **lebih besar dari 500 KB** per foto (disarankan setidaknya **1–2 MB** untuk `post_max_size` guna overhead `multipart/form-data`).

Contoh gagal validasi (`422`) — field wajib kosong atau bukan file gambar:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "photo": ["The photo field is required."],
    "latitude": ["The latitude field is required."],
    "longitude": ["The longitude field is required."]
  }
}
```

Contoh sukses (`201`):

```json
{
  "message": "Activity check-in successful",
  "data": {
    "id": 321,
    "name": "Jaga IGD Shift Pagi",
    "start_date": "2026-04-16T08:00:00.000000Z",
    "end_date": "2026-04-16T08:00:00.000000Z",
    "time_spend": "00:00:00",
    "latitude": "-7.0505490",
    "longitude": "110.3934650",
    "checkin_photo_path": "activities/checkin/2026/abc123.jpg",
    "checkout_photo_path": null,
    "checkout_latitude": null,
    "checkout_longitude": null
  }
}
```

## Activity Check-out

- **Method**: `POST`
- **URL**: `/api/v1/activities/{activity}/checkout`
- **Auth**: `Bearer token` (Sanctum)
- **Route Name**: `activity.checkout`
- **Content-Type**: `multipart/form-data` (wajib ada file `photo`)

`{activity}` di path = ID activity hasil check-in (pemilik harus sama dengan user token).

### Field wajib (API)

| Field | Wajib | Keterangan |
| --- | --- | --- |
| `finish_at` | Ya | Datetime selesai `Y-m-d H:i:s`, harus **lebih besar** dari `start_date` activity |
| `latitude` | **Ya** | Angka lokasi saat check-out, -90 … 90 → disimpan sebagai **`checkout_latitude`** |
| `longitude` | **Ya** | Angka, -180 … 180 → disimpan sebagai **`checkout_longitude`** |
| `photo` | **Ya** | File gambar jpg/jpeg/png/webp, max **500** KB → `checkout_photo_path` |

**Pemisahan data lokasi:** `latitude` / `longitude` pada record = titik saat **check-in** (tidak di-overwrite saat checkout). Titik saat **check-out** hanya di `checkout_latitude` / `checkout_longitude`.

**Checkout admin (web):** route `/activities/{activity}/checkout` hanya mengirim tanggal & jam; **tidak** mengirim `photo` maupun koordinat checkout — kolom `checkout_photo_path`, `checkout_latitude`, dan `checkout_longitude` dari web bisa tetap kosong.

Request body (`multipart/form-data`):

```text
finish_at=2026-04-16 12:30:00
latitude=-7.050549
longitude=110.393465
photo=@/path/to/checkout-photo.jpg
```

Catatan:

- File foto checkout disimpan dengan pola **`activities/checkout/{tahun}/...`** — tahun diambil dari **`finish_at`**.
- Endpoint ini melengkapi activity hasil check-in.
- `finish_at` harus lebih besar dari `start_at`.
- Jika activity open sudah **≥24 jam**, checkout ditolak untuk student dan activity ditandai `is_overdue_checkout = true`.
- Otorisasi: middleware `can:checkout,activity` (policy `ActivityPolicy::checkout`).
- **Penyimpanan checkout** di-server memakai `App\Services\Activity\SplitCheckoutService` (sama dengan checkout web admin untuk logika waktu & pemecahan record).
- **Upload foto:** batas **nginx** / **PHP** harus cukup untuk unggahan hingga **500 KB** per foto (sama seperti catatan pada Activity Check-in).

Contoh gagal validasi (`422`) — field wajib checkout API:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "photo": ["The photo field is required."],
    "latitude": ["The latitude field is required."],
    "longitude": ["The longitude field is required."]
  }
}
```

### Logika save checkout (API & web)

Satu **baris** activity tidak boleh “memotong” tengah malang secara sembarang: jika `finish_at` jatuh **setelah** batas hari kalender (baca di bawah), sistem **memecah** menjadi beberapa record.

- **Batas akhir segmen** untuk satu record bukan 23:59:59, melainkan **00:00:00 pada hari kalender berikutnya** (awal hari baru), relatif terhadap awal segmen.
- Untuk segmen berikutnya, record baru punya `start_date` **sama** dengan `end_date` segmen sebelumnya (keduanya di **00:00:00** hari yang sama, artinya menyambung di tengah malang).
- `time_spend` tiap record = selisih detik antara `start_date` dan `end_date` segmen tersebut.
- `week_group_id` dihitung dari **tanggal mulai segmen** (ISO week year + week), konsisten dengan pembuatan activity biasa.
- `is_overdue_checkout` di-set `false` pada record utama yang di-update; record lanjutan juga `false`.
- Field lain (nama, jenis, stase, lokasi, dosen, foto check-in/out, `checkout_latitude` / `checkout_longitude`, dll.) disalin dari activity asli ke record lanjutan.

**Contoh:** mulai Sen 10:00, checkout Rabu 15:00 → tiga baris: Sen 10:00 → Sel 00:00; Sel 00:00 → Rab 00:00; Rab 00:00 → Rab 15:00.

Respons API menyertakan record utama yang sudah di-update di `data`, dan bila ada pemecahan, array **`split_activities`** berisi activity tambahan (bisa kosong `[]`).

### Checkout admin (Inertia / web)

- **Method**: `POST`
- **URL**: `/activities/{activity}/checkout`
- **Route Name**: `activities.checkout`
- Body (form / Inertia): `date` (`Y-m-d`), `finish_time` (`HH:mm` atau `24:00` untuk tengah malam hari berikutnya).
- Tidak ada upload foto atau pengiriman koordinat checkout pada flow web ini.
- Otorisasi sama: `can:checkout,activity` (misalnya `admin_prodi` untuk overdue; aturan detail ada di policy).

Contoh sukses (`200`) — tanpa pemecahan (respons sama bentuknya setelah checkout via **API** dengan foto & koordinat):

```json
{
  "message": "Activity check-out successful",
  "data": {
    "id": 321,
    "name": "Jaga IGD Shift Pagi",
    "start_date": "2026-04-16T08:00:00.000000Z",
    "end_date": "2026-04-16T12:30:00.000000Z",
    "time_spend": "04:30:00",
    "latitude": "-7.0505490",
    "longitude": "110.3934650",
    "checkin_photo_path": "activities/checkin/2026/abc123.jpg",
    "checkout_photo_path": "activities/checkout/2026/xyz456.jpg",
    "checkout_latitude": "-7.0510000",
    "checkout_longitude": "110.3940000"
  },
  "split_activities": []
}
```

Contoh sukses (`200`) — dengan pemecahan (tanggal `finish_at` melewati satu atau lebih tengah malang):

```json
{
  "message": "Activity check-out successful",
  "data": {
    "id": 321,
    "name": "Jaga IGD Shift Pagi",
    "start_date": "2026-04-15T08:00:00.000000Z",
    "end_date": "2026-04-16T00:00:00.000000Z",
    "time_spend": "16:00:00"
  },
  "split_activities": [
    {
      "id": 456,
      "start_date": "2026-04-16T00:00:00.000000Z",
      "end_date": "2026-04-17T00:00:00.000000Z",
      "time_spend": "24:00:00"
    },
    {
      "id": 457,
      "start_date": "2026-04-17T00:00:00.000000Z",
      "end_date": "2026-04-17T15:30:00.000000Z",
      "time_spend": "15:30:00"
    }
  ]
}
```

Respons JSON riil mengembalikan objek activity lengkap (termasuk `latitude`, `longitude`, path foto, `checkout_latitude`, `checkout_longitude`, dll.) pada `data` dan pada setiap item di `split_activities`. Contoh di atas hanya menyingkat field agar fokus ke rentang waktu.

## Delete Activity

- **Method**: `DELETE`
- **URL**: `/api/v1/activities/{activity}`
- **Auth**: `Bearer token` (Sanctum)
- **Route Name**: `activity.destroy-api`

Catatan:

- **Student**: hanya activity milik token sendiri (termasuk saat overdue).
- **User selain student** (mis. admin): boleh menghapus activity milik user lain; otorisasi mengikuti policy `delete` pada route.
- Jika tidak berhak, API mengembalikan `403`.
- File **`checkin_photo_path`** dan **`checkout_photo_path`** di disk `public` ikut dihapus jika tidak ada activity lain yang masih memakai path yang sama (mis. setelah split checkout beberapa baris bisa berbagi satu file).

Contoh sukses (`200`):

```json
{
  "message": "Activity deleted successfully"
}
```

## Dokumentasi dengan Scramble

Controller method di `Api\V1\ActivityController` (`store`, `checkIn`, `checkOut`) sudah ditambah PHPDoc (`@group`, `@bodyParam`, `@response`) agar dipindai Scramble. Untuk check-in / check-out, PHPDoc menyatakan field `photo`, `latitude`, dan `longitude` sebagai **required** pada API (selaras dengan validasi di controller).
Bearer auth scheme juga sudah dikonfigurasi di `AppServiceProvider`, sehingga pada halaman docs tersedia input token untuk dipakai saat `Try It`.

Akses dokumentasi default Scramble:

- [http://localhost:8000/docs/api](http://localhost:8000/docs/api)
