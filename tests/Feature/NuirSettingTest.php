<?php

namespace Tests\Feature;

use App\Filament\Dbs\Resources\NuirSettingResource;
use App\Models\NuirSetting;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
            ->assertRedirect(NuirSettingResource::getUrl('index', panel: 'dbs'));

        $this->actingAs($this->dbs)
            ->get(NuirSettingResource::getUrl('index', panel: 'dbs'))
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
                'max_references' => 10,
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
        NuirSubmission::factory()->create(['year_generation' => '2022']);

        $this->actingAs($this->dbs)
            ->delete("/setting/nuir-settings/{$setting->id}")
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('nuir_settings', ['id' => $setting->id]);
    }
}
