<?php

namespace App\Actions\Auth;

use App\Exceptions\MasukGoogleDitolak;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as GoogleUser;

class MasukDenganGoogle
{
    public function jalankan(GoogleUser $google): User
    {
        $email = strtolower(trim((string) $google->getEmail()));
        $raw = $google instanceof AbstractUser ? $google->getRaw() : [];
        $terverifikasi = filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false, FILTER_VALIDATE_BOOL);

        if ($email === '' || ! $terverifikasi) {
            throw new MasukGoogleDitolak('Surel akun Google Anda belum terverifikasi.');
        }

        $user = User::where('google_id', $google->getId())->first()
            ?? User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user === null) {
            $user = User::create([
                'name' => $google->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'username' => $this->usernameUnik(Str::before($email, '@')),
                'password' => Hash::make(Str::random(40)),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($user->google_id !== null && $user->google_id !== $google->getId()) {
            throw new MasukGoogleDitolak('Akun ini sudah ditautkan ke akun Google lain.');
        }

        if ($user->google_id === null) {
            $user->forceFill(['google_id' => $google->getId()])->save();
        }

        return $user;
    }

    private function usernameUnik(string $dasar): string
    {
        $username = $dasar;
        $urutan = 1;

        while (User::where('username', $username)->exists()) {
            $username = $dasar.'-'.(++$urutan);
        }

        return $username;
    }
}
