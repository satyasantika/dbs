# Panduan Simulasi NUIR

Dokumen ini menjelaskan akun sementara dan alur uji coba NUIR **per role** setelah menjalankan seeder.

## Persiapan

```bash
docker exec dbs-php php artisan migrate:fresh --seed
```

Atau hanya ulang data simulasi (tanpa reset penuh):

```bash
docker exec dbs-php php artisan db:seed --class=NuirSimulationAccountSeeder
docker exec dbs-php php artisan db:seed --class=NuirSeeder
```

Semua akun simulasi memakai password:

```
simulasi
```

Angkatan simulasi NUIR: **2099** (stage 1, aktif, deadline +2 bulan).

---

## Daftar Akun per Role

| Username | Role | Password | Panel / Entry |
|---|---|---|---|
| `dbs` | DBS | `simulasi` | `/dbs` |
| `manajer1` | Manajer NUIR | `simulasi` | `/nuir-manajer` |
| `validator1` | Validator NUIR | `simulasi` | `/nuir-validator` |
| `pembimbing1` | Dosen (calon P1) | `simulasi` | `/home` → `/nuir/dosen` |
| `pembimbing2` | Dosen (calon P2) | `simulasi` | `/home` → `/nuir/dosen` |
| `penguji1` | Dosen (penguji) | `simulasi` | `/home` |
| `penguji2` | Dosen (penguji) | `simulasi` | `/home` |
| `penguji3` | Dosen (hanya kuota P2) | `simulasi` | `/home` |
| `mahasiswa1` … `mahasiswa8` | Mahasiswa | `simulasi` | `/mahasiswa` |

> **Catatan:** Akun ini hanya untuk development/staging. Jangan dipakai di production.

---

## Skenario Data per Mahasiswa

| Akun | Status NUIR | Kegunaan uji |
|---|---|---|
| `mahasiswa1` | `draft` | Isi/edit draft, simpan referensi, submit |
| `mahasiswa2` | `submitted` + proposal awal | Validator review referensi; histori revisi referensi |
| `mahasiswa3` | `submitted` + 10 ref approved | Manajer/DBS setujui konten (`content_ok`) |
| `mahasiswa4` | revisi v1 + draft v2 + proposal | Histori revisi DBS antarversi; mahasiswa lanjut revisi |
| `mahasiswa5` | `content_ok` + proposal pending | Review N/U/I per elemen; terima/tolak usulan |
| `mahasiswa6` | `content_ok` + P1 accepted, P2 pending | P1 sudah setujui semua NUI; P2 baru setujui Novelty |
| `mahasiswa7` | `content_ok` + penolakan penguji + proposal ulang | Histori penolakan usulan + isi ulang kursi |
| `mahasiswa8` | `finalized` | Semua NUI disetujui; pembimbing terisi di `guide_examiners` |

Proposal aktif memakai **`pembimbing1`** (P1) + **`pembimbing2`** (P2). Penolakan pada `mahasiswa7` memakai **`penguji1`** + **`penguji2`**.

---

## Kuota Pembimbing (angkatan 2099)

Seeder menyiapkan `guide_allocations` dan menyinkronkan `guide*_filled` dari proposal simulasi:

| Dosen | Kuota P1 | Kuota P2 | Catatan |
|---|---|---|---|
| `pembimbing1` | 10 | 10 | Calon pembimbing utama |
| `pembimbing2` | 10 | 10 | Calon pembimbing utama |
| `penguji1` | 5 | 5 | Dipakai skenario penolakan |
| `penguji2` | 5 | 5 | Dipakai skenario penolakan |
| `penguji3` | **0** | 5 | Hanya muncul di dropdown P2 |

Manajer dapat mengubah kuota di **Kuota Pembimbing** (`/nuir-manajer/guide-allocations`).

---

## Role: Manajer NUIR

**Login:** `manajer1` / `simulasi` → **`/nuir-manajer`**

### Fitur Filament

| Menu | URL | Kegunaan |
|---|---|---|
| Dashboard | `/nuir-manajer` | Ringkasan panel |
| Kuota Pembimbing | `/nuir-manajer/guide-allocations` | Atur kuota P1/P2 per dosen (tahun 2099) |
| Pengaturan NUIR | `/nuir-manajer/nuir-settings` | Batas kata, min referensi, deadline |
| Submission NUIR | `/nuir-manajer/nuir-submissions` | Daftar submission (bukan draft) |
| Detail submission | `/nuir-manajer/nuir-submissions/{id}` | Delegasi validator, setujui konten, minta revisi |

