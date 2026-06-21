<?php

namespace App\Http\Controllers\Setting\Examination;

use Carbon\Carbon;
use App\Models\User;
use App\Models\ExamType;
use App\Models\ExamScore;
use Illuminate\Http\Request;
use App\Models\GuideExaminer;
use App\Models\ExamRegistration;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\DataTables\ExamRegistrationsDataTable;
use App\Services\Examination\ExamRegistrationExaminerSync;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class ExamRegistrationController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:read exam registrations', ['only' => ['index', 'index2']]);
        // $this->middleware('permission:create examregistrations', ['only' => ['create','store']]);
        $this->middleware('permission:update exam registrations', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:delete examregistrations', ['only' => ['destroy']]);
    }

    public function index(ExamRegistrationsDataTable $dataTable)
    {
        $title = 'Jadwal Ujian';
        return $dataTable->render('setting.examination.examregistration-index', compact('title'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'            => 'required|integer|exists:users,id',
            'exam_type_id'       => 'required|integer|exists:exam_types,id',
            'registration_order' => 'required|integer|in:1,2,3',
            'exam_date'          => 'required|date',
            'exam_time'          => 'required',
            'room'               => 'required',
            'title'              => 'required|string',
            'ipk'                => 'nullable|numeric|min:2|max:4',
            'chief_id'           => 'nullable|integer|exists:users,id',
            'exam_file'          => 'nullable|string',
        ]);

        $guideexaminer = GuideExaminer::where('user_id', $validated['user_id'])->first();

        if (!$guideexaminer) {
            return back()
                ->withErrors(['user_id' => 'Data penguji mahasiswa tidak ditemukan di sistem.'])
                ->withInput();
        }

        ExamRegistration::create(array_merge($validated, [
            'examiner1_id' => $guideexaminer->examiner1_id,
            'examiner2_id' => $guideexaminer->examiner2_id,
            'examiner3_id' => $guideexaminer->examiner3_id,
            'guide1_id'    => $guideexaminer->guide1_id,
            'guide2_id'    => $guideexaminer->guide2_id,
        ]));

        $tanggalUjian = match((int) $validated['exam_type_id']) {
            1       => 'proposal_date',
            2       => 'seminar_date',
            3       => 'thesis_date',
            default => null,
        };

        if ($tanggalUjian) {
            $guideexaminer->update([$tanggalUjian => $validated['exam_date']]);
        }

        $student = User::find($validated['user_id']);
        $student->givePermissionTo('join exam');
        $name = strtoupper($student->name ?? '');

        return $this->showByStudent($validated['user_id'])->with('success', 'Pendaftaran ujian ' . $name . ' telah ditambahkan');
    }

    public function edit(ExamRegistration $examregistration)
    {
        $chiefs = User::whereIn('id', array_filter([
            $examregistration->examiner1_id,
            $examregistration->examiner2_id,
            $examregistration->examiner3_id,
            $examregistration->guide1_id,
            $examregistration->guide2_id,
        ]))->role('dosen')->select('initial', 'name', 'id')->get()->sort();

        $exam_score_set = ExamScore::where('exam_registration_id', $examregistration->id)->exists();

        return view('setting.examination.examregistration-form', array_merge(
            $this->_dataSelection(),
            [
                'examregistration' => $examregistration,
                'chiefs'           => $chiefs,
                'exam_score_set'   => $exam_score_set,
            ],
        ));
    }

    public function update(Request $request, ExamRegistration $examregistration)
    {
        $validated = $request->validate([
            'exam_type_id'       => 'required|integer|exists:exam_types,id',
            'registration_order' => 'required|integer|in:1,2,3',
            'exam_date'          => 'required|date',
            'exam_time'          => 'required',
            'room'               => 'required',
            'title'              => 'required|string',
            'ipk'                => 'nullable|numeric|min:2|max:4',
            'exam_file'          => 'nullable|string',
            // Penguji — hanya di-submit saat exam_score_set false (tidak disabled)
            'examiner1_id'       => 'sometimes|nullable|integer|exists:users,id',
            'examiner2_id'       => 'sometimes|nullable|integer|exists:users,id',
            'examiner3_id'       => 'sometimes|nullable|integer|exists:users,id',
            'guide1_id'          => 'sometimes|nullable|integer|exists:users,id',
            'guide2_id'          => 'sometimes|nullable|integer|exists:users,id',
            'chief_id'           => 'sometimes|nullable|integer|exists:users,id',
            // Accordion optional (edit only)
            'schedule_link'      => 'sometimes|nullable|string',
            'online_link'        => 'sometimes|nullable|string',
            'online_user'        => 'sometimes|nullable|string',
            'online_password'    => 'sometimes|nullable|string',
        ]);

        $examregistration->update($validated);

        $tanggalUjian = match((int) $examregistration->exam_type_id) {
            1       => 'proposal_date',
            2       => 'seminar_date',
            3       => 'thesis_date',
            default => null,
        };

        if ($tanggalUjian) {
            $geUpdate = [$tanggalUjian => $examregistration->exam_date];

            // Sinkronisasi penguji ke guide_examiners hanya jika field-nya di-submit
            if (array_key_exists('examiner1_id', $validated)) {
                $geUpdate += [
                    'examiner1_id' => $examregistration->examiner1_id,
                    'examiner2_id' => $examregistration->examiner2_id,
                    'examiner3_id' => $examregistration->examiner3_id,
                    'guide1_id'    => $examregistration->guide1_id,
                    'guide2_id'    => $examregistration->guide2_id,
                ];
            }

            GuideExaminer::where('user_id', $examregistration->user_id)->update($geUpdate);
        }

        $name = strtoupper($examregistration->student->name ?? '');
        return $this->showByStudent($examregistration->user_id)->with('success', 'Data pendaftaran ' . $name . ' telah diperbarui');
    }

    public function pasteImport(Request $request)
    {
        $request->validate(['rows' => 'required|array|min:1|max:200']);

        $results = [];

        foreach ($request->rows as $row) {
            $rowNum = $row['_rowNum'] ?? '?';

            if (!empty($row['_invalid'])) {
                continue;
            }

            if (($row['_duplicateAction'] ?? null) === 'cancel') {
                $results[] = ['row' => $rowNum, 'status' => 'skip', 'message' => 'Import dibatalkan (duplikat)'];
                continue;
            }

            try {
                $results[] = DB::transaction(fn () => $this->importPasteRow($row));
            } catch (Throwable $e) {
                Log::error('pasteImport row failed', [
                    'row' => $rowNum,
                    'npm' => $row['npm'] ?? null,
                    'message' => $e->getMessage(),
                ]);

                $results[] = [
                    'row'     => $rowNum,
                    'status'  => 'error',
                    'message' => 'Baris ' . $rowNum . ' gagal: ' . $e->getMessage(),
                ];
            }
        }

        return response()->json(['results' => $results]);
    }

    /**
     * @return array{row: int|string, status: string, message: string}
     */
    private function importPasteRow(array $row): array
    {
        $rowNum = $row['_rowNum'] ?? '?';
        $npm    = trim($row['npm'] ?? '');

        if (!$npm) {
            return ['row' => $rowNum, 'status' => 'error', 'message' => 'Kolom NPM kosong'];
        }

        [$student, $isNewStudent, $studentError] = $this->resolveStudentForImport($row, $npm);

        if ($studentError) {
            return ['row' => $rowNum, 'status' => 'error', 'message' => $studentError];
        }

        // ── 2. Update phone (strip non-digit & leading 0 untuk wa.me) ──
        if (!empty($row['kontak'])) {
            $phone = ltrim(preg_replace('/\D/', '', $row['kontak']), '0');
            if ($phone) {
                $student->update(['phone' => $phone]);
            }
        }

        // ── 3. Resolve jenis ujian ────────────────────────────────
        $examType = $this->resolveExamType($row['jenis_ujian'] ?? '');
        if (!$examType) {
            $available = ExamType::pluck('name')->join(', ');

            return ['row' => $rowNum, 'status' => 'error',
                'message' => "Jenis ujian '{$row['jenis_ujian']}' tidak dikenali. Tersedia: {$available}"];
        }

        // ── 4. Parse tanggal & waktu ──────────────────────────────
        try {
            $examDate = Carbon::parse($row['tanggal_ujian'] ?? null)->format('Y-m-d');
        } catch (\Exception) {
            return ['row' => $rowNum, 'status' => 'error',
                'message' => "Format tanggal tidak valid: {$row['tanggal_ujian']}"];
        }

        // "07.00 - 08.00" → "07:00"
        $examTime = str_replace('.', ':', explode(' - ', $row['waktu'] ?? '')[0]);

        // IPK: ganti koma desimal Indonesia → titik
        $ipk = !empty($row['ipk']) ? (float) str_replace(',', '.', $row['ipk']) : null;

        // ── 5. Resolve initial → user_id ──────────────────────────
        $resolveInitial = fn (?string $init) => filled($init)
            ? User::where('initial', strtoupper(trim($init)))->value('id')
            : null;

        $examiner1Id = $resolveInitial($row['penguji1'] ?? null);
        $examiner2Id = $resolveInitial($row['penguji2'] ?? null);
        $examiner3Id = $resolveInitial($row['penguji3'] ?? null);
        $guide1Id    = $resolveInitial($row['pembimbing1'] ?? null);
        $guide2Id    = $resolveInitial($row['pembimbing2'] ?? null);
        $chiefId     = $resolveInitial($row['ketua_penguji'] ?? null);

        // ── 6. Upsert guide_examiners ─────────────────────────────
        $tanggalField = match ($examType->id) {
            1       => 'proposal_date',
            2       => 'seminar_date',
            3       => 'thesis_date',
            default => null,
        };

        $guideExaminer = GuideExaminer::firstOrNew(['user_id' => $student->id]);

        if (!$guideExaminer->exists) {
            $username = (string) $student->username;
            if (preg_match('/^(20\d{2})/', $username, $m)) {
                $guideExaminer->year_generation = $m[1];
            } elseif (preg_match('/^(\d{2})/', $username, $m)) {
                $guideExaminer->year_generation = '20' . $m[1];
            } else {
                $guideExaminer->year_generation = (string) date('Y');
            }
        }

        $geAttributes = array_filter([
            'examiner1_id' => $examiner1Id,
            'examiner2_id' => $examiner2Id,
            'examiner3_id' => $examiner3Id,
            'guide1_id'    => $guide1Id,
            'guide2_id'    => $guide2Id,
            'chief_id'     => $chiefId,
        ], fn ($v) => $v !== null);

        if ($tanggalField) {
            $geAttributes[$tanggalField] = $examDate;
        }

        $guideExaminer->fill($geAttributes)->save();

        // ── 7. Tentukan registration_order & cek duplikat ─────────
        if (($row['_duplicateAction'] ?? null) === 'continue' && filled($row['_registration_order'] ?? null)) {
            $order = (int) $row['_registration_order'];

            if ($order < 1 || $order > 3) {
                return ['row' => $rowNum, 'status' => 'error',
                    'message' => strtoupper($student->name) . " — urutan ujian {$order} tidak valid (maks. 3)"];
            }
        } else {
            $order = ExamRegistration::where('user_id', $student->id)
                ->where('exam_type_id', $examType->id)->count() + 1;
        }

        if ($order > 3) {
            return ['row' => $rowNum, 'status' => 'skip',
                'message' => strtoupper($student->name) . " — sudah 3× ujian {$examType->name}, guide_examiners diperbarui"];
        }

        $exists = ExamRegistration::where([
            'user_id'            => $student->id,
            'exam_type_id'       => $examType->id,
            'registration_order' => $order,
        ])->exists();

        if ($exists) {
            return ['row' => $rowNum, 'status' => 'skip',
                'message' => strtoupper($student->name) . " — sudah terdaftar ujian ke-{$order} (guide_examiners diperbarui)"];
        }

        // ── 8. Buat exam_registration ─────────────────────────────
        $registration = ExamRegistration::create([
            'user_id'            => $student->id,
            'exam_type_id'       => $examType->id,
            'registration_order' => $order,
            'exam_date'          => $examDate,
            'exam_time'          => $examTime,
            'room'               => $row['ruang'] ?? null,
            'title'              => $row['judul'] ?? null,
            'ipk'                => $ipk,
            'chief_id'           => $chiefId,
            'examiner1_id'       => $guideExaminer->examiner1_id,
            'examiner2_id'       => $guideExaminer->examiner2_id,
            'examiner3_id'       => $guideExaminer->examiner3_id,
            'guide1_id'          => $guideExaminer->guide1_id,
            'guide2_id'          => $guideExaminer->guide2_id,
            'online_user'        => $row['meeting_id'] ?? null,
            'online_password'    => $row['passcode'] ?? null,
            'online_link'        => $row['link_room'] ?? null,
            'exam_file'          => $row['file_ujian'] ?? null,
        ]);

        app(ExamRegistrationExaminerSync::class)->syncFromRegistration($registration->fresh());

        $this->grantPermissionIfExists($student, 'join exam');

        $label = $isNewStudent ? ' (akun baru dibuat)' : '';

        return ['row' => $rowNum, 'status' => 'success',
            'message' => strtoupper($student->name) . " — {$examType->name} ke-{$order}{$label}"];
    }

    /**
     * @return array{0: ?User, 1: bool, 2: ?string}
     */
    private function resolveStudentForImport(array $row, string $npm): array
    {
        if (!Role::where('name', 'mahasiswa')->exists()) {
            return [null, false, "Role 'mahasiswa' belum ada di sistem — jalankan seeder permission"];
        }

        $student = User::where('username', $npm)->first();

        if ($student) {
            if (!$student->hasRole('mahasiswa')) {
                $student->assignRole('mahasiswa');
                $this->grantPermissionIfExists($student, 'active');
            }

            return [$student, false, null];
        }

        $namaRaw = trim($row['nama_mahasiswa'] ?? '');
        if (!$namaRaw) {
            return [null, false, "NPM {$npm} tidak ditemukan dan nama_mahasiswa kosong — tidak bisa didaftarkan"];
        }

        $email = $npm . '@student.unsil.ac.id';
        if (User::where('email', $email)->exists()) {
            return [null, false, "Email {$email} sudah dipakai akun lain — NPM {$npm} tidak bisa didaftarkan otomatis"];
        }

        try {
            $student = User::create([
                'username' => $npm,
                'name'     => strtoupper($namaRaw),
                'email'    => $email,
                'password' => bcrypt($npm),
            ]);
            $student->assignRole('mahasiswa');
            $this->grantPermissionIfExists($student, 'active');

            return [$student, true, null];
        } catch (QueryException $e) {
            return [null, false, 'Gagal daftarkan mahasiswa NPM ' . $npm . ': ' . $e->getMessage()];
        }
    }

    private function grantPermissionIfExists(User $user, string $permissionName): void
    {
        if (!Permission::where('name', $permissionName)->exists()) {
            return;
        }

        if (!$user->hasPermissionTo($permissionName)) {
            $user->givePermissionTo($permissionName);
        }
    }

    public function pasteImportCheckDuplicates(Request $request)
    {
        $request->validate(['rows' => 'required|array|min:1|max:200']);

        $checks = [];

        foreach ($request->rows as $row) {
            if (!$this->importRowHasRequiredFields($row)) {
                continue;
            }

            $checks[] = $this->checkImportRowDuplicate($row);
        }

        return response()->json(['checks' => $checks]);
    }

    public function destroy(ExamRegistration $examregistration)
    {
        $name = strtoupper($examregistration->student->name ?? '');
        $studentId = $examregistration->user_id;
        $examregistration->delete();

        return $this->showByStudent($studentId)->with('warning', 'Pendaftaran ' . $name . ' telah dihapus');
    }

    public function scoreSet(ExamRegistration $examregistration)
    {
        \App\Filament\Resources\SetScoringToExaminerResource::assignExaminerScores($examregistration);

        return redirect()->back()->with('success', 'Data para penguji telah ditambahkan');
    }

    public function createByStudent($student_id)
    {
        $student = User::find($student_id);
        $chiefs = User::role('dosen')->select('initial', 'name', 'id')->get()->sort();
        $examregistration = new ExamRegistration();

        return view('setting.examination.examregistration-form', array_merge(
            [
                'student'       => $student,
                'examregistration' => $examregistration,
                'exam_score_set'   => false,
                'chiefs'           => $chiefs,
            ],
            $this->_dataSelection(),
        ));
    }

    public function showByStudent($student_id)
    {
        $student = User::find($student_id);
        $examregistrations = ExamRegistration::with(['examtype', 'examiner1', 'examiner2', 'examiner3', 'guide1', 'guide2'])
            ->where('user_id', $student_id)
            ->orderBy('exam_date')
            ->get();

        return view('examination.examregistration', compact('examregistrations', 'student'));
    }

    private function resolveExamType(string $input): ?ExamType
    {
        $input = trim($input);
        if (!$input) return null;

        // 1. Cocokkan input sebagai substring dari nama — paling presisi
        $found = ExamType::where('name', 'LIKE', "%{$input}%")
                         ->orWhere('code', $input)
                         ->first();
        if ($found) return $found;

        // 2. Balik: cek apakah nama di DB merupakan substring dari input
        //    contoh: "Ujian Proposal" ada di dalam "Seminar Ujian Proposal 2024"
        $found = ExamType::get()->first(
            fn ($et) => mb_stripos($input, $et->name) !== false
                     || ($et->code && mb_stripos($input, $et->code) !== false)
        );
        if ($found) return $found;

        // 3. Pecah input menjadi kata-kata (≥5 karakter) dan cari satu per satu
        //    contoh: "Seminar Hasil Penelitian" → "Hasil" cocok dengan "Ujian Hasil Penelitian"
        foreach (explode(' ', $input) as $word) {
            if (mb_strlen($word) >= 5) {
                $found = ExamType::where('name', 'LIKE', "%{$word}%")->first();
                if ($found) return $found;
            }
        }

        return null;
    }

    private function importRowHasRequiredFields(array $row): bool
    {
        foreach (['npm', 'jenis_ujian', 'tanggal_ujian', 'judul', 'ruang', 'waktu'] as $field) {
            if (blank($row[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{
     *     row: int|string,
     *     is_duplicate: bool,
     *     can_continue: bool,
     *     suggested_order: int|null,
     *     existing_orders: array<int, int>,
     *     message: string|null
     * }
     */
    private function checkImportRowDuplicate(array $row): array
    {
        $rowNum = $row['_rowNum'] ?? '?';
        $npm    = trim($row['npm'] ?? '');

        $empty = [
            'row'              => $rowNum,
            'is_duplicate'     => false,
            'can_continue'     => false,
            'suggested_order'  => null,
            'existing_orders'  => [],
            'message'          => null,
        ];

        $student = User::where('username', $npm)->first();
        if (!$student) {
            return $empty;
        }

        $examType = $this->resolveExamType($row['jenis_ujian'] ?? '');
        if (!$examType) {
            return $empty;
        }

        try {
            $examDate = Carbon::parse($row['tanggal_ujian'] ?? null)->format('Y-m-d');
        } catch (\Exception) {
            return $empty;
        }

        $existing = ExamRegistration::query()
            ->where('user_id', $student->id)
            ->where('exam_type_id', $examType->id)
            ->orderBy('registration_order')
            ->get(['id', 'registration_order', 'exam_date', 'title']);

        if ($existing->isEmpty()) {
            return $empty;
        }

        $match = $existing->first(
            fn (ExamRegistration $reg) => $reg->exam_date?->format('Y-m-d') === $examDate
        );

        if (!$match) {
            return $empty;
        }

        $maxOrder       = (int) $existing->max('registration_order');
        $suggestedOrder = $maxOrder + 1;

        return [
            'row'             => $rowNum,
            'is_duplicate'    => true,
            'can_continue'    => $suggestedOrder <= 3,
            'suggested_order' => $suggestedOrder <= 3 ? $suggestedOrder : null,
            'existing_orders' => $existing->pluck('registration_order')->values()->all(),
            'message'         => sprintf(
                '%s — %s ke-%d sudah ada (tanggal %s).',
                strtoupper($student->name),
                $examType->name,
                $match->registration_order,
                Carbon::parse($examDate)->format('d M Y'),
            ),
        ];
    }

    private function _dataSelection()
    {
        $pass_students = GuideExaminer::whereNull('thesis_date')->pluck('user_id');
        return [
            'students'   => User::role('mahasiswa')->select('name', 'id', 'username')->whereIn('id', $pass_students)->get()->sort(),
            'lectures'   => User::role('dosen')->select('initial', 'name', 'id')->get()->sort(),
            'exam_types' => ExamType::select('name', 'id')->get(),
        ];
    }

    public function index2(ExamRegistrationsDataTable $dataTable, $id = "")
    {
        return $dataTable->with('user_id', $id)->render('layouts.setting');
    }
}
