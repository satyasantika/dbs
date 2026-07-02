# Prompt NUIR — Cicilan per Tahap

Sistem: Laravel 10, PHPUnit 10, Spatie Permission, Yajra DataTables.
Working dir: `/home/satya/code/dbs`
DB test: gunakan `RefreshDatabase` + seed via `PermissionSeeder` di setiap test.
Commit: jalankan `git add` + `git commit` setelah semua test hijau.

---

## Panduan Navigasi

Baca bagian ini sebelum menjalankan prompt. Detail lengkap tiap prompt ada di bawah.

### Konvensi tiap prompt

| Bagian | Arti |
|--------|------|
| **Prasyarat** | Prompt mana yang harus selesai (test hijau) |
| **Scope** | Yang dikerjakan prompt ini |
| **Out of scope** | Sengaja ditunda ke prompt lain |
| **Konteks Sistem** | Ringkasan state aplikasi |
| **Tugas** | Implementasi |
| **Test / Commit** | Verifikasi lokal, lalu commit |

### Ringkasan urutan eksekusi

| # | Prompt | Test File | Test Command |
|---|---|---|---|
| 1 | Migrasi, Model, Factory | `NuirModelTest` | `php artisan test --filter=NuirModelTest` |
| 2 | Konfigurasi DBS | `NuirSettingTest` | `php artisan test --filter=NuirSettingTest` |
| 3 | Form Konten Mahasiswa | `NuirSubmissionTest` | `php artisan test --filter=NuirSubmissionTest` |
| 4 | DBS Review | `NuirReviewTest` | `php artisan test --filter=NuirReviewTest` |
| 5 | Revisi Versioning | `NuirRevisionTest` | `php artisan test --filter=NuirRevisionTest` |
| 6 | Proposal ke Dosen | `NuirProposalTest` | `php artisan test --filter=NuirProposalTest` |
| 7 | Respons Dosen + Finalisasi | `NuirDosenResponseTest` | `php artisan test --filter=NuirDosenResponseTest` |
| 8 | Stage 2 & 3 | `NuirStageTest` | `php artisan test --filter=NuirStageTest` |
| 9 | Guard Rails + Dashboard | `NuirGuardTest` | `php artisan test --filter=NuirGuardTest` |

**Verifikasi akhir tiap prompt:**
```bash
php artisan test --filter=Nuir
```

### Alur status submission

```text
Stage 1 — submission (Prompt 3–7):
  draft → submitted → revision → content_ok → finalized
         (P3)        (P4)       (P5)          (P7 finalize)

Stage 2 — submission (Prompt 8, butuh P6 proposal + P7 finalize):
  store() langsung content_ok → proposal → finalized

Stage 3 — tanpa submission NUIR (Prompt 8 mahasiswa + Filament existing):
  DBS isi guide_examiners via GuideExaminerResource (detail Prompt 8 §Stage 3)
```

### Stage bisnis vs prompt implementasi

| Stage | Alur mahasiswa | Prompt implementasi |
|-------|----------------|---------------------|
| 1 | NUIR penuh → review DBS → revisi → proposal → dosen | P3–P7 |
| 2 | Judul saja → langsung proposal | P8 (perlu P6, P7) |
| 3 | Tidak ada NUIR; DBS tetapkan pembimbing | P8 (sisi mahasiswa) + Filament existing |

### Mengapa Prompt 8 setelah Prompt 6–7?

Prompt 8 bukan urutan stage bisnis, melainkan **varian alur** yang memperluas controller dari P3 dan P6.
Stage 2 membutuhkan `NuirProposalController` (P6) dan pemahaman finalize/`guide_examiners` (P7).

---

## PROMPT 1 — Fondasi: Migrasi, Model, Factory

**Prasyarat:** Tidak ada (prompt pertama).
**Scope:** 4 migrasi, 4 model, 4 factory, unit test relasi/helper.
**Out of scope:** Controller, permission, view (Prompt 2 ke atas).

### Konteks Sistem
Ini adalah aplikasi DBS (manajemen skripsi mahasiswa) berbasis Laravel 10.
Terdapat tabel `guide_examiners` (kolom: `user_id`, `year_generation`, `guide1_id`, `guide2_id`, dst.)
yang menjadi hasil akhir dari proses NUIR. Sistem menggunakan Spatie Permission dengan role:
`admin`, `dbs`, `dosen`, `mahasiswa`.

### Tugas
Buat 4 migrasi baru, 4 model, dan 4 factory. Jalankan migrasi, lalu jalankan test.

> **Penomoran subsection:** 1–4 = migrasi, 5–8 = model, 9–12 = factory.

#### 1. Migrasi `create_nuir_settings_table`
```php
Schema::create('nuir_settings', function (Blueprint $table) {
    $table->id();
    $table->string('year_generation');           // contoh: "2022"
    $table->tinyInteger('stage');                // 1, 2, atau 3
    $table->boolean('active')->default(false);
    $table->date('deadline')->nullable();
    $table->tinyInteger('min_references_approved')->default(10);
    $table->integer('max_chars_novelty')->nullable();
    $table->integer('max_chars_urgency')->nullable();
    $table->integer('max_chars_impact')->nullable();
    $table->timestamps();
    $table->unique('year_generation');
});
```

#### 2. Migrasi `create_nuir_submissions_table`
```php
Schema::create('nuir_submissions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();             // mahasiswa
    $table->string('year_generation');
    $table->foreignId('parent_submission_id')
          ->nullable()->constrained('nuir_submissions');     // versi sebelumnya
    $table->tinyInteger('version')->default(1);
    $table->text('title');
    $table->longText('novelty')->nullable();                 // hanya stage 1
    $table->longText('urgency')->nullable();
    $table->longText('impact')->nullable();
    $table->string('status')->default('draft');
    // draft → submitted → revision → content_ok → finalized
    $table->foreignId('dbs_reviewer_id')
          ->nullable()->constrained('users');
    $table->text('dbs_note')->nullable();
    $table->timestamp('dbs_reviewed_at')->nullable();
    $table->timestamps();
});
```

#### 3. Migrasi `create_nuir_references_table`
```php
Schema::create('nuir_references', function (Blueprint $table) {
    $table->id();
    $table->foreignId('nuir_submission_id')->constrained();
    $table->tinyInteger('ref_order');                        // 1–10
    $table->string('link_ojs')->nullable();
    $table->string('indexer_name')->nullable();
    // WoS/Scopus/Thomson/Elsevier/Springer/Wiley/Taylor&Francis/DOAJ/Sinta2
    $table->string('link_index')->nullable();
    $table->string('link_drive')->nullable();
    $table->text('quote')->nullable();                       // isi kutipan [page xx]
    $table->text('relevance')->nullable();                   // kaitan dengan penelitian
    $table->boolean('ref_approved')->nullable();             // keputusan DBS
    $table->text('ref_note')->nullable();                    // alasan penolakan DBS
    $table->timestamps();
    $table->unique(['nuir_submission_id', 'ref_order']);
});
```

#### 4. Migrasi `create_nuir_proposals_table`
```php
Schema::create('nuir_proposals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('nuir_submission_id')->constrained();
    $table->foreignId('guide1_id')->constrained('users');
    $table->foreignId('guide2_id')->constrained('users');
    $table->string('guide1_status')->default('pending');     // pending/accepted/rejected
    $table->string('guide2_status')->default('pending');
    $table->text('guide1_note')->nullable();
    $table->text('guide2_note')->nullable();
    $table->timestamp('guide1_responded_at')->nullable();
    $table->timestamp('guide2_responded_at')->nullable();
    $table->boolean('final')->default(false);
    $table->timestamps();
});
```

#### 5. Model `NuirSetting`
- `protected $guarded = ['id']`
- cast: `active => boolean`, `deadline => date`
- scope: `scopeActive($q) { $q->where('active', true); }`

#### 6. Model `NuirSubmission`
- `protected $guarded = ['id']`
- cast: `dbs_reviewed_at => datetime`
- relasi: `belongsTo(User::class, 'user_id')`, `belongsTo(User::class, 'dbs_reviewer_id')`,
  `belongsTo(NuirSubmission::class, 'parent_submission_id')`,
  `hasMany(NuirReference::class)`, `hasMany(NuirProposal::class)`
- helper: `isEditable(): bool { return in_array($this->status, ['draft', 'revision']); }`
- helper: `hasActiveFinalProposal(): bool { return $this->proposals()->where('final', true)->exists(); }`

#### 7. Model `NuirReference`
- `protected $guarded = ['id']`
- cast: `ref_approved => boolean`
- relasi: `belongsTo(NuirSubmission::class)`

#### 8. Model `NuirProposal`
- `protected $guarded = ['id']`
- cast: `final => boolean`, `guide1_responded_at => datetime`, `guide2_responded_at => datetime`
- relasi: `belongsTo(NuirSubmission::class)`, `belongsTo(User::class, 'guide1_id')`,
  `belongsTo(User::class, 'guide2_id')`
- helper: `isBothAccepted(): bool { return $this->guide1_status === 'accepted' && $this->guide2_status === 'accepted'; }`

#### 9. Factory `NuirSettingFactory`
State default: `year_generation='2022'`, `stage=1`, `active=true`, `deadline=null`,
`min_references_approved=10`, `max_chars_*=null`.
State `stage2()`: `stage=2`.
State `stage3()`: `stage=3`.
State `withDeadline(string $date)`: set deadline.

#### 10. Factory `NuirSubmissionFactory`
State default: `user_id` dari mahasiswa random, `year_generation='2022'`, `version=1`,
`parent_submission_id=null`, `status='draft'`, `title=fake()->sentence()`.
State `submitted()`: `status='submitted'`.
State `contentOk()`: `status='content_ok'`.
State `finalized()`: `status='finalized'`.
State `withNUI()`: isi `novelty`, `urgency`, `impact` dengan `fake()->paragraph(5)`.
State `revision()`: `status='revision'`, `dbs_note=fake()->sentence()`.

#### 11. Factory `NuirReferenceFactory`
Default: `ref_order=1`, semua link/text nullable random, `ref_approved=null`.
State `approved()`: `ref_approved=true`.
State `rejected(string $note)`: `ref_approved=false`, `ref_note=$note`.

#### 12. Factory `NuirProposalFactory`
Default: `guide1_status='pending'`, `guide2_status='pending'`, `final=false`.
State `guide1Accepted()`, `guide2Accepted()`, `guide1Rejected(string $note)`, `guide2Rejected(string $note)`.
State `bothAccepted()`: keduanya accepted, `final=true`.

### Test yang Harus Ditulis

File: `tests/Unit/NuirModelTest.php`

