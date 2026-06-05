<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Autenticar al usuario y retornar un token Bearer.
     */
    public function login(Request $request)
    {
        // 1. Validar que los campos de correo y contraseña vengan en la petición HTTP
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Buscar si el correo electrónico está registrado en PostgreSQL
        $user = User::where('email', $request->email)->first();

        // 3. Verificar la existencia del usuario y la validez de la contraseña contra el hash seguro
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Las credenciales proporcionadas son incorrectas.'
            ], 401);
        }

        // 4. Generar el token API único sin estado para este usuario
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Retornar la respuesta con el token listo para ser usado
        return response()->json([
            'message' => 'Autenticación exitosa',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'name' => $user->name,
                'email' => $user->email
            ]
        ], 200);
    }

    /**
     * Revocar el token del usuario actual para cerrar la sesión.
     */
    public function logout(Request $request)
    {
        // Se borra el token actual con el que el usuario firmó esta solicitud de red
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.'
        ], 200);
    }
}