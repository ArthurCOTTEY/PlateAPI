<?php

namespace App\Http\Controllers;

use App\Models\Plate;
use App\Models\User;
use App\Models\PlateTransfersHistory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlateController extends Controller
{
    public function all(Request $request) {
        ApiLogsController::addLog($request);
        return Plate::where('user_id', Auth::id())->get();
    }

    public function create(Request $request) {
        ApiLogsController::addLog($request);
        return Plate::create([]);
    }

    public function transfer(Request $request) {
        ApiLogsController::addLog($request);
        $request->validate([
            'plate_id' => 'required|integer|exists:plates,id',
            'to_user_id' => 'required|integer|exists:users,id',
        ]);
        Plate::where('user_id', Auth::id())->find($request->plate_id)->update(['user_id' => $request->to_user_id]);
        PlateTransfersHistory::create([
            'plate_id' => $request->plate_id,
            'to_user_id' => $request->to_user_id,
        ]);
    }

    public function transferHistory(Request $request, Plate $plate) {
        ApiLogsController::addLog($request);
        abort_if($plate->user_id !== Auth::id(), 403);
        return $plate->plateTransfersHistory()->latest('transferred_at')->get();
    }

    public function show(Request $request, Plate $plate) {
        ApiLogsController::addLog($request);
        abort_if($plate->user_id !== Auth::id(), 403);
        return $plate;
    }

    public static function generate(): string
    {
        $lastPlate = Plate::select('license_plate_number')->orderBy('id','desc')->first();
        $finalPlateNumber = '';
        if ($lastPlate) {
            $arrPlate = explode('-', $lastPlate->license_plate_number);
            $lastLicensePlatePrefix = $arrPlate[0];
            $lastLicensePlateNumber = (int)$arrPlate[1];
            $lastLicensePlateSuffix = $arrPlate[2];
            $finalLicensePlatePrefix = '';
            $finalLicensePlateNumber = '';
            $finalLicensePlateSuffix = '';
            if ($lastLicensePlateNumber === 999) {
                $finalLicensePlateNumber = '001';
                $arrLetter = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                if ($lastLicensePlateSuffix !== 'ZZ') {
                    $finalLicensePlatePrefix = $lastLicensePlatePrefix;
                    if ($lastLicensePlateSuffix[1] === 'Z') {
                        $nextLetter = $arrLetter[strpos($arrLetter, $lastLicensePlateSuffix[0]) + 1];
                        $finalLicensePlateSuffix = $nextLetter . 'A';
                    } else {
                        $nextLetter = $arrLetter[strpos($arrLetter, $lastLicensePlateSuffix[1]) + 1];
                        $finalLicensePlateSuffix = $lastLicensePlateSuffix[0] . $nextLetter;
                    }
                } else {
                    $finalLicensePlateSuffix = 'AA';
                    if ($lastLicensePlatePrefix !== 'ZZ') {
                        if ($lastLicensePlatePrefix[1] === 'Z') {
                            $nextLetter = $arrLetter[strpos($arrLetter, $lastLicensePlatePrefix[0]) + 1];
                            $finalLicensePlatePrefix = $nextLetter . 'A';
                        } else {
                            $nextLetter = $arrLetter[strpos($arrLetter, $lastLicensePlatePrefix[1]) + 1];
                            $finalLicensePlatePrefix = $lastLicensePlatePrefix[0] . $nextLetter;
                        }
                    } else {
                        throw new \Exception('Maximum plate number reached.');
                    }
                }
            } else {
                $finalLicensePlatePrefix = $lastLicensePlatePrefix;
                $finalLicensePlateNumber = $lastLicensePlateNumber + 1;
                $finalLicensePlateSuffix = $lastLicensePlateSuffix;
            }
            $finalPlateNumber = $finalLicensePlatePrefix . '-' . str_pad($finalLicensePlateNumber, 3, '0', STR_PAD_LEFT) . '-' . $finalLicensePlateSuffix;
        } else {
            $finalPlateNumber = 'AA-001-AA';
        }
        return $finalPlateNumber;
    }
}
