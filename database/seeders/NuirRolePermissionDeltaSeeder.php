<?php

namespace Database\Seeders;

use App\Models\Navigation;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * One-time production deploy helper: creates only the roles/permissions/nav
 * entries this NUIR branch adds on top of an already-seeded PermissionSeeder
 * baseline (admin/kajur/dbs/dosen/mahasiswa + their older permissions).
 *
 * PermissionSeeder itself uses non-idempotent Role::create()/Permission::create()
 * calls throughout and is NOT safe to re-run against a database that already
 * has those baseline rows — this seeder exists so production doesn't need to
 * touch that file. Every statement here is idempotent (firstOrCreate /
 * existence-checked), so it's safe to run more than once if needed.
 *
 * Usage: php artisan db:seed --class=NuirRolePermissionDeltaSeeder
 */
class NuirRolePermissionDeltaSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'manajer nuir']);
        Role::firstOrCreate(['name' => 'validator nuir']);

        Permission::firstOrCreate(['name' => 'active'])
            ->syncRoles(['admin', 'dbs', 'dosen', 'mahasiswa', 'kajur', 'manajer nuir', 'validator nuir']);

        Permission::firstOrCreate(['name' => 'manage nuir settings'])->syncRoles(['dbs', 'manajer nuir']);
        Permission::firstOrCreate(['name' => 'manage nuir guide quota'])->syncRoles(['manajer nuir']);
        Permission::firstOrCreate(['name' => 'access setting/nuir-settings'])->syncRoles(['dbs']);

        $this->createNavChild('C00', 'konfigurasi NUIR', 'setting/nuir-settings');

        Permission::firstOrCreate(['name' => 'create nuir submission'])->syncRoles(['mahasiswa']);
        Permission::firstOrCreate(['name' => 'update nuir submission'])->syncRoles(['mahasiswa']);
        $readNuirSubmission = Permission::firstOrCreate(['name' => 'read nuir submission']);
        Permission::firstOrCreate(['name' => 'access nuir/submission'])->syncRoles(['mahasiswa']);

        $this->createNavChild('M00', 'pengajuan NUIR', 'nuir/submission');

        Permission::firstOrCreate(['name' => 'review nuir submission'])->syncRoles(['dbs']);
        Permission::firstOrCreate(['name' => 'access setting/nuir/submissions'])->syncRoles(['dbs']);

        $this->createNavChild('C00', 'review NUIR', 'setting/nuir/submissions');

        Permission::firstOrCreate(['name' => 'create nuir proposal'])->syncRoles(['mahasiswa']);
        $readNuirProposal = Permission::firstOrCreate(['name' => 'read nuir proposal']);
        Permission::firstOrCreate(['name' => 'access nuir/proposal'])->syncRoles(['mahasiswa']);

        $this->createNavChild('M00', 'usulan calon pembimbing NUIR', 'nuir/proposal');

        Permission::firstOrCreate(['name' => 'respond nuir proposal'])->syncRoles(['dosen']);
        Permission::firstOrCreate(['name' => 'access nuir/dosen'])->syncRoles(['dosen']);

        $this->createNavChild('D00', 'usulan NUIR masuk', 'nuir/dosen');

        Permission::firstOrCreate(['name' => 'access dashboard manajer nuir'])->syncRoles(['manajer nuir']);
        Permission::firstOrCreate(['name' => 'access dashboard validator nuir'])->syncRoles(['validator nuir']);
        Permission::firstOrCreate(['name' => 'delegate nuir validator'])->syncRoles(['manajer nuir']);
        Permission::firstOrCreate(['name' => 'delete nuir submission'])->syncRoles(['manajer nuir']);
        Permission::firstOrCreate(['name' => 'validate nuir references'])->syncRoles(['validator nuir']);
        Permission::firstOrCreate(['name' => 'finalize nuir guide'])->syncRoles(['manajer nuir']);
        Permission::firstOrCreate(['name' => 'ratify selection stage'])->syncRoles(['manajer nuir']);

        // Union of every role that should read nuir submissions/proposals,
        // gathered across the original seeder's several partial assignments.
        $readNuirSubmission->syncRoles(['mahasiswa', 'dbs', 'manajer nuir', 'validator nuir']);
        $readNuirProposal->syncRoles(['mahasiswa', 'dosen']);
    }

    private function createNavChild(string $parentOrder, string $name, string $url): void
    {
        $parent = Navigation::where('order', $parentOrder)->first();

        if (! $parent) {
            return;
        }

        $parent->children()->firstOrCreate(
            ['url' => $url],
            [
                'name' => $name,
                // Matches PermissionSeeder's existing convention for this parent group.
                'order' => substr($parentOrder, 0, 1).'0'.($parent->children()->count() + 1),
            ],
        );
    }
}
