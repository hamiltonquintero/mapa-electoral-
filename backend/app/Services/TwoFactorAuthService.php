<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Crypt;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorAuthService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generar secreto 2FA para un usuario.
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Obtener URL para código QR.
     */
    public function getQRCodeUrl(User $user, string $secret): string
    {
        $issuer = config('auth.2fa.issuer', config('app.name'));
        $email = $user->email;

        return $this->google2fa->getQRCodeUrl(
            $issuer,
            $email,
            $secret
        );
    }

    /**
     * Generar código QR en formato SVG.
     */
    public function getQRCodeSvg(User $user, string $secret): string
    {
        $url = $this->getQRCodeUrl($user, $secret);

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        
        return $writer->writeString($url);
    }

    /**
     * Verificar código 2FA.
     */
    public function verify(string $secret, string $code): bool
    {
        $window = config('auth.2fa.window', 4);

        return $this->google2fa->verifyKey($secret, $code, $window);
    }

    /**
     * Habilitar 2FA para un usuario.
     */
    public function enable(User $user, string $code): bool
    {
        if (empty($user->google2fa_secret)) {
            return false;
        }

        // Verificar código antes de habilitar
        if (!$this->verify($user->google2fa_secret, $code)) {
            return false;
        }

        $user->update([
            'google2fa_enabled' => true,
            'google2fa_verified_at' => now(),
        ]);

        return true;
    }

    /**
     * Deshabilitar 2FA para un usuario.
     */
    public function disable(User $user): void
    {
        $user->update([
            'google2fa_enabled' => false,
            'google2fa_secret' => null,
            'google2fa_verified_at' => null,
        ]);
    }

    /**
     * Configurar 2FA inicial (generar secreto).
     */
    public function setup(User $user): array
    {
        $secret = $this->generateSecret();

        $user->update([
            'google2fa_secret' => Crypt::encryptString($secret),
            'google2fa_enabled' => false,
        ]);

        return [
            'secret' => $secret,
            'qr_code_url' => $this->getQRCodeUrl($user, $secret),
            'qr_code_svg' => $this->getQRCodeSvg($user, $secret),
        ];
    }

    /**
     * Obtener secreto descifrado de un usuario.
     */
    public function getSecret(User $user): ?string
    {
        if (empty($user->google2fa_secret)) {
            return null;
        }

        try {
            return Crypt::decryptString($user->google2fa_secret);
        } catch (\Exception $e) {
            \Log::error('Error descifrando secreto 2FA', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verificar si el usuario requiere 2FA según su rol.
     */
    public function isRequired(User $user): bool
    {
        // 2FA obligatorio para Superadmin y Admin de Campaña
        $requiredRoles = [
            'Superadministrador',
            'Administrador de Campaña',
        ];

        return $user->hasAnyRole($requiredRoles);
    }

    /**
     * Generar códigos de recuperación.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
        }

        return $codes;
    }

    /**
     * Verificar código de recuperación.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        // Los códigos de recuperación se guardarían en una tabla separada
        // Por ahora retornamos false
        return false;
    }
}
