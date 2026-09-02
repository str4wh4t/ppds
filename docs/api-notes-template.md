# Catatan Pola Pembuatan API (Scramble + JSON Konsisten)

Dokumen ini dipakai sebagai acuan saat membuat endpoint baru supaya:
- format request body & response konsisten,
- Scramble membaca schema dengan benar,
- client/web app tidak perlu menebak format parameter (query vs JSON body),
- status code sesuai semantik umum.

---

## 1) Struktur URL & Versioning
- Selalu gunakan prefix `api/v1`.
- Route “list/filter” yang butuh parameter filter sebaiknya menggunakan `POST` dengan **body JSON** (hindari query string untuk kebutuhan docs & konsistensi).

## 2) Auth (Sanctum Token)
- Endpoint yang butuh autentikasi: wajib `Authorization: Bearer <access_token>`.
- Pada Scramble, pastikan scheme `Bearer` sudah aktif sehingga field token muncul di halaman docs.
- Di controller docs, gunakan:
  - `@authenticated`
  - `@header Authorization string required Gunakan format: Bearer {access_token}`

## 3) Format Request (JSON body)
- Untuk endpoint `POST`:
  - baca data dari `$request->all()` atau `$request->input('field')`,
  - lakukan validasi dengan `Validator::make($request->all(), [...])->validate();`
- Dokumen parameter request gunakan `@bodyParam` (bukan `@queryParam`) agar Scramble menampilkan “request body” saat `Try It`.

Contoh pola validasi:
```php
Validator::make($request->all(), [
  'search' => ['nullable', 'string', 'max:255'],
  'stase_id' => ['required', 'integer', 'exists:stases,id'],
])->validate();
```

## 4) Format Response & Status Code
- `200 OK` untuk data dibaca/ditampilkan (list & retrieval).
- `201 Created` untuk operasi yang “membuat resource” (mis: create activity, login jika tim Anda sepakat menandingkan success token dengan created).
- `401 Unauthenticated` untuk belum login/invalid token.
- `403 Forbidden` untuk validasi otorisasi khusus (mis role/permission).
- `422 Validation error` untuk validasi input.

### Error body yang disarankan
- Unauthenticated (`401`):
  - `{ "message": "Unauthenticated." }`
- Validation (`422`):
  - `{ "message": "Validation error", "errors": [] }`

## 5) Scramble Doc: Wajib Pakai Attributes Response
- Saat sudah pakai `#[Dedoc\Scramble\Attributes\Response(...)]`, tulis schema response eksplisit.
- Gunakan `#[Response(STATUS, 'Description', type: '...')]` di atas method.

Contoh ringkas:
```php
#[Response(200, 'List X', type: 'array{data: array<array{id: int, name: string}>>')]
#[Response(401, 'Unauthenticated', type: 'array{message: string}')]
public function index(Request $request): JsonResponse
```

## 6) Konsistensi Penamaan Route Name
- Gunakan `name('...')` yang konsisten dengan tujuan:
  - `stase.index`, `stase.locations`, `location.index`, `dosen.index`
  - `activity.list`, `activities.calendar-generatedays`, `weekmonitor.index`, `weekmonitor.by-week`
- Jangan ganti nama route tanpa update semua consumer.

## 7) Contoh Konvensi “List via POST Body”
- `POST /api/v1/stases` dengan body:
  - `{ "search": "igd" }`
- `POST /api/v1/stases/locations` dengan body:
  - `{ "stase_id": 1 }`
- `POST /api/v1/dosens` dengan body:
  - `{ "search": "dr." }`

## 8) Logika checkout activity (domain)
- Aturan pemecahan record saat checkout (batas segmen di **00:00:00** hari berikutnya, `SplitCheckoutService`, respons `split_activities`) didokumentasikan di **`docs/api-activity.md`** bagian **Activity Check-out**.
- Endpoint **API** check-out mewajibkan `photo`, `latitude`, dan `longitude` (koordinat tersimpan sebagai `checkout_latitude` / `checkout_longitude`); checkout **web** admin tidak mengirim field tersebut — lihat tabel field di **`docs/api-activity.md`**.

## 9) Checklist Sebelum Merge
- [ ] Route method sesuai (list/filter pakai `POST` jika ada parameter filter).
- [ ] Parameter request memakai `@bodyParam` (bukan `@queryParam`).
- [ ] Validasi pakai `$request->all()` untuk `POST JSON`.
- [ ] `#[Response(...)]` untuk status 200/201/401/422 (minimal yang relevan).
- [ ] Controller mengembalikan JSON dengan struktur yang dijelaskan.
- [ ] Docs manual (markdown) mencerminkan method, url, status code, dan format body.

