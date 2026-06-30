<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ExamSeeder::class,
            PermissionSeeder::class,
            CreateAdminUserSeeder::class,
            // UserSeeder::class, // Nonaktif sementara: impor CSV dosen/mahasiswa memperlambat migrate --seed simulasi NUIR.
            ExamRegistrationSeeder::class,
            GuideAllocationGroupSeeder::class,
            NuirSimulationAccountSeeder::class,
            NuirSeeder::class,
        ]);

        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
