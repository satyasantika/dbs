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
            'exam registrations' => [
                '/setting/examregistrations',
                ['kode_ujian', 'room', 'exam_date', 'exam_time', 'mahasiswa', 'penguji_1', 'penguji_2', 'penguji_3', 'penguji_4', 'penguji_5'],
                'sempro',
            ],
            'guide examiners' => [
                '/setting/guideexaminers',
                ['npm', 'mahasiswa', 'penguji_1', 'penguji_2', 'penguji_3', 'penguji_4', 'penguji_5', 'proposal_date', 'seminar_date', 'thesis_date'],
                'mahasiswa',
            ],
            'information guides' => [
                '/information/guides',
                ['year_generation', 'mahasiswa', 'penguji_4', 'penguji_5', 'proposal_date', 'seminar_date', 'thesis_date', 'npm'],
                '2020',
            ],
            'selection guides' => [
                '/setting/selectionguides',
                ['mahasiswa', 'group_id', 'pasangan', 'pembimbing', 'dosen', 'status', 'keterangan', 'updated_at'],
                'disetujui',
            ],
            'selection stages' => [
                '/setting/selectionstages',
                ['tahap', 'final', 'npm', 'mahasiswa', 'pembimbing_1', 'pembimbing_2', 'grup1_id', 'grup2_id', 'updated_at'],
                '1',
            ],
            'guide allocations' => [
                '/setting/selectionguideallocations',
                ['active', 'year', 'dosen', 'guide1_quota', 'guide2_quota', 'examiner_quota'],
                'dosen',
            ],
            'guide groups' => [
                '/setting/selectionguidegroups',
                ['active', 'dosen', 'group', 'guide1_quota', 'guide1_filled', 'guide2_quota', 'guide2_filled'],
                'group',
            ],
            'scoring' => [
                '/examination/scoring',
                ['mahasiswa', 'waktu', 'revision_note'],
                'semhas',
            ],
            'information pass' => [
                '/information/pass',
                ['npm', 'mahasiswa', 'thesis_date', 'status'],
                'lulus',
            ],
            'information pass recap' => [
                '/information/recap-list/20/Mahasiswa%20Lulus',
                ['npm', 'mahasiswa', 'penguji_4', 'penguji_5', 'proposal_date', 'seminar_date', 'thesis_date', 'masa_studi'],
                '20',
            ],
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
