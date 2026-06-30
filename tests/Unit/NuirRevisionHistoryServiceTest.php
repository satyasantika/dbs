<?php

namespace Tests\Unit;

use App\Models\NuirRevisionEvent;
use App\Models\NuirSubmission;
use App\Models\User;
use App\Services\NuirRevisionHistoryService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NuirRevisionHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_field_history_menggabungkan_snapshot_dan_permintaan_revisi(): void
    {
        $this->seed(PermissionSeeder::class);

        $mahasiswa = User::factory()->create()->assignRole('mahasiswa');
        $dosen = User::factory()->create()->assignRole('dosen');

        $v1 = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $mahasiswa->id,
            'version' => 1,
            'title' => 'Judul versi 1',
            'novelty' => 'Novelty versi 1',
            'status' => 'revision',
            'dbs_note' => 'Perbaiki judul.',
        ]);

        $v2 = NuirSubmission::factory()->submitted()->withNUI()->create([
            'user_id' => $mahasiswa->id,
            'parent_submission_id' => $v1->id,
            'version' => 2,
            'title' => 'Judul versi 2 revisi',
            'novelty' => 'Novelty versi 2 revisi',
        ]);

        NuirRevisionEvent::create([
            'nuir_submission_id' => $v2->id,
            'submission_version' => 2,
            'actor_id' => $dosen->id,
            'actor_role' => NuirRevisionEvent::ROLE_GUIDE1,
            'event_type' => NuirRevisionEvent::TYPE_NUI_REVISION,
            'subject' => 'novelty',
            'note' => 'Perjelas novelty.',
            'recorded_at' => now()->subDay(),
        ]);

        $service = app(NuirRevisionHistoryService::class);

        $titleHistory = $service->contentFieldHistory($v2, 'title');
        $this->assertTrue($titleHistory->contains(fn (array $item) => $item['content'] === 'Judul versi 1'));

        $noveltyHistory = $service->contentFieldHistory($v2, 'novelty');
        $this->assertTrue($noveltyHistory->contains(fn (array $item) => $item['content'] === 'Novelty versi 1'));
        $this->assertTrue($noveltyHistory->contains(fn (array $item) => $item['note'] === 'Perjelas novelty.'));
    }
}
