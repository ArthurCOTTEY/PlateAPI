<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlateResource;
use App\Http\Resources\PlateTransfersHistoriesResource;
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
        return PlateResource::collection(Plate::where('user_id', Auth::id())->paginate(10));
    }

    public function show(Request $request, int $id)
    {
        $plate = Plate::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();
        if (!$plate) {
            return response()->json([
                'message' => 'Plate not found'
            ], 404);
        }
        ApiLogsController::addLog($request);
        return new PlateResource($plate);
    }

    public function create(Request $request) {
        $plate = Plate::create([]);
        ApiLogsController::addLog($request);
        return new PlateResource($plate);
    }

    public function transfer(Request $request) {
        $request->validate([
            'plate_id' => 'required|integer|exists:plates,id',
            'to_user_id' => 'required|integer|exists:users,id',
        ]);
        $plate = Plate::where('user_id', Auth::id())->find($request->plate_id);
        if (!$plate) {
            return response()->json([
                'message' => 'Plate not found'
            ], 404);
        }
        $plate->user_id = $request->to_user_id;
        $plate->save();
        PlateTransfersHistory::create([
            'plate_id' => $request->plate_id,
            'to_user_id' => $request->to_user_id,
        ]);
        ApiLogsController::addLog($request);
        return response()->json([
            'message' => 'Plate transferred successfully',
            'plate_id' => $plate->id,
            'license_plate_number' => $plate->license_plate_number,
        ], 200);
    }

    public function transferHistory(Request $request, int $id)
    {
        $plate = Plate::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();
        if (!$plate) {
            return response()->json([
                'message' => 'Plate not found',
            ], 404);
        }
        ApiLogsController::addLog($request);
        return PlateTransfersHistoriesResource::collection(
            $plate->plateTransfersHistory()
                ->latest('transferred_at')
                ->get()
        );
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
