<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\GuideExaminer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Throwable;

class GuideExaminerImportController extends Controller
{
    /**
     * Kolom lepas (bukan field DB) dari hasil paste, dalam urutan yang sama
     * dengan sheet Excel acuan: npm, nama_mahasiswa, penguji_1..3,
     * pembimbing_1..2, tahun_angkatan.
     */
    private const SLOT_LABELS = [
        'penguji_1' => 'Penguji 1',
        'penguji_2' => 'Penguji 2',
        'penguji_3' => 'Penguji 3',
        'pembimbing_1' => 'Pembimbing 1',
        'pembimbing_2' => 'Pembimbing 2',
    ];

    private const SLOT_TO_COLUMN = [
        'penguji_1' => 'examiner1_id',
        'penguji_2' => 'examiner2_id',
        'penguji_3' => 'examiner3_id',
        'pembimbing_1' => 'guide1_id',
        'pembimbing_2' => 'guide2_id',
    ];

    public function __construct()
    {
        $this->middleware('role:admin');
    }

    public function pasteImportCheck(Request $request)
    {
        $request->validate(['rows' => 'required|array|min:1|max:200']);

        $checks = [];

        foreach ($request->rows as $row) {
            $checks[] = $this->checkImportRow($row);
        }

        return response()->json(['checks' => $checks]);
    }

