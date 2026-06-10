<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiLogsResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ApiLogs;

class AccountController extends Controller
{
    /**
     * Afficher le compte utilisateur
     *
     * Retourne les informations de l’utilisateur actuellement connecté.
     *
     * @authenticated
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "Admin",
     *     "email": "admin@example.com"
     *   }
     * }
     */
    public function show(Request $request) {
        ApiLogsController::addLog($request);
        return new userResource(Auth::user());
    }

    /**
     * Afficher l’historique des actions
     *
     * Retourne l’historique des actions API effectuées par l’utilisateur connecté.
     * Les résultats sont paginés par groupes de 10 éléments.
     *
     * @authenticated
     *
     * @queryParam page integer Numéro de la page à récupérer. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "method": "GET",
     *       "ip": "127.0.0.1",
     *       "email": "admin@example.com",
     *       "at": "2026-06-10T12:00:00.000000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost:8000/api/account/actions?page=1",
     *     "last": "http://localhost:8000/api/account/actions?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "links": [],
     *     "path": "http://localhost:8000/api/account/actions",
     *     "per_page": 10,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function actions(Request $request) {
        ApiLogsController::addLog($request);
        return ApiLogsResource::collection(Auth::user()->apiLogs()->latest()->paginate(10));
    }

    /**
     * Mettre à jour le compte utilisateur
     *
     * Permet de modifier les informations du compte de l’utilisateur connecté.
     * Tous les champs sont optionnels : seuls les champs envoyés seront mis à jour.
     *
     * Si l’adresse email est modifiée, les anciens logs API de l’utilisateur sont également mis à jour avec la nouvelle adresse email.
     *
     * @authenticated
     *
     * @bodyParam name string Nom de l’utilisateur. Doit contenir au moins 1 caractère. Example: Admin Updated
     * @bodyParam email string Adresse email de l’utilisateur. Doit être une adresse email valide. Example: admin.updated@example.com
     * @bodyParam password string Nouveau mot de passe de l’utilisateur. Doit contenir au moins 8 caractères. Example: password123
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "name": "Admin Updated",
     *     "email": "admin.updated@example.com"
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The password field must be at least 8 characters.",
     *   "errors": {
     *     "password": [
     *       "The password field must be at least 8 characters."
     *     ]
     *   }
     * }
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string|min:1',
            'email' => 'sometimes|email',
            'password' => 'sometimes|string|min:8',
        ]);

        $user = Auth::user();

        $emailChanged = $request->filled('email') && $request->email !== $user->email;

        if ($request->filled('name') && $request->name !== $user->name) {
            $user->name = $request->name;
        }

        if ($emailChanged) {
            $user->email = $request->email;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if ($emailChanged) {
            ApiLogs::where('user_id', $user->id)->update([
                'email' => $user->email,
            ]);
        }

        ApiLogsController::addLog($request);

        return new UserResource($user);
    }
}
