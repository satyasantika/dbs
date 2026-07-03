<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\UserResource;
use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Models\ExamType;
use App\Models\GuideAllocation;
use App\Models\NuirSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserResourceTest extends TestCase
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

    public function test_user_yang_punya_nuir_submission_tidak_dapat_dihapus(): void
    {
        $mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        NuirSubmission::factory()->create([
            'user_id' => $mahasiswa->id,
            'year_generation' => '2022',
        ]);

        $this->assertTrue(UserResource::isUserInUse($mahasiswa));
    }

    public function test_user_yang_punya_guide_allocation_tidak_dapat_dihapus(): void
    {
        $dosen = User::factory()->create()->assignRole('dosen');
        GuideAllocation::create([
            'user_id' => $dosen->id,
            'year' => 2022,
            'guide1_quota' => 2,
            'guide2_quota' => 2,
            'active' => true,
        ]);

        $this->assertTrue(UserResource::isUserInUse($dosen));
    }

    public function test_user_yang_tidak_dipakai_tabel_manapun_dapat_dihapus(): void
    {
        $unused = User::factory()->create()->assignRole('mahasiswa');

        $this->assertFalse(UserResource::isUserInUse($unused));

        Livewire::actingAs($this->admin)
            ->test(UserResource\Pages\ListUsers::class)
            ->callTableAction('delete', $unused)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('users', ['id' => $unused->id]);
    }

    public function test_bulk_delete_melewati_user_yang_masih_dipakai(): void
    {
        $used = User::factory()->create()->assignRole('mahasiswa');
        NuirSubmission::factory()->create(['user_id' => $used->id, 'year_generation' => '2022']);
        $unused = User::factory()->create()->assignRole('mahasiswa');

        Livewire::actingAs($this->admin)
            ->test(UserResource\Pages\ListUsers::class)
            ->callTableBulkAction('delete', [$used, $unused]);

        $this->assertDatabaseHas('users', ['id' => $used->id]);
        $this->assertDatabaseMissing('users', ['id' => $unused->id]);
    }

    public function test_membuat_user_dengan_status_aktif_memberikan_permission_active(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserResource\Pages\CreateUser::class)
            ->fillForm([
                'name' => 'Budi',
                'username' => 'budi123',
                'email' => 'budi@example.com',
                'password' => 'rahasia123',
                'active_status' => 'aktif',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('username', 'budi123')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasPermissionTo('active'));
    }

    public function test_mengedit_user_menonaktifkan_status_mencabut_permission_active(): void
    {
        // Peran 'mahasiswa' sendiri sudah membawa permission 'active' via role
        // (lihat PermissionSeeder) — assersi di sini sengaja memeriksa
        // penetapan permission LANGSUNG (bukan hasPermissionTo(), yang tetap
        // true selama role masih memberikannya) karena itulah yang dikontrol
        // oleh select ini.
        $user = User::factory()->create()->assignRole('mahasiswa');
        $user->givePermissionTo('active');
        $this->assertTrue($user->permissions()->where('name', 'active')->exists());

        Livewire::actingAs($this->admin)
            ->test(UserResource\Pages\EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['active_status' => 'nonaktif'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($user->fresh()->permissions()->where('name', 'active')->exists());
    }

    public function test_reset_password_action_mengisi_password_dengan_username(): void
    {
        $user = User::factory()->create(['username' => 'targetuser'])->assignRole('mahasiswa');

        Livewire::actingAs($this->admin)
            ->test(UserResource\Pages\EditUser::class, ['record' => $user->getRouteKey()])
            ->mountFormComponentAction('password', 'resetPassword')
            ->callMountedFormComponentAction()
            ->assertFormSet(['password' => 'targetuser']);
    }

    public function test_simpan_edit_user_mengarahkan_ke_daftar_dan_tombol_batal_menjadi_kembali(): void
    {
        $user = User::factory()->create()->assignRole('mahasiswa');

        $component = Livewire::actingAs($this->admin)
            ->test(UserResource\Pages\EditUser::class, ['record' => $user->getRouteKey()]);

        $component->call('save')->assertHasNoFormErrors();
        $component->assertRedirect(UserResource::getUrl('index'));

        $this->actingAs($this->admin)
            ->get(UserResource::getUrl('edit', ['record' => $user]))
            ->assertSee('Kembali');
    }

    public function test_import_user_baru_via_paste(): void
    {
        $payload = [
            'rows' => [
                [
                    '_rowNum' => 1,
                    'nama' => 'Citra Dewi',
                    'username' => 'citra01',
                    'password' => 'rahasia',
                    'email' => 'citra@example.com',
                    'role' => 'mahasiswa',
                ],
            ],
        ];

        $this->actingAs($this->admin)
            ->postJson(route('users.paste-import'), $payload)
            ->assertOk()
            ->assertJsonPath('results.0.status', 'success');

        $user = User::where('username', 'citra01')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('rahasia', $user->password));
        $this->assertTrue($user->hasRole('mahasiswa'));
    }

    public function test_import_check_duplicates_mendeteksi_username_yang_sudah_ada(): void
    {
        User::factory()->create(['username' => 'existing01']);

        $this->actingAs($this->admin)
            ->postJson(route('users.paste-import-check-duplicates'), [
                'rows' => [
                    [
                        '_rowNum' => 1,
                        'nama' => 'Existing User',
                        'username' => 'existing01',
                        'email' => 'existing@example.com',
                        'role' => 'mahasiswa',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('checks.0.is_duplicate', true);
    }

    public function test_import_memperbarui_user_yang_sudah_ada_via_username(): void
    {
        $existing = User::factory()->create(['username' => 'update01', 'name' => 'Nama Lama'])->assignRole('mahasiswa');

        $this->actingAs($this->admin)
            ->postJson(route('users.paste-import'), [
                'rows' => [
                    [
                        '_rowNum' => 1,
                        'nama' => 'Nama Baru',
                        'username' => 'update01',
                        'email' => $existing->email,
                        'role' => 'dosen',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'success');

        $existing->refresh();
        $this->assertSame('Nama Baru', $existing->name);
        $this->assertTrue($existing->hasRole('dosen'));
        $this->assertFalse($existing->hasRole('mahasiswa'));
    }

    public function test_import_gagal_jika_role_tidak_ditemukan(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('users.paste-import'), [
                'rows' => [
                    [
                        '_rowNum' => 1,
                        'nama' => 'Test',
                        'username' => 'testrole',
                        'password' => 'rahasia',
                        'email' => 'testrole@example.com',
                        'role' => 'role_tidak_ada',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'error');

        $this->assertDatabaseMissing('users', ['username' => 'testrole']);
    }

    public function test_non_admin_tidak_dapat_akses_import_endpoint(): void
    {
        $dosen = User::factory()->create()->assignRole('dosen');

        $this->actingAs($dosen)
            ->postJson(route('users.paste-import'), ['rows' => [['_rowNum' => 1]]])
            ->assertForbidden();
    }

    public function test_exam_scores_terhapus_saat_exam_registration_dihapus(): void
    {
        $examType = ExamType::create(['name' => 'Skripsi']);
        $mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $dosen = User::factory()->create()->assignRole('dosen');

        $registration = ExamRegistration::create([
            'exam_type_id' => $examType->id,
            'registration_order' => 1,
            'user_id' => $mahasiswa->id,
        ]);

        $score = ExamScore::create([
            'exam_registration_id' => $registration->id,
            'user_id' => $dosen->id,
            'examiner_order' => 1,
        ]);

        $registration->delete();

        $this->assertDatabaseMissing('exam_scores', ['id' => $score->id]);
    }
}
