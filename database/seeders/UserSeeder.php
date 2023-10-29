<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
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
                    'password' => bcrypt($data[4]),
                    'initial'  => $data[5],
                ])->assignRole('dosen')->givePermissionTo('active');
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
                    'password' => bcrypt($data[5]),

                ])->assignRole('mahasiswa')->givePermissionTo('active');
            }
            $transRow = false;
        }
        fclose($csvData);

        User::create([
            'username'  => 'dosen1',
            'name'      => 'Dosen 01',
            'email'     => 'dosen01@unsil.ac.id',
            'password'  => bcrypt('1234'),
        ])->assignRole('dosen');
        User::create([
            'username'  => 'dosen2',
            'name'      => 'Dosen 02',
            'email'     => 'dosen02@unsil.ac.id',
            'password'  => bcrypt('1234'),
        ])->assignRole('dosen');

        User::create([
            'username'  => 'mahasiswa',
            'name'      => 'Mahasiswa 01',
            'email'     => 'mahasiswa@student.unsil.ac.id',
            'password'  => bcrypt('1234'),
        ])->assignRole('mahasiswa');

        User::create([
            'username'  => 'dbs',
            'name'      => 'Akun DBS',
            'email'     => 'dbs@unsil.ac.id',
            'password'  => bcrypt('1234'),
        ])->assignRole('dbs');

    }
}
