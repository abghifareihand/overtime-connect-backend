<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * ✅ REGISTER API
     * Endpoint: POST /api/register
     * Fungsi: Mendaftarkan user baru ke sistem
     */
    public function register(Request $request)
    {

        // Custom message untuk validasi
        $messages = [
            'email.unique' => 'Email sudah digunakan',
            'username.unique' => 'Username sudah digunakan',
            'email.email' => 'Format email tidak valid',
            'password.min' => 'Password minimal harus 6 karakter',
        ];

        // Validasi input dari request
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'phone' => 'nullable|string|max:15',
            'salary' => 'nullable|numeric',
            'password' => 'required|string|min:6',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => collect($validator->errors()->all())->first(), // Ambil error pertama
            ], 422);
        }


        // Membuat user baru
        $user = User::create([
            'fullname' => $request->fullname,
            'email' => $request->email,
            'username' => $request->username,
            'phone' => $request->phone,
            'salary' => $request->salary,
            'photo' => null,
            'password' => Hash::make($request->password), // Hash password sebelum disimpan
        ]);

        // Generate token menggunakan Laravel Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // Mengembalikan response JSON dengan status sukses
        return response()->json([
            'status' => 'success',
            'message' => 'Register berhasil',
            'token' => $token,
            'user' => $user
        ], 201); // Status HTTP 201 (Created)
    }

    /**
     * ✅ LOGIN API (Login dengan Username & Password)
     * Endpoint: POST /api/login
     * Fungsi: Login user dan mengembalikan token autentikasi
     */
    public function login(Request $request)
    {
        // Validasi input dari request
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Cek apakah user dengan username tersebut ada di database
        $user = User::where('username', $request->username)->first();

        // Jika username tidak ditemukan
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username tidak ditemukan'
            ], 404);
        }

        // Jika password salah
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password salah'
            ], 401);
        }

        // Generate token menggunakan Laravel Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // Mengembalikan response JSON dengan status sukses
        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $user
        ], 200); // Status HTTP 200 (OK)
    }

    /**
     * ✅ LOGOUT API
     * Endpoint: POST /api/logout
     * Fungsi: Logout user dengan menghapus semua token aktif
     * Akses: Hanya untuk user yang sudah login (Harus pakai token)
     */
    public function logout(Request $request)
    {
        // Hapus semua token yang dimiliki oleh user
        $request->user()->tokens()->delete();

        // Mengembalikan response JSON dengan status sukses
        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ], 200); // Status HTTP 200 (OK)
    }

    /**
     * ✅ GET AUTHENTICATED USER API
     * Endpoint: GET /api/user
     * Fungsi: Mengambil informasi user yang sedang login
     * Akses: Hanya untuk user yang sudah login (Harus pakai token)
     */
    public function getUser(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Data user berhasil diambil',
            'user' => $request->user()
        ], 200);
    }


    /**
     * ✅ UPDATE USER PROFILE API
     * Endpoint: PUT /api/user/profile
     * Fungsi: Mengupdate profil user yang sedang login
     * Akses: Hanya untuk user yang sudah login (Harus pakai token)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Validasi input
        $validator = Validator::make($request->all(), [
            'fullname' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'working_days' => 'sometimes|nullable|integer|in:5,6',
            'photo' => 'sometimes|image|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format foto tidak sesuai', // Mengambil pesan error pertama saja
            ], 422);
        }

        // Jika ada unggahan foto baru, hapus foto lama dan simpan yang baru
        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            // Simpan foto baru
            $photo = $request->file('photo');
            $photo_name = time() . '.' . $photo->getClientOriginalExtension();
            $filePath = $photo->storeAs('photo', $photo_name, 'public');

            // Update path foto di database
            $user->photo = $filePath;
        }

        // Update data user, mempertahankan data lama jika tidak ada input baru
        $user->update([
            'fullname' => $request->fullname ?? $user->fullname,
            'phone' => $request->phone ?? $user->phone,
            'working_days' => $request->working_days ?? $user->working_days,
            'photo' => $user->photo ?? $user->photo,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diupdate',
        ], 200);
    }

    /**
     * ✅ UPDATE USER EMAIL API
     * Endpoint: PUT /api/user/email
     * Fungsi: Mengupdate email user yang sedang login
     * Akses: Hanya untuk user yang sudah login (Harus pakai token)
     */
    public function updateEmail(Request $request)
    {
        $user = $request->user();

        // Custom validasi agar tidak mengembalikan field errors
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email sudah digunakan', // Mengambil pesan error pertama saja
            ], 422);
        }

        // Cek apakah password yang dimasukkan benar
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password yang anda masukkan salah'
            ], 401);
        }

        // Update email user
        $user->update(['email' => $request->email]);

        return response()->json([
            'status' => 'success',
            'message' => 'Email berhasil diupdate',
            'email' => $user->email
        ], 200);
    }

    /**
     * ✅ UPDATE SALARY API
     * Endpoint: PUT /api/user/salary
     * Fungsi: Mengupdate gaji user yang sedang login, dengan verifikasi password
     * Akses: Hanya untuk user yang sudah login (Harus pakai token)
     */
    public function updateSalary(Request $request)
    {
        $user = $request->user();

        // Validasi input
        $validator = Validator::make($request->all(), [
            'salary' => 'required|numeric|min:0', // Validasi untuk salary
            'password' => 'required|string', // Validasi untuk password
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format gaji atau password tidak sesuai', // Mengambil pesan error pertama saja
            ], 422);
        }

        // Cek apakah password yang dimasukkan benar
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password yang anda masukkan salah'
            ], 401);
        }

        // Update gaji user
        $user->update(['salary' => $request->salary]);

        return response()->json([
            'status' => 'success',
            'message' => 'Gaji berhasil diupdate',
            'salary' => $user->salary
        ], 200);
    }


    public function updatePassword(Request $request)
    {
        $user = $request->user();

        // Cek apakah password lama benar
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password lama yang anda masukkan salah'
            ], 401);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password yang baru tidak cocok', // Ambil pesan pertama
            ], 422);
        }


        // Update password user
        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diupdate'
        ], 200);
    }
}
