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

### Batas kata & referensi (setting angkatan 2099)

| Parameter | Nilai simulasi | Diterapkan saat simpan |
|---|---|---|
| Judul | min 3 / max 20 kata | Hanya **max** (min = petunjuk counter UI) |
| Novelty, Urgency, Impact | min 12 / max 300 kata | Hanya **max** (min = petunjuk counter UI) |
| Referensi | min 10 disetujui, max 10 slot | Validator |

---

## Daftar Akun per Role

| Username | Role | Password | Panel / Entry |
|---|---|---|---|
| `dbs` | DBS | `simulasi` | `/dbs` |
| `manajer1` | Manajer NUIR | `simulasi` | `/nuir-manajer` |
| `validator1` | Validator NUIR | `simulasi` | `/nuir-validator` |
| `pembimbing1` | Dosen (calon P1) + Manajer NUIR + Validator NUIR | `simulasi` | `/home` → `/nuir/dosen`, `/nuir-manajer`, `/nuir-validator` |
| `pembimbing2` | Dosen (calon P2) | `simulasi` | `/home` → `/nuir/dosen` |
| `penguji1` | Dosen (penguji) | `simulasi` | `/home` |
| `penguji2` | Dosen (penguji) | `simulasi` | `/home` |
| `penguji3` | Dosen (hanya kuota P2) | `simulasi` | `/home` |
| `mahasiswa1` … `mahasiswa8` | Mahasiswa | `simulasi` | `/mahasiswa` |
| `mahasiswa9` | Mahasiswa (belum mengajukan NUIR) | `simulasi` | `/mahasiswa` |

> **Catatan:** Akun ini hanya untuk development/staging. Jangan dipakai di production.

> **Catatan `pembimbing1`:** akun ini sengaja diberi role tambahan **Manajer NUIR** dan **Validator NUIR** (selain **Dosen**) agar satu login bisa dipakai menjelajahi ketiga panel NUIR (`/home` → `/nuir/dosen`, `/nuir-manajer`, `/nuir-validator`) tanpa perlu berpindah akun saat simulasi.

---

## Skenario Data per Mahasiswa

| Akun | Status NUIR | Kegunaan uji |
|---|---|---|
| `mahasiswa1` | `title_slot` (hanya judul) | Workspace: judul sudah disimpan; isi Novelty/Urgency/Impact per tombol Simpan |
| `mahasiswa2` | `submitted` + proposal + histori revisi | **Impact**: revisi ganda P1+P2 (belum diperbaiki); referensi campuran + histori validator |
| `mahasiswa3` | `submitted` + 10 ref disetujui | Semua referensi lulus validasi; siap diajukan ke pembimbing |
| `mahasiswa4` | `submitted` + **3 ref diminta revisi** + revisi NUI | Referensi #5–7 ditolak validator (catatan spesifik); Novelty diminta revisi P1, Impact diminta revisi P2 |
| `mahasiswa5` | `content_ok` + **P2 dibatalkan manajer** | P1 pending; P2 dibatalkan oleh `manajer1` (histori tercatat); mahasiswa dapat pilih ulang calon P2 |
| `mahasiswa6` | `content_ok` + P1 accepted, P2 pending | P1 sudah terima kursi; P2 belum merespons |
| `mahasiswa7` | `content_ok` + penolakan penguji + proposal ulang | Histori penolakan usulan + kursi baru ke pembimbing1/2 |
| `mahasiswa8` | `finalized` | Keduanya accepted; pembimbing terisi di `guide_examiners` |
| `mahasiswa9` | belum ada submission | Form Judul kosong; submission `title_slot` dibuat otomatis saat simpan judul pertama |

Proposal aktif memakai **`pembimbing1`** (P1) + **`pembimbing2`** (P2). Penolakan pada `mahasiswa7` memakai **`penguji1`** + **`penguji2`**.

---

## Skenario khusus: referensi diminta revisi + revisi NUI (mahasiswa4)

**Akun uji:** `mahasiswa4` → `/mahasiswa/nuir-submission`

