<?php

namespace Database\Seeders;

use App\Models\NuirSetting;
use Illuminate\Database\Seeder;

class NuirSettingSeeder extends Seeder
{
    public const YEAR_2026 = '2026';

    public function run(): void
    {
        NuirSetting::updateOrCreate(
            ['year_generation' => self::YEAR_2026],
            [
                'stage' => 1,
                'active' => true,
                'deadline' => now()->setYear(2026)->endOfYear()->toDateString(),
                'min_references_approved' => 10,
                'max_references' => 10,
                'min_words_title' => 3,
                'max_words_title' => 20,
                'min_words_novelty' => 12,
                'max_words_novelty' => 300,
                'min_words_urgency' => 12,
                'max_words_urgency' => 300,
                'min_words_impact' => 12,
                'max_words_impact' => 300,
                'max_chars_novelty' => null,
                'max_chars_urgency' => null,
                'max_chars_impact' => null,
            ],
        );

        $this->command?->info('NuirSettingSeeder: konfigurasi NUIR angkatan '.self::YEAR_2026.' siap.');
    }
}
