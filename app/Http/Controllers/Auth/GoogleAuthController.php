<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\MasukDenganGoogle;
use App\Exceptions\MasukGoogleDitolak;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): SymfonyRedirectResponse
    {
        abort_unless(config('services.google.aktif'), 404);

        if (Auth::check()) {
            return redirect()->to($this->tujuan(Auth::user()) ?? route('login'));
        }

        session(['google_asal' => $request->query('asal')]);

        return Socialite::driver('google')->scopes(['openid', 'email', 'profile'])->redirect();
    }

    public function callback(Request $request, MasukDenganGoogle $masuk): RedirectResponse
    {
        abort_unless(config('services.google.aktif'), 404);

        $asal = $request->session()->pull('google_asal');

        if ($request->filled('error')) {
            return $this->gagal('Masuk dengan Google dibatalkan.', $asal);
        }

        try {
            $google = Socialite::driver('google')->user();
            $user = $masuk->jalankan($google);
        } catch (MasukGoogleDitolak $e) {
            return $this->gagal($e->getMessage(), $asal);
        } catch (InvalidStateException) {
            return $this->gagal('Sesi masuk Google kedaluwarsa. Silakan coba lagi.', $asal);
        } catch (Throwable $e) {
            report($e);

            return $this->gagal('Tidak dapat terhubung dengan Google. Silakan coba lagi.', $asal);
        }

        $tujuan = $this->tujuan($user);

        if ($tujuan === null) {
            return $this->gagal('Akun Google Anda sudah tercatat, tetapi belum diberi peran. Hubungi admin.', $asal);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to($tujuan);
    }

    private function tujuan(User $user): ?string
    {
        return $user->roles()->exists() ? route('home') : null;
    }

    private function gagal(string $pesan, ?string $asal): RedirectResponse
    {
        $ke = ($asal !== null && str_starts_with($asal, url('/'))) ? $asal : route('login');

        return redirect()->to($ke)->with('galat_google', $pesan);
    }
}
