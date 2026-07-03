<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
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
                Log::error('user pasteImport row failed', [
                    'row' => $rowNum,
                    'username' => $row['username'] ?? null,
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
     * @return array{row: int|string, status: string, message: string}
     */
    private function importPasteRow(array $row): array
    {
        $rowNum = $row['_rowNum'] ?? '?';

        $role = Role::whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) ($row['role'] ?? '')))])->first();
        if (! $role) {
            return [
                'row' => $rowNum,
                'status' => 'error',
                'message' => "Role '{$row['role']}' tidak ditemukan.",
            ];
        }

        $username = trim((string) ($row['username'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'row' => $rowNum,
                'status' => 'error',
                'message' => "Email tidak valid: {$email}",
            ];
        }

        $existing = User::where('username', $username)->first();

        $data = [
            'name' => trim((string) ($row['nama'] ?? '')),
            'username' => $username,
            'email' => $email,
            'password' => (string) ($row['password'] ?? ''),
        ];

        if ($existing) {
            if (($row['_duplicateAction'] ?? null) === 'cancel') {
                return [
                    'row' => $rowNum,
                    'status' => 'skip',
                    'message' => "User {$username} dibatalkan — tidak diubah.",
                ];
            }

            if (blank($data['password'])) {
                unset($data['password']);
            }

            $existing->update($data);
            $existing->syncRoles([$role->name]);

            return [
                'row' => $rowNum,
                'status' => 'success',
                'message' => "Diperbarui: {$data['name']} ({$username}) — role {$role->name}",
            ];
        }

        if (blank($data['password'])) {
            return [
                'row' => $rowNum,
                'status' => 'error',
                'message' => 'Password wajib diisi untuk user baru.',
            ];
        }

        $user = User::create($data);
        $user->assignRole($role->name);

        return [
            'row' => $rowNum,
            'status' => 'success',
            'message' => "Ditambahkan: {$data['name']} ({$username}) — role {$role->name}",
        ];
    }

    private function importRowHasRequiredFields(array $row): bool
    {
        foreach (['nama', 'username', 'email', 'role'] as $field) {
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

        $username = trim((string) ($row['username'] ?? ''));
        if ($username === '') {
            return $empty;
        }

        $existing = User::where('username', $username)->first();
        if (! $existing) {
            return $empty;
        }

        return [
            'row' => $rowNum,
            'is_duplicate' => true,
            'can_update' => true,
            'message' => "User '{$username}' sudah ada ({$existing->name}, {$existing->email}).",
            'existing' => [
                'name' => $existing->name,
                'email' => $existing->email,
                'roles' => $existing->roles->pluck('name')->implode(', '),
            ],
        ];
    }
}
