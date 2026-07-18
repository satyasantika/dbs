<?php

namespace Tests\Feature\Filament;

use App\Filament\Shared\Pages\ChangePassword;
use App\Filament\Shared\Pages\EditProfile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SharedProfilePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    public function test_edit_profile_updates_name_and_email(): void
    {
        $manajer = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@example.test',
        ])->assignRole('manajer nuir');

        Livewire::actingAs($manajer)
            ->test(EditProfile::class)
            ->assertFormFieldExists('name')
            ->fillForm([
                'name' => 'Nama Baru',
                'email' => 'baru@example.test',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals('Nama Baru', $manajer->fresh()->name);
        $this->assertEquals('baru@example.test', $manajer->fresh()->email);
    }

    public function test_edit_profile_rejects_email_already_used_by_another_user(): void
    {
        $manajer = User::factory()->create()->assignRole('manajer nuir');
        User::factory()->create(['email' => 'dipakai@example.test']);

        Livewire::actingAs($manajer)
            ->test(EditProfile::class)
            ->fillForm(['name' => 'Nama', 'email' => 'dipakai@example.test'])
            ->call('save')
            ->assertHasFormErrors(['email']);
    }

    public function test_change_password_updates_hashed_password(): void
    {
        $manajer = User::factory()->create()->assignRole('manajer nuir');

        Livewire::actingAs($manajer)
            ->test(ChangePassword::class)
            ->fillForm([
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('password-baru-123', $manajer->fresh()->password));
    }

    public function test_change_password_rejects_mismatched_confirmation(): void
    {
        $manajer = User::factory()->create()->assignRole('manajer nuir');

        Livewire::actingAs($manajer)
            ->test(ChangePassword::class)
            ->fillForm([
                'password' => 'password-baru-123',
                'password_confirmation' => 'tidak-cocok',
            ])
            ->call('save')
            ->assertHasFormErrors(['password']);
    }
}
