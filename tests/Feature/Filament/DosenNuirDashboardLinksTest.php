<?php

namespace Tests\Feature\Filament;

use App\Filament\Dosen\Pages\Dashboard as DosenDashboard;
use App\Filament\Dosen\Widgets\DosenStatsWidget;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DosenNuirDashboardLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    public function test_dosen_dengan_role_validator_melihat_tautan_panel_nuir_validator(): void
    {
        $user = User::factory()->create()->assignRole(['dosen', 'validator nuir']);
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk();

        Livewire::actingAs($user)
            ->test(DosenStatsWidget::class)
            ->assertSee('Validasi NUIR')
            ->assertSee('nuir-validator');
    }

    public function test_dosen_dengan_role_manajer_melihat_tautan_panel_nuir_manajer(): void
    {
        $user = User::factory()->create()->assignRole(['dosen', 'manajer nuir']);
        $user->givePermissionTo('active');

        $this->actingAs($user)
            ->get(DosenDashboard::getUrl(panel: 'dosen'))
            ->assertOk();

        Livewire::actingAs($user)
            ->test(DosenStatsWidget::class)
            ->assertSee('Manajemen NUIR')
            ->assertSee('nuir-manajer');
    }

    public function test_dosen_tanpa_role_nuir_tidak_melihat_tautan_panel_nuir(): void
    {
        $user = User::factory()->create()->assignRole('dosen');
        $user->givePermissionTo('active');

        Livewire::actingAs($user)
            ->test(DosenStatsWidget::class)
            ->assertDontSee('Validasi NUIR')
            ->assertDontSee('Manajemen NUIR');
    }

    public function test_dosen_dengan_kedua_role_nuir_melihat_kedua_tautan(): void
    {
        $user = User::factory()->create()->assignRole(['dosen', 'validator nuir', 'manajer nuir']);
        $user->givePermissionTo('active');

        Livewire::actingAs($user)
            ->test(DosenStatsWidget::class)
            ->assertSee('Validasi NUIR')
            ->assertSee('Manajemen NUIR')
            ->assertSee('nuir-validator')
            ->assertSee('nuir-manajer');
    }
}
