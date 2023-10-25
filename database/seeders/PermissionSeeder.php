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

        Permission::create(['name' => 'read dashboard kajur'])->assignRole('kajur');
        Permission::create(['name' => 'read dashboard dbs'])->assignRole('dbs');
        Permission::create(['name' => 'read dashboard dosen'])->assignRole('dosen');
        Permission::create(['name' => 'read dashboard mahasiswa'])->assignRole('mahasiswa');

        $actions = ['read', 'create', 'update', 'delete'];

        // general permission for admin
        $general_permissions = [
            'roles',
            'users',
            'permissions',
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

        $admin_access = [
            ['role','setting/roles'],
            ['permission','setting/permissions'],
            ['user','setting/users'],
            ['navigation','setting/navigations'],
            ['proposal','setting/guideproposals'],
        ];

        // Role Admin
        $permissions = [];
        foreach ($admin_access as $access_value) {
            // foreach ($actions as $action_value) {
                array_push($permissions,'read '.$access_value[1]);
            // }
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

        // general permission for Mahasiswa
        $mahasiswa_access = [
            'proposal/stages',
            'proposal/steps',
            'proposal/guides',
        ];
        $permissions = [];
        foreach ($mahasiswa_access as $access_value) {
            // foreach ($actions as $action_value) {
                array_push($permissions,'read '.$access_value);
            // }
        }

        foreach ($permissions as $permission) {
            $permissionOne = Permission::create(['name' => $permission]);
            $permissionOne->assignRole('mahasiswa');
            $permissionOne->assignRole('dosen');
        }
        // dosen permission
        $dosen_access = [
            'proposal/comments',
        ];
        $permissions = [];
        foreach ($dosen_access as $access_value) {
            // foreach ($actions as $action_value) {
                array_push($permissions,'read '.$access_value);
                // array_push($permissions,$action_value.' '.$access_value);
            // }
        }

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission])->assignRole('dosen');
        }

    }
}
