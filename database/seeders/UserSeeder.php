<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Import Data Dosen
        $csvData = fopen(base_path('/database/seeders/csvs/lectures.csv'), 'r');
        $transRow = true;
        while (($data = fgetcsv($csvData, 555, ',')) !== false) {
            if (!$transRow) {
                User::create([
                    'username'  => $data[0],
                    'name'      => $data[1],
                    'phone'     => $data[2],
                    'email'     => $data[3],
                    'password'  => Hash::make($data[4]),

                    // 'address'   => $data[6],
                ])->assignRole('dosen');
            }
            $transRow = false;
        }
        fclose($csvData);

        // Import Data Mahasiswa
        $csvData = fopen(base_path('/database/seeders/csvs/students.csv'), 'r');
        $transRow = true;
        while (($data = fgetcsv($csvData, 555, ',')) !== false) {
            if (!$transRow) {
                User::create([
                    'username'  => $data[0],
                    'name'      => $data[1],
                    'phone'     => $data[2],
                    'address'   => $data[3],
                    'email'     => $data[4],
                    'password'  => Hash::make($data[5]),

                ])->assignRole('mahasiswa');
            }
            $transRow = false;
        }
        fclose($csvData);
    }
}
