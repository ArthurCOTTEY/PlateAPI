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
    /**
     * Lister les plaques
     *
     * Retourne la liste paginée des plaques d’immatriculation appartenant à l’utilisateur connecté.
     *
     * @authenticated
     *
     * @queryParam page integer Numéro de la page à récupérer. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "license_plate_number": "AA-001-AA",
     *       "created_at": "2026-06-10T12:00:00.000000Z",
     *       "updated_at": "2026-06-10T12:00:00.000000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost:8000/api/plates/all?page=1",
     *     "last": "http://localhost:8000/api/plates/all?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "links": [],
     *     "path": "http://localhost:8000/api/plates/all",
     *     "per_page": 10,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function all(Request $request) {
        ApiLogsController::addLog($request);
        return PlateResource::collection(Plate::where('user_id', Auth::id())->paginate(10));
    }

    /**
     * Afficher une plaque
     *
     * Retourne les informations d’une plaque appartenant à l’utilisateur connecté.
     *
     * L’utilisateur ne peut consulter que ses propres plaques.
     *
     * @authenticated
     *
     * @urlParam plate integer required Identifiant de la plaque. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "license_plate_number": "AA-001-AA",
     *     "created_at": "2026-06-10T12:00:00.000000Z",
     *     "updated_at": "2026-06-10T12:00:00.000000Z"
     *   }
     * }
     *
     * @response 404 {
     *   "message": "Plate not found"
     * }
     */
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

    /**
     * Créer une plaque d’immatriculation
     *
     * Crée une nouvelle plaque d’immatriculation pour l’utilisateur connecté.
     *
     * Aucun paramètre n’est nécessaire dans le body de la requête.
     * Le numéro de plaque est généré automatiquement côté serveur au format `AA-001-AA`.
     *
     * @authenticated
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "license_plate_number": "AA-001-AA",
     *     "created_at": "2026-06-10T12:00:00.000000Z",
     *     "updated_at": "2026-06-10T12:00:00.000000Z"
     *   }
     * }
     */
    public function create(Request $request) {
        $plate = Plate::create([]);
        ApiLogsController::addLog($request);
        return new PlateResource($plate);
    }

    /**
     * Transférer une plaque
     *
     * Transfère une plaque appartenant à l’utilisateur connecté vers un autre utilisateur.
     *
     * L’utilisateur connecté doit être propriétaire de la plaque pour pouvoir la transférer.
     * Une entrée est automatiquement ajoutée dans l’historique des transferts.
     *
     * @authenticated
     *
     * @bodyParam plate_id integer required Identifiant de la plaque à transférer. Example: 1
     * @bodyParam to_user_id integer required Identifiant de l’utilisateur qui recevra la plaque. Example: 2
     *
     * @response 200 {
     *   "message": "Plate transferred successfully",
     *   "plate_id": 1,
     *   "license_plate_number": "AA-001-AA"
     * }
     *
     * @response 404 {
     *   "message": "Plate not found"
     * }
     *
     * @response 422 {
     *   "message": "The plate id field is required.",
     *   "errors": {
     *     "plate_id": [
     *       "The plate id field is required."
     *     ]
     *   }
     * }
     */
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

    /**
     * Afficher l’historique des transferts d’une plaque
     *
     * Retourne l’historique des transferts d’une plaque appartenant à l’utilisateur connecté.
     *
     * L’historique est trié du transfert le plus récent au plus ancien.
     *
     * @authenticated
     *
     * @urlParam plate integer required Identifiant de la plaque. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "plate": {
     *         "data": {
     *           "id": 1,
     *           "license_plate_number": "AA-001-AA",
     *           "created_at": "2026-06-10T12:00:00.000000Z",
     *           "updated_at": "2026-06-10T12:00:00.000000Z"
     *         }
     *       },
     *       "from_user": {
     *         "data": {
     *           "id": 1,
     *           "name": "Admin",
     *           "email": "admin@example.com"
     *         }
     *       },
     *       "to_user": {
     *         "data": {
     *           "id": 2,
     *           "name": "User",
     *           "email": "user@example.com"
     *         }
     *       },
     *       "transferred_at": "2026-06-10T12:00:00.000000Z"
     *     }
     *   ]
     * }
     *
     * @response 404 {
     *   "message": "Plate not found"
     * }
     */
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