```php
<?php
namespace Tests\Unit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{NuirSetting, NuirSubmission, NuirReference, NuirProposal};

class NuirModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_nuir_setting_active_scope_filters_correctly(): void
    {
        NuirSetting::factory()->create(['active' => true, 'year_generation' => '2022']);
        NuirSetting::factory()->create(['active' => false, 'year_generation' => '2023']);
        $this->assertCount(1, NuirSetting::active()->get());
    }

    public function test_nuir_submission_is_editable_only_in_draft_and_revision(): void
    {
        $draft = NuirSubmission::factory()->create(['status' => 'draft']);
        $revision = NuirSubmission::factory()->create(['status' => 'revision']);
        $submitted = NuirSubmission::factory()->create(['status' => 'submitted']);
        $ok = NuirSubmission::factory()->create(['status' => 'content_ok']);

        $this->assertTrue($draft->isEditable());
        $this->assertTrue($revision->isEditable());
        $this->assertFalse($submitted->isEditable());
        $this->assertFalse($ok->isEditable());
    }

    public function test_nuir_submission_version_chain_via_parent(): void
    {
        $v1 = NuirSubmission::factory()->create(['version' => 1]);
        $v2 = NuirSubmission::factory()->create([
            'parent_submission_id' => $v1->id,
            'version' => 2,
        ]);
        $this->assertEquals($v1->id, $v2->parentSubmission->id);
    }

    public function test_nuir_reference_belongs_to_submission(): void
    {
        $sub = NuirSubmission::factory()->create();
        $ref = NuirReference::factory()->create(['nuir_submission_id' => $sub->id]);
        $this->assertEquals($sub->id, $ref->submission->id);
    }

    public function test_nuir_proposal_is_both_accepted_helper(): void
    {
        $proposal = NuirProposal::factory()->bothAccepted()->create();
        $this->assertTrue($proposal->isBothAccepted());

        $pending = NuirProposal::factory()->create();
        $this->assertFalse($pending->isBothAccepted());
    }

    public function test_nuir_submission_has_active_final_proposal(): void
    {
        $sub = NuirSubmission::factory()->create();
        NuirProposal::factory()->bothAccepted()->create(['nuir_submission_id' => $sub->id]);
        $this->assertTrue($sub->hasActiveFinalProposal());
    }

    public function test_nuir_reference_unique_order_per_submission(): void
    {
        $sub = NuirSubmission::factory()->create();
        NuirReference::factory()->create(['nuir_submission_id' => $sub->id, 'ref_order' => 1]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        NuirReference::factory()->create(['nuir_submission_id' => $sub->id, 'ref_order' => 1]);
    }
}
```

### Perintah Test
```bash
php artisan test --filter=NuirModelTest
```

### Pesan Commit
```
feat(nuir): tambah migrasi, model, dan factory untuk sistem NUIR

Menambahkan 4 tabel: nuir_settings, nuir_submissions, nuir_references,
nuir_proposals. Model dilengkapi relasi, cast, dan helper method.
Factory mendukung semua state yang dibutuhkan test tahap berikutnya.

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
```

---

## PROMPT 2 — Konfigurasi NUIR oleh DBS

**Prasyarat:** Prompt 1 selesai, `NuirModelTest` hijau.
**Scope:** CRUD konfigurasi NUIR per angkatan (role DBS).
**Out of scope:** Form mahasiswa (P3), review DBS (P4), guard rails (P9).

### Konteks Sistem
Aplikasi DBS Laravel 10. Sistem NUIR sudah punya 4 tabel dari Prompt 1
(`nuir_settings`, `nuir_submissions`, `nuir_references`, `nuir_proposals`)
beserta model dan factory-nya. Role yang ada: `admin`, `dbs`, `dosen`, `mahasiswa`.
Permission dikelola oleh Spatie Permission. Navigasi DBS didaftarkan di tabel `navigations`
via seeder dengan parent `order='C00'`.

### Tugas
Buat fitur manajemen konfigurasi NUIR khusus role DBS:
daftar setting per angkatan, tambah, edit (aktif/nonaktif, deadline, standar).

#### 1. Permission baru — tambahkan ke `PermissionSeeder` atau buat seeder tersendiri `NuirPermissionSeeder`
```php
// Role DBS
Permission::create(['name' => 'manage nuir settings'])->assignRole('dbs');
Permission::create(['name' => 'access nuir/settings'])->assignRole('dbs');
```

#### 2. Controller `app/Http/Controllers/Setting/Nuir/SettingController.php`
```php
// index()   → DataTable NuirSetting, semua angkatan
// create()  → form tambah setting baru
// store()   → validasi: year_generation unik, stage in [1,2,3],
//             min_references_approved 1–20, max_chars_* nullable integer min 100
// edit($s)  → form edit
// update()  → validasi sama dengan store
// destroy() → hanya jika tidak ada submission terkait
```

Route group (dalam middleware `can:active`):
```php
Route::resource('setting/nuir-settings', \App\Http\Controllers\Setting\Nuir\SettingController::class)
     ->except('show')
     ->middleware('can:manage nuir settings');
Route::put('setting/nuir-settings/{nuirSetting}/toggle',
    [\App\Http\Controllers\Setting\Nuir\SettingController::class, 'toggle'])
    ->name('nuir-settings.toggle')
    ->middleware('can:manage nuir settings');
```

Method `toggle()`: flip kolom `active`, redirect back.

#### 3. DataTable `app/DataTables/NuirSettingsDataTable.php`
Kolom: `year_generation`, `stage`, `active` (badge), `deadline`, `min_references_approved`,
`max_chars_novelty`, `max_chars_urgency`, `max_chars_impact`, tombol Edit.

#### 4. View `resources/views/setting/nuir/setting-index.blade.php`
Extend layout yang sama dengan halaman setting lain. Tombol "Tambah".

#### 5. View `resources/views/setting/nuir/setting-form.blade.php`
Form dengan field: `year_generation` (text), `stage` (select 1/2/3),
`active` (checkbox), `deadline` (date input),
`min_references_approved` (number, 1–20, default 10),
`max_chars_novelty/urgency/impact` (number, nullable, placeholder "kosongkan = tidak dibatasi").

#### 6. Navigasi — tambahkan entry ke menu DBS
Di `NuirPermissionSeeder` atau via `DatabaseSeeder`, tambahkan child navigation
di bawah parent `order='C00'`:
```php
$dbsNav = Navigation::where('order', 'C00')->first();
$dbsNav->children()->create([
    'name' => 'konfigurasi NUIR',
    'url' => 'nuir/settings',
    'order' => 'C0'.($dbsNav->children()->count()+1),
]);
```

### Test yang Harus Ditulis

File: `tests/Feature/NuirSettingTest.php`

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, NuirSetting};
use Database\Seeders\PermissionSeeder;

class NuirSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $dbs;
    protected User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->dbs = User::factory()->create()->assignRole('dbs');
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
    }

    public function test_dbs_can_view_nuir_settings_index(): void
    {
        $this->actingAs($this->dbs)
             ->get('/setting/nuir-settings')
             ->assertOk();
    }

    public function test_non_dbs_cannot_access_nuir_settings(): void
    {
        $this->actingAs($this->mahasiswa)
             ->get('/setting/nuir-settings')
             ->assertForbidden();
    }

    public function test_dbs_can_create_nuir_setting(): void
    {
        $this->actingAs($this->dbs)
             ->post('/setting/nuir-settings', [
                 'year_generation' => '2022',
                 'stage' => 1,
                 'active' => true,
                 'min_references_approved' => 10,
             ])
             ->assertRedirect();

        $this->assertDatabaseHas('nuir_settings', [
            'year_generation' => '2022',
            'stage' => 1,
            'min_references_approved' => 10,
        ]);
    }

    public function test_year_generation_must_be_unique(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022']);

        $this->actingAs($this->dbs)
             ->post('/setting/nuir-settings', [
                 'year_generation' => '2022',
                 'stage' => 1,
             ])
             ->assertSessionHasErrors('year_generation');
    }

    public function test_stage_must_be_1_2_or_3(): void
    {
        $this->actingAs($this->dbs)
             ->post('/setting/nuir-settings', [
                 'year_generation' => '2022',
                 'stage' => 5,
             ])
             ->assertSessionHasErrors('stage');
    }

    public function test_min_references_must_be_between_1_and_20(): void
    {
        $this->actingAs($this->dbs)
             ->post('/setting/nuir-settings', [
                 'year_generation' => '2022',
                 'stage' => 1,
                 'min_references_approved' => 25,
             ])
             ->assertSessionHasErrors('min_references_approved');
    }

    public function test_dbs_can_toggle_active_status(): void
    {
        $setting = NuirSetting::factory()->create(['active' => true]);

        $this->actingAs($this->dbs)
             ->put("/setting/nuir-settings/{$setting->id}/toggle")
             ->assertRedirect();

        $this->assertFalse($setting->fresh()->active);
    }

    public function test_cannot_delete_setting_with_existing_submissions(): void
    {
        $setting = NuirSetting::factory()->create(['year_generation' => '2022']);
        \App\Models\NuirSubmission::factory()->create(['year_generation' => '2022']);

        $this->actingAs($this->dbs)
             ->delete("/setting/nuir-settings/{$setting->id}")
             ->assertRedirect()
             ->assertSessionHas('warning');

        $this->assertDatabaseHas('nuir_settings', ['id' => $setting->id]);
    }
}
```

### Perintah Test
```bash
php artisan test --filter=NuirSettingTest
```

### Pesan Commit
```
feat(nuir): manajemen konfigurasi NUIR per angkatan oleh DBS

