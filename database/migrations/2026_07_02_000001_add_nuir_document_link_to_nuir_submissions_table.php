<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nuir_submissions', function (Blueprint $table) {
            $table->text('nuir_document_link')->nullable()->after('impact');
        });
    }

    public function down(): void
    {
        Schema::table('nuir_submissions', function (Blueprint $table) {
            $table->dropColumn('nuir_document_link');
        });
    }
};
