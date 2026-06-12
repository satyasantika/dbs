<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->dateTime('scoring_edit_unlocked_at')->nullable()->after('pass_approved');
        });
    }

    public function down(): void
    {
        Schema::table('exam_scores', function (Blueprint $table) {
            $table->dropColumn('scoring_edit_unlocked_at');
        });
    }
};
