<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NuirSimulationAccountSeeder extends Seeder
{
    public const SIMULATION_YEAR = '2099';

    public const PASSWORD = 'simulasi';

    /**
     * Username mahasiswa simulasi berpola NIM (2 digit awal = angkatan, dibaca oleh
     * StudentYearGeneration) — "99" → SIMULATION_YEAR "2099". Label mahasiswaN pada
     * docs/nuir-simulasi.md merujuk ke urutan angka (1-9) pada array ini.
     *
     * @var array<int, string>
     */
    public const MAHASISWA_USERNAMES = [
        1 => '992151001',
        2 => '992151002',
        3 => '992151003',
        4 => '992151004',
        5 => '992151005',
        6 => '992151006',
        7 => '992151007',
        8 => '992151008',
        9 => '992151009',
    ];

    /** Mahasiswa simulasi tanpa pengajuan NUIR (workspace kosong). */
    public const EMPTY_NUIR_STUDENT_USERNAME = self::MAHASISWA_USERNAMES[9];

    /**
     * Dosen simulasi dengan kode inisial (untuk import kuota / dropdown pembimbing).
     *
     * @var array<string, string> initial => nama lengkap
     */
    public const SIMULATION_LECTURERS = [
        'DVR' => 'Dr. Diar Veni Rahayu',
        'HET' => 'Dr. Hetty Patmawati',
        'MEG' => 'Dr. Mega Nur Prabawati',
        'NAN' => 'Dr. Nani Ratnaningsih',
        'PUJ' => 'Dr. Puji Lestari',
        'MAD' => 'Dr. Sri Tirto Madawistama',
        'SUP' => 'Dr. Supratman',
        'YEN' => 'Dr. Yeni Heryani',
        'ELS' => 'Elis Nurhayati, M.Pd.',
        'EVA' => 'Eva Mulyani, M.Pd.',
        'IPH' => 'Ipah Muzdalipah, M.Pd.',
        'RAT' => 'Ratna Rustina, M.Pd.',
        'VEP' => 'Vepi Apiati, M.Pd.',
        'DDM' => 'Dedi Muhtadi, M.Pd.',
        'DPA' => 'Depi Ardian Nugraha, M.Pd.',
        'DPS' => 'Depi Setialesmana, M.Pd.',
        'DNK' => 'Dian Kurniawan, M.Pd.',
        'EKO' => 'Dr. Eko Yulianto',
        'SIN' => 'Dr. Sinta Verawati Dewi',
        'SUK' => 'Dr. Sukirwan',
        'IKE' => 'Ike Natalliasari, M.Pd.',
        'LIN' => 'Linda Herawati, M.Pd.',
        'RED' => 'Redi Hermanto, M.Pd.',
        'SAT' => 'Satya Santika, M.Pd.',
        'SIS' => 'Siska Ryane Muslim, M.Pd.',
    ];

    /**
     * Akun sementara untuk uji coba alur NUIR per role.
     * Password semua akun: {@see self::PASSWORD}
     */
    public function run(): void
    {
        $this->seedAccount('dbs', 'DBS Simulasi', 'dbs@simulasi.test', 'dbs', active: false);

        foreach (self::SIMULATION_LECTURERS as $initial => $name) {
            $this->seedAccount(
                strtolower($initial),
                $name,
                strtolower($initial).'@simulasi.test',
                'dosen',
                initial: $initial,
            );
        }

        foreach (['pembimbing1', 'pembimbing2', 'penguji1', 'penguji2', 'penguji3'] as $username) {
            $labels = [
                'pembimbing1' => 'Pembimbing Satu',
                'pembimbing2' => 'Pembimbing Dua',
                'penguji1' => 'Penguji Satu',
                'penguji2' => 'Penguji Dua',
                'penguji3' => 'Penguji Tiga',
            ];
            // pembimbing1 juga diberi role manajer & validator agar satu akun bisa
            // menjelajahi ketiga panel NUIR (Dosen, Manajer, Validator) saat simulasi.
            $roles = $username === 'pembimbing1'
                ? ['dosen', 'manajer nuir', 'validator nuir']
                : ['dosen'];
            $this->seedAccount($username, $labels[$username], "{$username}@simulasi.test", $roles);
        }

        foreach (range(1, 8) as $number) {
            $this->seedAccount(
                self::MAHASISWA_USERNAMES[$number],
                'Mahasiswa Simulasi '.$number,
                'mahasiswa'.$number.'@simulasi.test',
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
        $this->command?->info('Dosen inisial: '.count(self::SIMULATION_LECTURERS).' akun + 5 akun skenario (pembimbing1/2, penguji1/2/3).');
        $this->command?->info('Angkatan simulasi: '.self::SIMULATION_YEAR.' — lihat docs/nuir-simulasi.md untuk panduan per role.');
        $this->command?->info('Mahasiswa belum NUIR: '.self::EMPTY_NUIR_STUDENT_USERNAME.' (password: '.self::PASSWORD.').');
    }

    /**
     * @param  string|list<string>  $roles
     */
    private function seedAccount(
        string $username,
        string $name,
        string $email,
        string|array $roles,
        bool $active = true,
        ?string $initial = null,
    ): User {
        $attributes = [
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
        ];

        if ($initial !== null) {
            $attributes['initial'] = strtoupper($initial);
        }

        $user = User::updateOrCreate(
            ['username' => $username],
            $attributes,
        );

        foreach (is_array($roles) ? $roles : [$roles] as $role) {
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }

        if ($active && ! $user->can('active')) {
            $user->givePermissionTo('active');
        }

        return $user;
    }
}
