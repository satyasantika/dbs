<?php

use App\Models\Navigation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->where('name', 'access exam/scores')
            ->update(['name' => 'access examination/scoring']);

        Navigation::query()
            ->where('url', 'exam/scores')
            ->update(['url' => 'examination/scoring']);

        $dosenParent = Navigation::query()
            ->whereNull('parent_id')
            ->where('url', 'dosen')
            ->first();

        if (!$dosenParent) {
            return;
        }

        Navigation::query()->updateOrCreate(
            [
                'parent_id' => $dosenParent->id,
                'name' => 'penilaian ujian',
            ],
            [
                'url' => 'examination/scoring',
                'order' => 'D01',
            ]
        );

        $permission = Permission::query()->firstOrCreate(['name' => 'access examination/scoring']);
        Role::findByName('dosen')?->givePermissionTo($permission);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'access examination/scoring')
            ->update(['name' => 'access exam/scores']);

        Navigation::query()
            ->where('url', 'examination/scoring')
            ->update(['url' => 'exam/scores']);
    }
};
