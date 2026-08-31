<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * guide_examiners.year_generation dan exam_registrations.exam_date/pass_exam
 * dipakai sebagai filter utama di App\Filament\Informasi\Pages\Beranda
 * (rekap kelulusan publik) tanpa index sama sekali sejak tabel dibuat —
 * setiap query jadi full table scan dan membebani halaman beranda publik
 * (tanpa auth, tanpa cache) yang timeout di production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_examiners', function (Blueprint $table) {
            $table->index(['year_generation', 'user_id'], 'guide_examiners_year_generation_user_id_index');
        });

        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->index(
                ['user_id', 'exam_type_id', 'pass_exam', 'exam_date'],
                'exam_registrations_user_type_pass_date_index'
            );
            $table->index('exam_date', 'exam_registrations_exam_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('guide_examiners', function (Blueprint $table) {
            $table->dropIndex('guide_examiners_year_generation_user_id_index');
        });

        Schema::table('exam_registrations', function (Blueprint $table) {
            $table->dropIndex('exam_registrations_user_type_pass_date_index');
            $table->dropIndex('exam_registrations_exam_date_index');
        });
    }
};