DBS dapat mengatur angkatan aktif, tahap (1/2/3), deadline pengajuan,
standar minimum referensi diterima, dan batas karakter N/U/I.
Navigasi DBS diperbaharui, dilengkapi feature test.

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
```

---

## PROMPT 3 — Form Konten NUIR oleh Mahasiswa (Tahap 1: Draft & Submit)

**Prasyarat:** Prompt 1–2 selesai, test hijau.
**Scope:** Hanya `NuirSetting.stage = 1`. Alur `draft → submitted`.
**Out of scope:** Stage 2/3 (P8), review DBS (P4), revisi (P5), guard deadline (P9).

### Konteks Sistem
Aplikasi DBS Laravel 10. Dari Prompt 1 & 2 sudah ada:
- Tabel & model: `NuirSetting`, `NuirSubmission`, `NuirReference`
- DBS bisa kelola konfigurasi via `/setting/nuir-settings`

Role mahasiswa dapat akses halaman sesuai angkatan mereka di `guide_examiners.year_generation`.
NuirSetting dengan `stage=1` dan `active=true` berarti mahasiswa angkatan tersebut
wajib isi NUIR penuh (title + novelty + urgency + impact + 10 referensi).
`NuirSetting` dengan `stage=2` atau `stage=3` ditangani di Prompt 8.
**Prompt ini hanya menangani stage=1.**

Status submission flow: `draft → submitted` (DBS review di Prompt 4).
Mahasiswa hanya boleh punya **satu submission aktif** (status selain `finalized`).

### Tugas

#### 1. Permission baru
```php
Permission::create(['name' => 'create nuir submission'])->assignRole('mahasiswa');
Permission::create(['name' => 'update nuir submission'])->assignRole('mahasiswa');
Permission::create(['name' => 'read nuir submission'])->assignRole('mahasiswa');
Permission::create(['name' => 'access nuir/submission'])->assignRole('mahasiswa');
```
Tambahkan navigasi mahasiswa:
```php
$mahaNav = Navigation::where('order', 'M00')->first();
$mahaNav->children()->create(['name' => 'pengajuan NUIR', 'url' => 'nuir/submission']);
```

#### 2. Controller `app/Http/Controllers/Selection/NuirSubmissionController.php`

```
index()         GET  /nuir/submission
  → Tampilkan status submission aktif + tombol aksi.
  → Jika angkatan tidak aktif stage=1: tampilkan pesan "NUIR belum dibuka".
  → Jika tidak ada submission: tampilkan tombol "Buat Pengajuan NUIR".

create()        GET  /nuir/submission/create
  → Guard: angkatan aktif stage=1, belum ada submission aktif.
  → Tampilkan form kosong.

store()         POST /nuir/submission
  → Validasi: title required, novelty/urgency/impact required,
    panjang karakter dicek terhadap nuir_settings.max_chars_*.
  → Simpan NuirSubmission status='draft'.
  → Simpan hingga 10 NuirReference (hanya yang terisi).
  → Redirect ke index dengan flash success.

edit($id)       GET  /nuir/submission/{id}/edit
  → Guard: submission milik auth user, isEditable() true.

update($id)     PUT  /nuir/submission/{id}
  → Guard sama dengan edit.
  → Update submission + sync references (upsert by ref_order).

submit($id)     PUT  /nuir/submission/{id}/submit
  → Guard: submission milik auth user, status='draft'.
  → Set status='submitted'.
  → Redirect ke index dengan flash success.
```

Route group (dalam middleware `can:active`):
```php
Route::middleware('can:read nuir submission')->group(function () {
    Route::get('nuir/submission', [NuirSubmissionController::class, 'index'])
         ->name('nuir.submission.index');
    Route::get('nuir/submission/create', [NuirSubmissionController::class, 'create'])
         ->name('nuir.submission.create')
         ->middleware('can:create nuir submission');
    Route::post('nuir/submission', [NuirSubmissionController::class, 'store'])
         ->name('nuir.submission.store')
         ->middleware('can:create nuir submission');
    Route::get('nuir/submission/{nuirSubmission}/edit', [NuirSubmissionController::class, 'edit'])
         ->name('nuir.submission.edit')
         ->middleware('can:update nuir submission');
    Route::put('nuir/submission/{nuirSubmission}', [NuirSubmissionController::class, 'update'])
         ->name('nuir.submission.update')
         ->middleware('can:update nuir submission');
    Route::put('nuir/submission/{nuirSubmission}/submit', [NuirSubmissionController::class, 'submit'])
         ->name('nuir.submission.submit')
         ->middleware('can:update nuir submission');
});
```

#### 3. Helper method di controller
```php
private function getActiveSetting(User $user): ?NuirSetting
{
    $guideExaminer = \App\Models\GuideExaminer::where('user_id', $user->id)->first();
    if (!$guideExaminer) return null;
    return NuirSetting::where('year_generation', $guideExaminer->year_generation)
                      ->where('active', true)
                      ->first();
}
```

#### 4. Views
`resources/views/selection/nuir/index.blade.php`
- Tampilkan status submission: badge status, tanggal submit, catatan DBS jika ada.
- Tombol: "Edit" (jika draft/revision), "Kirim ke DBS" (jika draft), "Lihat Detail".
- Tabel riwayat versi (jika ada parent_submission_id chain).

`resources/views/selection/nuir/form.blade.php`
- Identitas mahasiswa: nama, NIM (readonly dari auth user).
- Field: `title` (textarea), `novelty`, `urgency`, `impact` (textarea masing-masing
  dengan counter karakter live jika `max_chars_*` di-set).
- Tabel 10 referensi: tiap baris punya field
  `link_ojs`, `indexer_name` (select dropdown), `link_index`, `link_drive`, `quote`, `relevance`.
- Pilihan `indexer_name`: WoS, Scopus, Thomson, Elsevier, Springer, Wiley, Taylor&Francis, DOAJ, Sinta 2.
- Tombol "Simpan Draft".

### Test yang Harus Ditulis

File: `tests/Feature/NuirSubmissionTest.php`

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, NuirSetting, NuirSubmission, NuirReference, GuideExaminer};
use Database\Seeders\PermissionSeeder;

class NuirSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $mahasiswa;
    protected User $dosen1;
    protected User $dbs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen1 = User::factory()->create()->assignRole('dosen');
        $this->dbs = User::factory()->create()->assignRole('dbs');

        // Daftarkan mahasiswa ke angkatan 2022
        GuideExaminer::factory()->forStudent($this->mahasiswa)
            ->create(['year_generation' => '2022']);
    }

    public function test_mahasiswa_angkatan_aktif_dapat_akses_form(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
             ->get('/nuir/submission')
             ->assertOk();
    }

    public function test_mahasiswa_angkatan_tidak_aktif_tidak_dapat_akses_form(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => false]);

        $response = $this->actingAs($this->mahasiswa)->get('/nuir/submission');
        $response->assertOk()
                 ->assertSeeText('belum dibuka');
    }

    public function test_mahasiswa_tanpa_guide_examiner_tidak_dapat_akses_form(): void
    {
        $mahasiswaBaru = User::factory()->create()->assignRole('mahasiswa');
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1]);

        $this->actingAs($mahasiswaBaru)
             ->get('/nuir/submission')
             ->assertSeeText('belum dibuka');
    }

    public function test_mahasiswa_dapat_simpan_draft(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/submission', [
                 'title' => 'Judul Penelitian',
                 'novelty' => str_repeat('a', 100),
                 'urgency' => str_repeat('b', 100),
                 'impact' => str_repeat('c', 100),
                 'references' => [
                     1 => ['link_ojs' => 'https://ojs.example.com/1', 'indexer_name' => 'Scopus',
                           'link_index' => 'https://scopus.com', 'link_drive' => 'https://drive.google.com/1',
                           'quote' => 'kutipan artikel 1 [page 5]', 'relevance' => 'relevan karena...'],
                 ],
             ])
             ->assertRedirect();

        $this->assertDatabaseHas('nuir_submissions', [
            'user_id' => $this->mahasiswa->id,
            'status' => 'draft',
            'title' => 'Judul Penelitian',
        ]);
        $this->assertDatabaseHas('nuir_references', [
            'ref_order' => 1,
            'indexer_name' => 'Scopus',
        ]);
    }

    public function test_novelty_melebihi_batas_karakter_ditolak(): void
    {
        NuirSetting::factory()->create([
            'year_generation' => '2022', 'stage' => 1, 'active' => true,
            'max_chars_novelty' => 100,
        ]);

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/submission', [
                 'title' => 'Judul',
                 'novelty' => str_repeat('x', 101),
                 'urgency' => 'urgency oke',
                 'impact' => 'impact oke',
             ])
             ->assertSessionHasErrors('novelty');
    }

    public function test_submit_mengubah_status_menjadi_submitted(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $sub = NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'status' => 'draft',
        ]);

        $this->actingAs($this->mahasiswa)
             ->put("/nuir/submission/{$sub->id}/submit")
             ->assertRedirect();

        $this->assertEquals('submitted', $sub->fresh()->status);
    }

    public function test_submission_yang_sudah_submitted_tidak_bisa_diedit(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $sub = NuirSubmission::factory()->submitted()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
             ->get("/nuir/submission/{$sub->id}/edit")
             ->assertForbidden();
    }

    public function test_dbs_tidak_dapat_akses_form_mahasiswa(): void
    {
        $this->actingAs($this->dbs)
             ->get('/nuir/submission')
             ->assertForbidden();
    }
}
```

### Perintah Test
```bash
php artisan test --filter=NuirSubmissionTest
```

### Pesan Commit
```
feat(nuir): form pengajuan konten NUIR oleh mahasiswa tahap 1

Mahasiswa angkatan aktif (stage=1) dapat mengisi draft NUIR lengkap
(judul, novelty, urgency, impact, 10 referensi) dan mengajukannya ke DBS.
Validasi panjang karakter mengacu pada nuir_settings. Navigasi mahasiswa
diperbaharui, dilengkapi feature test.

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
```

---

## PROMPT 4 — DBS Review Konten NUIR & Approval Referensi

**Prasyarat:** Prompt 1–3 selesai, test hijau.
**Scope:** Review submission stage 1, approval referensi, `content_ok` / `revision`.
**Out of scope:** Revisi versioning (P5), proposal ke dosen (P6).

### Konteks Sistem
Aplikasi DBS Laravel 10. Sudah ada dari Prompt 1–3:
- Model & tabel NUIR (4 tabel)
- DBS kelola konfigurasi (`/setting/nuir-settings`)
- Mahasiswa bisa draft & submit NUIR (`/nuir/submission`)

Sekarang DBS perlu bisa melihat semua submission yang masuk (`status='submitted'`),
mereview per-elemen (catatan), approve/reject per referensi, lalu set
`status='content_ok'` atau `status='revision'`.
Tombol "Setujui Konten" hanya aktif jika `ref_approved=true >= nuir_settings.min_references_approved`.

### Tugas

#### 1. Permission baru
```php
Permission::create(['name' => 'review nuir submission'])->assignRole('dbs');
Permission::create(['name' => 'read nuir submission'])->assignRole('dbs'); // DBS juga bisa read
Permission::create(['name' => 'access nuir/review'])->assignRole('dbs');
```
Tambahkan navigasi DBS: `'review NUIR' → 'nuir/review'`.

#### 2. Controller `app/Http/Controllers/Setting/Nuir/SubmissionController.php`

