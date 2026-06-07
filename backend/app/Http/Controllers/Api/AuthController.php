<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        abort(404);
    }

    public function login(Request $request, AuditLogger $audit): JsonResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $usuario = User::where('email', $dados['email'])->first();

        if (! $usuario || ! Hash::check($dados['password'], $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        $token = $usuario->createToken('clinic-token')->plainTextToken;
        $request->setUserResolver(fn () => $usuario);
        $audit->log($request, 'auth.login');

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'user' => $usuario->load('clinic', 'roles', 'permissions'),
            'token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('clinic', 'roles', 'permissions'),
        ]);
    }

    public function logout(Request $request, AuditLogger $audit): JsonResponse
    {
        $audit->log($request, 'auth.logout');
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }
}
