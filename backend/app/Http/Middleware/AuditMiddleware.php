<?php

namespace App\Http\Middleware;

use App\Models\Auditoria;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que registra automáticamente acciones sensibles en la tabla de auditoría.
 * 
 * Registra:
 * - Método HTTP y ruta
 * - Usuario que ejecuta la acción
 * - IP del cliente y User-Agent
 * - Datos relevantes de la petición (sanitizados)
 * - Cambios realizados (antes/después)
 */
class AuditMiddleware
{
    /**
     * Rutas que deben ser auditadas.
     * Se evalúan como patrones regex.
     */
    private array $auditableRoutes = [
        'simpatizantes',
        'usuarios',
        'territorios',
        'campanas',
        'importaciones',
        'exportaciones',
        'lotes',
        '2fa',
    ];

    /**
     * Acciones HTTP que deben ser auditadas
     */
    private array $auditableMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Campos sensibles que NO deben guardarse en datos_relevantes
     */
    private array $sensitiveFields = [
        'password',
        'password_confirmation',
        'current_password',
        'two_factor_secret',
        'recovery_codes',
        'token',
        'api_token',
        'remember_token',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo auditar si la ruta es auditable
        if ($this->shouldAudit($request)) {
            $this->logAudit($request, $response);
        }

        return $response;
    }

    /**
     * Determinar si la petición debe ser auditada
     */
    private function shouldAudit(Request $request): bool
    {
        // Solo auditar métodos específicos
        if (!in_array($request->method(), $this->auditableMethods)) {
            return false;
        }

        // Verificar si la ruta coincide con alguna auditable
        $path = $request->path();
        
        foreach ($this->auditableRoutes as $pattern) {
            if (str_contains($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registrar la acción en la tabla de auditoría
     */
    private function logAudit(Request $request, Response $response): void
    {
        try {
            $user = $request->user();
            
            // Preparar datos relevantes (sin campos sensibles)
            $datosRelevantes = $this->sanitizeData($request->all());

            // Determinar el tipo de acción
            $accion = $this->determineAction($request);

            // Obtener tabla e ID afectados (si aplica)
            [$tablaAfectada, $registroId] = $this->extractAffectedResource($request, $response);

            Auditoria::create([
                'usuario_id' => $user?->id,
                'accion' => $accion,
                'tabla_afectada' => $tablaAfectada,
                'registro_id' => $registroId,
                'datos_relevantes' => $datosRelevantes,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'resultado' => $response->isSuccessful() ? 'exitoso' : 'fallido',
                'codigo_http' => $response->getStatusCode(),
            ]);

        } catch (\Exception $e) {
            // Si falla la auditoría, registrar en log pero no interrumpir la petición
            Log::error('Error al registrar auditoría', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'user' => $request->user()?->id,
                ]
            ]);
        }
    }

    /**
     * Sanitizar datos removiendo campos sensibles
     */
    private function sanitizeData(array $data): array
    {
        foreach ($this->sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        // Limitar campos cifrados
        if (isset($data['cedula'])) {
            $data['cedula'] = substr($data['cedula'], 0, 4) . '****';
        }

        return $data;
    }

    /**
     * Determinar el tipo de acción basándose en la ruta y método
     */
    private function determineAction(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();

        // Mapeo de rutas especiales
        if (str_contains($path, '2fa')) {
            if (str_contains($path, 'enable')) return '2fa_habilitado';
            if (str_contains($path, 'disable')) return '2fa_deshabilitado';
            if (str_contains($path, 'verify')) return '2fa_verificado';
        }

        if (str_contains($path, 'importacion')) {
            return 'importacion_' . strtolower($method);
        }

        if (str_contains($path, 'exportacion')) {
            return 'exportacion_creada';
        }

        // Mapeo por método HTTP
        return match($method) {
            'POST' => $this->getResourceName($path) . '_creado',
            'PUT', 'PATCH' => $this->getResourceName($path) . '_actualizado',
            'DELETE' => $this->getResourceName($path) . '_eliminado',
            default => 'accion_' . strtolower($method),
        };
    }

    /**
     * Extraer el nombre del recurso de la ruta
     */
    private function getResourceName(string $path): string
    {
        // Extraer el recurso principal de la ruta
        // Ej: api/v1/simpatizantes/123 -> simpatizante
        $segments = explode('/', $path);
        
        foreach ($this->auditableRoutes as $route) {
            if (str_contains($path, $route)) {
                // Convertir plural a singular aproximado
                return rtrim($route, 's');
            }
        }

        return 'recurso';
    }

    /**
     * Extraer tabla e ID del recurso afectado de la respuesta
     */
    private function extractAffectedResource(Request $request, Response $response): array
    {
        $tabla = null;
        $id = null;

        // Intentar obtener de la ruta
        $path = $request->path();
        
        // Mapeo de rutas a tablas
        $tableMap = [
            'simpatizantes' => 'simpatizantes',
            'usuarios' => 'users',
            'territorios' => 'territorios',
            'campanas' => 'campanas',
            'candidatos' => 'candidatos',
            'importaciones' => 'importaciones',
            'exportaciones' => 'exportaciones',
            'lotes' => 'lotes',
        ];

        foreach ($tableMap as $route => $tableName) {
            if (str_contains($path, $route)) {
                $tabla = $tableName;
                
                // Intentar extraer ID de la URL
                preg_match('/\/' . $route . '\/([a-f0-9-]+)/', $path, $matches);
                if (!empty($matches[1])) {
                    $id = $matches[1];
                }
                break;
            }
        }

        // Si es POST, intentar obtener ID de la respuesta
        if ($request->method() === 'POST' && $response instanceof \Illuminate\Http\JsonResponse) {
            $content = json_decode($response->getContent(), true);
            if (isset($content['data']['id'])) {
                $id = $content['data']['id'];
            }
        }

        return [$tabla, $id];
    }
}
