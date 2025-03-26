<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\ResetPasswordOTP;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    /**
     * ✅ REQUEST OTP - Kirim kode OTP ke email
     * Endpoint: POST /api/request-otp
     */
    public function requestOtp(Request $request)
    {
        // Validasi email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                'status' => 'error',
                'message' => $errors->has('email') ? 'Email tidak terdaftar atau tidak aktif.' : $errors->first()
            ], 422);
        }

        // Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan.'
            ], 404);
        }

        // Generate OTP 6 digit
        $otp = rand(100000, 999999);

        // Simpan OTP ke database dengan expiry 10 menit
        PasswordOtp::updateOrCreate(
            ['email' => $request->email],
            ['otp' => $otp, 'expires_at' => now()->addMinutes(10)]
        );

        // Kirim email dengan OTP
        Mail::to($request->email)->send(new ResetPasswordOTP($otp, $user->username));

        return response()->json([
            'status' => 'success',
            'message' => 'Kode OTP telah dikirim ke email Anda.'
        ], 200);
    }


    /**
     * ✅ RESET PASSWORD - Atur ulang password setelah OTP diverifikasi
     * Endpoint: POST /api/reset-password
     */
    public function resetPassword(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:users,email',
            'otp'      => 'required|digits:6',
            'password' => 'required|min:6|confirmed' // password_confirmation harus disertakan
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Cek apakah OTP valid
        $otpRecord = PasswordOtp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'
            ], 400);
        }

        // Perbarui password user
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Hapus OTP setelah digunakan
        $otpRecord->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Password berhasil direset. Silakan login dengan password baru Anda.'
        ], 200);
    }
}
