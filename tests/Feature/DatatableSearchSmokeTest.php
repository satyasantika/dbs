<?php

namespace Tests\Feature;

use Tests\TestCase;

class DatatableSearchSmokeTest extends TestCase
{
    /**
     * @dataProvider datatableEndpointsProvider
     */
    public function test_datatable_global_search_requests_return_successful_json(string $url, array $columns, string $search): void
    {
        $this->withoutMiddleware();

        $response = $this->getJson($url . '?' . http_build_query($this->datatableParams($columns, $search)), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
    }

    public static function datatableEndpointsProvider(): array
    {
        return [
            // Route-route berikut masih aktif (belum/tidak digantikan Filament):
            'selection guides' => [
                '/setting/selectionguides',
                ['mahasiswa', 'group_id', 'pasangan', 'pembimbing', 'dosen', 'status', 'keterangan', 'updated_at'],
                'disetujui',
            ],
            'guide groups' => [
                '/setting/selectionguidegroups',
                ['active', 'dosen', 'group', 'guide1_quota', 'guide1_filled', 'guide2_quota', 'guide2_filled'],
                'group',
            ],
            'information pass recap' => [
                '/information/recap-list/20/Mahasiswa%20Lulus',
                ['npm', 'mahasiswa', 'penguji_4', 'penguji_5', 'proposal_date', 'seminar_date', 'thesis_date', 'masa_studi'],
                '20',
            ],
            // Route yang sudah digantikan Filament (dihapus dari sini):
            // - /setting/examregistrations   → ExamRegistrationResource (/admin/exam-registrations)
            // - /setting/guideexaminers      → GuideExaminerResource    (/admin/guide-examiners)
            // - /setting/selectionstages     → SelectionStageResource   (/admin/selection-stages)
            // - /setting/selectionguideallocations → GuideAllocationResource (/admin/guide-allocations)
            // - /examination/scoring         → UnscoredScoring page      (/home/examination/scoring)
        ];
    }

    private function datatableParams(array $columns, string $search): array
    {
        $payload = [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => [
                'value' => $search,
                'regex' => 'false',
            ],
            'order' => [
                [
                    'column' => 0,
                    'dir' => 'asc',
                ],
            ],
        ];

        $payload['columns'] = array_map(function (string $name) {
            return [
                'data' => $name,
                'name' => $name,
                'searchable' => 'true',
                'orderable' => 'true',
                'search' => [
                    'value' => '',
                    'regex' => 'false',
                ],
            ];
        }, $columns);

        return $payload;
    }
}
