<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NuirSimulationAccountSeeder extends Seeder
{
    public const SIMULATION_YEAR = '2099';

    public const PASSWORD = 'simulasi';

    /** Mahasiswa simulasi tanpa pengajuan NUIR (workspace kosong). */
    public const EMPTY_NUIR_STUDENT_USERNAME = 'mahasiswa9';

    /**
     * Akun sementara untuk uji coba alur NUIR per role.
     * Password semua akun: {@see self::PASSWORD}
     */
    public function run(): void
    {
        $this->seedAccount('dbs', 'DBS Simulasi', 'dbs@simulasi.test', 'dbs', active: false);

        foreach (['pembimbing1', 'pembimbing2', 'penguji1', 'penguji2', 'penguji3'] as $index => $username) {
            $labels = [
                'pembimbing1' => 'Pembimbing Satu',
                'pembimbing2' => 'Pembimbing Dua',
                'penguji1' => 'Penguji Satu',
                'penguji2' => 'Penguji Dua',
                'penguji3' => 'Penguji Tiga',
            ];
            $this->seedAccount($username, $labels[$username], "{$username}@simulasi.test", 'dosen');
        }

        foreach (range(1, 8) as $number) {
            $this->seedAccount(
                "mahasiswa{$number}",
                'Mahasiswa Simulasi '.$number,
                "mahasiswa{$number}@simulasi.test",
                'mahasiswa',
            );
        }

        $this->seedAccount(
            self::EMPTY_NUIR_STUDENT_USERNAME,
            'Mahasiswa Simulasi 9 (Belum NUIR)',
            self::EMPTY_NUIR_STUDENT_USERNAME.'@simulasi.test',
            'mahasiswa',
        );

        $this->seedAccount('manajer1', 'Manajer NUIR Simulasi', 'manajer1@simulasi.test', 'manajer nuir');
        $this->seedAccount('validator1', 'Validator NUIR Simulasi', 'validator1@simulasi.test', 'validator nuir');

        $this->command?->info('NuirSimulationAccountSeeder: akun simulasi NUIR siap (password: '.self::PASSWORD.').');
        $this->command?->info('Angkatan simulasi: '.self::SIMULATION_YEAR.' — lihat docs/nuir-simulasi.md untuk panduan per role.');
        $this->command?->info('Mahasiswa belum NUIR: '.self::EMPTY_NUIR_STUDENT_USERNAME.' (password: '.self::PASSWORD.').');
    }

    private function seedAccount(
        string $username,
        string $name,
        string $email,
        string $role,
        bool $active = true,
    ): User {
        $user = User::updateOrCreate(
            ['username' => $username],
            [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(self::PASSWORD),
            ],
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        if ($active && ! $user->can('active')) {
            $user->givePermissionTo('active');
        }

        return $user;
    }
}
