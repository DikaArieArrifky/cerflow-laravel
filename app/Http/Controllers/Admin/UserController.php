<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * Display the User Management page.
     */
    public function index(Request $request): View|JsonResponse
    {
        $users = User::orderBy('id', 'asc')->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status'  => 'success',
                'data'    => $users,
            ]);
        }

        return view('admin.users', compact('users'));
    }

    /**
     * Store or update a user (API/AJAX).
     */
    public function store(Request $request): JsonResponse
    {
        $userId = $request->input('id');

        $validated = $request->validate([
            'username'       => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'namalengkap'    => ['required', 'string', 'max:100'],
            'role'           => ['required', 'string', Rule::in(['guru', 'siswa'])],
            'password'       => [$userId ? 'nullable' : 'required', 'string', 'min:4'],
            'gemini_api_key' => ['nullable', 'string', 'max:255'],
        ], [
            'username.required'    => 'Username wajib diisi.',
            'username.unique'      => 'Username sudah digunakan oleh akun lain.',
            'namalengkap.required' => 'Nama lengkap wajib diisi.',
            'role.in'              => 'Role harus guru atau siswa.',
            'password.required'    => 'Password wajib diisi untuk pengguna baru.',
        ]);

        if ($userId) {
            $user = User::findOrFail($userId);
            $user->username = $validated['username'];
            $user->namalengkap = $validated['namalengkap'];
            $user->role = $validated['role'];
            $user->gemini_api_key = $validated['gemini_api_key'] ?? null;

            // Only update password if explicitly provided
            if (! empty($validated['password'])) {
                $user->password = $validated['password'];
            }

            $user->save();

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => 'User berhasil diperbarui.',
                'data'    => $user,
            ]);
        }

        $user = User::create([
            'username'       => $validated['username'],
            'namalengkap'    => $validated['namalengkap'],
            'password'       => $validated['password'],
            'role'           => $validated['role'],
            'gemini_api_key' => $validated['gemini_api_key'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'status'  => 'success',
            'message' => 'User berhasil ditambahkan.',
            'data'    => $user,
        ], 201);
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent deleting oneself
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri saat sedang aktif.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'status'  => 'success',
            'message' => 'User berhasil dihapus.',
        ]);
    }

    /**
     * Download CSV template for bulk user import.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="user_template.csv"',
        ];

        return response()->stream(function () {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['username', 'namalengkap', 'password', 'role']);
            fputcsv($output, ['guru_teladan', 'Guru Teladan, M.Pd', 'pass123', 'guru']);
            fputcsv($output, ['siswa_rajin', 'Siswa Rajin Sekali', 'siswa456', 'siswa']);
            fclose($output);
        }, 200, $headers);
    }

    /**
     * Bulk import users from a CSV file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ], [
            'csv_file.required' => 'File CSV wajib diunggah.',
            'csv_file.mimes'    => 'Format file harus berupa .csv',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Gagal membuka file CSV.',
            ], 422);
        }

        // Read and skip header row
        $header = fgetcsv($handle);

        $created = 0;
        $updated = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 4) {
                    $errors++;
                    continue;
                }

                $username = trim($row[0]);
                $namalengkap = trim($row[1]);
                $password = trim($row[2]);
                $role = strtolower(trim($row[3]));

                if (empty($username) || ! in_array($role, ['guru', 'siswa'], true)) {
                    $errors++;
                    continue;
                }

                $existingUser = User::where('username', $username)->first();

                if ($existingUser) {
                    $existingUser->namalengkap = $namalengkap;
                    $existingUser->role = $role;
                    if (! empty($password)) {
                        $existingUser->password = $password;
                    }
                    $existingUser->save();
                    $updated++;
                } else {
                    if (empty($password)) {
                        $errors++;
                        continue;
                    }
                    User::create([
                        'username'    => $username,
                        'namalengkap' => $namalengkap,
                        'password'    => $password,
                        'role'        => $role,
                    ]);
                    $created++;
                }
            }

            DB::commit();
            fclose($handle);

            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => "Import selesai: {$created} user baru ditambahkan, {$updated} user diperbarui, {$errors} baris dilewati/error.",
                'data'    => compact('created', 'updated', 'errors'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);

            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat memproses data CSV: ' . $e->getMessage(),
            ], 500);
        }
    }
}
