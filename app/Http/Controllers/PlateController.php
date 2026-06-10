<?php

namespace App\Http\Controllers;

use App\Models\Plate;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class PlateController extends Controller
{
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
