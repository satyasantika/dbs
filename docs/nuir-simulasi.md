# Panduan Simulasi NUIR

Dokumen ini menjelaskan akun sementara dan alur uji coba NUIR per role setelah menjalankan seeder.

## Persiapan

```bash
docker exec dbs-php php artisan migrate:fresh --seed
```

Semua akun simulasi memakai password:

```
simulasi
```

Angkatan simulasi NUIR: **2099** (stage 1, aktif, deadline +2 bulan).

---

## Daftar Akun

| Username | Role | Password | Panel / Entry |
|---|---|---|---|
| `dbs` | DBS | `simulasi` | `/dbs` |
| `pembimbing1` | Dosen (pembimbing) | `simulasi` | `/home` |
| `pembimbing2` | Dosen (pembimbing) | `simulasi` | `/home` |
| `penguji1` | Dosen (penguji) | `simulasi` | `/home` |
| `penguji2` | Dosen (penguji) | `simulasi` | `/home` |
| `penguji3` | Dosen (penguji) | `simulasi` | `/home` |
| `mahasiswa1` … `mahasiswa8` | Mahasiswa | `simulasi` | `/mahasiswa` |
| `manajer1` | Manajer NUIR | `simulasi` | `/nuir-manajer` |
| `validator1` | Validator NUIR | `simulasi` | `/nuir-validator` |

> **Catatan:** Akun ini hanya untuk lingkungan development/staging. Jangan dipakai di production.

---

## Skenario Data per Mahasiswa

| Akun | Status NUIR | Kegunaan uji |
|---|---|---|
| `mahasiswa1` | `draft` | Isi/edit draft, simpan referensi, submit |
| `mahasiswa2` | `submitted` + proposal awal | Validator review referensi; pembimbing lihat usulan |
| `mahasiswa3` | `submitted` + 10 ref approved | DBS setujui konten (`content_ok`) |
| `mahasiswa4` | revisi v1 + draft v2 + proposal | Mahasiswa lanjut revisi; pembimbing lihat permintaan revisi |
| `mahasiswa5` | `content_ok` + proposal pending | Mahasiswa lihat proposal; pembimbing1 & pembimbing2 terima/tolak |
| `mahasiswa6` | `content_ok` + pembimbing1 accepted | pembimbing2 masih bisa terima/tolak |
| `mahasiswa7` | `content_ok` + proposal ditolak penguji + ulang ke pembimbing | Uji riwayat penolakan + proposal baru |
| `mahasiswa8` | `finalized` | Pembimbing sudah terisi di `guide_examiners` |

Proposal simulasi memakai pasangan pembimbing **`pembimbing1`** + **`pembimbing2`**. Proposal ditolak pada `mahasiswa7` memakai **`penguji1`** + **`penguji2`**.

---

## Role: Manajer NUIR

**Login:** `manajer1` / `simulasi` → otomatis masuk **`/nuir-manajer`**

### Fitur Filament

| Menu | URL | Kegunaan |
|---|---|---|
| Dashboard | `/nuir-manajer` | Ringkasan panel |
| Submission NUIR | `/nuir-manajer/nuir-submissions` | Daftar submission (bukan draft) |
| Detail submission | `/nuir-manajer/nuir-submissions/{id}` | Delegasi validator, setujui konten, minta revisi |

### Langkah uji cepat

1. Buka submission `mahasiswa2` → **Delegasikan Validator** ke `validator1`.
2. Buka submission `mahasiswa3` (10 ref approved) → **Setujui Konten**.
3. Buka submission `mahasiswa2` → **Minta Revisi** + catatan (calon pembimbing & mahasiswa melihat feedback).

---

## Role: Validator NUIR

**Login:** `validator1` / `simulasi` → otomatis masuk **`/nuir-validator`**

Hanya melihat submission yang sudah didelegasikan manajer. Submission **draft** tidak muncul.

### Fitur Filament

| Menu | URL | Kegunaan |
|---|---|---|
| Dashboard | `/nuir-validator` | Ringkasan panel |
| Validasi Referensi | `/nuir-validator/nuir-submissions` | Daftar submission ditugaskan |
| Detail + tab Referensi | `/nuir-validator/nuir-submissions/{id}` | Setujui/tolak referensi |

### Langkah uji cepat

1. Login `validator1` → buka submission `mahasiswa2`.
2. Tab **Referensi** → setujui/tolak referensi (feedback langsung tampil di mahasiswa).
3. Pastikan submission draft (`mahasiswa1`) **tidak** muncul di daftar validator.

---

## Role: DBS

**Login:** `dbs` / `simulasi` → otomatis masuk **`/dbs`**

### Fitur yang dapat diakses

| Menu Filament | URL | Uji dengan |
|---|---|---|
| Dashboard | `/dbs` | Statistik ujian |
| Konfigurasi NUIR | `/dbs/nuir-settings` | Lihat setting angkatan 2099 |
| Review Submission | `/dbs/nuir-submissions` | Buka submission `mahasiswa2`, `mahasiswa3`, `mahasiswa4` |
| Monitor Usulan Calon Pembimbing | `/dbs/nuir-proposals` | Force finalize jika kedua pembimbing sudah accept |
| Kuota / Kelompok / Ujian | menu Manajemen Seleksi & Ujian | Sesuai permission DBS |

### Langkah uji cepat

