<?php

namespace Tests\Unit;

use App\Models\Navigation;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\NuirRolePermissionDeltaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirRolePermissionDeltaSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Recreates only what a pre-NUIR production database already has:
     * the baseline roles/navigation from the (non-idempotent) PermissionSeeder,
     * WITHOUT any of the manajer/validator/NUIR additions this branch introduces.
     */
    private function seedPreNuirBaseline(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'kajur']);
        Role::create(['name' => 'dbs']);
        Role::create(['name' => 'dosen']);
        Role::create(['name' => 'mahasiswa']);

        Permission::create(['name' => 'access dashboard kajur'])->assignRole('kajur');
        Permission::create(['name' => 'access dashboard dbs'])->syncRoles(['dbs', 'admin']);
        Permission::create(['name' => 'access dashboard dosen'])->assignRole('dosen');
        Permission::create(['name' => 'access dashboard mahasiswa'])->assignRole('mahasiswa');

        $dbs = Navigation::create(['name' => 'DBS', 'url' => 'dbs', 'parent_id' => null, 'order' => 'C00']);
        Navigation::create(['name' => 'MAHASISWA', 'url' => 'mahasiswa', 'parent_id' => null, 'order' => 'M00']);
        Navigation::create(['name' => 'DOSEN', 'url' => 'dosen', 'parent_id' => null, 'order' => 'D00']);

        $dbs->children()->create(['name' => 'quota pembimbing', 'url' => 'selection/guide/allocations', 'order' => 'C01']);
    }

    public function test_delta_seeder_aman_dijalankan_di_atas_baseline_lama_tanpa_error(): void
    {
        $this->seedPreNuirBaseline();

        (new NuirRolePermissionDeltaSeeder())->run();

        $this->assertTrue(Role::where('name', 'manajer nuir')->exists());
        $this->assertTrue(Role::where('name', 'validator nuir')->exists());
        $this->assertTrue(Permission::where('name', 'finalize nuir guide')->exists());
        $this->assertTrue(Permission::where('name', 'ratify selection stage')->exists());

        $manajer = Role::where('name', 'manajer nuir')->first();
        $this->assertTrue($manajer->hasPermissionTo('finalize nuir guide'));
        $this->assertTrue($manajer->hasPermissionTo('ratify selection stage'));
        $this->assertTrue($manajer->hasPermissionTo('delegate nuir validator'));

        $validator = Role::where('name', 'validator nuir')->first();
        $this->assertTrue($validator->hasPermissionTo('validate nuir references'));

        // Old, pre-existing data untouched.
        $this->assertTrue(Role::where('name', 'admin')->exists());
        $this->assertSame(1, Role::where('name', 'admin')->count());
        $this->assertSame(1, Permission::where('name', 'access dashboard kajur')->count());

        $this->assertDatabaseHas('navigations', [
            'url' => 'setting/nuir-settings',
            'name' => 'konfigurasi NUIR',
        ]);
    }

    public function test_delta_seeder_aman_dijalankan_dua_kali_idempoten(): void
    {
        $this->seedPreNuirBaseline();

        (new NuirRolePermissionDeltaSeeder())->run();
        (new NuirRolePermissionDeltaSeeder())->run();

        $this->assertSame(1, Role::where('name', 'manajer nuir')->count());
        $this->assertSame(1, Permission::where('name', 'finalize nuir guide')->count());
        $this->assertSame(1, Permission::where('name', 'read nuir submission')->count());

        $navCount = Navigation::where('url', 'nuir/submission')->count();
        $this->assertSame(1, $navCount);
    }

    public function test_read_nuir_submission_diberikan_ke_semua_role_yang_relevan(): void
    {
        $this->seedPreNuirBaseline();

        (new NuirRolePermissionDeltaSeeder())->run();

        $permission = Permission::where('name', 'read nuir submission')->first();
        $roleNames = $permission->roles()->pluck('name')->sort()->values()->all();

        $this->assertSame(['dbs', 'mahasiswa', 'manajer nuir', 'validator nuir'], $roleNames);
    }
}
