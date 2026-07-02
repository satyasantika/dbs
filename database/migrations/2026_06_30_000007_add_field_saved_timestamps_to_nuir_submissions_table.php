<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nuir_submissions', function (Blueprint $table) {
            $table->timestamp('title_saved_at')->nullable()->after('impact');
            $table->timestamp('novelty_saved_at')->nullable()->after('title_saved_at');
            $table->timestamp('urgency_saved_at')->nullable()->after('novelty_saved_at');
            $table->timestamp('impact_saved_at')->nullable()->after('urgency_saved_at');
        });

        foreach (['title', 'novelty', 'urgency', 'impact'] as $field) {
            DB::table('nuir_submissions')
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->update([$field.'_saved_at' => DB::raw('updated_at')]);
        }
    }

    public function down(): void
    {
        Schema::table('nuir_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'title_saved_at',
                'novelty_saved_at',
                'urgency_saved_at',
                'impact_saved_at',
            ]);
        });
    }
};