Kondisi seeder: submission `submitted`; validator sudah menolak 3 referensi dengan catatan spesifik; P1 meminta revisi Novelty dan P2 meminta revisi Impact; mahasiswa belum memperbaiki keduanya.

### Referensi (`mahasiswa4`)

| Slot | Status badge | Catatan validator | Bagian yang perlu diperbaiki |
|---|---|---|---|
| #1–#4 | Disetujui Validator | — | — |
| #5 | **Diminta Revisi Validator** | *Link OJS tidak dapat diakses, halaman 404.* | Link OJS |
| #6 | **Diminta Revisi Validator** | *Jurnal ini terindeks Scopus, bukan SINTA — perbaiki nama indexer dan link index.* | Nama Indexer, Link Index |
| #7 | **Diminta Revisi Validator** | *Kutipan tidak relevan dengan variabel penelitian. Perbaiki kutipan dan uraian relevansi.* | Kutipan, Relevansi |
| #8–#10 | Menunggu Respon Validator | — | — |

Setiap referensi yang ditolak menampilkan catatan validator dan bagian yang perlu diperbaiki. Mahasiswa dapat langsung mengedit field yang bersangkutan dan menekan **Simpan Referensi #N**.

### Komponen NUIR (`mahasiswa4`)

| Komponen | P1 | P2 | Tampilan workspace |
|---|---|---|---|
| Novelty | **Diminta Revisi** | Disetujui | Accordion terbuka; banner catatan P1; textarea editable; tombol **Simpan Revisi Novelty** |
| Urgency | Disetujui | Disetujui | Accordion tertutup; badge hijau P1/P2; teks readonly hijau |
| Impact | Disetujui | **Diminta Revisi** | Accordion terbuka; banner catatan P2; textarea editable; tombol **Simpan Revisi Impact** |

Catatan simulasi:
- **P1 (Novelty):** *Kebaruan penelitian perlu lebih spesifik — bandingkan dengan literatur terkini.*
- **P2 (Impact):** *Indikator dampak belum terukur — sertakan metrik kuantitatif yang spesifik.*

---

## Skenario khusus: revisi NUI ganda (P1 + P2), mahasiswa belum memperbaiki

**Akun uji:** `mahasiswa2` → `/mahasiswa/nuir-submission`

Kondisi seeder: usulan sudah ke P1/P2; Novelty & Urgency disetujui keduanya; **Impact** diminta revisi oleh **P1 dan P2** dengan catatan berbeda; mahasiswa **belum** menekan **Simpan Revisi Impact**.

### Komponen NUIR (`mahasiswa2`)

| Komponen | P1 | P2 | Tampilan workspace |
|---|---|---|---|
| Novelty | Disetujui | Disetujui | Accordion tertutup; badge hijau P1/P2; teks readonly hijau |
| Urgency | Disetujui | Disetujui | Accordion tertutup; badge hijau P1/P2; teks readonly hijau |
| **Impact** | Diminta revisi | Diminta revisi | Accordion **terbuka**; badge **P1: Diminta Revisi** + **P2: Diminta Revisi**; banner **Catatan Revisi** berisi catatan P1 **dan** P2; textarea editable; tombol **Simpan Revisi Impact** |

Catatan simulasi Impact:
- **P1:** *Uraikan manfaat praktis bagi pemangku kebijakan daerah.*
- **P2:** *Tambahkan indikator dampak jangka panjang yang dapat diukur.*

Histori accordion Impact menampilkan dua entri permintaan revisi. Setelah **Simpan Revisi Impact**, review pembimbing di-reset dan badge kembali *Menunggu*.

### Referensi (`mahasiswa2`)

| Slot | Status badge | Catatan |
|---|---|---|
| #1–#5 | Disetujui Validator | Readonly |
| #6–#7 | Menunggu Respon Validator | Mahasiswa sudah perbaiki kutipan (reset validasi) |
| #8 | Menunggu Respon Validator | Ada histori revisi validator; menunggu validasi ulang |
| #9–#10 | Menunggu Respon Validator | Belum divalidasi |

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

