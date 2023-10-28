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

        Permission::create(['name' => 'access dashboard kajur'])->assignRole('kajur');
        Permission::create(['name' => 'access dashboard dbs'])->assignRole('dbs');
        Permission::create(['name' => 'access dashboard dosen'])->assignRole('dosen');
        Permission::create(['name' => 'access dashboard mahasiswa'])->assignRole('mahasiswa');

        $actions = ['read', 'create', 'update', 'delete'];

        // general permission for ADMIN
        $general_permissions = [
            'roles',
            'users',
            'permissions',
            'navigations',
            'selection stages',
            'selection elements',
            'selection element comments',
            'selection guides',
            'guide allocations',
            'guide groups',
            'exam types',
            'exam form items',
            'exam registrations',
            'examiners',
            'exam scores',
        ];
        $permissions = [];
        foreach ($general_permissions as $general_permission) {
            foreach ($actions as $action_value) {
                array_push($permissions,$action_value.' '.$general_permission);
            }
        }

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission])->assignRole('admin');
        }

        // MENU for ADMIN
        $admin_access = [
            ['role','setting/roles'],
            ['permission','setting/permissions'],
            ['user','setting/users'],
            ['navigation','setting/navigations'],
            ['stage','setting/selectionstages'],
            ['element','setting/selectionelements'],
            ['elementcomment','setting/selectionelementcomments'],
            ['guide','setting/selectionguides'],
            ['guideallocation','setting/selectionguideallocations'],
            ['guidegroup','setting/selectionguidegroups'],
            ['exam type','setting/exam/types'],
            ['exam form item','setting/exam/formitems'],
            ['exam registration','setting/examregistrations'],
            ['examiner','setting/examexaminers'],
            ['exam score','setting/examscores'],
        ];

        // Role Admin
        $permissions = [];
        foreach ($admin_access as $access_value) {
                array_push($permissions,'access '.$access_value[1]);
        }
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission])->assignRole('admin');
        }
        // MENU setting
        $setting = Navigation::create([
            'name' => 'Setting',
            'url' => 'setting',
            'parent_id' => null,
            'order' => 'A00',
        ]);
        foreach ($admin_access as $child) {
            $setting->children()->create([
                'name' => $child[0],
                'url' => $child[1],
                'order' => (Navigation::where('parent_id',$setting->id)->count()+1<10 ? 'A0':'A').Navigation::where('parent_id',$setting->id)->count()+1,
            ]);
        }

        // general permission for MAHASISWA
        // MENU for MAHASISWA
        $mahasiswa_access = [
            ['ajuan NUIR','selection/elements'],
            ['ajuan pembimbing','selection/guides'],
            ['registrasi ujian','exam/registrations'],
        ];
        $permissions = [];
        foreach ($mahasiswa_access as $access_value) {
            array_push($permissions,'access '.$access_value[1]);
        }

        foreach ($permissions as $permission) {
            $permissionOne = Permission::create(['name' => $permission]);
            $permissionOne->assignRole('mahasiswa');
            $permissionOne->assignRole('dosen');
        }

        // MENU selection
        $setting = Navigation::create([
            'name' => 'MAHASISWA',
            'url' => 'mahasiswa',
            'parent_id' => null,
            'order' => 'M00',
        ]);
        foreach ($mahasiswa_access as $child) {
            $setting->children()->create([
                'name' => $child[0],
                'url' => $child[1],
                'order' => (Navigation::where('parent_id',$setting->id)->count()+1<10 ? 'M0':'M').Navigation::where('parent_id',$setting->id)->count()+1,
            ]);
        }

        // general permission for DOSEN
        // menu for DOSEN
        $dosen_access = [
            ['ajuan pembimbing','selection/guiderespons'],
            ['penilaian ujian','exam/scores'],
        ];
        $permissions = [];
        foreach ($dosen_access as $access_value) {
            array_push($permissions,'access '.$access_value[1]);
        }

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission])->assignRole('dosen');
        }

        // MENU selection
        $setting = Navigation::create([
            'name' => 'DOSEN',
            'url' => 'dosen',
            'parent_id' => null,
            'order' => 'D00',
        ]);
        foreach ($mahasiswa_access as $child) {
            $setting->children()->create([
                'name' => $child[0],
                'url' => $child[1],
                'order' => (Navigation::where('parent_id',$setting->id)->count()+1<10 ? 'D0':'D').Navigation::where('parent_id',$setting->id)->count()+1,
            ]);
        }

        // general permission for DBS
        $dbs_access = [
            ['quota pembimbing','selection/guide/allocations'],
            ['kelompok pembimbing','selection/guide/groups'],
            ['verifikasi NUIR','selection/element/verifications'],
            ['komentar NUIR','selection/element/comments'],
            ['registrasi ujian','exam/registration/lists'],
            ['set penguji','exam/examiners'],
        ];
        $permissions = [];
        foreach ($dbs_access as $access_value) {
            array_push($permissions,'access '.$access_value[1]);
        }

        foreach ($permissions as $permission) {
            $permissionOne = Permission::create(['name' => $permission]);
            $permissionOne->assignRole('dbs');
        }

        // MENU selection
        $setting = Navigation::create([
            'name' => 'DBS',
            'url' => 'dbs',
            'parent_id' => null,
            'order' => 'C00',
        ]);
        foreach ($mahasiswa_access as $child) {
            $setting->children()->create([
                'name' => $child[0],
                'url' => $child[1],
                'order' => (Navigation::where('parent_id',$setting->id)->count()+1<10 ? 'C0':'C').Navigation::where('parent_id',$setting->id)->count()+1,
            ]);
        }

    }
}
