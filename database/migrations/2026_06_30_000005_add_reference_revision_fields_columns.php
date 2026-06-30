<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nuir_references', function (Blueprint $table) {
            $table->json('ref_revision_fields')->nullable()->after('ref_note');
        });

        Schema::table('nuir_revision_events', function (Blueprint $table) {
            $table->json('revision_fields')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('nuir_references', function (Blueprint $table) {
            $table->dropColumn('ref_revision_fields');
        });

        Schema::table('nuir_revision_events', function (Blueprint $table) {
            $table->dropColumn('revision_fields');
        });
    }
};
