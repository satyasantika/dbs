<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->date('invited_exam_date')->nullable()->after('corrected_at');
            $table->time('invited_exam_time')->nullable()->after('invited_exam_date');
            $table->string('invited_room')->nullable()->after('invited_exam_time');
        });

        DB::table('exam_registrations')
            ->whereNotNull('invited_at')
            ->update([
                'invited_exam_date' => DB::raw('exam_date'),
                'invited_exam_time' => DB::raw('exam_time'),
                'invited_room'      => DB::raw('room'),
            ]);
    }

    public function down(): void
    {
        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->dropColumn(['invited_exam_date', 'invited_exam_time', 'invited_room']);
        });
    }
};
