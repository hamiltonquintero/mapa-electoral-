# Frontend — Mapa Electoral Caldas

Interfaz web del sistema de georreferenciación electoral. Construida con Vue 3 + Vite + Tailwind CSS + MapLibre GL JS.

## Stack

| Tecnología | Versión | Uso |
|---|---|---|
| Vue 3 | ^3.4 | Framework reactivo (Composition API) |
| Vite | ^5.1 | Bundler y dev server |
| Pinia | ^2.1 | Gestión de estado |
| Vue Router | ^4.3 | Enrutamiento SPA |
| Tailwind CSS | ^3.4 | Utilidades CSS |
| MapLibre GL JS | ^4.1 | Mapa interactivo vectorial |
| Axios | ^1.6 | Cliente HTTP |
| Chart.js | ^4.4 | Gráficas |
| Day.js | ^1.11 | Fechas |
| @vueuse/core | ^10.9 | Composables utilitarios |
| @heroicons/vue | ^2.1 | Iconografía |

## Instalación

```bash
cd frontend
npm install
```

## Desarrollo

```bash
npm run dev
# http://localhost:5173
```

El dev server proxea `/api/*` hacia `http://localhost:8000` (backend Laravel).

## Producción

```bash
npm run build
# Genera dist/ que se copia a backend/public/app/
```

## Estructura

```
src/
├── assets/
│   └── main.css          # Estilos Tailwind + variables
├── components/
│   ├── auditoria/        # Badges y modales de auditoría
│   ├── layout/           # Sidebar, Header, NavItem
│   ├── mapa/             # MapaInteractivo, PanelLateral, Breadcrumb, etc.
│   ├── simpatizantes/    # EstadoBadge, DetalleField
│   └── ui/               # Componentes reutilizables (Button, Toast, Pagination…)
├── layouts/
│   └── AppLayout.vue     # Shell principal (sidebar + header)
├── router/
│   └── index.js          # Rutas + guards de autenticación/permisos
├── services/
│   ├── api.js            # Instancia Axios configurada
│   ├── authService.js    # Login, 2FA, logout
│   ├── mapaService.js    # Endpoints del mapa
│   ├── simpatizanteService.js
│   └── estadisticasService.js
├── stores/
│   ├── auth.js           # Sesión y permisos
│   ├── mapa.js           # Estado del mapa (navegación, GeoJSON, filtros)
│   └── toast.js          # Notificaciones globales
└── views/
    ├── auth/             # Login, 2FA
    ├── mapa/             # Vista principal del mapa
    ├── simpatizantes/    # Lista, detalle, formulario
    ├── importacion/      # Asistente de importación por pasos
    ├── auditoria/        # Logs de auditoría con filtros
    └── perfil/           # Perfil de usuario y configuración 2FA
```

## Pantallas implementadas

| Ruta | Vista | Descripción |
|---|---|---|
| `/login` | LoginView | Login con correo/contraseña |
| `/login/2fa` | TwoFactorView | Verificación TOTP o código de recuperación |
| `/mapa` | MapaView | **Mapa principal** con navegación drill-down |
| `/simpatizantes` | SimpatizantesView | Listado con filtros y paginación |
| `/simpatizantes/nuevo` | SimpatizanteFormView | Captura de nuevo simpatizante |
| `/simpatizantes/:id` | SimpatizanteDetalleView | Detalle + visor formulario + historial |
| `/simpatizantes/:id/editar` | SimpatizanteFormView | Edición de registro |
| `/importacion` | ImportacionView | Asistente 4 pasos: cargar → validar → confirmar → listo |
| `/auditoria` | AuditoriaView | Logs de auditoría con alertas y exportación CSV |
| `/perfil` | PerfilView | Datos de cuenta, configuración 2FA, permisos |

## Mapa interactivo

El mapa usa **MapLibre GL JS** con:

- Tiles vectoriales gratuitos (demotiles.maplibre.org)
- Navegación jerárquica: Caldas → Municipio → Zona → Puesto → Mesa
- Capa de polígonos de territorios coloreados por volumen de apoyos
- Capa de puntos para puestos de votación
- Tooltips on hover con nombre y conteo
- Panel lateral colapsable con breadcrumb, estadísticas y filtros
- Drawer de detalle de puesto con desglose por mesa
- Buscador global con debounce (territorios + personas)

## Variables de entorno

```env
# Crear frontend/.env.local para desarrollo local
VITE_API_URL=http://localhost:8000
```

## Consideraciones de seguridad

- Token Sanctum en `localStorage` (evaluar `httpOnly cookie` para producción)
- Guard de router verifica autenticación + permisos por ruta
- Campos sensibles nunca se muestran completos (cédula parcialmente enmascarada)
- Todas las peticiones van con `Authorization: Bearer <token>`
