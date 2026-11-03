<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MasukGoogleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.aktif' => true,
            'services.google.client_id' => 'id-uji',
            'services.google.client_secret' => 'rahasia',
        ]);
    }

    private function googleSebagai(string $email, array $tambahan = []): void
    {
        Socialite::fake('google', \Laravel\Socialite\Two\User::fake(array_merge([
            'id' => 'g-'.md5($email),
            'name' => 'Nama Google',
            'email' => $email,
            'email_verified' => true,
        ], $tambahan)));
    }

    private function penggunaDosen(array $atribut = []): User
    {
        Role::findOrCreate('dosen', 'web');

        return User::factory()->create(array_merge(['email' => 'budi@unsil.ac.id'], $atribut))->assignRole('dosen');
    }

    public function test_fitur_nonaktif_kedua_rute_404_dan_halaman_masuk_tidak_memuat_tombol(): void
    {
        config(['services.google.aktif' => false]);

        $this->get(route('auth.google.redirect'))->assertNotFound();
        $this->get(route('auth.google.callback'))->assertNotFound();
        $this->get(route('login'))->assertOk()->assertDontSee('Masuk dengan Google');
    }

    public function test_halaman_masuk_memuat_tombol_dan_tautannya(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk dengan Google')
            ->assertSee(route('auth.google.redirect', ['asal' => route('login')]), false);
    }

    public function test_halaman_masuk_filament_memuat_tombol_dan_tautannya(): void
    {
        // /admin/login dialihkan ke halaman masuk laravel/ui oleh AppServiceProvider,
        // jadi hook render Filament diuji lewat komponen Livewire halaman masuk panel admin.
        $this->get('/admin/login')->assertRedirect(route('login'));

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        \Livewire\Livewire::test(\Filament\Pages\Auth\Login::class)
            ->assertSee('Masuk dengan Google')
            ->assertSee(route('auth.google.redirect'), false);
    }

    public function test_mengarahkan_ke_google_dengan_redirect_uri_bawaan(): void
    {
        $lokasi = $this->get(route('auth.google.redirect'))->assertRedirect()->headers->get('Location');

        $this->assertStringStartsWith('https://accounts.google.com/', $lokasi);
        parse_str(parse_url($lokasi, PHP_URL_QUERY), $q);
        $this->assertSame(route('auth.google.callback'), $q['redirect_uri']);
        $this->assertStringContainsString('openid', urldecode($q['scope']));
    }

    public function test_memasukkan_akun_yang_sudah_ada_dan_menautkan_google_id(): void
    {
        $user = $this->penggunaDosen();
        $this->googleSebagai('budi@unsil.ac.id');

        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-'.md5('budi@unsil.ac.id'), $user->refresh()->google_id);
    }

    public function test_mencocokkan_surel_tanpa_peduli_huruf(): void
    {
        $user = $this->penggunaDosen();
        $this->googleSebagai('Budi@Unsil.ac.id');

        $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
    }

    public function test_membuat_akun_baru_untuk_surel_yang_belum_terdaftar(): void
    {
        $this->googleSebagai('baru@gmail.com');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('galat_google', 'Akun Google Anda sudah tercatat, tetapi belum diberi peran. Hubungi admin.');

        $this->assertGuest();
        $u = User::firstWhere('email', 'baru@gmail.com');
        $this->assertNotNull($u);
        $this->assertSame('Nama Google', $u->name);
        $this->assertNotNull($u->email_verified_at);
        $this->assertSame('g-'.md5('baru@gmail.com'), $u->google_id);
        $this->assertSame('baru', $u->username);
    }

    public function test_username_akun_baru_dibuat_unik_bila_bentrok(): void
    {
        User::factory()->create(['username' => 'baru', 'email' => 'lain@unsil.ac.id']);
        $this->googleSebagai('baru@gmail.com');

        $this->get(route('auth.google.callback'));

        $u = User::firstWhere('email', 'baru@gmail.com');
        $this->assertNotNull($u);
        $this->assertNotSame('baru', $u->username);
        $this->assertStringStartsWith('baru', $u->username);
        $this->assertSame(2, User::count());
    }

    public function test_menolak_surel_yang_belum_diverifikasi_google(): void
    {
        $this->googleSebagai('x@gmail.com', ['email_verified' => false]);

        $this->get(route('auth.google.callback'))
            ->assertSessionHas('galat_google', 'Surel akun Google Anda belum terverifikasi.');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_menolak_akun_yang_sudah_tertaut_ke_google_lain(): void
    {
        User::factory()->create(['email' => 'budi@unsil.ac.id'])->forceFill(['google_id' => 'google-lain'])->save();
        $this->googleSebagai('budi@unsil.ac.id');

        $this->get(route('auth.google.callback'))
            ->assertSessionHas('galat_google', 'Akun ini sudah ditautkan ke akun Google lain.');

        $this->assertGuest();
    }

    public function test_membatalkan_di_google_kembali_ke_halaman_masuk(): void
    {
        $this->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('login'))
            ->assertSessionHas('galat_google', 'Masuk dengan Google dibatalkan.');

        $this->assertGuest();
    }

    public function test_state_oauth_tidak_cocok_kembali_ke_halaman_masuk(): void
    {
        Socialite::fake('google', fn () => throw new InvalidStateException);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('galat_google', 'Sesi masuk Google kedaluwarsa. Silakan coba lagi.');

        $this->assertGuest();
    }

    public function test_kembali_ke_asal_bila_asal_berada_di_dalam_aplikasi_mengabaikan_asal_di_luar_aplikasi(): void
    {
        $this->withSession(['google_asal' => url('/halaman-x')])
            ->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect(url('/halaman-x'));

        $this->withSession(['google_asal' => 'https://evil.example/'])
            ->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('login'));
    }

    public function test_pengguna_yang_sudah_masuk_langsung_diarahkan(): void
    {
        $user = $this->penggunaDosen();

        $this->actingAs($user)
            ->get(route('auth.google.redirect'))
            ->assertRedirect(route('home'));
    }

    public function test_membatasi_10_permintaan_per_menit_per_ip(): void
    {
        $this->googleSebagai('x@gmail.com', ['email_verified' => false]);

        for ($i = 0; $i < 10; $i++) {
            $this->get(route('auth.google.callback'))->assertRedirect();
        }

        $this->get(route('auth.google.callback'))->assertStatus(429);
    }
}
