<?php

namespace App\Support;

use App\Models\ExamRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Paste "NPM <tab> link drive" dari spreadsheet untuk mengisi exam_file
 * (Link File Ujian) semua ujian pada satu tanggal sekaligus. Dipakai
 * bersama oleh preview (live, setiap kali textarea berubah) dan simpan,
 * supaya yang ditampilkan di preview persis yang akan disimpan.
 */
class ExamFileLinkPaste
{
    public const STATUS_NEW = 'new';

    public const STATUS_REPLACE = 'replace';

    public const STATUS_SAME = 'same';

    public const STATUS_NOT_FOUND = 'not_found';

    public const STATUS_INVALID_LINK = 'invalid_link';

    public const STATUS_DUPLICATE = 'duplicate';

    /**
     * @return list<array{npm: string, link: string}>
     */
    public static function parse(?string $text): array
    {
        $rows = [];

        foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
            if (trim($line) === '') {
                continue;
            }

            $cells = str_contains($line, "\t")
                ? explode("\t", $line)
                : preg_split('/[;,]\s*|\s+/', trim($line), 2);

            $npm = preg_replace('/\s+/', '', (string) ($cells[0] ?? ''));
            $link = trim((string) ($cells[1] ?? ''));

            // Baris judul kolom (mis. "NPM | Link") ikut ter-copy dari Excel.
            if (! preg_match('/\d/', $npm) && ! self::isValidLink($link)) {
                continue;
            }

            $rows[] = ['npm' => $npm, 'link' => $link];
        }

        return $rows;
    }

    /**
     * @param  Builder<ExamRegistration>  $registrations  ujian pada tanggal terpilih
     * @return list<array{npm: string, link: string, name: ?string, current: ?string, status: string, ids: list<int>}>
     */
    public static function preview(?string $text, Builder $registrations): array
    {
        $rows = self::parse($text);

        if ($rows === []) {
            return [];
        }

        /** @var Collection<string, Collection<int, ExamRegistration>> $byNpm */
        $byNpm = (clone $registrations)
            ->with('student:id,username,name')
            ->get()
            ->filter(fn (ExamRegistration $r) => filled($r->student?->username))
            ->groupBy(fn (ExamRegistration $r) => (string) $r->student->username);

        $seen = [];
        $result = [];

        foreach ($rows as $row) {
            $matches = $byNpm->get($row['npm'], collect());
            $first = $matches->first();

            $status = match (true) {
                isset($seen[$row['npm']]) => self::STATUS_DUPLICATE,
                $matches->isEmpty() => self::STATUS_NOT_FOUND,
                ! self::isValidLink($row['link']) => self::STATUS_INVALID_LINK,
                blank($first->exam_file) => self::STATUS_NEW,
                $first->exam_file === $row['link'] => self::STATUS_SAME,
                default => self::STATUS_REPLACE,
            };

            $seen[$row['npm']] = true;

            $result[] = [
                'npm' => $row['npm'],
                'link' => $row['link'],
                'name' => $first?->student?->name,
                'current' => $first?->exam_file,
                'status' => $status,
                'ids' => $matches->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ];
        }

        return $result;
    }

    /**
     * @param  Builder<ExamRegistration>  $registrations
     * @return array{saved: int, skipped: int}
     */
    public static function apply(?string $text, Builder $registrations): array
    {
        $saved = 0;
        $skipped = 0;

        foreach (self::preview($text, $registrations) as $row) {
            if (! in_array($row['status'], [self::STATUS_NEW, self::STATUS_REPLACE], true)) {
                $skipped++;

                continue;
            }

            ExamRegistration::query()
                ->whereKey($row['ids'])
                ->update(['exam_file' => $row['link']]);

            $saved++;
        }

        return ['saved' => $saved, 'skipped' => $skipped];
    }

    public static function isValidLink(string $link): bool
    {
        return filter_var($link, FILTER_VALIDATE_URL) !== false
            && preg_match('#^https?://#i', $link) === 1;
    }
}
