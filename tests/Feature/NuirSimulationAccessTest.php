<?php

namespace Tests\Feature;

use App\Filament\Dbs\Resources\NuirProposalResource;
use App\Filament\Dbs\Resources\NuirSettingResource;
use App\Filament\Dbs\Resources\NuirSubmissionResource;
use App\Filament\Mahasiswa\Pages\Dashboard;
use App\Models\User;
use Database\Seeders\NuirSeeder;
use Database\Seeders\NuirSimulationAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NuirSimulationAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(NuirSimulationAccountSeeder::class);
        $this->seed(NuirSeeder::class);
    }

    public function test_akun_simulasi_memakai_password_simulasi(): void
    {
        foreach (['dbs', 'manajer1', 'validator1', 'pembimbing1', 'pembimbing2', 'penguji1', 'mahasiswa1', 'mahasiswa8'] as $username) {
            $user = User::where('username', $username)->first();
            $this->assertNotNull($user, "Akun {$username} harus ada.");
            $this->assertTrue(
                Hash::check(NuirSimulationAccountSeeder::PASSWORD, $user->password),
                "Password akun {$username} harus 'simulasi'.",
            );
        }
    }

    public function test_dbs_dapat_akses_panel_dan_nuir_filament(): void
    {
        $dbs = User::where('username', 'dbs')->first();

        $this->actingAs($dbs)
            ->get('/dbs')
            ->assertOk();

        $this->actingAs($dbs)
            ->get(NuirSettingResource::getUrl('index', panel: 'dbs'))
            ->assertOk();

        $this->actingAs($dbs)
            ->get(NuirSubmissionResource::getUrl('index', panel: 'dbs'))
            ->assertOk();

        $this->actingAs($dbs)
            ->get(NuirProposalResource::getUrl('index', panel: 'dbs'))
            ->assertOk();
    }

    public function test_mahasiswa_simulasi_dapat_akses_nuir_submission_dan_proposal(): void
    {
        $mahasiswa1 = User::where('username', 'mahasiswa1')->first();
        $mahasiswa5 = User::where('username', 'mahasiswa5')->first();

        $this->actingAs($mahasiswa1)
            ->get(Dashboard::getUrl(panel: 'mahasiswa'))
            ->assertOk();

        $this->actingAs($mahasiswa1)
            ->get(\App\Filament\Mahasiswa\Pages\NuirSubmissionOverview::getUrl(panel: 'mahasiswa'))
            ->assertOk();

        $this->actingAs($mahasiswa5)
            ->followingRedirects()
            ->get(\App\Filament\Mahasiswa\Pages\NuirProposalOverview::getUrl(panel: 'mahasiswa'))
            ->assertOk();
    }

    public function test_pembimbing_dapat_akses_usulan_nuir(): void
    {
        $pembimbing1 = User::where('username', 'pembimbing1')->first();

        $this->actingAs($pembimbing1)
            ->get('/home')
            ->assertOk();

        $this->actingAs($pembimbing1)
            ->get('/nuir/dosen')
            ->assertOk();
    }

    public function test_manajer_simulasi_dapat_akses_panel(): void
    {
        $manajer = User::where('username', 'manajer1')->first();

        $this->actingAs($manajer)
            ->get('/nuir-manajer')
            ->assertOk();
    }

    public function test_validator_simulasi_dapat_akses_panel(): void
    {
        $validator = User::where('username', 'validator1')->first();

        $this->actingAs($validator)
            ->get('/nuir-validator')
            ->assertOk();
    }

    public function test_penguji_dapat_akses_panel_dosen(): void
    {
        $penguji1 = User::where('username', 'penguji1')->first();

        $this->actingAs($penguji1)
            ->get('/home')
            ->assertOk();
    }

    public function test_mahasiswa_simulasi_tidak_dapat_akses_panel_dbs(): void
    {
        $mahasiswa1 = User::where('username', 'mahasiswa1')->first();

        $this->actingAs($mahasiswa1)
            ->get(NuirSettingResource::getUrl('index', panel: 'dbs'))
            ->assertForbidden();
    }
}