1. **Review referensi** — login `dbs` → Review Submission → buka submission milik `mahasiswa2` → setujui/tolak referensi.
2. **Setujui konten** — buka submission `mahasiswa3` (10 referensi sudah approved) → **Setujui Konten**.
3. **Minta revisi** — buka submission `mahasiswa2` → **Minta Revisi** + isi catatan.
4. **Monitor usulan** — Monitor Usulan Calon Pembimbing → lihat usulan `mahasiswa5`–`mahasiswa8` → **Force Finalize** bila keduanya `accepted`.

---

## Role: Pembimbing (Dosen)

**Login:** `pembimbing1` atau `pembimbing2` / `simulasi` → **`/home`**

### Fitur NUIR

| Fitur | URL | Keterangan |
|---|---|---|
| Usulan NUIR masuk | `/nuir/dosen` | Usulan calon pembimbing yang ditujukan ke akun login |
| Dashboard dosen | `/home` | Penilaian ujian & statistik |

### Langkah uji per akun

**`pembimbing1`**

- Lihat proposal pending dari `mahasiswa5` (belum direspons).
- Lihat proposal `mahasiswa6` (pembimbing1 sudah **accepted**).
- Lihat proposal ulang `mahasiswa7` (pending ke pembimbing1 & pembimbing2).

**`pembimbing2`**

- Terima/tolak proposal `mahasiswa5` dan `mahasiswa6` (masih pending di guide2).
- Terima proposal ulang `mahasiswa7`.

Setelah **keduanya accept** pada proposal yang sama, status mahasiswa menjadi `finalized` dan `guide_examiners` terisi otomatis (contoh: `mahasiswa8` sudah finalized).

---

## Role: Penguji (Dosen)

**Login:** `penguji1`, `penguji2`, atau `penguji3` / `simulasi` → **`/home`**

Penguji **tidak** menerima proposal NUIR kecuali ikut sebagai pasangan proposal (simulasi penolakan `mahasiswa7` memakai `penguji1` + `penguji2`).

### Fitur yang dapat diakses

| Fitur | URL |
|---|---|
| Panel penilaian dosen | `/home` |
| Penilaian belum selesai | `/home/examination/scoring` |

Slot penguji (`penguji1`–`penguji3`) sudah terhubung ke `guide_examiners` mahasiswa simulasi untuk keperluan ujian skripsi.

---

## Role: Mahasiswa

**Login:** `mahasiswa1` … `mahasiswa8` / `simulasi` → **`/mahasiswa`**

Card **Pengajuan NUIR** muncul karena angkatan 2099 aktif stage 1.

### Fitur yang dapat diakses

| Fitur | URL | Permission |
|---|---|---|
| Dashboard | `/mahasiswa` | Ujian, seleksi tahap 2, shortcut NUIR |
| NUIR Saya | `/mahasiswa/nuir-submission` | Draft, submit, revisi |
| Usulan calon pembimbing | `/mahasiswa/nuir-proposal` | Ajukan calon pembimbing setelah submit (sebelum/sambil review) |

### Langkah uji per akun

| Akun | Yang bisa dicoba |
|---|---|
| `mahasiswa1` | Edit draft → submit ke DBS |
| `mahasiswa2` | Lihat feedback validator; usulan calon pembimbing sudah masuk ke pembimbing |
| `mahasiswa3` | Tunggu DBS setujui konten, lalu buat usulan calon pembimbing |
| `mahasiswa4` | Buka form revisi v2 (parent v1 status `revision`) |
| `mahasiswa5` | Lihat usulan pending ke pembimbing1 & pembimbing2 |
| `mahasiswa6` | Lihat usulan sebagian diterima |
| `mahasiswa7` | Lihat riwayat usulan ditolak + usulan aktif |
| `mahasiswa8` | Lihat status finalized (pembimbing sudah ditetapkan) |

---

## Alur End-to-End (Recommended)

1. **`mahasiswa1`** — lengkapi draft → submit.
2. **`manajer1`** — delegasikan submission ke `validator1`.
3. **`validator1`** — review referensi submission `mahasiswa2`.
4. **`manajer1`** — setujui konten submission `mahasiswa3`.
5. **`mahasiswa3`** — buat usulan calon pembimbing ke `pembimbing1` + `pembimbing2`.
6. **`pembimbing1`** & **`pembimbing2`** — review referensi; terima usulan setelah NUIR `content_ok`.
7. **`dbs`** — Monitor Usulan Calon Pembimbing → verifikasi status finalized bila perlu.

---

## Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| Card NUIR tidak muncul | Setting angkatan tidak aktif / stage 3 | Jalankan ulang seed; pastikan setting 2099 aktif |
| Tidak bisa buat submission baru | Sudah ada submission `finalized` | Login `mahasiswa8` hanya lihat; gunakan akun lain |
| Pembimbing tidak melihat usulan | Bukan guide1/guide2 pada proposal | Pastikan login `pembimbing1`/`pembimbing2` |
| Validator tidak melihat submission | Belum didelegasikan manajer | Login `manajer1` → delegasikan validator |
| Manajer/validator tidak masuk panel | Role salah | Login `manajer1` / `validator1` |

---

## Referensi Teknis

- Seeder akun: `database/seeders/NuirSimulationAccountSeeder.php`
- Seeder data NUIR: `database/seeders/NuirSeeder.php`
- Test akses: `tests/Feature/NuirSimulationAccessTest.php`
- Test panel Filament: `tests/Feature/Filament/NuirManajerPanelSmokeTest.php`, `tests/Feature/Filament/NuirValidatorPanelSmokeTest.php`
