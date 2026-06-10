<?php

namespace App\Http\Controllers;

use App\Http\Resources\TokenResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    /**
     * Créer un token d’accès
     *
     * Permet de créer un token Sanctum à partir de l’adresse email, du mot de passe
     * et du nom de l’application utilisée.
     *
     * Cette route est publique et ne nécessite pas de token d’authentification.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Adresse email de l’utilisateur. Example: admin@example.com
     * @bodyParam password string required Mot de passe de l’utilisateur. Example: password
     * @bodyParam app_name string required Nom de l’application ou du client utilisant l’API. Example: PlateAPI
     *
     * @response 201 {
     *   "token": "1|abcdef123456789..."
     * }
     *
     * @response 422 {
     *   "message": "The provided credentials are incorrect.",
     *   "errors": {
     *     "email": [
     *       "The provided credentials are incorrect."
     *     ]
     *   }
     * }
     */
    public function create(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'app_name' => 'required',
        ]);
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        ApiLogsController::addLog($request, $user->email, $user->id);
        return response()->json([
            'token' => $user->createToken($request->app_name)->plainTextToken,
        ], 201);
    }

    /**
     * Lister les sessions actives
     *
     * Retourne la liste des tokens actifs de l’utilisateur connecté.
     *
     * @authenticated
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "PlateAPI",
     *       "created_at": "2026-06-10T12:00:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function sessions(Request $request) {
        return TokenResource::collection(Auth::user()->tokens()->get());
    }

    /**
     * Supprimer le token actuel
     *
     * Supprime uniquement le token actuellement utilisé pour authentifier la requête.
     *
     * Cette route permet de déconnecter l’utilisateur de la session courante.
     *
     * @authenticated
     *
     * @response 204
     */
    public function destroy(Request $request) {
        ApiLogsController::addLog($request);
        Auth::user()->currentAccessToken()->delete();
        return response()->noContent();
    }

    /**
     * Supprimer tous les tokens
     *
     * Supprime tous les tokens associés à l’utilisateur connecté.
     *
     * Cette route permet de déconnecter l’utilisateur de tous ses appareils ou applications.
     *
     * @authenticated
     *
     * @response 204
     */
    function destroyAll(Request $request) {
        ApiLogsController::addLog($request);
        Auth::user()->tokens()->delete();
        return response()->noContent();
    }
}
