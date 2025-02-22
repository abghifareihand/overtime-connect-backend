<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OvertimeController extends Controller
{
    /**
     * Menyimpan data lembur.
     * Endpoint: POST /api/overtime
     */
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'date' => 'required|date',
            'overtime_hours' => 'required|numeric|min:0',
            'total_overtime' => 'required|numeric|min:0',
            'status' => 'required|boolean',  // 0 untuk Tidak Masuk, 1 untuk Masuk
            'day_type' => 'required|string|in:regular,holiday',
        ]);

        // Ambil data user dari token (karena menggunakan auth:sanctum)
        $user = Auth::user();

        // Cek apakah sudah ada data lembur di tanggal yang sama
        $existingOvertime = Overtime::where('user_id', $user->id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($existingOvertime) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already recorded overtime for this date.',
            ], 422);
        }

        // Simpan data lembur
        $overtime = Overtime::create([
            'user_id' => $user->id,
            'date' => $request->date,
            'overtime_hours' => $request->overtime_hours,  // Simpan jumlah jam lembur
            'total_overtime' => $request->total_overtime,  // Simpan total harga lemburan
            'status' => $request->status,  // Menyimpan status kehadiran
            'day_type' => $request->day_type,  // Menyimpan day type
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Overtime recorded successfully',
            'data' => $overtime
        ], 201);
    }

    public function getYears()
    {
        // Ambil data user dari token
        $user = Auth::user();

        // Ambil tahun unik dari data lembur user
        $years = Overtime::where('user_id', $user->id)
            ->selectRaw('YEAR(date) as year')  // Ambil hanya tahun dari kolom date
            ->distinct()  // Ambil hanya tahun yang unik
            ->orderBy('year', 'asc')  // Urutkan dari terkecil ke terbesar
            ->pluck('year')
            ->map(fn($year) => (string) $year); // Ubah ke string agar menjadi array JSON yang rapi

        return response()->json([
            'status' => 'success',
            'message' => count($years) > 0 ? 'Daftar tahun berhasil diambil' : 'Tidak ada data lembur tersedia',
            'years' => $years
        ], 200);
    }


    /**
     * Mendapatkan total lembur berdasarkan bulan atau tahun.
     * Endpoint: GET /api/overtime-report
     */
    public function getReport(Request $request)
    {
        // Ambil data user dari token
        $user = Auth::user();

        // Ambil bulan dan tahun dari request, jika tidak ada gunakan bulan dan tahun sekarang
        $month = $request->query('month', Carbon::now()->format('m'));  // Default bulan sekarang
        $year = $request->query('year', Carbon::now()->format('Y'));   // Default tahun sekarang jika tidak ada tahun

        // Jika hanya tahun yang diberikan, set month ke "-"
        if (!$request->has('month')) {
            $month = "-";  // Set month ke "-" jika tidak ada bulan
        }

        // Jika hanya bulan yang diberikan, gunakan tahun sekarang
        if (!$request->has('year')) {
            $year = Carbon::now()->format('Y');
        }

        // Jika tidak ada parameter year dan month, gunakan bulan dan tahun sekarang
        if (!$request->has('year') && !$request->has('month')) {
            $month = Carbon::now()->format('m'); // Bulan sekarang
            $year = Carbon::now()->format('Y');  // Tahun sekarang
        }

        // Cek apakah year diberikan
        if ($request->has('year') && !$request->has('month')) {
            // Ambil data untuk seluruh tahun (semua bulan dalam tahun itu)
            $startDate = Carbon::createFromFormat('Y-m-d', "$year-01-01")->startOfYear()->toDateString();
            $endDate = Carbon::createFromFormat('Y-m-d', "$year-12-31")->endOfYear()->toDateString();
        } else {
            // Jika month dan year diberikan, ambil data untuk bulan tertentu dalam tahun itu
            $startDate = Carbon::createFromFormat('Y-m-d', "$year-$month-01")->startOfMonth()->toDateString();
            $endDate = Carbon::createFromFormat('Y-m-d', "$year-$month-01")->endOfMonth()->toDateString();
        }

        // Ambil data lembur di bulan/tahun tersebut
        $overtimeData = Overtime::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc') // Urutkan dari terbaru
            ->get();

        // Hitung total jam lembur dan total harga lemburan
        $totalHours = $overtimeData->sum('overtime_hours');
        $totalAmount = $overtimeData->sum('total_overtime');

        return response()->json([
            'status' => 'success',
            'message' => 'Report retrieved successfully',
            'month' => $month,
            'year' => $year,
            'total_hours' => $totalHours,
            'total_amount' => $totalAmount,
            'data' => $overtimeData
        ], 200);
    }

    public function getReportWeekly(Request $request)
    {
        // Ambil data user dari token
        $user = Auth::user();

        // Tentukan rentang minggu ini (Senin - Minggu)
        $startDate = Carbon::now()->startOfWeek()->toDateString();
        $endDate = Carbon::now()->endOfWeek()->toDateString();

        // Ambil data lembur dalam rentang minggu ini
        $overtimeData = Overtime::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc') // Urutkan dari terbaru
            ->get();

        // Hitung total jam lembur dan total harga lemburan
        $totalHours = $overtimeData->sum('overtime_hours');
        $totalAmount = $overtimeData->sum('total_overtime');

        return response()->json([
            'status' => 'success',
            'week_start' => $startDate,
            'week_end' => $endDate,
            'total_hours' => $totalHours,
            'total_amount' => $totalAmount,
            'data' => $overtimeData
        ], 200);
    }

    public function getReportMonthly(Request $request)
    {
        // Ambil data user dari token
        $user = Auth::user();

        // Ambil bulan dan tahun dari request atau gunakan default (bulan & tahun sekarang)
        $month = $request->query('month', Carbon::now()->format('m'));
        $year = $request->query('year', Carbon::now()->format('Y'));

        // Tentukan tanggal awal dan akhir bulan ini
        $startDate = Carbon::createFromFormat('Y-m-d', "$year-$month-01")->startOfMonth()->toDateString();
        $endDate = Carbon::createFromFormat('Y-m-d', "$year-$month-01")->endOfMonth()->toDateString();

        // Ambil data lembur dalam bulan ini
        $overtimeData = Overtime::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc') // Urutkan dari terbaru
            ->get();

        // Hitung total jam lembur dan total harga lemburan
        $totalHours = $overtimeData->sum('overtime_hours');
        $totalAmount = $overtimeData->sum('total_overtime');

        return response()->json([
            'status' => 'success',
            'month_start' => $startDate,
            'month_end' => $endDate,
            'total_hours' => $totalHours,
            'total_amount' => $totalAmount,
            'data' => $overtimeData
        ], 200);
    }

    public function getReportYearly(Request $request)
    {
        // Ambil data user dari token
        $user = Auth::user();

        // Ambil tahun dari request, default ke tahun sekarang jika tidak ada
        $year = $request->query('year', Carbon::now()->format('Y'));

        // Ambil semua data lembur dalam tahun yang dipilih
        $overtimeData = Overtime::where('user_id', $user->id)
            ->whereYear('date', $year)
            ->orderBy('date', 'asc')
            ->get();

        // Kelompokkan data berdasarkan bulan
        $groupedData = [];
        $totalHours = 0;
        $totalAmount = 0;

        foreach ($overtimeData as $overtime) {
            $month = Carbon::parse($overtime->date)->format('m');
            $monthName = Carbon::parse($overtime->date)->format('F');

            if (!isset($groupedData[$month])) {
                $groupedData[$month] = [
                    'month' => $monthName,
                    'total_hours' => 0,
                    'total_amount' => 0,
                    'overtimes' => []
                ];
            }

            $groupedData[$month]['total_hours'] += $overtime->overtime_hours;
            $groupedData[$month]['total_amount'] += $overtime->total_overtime;
            $groupedData[$month]['overtimes'][] = $overtime;

            // Akumulasi total per tahun
            $totalHours += $overtime->overtime_hours;
            $totalAmount += $overtime->total_overtime;
        }

        return response()->json([
            'status' => 'success',
            'year' => $year,
            'total_hours' => $totalHours,
            'total_amount' => $totalAmount,
            'data' => $groupedData
        ], 200);
    }
}