```
index()              GET  /setting/nuir/submissions
  → DataTable NuirSubmission dengan filter angkatan & status.
  → Join ke users untuk tampilkan nama mahasiswa.

show($submission)    GET  /setting/nuir/submissions/{id}
  → Tampilkan detail submission + semua referensi + status per referensi.
  → Tampilkan chain versi sebelumnya (parent chain) sebagai riwayat.

reviewReference($ref)  PATCH /setting/nuir/references/{id}
  → Input: ref_approved (boolean), ref_note (text, required jika ref_approved=false).
  → Update NuirReference.
  → Tidak ubah status submission.

review($submission)  PUT /setting/nuir/submissions/{id}/review
  → Input: action ('content_ok' | 'revision'), dbs_note.
  → Guard 'content_ok': jumlah ref_approved=true >= min_references_approved dari setting.
  → Set status, dbs_reviewer_id=auth()->id(), dbs_reviewed_at=now().
  → Redirect ke show dengan flash.
```

Route group (dalam middleware `can:active`, `can:review nuir submission`):
```php
Route::get('setting/nuir/submissions', [SubmissionController::class, 'index'])
     ->name('nuir.review.index');
Route::get('setting/nuir/submissions/{nuirSubmission}', [SubmissionController::class, 'show'])
     ->name('nuir.review.show');
Route::patch('setting/nuir/references/{nuirReference}', [SubmissionController::class, 'reviewReference'])
     ->name('nuir.review.reference');
Route::put('setting/nuir/submissions/{nuirSubmission}/review', [SubmissionController::class, 'review'])
     ->name('nuir.review.submit');
```

#### 3. DataTable `app/DataTables/NuirSubmissionsDataTable.php`
Kolom: mahasiswa (nama), angkatan, versi, status (badge berwarna), tgl submit, reviewer, aksi (Lihat).
Filter: angkatan (select), status (select).

#### 4. Views
`resources/views/setting/nuir/submission-index.blade.php` — DataTable.

`resources/views/setting/nuir/submission-show.blade.php`
- Header: info mahasiswa, versi, status.
- Riwayat versi: card lipat setiap versi sebelumnya dengan status & catatan DBS-nya.
- Konten: title, novelty, urgency, impact (dengan info batas karakter jika diset).
- Tabel referensi: tiap baris tampilkan semua field + tombol ✓ (approve) / ✗ (reject + isian catatan).
  Gunakan form kecil AJAX atau form submit biasa per referensi.
- Info: "X dari Y referensi disetujui. Standar minimum: Z."
- Tombol "Setujui Konten" (disabled jika belum memenuhi standar) + textarea `dbs_note`.
- Tombol "Minta Revisi" + textarea `dbs_note` (wajib isi).

### Test yang Harus Ditulis

File: `tests/Feature/NuirReviewTest.php`

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, NuirSetting, NuirSubmission, NuirReference, GuideExaminer};
use Database\Seeders\PermissionSeeder;

class NuirReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $dbs;
    protected User $mahasiswa;
    protected NuirSetting $setting;
    protected NuirSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->dbs = User::factory()->create()->assignRole('dbs');
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        GuideExaminer::factory()->forStudent($this->mahasiswa)
            ->create(['year_generation' => '2022']);
        $this->setting = NuirSetting::factory()->create([
            'year_generation' => '2022', 'stage' => 1,
            'active' => true, 'min_references_approved' => 2,
        ]);
        $this->submission = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);
    }

    public function test_dbs_dapat_melihat_daftar_submission(): void
    {
        $this->actingAs($this->dbs)
             ->get('/setting/nuir/submissions')
             ->assertOk();
    }

    public function test_mahasiswa_tidak_dapat_akses_review(): void
    {
        $this->actingAs($this->mahasiswa)
             ->get('/setting/nuir/submissions')
             ->assertForbidden();
    }

    public function test_dbs_dapat_approve_referensi(): void
    {
        $ref = NuirReference::factory()->create([
            'nuir_submission_id' => $this->submission->id,
            'ref_order' => 1,
        ]);

        $this->actingAs($this->dbs)
             ->patch("/setting/nuir/references/{$ref->id}", [
                 'ref_approved' => true,
             ])
             ->assertRedirect();

        $this->assertTrue($ref->fresh()->ref_approved);
    }

    public function test_dbs_reject_referensi_wajib_isi_catatan(): void
    {
        $ref = NuirReference::factory()->create([
            'nuir_submission_id' => $this->submission->id, 'ref_order' => 1,
        ]);

        $this->actingAs($this->dbs)
             ->patch("/setting/nuir/references/{$ref->id}", [
                 'ref_approved' => false,
                 'ref_note' => '',
             ])
             ->assertSessionHasErrors('ref_note');
    }

    public function test_dbs_tidak_dapat_set_content_ok_jika_referensi_kurang(): void
    {
        // min=2, tapi 0 yang diapprove
        $this->actingAs($this->dbs)
             ->put("/setting/nuir/submissions/{$this->submission->id}/review", [
                 'action' => 'content_ok',
                 'dbs_note' => '',
             ])
             ->assertRedirect()
             ->assertSessionHas('warning');

        $this->assertEquals('submitted', $this->submission->fresh()->status);
    }

    public function test_dbs_dapat_set_content_ok_jika_referensi_cukup(): void
    {
        NuirReference::factory()->approved()->create([
            'nuir_submission_id' => $this->submission->id, 'ref_order' => 1]);
        NuirReference::factory()->approved()->create([
            'nuir_submission_id' => $this->submission->id, 'ref_order' => 2]);

        $this->actingAs($this->dbs)
             ->put("/setting/nuir/submissions/{$this->submission->id}/review", [
                 'action' => 'content_ok',
                 'dbs_note' => 'Semua oke',
             ])
             ->assertRedirect();

        $this->assertEquals('content_ok', $this->submission->fresh()->status);
        $this->assertEquals($this->dbs->id, $this->submission->fresh()->dbs_reviewer_id);
    }

    public function test_dbs_dapat_minta_revisi(): void
    {
        $this->actingAs($this->dbs)
             ->put("/setting/nuir/submissions/{$this->submission->id}/review", [
                 'action' => 'revision',
                 'dbs_note' => 'Referensi masih kurang berkualitas',
             ])
             ->assertRedirect();

        $this->assertEquals('revision', $this->submission->fresh()->status);
        $this->assertEquals('Referensi masih kurang berkualitas', $this->submission->fresh()->dbs_note);
    }

    public function test_minta_revisi_wajib_isi_dbs_note(): void
    {
        $this->actingAs($this->dbs)
             ->put("/setting/nuir/submissions/{$this->submission->id}/review", [
                 'action' => 'revision',
                 'dbs_note' => '',
             ])
             ->assertSessionHasErrors('dbs_note');
    }

    public function test_keputusan_referensi_diversi_lama_tidak_berubah_saat_versi_baru_dibuat(): void
    {
        $ref = NuirReference::factory()->rejected('link tidak valid')->create([
            'nuir_submission_id' => $this->submission->id, 'ref_order' => 1,
        ]);
        // Buat versi baru (simulasi revisi)
        $v2 = NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'parent_submission_id' => $this->submission->id,
            'version' => 2,
            'status' => 'draft',
        ]);
        NuirReference::factory()->create(['nuir_submission_id' => $v2->id, 'ref_order' => 1]);

        // Versi lama tidak berubah
        $this->assertFalse($ref->fresh()->ref_approved);
        $this->assertEquals('link tidak valid', $ref->fresh()->ref_note);
    }
}
```

### Perintah Test
```bash
php artisan test --filter=NuirReviewTest
```

### Pesan Commit
```
feat(nuir): review konten NUIR dan approval referensi oleh DBS

DBS dapat mereview submission mahasiswa, approve/reject per referensi
dengan catatan wajib, dan menetapkan content_ok atau revision.
Validasi standar minimum referensi diterapkan sebelum content_ok diizinkan.
Riwayat keputusan referensi dipertahankan per versi. Feature test terlampir.

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
```

---

## PROMPT 5 — Revisi NUIR dengan Versioning

**Prasyarat:** Prompt 1–4 selesai, test hijau.
**Scope:** Buat versi baru saat status `revision`; versi lama immutable.
**Out of scope:** Proposal ke dosen (P6), varian stage 2/3 (P8).

### Konteks Sistem
Aplikasi DBS Laravel 10. Sudah ada dari Prompt 1–4:
- Mahasiswa submit NUIR → DBS review → bisa set `revision` atau `content_ok`
- Saat DBS set `revision`, mahasiswa perlu membuat versi baru (`version+1`)
  dengan `parent_submission_id` ke versi lama.
- Versi lama tidak pernah diubah (immutable setelah ada versi anak).

### Tugas

#### 1. Tambah method ke `NuirSubmissionController`

```
createRevision($id)   GET  /nuir/submission/{id}/revise
  → Guard: submission milik auth user, status='revision'.
  → Cek tidak ada versi anak (belum pernah buat revisi ini sebelumnya).
  → Tampilkan form pre-filled dari submission lama.
  → Referensi yang ref_approved=false disorot (highlight merah) di form.
  → Referensi yang ref_approved=true ditandai (read-only opsional).
  → Pass $setting ke view untuk validasi karakter.

storeRevision($id)    POST /nuir/submission/{id}/revise
  → Validasi sama dengan store() di Prompt 3.
  → Buat NuirSubmission baru:
      parent_submission_id = $id,
      version = $parent->version + 1,
      status = 'draft'
  → Simpan NuirReference baru (set lengkap, tidak copy dari parent).
  → Redirect ke nuir.submission.index.
```

Route (dalam middleware `can:active`, `can:update nuir submission`):
```php
Route::get('nuir/submission/{nuirSubmission}/revise', [NuirSubmissionController::class, 'createRevision'])
     ->name('nuir.submission.revise');
Route::post('nuir/submission/{nuirSubmission}/revise', [NuirSubmissionController::class, 'storeRevision'])
     ->name('nuir.submission.store-revision');
