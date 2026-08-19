<?php

namespace App\Support;

use App\Models\ExamType;

class SintesysExamTypeMap
{
    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            1 => 'Ujian Proposal',
            2 => 'Ujian Hasil Penelitian',
            3 => 'Ujian Sidang Akhir',
            4 => 'Ujian Seminar Usulan Penelitian',
            5 => 'Ujian Kolokium',
            6 => 'Ujian Sidang Akhir Tesis',
            7 => 'Ujian Kualifikasi',
            8 => 'Ujian Seminar Ujian Hasil',
            9 => 'Ujian Terbuka',
            10 => 'Ujian Tertutup',
        ];
    }

    public static function localExamTypeId(?int $sintesysId, ?string $jenisUjian = null): ?int
    {
        $code = static::localExamTypeCode($sintesysId, $jenisUjian);

        if (! $code) {
            return null;
        }

        $id = ExamType::query()->where('code', $code)->value('id');

        return $id ? (int) $id : null;
    }

    public static function localExamTypeCode(?int $sintesysId, ?string $jenisUjian = null): ?string
    {
        if ($sintesysId) {
            return match ($sintesysId) {
                1, 4 => 'sempro',
                2, 8 => 'semhas',
                3, 6, 9, 10 => 'skripsi',
                default => null,
            };
        }

        $name = mb_strtolower(trim((string) $jenisUjian));

        if ($name === '') {
            return null;
        }

        return match (true) {
            str_contains($name, 'kolokium'), str_contains($name, 'kualifikasi') => null,
            str_contains($name, 'hasil') => 'semhas',
            str_contains($name, 'proposal'), str_contains($name, 'usulan') => 'sempro',
            str_contains($name, 'sidang'), str_contains($name, 'terbuka'), str_contains($name, 'tertutup'), str_contains($name, 'tesis'), str_contains($name, 'skripsi') => 'skripsi',
            default => null,
        };
    }

    public static function label(?int $sintesysId, ?string $jenisUjian = null): string
    {
        if ($sintesysId && isset(static::options()[$sintesysId])) {
            return static::options()[$sintesysId];
        }

        $name = trim((string) $jenisUjian);

        return $name !== '' ? $name : 'Jenis ujian tidak diketahui';
    }

    public static function badgeColor(?int $sintesysId, ?string $jenisUjian = null): string
    {
        return match (static::localExamTypeCode($sintesysId, $jenisUjian)) {
            'sempro' => 'proposal',
            'semhas' => 'hasil',
            'skripsi' => 'sidang',
            default => 'gray',
        };
    }
}
