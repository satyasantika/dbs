<?php

namespace App\Http\Controllers\Setting\Nuir;

use App\Http\Controllers\Controller;
use App\Models\GuideAllocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GuideAllocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage nuir guide quota');
    }

    public function pasteImportCheckDuplicates(Request $request)
    {
        $request->validate(['rows' => 'required|array|min:1|max:200']);

        $checks = [];

        foreach ($request->rows as $row) {
            if (! $this->importRowHasRequiredFields($row)) {
                continue;
            }

            $checks[] = $this->checkImportRowDuplicate($row);
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

            if (($row['_duplicateAction'] ?? null) === 'cancel') {
                $results[] = ['row' => $rowNum, 'status' => 'skip', 'message' => 'Import dibatalkan (sudah ada)'];

                continue;
            }

            try {
                $results[] = DB::transaction(fn () => $this->importPasteRow($row));
            } catch (Throwable $e) {
                Log::error('guideAllocation pasteImport row failed', [
                    'row' => $rowNum,
                    'dosen' => $row['dosen'] ?? null,
                    'message' => $e->getMessage(),
                ]);

                $results[] = [
                    'row' => $rowNum,
                    'status' => 'error',
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

        $lecturer = $this->resolveLecturer($row['dosen'] ?? null);
        if (! $lecturer) {
            return [
                'row' => $rowNum,
                'status' => 'error',
                'message' => "Dosen '{$row['dosen']}' tidak ditemukan. Gunakan inisial (contoh: SIN) atau nama lengkap.",
            ];
        }

        if (! $lecturer->hasRole('dosen')) {
            return [
                'row' => $rowNum,
                'status' => 'error',
                'message' => "User '{$lecturer->name}' bukan dosen.",
            ];
        }

        $year = (int) trim((string) ($row['tahun'] ?? ''));
        if ($year < 2000 || $year > 2100) {
            return [
                'row' => $rowNum,
                'status' => 'error',
                'message' => "Tahun tidak valid: {$row['tahun']}",
            ];
        }

        [$guide1Quota, $quotaError] = $this->parseQuota($row['kuota_p1'] ?? null, 'Kuota P1');
        if ($quotaError) {
            return ['row' => $rowNum, 'status' => 'error', 'message' => $quotaError];
        }

        [$guide2Quota, $quotaError] = $this->parseQuota($row['kuota_p2'] ?? null, 'Kuota P2');
        if ($quotaError) {
            return ['row' => $rowNum, 'status' => 'error', 'message' => $quotaError];
        }

        $active = $this->parseActive($row['aktif'] ?? null);

        $existing = GuideAllocation::query()
            ->where('user_id', $lecturer->id)
            ->where('year', $year)
            ->first();

        if ($existing) {
            if (($row['_duplicateAction'] ?? null) === 'cancel') {
                return [
                    'row' => $rowNum,
                    'status' => 'skip',
                    'message' => "Kuota {$lecturer->name} ({$year}) dibatalkan — tidak diubah.",
                ];
            }

            if ($guide1Quota < $existing->guide1_filled) {
                return [
                    'row' => $rowNum,
                    'status' => 'error',
                    'message' => "Kuota P1 ({$guide1Quota}) tidak boleh kurang dari terisi ({$existing->guide1_filled}).",
                ];
            }

            if ($guide2Quota < $existing->guide2_filled) {
                return [
                    'row' => $rowNum,
                    'status' => 'error',
                    'message' => "Kuota P2 ({$guide2Quota}) tidak boleh kurang dari terisi ({$existing->guide2_filled}).",
                ];
            }

            $existing->update([
                'guide1_quota' => $guide1Quota,
                'guide2_quota' => $guide2Quota,
                'active' => $active,
            ]);

            return [
                'row' => $rowNum,
                'status' => 'success',
                'message' => "Diperbarui: {$lecturer->name} ({$year}) — P1={$guide1Quota}, P2={$guide2Quota}",
            ];
        }

        GuideAllocation::create([
            'user_id' => $lecturer->id,
            'year' => $year,
            'guide1_quota' => $guide1Quota,
            'guide2_quota' => $guide2Quota,
            'guide1_filled' => 0,
            'guide2_filled' => 0,
            'active' => $active,
        ]);

        return [
            'row' => $rowNum,
            'status' => 'success',
            'message' => "Ditambahkan: {$lecturer->name} ({$year}) — P1={$guide1Quota}, P2={$guide2Quota}",
        ];
    }

    private function importRowHasRequiredFields(array $row): bool
    {
        foreach (['dosen', 'tahun', 'kuota_p1', 'kuota_p2'] as $field) {
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
     *     can_update: bool,
     *     message: string|null,
     *     existing: array<string, mixed>|null
     * }
     */
    private function checkImportRowDuplicate(array $row): array
    {
        $rowNum = $row['_rowNum'] ?? '?';

        $empty = [
            'row' => $rowNum,
            'is_duplicate' => false,
            'can_update' => false,
            'message' => null,
            'existing' => null,
        ];

        $lecturer = $this->resolveLecturer($row['dosen'] ?? null);
        if (! $lecturer) {
            return $empty;
        }

        $year = (int) trim((string) ($row['tahun'] ?? ''));
        if ($year < 2000 || $year > 2100) {
            return $empty;
        }

        $existing = GuideAllocation::query()
            ->where('user_id', $lecturer->id)
            ->where('year', $year)
            ->first();

        if (! $existing) {
            return $empty;
        }

        return [
            'row' => $rowNum,
            'is_duplicate' => true,
            'can_update' => true,
            'message' => "Kuota {$lecturer->name} tahun {$year} sudah ada "
                . "(P1: {$existing->guide1_quota}/{$existing->guide1_filled}, "
                . "P2: {$existing->guide2_quota}/{$existing->guide2_filled}).",
            'existing' => [
                'guide1_quota' => $existing->guide1_quota,
                'guide1_filled' => $existing->guide1_filled,
                'guide2_quota' => $existing->guide2_quota,
                'guide2_filled' => $existing->guide2_filled,
                'active' => $existing->active,
            ],
        ];
    }

    private function resolveLecturer(?string $dosen): ?User
    {
        $dosen = trim((string) $dosen);

        if ($dosen === '') {
            return null;
        }

        $baseQuery = User::query()->role('dosen');

        $byInitial = (clone $baseQuery)
            ->where('initial', strtoupper($dosen))
            ->first();

        if ($byInitial) {
            return $byInitial;
        }

        $byUsername = (clone $baseQuery)
            ->whereRaw('LOWER(username) = ?', [mb_strtolower($dosen)])
            ->first();

        if ($byUsername) {
            return $byUsername;
        }

        return (clone $baseQuery)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($dosen)])
            ->first();
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    private function parseQuota(mixed $value, string $label): array
    {
        if ($value === null || trim((string) $value) === '') {
            return [null, "{$label} wajib diisi."];
        }

        if (! is_numeric($value)) {
            return [null, "{$label} harus angka: {$value}"];
        }

        $quota = (int) $value;

        if ($quota < 0) {
            return [null, "{$label} tidak boleh negatif."];
        }

        return [$quota, null];
    }

    private function parseActive(mixed $value): bool
    {
        if ($value === null || trim((string) $value) === '') {
            return true;
        }

        $normalized = mb_strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'ya', 'yes', 'true', 'aktif', 'y'], true);
    }
}