```

#### 2. Update view `selection/nuir/index.blade.php`
- Jika status='revision': tampilkan card "Diminta Revisi" berisi `dbs_note`.
- Di bawahnya: tabel referensi yang ditolak (ref_approved=false + ref_note).
- Tombol "Buat Revisi (v{n+1})".
- Tampilkan riwayat semua versi sebelumnya dalam panel collapsible:
  tiap versi: nomor versi, status, catatan DBS, tanggal.

#### 3. Update view `selection/nuir/form.blade.php`
- Tambahkan parameter opsional `$rejectedRefs` (array ref_order).
- Baris referensi yang ref_order-nya ada di `$rejectedRefs` diberi class `table-danger`
  dan badge "ditolak DBS: {ref_note}".

### Test yang Harus Ditulis

File: `tests/Feature/NuirRevisionTest.php`

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, NuirSetting, NuirSubmission, NuirReference, GuideExaminer};
use Database\Seeders\PermissionSeeder;

class NuirRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected User $mahasiswa;
    protected NuirSubmission $v1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $this->v1 = NuirSubmission::factory()->revision()->withNUI()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'version' => 1,
        ]);
    }

    public function test_mahasiswa_dapat_akses_form_revisi(): void
    {
        $this->actingAs($this->mahasiswa)
             ->get("/nuir/submission/{$this->v1->id}/revise")
             ->assertOk();
    }

    public function test_mahasiswa_tidak_dapat_revisi_jika_status_bukan_revision(): void
    {
        $sub = NuirSubmission::factory()->submitted()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
             ->get("/nuir/submission/{$sub->id}/revise")
             ->assertForbidden();
    }

    public function test_revisi_membuat_versi_baru_dengan_parent_id(): void
    {
        $this->actingAs($this->mahasiswa)
             ->post("/nuir/submission/{$this->v1->id}/revise", [
                 'title' => 'Judul Direvisi',
                 'novelty' => str_repeat('n', 100),
                 'urgency' => str_repeat('u', 100),
                 'impact' => str_repeat('i', 100),
                 'references' => [],
             ])
             ->assertRedirect(route('nuir.submission.index'));

        $v2 = NuirSubmission::where('parent_submission_id', $this->v1->id)->first();
        $this->assertNotNull($v2);
        $this->assertEquals(2, $v2->version);
        $this->assertEquals('draft', $v2->status);
        $this->assertEquals('Judul Direvisi', $v2->title);
    }

    public function test_versi_lama_tidak_berubah_setelah_revisi_dibuat(): void
    {
        $originalNote = $this->v1->dbs_note;

        $this->actingAs($this->mahasiswa)
             ->post("/nuir/submission/{$this->v1->id}/revise", [
                 'title' => 'Judul Baru',
                 'novelty' => str_repeat('n', 50),
                 'urgency' => str_repeat('u', 50),
                 'impact' => str_repeat('i', 50),
                 'references' => [],
             ]);

        $this->assertEquals('revision', $this->v1->fresh()->status);
        $this->assertEquals($originalNote, $this->v1->fresh()->dbs_note);
    }

    public function test_tidak_dapat_revisi_jika_sudah_ada_versi_anak(): void
    {
        NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022',
            'parent_submission_id' => $this->v1->id,
            'version' => 2,
            'status' => 'draft',
        ]);

        $this->actingAs($this->mahasiswa)
             ->get("/nuir/submission/{$this->v1->id}/revise")
             ->assertForbidden();
    }

    public function test_referensi_ditolak_muncul_di_form_revisi(): void
    {
        NuirReference::factory()->rejected('Link tidak valid')->create([
            'nuir_submission_id' => $this->v1->id, 'ref_order' => 3,
        ]);

        $this->actingAs($this->mahasiswa)
             ->get("/nuir/submission/{$this->v1->id}/revise")
             ->assertSeeText('Link tidak valid');
    }

    public function test_versi_baru_punya_referensi_sendiri(): void
    {
        NuirReference::factory()->rejected('Link tidak valid')->create([
            'nuir_submission_id' => $this->v1->id, 'ref_order' => 1,
        ]);

        $this->actingAs($this->mahasiswa)
             ->post("/nuir/submission/{$this->v1->id}/revise", [
                 'title' => 'Direvisi', 'novelty' => 'n', 'urgency' => 'u', 'impact' => 'i',
                 'references' => [
                     1 => ['link_ojs' => 'https://ojs.baru.com', 'indexer_name' => 'Scopus',
                           'link_index' => 'https://scopus.com', 'link_drive' => 'https://drive.com',
                           'quote' => 'kutipan baru', 'relevance' => 'relevan baru'],
                 ],
             ]);

        $v2 = NuirSubmission::where('parent_submission_id', $this->v1->id)->first();
        $this->assertCount(1, $v2->references);
        $this->assertEquals('https://ojs.baru.com', $v2->references->first()->link_ojs);

        // Referensi v1 tidak berubah
        $this->assertEquals('link tidak valid', 
            NuirReference::where('nuir_submission_id', $this->v1->id)->first()->ref_note);
    }
}
```

### Perintah Test
```bash
php artisan test --filter=NuirRevisionTest
```

### Pesan Commit
```
feat(nuir): alur revisi NUIR dengan versioning immutable

Saat DBS meminta revisi, mahasiswa membuat versi baru yang menjadi
anak dari versi sebelumnya. Versi lama tidak pernah dimodifikasi.
Referensi yang ditolak disorot di form revisi sebagai panduan mahasiswa.
Feature test memverifikasi chain versi dan immutabilitas data lama.

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
```

---

## PROMPT 6 — Proposal NUIR Mahasiswa ke Dosen (Stage 1)

**Prasyarat:** Prompt 1–5 selesai, test hijau.
**Scope:** Mahasiswa usulkan pasangan dosen setelah `content_ok` (stage 1).
**Out of scope:** Respons dosen (P7), validasi duplikat lengkap (P9 §3), stage 2 (P8).

### Konteks Sistem
Aplikasi DBS Laravel 10. Sudah ada dari Prompt 1–5:
- Mahasiswa bisa submit NUIR, DBS review, mahasiswa revisi (versioning).
- Saat submission `status='content_ok'`, mahasiswa boleh membuat proposal ke 2 calon dosen.
- Proposal (`nuir_proposals`) tidak pernah dihapus — sejarah dipertahankan.
- Shortcut: jika proposal sebelumnya ditolak tapi konten sama, buat proposal baru
  dengan `nuir_submission_id` yang sama (tanpa input ulang NUIR).

### Tugas

#### 1. Permission baru
```php
Permission::create(['name' => 'create nuir proposal'])->assignRole('mahasiswa');
Permission::create(['name' => 'read nuir proposal'])->assignRole('mahasiswa');
Permission::create(['name' => 'access nuir/proposal'])->assignRole('mahasiswa');
```
Tambahkan navigasi mahasiswa: `'proposal pembimbing NUIR' → 'nuir/proposal'`.

#### 2. Controller `app/Http/Controllers/Selection/NuirProposalController.php`

```
index()         GET  /nuir/proposal
  → Tampilkan semua proposal mahasiswa ini (urut terbaru).
  → Tiap proposal: nama dosen 1 & 2, status masing-masing, catatan penolakan, tanggal.
  → Jika ada final=true: tampilkan banner "Pembimbing sudah ditetapkan".
  → Tombol "Usulkan ke Dosen" (jika belum ada final, ada submission content_ok).

create()        GET  /nuir/proposal/create
  → Guard: ada submission content_ok milik mahasiswa ini, belum ada final=true.
  → Jika ada submission content_ok sebelumnya DAN proposal pernah ditolak:
    tampilkan info "NUIR Anda sudah diverifikasi (v{n})" + ringkasan judul.
    Tombol "Gunakan NUIR yang sama" (pre-select submission_id) atau "Buat NUIR baru".
  → Form: dropdown guide1 & guide2 (list dosen aktif, exclude diri sendiri & sama satu sama lain).

store()         POST /nuir/proposal
  → Input: nuir_submission_id, guide1_id, guide2_id.
  → Validasi: submission_id milik mahasiswa, status='content_ok',
    guide1 ≠ guide2, keduanya adalah dosen aktif,
    tolak jika sudah ada proposal dengan pasangan dosen yang sama
    dan masih ada status `pending` (query lengkap diperketat di Prompt 9 §3).
  <!-- LAMA: belum ada proposal pending/final dengan kombinasi dosen yang sama. -->
  → Buat NuirProposal.
  → Redirect ke index.
```

Route:
```php
Route::middleware(['can:active', 'can:read nuir proposal'])->group(function () {
    Route::get('nuir/proposal', [NuirProposalController::class, 'index'])
         ->name('nuir.proposal.index');
    Route::get('nuir/proposal/create', [NuirProposalController::class, 'create'])
         ->name('nuir.proposal.create')
         ->middleware('can:create nuir proposal');
    Route::post('nuir/proposal', [NuirProposalController::class, 'store'])
         ->name('nuir.proposal.store')
         ->middleware('can:create nuir proposal');
});
```

#### 3. Views
`resources/views/selection/nuir/proposal-index.blade.php`
- Tabel riwayat proposal: dosen1, dosen2, status1, status2, catatan1/2, tanggal.
- Badge berwarna per status (pending=abu, accepted=hijau, rejected=merah).
- Jika `final=true`: banner hijau besar "Pembimbing sudah ditetapkan: [Nama1] & [Nama2]".

`resources/views/selection/nuir/proposal-form.blade.php`
- Info/card NUIR yang akan digunakan (judul, versi).
- Select dosen 1 dan dosen 2 dengan opsi semua dosen aktif.

### Test yang Harus Ditulis

File: `tests/Feature/NuirProposalTest.php`

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, NuirSetting, NuirSubmission, NuirProposal, GuideExaminer};
use Database\Seeders\PermissionSeeder;

class NuirProposalTest extends TestCase
{
    use RefreshDatabase;

