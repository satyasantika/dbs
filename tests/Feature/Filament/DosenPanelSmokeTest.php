<?php

namespace Tests\Feature\Filament;

use App\Filament\Dosen\Pages\ChiefExam;
use App\Filament\Dosen\Pages\GraduationEvidence;
use App\Filament\Dosen\Pages\GuideSupervision;
use App\Filament\Dosen\Pages\Scoring;
use App\Filament\Dosen\Pages\UnscoredScoring;
use App\Models\User;
use Tests\TestCase;

/**
 * Smoke test: memastikan Filament Dosen panel berfungsi sebagai pengganti
 * route-route lama yang akan dihapus:
 *
 *  - GET /examination/scoring          → UnscoredScoring  (/home/examination/scoring)
 *  - GET /examination/scoring-archieves → Scoring          (/home/examination/scoring/all)
 *  - redirect /examination/chief        → ChiefExam        (/home/examination/chief)
 *  - redirect /information/guides       → GuideSupervision (/home/information/guides)
 *  - redirect /information/pass         → GraduationEvidence (/home/information/pass)
 */
class DosenPanelSmokeTest extends TestCase
{
    private User $dosen;

    private User $dosenWithActive;

    protected function setUp(): void
    {
        parent::setUp();

        $dosen = User::role('dosen')->first();

        if (! $dosen) {
            $this->markTestSkipped('Tidak ada user dengan role dosen di database.');
        }

        $this->dosen = $dosen;

        // Untuk page yang membutuhkan permission 'active' (GuideSupervision, GraduationEvidence)
        $dosenWithActive = User::role('dosen')
            ->whereHas('permissions', fn ($q) => $q->where('name', 'active'))
            ->first();

        // Fallback: dosen yang dapat permission 'active' via rolenya
        if (! $dosenWithActive) {
            $dosenWithActive = User::role('dosen')->get()->first(
                fn (User $u) => $u->can('active')
            );
        }

        $this->dosenWithActive = $dosenWithActive ?? $this->dosen;
    }

    // ----------------------------------------------------------------
    // Penilaian Ujian
    // Menggantikan: GET /examination/scoring (route: scoring.index)
    // ----------------------------------------------------------------

    public function test_unscored_scoring_page_accessible(): void
    {
        // Menggantikan GET /examination/scoring
        if (! $this->dosen->can('access examination/scoring')) {
            $this->markTestSkipped('User dosen tidak punya permission access examination/scoring.');
        }

        $this->actingAs($this->dosen)
            ->get(UnscoredScoring::getUrl())
            ->assertOk();
    }

    // ----------------------------------------------------------------
    // Arsip Penilaian
    // Menggantikan: GET /examination/scoring-archieves (route: scoring.archieves)
    // ----------------------------------------------------------------

    public function test_scoring_archive_page_accessible(): void
    {
        // Menggantikan GET /examination/scoring-archieves
        if (! $this->dosen->can('access examination/scoring')) {
            $this->markTestSkipped('User dosen tidak punya permission access examination/scoring.');
        }

        $this->actingAs($this->dosen)
            ->get(Scoring::getUrl())
            ->assertOk();
    }

    // ----------------------------------------------------------------
    // Halaman Ketua Penguji
    // Menggantikan: redirect /examination/chief + GET /examination/chief/{chief}
    // ----------------------------------------------------------------

    public function test_chief_exam_page_accessible(): void
    {
        // Menggantikan GET /examination/chief (lama: redirect ke /home/examination/chief)
        $this->actingAs($this->dosen)
            ->get(ChiefExam::getUrl())
            ->assertOk();
    }

    // ----------------------------------------------------------------
    // Bimbingan Belum Lulus
    // Menggantikan: redirect /information/guides
    // ----------------------------------------------------------------

    public function test_guide_supervision_page_accessible(): void
    {
        // Menggantikan redirect /information/guides → /home/information/guides
        if (! $this->dosenWithActive->can('active')) {
            $this->markTestSkipped('Tidak ada dosen dengan permission active di database.');
        }

        $this->actingAs($this->dosenWithActive)
            ->get(GuideSupervision::getUrl())
            ->assertOk();
    }

    // ----------------------------------------------------------------
    // Lulusan Pembimbing Penguji
    // Menggantikan: redirect /information/pass
    // ----------------------------------------------------------------

    public function test_graduation_evidence_page_accessible(): void
    {
        // Menggantikan redirect /information/pass → /home/information/pass
        if (! $this->dosenWithActive->can('active')) {
            $this->markTestSkipped('Tidak ada dosen dengan permission active di database.');
        }

        $this->actingAs($this->dosenWithActive)
            ->get(GraduationEvidence::getUrl())
            ->assertOk();
    }

    // ----------------------------------------------------------------
    // Verifikasi redirect lama masih bekerja
    // ----------------------------------------------------------------

    public function test_old_information_guides_redirect_goes_to_filament(): void
    {
        $this->actingAs($this->dosen)
            ->get('/information/guides')
            ->assertRedirect('/home/information/guides');
    }

    public function test_old_information_pass_redirect_goes_to_filament(): void
    {
        $this->actingAs($this->dosen)
            ->get('/information/pass')
            ->assertRedirect('/home/information/pass');
    }

    // ----------------------------------------------------------------
    // Perlindungan akses: non-dosen tidak boleh masuk panel dosen
    // ----------------------------------------------------------------

    public function test_admin_cannot_access_dosen_panel(): void
    {
        $admin = User::role('admin')->first();

        if (! $admin) {
            $this->markTestSkipped('Tidak ada user admin di database.');
        }

        $this->actingAs($admin)
            ->get(UnscoredScoring::getUrl())
            ->assertForbidden();
    }
}
