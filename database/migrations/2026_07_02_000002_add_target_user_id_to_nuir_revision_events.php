<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nuir_revision_events', function (Blueprint $table) {
            $table->foreignId('target_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('actor_id');
        });
    }

    public function down(): void
    {
        Schema::table('nuir_revision_events', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'target_user_id');
            $table->dropColumn('target_user_id');
        });
    }
};
