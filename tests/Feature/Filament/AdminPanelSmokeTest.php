<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ExamRegistrationResource;
use App\Filament\Resources\GuideExaminerResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Tests\TestCase;

/**
 * Smoke test: memastikan Filament Admin panel berfungsi sebagai pengganti
 * route-route lama di setting/ yang akan dihapus.
 *
 * Jalankan sebelum dan sesudah penghapusan route lama untuk konfirmasi.
 */
class AdminPanelSmokeTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::role('admin')->first();

        if (! $admin) {
            $this->markTestSkipped('Tidak ada user dengan role admin di database.');
        }

        $this->admin = $admin;
    }

    // ----------------------------------------------------------------
    // Manajemen Pengguna
    // Menggantikan: setting/users, setting/userroles, setting/userpermissions
    // ----------------------------------------------------------------

    public function test_users_index_accessible(): void
    {
        // Menggantikan GET /setting/users
        $this->actingAs($this->admin)
            ->get(UserResource::getUrl('index'))
            ->assertOk();
    }

    public function test_users_create_accessible(): void
    {
        // Menggantikan GET /setting/users/create
        $this->actingAs($this->admin)
            ->get(UserResource::getUrl('create'))
            ->assertOk();
    }

    public function test_users_edit_accessible(): void
    {
        // Menggantikan GET /setting/users/{id}/edit
        $user = User::where('id', '!=', $this->admin->id)->first();

        if (! $user) {
            $this->markTestSkipped('Tidak ada user lain untuk diedit.');
        }

        $this->actingAs($this->admin)
            ->get(UserResource::getUrl('edit', ['record' => $user]))
            ->assertOk();
    }

    // ----------------------------------------------------------------
    // Manajemen Role
    // Menggantikan: setting/roles, setting/rolepermissions
    // ----------------------------------------------------------------

    public function test_roles_index_accessible(): void
    {
        // Menggantikan GET /setting/roles
        $this->actingAs($this->admin)
            ->get(RoleResource::getUrl('index'))
            ->assertOk();
    }

    public function test_roles_create_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(RoleResource::getUrl('create'))
            ->assertOk();
    }

    public function test_roles_edit_has_permission_assignment(): void
    {
        // Menggantikan: edit role + setting/rolepermissions (assign permission ke role)
        $role = \App\Models\Role::where('name', '!=', 'admin')->first();

        if (! $role) {
            $this->markTestSkipped('Tidak ada role non-admin untuk diedit.');
        }

        $this->actingAs($this->admin)
            ->get(RoleResource::getUrl('edit', ['record' => $role]))
            ->assertOk();
    }

    // ----------------------------------------------------------------
    // Manajemen Permission dan Manajemen Seleksi (Tahap, Elemen NUIR, Kuota
    // Pembimbing) sengaja TIDAK terdaftar di panel admin — sudah dikelola
    // lewat panel Manajer NUIR / Dbs. Lihat AdminPanelProvider::panel().
    // ----------------------------------------------------------------

    // ----------------------------------------------------------------
    // Manajemen Ujian — Pembimbing & Penguji
    // Menggantikan: setting/guideexaminers
    // ----------------------------------------------------------------

    public function test_guide_examiners_index_accessible(): void
    {
        // Menggantikan GET /setting/guideexaminers
        $this->actingAs($this->admin)
            ->get(GuideExaminerResource::getUrl('index'))
            ->assertOk();
    }

    public function test_guide_examiners_create_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(GuideExaminerResource::getUrl('create'))
            ->assertOk();
    }

    // ----------------------------------------------------------------
    // Manajemen Ujian — Pendaftaran Ujian
    // Menggantikan: setting/examregistrations (CRUD dasar)
    // ----------------------------------------------------------------

    public function test_exam_registrations_index_accessible(): void
    {
        // Menggantikan GET /setting/examregistrations
        $this->actingAs($this->admin)
            ->get(ExamRegistrationResource::getUrl('index'))
            ->assertOk();
    }

    public function test_exam_registrations_create_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(ExamRegistrationResource::getUrl('create'))
            ->assertOk();
    }

    // ----------------------------------------------------------------
    // Perlindungan akses: non-admin tidak boleh masuk panel admin
    // ----------------------------------------------------------------

    public function test_dosen_cannot_access_admin_panel(): void
    {
        $dosen = User::role('dosen')->first();

        if (! $dosen) {
            $this->markTestSkipped('Tidak ada user dosen di database.');
        }

        $this->actingAs($dosen)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }
}
