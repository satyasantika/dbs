<?php

namespace Tests\Feature;

use App\Filament\Dosen\Pages\EditScoring;
use App\Filament\Widgets\ExamRegistrationsByDateWidget;
use App\Livewire\ExamScoresDetail;
use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Models\ExamType;
use App\Models\User;
use App\Services\Examination\ExamScoreUpdater;
use App\Services\Examination\SintesysExamRegistrationImporter;
use App\Services\Sintesys\SintesysException;
use App\Services\Sintesys\SintesysTugasAkhirClient;
use Database\Seeders\ExamSeeder;
use Database\Seeders\PermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SintesysExamRegistrationSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $dosen1;

    protected User $dosen2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(ExamSeeder::class);

        $this->dosen1 = User::factory()->create([
            'username' => '0429017801',
            'name' => 'Dr. Hetty Patmawati, S.Pd., M.Pd',
        ])->assignRole('dosen');

        $this->dosen2 = User::factory()->create([
            'username' => '0419117705',
            'name' => 'Depi Setialesmana, M.Pd.',
        ])->assignRole('dosen');
    }

    public function test_client_sends_bearer_token_and_kode_prodi(): void
    {
        Http::fake([
            '*' => Http::response(['data' => []], 200),
        ]);

        app(SintesysTugasAkhirClient::class)->fetch('2026-08-01', '2026-08-06', 1);

        Http::assertSent(function ($request): bool {
            return $request->url() === config('services.sintesys.url')
                && $request->hasHeader('Authorization', 'Bearer testing-sintesys-token')
                && $request['kode_prodi'] === '2151'
                && $request['tanggal_mulai'] === '2026-08-01'
                && $request['tanggal_selesai'] === '2026-08-06'
                && $request['jenis_ujian_id'] === 1;
        });
    }

    public function test_rentang_lebih_dari_183_hari_ditolak(): void
    {
        $this->expectException(SintesysException::class);
        $this->expectExceptionMessage('183');

        app(SintesysExamRegistrationImporter::class)
            ->assertDateRange('2026-01-01', '2026-07-04');
    }

    public function test_harian_tanggal_mulai_sama_dengan_selesai_diterima(): void
    {
        app(SintesysExamRegistrationImporter::class)
            ->assertDateRange('2026-08-06', '2026-08-06');

        $this->assertTrue(true);
    }

    public function test_preview_menandai_baru_dan_duplikat(): void
    {
        Http::fake([
            '*' => Http::response(['data' => [$this->sampleRow()]], 200),
        ]);

        $importer = app(SintesysExamRegistrationImporter::class);
        $preview = $importer->preview('2025-10-01', '2025-10-31');

        $this->assertSame(1, $preview['baru']);
        $this->assertSame(0, $preview['duplikat']);
        $this->assertSame('baru', $preview['rows'][0]['status']);
        $this->assertSame('proposal', $preview['rows'][0]['jenis_ujian_color']);
        $this->assertSame('Dr. Hetty Patmawati, S.Pd., M.Pd', $preview['rows'][0]['penguji'][1]);
        $this->assertStringContainsString('Oktober', (string) $preview['rows'][0]['exam_date_label']);

        $importer->persist('2025-10-01', '2025-10-31');

        $previewAgain = $importer->preview('2025-10-01', '2025-10-31');

        $this->assertSame(0, $previewAgain['baru']);
        $this->assertSame(1, $previewAgain['duplikat']);
        $this->assertSame('duplikat', $previewAgain['rows'][0]['status']);
    }

    public function test_persist_membuat_mahasiswa_baru_dan_mengisi_nilai(): void
    {
        Http::fake([
            '*' => Http::response(['data' => [$this->sampleRow()]], 200),
        ]);

        $result = app(SintesysExamRegistrationImporter::class)
            ->persist('2025-10-01', '2025-10-31');

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['updated']);

        $student = User::query()->where('username', '222151146')->first();
        $this->assertNotNull($student);
        $this->assertTrue($student->hasRole('mahasiswa'));

        $registration = ExamRegistration::query()->where('user_id', $student->id)->first();
        $this->assertNotNull($registration);
        $this->assertSame((int) ExamType::query()->where('code', 'sempro')->value('id'), (int) $registration->exam_type_id);
        $this->assertSame('2025-10-15', $registration->exam_date->format('Y-m-d'));
        $this->assertSame($this->dosen1->id, $registration->examiner1_id);
        $this->assertSame($this->dosen2->id, $registration->examiner2_id);
        $this->assertSame($this->dosen1->id, $registration->chief_id);

        $score = ExamScore::query()
            ->where('exam_registration_id', $registration->id)
            ->where('examiner_order', 1)
            ->first();

        $this->assertNotNull($score);
        $this->assertSame(75, (int) $score->score01);
        $this->assertSame(75, (int) $score->score05);
        $this->assertSame(75.0, (float) $score->grade);
        $this->assertSame(0, (int) $score->revision);
        $this->assertStringContainsString('daftar pustaka', (string) $score->revision_note);
        $this->assertSame(1, (int) $score->pass_approved);
    }

    public function test_persist_memperbarui_duplikat_termasuk_nilai(): void
    {
        $importer = app(SintesysExamRegistrationImporter::class);

        $updatedRow = $this->sampleRow();
        $updatedRow['judul_unformated'] = 'Judul yang Diperbarui';
        $updatedRow['penguji'][0]['nilai'] = 80;
        $updatedRow['penguji'][0]['catatan'] = 'Catatan baru dari Sintesys';

        Http::fake([
            '*' => Http::sequence()
                ->push(['data' => [$this->sampleRow()]])
                ->push(['data' => [$updatedRow]]),
        ]);

        $importer->persist('2025-10-01', '2025-10-31');
        $result = $importer->persist('2025-10-01', '2025-10-31');

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);

        $registration = ExamRegistration::query()->first();
        $this->assertSame('Judul yang Diperbarui', $registration->title);
        $this->assertSame($this->dosen1->id, $registration->chief_id);

        $score = ExamScore::query()
            ->where('exam_registration_id', $registration->id)
            ->where('examiner_order', 1)
            ->first();

        $this->assertSame(80, (int) $score->score01);
        $this->assertSame(80, (int) $score->score05);
        $this->assertSame('Catatan baru dari Sintesys', $score->revision_note);
    }

    public function test_persist_mengisi_chief_id_kosong_dari_examiner1_tanpa_menimpa_ketua_yang_sudah_ada(): void
    {
        $importer = app(SintesysExamRegistrationImporter::class);

        Http::fake([
            '*' => Http::response(['data' => [$this->sampleRow()]], 200),
        ]);

        $importer->persist('2025-10-01', '2025-10-31');

        $registration = ExamRegistration::query()->first();
        $this->assertSame($this->dosen1->id, $registration->chief_id);

        $registration->update(['chief_id' => $this->dosen2->id]);

        $importer->persist('2025-10-01', '2025-10-31');

        $this->assertSame($this->dosen2->id, $registration->fresh()->chief_id);

        $registration->update(['chief_id' => null]);

        $importer->persist('2025-10-01', '2025-10-31');

        $this->assertSame($this->dosen1->id, $registration->fresh()->chief_id);
    }

    public function test_penguji_nidn_tidak_ada_menjadi_warning_slot_kosong(): void
    {
        $row = $this->sampleRow();
        $row['penguji'][1]['nidn'] = '9999999999';
        $row['penguji'][1]['nama'] = 'Dosen Tidak Ada';

        Http::fake([
            '*' => Http::response(['data' => [$row]], 200),
        ]);

        $preview = app(SintesysExamRegistrationImporter::class)
            ->preview('2025-10-01', '2025-10-31');

        $this->assertSame('baru', $preview['rows'][0]['status']);
        $this->assertNotEmpty($preview['rows'][0]['warnings']);
        $this->assertNull($preview['rows'][0]['slots'][2]);
    }

    public function test_updater_menolak_tulis_nilai_tanpa_izin_sintesys(): void
    {
        Http::fake(['*' => Http::response(['data' => [$this->sampleRow()]], 200)]);
        app(SintesysExamRegistrationImporter::class)->persist('2025-10-01', '2025-10-31');

        $score = ExamScore::query()->first();

        $this->expectException(HttpException::class);

        app(ExamScoreUpdater::class)->applyAdminFinalGrade($score, 99);
    }

    public function test_dosen_tidak_dapat_menyimpan_nilai_lewat_http(): void
    {
        Http::fake(['*' => Http::response(['data' => [$this->sampleRow()]], 200)]);
        app(SintesysExamRegistrationImporter::class)->persist('2025-10-01', '2025-10-31');

        $score = ExamScore::query()->where('user_id', $this->dosen1->id)->first();

        $this->actingAs($this->dosen1)
            ->put(route('scoring.update', $score), [
                'revision' => 0,
                'score01' => 90,
                'score02' => 90,
                'score03' => 90,
                'score04' => 90,
                'score05' => 90,
            ])
            ->assertForbidden();

        $this->assertSame(75, (int) $score->fresh()->grade);
    }

    public function test_dosen_masih_dapat_melihat_nilai_dan_catatan(): void
    {
        Http::fake(['*' => Http::response(['data' => [$this->sampleRow()]], 200)]);
        app(SintesysExamRegistrationImporter::class)->persist('2025-10-01', '2025-10-31');

        $score = ExamScore::query()->where('user_id', $this->dosen1->id)->first();
        $this->assertNotNull($score);
        $this->assertSame(75, (int) $score->grade);
        $this->assertStringContainsString('daftar pustaka', (string) $score->revision_note);

        $this->actingAs($this->dosen1)
            ->get(EditScoring::getUrl(['record' => $score->id]).'?from=archive')
            ->assertOk()
            ->assertSee('75', false)
            ->assertSee('daftar pustaka', false)
            ->assertDontSee('Simpan Penilaian', false);
    }

    public function test_admin_tidak_dapat_menyimpan_nilai_lewat_livewire(): void
    {
        Http::fake(['*' => Http::response(['data' => [$this->sampleRow()]], 200)]);
        app(SintesysExamRegistrationImporter::class)->persist('2025-10-01', '2025-10-31');

        $admin = User::factory()->create()->assignRole('admin');
        $registration = ExamRegistration::query()->first();
        $score = $registration->examScores()->first();

        Livewire::actingAs($admin)
            ->test(ExamScoresDetail::class, ['recordId' => $registration->id])
            ->assertSee((string) (int) $score->grade)
            ->assertSee('daftar pustaka')
            ->call('saveGrade')
            ->assertForbidden();
    }

    public function test_dashboard_menyinkronkan_tanggal_kalender_dengan_create_lalu_update(): void
    {
        $updatedRow = $this->sampleRow();
        $updatedRow['judul_unformated'] = 'Judul Dashboard';

        Http::fake([
            '*' => Http::sequence()
                ->push(['data' => [$this->sampleRow()]])
                ->push(['data' => [$updatedRow]]),
        ]);

        $admin = User::factory()->create()->assignRole('admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $widget = Livewire::actingAs($admin)
            ->test(ExamRegistrationsByDateWidget::class)
            ->assertSee('Sinkronisasi Sintesys')
            ->call('selectExamDate', '2025-10-15');

        $this->assertSame('2025-10-15', $widget->get('examDate'));
        $this->assertSame(0, ExamRegistration::query()->count());
        Http::assertNothingSent();

        $widget->call('syncSintesysForSelectedDate')
            ->assertNotified('Sinkronisasi Sintesys selesai');

        $this->assertSame(1, ExamRegistration::query()->count());
        $this->assertDatabaseHas('users', ['username' => '222151146']);

        Livewire::actingAs($admin)
            ->test(ExamRegistrationsByDateWidget::class)
            ->set('examDate', '2025-10-15')
            ->call('syncSintesysForSelectedDate')
            ->assertNotified('Sinkronisasi Sintesys selesai');

        $this->assertSame(1, ExamRegistration::query()->count());
        $this->assertSame('Judul Dashboard', ExamRegistration::query()->first()->title);
    }

    public function test_ujian_yang_dipindah_dihapus_otomatis(): void
    {
        $importer = app(SintesysExamRegistrationImporter::class);

        Http::fake([
            '*' => Http::sequence()
                ->push(['data' => [$this->sampleRow()]])
                ->push(['data' => []]),
        ]);

        $importer->persist('2025-10-01', '2025-10-31');

        $this->assertSame(1, ExamRegistration::query()->count());

        $result = $importer->persist('2025-10-01', '2025-10-31');

        $this->assertSame(1, $result['removed']);
        $this->assertSame(0, ExamRegistration::query()->count());
        $this->assertDatabaseCount('exam_scores', 0);
    }

    public function test_ujian_yang_dipindah_ke_tanggal_lain_di_rentang_yang_sama_tidak_hilang(): void
    {
        $importer = app(SintesysExamRegistrationImporter::class);

        $movedRow = $this->sampleRow();
        $movedRow['tanggal_ujian'] = '2025-10-20 08:05:00';

        Http::fake([
            '*' => Http::sequence()
                ->push(['data' => [$this->sampleRow()]])
                ->push(['data' => [$movedRow]]),
        ]);

        $importer->persist('2025-10-01', '2025-10-31');

        $result = $importer->persist('2025-10-01', '2025-10-31');

        // Baris lama (15 Okt) hilang dari Sintesys tapi mahasiswa yang sama
        // muncul lagi di tanggal baru (20 Okt) dalam rentang yang sama →
        // baris lama harus dihapus, baris baru dibuat, total tetap 1.
        $this->assertSame(1, $result['removed']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(1, ExamRegistration::query()->count());
        $this->assertSame('2025-10-20', ExamRegistration::query()->first()->exam_date->format('Y-m-d'));
    }

    public function test_prune_tidak_berlaku_untuk_ujian_sebelum_agustus_2025(): void
    {
        $importer = app(SintesysExamRegistrationImporter::class);

        $oldRow = $this->sampleRow();
        $oldRow['tanggal_ujian'] = '2025-07-15 08:05:00';

        Http::fake(['*' => Http::response(['data' => [$oldRow]], 200)]);
        $importer->persist('2025-07-01', '2025-07-31');

        $this->assertSame(1, ExamRegistration::query()->count());

        // Sync ulang rentang Juli 2025 (sebelum cutoff) dengan Sintesys kosong —
        // data lama tidak boleh terhapus karena di luar masa berlaku fitur ini.
        Http::fake(['*' => Http::response(['data' => []], 200)]);
        $result = $importer->persist('2025-07-01', '2025-07-31');

        $this->assertSame(0, $result['removed']);
        $this->assertSame(1, ExamRegistration::query()->count());
    }

    public function test_ujian_yang_sudah_dikirim_ke_mahasiswa_tidak_dihapus_otomatis(): void
    {
        $importer = app(SintesysExamRegistrationImporter::class);

        Http::fake(['*' => Http::response(['data' => [$this->sampleRow()]], 200)]);
        $importer->persist('2025-10-01', '2025-10-31');

        ExamRegistration::query()->first()->update(['sent_at' => now()]);

        Http::fake(['*' => Http::response(['data' => []], 200)]);
        $result = $importer->persist('2025-10-01', '2025-10-31');

        $this->assertSame(0, $result['removed']);
        $this->assertSame(1, ExamRegistration::query()->count());
    }

    /**
     * @return array<string, mixed>
     */
    protected function sampleRow(): array
    {
        return [
            'kode_prodi' => '2151',
            'nama' => 'ALLIYA PUTRI SEPTIANI',
            'nim' => '222151146',
            'judul_unformated' => 'Pengembangan E-Modul Interaktif',
            'jenis_ujian' => 'Ujian Proposal',
            'tanggal_ujian' => '2025-10-15 08:05:00',
            'tempat_ujian' => 'Ruang Sidang FKIP',
            'link_ujian' => null,
            'penguji' => [
                [
                    'urutan' => 1,
                    'nidn' => '0429017801',
                    'nama' => 'Dr. Hetty Patmawati, S.Pd., M.Pd',
                    'nilai' => 75,
                    'status_kelulusan' => 'Lulus',
                    'status_revisi' => 'Sudah Revisi',
                    'catatan' => 'perbaiki daftar pustaka',
                ],
                [
                    'urutan' => 2,
                    'nidn' => '0419117705',
                    'nama' => 'Depi Setialesmana, M.Pd.',
                    'nilai' => 80,
                    'status_kelulusan' => 'Lulus',
                    'status_revisi' => 'Belum Revisi',
                    'catatan' => 'Tinjau kembali judul',
                ],
            ],
        ];
    }
}
