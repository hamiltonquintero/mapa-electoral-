<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected TwoFactorAuthService $twoFactorService;

    public function __construct(TwoFactorAuthService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Login (paso 1: usuario y contraseña).
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Rate limiting por IP
        $key = 'login.' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'message' => "Demasiados intentos. Intente de nuevo en {$seconds} segundos.",
            ], 429);
        }

        $user = User::where('email', $request->email)->first();

        // Verificar usuario existe
        if (!$user) {
            RateLimiter::hit($key, 60);
            
            Auditoria::registrar('login_fallido', 'usuario', null, [
                'email' => $request->email,
                'razon' => 'usuario_no_existe',
            ]);

            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        // Verificar usuario activo
        if (!$user->activo) {
            RateLimiter::hit($key, 60);
            
            Auditoria::registrar('login_fallido', 'usuario', $user->id, [
                'razon' => 'usuario_inactivo',
            ]);

            return response()->json([
                'message' => 'Usuario inactivo. Contacte al administrador.',
            ], 423);
        }

        // Verificar bloqueo temporal
        if ($user->estaBloqueado()) {
            RateLimiter::hit($key, 60);
            
            $hasta = $user->bloqueado_hasta->diffForHumans();
            
            return response()->json([
                'message' => "Cuenta bloqueada temporalmente. Intente de nuevo {$hasta}.",
            ], 403);
        }

        // Verificar contraseña
        if (!Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 60);
            
            // Incrementar intentos fallidos
            $user->increment('intentos_fallidos');
            
            // Bloquear después de 5 intentos
            if ($user->intentos_fallidos >= 5) {
                $user->update([
                    'bloqueado_hasta' => now()->addMinutes(30),
                ]);
                
                Auditoria::registrar('usuario_bloqueado', 'usuario', $user->id, [
                    'razon' => 'intentos_fallidos',
                ]);

                return response()->json([
                    'message' => 'Cuenta bloqueada por múltiples intentos fallidos. Intente en 30 minutos.',
                ], 403);
            }

            Auditoria::registrar('login_fallido', 'usuario', $user->id, [
                'razon' => 'contraseña_incorrecta',
                'intentos' => $user->intentos_fallidos,
            ]);

            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        // Limpiar rate limiter
        RateLimiter::clear($key);

        // Resetear intentos fallidos
        $user->update([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        // Si tiene 2FA habilitado, no generar token aún
        if ($user->google2fa_enabled) {
            // Guardar temporalmente el ID del usuario en sesión
            session(['pending_2fa_user_id' => $user->id]);
            
            Auditoria::registrar('login_pendiente_2fa', 'usuario', $user->id);

            return response()->json([
                'message' => 'Verificación 2FA requerida',
                'requires_2fa' => true,
            ]);
        }

        // Login exitoso sin 2FA
        return $this->completeLogin($user);
    }

    /**
     * Verificar código 2FA (paso 2).
     */
    public function verify2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $userId = session('pending_2fa_user_id');

        if (!$userId) {
            return response()->json([
                'message' => 'No hay sesión 2FA pendiente',
            ], 400);
        }

        $user = User::find($userId);

        if (!$user || !$user->google2fa_enabled) {
            session()->forget('pending_2fa_user_id');
            return response()->json([
                'message' => 'Usuario inválido',
            ], 400);
        }

        // Obtener secreto
        $secret = $this->twoFactorService->getSecret($user);

        if (!$secret) {
            return response()->json([
                'message' => 'Error al verificar 2FA',
            ], 500);
        }

        // Verificar código
        if (!$this->twoFactorService->verify($secret, $request->code)) {
            Auditoria::registrar('2fa_fallido', 'usuario', $user->id);

            return response()->json([
                'message' => 'Código 2FA inválido',
            ], 401);
        }

        // Marcar sesión como verificada
        session(['2fa_verified' => true]);
        session()->forget('pending_2fa_user_id');

        return $this->completeLogin($user);
    }

    /**
     * Completar login y generar token.
     */
    protected function completeLogin(User $user)
    {
        // Generar token Sanctum
        $token = $user->createToken('auth-token', ['*'], now()->addHours(8))->plainTextToken;

        // Actualizar último acceso
        $user->update([
            'ultimo_acceso' => now(),
            'ultima_ip' => request()->ip(),
        ]);

        Auditoria::registrar('login_exitoso', 'usuario', $user->id);

        return response()->json([
            'message' => 'Login exitoso',
            'user' => [
                'id' => $user->id,
                'nombres' => $user->nombres,
                'apellidos' => $user->apellidos,
                'email' => $user->email,
                'nombre_completo' => $user->nombre_completo,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'google2fa_enabled' => $user->google2fa_enabled,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Revocar token actual
        if ($request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        // Limpiar sesión
        session()->flush();

        Auditoria::registrar('logout', 'usuario', $user->id);

        return response()->json([
            'message' => 'Logout exitoso',
        ]);
    }

    /**
     * Obtener usuario actual.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        $userData = [
            'id' => $user->id,
            'nombres' => $user->nombres,
            'apellidos' => $user->apellidos,
            'email' => $user->email,
            'name' => $user->name,
            'nombre_completo' => $user->nombre_completo,
            'activo' => $user->activo,
            'ultimo_acceso' => $user->ultimo_acceso,
            'google2fa_enabled' => $user->google2fa_enabled,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'campanas' => $user->campanas->map(fn($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
            ]),
            'territorios' => $user->territorios->map(fn($t) => [
                'id' => $t->id,
                'nombre' => $t->nombre,
                'tipo' => $t->tipo,
            ]),
        ];

        return response()->json([
            'message' => 'Usuario autenticado',
            'data' => $userData,
            'user' => $userData,
        ]);
    }

    /**
     * Refrescar token.
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        // Revocar token actual
        if ($request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        // Generar nuevo token
        $token = $user->createToken('auth-token', ['*'], now()->addHours(8))->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }
}
