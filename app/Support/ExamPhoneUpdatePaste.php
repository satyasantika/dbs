<?php

namespace App\Support;

use App\Models\ExamRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Paste "NPM <tab> nomor HP" dari spreadsheet untuk memperbarui nomor HP
 * (users.phone) mahasiswa yang ujian pada satu tanggal sekaligus — sama
 * pola dengan ExamFileLinkPaste (Link Drive File Ujian), hanya kolom
 * kedua & target kolomnya beda (users.phone, bukan exam_registrations.
 * exam_file). Dipakai bersama oleh preview (live, setiap kali textarea
 * berubah) dan simpan, supaya yang ditampilkan di preview persis yang
 * akan disimpan.
 */
class ExamPhoneUpdatePaste
{
    public const STATUS_NEW = 'new';

    public const STATUS_REPLACE = 'replace';

    public const STATUS_SAME = 'same';

    public const STATUS_NOT_FOUND = 'not_found';

    public const STATUS_INVALID_PHONE = 'invalid_phone';

    public const STATUS_DUPLICATE = 'duplicate';

    /**
     * @return list<array{npm: string, phone: string}>
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
            $phone = UserPhone::normalize((string) ($cells[1] ?? ''));

            // Baris judul kolom (mis. "NPM | HP") ikut ter-copy dari Excel.
            if (! preg_match('/\d/', $npm) && $phone === '') {
                continue;
            }

            $rows[] = ['npm' => $npm, 'phone' => $phone];
        }

        return $rows;
    }

    /**
     * @param  Builder<ExamRegistration>  $registrations  ujian pada tanggal terpilih
     * @return list<array{npm: string, phone: string, name: ?string, current: ?string, status: string, user_id: ?int}>
     */
    public static function preview(?string $text, Builder $registrations): array
    {
        $rows = self::parse($text);

        if ($rows === []) {
            return [];
        }

        /** @var Collection<string, User> $byNpm */
        $byNpm = (clone $registrations)
            ->with('student:id,username,name,phone')
            ->get()
            ->pluck('student')
            ->filter(fn (?User $student) => filled($student?->username))
            ->unique('id')
            ->keyBy(fn (User $student) => (string) $student->username);

        $seen = [];
        $result = [];

        foreach ($rows as $row) {
            $student = $byNpm->get($row['npm']);

            $status = match (true) {
                isset($seen[$row['npm']]) => self::STATUS_DUPLICATE,
                ! $student => self::STATUS_NOT_FOUND,
                ! UserPhone::isValid($row['phone']) => self::STATUS_INVALID_PHONE,
                blank($student->phone) => self::STATUS_NEW,
                $student->phone === $row['phone'] => self::STATUS_SAME,
                default => self::STATUS_REPLACE,
            };

            $seen[$row['npm']] = true;

            $result[] = [
                'npm' => $row['npm'],
                'phone' => $row['phone'],
                'name' => $student?->name,
                'current' => $student?->phone,
                'status' => $status,
                'user_id' => $student?->id,
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

            User::whereKey($row['user_id'])->update(['phone' => $row['phone']]);

            $saved++;
        }

        return ['saved' => $saved, 'skipped' => $skipped];
    }
}