    protected User $mahasiswa;
    protected User $dosen1;
    protected User $dosen2;
    protected User $dosen3;
    protected NuirSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen1 = User::factory()->create()->assignRole('dosen');
        $this->dosen2 = User::factory()->create()->assignRole('dosen');
        $this->dosen3 = User::factory()->create()->assignRole('dosen');
        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $this->submission = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
    }

    public function test_mahasiswa_dapat_buat_proposal(): void
    {
        $this->actingAs($this->mahasiswa)
             ->post('/nuir/proposal', [
                 'nuir_submission_id' => $this->submission->id,
                 'guide1_id' => $this->dosen1->id,
                 'guide2_id' => $this->dosen2->id,
             ])
             ->assertRedirect(route('nuir.proposal.index'));

        $this->assertDatabaseHas('nuir_proposals', [
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
            'guide1_status' => 'pending',
            'guide2_status' => 'pending',
        ]);
    }

    public function test_guide1_dan_guide2_tidak_boleh_sama(): void
    {
        $this->actingAs($this->mahasiswa)
             ->post('/nuir/proposal', [
                 'nuir_submission_id' => $this->submission->id,
                 'guide1_id' => $this->dosen1->id,
                 'guide2_id' => $this->dosen1->id,
             ])
             ->assertSessionHasErrors('guide2_id');
    }

    public function test_tidak_dapat_proposal_jika_submission_bukan_content_ok(): void
    {
        $draft = NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022', 'status' => 'draft',
        ]);

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/proposal', [
                 'nuir_submission_id' => $draft->id,
                 'guide1_id' => $this->dosen1->id,
                 'guide2_id' => $this->dosen2->id,
             ])
             ->assertSessionHasErrors('nuir_submission_id');
    }

    public function test_tidak_dapat_buat_proposal_jika_sudah_ada_final(): void
    {
        NuirProposal::factory()->bothAccepted()->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/proposal', [
                 'nuir_submission_id' => $this->submission->id,
                 'guide1_id' => $this->dosen1->id,
                 'guide2_id' => $this->dosen3->id,
             ])
             ->assertRedirect()
             ->assertSessionHas('warning');
    }

    public function test_proposal_ditolak_sebelumnya_tetap_tersimpan_saat_buat_proposal_baru(): void
    {
        $rejected = NuirProposal::factory()->guide2Rejected('tidak sesuai bidang')->create([
            'nuir_submission_id' => $this->submission->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/proposal', [
                 'nuir_submission_id' => $this->submission->id,
                 'guide1_id' => $this->dosen1->id,
                 'guide2_id' => $this->dosen3->id,
             ]);

        // Proposal lama masih ada
        $this->assertDatabaseHas('nuir_proposals', ['id' => $rejected->id]);
        $this->assertEquals('rejected', $rejected->fresh()->guide2_status);
        $this->assertEquals('tidak sesuai bidang', $rejected->fresh()->guide2_note);
        // Proposal baru juga ada
        $this->assertDatabaseHas('nuir_proposals', [
            'nuir_submission_id' => $this->submission->id,
            'guide2_id' => $this->dosen3->id,
        ]);
    }

    public function test_submission_id_harus_milik_mahasiswa_sendiri(): void
    {
        $mahasiswaLain = User::factory()->create()->assignRole('mahasiswa');
        $subLain = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $mahasiswaLain->id, 'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/proposal', [
                 'nuir_submission_id' => $subLain->id,
                 'guide1_id' => $this->dosen1->id,
                 'guide2_id' => $this->dosen2->id,
             ])
             ->assertSessionHasErrors('nuir_submission_id');
    }
}
```

### Perintah Test
```bash
php artisan test --filter=NuirProposalTest
```

### Pesan Commit
```
feat(nuir): pengajuan proposal pembimbing dari mahasiswa ke calon dosen

Mahasiswa dengan submission content_ok dapat mengajukan proposal ke 2 calon
dosen. Proposal lama tidak pernah dihapus — seluruh riwayat penolakan
tersimpan. Shortcut reuse submission ditampilkan di form jika ada proposal
sebelumnya. Feature test memverifikasi validasi dan preservasi sejarah.

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
```

---

## PROMPT 7 — Respons Dosen & Auto-fill guide_examiners

**Prasyarat:** Prompt 1–6 selesai, test hijau.
**Scope:** Dosen accept/reject; finalize → isi `guide_examiners`, status `finalized`.
**Out of scope:** Varian stage 2/3 (P8), guard rails & dashboard (P9).

### Konteks Sistem
Aplikasi DBS Laravel 10. Sudah ada dari Prompt 1–6:
- Mahasiswa bisa buat proposal ke 2 calon dosen setelah NUIR content_ok.
- `nuir_proposals` berisi guide1_id, guide2_id, masing-masing dengan status pending/accepted/rejected.
- Ketika keduanya accepted: `final=true` → `guide_examiners.guide1_id & guide2_id` diisi otomatis.
- Setiap proposal adalah baris terpisah; tidak ada penghapusan.

### Tugas

#### 1. Permission baru
```php
Permission::create(['name' => 'respond nuir proposal'])->assignRole('dosen');
Permission::create(['name' => 'read nuir proposal'])->assignRole('dosen');
Permission::create(['name' => 'access nuir/dosen'])->assignRole('dosen');
```
Tambahkan navigasi dosen di bawah parent `order='D00'`:
`'usulan NUIR masuk' → 'nuir/dosen'`.

#### 2. Controller `app/Http/Controllers/Dosen/NuirProposalController.php`

```
index()       GET  /nuir/dosen
  → Daftar semua proposal di mana guide1_id=auth()->id() ATAU guide2_id=auth()->id().
  → Kelompokkan: Menunggu Respons | Sudah Direspons.
  → Tiap proposal: nama mahasiswa, judul NUIR (link ke show), status pasangan.

show($p)      GET  /nuir/dosen/{proposal}
  → Guard: proposal melibatkan auth dosen.
  → Tampilkan detail: nama mahasiswa, judul, novelty, urgency, impact, 10 referensi.
  → Tampilkan status keputusan DBS per referensi (ref_approved + ref_note).
  → Tombol Accept / Reject (hanya jika status masih pending untuk dosen ini).

accept($p)    PUT  /nuir/dosen/{proposal}/accept
  → Guard: proposal melibatkan auth dosen, status dosen ini masih 'pending'.
  → Set guide1_status='accepted' (jika guide1=auth) atau guide2_status='accepted'.
  → Set responded_at = now().
  → Cek isBothAccepted(): jika true → finalize().
  → Redirect ke index.

reject($p)    PUT  /nuir/dosen/{proposal}/reject
  → Guard sama accept.
  → Input: note (required).
  → Set guide1_status='rejected' + guide1_note atau guide2_status='rejected' + guide2_note.
  → Set responded_at = now().
  → Redirect ke index.
```

#### 3. Private method `finalize(NuirProposal $proposal)` (di controller atau Service class)
```php
private function finalize(NuirProposal $proposal): void
{
    $proposal->update(['final' => true]);
    $proposal->submission->update(['status' => 'finalized']);

    \App\Models\GuideExaminer::where('user_id', $proposal->submission->user_id)
        ->update([
            'guide1_id' => $proposal->guide1_id,
            'guide2_id' => $proposal->guide2_id,
        ]);
}
```

Route:
```php
Route::middleware(['can:active', 'can:read nuir proposal'])->group(function () {
    Route::get('nuir/dosen', [\App\Http\Controllers\Dosen\NuirProposalController::class, 'index'])
         ->name('nuir.dosen.index');
    Route::get('nuir/dosen/{nuirProposal}', [\App\Http\Controllers\Dosen\NuirProposalController::class, 'show'])
         ->name('nuir.dosen.show');
    Route::put('nuir/dosen/{nuirProposal}/accept', [\App\Http\Controllers\Dosen\NuirProposalController::class, 'accept'])
         ->name('nuir.dosen.accept')
         ->middleware('can:respond nuir proposal');
    Route::put('nuir/dosen/{nuirProposal}/reject', [\App\Http\Controllers\Dosen\NuirProposalController::class, 'reject'])
         ->name('nuir.dosen.reject')
         ->middleware('can:respond nuir proposal');
});
```

#### 4. Views
`resources/views/dosen/nuir/proposal-index.blade.php`
`resources/views/dosen/nuir/proposal-show.blade.php`

### Test yang Harus Ditulis

File: `tests/Feature/NuirDosenResponseTest.php`

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, NuirSetting, NuirSubmission, NuirProposal, GuideExaminer};
use Database\Seeders\PermissionSeeder;

class NuirDosenResponseTest extends TestCase
{
    use RefreshDatabase;

    protected User $mahasiswa;
    protected User $dosen1;
    protected User $dosen2;
    protected NuirProposal $proposal;
    protected GuideExaminer $ge;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen1 = User::factory()->create()->assignRole('dosen');
        $this->dosen2 = User::factory()->create()->assignRole('dosen');
        $this->ge = GuideExaminer::factory()->forStudent($this->mahasiswa)
            ->create(['year_generation' => '2022', 'guide1_id' => null, 'guide2_id' => null]);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
        $this->proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $this->dosen2->id,
        ]);
    }

    public function test_dosen_dapat_lihat_proposal_yang_ditujukan(): void
    {
        $this->actingAs($this->dosen1)
             ->get('/nuir/dosen')
             ->assertOk()
             ->assertSee($this->mahasiswa->name);
    }

    public function test_dosen_tidak_dapat_lihat_proposal_yang_tidak_ditujukan(): void
    {
        $dosenLain = User::factory()->create()->assignRole('dosen');

        $this->actingAs($dosenLain)
             ->get("/nuir/dosen/{$this->proposal->id}")
             ->assertForbidden();
    }

    public function test_dosen1_dapat_menerima_proposal(): void
    {
        $this->actingAs($this->dosen1)
             ->put("/nuir/dosen/{$this->proposal->id}/accept")
             ->assertRedirect(route('nuir.dosen.index'));

        $this->assertEquals('accepted', $this->proposal->fresh()->guide1_status);
        $this->assertNotNull($this->proposal->fresh()->guide1_responded_at);
    }

    public function test_dosen_tidak_dapat_respond_dua_kali(): void
    {
        $this->proposal->update(['guide1_status' => 'accepted']);

        $this->actingAs($this->dosen1)
             ->put("/nuir/dosen/{$this->proposal->id}/accept")
             ->assertForbidden();
    }

    public function test_dosen_dapat_menolak_dengan_catatan(): void
    {
        $this->actingAs($this->dosen2)
             ->put("/nuir/dosen/{$this->proposal->id}/reject", [
                 'note' => 'Topik tidak sesuai keahlian saya',
             ])
             ->assertRedirect();

        $this->assertEquals('rejected', $this->proposal->fresh()->guide2_status);
        $this->assertEquals('Topik tidak sesuai keahlian saya', $this->proposal->fresh()->guide2_note);
    }

    public function test_menolak_tanpa_catatan_ditolak_validasi(): void
    {
        $this->actingAs($this->dosen1)
             ->put("/nuir/dosen/{$this->proposal->id}/reject", ['note' => ''])
             ->assertSessionHasErrors('note');
    }

    public function test_kedua_dosen_terima_memicu_final_dan_guide_examiners(): void
    {
        $this->actingAs($this->dosen1)
             ->put("/nuir/dosen/{$this->proposal->id}/accept");

        $this->actingAs($this->dosen2)
             ->put("/nuir/dosen/{$this->proposal->id}/accept");

        $proposal = $this->proposal->fresh();
        $this->assertTrue($proposal->final);
        $this->assertEquals('finalized', $proposal->submission->status);

        $ge = $this->ge->fresh();
        $this->assertEquals($this->dosen1->id, $ge->guide1_id);
        $this->assertEquals($this->dosen2->id, $ge->guide2_id);
    }

    public function test_sejarah_penolakan_proposal_tahap_1_tersimpan_saat_proposal_baru_dibuat(): void
    {
        // dosen2 tolak
        $this->actingAs($this->dosen2)
             ->put("/nuir/dosen/{$this->proposal->id}/reject", ['note' => 'tidak sesuai']);

        $dosenBaru = User::factory()->create()->assignRole('dosen');
        $sub = $this->proposal->submission;
        NuirProposal::factory()->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id,
            'guide2_id' => $dosenBaru->id,
        ]);

        // Proposal lama masih ada dengan status rejected
        $this->assertEquals('rejected', $this->proposal->fresh()->guide2_status);
        $this->assertEquals('tidak sesuai', $this->proposal->fresh()->guide2_note);
        $this->assertCount(2, NuirProposal::where('nuir_submission_id', $sub->id)->get());
    }

    public function test_mahasiswa_tidak_dapat_respond_proposal(): void
    {
        $this->actingAs($this->mahasiswa)
             ->put("/nuir/dosen/{$this->proposal->id}/accept")
             ->assertForbidden();
    }
}
```