### Langkah uji cepat

1. **Kuota** — buka Kuota Pembimbing → filter tahun **2099** → lihat kuota `pembimbing1`/`pembimbing2`.
2. **Delegasi** — submission `mahasiswa2` sudah didelegasikan ke `validator1` (seeder).
3. **Setujui konten** — buka `mahasiswa3` (10 ref approved) → **Setujui Konten**.
4. **Minta revisi** — buka `mahasiswa2` → **Minta Revisi** + catatan (masuk histori revisi mahasiswa).

---

## Role: Validator NUIR

**Login:** `validator1` / `simulasi` → **`/nuir-validator`**

Hanya melihat submission yang sudah didelegasikan manajer. Submission **draft** tidak muncul.

### Fitur Filament

| Menu | URL | Kegunaan |
|---|---|---|
| Dashboard | `/nuir-validator` | Ringkasan panel |
| Validasi Referensi | `/nuir-validator/nuir-submissions` | Daftar submission ditugaskan |
| Detail + tab Referensi | `/nuir-validator/nuir-submissions/{id}` | Setujui/minta revisi per referensi (catatan wajib) |

### Langkah uji cepat

1. Login `validator1` → buka submission `mahasiswa2`.
2. Tab **Referensi** → setujui/tolak referensi (catatan wajib saat minta revisi).
3. Lihat **Histori Revisi** di panel mahasiswa (`mahasiswa2`) — referensi #6–#7 sudah punya catatan simulasi.
4. Pastikan `mahasiswa1` (draft) **tidak** muncul di daftar validator.

---

## Role: DBS

**Login:** `dbs` / `simulasi` → **`/dbs`**

### Fitur yang dapat diakses

| Menu Filament | URL | Uji dengan |
|---|---|---|
| Dashboard | `/dbs` | Statistik ujian |
| Konfigurasi NUIR | `/dbs/nuir-settings` | Setting angkatan 2099 |
| Review Submission | `/dbs/nuir-submissions` | `mahasiswa2`, `mahasiswa3`, `mahasiswa4` |
| Monitor Usulan Calon Pembimbing | `/dbs/nuir-proposals` | Force finalize jika kedua pembimbing sudah accept |
| Kuota / Kelompok / Ujian | menu Manajemen Seleksi & Ujian | Permission DBS |

### Langkah uji cepat

1. **Review referensi** — Review Submission → `mahasiswa2` → setujui/tolak referensi.
2. **Setujui konten** — `mahasiswa3` (10 referensi approved) → **Setujui Konten**.
3. **Minta revisi** — `mahasiswa2` → **Minta Revisi** + catatan (histori DBS).
4. **Monitor usulan** — Monitor Usulan → `mahasiswa5`–`mahasiswa8` → **Force Finalize** bila perlu.

---

## Role: Pembimbing (Dosen calon P1/P2)

**Login:** `pembimbing1` atau `pembimbing2` / `simulasi` → **`/home`** → **Usulan NUIR** (`/nuir/dosen`)

### Fitur NUIR

| Fitur | URL | Keterangan |
|---|---|---|
| Usulan NUIR masuk | `/nuir/dosen` | Usulan yang ditujukan ke akun login |
| Detail usulan | `/nuir/dosen/{id}` | Review N/U/I per elemen, review referensi, terima/tolak usulan |
| Histori revisi & penolakan | halaman detail | Jejak catatan revisi NUI, referensi, penolakan usulan |

### Alur review NUI (wajib sebelum kursi diterima)

1. Setujui atau **minta revisi** per elemen: **Novelty**, **Urgency**, **Impact** (catatan wajib jika minta revisi).
2. Setelah **semua elemen disetujui** dan NUIR `content_ok`, kursi otomatis **accepted**.
3. Finalisasi terjadi hanya jika **P1 dan P2** keduanya accept **dan** semua elemen NUI disetujui.

### Langkah uji per akun

**`pembimbing1` (P1)**

- `mahasiswa5` — review N/U/I dari awal (proposal pending).
- `mahasiswa6` — semua NUI sudah disetujui P1 → kursi P1 **accepted**.
- `mahasiswa7` — proposal ulang pending; lihat histori penolakan penguji.

**`pembimbing2` (P2)**

- `mahasiswa5` & `mahasiswa6` — lanjutkan review NUI (P2 pada `mahasiswa6` baru setujui Novelty).
- `mahasiswa7` — terima/tolak proposal ulang (catatan wajib jika tolak).

