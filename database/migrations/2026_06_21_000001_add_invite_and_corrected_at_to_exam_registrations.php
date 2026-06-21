<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->dateTime('invited_at')->nullable()->after('sent_at');
            $table->dateTime('corrected_at')->nullable()->after('invited_at');
        });
    }

    public function down(): void
    {
        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->dropColumn(['invited_at', 'corrected_at']);
        });
    }
};