### Perintah Test
```bash
php artisan test --filter=NuirDosenResponseTest
```

### Pesan Commit
```
feat(nuir): respons dosen terhadap proposal dan pengisian guide_examiners

Dosen dapat menerima atau menolak proposal NUIR dengan catatan wajib.
Saat kedua dosen menerima, proposal di-finalize dan guide_examiners
diperbarui otomatis. Sejarah semua penolakan dipertahankan sepenuhnya.
Feature test memverifikasi seluruh skenario respons dan finalisasi.

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
```

---

## PROMPT 8 — Stage 2 (Judul Saja) dan Stage 3 (Tanpa NUIR)

**Prasyarat:** Prompt 1–7 selesai (terutama P6 proposal + P7 finalize).
**Scope:** Perluasan `NuirSubmissionController` untuk stage 2 & 3; reuse `NuirProposalController`.
**Out of scope:** Guard deadline/finalized (P9). Filament stage 3 — tidak buat resource baru.

### Konteks Sistem
Aplikasi DBS Laravel 10. Sudah ada dari Prompt 1–7:
- Stage 1 (NUIR penuh): mahasiswa isi NUIR lengkap → DBS review → proposal ke dosen.
- Sekarang perlu ditambahkan stage 2 dan stage 3.
- Stage 2: mahasiswa hanya mengisi judul → langsung bisa proposal ke dosen (tanpa DBS review).
- Stage 3: tidak ada proses NUIR sama sekali; DBS langsung mengisi guide_examiners via Filament.

### Tugas

#### Stage 2

##### 1. Update `NuirSubmissionController`
Pada `create()`, `store()`, `edit()`, `update()`:
- Ambil `$setting = $this->getActiveSetting(auth()->user())`.
- Jika `$setting->stage === 2`:
  - Form hanya tampilkan field `title` (novelty/urgency/impact disembunyikan).
  - Validasi hanya `title` required, tidak ada validasi karakter N/U/I.
  - Setelah `store()`: langsung set `status = 'content_ok'` (skip DBS review).

Pada view `selection/nuir/form.blade.php`:
- Terima variabel `$stage` (1 atau 2).
- Jika `$stage === 2`: sembunyikan section novelty/urgency/impact/referensi,
  tampilkan hanya field judul + tombol "Simpan".

##### 2. Update `NuirSubmissionController::submit()`
Jika stage 2: submit tidak diperlukan (sudah `content_ok` dari store),
redirect ke `nuir.proposal.create` langsung.

#### Stage 3

##### 3. Update `NuirSubmissionController::index()`
Jika `$setting->stage === 3`:
- Tampilkan halaman info: "Angkatan Anda tidak memerlukan pengajuan NUIR.
  Pembimbing akan ditetapkan langsung oleh DBS."
- Tidak ada form/tombol NUIR.

##### 4. Stage 3 — Pembimbing via Filament (sudah ada, tanpa fitur baru)

Tidak buat resource Filament baru. DBS mengisi `guide1_id` / `guide2_id` lewat:

- Resource: `App\Filament\Resources\GuideExaminerResource`
- URL: `GuideExaminerResource::getUrl('index')` (sudah dipakai di `dashboard/dbs.blade.php`)

Tugas Prompt 8 untuk stage 3 hanya sisi mahasiswa (blokir form/store/proposal — lihat test `NuirStageTest`).
Opsional di Prompt 9 §6: link Penjadwalan Filament di card Manajemen NUIR untuk angkatan stage 3.

<!-- LAMA: Stage 3 hanya disebut di Konteks Sistem ("via Filament") tanpa detail resource/path. -->

#### Stage 2 Proposal
Proposal stage 2 menggunakan `NuirProposalController` yang sama persis —
tidak ada perubahan. Submission stage 2 sudah `content_ok`, jadi proposal bisa langsung dibuat.

Shortcut reuse pada `create()`: tampilkan info submission lama jika ada
(judul + status `content_ok`) saat proposal ditolak dan mahasiswa ingin usul ke dosen lain.

### Test yang Harus Ditulis

File: `tests/Feature/NuirStageTest.php`

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, NuirSetting, NuirSubmission, NuirProposal, GuideExaminer};
use Database\Seeders\PermissionSeeder;

class NuirStageTest extends TestCase
{
    use RefreshDatabase;

    protected User $mahasiswa;
    protected User $dosen1;
    protected User $dosen2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen1 = User::factory()->create()->assignRole('dosen');
        $this->dosen2 = User::factory()->create()->assignRole('dosen');
        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
    }

    // --- Stage 2 ---

    public function test_stage2_store_langsung_content_ok_tanpa_review_dbs(): void
    {
        NuirSetting::factory()->stage2()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/submission', ['title' => 'Judul Saya'])
             ->assertRedirect();

        $sub = NuirSubmission::where('user_id', $this->mahasiswa->id)->first();
        $this->assertEquals('content_ok', $sub->status);
    }

    public function test_stage2_novelty_tidak_diperlukan(): void
    {
        NuirSetting::factory()->stage2()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/submission', ['title' => 'Judul Saja'])
             ->assertRedirect()
             ->assertSessionMissing('errors');

        $this->assertDatabaseHas('nuir_submissions', [
            'user_id' => $this->mahasiswa->id,
            'novelty' => null,
        ]);
    }

    public function test_stage2_mahasiswa_langsung_bisa_proposal_setelah_store(): void
    {
        NuirSetting::factory()->stage2()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/submission', ['title' => 'Judul Saja']);

        $sub = NuirSubmission::where('user_id', $this->mahasiswa->id)->first();

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/proposal', [
                 'nuir_submission_id' => $sub->id,
                 'guide1_id' => $this->dosen1->id,
                 'guide2_id' => $this->dosen2->id,
             ])
             ->assertRedirect(route('nuir.proposal.index'));
    }

    public function test_stage2_judul_reuse_saat_proposal_ditolak(): void
    {
        NuirSetting::factory()->stage2()->create(['year_generation' => '2022', 'active' => true]);
        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
        $dosenBaru = User::factory()->create()->assignRole('dosen');

        // Proposal ditolak
        NuirProposal::factory()->guide2Rejected('tidak bisa')->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id, 'guide2_id' => $this->dosen2->id,
        ]);

        // Buat proposal baru reuse submission yang sama
        $this->actingAs($this->mahasiswa)
             ->post('/nuir/proposal', [
                 'nuir_submission_id' => $sub->id,
                 'guide1_id' => $this->dosen1->id,
                 'guide2_id' => $dosenBaru->id,
             ])
             ->assertRedirect();

        $this->assertCount(2, NuirProposal::where('nuir_submission_id', $sub->id)->get());
    }

    // --- Stage 3 ---

    public function test_stage3_mahasiswa_tidak_lihat_form_nuir(): void
    {
        NuirSetting::factory()->stage3()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
             ->get('/nuir/submission')
             ->assertOk()
             ->assertSeeText('tidak memerlukan pengajuan NUIR');
    }

    public function test_stage3_mahasiswa_tidak_dapat_store_submission(): void
    {
        NuirSetting::factory()->stage3()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/submission', ['title' => 'Judul'])
             ->assertForbidden();
    }

    public function test_stage3_tidak_ada_proposal_yang_bisa_dibuat(): void
    {
        NuirSetting::factory()->stage3()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
             ->get('/nuir/proposal/create')
             ->assertForbidden();
    }
}
```

### Perintah Test
```bash
php artisan test --filter=NuirStageTest
```

### Pesan Commit
```
feat(nuir): alur tahap 2 (judul saja) dan tahap 3 (tanpa NUIR)

Stage 2: mahasiswa hanya mengisi judul, submission langsung content_ok
tanpa melalui review DBS, proposal ke dosen bisa dibuat segera.
Stage 3: halaman NUIR menampilkan info bahwa pembimbing ditetapkan DBS
langsung. Reuse shortcut berfungsi di stage 2 saat proposal ditolak.

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
```

---

## PROMPT 9 — Guard Rails, Navigasi Dashboard, dan Monitor DBS

**Prasyarat:** Prompt 1–8 selesai, test hijau.
**Scope:** Guard rails (deadline, finalized, duplikat), dashboard, monitor DBS, force finalize.
**Out of scope:** Fitur bisnis inti stage 1–3 (sudah di P3–P8).

### Konteks Sistem
Aplikasi DBS Laravel 10. Sudah ada dari Prompt 1–8:
- Sistem NUIR lengkap: stage 1 (NUIR penuh + DBS review), stage 2 (judul + proposal),
  stage 3 (tidak ada NUIR), versioning revisi, auto-fill guide_examiners.
- Prompt ini menyelesaikan: integrasi navigasi di dashboard, guard rails
  (deadline, finalized, duplikasi), dan halaman monitoring DBS (semua proposal).

### Tugas

#### 1. Guard Rails — Deadline

Di semua controller yang menerima aksi mahasiswa (`store`, `submit`, `storeRevision`, `store` proposal):
```php
private function checkDeadline(NuirSetting $setting): bool
{
    return is_null($setting->deadline) || $setting->deadline->isFuture();
}
```
Jika deadline terlewat: redirect back dengan flash `'warning' => 'Batas pengajuan NUIR telah berakhir.'`.

#### 2. Guard Rails — Submission Finalized

Di `NuirSubmissionController`: pastikan mahasiswa tidak bisa buat submission baru
jika sudah ada submission dengan `status='finalized'`:
```php
// Di create() dan store()
if (NuirSubmission::where('user_id', auth()->id())->where('status', 'finalized')->exists()) {
    return redirect()->route('nuir.submission.index')
                     ->with('info', 'Pembimbing Anda sudah ditetapkan.');
}
```

#### 3. Guard Rails — Proposal Duplikat (perketat validasi Prompt 6)

<!-- Judul lama: Guard Rails — Proposal Duplikat (pasangan dosen yang sama) -->

Ganti/perketat validasi dasar di `NuirProposalController::store()` dari Prompt 6 dengan query berikut:

<!-- LAMA: Di `NuirProposalController::store()`, tambahkan validasi: -->

```php
// Cek apakah ada proposal pending dengan pasangan dosen yang sama
$duplikat = NuirProposal::where('nuir_submission_id', $request->nuir_submission_id)
    ->where(function($q) use ($request) {
        $q->where([
            'guide1_id' => $request->guide1_id,
            'guide2_id' => $request->guide2_id,
            'guide1_status' => 'pending',
        ])->orWhere([
            'guide1_id' => $request->guide1_id,
            'guide2_id' => $request->guide2_id,
            'guide2_status' => 'pending',
        ]);
    })->exists();