---

## Role: Penguji (Dosen)

**Login:** `penguji1`, `penguji2`, atau `penguji3` / `simulasi` → **`/home`**

Penguji **tidak** menerima proposal NUIR kecuali ikut sebagai pasangan proposal. Simulasi penolakan `mahasiswa7` memakai `penguji1` + `penguji2`.

| Akun | Kegunaan simulasi |
|---|---|
| `penguji1` / `penguji2` | Lihat histori penolakan usulan `mahasiswa7` |
| `penguji3` | Hanya kuota P2 — uji filter dropdown usulan mahasiswa |

Slot penguji sudah terhubung ke `guide_examiners` mahasiswa simulasi untuk ujian skripsi.

---

## Role: Mahasiswa

**Login:** `mahasiswa1` … `mahasiswa8` / `simulasi` → **`/mahasiswa`**

Card **Pengajuan NUIR** muncul karena angkatan 2099 aktif stage 1.

### Fitur yang dapat diakses

| Fitur | URL | Kegunaan |
|---|---|---|
| Dashboard | `/mahasiswa` | Shortcut NUIR |
| NUIR Saya | `/mahasiswa/nuir-submission` | Draft, submit, revisi, **Histori Revisi** |
| Usulan calon pembimbing | `/mahasiswa/nuir-proposal` | Ajukan P1/P2 (terfilter kuota posisi) |

### Langkah uji per akun

| Akun | Yang bisa dicoba |
|---|---|
| `mahasiswa1` | Edit draft → submit ke DBS |
| `mahasiswa2` | Lihat histori revisi referensi; usulan sudah ke pembimbing |
| `mahasiswa3` | Tunggu konten disetujui → buat usulan P1+P2 |
| `mahasiswa4` | Form revisi v2; histori catatan DBS dari v1 |
| `mahasiswa5` | Usulan pending; tunggu review NUI pembimbing |
| `mahasiswa6` | Lihat usulan sebagian diterima (P1 accepted) |
| `mahasiswa7` | Histori penolakan + usulan aktif ke pembimbing |
| `mahasiswa8` | Status finalized |

---

## Alur End-to-End (Recommended)

1. **`mahasiswa1`** — lengkapi draft → submit.
2. **`manajer1`** — delegasikan (atau verifikasi delegasi seeder) ke `validator1`.
3. **`validator1`** — review referensi `mahasiswa2` (catatan wajib jika minta revisi).
4. **`manajer1`** — setujui konten `mahasiswa3`.
5. **`mahasiswa3`** — usulan calon pembimbing `pembimbing1` + `pembimbing2` (kuota P1/P2 terpisah).
6. **`pembimbing1`** & **`pembimbing2`** — setujui N/U/I per elemen pada `mahasiswa5`.
7. Verifikasi kursi **accepted** otomatis → status **finalized** bila keduanya selesai.
8. **`mahasiswa7`** — lihat histori penolakan; **`pembimbing1`** lihat jejak yang sama.

---

## Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| Card NUIR tidak muncul | Setting angkatan tidak aktif / stage 3 | Jalankan ulang seed; pastikan setting 2099 aktif |
| Tidak bisa buat submission baru | Sudah ada submission `finalized` | Gunakan akun selain `mahasiswa8` |
| Dropdown pembimbing kosong | Kuota P1/P2 habis | Login `manajer1` → tambah kuota tahun 2099 |
| `penguji3` tidak muncul di P1 | Kuota P1 = 0 (by design) | Pilih posisi P2 atau gunakan pembimbing1/2 |
| Pembimbing tidak melihat usulan | Bukan guide1/guide2 pada proposal | Login `pembimbing1`/`pembimbing2` |
| Validator tidak melihat submission | Belum didelegasikan | Login `manajer1` → delegasikan validator |
| Kursi tidak accepted otomatis | Belum semua N/U/I disetujui | Setujui ketiga elemen NUI terlebih dahulu |

---

## Referensi Teknis

- Seeder akun: `database/seeders/NuirSimulationAccountSeeder.php`
- Seeder data NUIR: `database/seeders/NuirSeeder.php`
- Test akses: `tests/Feature/NuirSimulationAccessTest.php`
- Test konsistensi data: `tests/Feature/NuirSeederTest.php`
- Test panel Filament: `tests/Feature/Filament/NuirManajerPanelSmokeTest.php`, `NuirValidatorPanelSmokeTest.php`
