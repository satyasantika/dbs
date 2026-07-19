<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\RoleResource;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminRoleResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);

        $this->admin = User::factory()->create()->assignRole('admin');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_daftar_role_menampilkan_jumlah_pengguna(): void
    {
        $role = Role::create(['name' => 'peran-uji', 'guard_name' => 'web']);
        User::factory()->count(2)->create()->each(fn (User $user) => $user->assignRole($role));

        Livewire::actingAs($this->admin)
            ->test(RoleResource\Pages\ListRoles::class)
            ->assertTableColumnStateSet('users_count', 2, record: $role->getKey());
    }

    public function test_hapus_disembunyikan_untuk_role_yang_masih_punya_pengguna(): void
    {
        $role = Role::create(['name' => 'peran-terpakai', 'guard_name' => 'web']);
        User::factory()->create()->assignRole($role);

        Livewire::actingAs($this->admin)
            ->test(RoleResource\Pages\ListRoles::class)
            ->assertTableActionHidden('delete', record: $role->getKey());
    }

    public function test_hapus_tersedia_untuk_role_tanpa_pengguna(): void
    {
        $role = Role::create(['name' => 'peran-kosong', 'guard_name' => 'web']);

        Livewire::actingAs($this->admin)
            ->test(RoleResource\Pages\ListRoles::class)
            ->assertTableActionVisible('delete', record: $role->getKey());
    }

    public function test_role_admin_tidak_pernah_bisa_dihapus_walau_tanpa_pengguna_lain(): void
    {
        $adminRole = Role::where('name', 'admin')->first();

        Livewire::actingAs($this->admin)
            ->test(RoleResource\Pages\ListRoles::class)
            ->assertTableActionHidden('delete', record: $adminRole->getKey());
    }
}
