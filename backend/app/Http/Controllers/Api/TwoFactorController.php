<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
    protected TwoFactorAuthService $twoFactorService;

    public function __construct(TwoFactorAuthService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Obtener estado de 2FA del usuario.
     */
    public function status(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'enabled' => $user->google2fa_enabled,
            'verified_at' => $user->google2fa_verified_at,
            'required' => $this->twoFactorService->isRequired($user),
        ]);
    }

    /**
     * Iniciar configuración de 2FA (generar QR).
     */
    public function setup(Request $request)
    {
        $user = $request->user();

        if ($user->google2fa_enabled) {
            return response()->json([
                'message' => '2FA ya está habilitado. Primero debe deshabilitarlo.',
            ], 400);
        }

        $setup = $this->twoFactorService->setup($user);

        Auditoria::registrar('2fa_setup_iniciado', 'usuario', $user->id);

        return response()->json([
            'message' => 'Escanee el código QR con Google Authenticator',
            'secret' => $setup['secret'],
            'qr_code_svg' => $setup['qr_code_svg'],
            'qr_code_url' => $setup['qr_code_url'],
            'manual_entry_key' => $setup['secret'], // Para entrada manual
        ]);
    }

    /**
     * Habilitar 2FA (verificar código).
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verificar contraseña
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Contraseña incorrecta',
            ], 401);
        }

        if ($user->google2fa_enabled) {
            return response()->json([
                'message' => '2FA ya está habilitado',
            ], 400);
        }

        if (empty($user->google2fa_secret)) {
            return response()->json([
                'message' => 'Primero debe iniciar la configuración de 2FA',
            ], 400);
        }

        // Habilitar 2FA (verifica código internamente)
        if (!$this->twoFactorService->enable($user, $request->code)) {
            Auditoria::registrar('2fa_habilitacion_fallida', 'usuario', $user->id, [
                'razon' => 'codigo_invalido',
            ]);

            return response()->json([
                'message' => 'Código 2FA inválido',
            ], 401);
        }

        Auditoria::registrar('2fa_habilitado', 'usuario', $user->id);

        // Generar códigos de recuperación
        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        return response()->json([
            'message' => '2FA habilitado exitosamente',
            'recovery_codes' => $recoveryCodes,
            'warning' => 'Guarde estos códigos de recuperación en un lugar seguro. No se volverán a mostrar.',
        ]);
    }

    /**
     * Deshabilitar 2FA.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'code' => 'required_if:current_2fa_enabled,true|string|size:6',
        ]);

        $user = $request->user();

        // Verificar contraseña
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Contraseña incorrecta',
            ], 401);
        }

        if (!$user->google2fa_enabled) {
            return response()->json([
                'message' => '2FA no está habilitado',
            ], 400);
        }

        // Si 2FA está habilitado, verificar código antes de deshabilitar
        if ($user->google2fa_enabled) {
            $secret = $this->twoFactorService->getSecret($user);
            
            if (!$this->twoFactorService->verify($secret, $request->code)) {
                return response()->json([
                    'message' => 'Código 2FA inválido',
                ], 401);
            }
        }

        // Verificar si es obligatorio por rol
        if ($this->twoFactorService->isRequired($user)) {
            return response()->json([
                'message' => '2FA es obligatorio para su rol y no puede deshabilitarse',
            ], 403);
        }

        $this->twoFactorService->disable($user);

        Auditoria::registrar('2fa_deshabilitado', 'usuario', $user->id);

        return response()->json([
            'message' => '2FA deshabilitado exitosamente',
        ]);
    }

    /**
     * Verificar código 2FA (sin habilitar, solo test).
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (empty($user->google2fa_secret)) {
            return response()->json([
                'message' => 'Primero debe configurar 2FA',
            ], 400);
        }

        $secret = $this->twoFactorService->getSecret($user);
        $valid = $this->twoFactorService->verify($secret, $request->code);

        return response()->json([
            'valid' => $valid,
            'message' => $valid ? 'Código válido' : 'Código inválido',
        ]);
    }

    /**
     * Regenerar códigos de recuperación.
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verificar contraseña
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Contraseña incorrecta',
            ], 401);
        }

        if (!$user->google2fa_enabled) {
            return response()->json([
                'message' => '2FA debe estar habilitado',
            ], 400);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        Auditoria::registrar('2fa_codigos_regenerados', 'usuario', $user->id);

        return response()->json([
            'recovery_codes' => $recoveryCodes,
            'warning' => 'Guarde estos códigos en un lugar seguro. Los códigos anteriores ya no son válidos.',
        ]);
    }
}
