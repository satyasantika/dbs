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

> **Catatan:** Akun ini hanya untuk lingkungan development/staging. Jangan dipakai di production.

---

## Skenario Data per Mahasiswa

| Akun | Status NUIR | Kegunaan uji |
|---|---|---|
| `mahasiswa1` | `draft` | Isi/edit draft, simpan referensi, submit |
| `mahasiswa2` | `submitted` | DBS review referensi (sebagian sudah diproses) |
| `mahasiswa3` | `submitted` + 10 ref approved | DBS setujui konten (`content_ok`) |
| `mahasiswa4` | revisi v1 + draft v2 | Mahasiswa lanjut revisi, DBS lihat riwayat versi |
| `mahasiswa5` | `content_ok` + proposal pending | Mahasiswa lihat proposal; pembimbing1 & pembimbing2 terima/tolak |
| `mahasiswa6` | `content_ok` + pembimbing1 accepted | pembimbing2 masih bisa terima/tolak |
| `mahasiswa7` | `content_ok` + proposal ditolak penguji + ulang ke pembimbing | Uji riwayat penolakan + proposal baru |
| `mahasiswa8` | `finalized` | Pembimbing sudah terisi di `guide_examiners` |

Proposal simulasi memakai pasangan pembimbing **`pembimbing1`** + **`pembimbing2`**. Proposal ditolak pada `mahasiswa7` memakai **`penguji1`** + **`penguji2`**.

---

## Role: DBS

**Login:** `dbs` / `simulasi` → otomatis masuk **`/dbs`**

### Fitur yang dapat diakses

| Menu Filament | URL | Uji dengan |
|---|---|---|
| Dashboard | `/dbs` | Statistik ujian |
| Konfigurasi NUIR | `/dbs/nuir-settings` | Lihat setting angkatan 2099 |
| Review Submission | `/dbs/nuir-submissions` | Buka submission `mahasiswa2`, `mahasiswa3`, `mahasiswa4` |
| Monitor Proposal | `/dbs/nuir-proposals` | Force finalize jika kedua pembimbing sudah accept |
| Kuota / Kelompok / Ujian | menu Manajemen Seleksi & Ujian | Sesuai permission DBS |

### Langkah uji cepat

1. **Review referensi** — login `dbs` → Review Submission → buka submission milik `mahasiswa2` → setujui/tolak referensi.
2. **Setujui konten** — buka submission `mahasiswa3` (10 referensi sudah approved) → **Setujui Konten**.
3. **Minta revisi** — buka submission `mahasiswa2` → **Minta Revisi** + isi catatan.
4. **Monitor proposal** — Monitor Proposal → lihat proposal `mahasiswa5`–`mahasiswa8` → **Force Finalize** bila keduanya `accepted`.

---

## Role: Pembimbing (Dosen)

**Login:** `pembimbing1` atau `pembimbing2` / `simulasi` → **`/home`**

### Fitur NUIR

| Fitur | URL | Keterangan |
|---|---|---|
| Usulan NUIR masuk | `/nuir/dosen` | Proposal yang ditujukan ke akun login |
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
| Proposal Pembimbing | `/mahasiswa/nuir-proposal` | Ajukan calon pembimbing setelah `content_ok` |

### Langkah uji per akun

| Akun | Yang bisa dicoba |
|---|---|
| `mahasiswa1` | Edit draft → submit ke DBS |
| `mahasiswa2` | Lihat status submitted (menunggu DBS) |
| `mahasiswa3` | Tunggu DBS setujui konten, lalu buat proposal |
| `mahasiswa4` | Buka form revisi v2 (parent v1 status `revision`) |
| `mahasiswa5` | Lihat proposal pending ke pembimbing1 & pembimbing2 |
| `mahasiswa6` | Lihat proposal sebagian diterima |
| `mahasiswa7` | Lihat riwayat proposal ditolak + proposal aktif |
| `mahasiswa8` | Lihat status finalized (pembimbing sudah ditetapkan) |

---

## Alur End-to-End (Recommended)

1. **`mahasiswa1`** — lengkapi draft → submit.
2. **`dbs`** — review referensi & konten submission `mahasiswa3` → setujui konten.
3. **`mahasiswa3`** — buat proposal ke `pembimbing1` + `pembimbing2`.
4. **`pembimbing1`** & **`pembimbing2`** — terima proposal.
5. **`dbs`** — Monitor Proposal → verifikasi status finalized / force finalize bila perlu.

---

## Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| Card NUIR tidak muncul | Setting angkatan tidak aktif / stage 3 | Jalankan ulang seed; pastikan setting 2099 aktif |
| Tidak bisa buat submission baru | Sudah ada submission `finalized` | Login `mahasiswa8` hanya lihat; gunakan akun lain |
| Pembimbing tidak melihat usulan | Bukan guide1/guide2 pada proposal | Pastikan login `pembimbing1`/`pembimbing2` |
| DBS tidak masuk panel | Role bukan `dbs` | Login dengan akun `dbs` |

---

## Referensi Teknis

- Seeder akun: `database/seeders/NuirSimulationAccountSeeder.php`
- Seeder data NUIR: `database/seeders/NuirSeeder.php`
- Test akses: `tests/Feature/NuirSimulationAccessTest.php`
