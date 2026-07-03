<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nuir_settings', function (Blueprint $table) {
            $table->date('stage_starts_at')->nullable()->after('stage');
        });
    }

    public function down(): void
    {
        Schema::table('nuir_settings', function (Blueprint $table) {
            $table->dropColumn('stage_starts_at');
        });
    }
};
