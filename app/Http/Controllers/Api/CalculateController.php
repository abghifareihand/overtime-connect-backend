<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Calculate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalculateController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'monthly_salary' => 'required|numeric',
            'day_type' => 'required|string',
            'working_days' => 'required|integer',
            'overtime_hours' => 'required|numeric|min:0'
        ]);

        $user = Auth::user();
        $salary = $request->monthly_salary;
        $dayType = $request->day_type;
        $workingDays = $request->working_days;
        $hours = $request->overtime_hours;

        $totalOvertime = 0;
        $overtimeDetails = [];
        $overtimeFormulas = [];

        if ($dayType === 'holiday') {
            if ($workingDays == 5) {
                for ($i = 1; $i <= $hours; $i++) {
                    if ($i <= 8) {
                        $currentOvertime = ($salary * 2) / 173;
                        $formula = "$salary x 2 / 173";
                    } else if ($i == 9) {
                        $currentOvertime = ($salary * 3) / 173;
                        $formula = "$salary x 3 / 173";
                    } else {
                        $currentOvertime = ($salary * 4) / 173;
                        $formula = "$salary x 4 / 173";
                    }
                    $overtimeDetails[] = $currentOvertime;
                    $overtimeFormulas[] = $formula;
                    $totalOvertime += $currentOvertime;
                }
            } else if ($workingDays == 6) {
                for ($i = 1; $i <= $hours; $i++) {
                    if ($i <= 7) {
                        $currentOvertime = ($salary * 2) / 173;
                        $formula = "$salary x 2 / 173";
                    } else if ($i == 8) {
                        $currentOvertime = ($salary * 3) / 173;
                        $formula = "$salary x 3 / 173";
                    } else {
                        $currentOvertime = ($salary * 4) / 173;
                        $formula = "$salary x 4 / 173";
                    }
                    $overtimeDetails[] = $currentOvertime;
                    $overtimeFormulas[] = $formula;
                    $totalOvertime += $currentOvertime;
                }
            }
        } else if ($dayType === 'regular') {
            for ($i = 1; $i <= $hours; $i++) {
                if ($i == 1) {
                    $currentOvertime = ($salary * 1.5) / 173;
                    $formula = "$salary x 1.5 / 173";
                } else {
                    $currentOvertime = ($salary * 2) / 173;
                    $formula = "$salary x 2 / 173";
                }
                $overtimeDetails[] = $currentOvertime;
                $overtimeFormulas[] = $formula;
                $totalOvertime += $currentOvertime;
            }
        }


        // Cek apakah sudah ada data lembur di tanggal yang sama
        $existingCalculate = Calculate::where('user_id', $user->id)
            ->whereDate('date', $request->date)
            ->exists();

        if ($existingCalculate) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already recorded overtime for this date.',
            ], 422);
        }

        // Simpan data tanpa pembulatan
        Calculate::create([
            'user_id' => $user->id,
            'date' => $request->date,
            'total_overtime' => $totalOvertime,
            'overtime_details' => json_encode($overtimeDetails, JSON_NUMERIC_CHECK),
            'overtime_formulas' => json_encode($overtimeFormulas, JSON_UNESCAPED_SLASHES)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Overtime calculation stored successfully!',
        ]);
    }

    public function getCalculate()
    {
        $user = Auth::user();
        $calculations = Calculate::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->get(['id', 'date', 'total_overtime']); // Ambil hanya 3 field

        return response()->json([
            'status' => 'success',
            'message' => 'Overtime calculations retrieved successfully!',
            'data' => $calculations
        ]);
    }


    public function getCalculateById($id)
    {
        $user = Auth::user();
        $calculate = Calculate::where('user_id', $user->id)->where('id', $id)->first();

        if (!$calculate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Calculation not found.',
            ], 404);
        }

        $overtimeDetails = json_decode($calculate->overtime_details, true);
        $overtimeFormulas = json_decode($calculate->overtime_formulas, true);

        $formattedDetails = [];
        foreach ($overtimeFormulas as $key => $formula) {
            $formattedDetails[] = [
                'formula' => $formula,
                'result' => $overtimeDetails[$key] ?? 0
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Calculation details retrieved successfully!',
            'data' => [
                'id' => $calculate->id,
                'date' => $calculate->date,
                'total_overtime' => $calculate->total_overtime,
                'overtime_details' => $formattedDetails
            ]
        ]);
    }
}
