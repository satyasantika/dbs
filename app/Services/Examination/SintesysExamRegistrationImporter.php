<?php

namespace App\Services\Examination;

use App\Models\ExamRegistration;
use App\Models\ExamScore;
use App\Models\ExamType;
use App\Models\User;
use App\Services\Sintesys\SintesysException;
use App\Services\Sintesys\SintesysTugasAkhirClient;
use App\Support\SintesysExamTypeMap;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class SintesysExamRegistrationImporter
{
    public const MAX_RANGE_DAYS = 183;

    /**
     * Sinkronisasi "hapus karena dipindah" hanya berlaku untuk ujian dengan
     * exam_date pada atau setelah tanggal ini. Data lebih lama dari cutoff
     * tidak pernah dihapus otomatis meski tidak lagi muncul di Sintesys,
     * karena riwayat sebelum tanggal ini tidak terjamin konsisten dengan Sintesys.
     */
    public const PRUNE_CUTOFF_DATE = '2025-08-01';

    public function __construct(
        protected SintesysTugasAkhirClient $client,
        protected ExamRegistrationExaminerSync $examinerSync,
        protected ExamScoreUpdater $scoreUpdater,
    ) {
    }

    /**
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     total: int,
     *     baru: int,
     *     duplikat: int,
     *     error: int
     * }
     */
    public function preview(string $tanggalMulai, string $tanggalSelesai, ?int $jenisUjianId = null): array
    {
        $this->assertDateRange($tanggalMulai, $tanggalSelesai);

        $rows = [];
        $baru = 0;
        $duplikat = 0;
        $error = 0;

        foreach ($this->client->fetch($tanggalMulai, $tanggalSelesai, $jenisUjianId) as $item) {
            $classified = $this->classifyRow(is_array($item) ? $item : []);
            $rows[] = $classified;

            match ($classified['status']) {
                'baru' => $baru++,
                'duplikat' => $duplikat++,
                default => $error++,
            };
        }

        return [
            'rows' => $rows,
            'total' => count($rows),
            'baru' => $baru,
            'duplikat' => $duplikat,
            'error' => $error,
        ];
    }

    /**
     * @return array{created: int, updated: int, skipped: int, errors: array<int, string>, removed: int, removed_details: array<int, string>}
     */
    public function persist(string $tanggalMulai, string $tanggalSelesai, ?int $jenisUjianId = null): array
    {
        $preview = $this->preview($tanggalMulai, $tanggalSelesai, $jenisUjianId);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($preview['rows'] as $row) {
            if ($row['status'] === 'error') {
                $skipped++;
                $errors[] = $row['message'] ?? 'Baris dilewati';

                continue;
            }

            try {
                $wasUpdate = DB::transaction(fn (): bool => $this->persistRow($row));

                if ($wasUpdate) {
                    $updated++;
                } else {
                    $created++;
                }
            } catch (Throwable $e) {
                $skipped++;
                $errors[] = ($row['nama'] ?? $row['nim'] ?? 'Baris').': '.$e->getMessage();
            }
        }

        [$removed, $removedDetails] = $this->pruneMovedRegistrations(
            $tanggalMulai,
            $tanggalSelesai,
            $jenisUjianId,
            $preview['rows'],
        );

        return compact('created', 'updated', 'skipped', 'errors', 'removed', 'removedDetails');
    }

    /**
     * Hapus pendaftaran ujian lokal yang berada di dalam rentang tanggal yang
     * disinkronkan tetapi TIDAK lagi muncul di respons Sintesys untuk rentang
     * tersebut — artinya jadwal ujian tersebut sudah dipindah (atau dibatalkan)
     * di Sintesys. Ini hanya berlaku untuk exam_date >= self::PRUNE_CUTOFF_DATE
     * (Agustus 2025 ke atas); data lebih lama tidak pernah disentuh.
     *
     * Aman untuk dihapus karena exam_scores punya FK cascadeOnDelete ke
     * exam_registrations (lihat migrasi 2026_07_04_000001).
     *
     * @param  array<int, array<string, mixed>>  $previewRows  Baris hasil preview() rentang yang sama (sumber kebenaran Sintesys saat ini)
     * @return array{0: int, 1: array<int, string>}
     */
    protected function pruneMovedRegistrations(
        string $tanggalMulai,
        string $tanggalSelesai,
        ?int $jenisUjianId,
        array $previewRows,
    ): array {
        $cutoff = Carbon::parse(self::PRUNE_CUTOFF_DATE)->startOfDay();
        $rangeStart = Carbon::parse($tanggalMulai)->startOfDay();
        $rangeEnd = Carbon::parse($tanggalSelesai)->startOfDay();

        // Cutoff belum tercapai sama sekali oleh rentang sync ini — tidak ada yang diprune.
        if ($rangeEnd->lt($cutoff)) {
            return [0, []];
        }

        // Prune hanya berlaku mulai cutoff, meski rentang sync mundur ke sebelum itu.
        $pruneFrom = $rangeStart->lt($cutoff) ? $cutoff : $rangeStart;

        // Kumpulkan pasangan (user_id, exam_type_id, exam_date) yang MASIH ada di Sintesys
        // untuk rentang ini, dari baris preview yang valid (baru/duplikat).
        $stillPresent = [];

        foreach ($previewRows as $row) {
            if (! in_array($row['status'] ?? null, ['baru', 'duplikat'], true)) {
                continue;
            }

            $nim = (string) ($row['nim'] ?? '');
            $examTypeId = $row['exam_type_id'] ?? null;
            $examDate = $row['exam_date'] ?? null;

            if ($nim === '' || ! $examTypeId || ! $examDate) {
                continue;
            }

            $stillPresent[$nim.'|'.$examTypeId.'|'.$examDate] = true;
        }

        $query = ExamRegistration::query()
            ->join('users', 'users.id', '=', 'exam_registrations.user_id')
            ->whereBetween('exam_registrations.exam_date', [
                $pruneFrom->toDateString(),
                $rangeEnd->toDateString(),
            ])
            ->select(['exam_registrations.*', 'users.username as student_username']);

        if ($jenisUjianId) {
            $localTypeId = SintesysExamTypeMap::localExamTypeId($jenisUjianId);

            // Jenis ujian Sintesys ini tidak terpetakan ke sistem lokal —
            // tidak ada dasar aman untuk memilih baris yang akan diprune.
            if (! $localTypeId) {
                return [0, []];
            }

            $query->where('exam_registrations.exam_type_id', $localTypeId);
        }

        $removed = 0;
        $removedDetails = [];

        foreach ($query->get() as $registration) {
            $key = $registration->student_username.'|'.$registration->exam_type_id.'|'.$registration->exam_date->format('Y-m-d');

            if (isset($stillPresent[$key])) {
                continue;
            }

            // Jangan hapus jika penilaian sudah pernah dikirim ke mahasiswa —
            // hindari kehilangan data yang sudah difinalisasi/dibagikan.
            if ($registration->sent_at !== null) {
                continue;
            }

            DB::transaction(function () use ($registration): void {
                $registration->delete();
            });

            Log::info('Sintesys sync: exam_registration dihapus karena dipindah/tidak ditemukan di Sintesys', [
                'exam_registration_id' => $registration->id,
                'user_id' => $registration->user_id,
                'exam_type_id' => $registration->exam_type_id,
                'exam_date' => $registration->exam_date->format('Y-m-d'),
            ]);

            $removed++;
            $removedDetails[] = strtoupper($registration->student->name ?? $registration->student_username)
                .' — jadwal '.$registration->exam_date->format('d-m-Y').' tidak lagi ada di Sintesys (dihapus)';
        }

        return [$removed, $removedDetails];
    }

    public function assertDateRange(string $tanggalMulai, string $tanggalSelesai): void
    {
        try {
            $start = Carbon::parse($tanggalMulai)->startOfDay();
            $end = Carbon::parse($tanggalSelesai)->startOfDay();
        } catch (Throwable) {
            throw new SintesysException('Format tanggal tidak valid.');
        }

        if ($end->lt($start)) {
            throw new SintesysException('Tanggal selesai tidak boleh sebelum tanggal mulai.');
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            throw new SintesysException('Rentang tanggal maksimal '.self::MAX_RANGE_DAYS.' hari.');
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function classifyRow(array $item): array
    {
        $nim = trim((string) ($item['nim'] ?? ''));
        $nama = strtoupper(trim((string) ($item['nama'] ?? '')));
        $jenisUjian = trim((string) ($item['jenis_ujian'] ?? ''));
        $sintesysTypeId = isset($item['jenis_ujian_id']) ? (int) $item['jenis_ujian_id'] : null;
        $examTypeId = SintesysExamTypeMap::localExamTypeId($sintesysTypeId, $jenisUjian);
        $jenisLabel = SintesysExamTypeMap::label($sintesysTypeId, $jenisUjian);

        $base = [
            'nim' => $nim,
            'nama' => $nama !== '' ? $nama : $nim,
            'jenis_ujian' => $jenisLabel,
            'jenis_ujian_color' => SintesysExamTypeMap::badgeColor($sintesysTypeId, $jenisUjian),
            'exam_date' => null,
            'exam_date_label' => null,
            'exam_time' => null,
            'penguji' => $this->pengujiPreviewNames($item['penguji'] ?? []),
            'status' => 'error',
            'message' => null,
            'warnings' => [],
            'payload' => $item,
        ];

        if ($nim === '') {
            return array_merge($base, ['message' => 'NIM kosong']);
        }

        if (! $examTypeId || ! ExamType::query()->whereKey($examTypeId)->exists()) {
            return array_merge($base, [
                'message' => "Jenis ujian '{$jenisLabel}' tidak dipetakan ke sistem",
            ]);
        }

        try {
            $examAt = Carbon::parse($item['tanggal_ujian'] ?? null);
            $examDate = $examAt->format('Y-m-d');
            $examTime = $examAt->format('H:i:s');
        } catch (Throwable) {
            return array_merge($base, [
                'message' => 'Tanggal ujian tidak valid: '.($item['tanggal_ujian'] ?? ''),
            ]);
        }

        $base['exam_date'] = $examDate;
        $base['exam_time'] = $examTime;
        $base['exam_date_label'] = $examAt->locale('id')->isoFormat('dddd, D MMMM Y[ · ]HH.mm');

        [$slots, $warnings] = $this->resolveExaminerSlots($item['penguji'] ?? []);
        $base['warnings'] = $warnings;
        $base['slots'] = $slots;
        $base['exam_type_id'] = $examTypeId;

        $student = User::query()->where('username', $nim)->first();

        if (! $student) {
            return array_merge($base, [
                'status' => 'baru',
                'message' => ($nama !== '' ? $nama : $nim).' — pendaftaran baru (akun mahasiswa akan dibuat)',
            ]);
        }

        $existing = ExamRegistration::query()
            ->where('user_id', $student->id)
            ->where('exam_type_id', $examTypeId)
            ->whereDate('exam_date', $examDate)
            ->orderBy('registration_order')
            ->first();

        if ($existing) {
            return array_merge($base, [
                'status' => 'duplikat',
                'registration_id' => $existing->id,
                'message' => strtoupper($student->name).' — sudah terdaftar, akan diperbarui',
            ]);
        }

        $nextOrder = ExamRegistration::query()
            ->where('user_id', $student->id)
            ->where('exam_type_id', $examTypeId)
            ->count() + 1;

        if ($nextOrder > 3) {
            return array_merge($base, [
                'message' => strtoupper($student->name)." — sudah 3× {$jenisLabel}",
            ]);
        }

        return array_merge($base, [
            'status' => 'baru',
            'message' => strtoupper($student->name).' — pendaftaran baru',
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function persistRow(array $row): bool
    {
        $item = $row['payload'] ?? [];
        $nim = (string) $row['nim'];
        $examTypeId = (int) $row['exam_type_id'];
        $examDate = (string) $row['exam_date'];
        $examTime = (string) ($row['exam_time'] ?? '00:00:00');
        $slots = $row['slots'] ?? [1 => null, 2 => null, 3 => null, 4 => null, 5 => null];

        $student = $this->resolveStudent($nim, (string) ($item['nama'] ?? $row['nama'] ?? ''));

        $title = trim((string) ($item['judul_unformated'] ?? ''));

        if ($title === '' && filled($item['judul_formated'] ?? null)) {
            $title = trim(html_entity_decode(strip_tags((string) $item['judul_formated'])));
        }

        $attributes = [
            'exam_date' => $examDate,
            'exam_time' => $examTime,
            'title' => $title !== '' ? $title : null,
            'room' => filled($item['tempat_ujian'] ?? null) ? (string) $item['tempat_ujian'] : null,
            'online_link' => filled($item['link_ujian'] ?? null) ? (string) $item['link_ujian'] : null,
            'examiner1_id' => $slots[1] ?? null,
            'examiner2_id' => $slots[2] ?? null,
            'examiner3_id' => $slots[3] ?? null,
            'guide1_id' => $slots[4] ?? null,
            'guide2_id' => $slots[5] ?? null,
        ];

        $existing = ExamRegistration::query()
            ->where('user_id', $student->id)
            ->where('exam_type_id', $examTypeId)
            ->whereDate('exam_date', $examDate)
            ->orderBy('registration_order')
            ->first();

        $isUpdate = (bool) $existing;
        $examiner1Id = $attributes['examiner1_id'] ?? null;

        if ($existing) {
            if (! filled($existing->chief_id) && filled($examiner1Id)) {
                $attributes['chief_id'] = $examiner1Id;
            }

            $existing->fill($attributes)->save();
            $registration = $existing->fresh();
        } else {
            $order = ExamRegistration::query()
                ->where('user_id', $student->id)
                ->where('exam_type_id', $examTypeId)
                ->count() + 1;

            if ($order > 3) {
                throw new SintesysException(strtoupper($student->name).' sudah 3× ujian jenis ini.');
            }

            if (filled($examiner1Id)) {
                $attributes['chief_id'] = $examiner1Id;
            }

            $registration = ExamRegistration::create(array_merge($attributes, [
                'user_id' => $student->id,
                'exam_type_id' => $examTypeId,
                'registration_order' => $order,
            ]));
        }

        $this->examinerSync->syncFromRegistration($registration->fresh());
        $this->applyPengujiScores($registration->fresh(), is_array($item['penguji'] ?? null) ? $item['penguji'] : []);
        $this->grantPermissionIfExists($student, 'join exam');

        return $isUpdate;
    }

    /**
     * @param  mixed  $penguji
     * @return array<int, string>
     */
    protected function pengujiPreviewNames(mixed $penguji): array
    {
        $names = [];

        if (! is_array($penguji)) {
            return $names;
        }

        foreach ($penguji as $examiner) {
            if (! is_array($examiner)) {
                continue;
            }

            $order = (int) ($examiner['urutan'] ?? 0);

            if ($order < 1 || $order > 5) {
                continue;
            }

            $nama = trim((string) ($examiner['nama'] ?? ''));
            $nidn = trim((string) ($examiner['nidn'] ?? ''));

            $names[$order] = $nama !== '' ? $nama : ($nidn !== '' ? $nidn : '—');
        }

        ksort($names);

        return $names;
    }

    /**
     * @param  mixed  $penguji
     * @return array{0: array<int, int|null>, 1: array<int, string>}
     */
    protected function resolveExaminerSlots(mixed $penguji): array
    {
        $slots = [1 => null, 2 => null, 3 => null, 4 => null, 5 => null];
        $warnings = [];

        if (! is_array($penguji)) {
            return [$slots, $warnings];
        }

        foreach ($penguji as $examiner) {
            if (! is_array($examiner)) {
                continue;
            }

            $order = (int) ($examiner['urutan'] ?? 0);

            if ($order < 1 || $order > 5) {
                continue;
            }

            $nidn = trim((string) ($examiner['nidn'] ?? ''));
            $nama = trim((string) ($examiner['nama'] ?? ''));

            $user = $nidn !== ''
                ? User::query()->where('username', $nidn)->first()
                : null;

            if (! $user && $nama !== '') {
                $user = User::role('dosen')->where('name', $nama)->first();
            }

            if (! $user) {
                $warnings[] = 'Penguji '.($nama !== '' ? $nama : $nidn).($nidn !== '' ? " (NIDN {$nidn})" : '').' tidak ditemukan';

                continue;
            }

            $slots[$order] = (int) $user->id;
        }

        return [$slots, $warnings];
    }

    protected function resolveStudent(string $nim, string $nama): User
    {
        if (! Role::where('name', 'mahasiswa')->exists()) {
            throw new SintesysException("Role 'mahasiswa' belum ada di sistem.");
        }

        $student = User::query()->where('username', $nim)->first();

        if ($student) {
            if (! $student->hasRole('mahasiswa')) {
                $student->assignRole('mahasiswa');
                $this->grantPermissionIfExists($student, 'active');
            }

            if ($nama !== '' && $student->name !== strtoupper($nama)) {
                $student->update(['name' => strtoupper($nama)]);
            }

            return $student->fresh();
        }

        if (trim($nama) === '') {
            throw new SintesysException("NPM {$nim} tidak ditemukan dan nama kosong.");
        }

        $email = $nim.'@student.unsil.ac.id';

        if (User::query()->where('email', $email)->exists()) {
            throw new SintesysException("Email {$email} sudah dipakai akun lain.");
        }

        try {
            $student = User::create([
                'username' => $nim,
                'name' => strtoupper($nama),
                'email' => $email,
                'password' => bcrypt($nim),
            ]);
            $student->assignRole('mahasiswa');
            $this->grantPermissionIfExists($student, 'active');
        } catch (QueryException $e) {
            throw new SintesysException('Gagal daftarkan mahasiswa NPM '.$nim.': '.$e->getMessage());
        }

        return $student;
    }

    /**
     * @param  array<int, mixed>  $penguji
     */
    protected function applyPengujiScores(ExamRegistration $registration, array $penguji): void
    {
        $registration->load('examScores');

        foreach ($penguji as $examiner) {
            if (! is_array($examiner)) {
                continue;
            }

            $order = (int) ($examiner['urutan'] ?? 0);

            if ($order < 1 || $order > 5) {
                continue;
            }

            $score = $registration->examScores->firstWhere('examiner_order', $order);

            if (! $score) {
                continue;
            }

            if (! isset($examiner['nilai']) || $examiner['nilai'] === null || $examiner['nilai'] === '') {
                continue;
            }

            $this->scoreUpdater->allowSintesysWrite()->applySintesysGrade($score, [
                'nilai' => $examiner['nilai'],
                'revision' => $this->mapRevision($examiner['status_revisi'] ?? null),
                'revision_note' => filled($examiner['catatan'] ?? null) ? (string) $examiner['catatan'] : null,
                'pass_approved' => $this->mapPassApproved($examiner['status_kelulusan'] ?? null),
            ]);
        }
    }

    protected function mapRevision(mixed $statusRevisi): int
    {
        $value = mb_strtolower(trim((string) $statusRevisi));

        return match (true) {
            $value === 'belum revisi' => 1,
            $value === 'sudah revisi' => 0,
            default => 0,
        };
    }

    protected function mapPassApproved(mixed $statusKelulusan): int
    {
        return mb_strtolower(trim((string) $statusKelulusan)) === 'lulus' ? 1 : 0;
    }

    protected function grantPermissionIfExists(User $user, string $permissionName): void
    {
        if (! Permission::where('name', $permissionName)->exists()) {
            return;
        }

        if (! $user->hasPermissionTo($permissionName)) {
            $user->givePermissionTo($permissionName);
        }
    }
}