```

#### 4. Dashboard Mahasiswa — Update `resources/views/dashboard/mahasiswa.blade.php`

<!-- LAMA (typo path): resources/views/dashboard/dbs.blade.php -->

Tambahkan card "Pengajuan NUIR" yang hanya muncul jika angkatan aktif & stage 1/2:
```blade
@php
    $guideExaminer = App\Models\GuideExaminer::where('user_id', auth()->id())->first();
    $nuirSetting = $guideExaminer
        ? App\Models\NuirSetting::where('year_generation', $guideExaminer->year_generation)
            ->where('active', true)->first()
        : null;
@endphp
@if ($nuirSetting && in_array($nuirSetting->stage, [1, 2]))
<div class="card mt-3">
    <div class="card-header">Pengajuan NUIR</div>
    <div class="card-body">
        <a href="{{ route('nuir.submission.index') }}" class="btn btn-sm btn-primary">NUIR Saya</a>
        <a href="{{ route('nuir.proposal.index') }}" class="btn btn-sm btn-outline-primary">Proposal Pembimbing</a>
    </div>
</div>
@endif
```

#### 5. Dashboard Dosen — Update `resources/views/dashboard/dosen.blade.php`
Tambahkan card "Usulan NUIR Masuk":
```blade
@can('respond nuir proposal')
<div class="col-md-6 mb-3">
    <div class="card">
        <div class="card-header">Usulan NUIR Masuk</div>
        <div class="card-body">
            <a href="{{ route('nuir.dosen.index') }}" class="btn btn-sm btn-primary">Lihat Usulan</a>
        </div>
    </div>
</div>
@endcan
```

#### 6. Dashboard DBS — Update `resources/views/dashboard/dbs.blade.php`

Tambahkan card "Manajemen NUIR" dengan link ke review submissions, monitor proposal, konfigurasi.
Untuk angkatan stage 3, sertakan juga link ke `GuideExaminerResource::getUrl('index')` (Penjadwalan Filament).

#### 7. Monitor DBS — Semua Proposal

Tambahkan method `proposals()` ke `SubmissionController` (Setting):
```
GET /setting/nuir/proposals
  → DataTable NuirProposal join ke mahasiswa, dosen1, dosen2.
  → Kolom: mahasiswa, angkatan, versi, guide1, guide2, status1, status2, final, tanggal.
  → Filter: angkatan, status.
  → Read-only. Tidak ada aksi hapus.
  → Tambahkan tombol "Force Finalize" (hanya jika kedua accepted tapi final masih false).
```

#### 8. Force Finalize DBS
```
PUT /setting/nuir/proposals/{proposal}/finalize
  → Guard: guide1_status='accepted' AND guide2_status='accepted'.
  → Eksekusi logika finalize() yang sama dengan dosen accept.
  → Hanya untuk DBS.
```

### Test yang Harus Ditulis

File: `tests/Feature/NuirGuardTest.php`

```php
<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, NuirSetting, NuirSubmission, NuirProposal, GuideExaminer};
use Database\Seeders\PermissionSeeder;

class NuirGuardTest extends TestCase
{
    use RefreshDatabase;

    protected User $mahasiswa;
    protected User $dosen1;
    protected User $dosen2;
    protected User $dbs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $this->dosen1 = User::factory()->create()->assignRole('dosen');
        $this->dosen2 = User::factory()->create()->assignRole('dosen');
        $this->dbs = User::factory()->create()->assignRole('dbs');
        GuideExaminer::factory()->forStudent($this->mahasiswa)->create(['year_generation' => '2022']);
    }

    public function test_mahasiswa_tidak_dapat_submit_setelah_deadline(): void
    {
        NuirSetting::factory()->withDeadline('2020-01-01')->create([
            'year_generation' => '2022', 'stage' => 1, 'active' => true,
        ]);
        $sub = NuirSubmission::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'year_generation' => '2022', 'status' => 'draft',
        ]);

        $this->actingAs($this->mahasiswa)
             ->put("/nuir/submission/{$sub->id}/submit")
             ->assertRedirect()
             ->assertSessionHas('warning');

        $this->assertEquals('draft', $sub->fresh()->status);
    }

    public function test_mahasiswa_tidak_dapat_buat_proposal_setelah_deadline(): void
    {
        NuirSetting::factory()->withDeadline('2020-01-01')->create([
            'year_generation' => '2022', 'stage' => 1, 'active' => true,
        ]);
        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/proposal', [
                 'nuir_submission_id' => $sub->id,
                 'guide1_id' => $this->dosen1->id,
                 'guide2_id' => $this->dosen2->id,
             ])
             ->assertRedirect()
             ->assertSessionHas('warning');
    }

    public function test_mahasiswa_tidak_dapat_buat_submission_baru_jika_sudah_finalized(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        NuirSubmission::factory()->finalized()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);

        $this->actingAs($this->mahasiswa)
             ->get('/nuir/submission/create')
             ->assertRedirect(route('nuir.submission.index'));
    }

    public function test_mahasiswa_tidak_dapat_buat_proposal_duplikat_pasangan_sama(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);
        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
        NuirProposal::factory()->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id, 'guide2_id' => $this->dosen2->id,
            'guide1_status' => 'pending', 'guide2_status' => 'pending',
        ]);

        $this->actingAs($this->mahasiswa)
             ->post('/nuir/proposal', [
                 'nuir_submission_id' => $sub->id,
                 'guide1_id' => $this->dosen1->id,
                 'guide2_id' => $this->dosen2->id,
             ])
             ->assertSessionHasErrors();
    }

    public function test_dbs_dapat_melihat_monitor_semua_proposal(): void
    {
        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
        NuirProposal::factory()->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id, 'guide2_id' => $this->dosen2->id,
        ]);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1]);

        $this->actingAs($this->dbs)
             ->get('/setting/nuir/proposals')
             ->assertOk();
    }

    public function test_dbs_dapat_force_finalize_jika_kedua_dosen_sudah_accept(): void
    {
        $ge = GuideExaminer::where('user_id', $this->mahasiswa->id)->first();
        $ge->update(['guide1_id' => null, 'guide2_id' => null]);

        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id, 'guide2_id' => $this->dosen2->id,
            'guide1_status' => 'accepted', 'guide2_status' => 'accepted', 'final' => false,
        ]);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1]);

        $this->actingAs($this->dbs)
             ->put("/setting/nuir/proposals/{$proposal->id}/finalize")
             ->assertRedirect();

        $this->assertTrue($proposal->fresh()->final);
        $this->assertEquals('finalized', $sub->fresh()->status);
        $this->assertEquals($this->dosen1->id, $ge->fresh()->guide1_id);
    }

    public function test_dbs_tidak_dapat_force_finalize_jika_masih_pending(): void
    {
        $sub = NuirSubmission::factory()->contentOk()->create([
            'user_id' => $this->mahasiswa->id, 'year_generation' => '2022',
        ]);
        $proposal = NuirProposal::factory()->create([
            'nuir_submission_id' => $sub->id,
            'guide1_id' => $this->dosen1->id, 'guide2_id' => $this->dosen2->id,
        ]);
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1]);

        $this->actingAs($this->dbs)
             ->put("/setting/nuir/proposals/{$proposal->id}/finalize")
             ->assertForbidden();
    }

    public function test_mahasiswa_dashboard_menampilkan_card_nuir_jika_angkatan_aktif(): void
    {
        NuirSetting::factory()->create(['year_generation' => '2022', 'stage' => 1, 'active' => true]);

        $this->actingAs($this->mahasiswa)
             ->get('/dashboard')
             ->assertSeeText('Pengajuan NUIR');
    }

    public function test_mahasiswa_dashboard_tidak_menampilkan_nuir_jika_stage3(): void
    {
        NuirSetting::factory()->stage3()->create(['year_generation' => '2022', 'active' => true]);

        $this->actingAs($this->mahasiswa)
             ->get('/dashboard')
             ->assertDontSeeText('Pengajuan NUIR');
    }
}
```

### Perintah Test
```bash
php artisan test --filter=NuirGuardTest
```

### Jalankan semua test NUIR sekaligus untuk verifikasi akhir:
```bash
php artisan test --filter=Nuir
```

### Pesan Commit
```
feat(nuir): guard rails, navigasi dashboard, dan monitor proposal DBS

Menambahkan penjagaan deadline, proteksi submission finalized,
dan pencegahan proposal duplikat. Dashboard mahasiswa, dosen, dan DBS
diperbarui dengan card/link NUIR sesuai role dan stage aktif.
DBS mendapat halaman monitor semua proposal dan aksi force-finalize.
Test menyeluruh memverifikasi seluruh alur sistem NUIR.

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
```

---

## Ringkasan Urutan Eksekusi

<!-- Duplikat navigasi: versi canonical ada di [Panduan Navigasi](#panduan-navigasi) di awal dokumen. -->

| # | Prompt | Test File | Test Command |
|---|---|---|---|
| 1 | Migrasi, Model, Factory | `NuirModelTest` | `php artisan test --filter=NuirModelTest` |
| 2 | Konfigurasi DBS | `NuirSettingTest` | `php artisan test --filter=NuirSettingTest` |
| 3 | Form Konten Mahasiswa | `NuirSubmissionTest` | `php artisan test --filter=NuirSubmissionTest` |
| 4 | DBS Review | `NuirReviewTest` | `php artisan test --filter=NuirReviewTest` |
| 5 | Revisi Versioning | `NuirRevisionTest` | `php artisan test --filter=NuirRevisionTest` |
| 6 | Proposal ke Dosen | `NuirProposalTest` | `php artisan test --filter=NuirProposalTest` |
| 7 | Respons Dosen + Finalisasi | `NuirDosenResponseTest` | `php artisan test --filter=NuirDosenResponseTest` |
| 8 | Stage 2 & 3 | `NuirStageTest` | `php artisan test --filter=NuirStageTest` |
| 9 | Guard Rails + Dashboard | `NuirGuardTest` | `php artisan test --filter=NuirGuardTest` |

**Jalankan semua sekaligus (verifikasi akhir tiap prompt):**
```bash
php artisan test --filter=Nuir
```