    public function pasteImport(Request $request)
    {
        $request->validate(['rows' => 'required|array|min:1|max:200']);

        $results = [];

        foreach ($request->rows as $row) {
            $rowNum = $row['_rowNum'] ?? '?';

            if ($row['_invalid'] ?? false) {
                continue;
            }

            try {
                $results[] = DB::transaction(fn () => $this->importPasteRow($row));
            } catch (Throwable $e) {
                Log::error('guideexaminers pasteImport row failed', [
                    'row' => $rowNum,
                    'npm' => $row['npm'] ?? null,
                    'message' => $e->getMessage(),
                ]);

                $results[] = [
                    'row' => $rowNum,
                    'status' => 'error',
                    'message' => 'Baris '.$rowNum.' gagal: '.$e->getMessage(),
                ];
            }
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Pratinjau: resolve NPM -> mahasiswa (ada/baru) dan kode inisial ->
     * dosen, tanpa menyimpan apa pun.
     *
     * @return array<string, mixed>
     */
    private function checkImportRow(array $row): array
    {
        $rowNum = $row['_rowNum'] ?? '?';
        $npm = trim((string) ($row['npm'] ?? ''));
        $namaRaw = trim((string) ($row['nama_mahasiswa'] ?? ''));
        $angkatan = trim((string) ($row['tahun_angkatan'] ?? ''));

        $base = [
            'row' => $rowNum,
            'npm' => $npm,
            'student_name' => null,
            'student_is_new' => false,
            'angkatan' => $angkatan,
            'slots' => [],
            'invalid' => false,
            'message' => null,
        ];

        if ($npm === '') {
            return array_merge($base, ['invalid' => true, 'message' => 'NPM kosong']);
        }

        $student = User::where('username', $npm)->first();

        if ($student) {
            $base['student_name'] = $student->name;
        } elseif ($namaRaw !== '') {
            $base['student_name'] = strtoupper($namaRaw);
            $base['student_is_new'] = true;
        } else {
            return array_merge($base, [
                'invalid' => true,
                'message' => "NPM {$npm} tidak ditemukan dan nama_mahasiswa kosong, tidak bisa didaftarkan",
            ]);
        }

        if ($angkatan === '' || ! ctype_digit($angkatan) || strlen($angkatan) !== 4) {
            return array_merge($base, [
                'invalid' => true,
                'message' => "Tahun angkatan '{$angkatan}' tidak valid (harus 4 digit)",
            ]);
        }

        if ($student) {
            $existing = GuideExaminer::where('user_id', $student->id)->first();
            if ($existing && filled($existing->thesis_date)) {
                return array_merge($base, [
                    'invalid' => true,
                    'message' => strtoupper($student->name).' sudah lulus, dilewati demi keamanan data',
                ]);
            }
        }

        $errors = [];
        foreach (self::SLOT_LABELS as $field => $label) {
            $code = trim((string) ($row[$field] ?? ''));
            if ($code === '') {
                $base['slots'][$field] = null;
                continue;
            }

            $lecturer = User::role('dosen')->where('initial', strtoupper($code))->first();

            if (! $lecturer) {
                $errors[] = "{$label}: kode '{$code}' tidak ditemukan";
                $base['slots'][$field] = ['code' => strtoupper($code), 'name' => null, 'found' => false];
                continue;
            }

            $base['slots'][$field] = ['code' => strtoupper($code), 'name' => $lecturer->name, 'found' => true];
        }

        if ($errors) {
            return array_merge($base, ['invalid' => true, 'message' => implode('; ', $errors)]);
        }

        return $base;
    }

    /**
     * @return array{row: int|string, status: string, message: string}
     */
    private function importPasteRow(array $row): array
    {
        $rowNum = $row['_rowNum'] ?? '?';
        $npm = trim((string) ($row['npm'] ?? ''));
        $namaRaw = trim((string) ($row['nama_mahasiswa'] ?? ''));
        $angkatan = trim((string) ($row['tahun_angkatan'] ?? ''));

        if (! Role::where('name', 'mahasiswa')->exists()) {
            return ['row' => $rowNum, 'status' => 'error', 'message' => "Role 'mahasiswa' belum ada di sistem"];
        }

        $student = User::where('username', $npm)->first();
        $isNewStudent = false;

        if (! $student) {
            $email = $npm.'@student.unsil.ac.id';

            if (User::where('email', $email)->exists()) {
                return ['row' => $rowNum, 'status' => 'error', 'message' => "Email {$email} sudah dipakai akun lain, NPM {$npm} tidak bisa didaftarkan otomatis"];
            }

            try {
                $student = User::create([
                    'username' => $npm,
                    'name' => strtoupper($namaRaw),
                    'email' => $email,
                    'password' => bcrypt($npm),
                ]);
                $student->assignRole('mahasiswa');
                $isNewStudent = true;
            } catch (QueryException $e) {
                return ['row' => $rowNum, 'status' => 'error', 'message' => "Gagal daftarkan mahasiswa NPM {$npm}: {$e->getMessage()}"];
            }
        } elseif (! $student->hasRole('mahasiswa')) {
            $student->assignRole('mahasiswa');
        }

        $existing = GuideExaminer::where('user_id', $student->id)->first();

        if ($existing && filled($existing->thesis_date)) {
            return ['row' => $rowNum, 'status' => 'skip', 'message' => strtoupper($student->name).' sudah lulus, dilewati'];
        }

        $resolveInitial = fn (?string $code): ?int => filled($code)
            ? User::role('dosen')->where('initial', strtoupper(trim($code)))->value('id')
            : null;

        $slotIds = [];
        foreach (self::SLOT_TO_COLUMN as $field => $column) {
            $slotIds[$column] = $resolveInitial($row[$field] ?? null);
        }

        $guideExaminer = $existing ?? new GuideExaminer(['user_id' => $student->id]);
        $guideExaminer->fill(array_merge($slotIds, [
            'year_generation' => $angkatan,
        ]));

        // Kosongkan ketua penguji kalau dia bukan lagi salah satu dari slot
        // penguji/pembimbing setelah baris ini diterapkan (mencegah chief_id
        // nyangkut ke dosen yang sudah diganti lewat paste import).
        $assignedIds = array_filter(array_values($slotIds));
        if ($guideExaminer->chief_id && ! in_array((int) $guideExaminer->chief_id, array_map('intval', $assignedIds), true)) {
            $guideExaminer->chief_id = null;
        }

        $guideExaminer->save();

        $prefix = $isNewStudent ? 'Mahasiswa baru didaftarkan, ' : '';

        return [
            'row' => $rowNum,
            'status' => 'success',
            'message' => $prefix.strtoupper($student->name)." ({$npm}), angkatan {$angkatan} disimpan",
        ];
    }
}