Peran manajer: **monitor submission**, **delegasi validator**, dan **atur konfigurasi/kuota**.

### Dashboard (kartu statistik)

| Kartu | Filter daftar |
|---|---|
| Submission Aktif | Semua submission non-draft |
| Belum Didelegasikan | Belum punya validator |
| Menunggu Review | Status `submitted` |
| Diminta Revisi | Status `revision` |
| Konten Disetujui | Status `content_ok` |

### Fitur Filament

| Menu | URL | Kegunaan |
|---|---|---|
| Dashboard | `/nuir-manajer` | Ringkasan kartu statistik |
| Submission NUIR | `/nuir-manajer/nuir-submissions` | Daftar submission (bukan draft); kolom validator & progress validasi referensi; tombol **Dashboard** di kanan atas kembali ke halaman **`/home`** (rute otomatis sesuai role — dosen/manajer/validator tetap ke `/home`) |
| Detail submission | `/nuir-manajer/nuir-submissions/{id}` | Lihat konten/referensi + histori revisi; **Delegasikan/Ubah** validator; **Batalkan Calon P1/P2**; tombol **Kembali ke Daftar Submission** di kanan atas kembali ke `/nuir-manajer/nuir-submissions` |
| Konfigurasi NUIR | `/nuir-manajer/nuir-settings` | Batas kata (judul & NUI), min/max referensi, deadline |
| Kuota Pembimbing | `/nuir-manajer/guide-allocations` | Atur kuota P1/P2 per dosen (tahun 2099) |

Filter daftar: `?view=unassigned`, `?view=submitted`, `?view=revision`, `?view=content_ok`.

### Card Pengusulan Pembimbing

Setiap halaman detail submission (`/nuir-manajer/nuir-submissions/{id}`) memiliki section **Pengusulan Pembimbing** yang:
- Menampilkan daftar semua usulan dari yang terbaru ke yang lama.
- Untuk usulan aktif (non-final): tombol **Batalkan Calon P1** / **Batalkan Calon P2** di sudut kanan atas section.
- Setiap seat menampilkan nama dosen, badge status (Menunggu respons / Diterima / Ditolak), catatan penolakan bila ada.
- Jika sebuah seat pernah dibatalkan manajer, muncul informasi pembatalan (tanggal + aktor + alasan) di bawah nama seat tersebut.

**Alur pembatalan:**
1. Buka detail submission `mahasiswa5` (`/nuir-manajer/nuir-submissions/{id}`).
2. Section **Pengusulan Pembimbing** → klik **Batalkan Calon P2**.
3. Isi catatan (opsional) → konfirmasi → histori pembatalan tercatat.
4. Mahasiswa login → workspace P2 kembali ke dropdown kosong; dapat pilih calon baru.

Pembatalan hanya memengaruhi kursi yang dipilih — kursi lain tidak berubah.

### Langkah uji cepat

1. **Kuota** — filter tahun **2099** → lihat kuota `pembimbing1`/`pembimbing2`.
2. **Delegasi** — `mahasiswa2` sudah didelegasikan ke `validator1` (seeder); coba **Ubah** validator pada submission lain.
3. **Monitor progress** — buka `mahasiswa3` → progress validasi referensi 10/10 disetujui.
4. **Filter dashboard** — klik kartu **Belum Didelegasikan** / **Konten Disetujui**.
5. **Pembatalan calon pembimbing** — buka `mahasiswa5` → section Pengusulan Pembimbing → verifikasi P2 sudah dibatalkan seeder; coba **Batalkan Calon P1**.

---

## Role: Validator NUIR

**Login:** `validator1` / `simulasi` → **`/nuir-validator`**

Hanya melihat submission yang sudah didelegasikan manajer. `mahasiswa9` (belum ada submission) **tidak** muncul.

### Dashboard (kartu statistik)

