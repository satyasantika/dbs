<?php

namespace Database\Seeders;

use App\Models\Navigation;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'kajur']);
        Role::create(['name' => 'dbs']);
        Role::create(['name' => 'dosen']);
        Role::create(['name' => 'mahasiswa']);

        Permission::create(['name' => 'read dashboard/kajur'])->assignRole('kajur');
        Permission::create(['name' => 'read dashboard/dbs'])->assignRole('dbs');
        Permission::create(['name' => 'read dashboard/dosen'])->assignRole('dosen');
        Permission::create(['name' => 'read dashboard/mahasiswa'])->assignRole('mahasiswa');

        $action = ['read', 'create', 'update', 'delete'];

        // permission all complete
        $alone_access = [
            'roles',
            'users',
            'permissions',
            'setting/rolepermissions',
            'setting/userroles',
            'setting/userpermissions',
        ];
        $permissions = [];
        foreach ($alone_access as $access_value) {
            foreach ($action as $action_value) {
                array_push($permissions,$action_value.' '.$access_value);
            }
        }

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission])->assignRole('admin');
        }

        $admin_access = [
            ['role','setting/roles'],
            ['permission','setting/permissions'],
            ['user','setting/users'],
            ['navigation','setting/navigations'],
        ];

        // Role Admin
        $permissions = [];
        foreach ($admin_access as $access_value) {
            foreach ($action as $action_value) {
                array_push($permissions,$action_value.' '.$access_value[1]);
            }
        }
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission])->assignRole('admin');
        }
        // MENU setting
        $setting = Navigation::create([
            'name' => 'Setting',
            'url' => 'setting',
            'icon' => 'ti-settings',
            'parent_id' => null,
            'order' => Navigation::count() + 1,
        ]);
        foreach ($admin_access as $child) {
            // $part = explode('/',$child);
            $setting->children()->create([
                'name' => $child[0],
                'url' => $child[1],
                'icon' => 'ti-settings',
                'order' => Navigation::count() + 1,
            ]);
        }

        // Role Kajur
        // $kajur_access = [
            // ['mysubject','mapping/mysubjects'],
            // ['Mapping Jurusan','mapping/departementmaps'],
        // ];
        // $permissions = [];
        // foreach ($kajur_access as $access_value) {
        //     foreach ($action as $action_value) {
        //         array_push($permissions,$action_value.' '.$access_value[1]);
        //     }
        // }

        // foreach ($permissions as $permission) {
        //     Permission::create(['name' => $permission])->assignRole('kajur');
        // }

        // $mapping = Navigation::create([
        //     'name' => 'Mapping',
        //     'url' => 'mapping',
        //     'icon' => '',
        //     'parent_id' => null,
        //     'order' => Navigation::count() + 1,
        // ]);

        // foreach ($kajur_access as $child) {
        //     $mapping->children()->create([
        //         'name' => $child[0],
        //         'url' => $child[1],
        //         'icon' => '',
        //         'order' => Navigation::count() + 1,
        //     ]);
        // }

        // $kajur_access = [
        //     ['Yudisium PLP 1','yudisium/plp1'],
        //     ['Yudisium PLP 2','yudisium/plp2'],
        // ];
        // $permissions = [];
        // foreach ($kajur_access as $access_value) {
        //     foreach ($action as $action_value) {
        //         array_push($permissions,$action_value.' '.$access_value[1]);
        //     }
        // }

        // foreach ($permissions as $permission) {
        //     Permission::create(['name' => $permission])->assignRole('kajur');
        // }

        // $yudisium = Navigation::create([
        //     'name' => 'Yudisium',
        //     'url' => 'yudisium',
        //     'icon' => '',
        //     'parent_id' => null,
        //     'order' => Navigation::count() + 1,
        // ]);

        // foreach ($kajur_access as $child) {
        //     // $part = explode('/',$child);
        //     $yudisium->children()->create([
        //         'name' => $child[0],
        //         'url' => $child[1],
        //         'icon' => '',
        //         'order' => Navigation::count() + 1,
        //     ]);
        // }

        // Role Dosen
        // $dosen_access = [
        //     ['Monitoring','aktivitas/lecturemonitors'],
        //     ['Verifikasi Logbook 1','aktivitas/diaryverifications/plp1'],
        //     ['Verifikasi Logbook 2','aktivitas/diaryverifications/plp2'],
        //     ['Nilai N2.1','aktivitas/schoolassessments/plp1/2022N2'],
        //     ['Nilai N2.2','aktivitas/schoolassessments/plp2/2022N2'],
        //     ['Nilai N8','aktivitas/schoolassessments/plp1/2022N8'],
        // ];
        // $permissions = [];
        // foreach ($dosen_access as $access_value) {
        //     foreach ($action as $action_value) {
        //         array_push($permissions,$action_value.' '.$access_value[1]);
        //     }
        // }

        // foreach ($permissions as $permission) {
        //     Permission::create(['name' => $permission])->assignRole('dosen');
        // }

        // foreach ($dosen_access as $child) {
        //     // $part = explode('/',$child);
        //     $aktivitas->children()->create([
        //         'name' => $child[0],
        //         'url' => $child[1],
        //         'icon' => '',
        //         'order' => Navigation::count() + 1,
        //     ]);
        // }

        // Role Mahasiswa
        // $mahasiswa_access = [
        //     ['Observasi','aktivitas/studentobservations'],
        //     ['Logbook 1','aktivitas/studentdiaries/plp1'],
        //     ['Logbook 2','aktivitas/studentdiaries/plp2'],
        //     ['Catatan Ujian','aktivitas/teachingrespons'],
        // ];
        // $permissions = [];
        // foreach ($mahasiswa_access as $access_value) {
        //     foreach ($action as $action_value) {
        //         array_push($permissions,$action_value.' '.$access_value[1]);
        //     }
        // }

        // foreach ($permissions as $permission) {
        //     Permission::create(['name' => $permission])->assignRole('mahasiswa');
        // }

        // foreach ($mahasiswa_access as $child) {
        //     // $part = explode('/',$child);
        //     $aktivitas->children()->create([
        //         'name' => $child[0],
        //         'url' => $child[1],
        //         'icon' => '',
        //         'order' => Navigation::count() + 1,
        //     ]);
        // }

    }
}
