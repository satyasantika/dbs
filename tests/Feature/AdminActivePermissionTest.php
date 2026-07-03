<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi: role 'admin' sempat tidak kebagian permission 'active', sehingga
 * seluruh route legacy yang dijaga middleware `can:active` (termasuk
 * paste-import pendaftaran ujian) menolak akun admin dengan
 * "This action is unauthorized." meski sudah punya role 'admin'.
 */
class AdminActivePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_admin_memiliki_permission_active(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create()->assignRole('admin');

        $this->assertTrue($admin->can('active'));
    }

    public function test_admin_dapat_akses_route_yang_dijaga_middleware_can_active(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('examregistrations.paste-import-check-duplicates'), ['rows' => [[]]])
            ->assertOk();
    }
}