| Kartu | Daftar terkait |
|---|---|
| Submission Ditugaskan | `/nuir-validator/nuir-submissions` |
| Referensi Pending | `/nuir-validator/nuir-references?view=pending_references` |
| Validasi Selesai | `/nuir-validator/nuir-submissions?view=validation_complete` |
| Permintaan Revisi | `/nuir-validator/nuir-references?view=awaiting_revalidation` |

### Fitur Filament

| Menu | URL | Kegunaan |
|---|---|---|
| Detail validasi | `/nuir-validator/nuir-submissions/{id}` | Setujui/minta revisi per referensi; fokus via `?reference={id}` |

Saat **Minta Revisi** referensi: catatan wajib + pilih **Bagian yang perlu diperbaiki**. Tombol kembali mengikuti daftar asal (`return` query).

Interaksi tombol per referensi:
- Klik **Minta Revisi** langsung menyembunyikan tombol **Setujui**/**Minta Revisi** dan menampilkan form catatan; klik **Batal** pada form mengembalikan kedua tombol tadi.
- Mencentang **Bagian yang perlu diperbaiki** otomatis menambahkan baris berformat `(Nama Bagian): ` ke textarea catatan (mis. `(Link OJS): catatannya`) supaya validator tinggal melengkapi setelah titik dua; centang dilepas → barisnya ikut terhapus.
- Setelah referensi **Disetujui**, muncul tombol **Batalkan Persetujuan** untuk mengembalikan referensi ke status pending (mis. jika validator salah klik Setujui).
- **Lihat histori revisi** ditampilkan **setelah** tombol aksi (Setujui/Minta Revisi atau Batalkan Persetujuan), bukan sebelumnya.

### Langkah uji cepat

1. Kartu **Submission Ditugaskan** → buka `mahasiswa2`.
2. Validasi referensi — **Setujui** atau **Minta Revisi** (catatan + bagian wajib).
3. Kartu **Permintaan Revisi** — referensi #8 `mahasiswa2` dan referensi #5–7 `mahasiswa4` menunggu validasi ulang dari mahasiswa.
4. Buka `mahasiswa4` — lihat catatan revisi spesifik per referensi (link rusak, indexer salah, kutipan tidak relevan).
5. Coba **Setujui** salah satu referensi lalu klik **Batalkan Persetujuan** — status kembali *Pending* dan tombol Setujui/Minta Revisi muncul lagi.

---

## Role: DBS

**Login:** `dbs` / `simulasi` → **`/dbs`**

DBS bertanggung jawab memantau submission, mengatur konfigurasi NUIR, dan memonitor usulan calon pembimbing. **Persetujuan konten NUI dilakukan langsung oleh pembimbing**, bukan DBS.

### Fitur yang dapat diakses

| Menu Filament | URL | Kegunaan |
|---|---|---|
| Konfigurasi NUIR | `/dbs/nuir-settings` | Setting angkatan 2099 (buat/edit) |
| Monitor Submission | `/dbs/nuir-submissions` | Lihat status dan histori seluruh submission |
| Monitor Usulan Calon Pembimbing | `/dbs/nuir-proposals` | Force finalize bila kedua pembimbing sudah accept |

### Langkah uji cepat

1. Monitor Submission → lihat progress `mahasiswa2`, `mahasiswa3`, `mahasiswa4`.
2. Monitor Usulan → `mahasiswa5`–`mahasiswa8` → **Force Finalize** bila perlu.

---

## Role: Pembimbing (Dosen calon P1/P2)

**Login:** `pembimbing1` atau `pembimbing2` / `simulasi` → **`/home`** → **Usulan NUIR** (`/nuir/dosen`)

> `pembimbing1` juga punya akses `/nuir-manajer` dan `/nuir-validator` (lihat catatan di [Daftar Akun per Role](#daftar-akun-per-role)).

### Alur review NUI (wajib sebelum kursi diterima)

1. Setujui atau **minta revisi** per elemen: **Judul**, **Novelty**, **Urgency**, **Impact** (catatan wajib jika minta revisi).
2. Setelah **semua elemen disetujui oleh kedua pembimbing**, status judul dan NUI berubah menjadi *Disetujui* dan kursi pembimbing otomatis **accepted**.
3. Finalisasi terjadi jika **P1 dan P2** keduanya accept.
4. Setiap elemen (Judul/Novelty/Urgency/Impact) maupun referensi yang sudah **Disetujui** menampilkan tombol **Batalkan** untuk mengembalikan review tersebut ke *Pending* — berguna bila pembimbing salah klik Setujui. Membatalkan persetujuan elemen NUI mengembalikan kursi ke *pending* jika sebelumnya sudah accepted.

### Langkah uji per akun

**`pembimbing1` (P1):** `mahasiswa4` (Novelty diminta revisi oleh P1 — lihat catatan), `mahasiswa5` (review dari awal), `mahasiswa6` (P1 sudah accepted).

**`pembimbing2` (P2):** `mahasiswa4` (Impact diminta revisi oleh P2), `mahasiswa5` & `mahasiswa6` (lanjutkan review), `mahasiswa7` (terima/tolak ulang).

**`mahasiswa2`:** Novelty/Urgency disetujui; **Impact** sudah diminta revisi P1 **dan** P2 — mahasiswa belum simpan perbaikan.

---

## Role: Penguji (Dosen)

**Login:** `penguji1`, `penguji2`, atau `penguji3` / `simulasi` → **`/home`**

Simulasi penolakan `mahasiswa7` memakai `penguji1` + `penguji2`. `penguji3` hanya kuota P2 — uji filter dropdown usulan mahasiswa.

---

## Role: Mahasiswa

**Login:** `mahasiswa1` … `mahasiswa9` / `simulasi` → **`/mahasiswa`**

### Fitur yang dapat diakses

| Fitur | URL | Kegunaan |
|---|---|---|
| Dashboard | `/mahasiswa` | Widget statistik referensi + **Status Pengajuan NUIR** (Judul, Novelty, Urgency, Impact) |
| Pengajuan NUIR | `/mahasiswa/nuir-submission` | Workspace accordion: judul → usulan pembimbing → NUI → referensi |
| Usulan Calon Pembimbing | `/mahasiswa/nuir-proposal` | Ringkasan usulan (tidak di menu navigasi) |

### Alur workspace (penting)

1. **Judul** — isi dan **Simpan Judul** (submission `title_slot` dibuat otomatis jika belum ada).
2. Setelah judul tersimpan muncul: **Usulan Calon Pembimbing**, **Komponen NUIR**, **Referensi**.
3. Setiap kartu memakai **accordion** dengan counter kata (min = petunjuk, max = validasi).
4. Jika sudah ada usulan pembimbing, komponen NUI menampilkan badge **per pembimbing** (`P1: …`, `P2: …`); referensi memakai badge status validator.
5. Selama status usulan **menunggu respons** (belum diterima/ditolak pembimbing), kartu Usulan Calon Pembimbing menampilkan tombol **Batalkan Usulan** per kursi (P1/P2) — mahasiswa dapat membatalkan sendiri usulannya sebelum direspons dosen. Tombol hilang begitu pembimbing menerima usulan tersebut.
6. Histori usulan membedakan siapa yang membatalkan: **Dibatalkan Mahasiswa** (mahasiswa memakai tombol Batalkan Usulan) vs **Dibatalkan Manajer** (manajer memakai tombol Batalkan Calon P1/P2 di panel `/nuir-manajer`).

### Langkah uji per akun

| Akun | Yang bisa dicoba |
|---|---|
| `mahasiswa9` | Form Judul kosong → **Simpan Judul** |
| `mahasiswa1` | Judul ada; lanjutkan isi NUI per komponen |
| `mahasiswa2` | **Impact**: badge P1+P2 revisi, banner catatan ganda, **Simpan Revisi Impact**; referensi campuran |
| `mahasiswa3` | Semua referensi lulus; ajukan P1 dan P2 dari workspace |
| `mahasiswa4` | **Referensi #5–7**: badge "Diminta Revisi Validator", catatan spesifik per slot, edit & simpan ulang; **Novelty/Impact**: banner revisi pembimbing, simpan perbaikan |
| `mahasiswa5` | P1 masih pending; P2 dibatalkan manajer → **histori P2** menampilkan Diusulkan + Dibatalkan; workspace P2 bisa pilih calon baru |
| `mahasiswa6` | P1 accepted → **histori P1** menampilkan Diusulkan + Diterima; P2 pending |
| `mahasiswa7` | **Histori P1 & P2**: Diusulkan (penguji) → Ditolak → Diusulkan kembali (pembimbing); lihat timeline lengkap |
| `mahasiswa8` | Status finalized; **histori** keduanya menampilkan Diusulkan + Diterima |

---

## Alur End-to-End (Recommended)

1. **`mahasiswa9`** — **Simpan Judul** (submission otomatis).
2. **`manajer1`** — verifikasi delegasi seeder; coba delegasi ulang pada submission lain.
3. **`validator1`** — review referensi `mahasiswa2`; buka `mahasiswa4` → lihat 3 referensi yang sudah ditolak.
4. **`mahasiswa4`** — perbaiki referensi #5–7 (edit field yang diminta) + perbaiki Novelty & Impact.
5. **`mahasiswa2`** — verifikasi **Impact** (badge P1+P2, catatan ganda, **Simpan Revisi Impact**).
6. **`mahasiswa3`** — ajukan P1 lalu P2 dari workspace (semua referensi sudah lulus).
7. **`pembimbing1`** & **`pembimbing2`** — setujui N/U/I pada `mahasiswa5`.
8. Verifikasi kursi **accepted** → **finalized** bila keduanya selesai.
9. **`mahasiswa7`** — histori penolakan; **`pembimbing1`** lihat jejak yang sama.

---

## Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| Card NUIR tidak muncul | Setting angkatan tidak aktif / stage 3 | Jalankan ulang seed; pastikan setting 2099 aktif |
| NUI/referensi/pembimbing tidak muncul | Judul belum disimpan | Simpan judul terlebih dahulu |
| Simpan NUI gagal (notifikasi error) | Melebihi batas **max** kata | Perpendek teks; min kata hanya petunjuk UI |
| Dropdown pembimbing kosong | Kuota P1/P2 habis | `manajer1` → tambah kuota tahun 2099 |
| `penguji3` tidak muncul di P1 | Kuota P1 = 0 | Pilih posisi P2 |
| Validator tidak melihat submission | Belum didelegasikan | `manajer1` → delegasikan validator |
| Referensi tidak menampilkan catatan revisi | Ref belum ditolak oleh validator | Gunakan `mahasiswa4` (slot #5–7 sudah ditolak seeder) |
| Simpan revisi referensi tidak muncul | Referensi berstatus disetujui / readonly | Gunakan slot yang berstatus "Diminta Revisi Validator" |
| Impact hanya badge P1 revisi | Data seeder stale | Jalankan ulang `NuirSeeder` |
| Kursi tidak accepted otomatis | N/U/I belum semua disetujui oleh kedua pembimbing | Setujui ketiga elemen NUI dari kedua akun pembimbing |
| Minta revisi referensi gagal | Catatan/bagian kosong | Isi keduanya |
| Link referensi tidak bisa diklik | Referensi berstatus editable | Klik buka di editable mode via tombol "Buka ↗" di samping label |

---

## Referensi Teknis

- Seeder akun: `database/seeders/NuirSimulationAccountSeeder.php`
- Seeder data NUIR: `database/seeders/NuirSeeder.php`
- Test akses: `tests/Feature/NuirSimulationAccessTest.php`
- Test konsistensi data: `tests/Feature/NuirSeederTest.php`
- Test panel Filament: `tests/Feature/Filament/NuirManajerPanelSmokeTest.php`, `NuirValidatorPanelSmokeTest.php`
