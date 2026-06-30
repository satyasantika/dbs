<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_allocations', function (Blueprint $table) {
            $table->integer('guide1_filled')->default(0)->after('guide2_quota');
            $table->integer('guide2_filled')->default(0)->after('guide1_filled');
        });
    }

    public function down(): void
    {
        Schema::table('guide_allocations', function (Blueprint $table) {
            $table->dropColumn(['guide1_filled', 'guide2_filled']);
        });
    }
};
