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
            'status' => 'required|boolean',  // 0 untuk Tidak Masuk, 1 untuk Masuk
            'day_type' => 'required|string|in:regular,holiday',
            'total_overtime' => 'required|numeric|min:0',
            'overtime_details' => 'array',
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
                'message' => 'Anda telah mencatat lembur untuk tanggal ini',
            ], 422);
        }

        // Simpan data lembur
        $overtime = Overtime::create([
            'user_id' => $user->id,
            'date' => $request->date,
            'overtime_hours' => $request->overtime_hours,  // Simpan jumlah jam lembur
            'status' => $request->status,  // Menyimpan status kehadiran
            'day_type' => $request->day_type,  // Menyimpan day type
            'total_overtime' => $request->total_overtime,  // Simpan total harga lemburan
            'overtime_details' => json_encode($request->overtime_details),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Overtime recorded successfully',
            'data' => collect($overtime)->except(['overtime_details'])
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
            ->get()
            ->map(function ($overtime) {
                return collect($overtime)->except(['overtime_details']); // Hilangkan overtime_details
            });

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
            ->get()->map(function ($overtime) {
                return collect($overtime)->except(['overtime_details']); // Hilangkan overtime_details
            });

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
            ->get()
            ->map(function ($overtime) {
                return collect($overtime)->except(['overtime_details']); // Hilangkan overtime_details
            });

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
            $groupedData[$month]['overtimes'][] = collect($overtime)->except(['overtime_details']);

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

    public function getReportDate(Request $request)
    {
        $user = Auth::user(); // Ambil user yang sedang login
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (!$startDate || !$endDate) {
            // Ambil awal dan akhir bulan ini
            $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
            $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

            // Ambil data bulan ini hanya untuk user yang sedang login
            $overtimes = Overtime::where('user_id', $user->id)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->orderBy('date', 'desc')
                ->get();

            if ($overtimes->isNotEmpty()) {
                $firstDate = Carbon::parse($overtimes->last()->date)->format('d/m/Y');
                $lastDate = Carbon::parse($overtimes->first()->date)->format('d/m/Y');
                $formattedDateRange = "{$firstDate} - {$lastDate}";
            } else {
                // Jika data kosong, tetap tampilkan rentang awal & akhir bulan ini
                $formattedDateRange = Carbon::now()->startOfMonth()->format('d/m/Y') . ' - ' . Carbon::now()->endOfMonth()->format('d/m/Y');
            }
        } else {
            // Ambil data sesuai input tanggal user hanya untuk user yang sedang login
            $overtimes = Overtime::where('user_id', $user->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            // Format tanggal input menjadi dd/mm/yyyy
            $formattedStartDate = Carbon::parse($startDate)->format('d/m/Y');
            $formattedEndDate = Carbon::parse($endDate)->format('d/m/Y');
            $formattedDateRange = "{$formattedStartDate} - {$formattedEndDate}";
        }

        // Menghitung total jam lembur dan total harga lemburan
        $totalHours = $overtimes->sum('overtime_hours');
        $totalAmount = $overtimes->sum('total_overtime');

        return response()->json([
            'success' => true,
            'date_range' => $formattedDateRange,
            'total_hours' => $totalHours,
            'total_amount' => $totalAmount,
            'data' => $overtimes
        ], 200);
    }


    public function getReportById($id)
    {
        $overtime = Overtime::find($id);

        if (!$overtime) {
            return response()->json([
                'success' => false,
                'message' => 'Data not found'
            ], 404);
        }

        // // Hapus overtime_details sebelum mengembalikan response
        // $filteredOvertime = $overtime->toArray();
        // unset($filteredOvertime['overtime_details']);

        return response()->json([
            'success' => true,
            'data' => $overtime
        ], 200);
    }

    public function getOvertimeDetails($id)
    {
        // Cari data lembur berdasarkan ID dan pastikan milik user yang login
        $user = Auth::user();
        $overtime = Overtime::where('user_id', $user->id)->find($id);

        if (!$overtime) {
            return response()->json([
                'status' => 'error',
                'message' => 'Overtime record not found'
            ], 404);
        }

        // Decode JSON overtime_details
        $overtimeDetails = json_decode($overtime->overtime_details, true);

        return response()->json([
            'status' => 'success',
            'message' => 'Overtime details retrieved successfully',
            'date' => $overtime->date, // Ambil langsung dari database
            'day_type' => $overtime->day_type, // Ambil langsung dari database
            'overtime_hours' => $overtime->overtime_hours, // Ambil langsung dari database
            'total_overtime' => $overtime->total_overtime, // Ambil langsung dari database
            'data' => $overtimeDetails
        ], 200);
    }

    /**
     * Menghapus data lembur berdasarkan ID.
     * Endpoint: DELETE /api/overtime/{id}
     */
    public function destroy($id)
    {
        // Ambil data user dari token
        $user = Auth::user();

        // Cari data lembur berdasarkan ID dan pastikan milik user yang login
        $overtime = Overtime::where('user_id', $user->id)->find($id);

        if (!$overtime) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data lembur tidak ditemukan'
            ], 404);
        }

        // Hapus data lembur
        $overtime->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data lembur berhasil dihapus'
        ], 200);
    }



    /**
     * ✅ CALCULATED
     * Fungsi: Menghitung jam lembur
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'monthly_salary' => 'required|numeric',
            'day_type' => 'required|string',
            'working_days' => 'required|integer',
            'overtime_hours' => 'required|numeric|min:0'
        ]);

        $salary = $request->monthly_salary;
        $dayType = $request->day_type;
        $workingDays = $request->working_days;
        $hours = $request->overtime_hours;

        $result = $this->calculateOvertime($salary, $dayType, $workingDays, $hours);

        return response()->json([
            'status' => 'success',
            'message' => 'Overtime calculation successful!',
            'total_overtime' => $result['total_overtime'],
            'overtime_details' => $result['overtime_details']
        ]);
    }

    private function calculateOvertime($salary, $dayType, $workingDays, $hours)
    {
        $totalOvertime = 0;
        $overtimeDetails = [];

        $fullHours = floor($hours); // Jam bulat
        $extraHalfHour = ($hours - $fullHours) == 0.5; // Cek apakah ada tambahan 0.5 jam
        $lastRate = null;

        $formattedSalary = number_format($salary, 0, ',', '.'); // Format angka

        if ($dayType === 'regular') {
            for ($i = 1; $i <= $fullHours; $i++) {
                $rate = ($i == 1) ? 1.5 : 2;
                $currentOvertime = round(($salary * $rate) / 173);
                $formula = "1 x $formattedSalary x $rate / 173"; // Format salary

                $overtimeDetails[] = [
                    'formula' => $formula,
                    'result' => $currentOvertime
                ];
                $totalOvertime += $currentOvertime;
                $lastRate = $rate;
            }
        } elseif ($dayType === 'holiday') {
            for ($i = 1; $i <= $fullHours; $i++) {
                if ($workingDays == 5) {
                    $rate = ($i <= 8) ? 2 : (($i == 9) ? 3 : 4);
                } elseif ($workingDays == 6) {
                    $rate = ($i <= 7) ? 2 : (($i == 8) ? 3 : 4);
                }

                $currentOvertime = round(($salary * $rate) / 173);
                $formula = "1 x $formattedSalary x $rate / 173"; // Format salary

                $overtimeDetails[] = [
                    'formula' => $formula,
                    'result' => $currentOvertime
                ];
                $totalOvertime += $currentOvertime;
                $lastRate = $rate;
            }
        }

        // Jika ada tambahan 0.5 jam, gabungkan dengan jam terakhir jika rate-nya sama
        if ($extraHalfHour && $lastRate !== null) {
            $currentOvertime = round(($salary * $lastRate * 0.5) / 173);
            $formula = "0.5 x $formattedSalary x $lastRate / 173"; // Format salary

            $overtimeDetails[] = [
                'formula' => $formula,
                'result' => $currentOvertime
            ];
            $totalOvertime += $currentOvertime;
        }

        return [
            'total_overtime' => round($totalOvertime),
            'overtime_details' => $overtimeDetails
        ];
    }
}
