import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    /* ── Auth ─────────────────────────────────────────────── */
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/auth/LoginView.vue'),
      meta: { guest: true, transition: 'fade' },
    },
    {
      path: '/login/2fa',
      name: 'login-2fa',
      component: () => import('@/views/auth/TwoFactorView.vue'),
      meta: { guest: true, transition: 'slide' },
    },

    /* ── App shell ─────────────────────────────────────────── */
    {
      path: '/',
      component: () => import('@/layouts/AppLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          redirect: '/mapa',
        },
        /* Mapa principal */
        {
          path: 'mapa',
          name: 'mapa',
          component: () => import('@/views/mapa/MapaView.vue'),
          meta: { title: 'Mapa Electoral', transition: 'fade' },
        },
        /* Simpatizantes */
        {
          path: 'simpatizantes',
          name: 'simpatizantes',
          component: () => import('@/views/simpatizantes/SimpatizantesView.vue'),
          meta: { title: 'Simpatizantes', permission: 'simpatizante.ver' },
        },
        {
          path: 'simpatizantes/nuevo',
          name: 'simpatizante-nuevo',
          component: () => import('@/views/simpatizantes/SimpatizanteFormView.vue'),
          meta: { title: 'Nuevo Simpatizante', permission: 'simpatizante.crear' },
        },
        {
          path: 'simpatizantes/:id',
          name: 'simpatizante-detalle',
          component: () => import('@/views/simpatizantes/SimpatizanteDetalleView.vue'),
          meta: { title: 'Detalle Simpatizante', permission: 'simpatizante.ver' },
        },
        {
          path: 'simpatizantes/:id/editar',
          name: 'simpatizante-editar',
          component: () => import('@/views/simpatizantes/SimpatizanteFormView.vue'),
          meta: { title: 'Editar Simpatizante', permission: 'simpatizante.editar' },
        },
        /* Importación */
        {
          path: 'importacion',
          name: 'importacion',
          component: () => import('@/views/importacion/ImportacionView.vue'),
          meta: { title: 'Importación Masiva', permission: 'importacion.realizar' },
        },
        /* Auditoría */
        {
          path: 'auditoria',
          name: 'auditoria',
          component: () => import('@/views/auditoria/AuditoriaView.vue'),
          meta: { title: 'Auditoría', permission: 'auditoria.ver' },
        },
        /* Reportes */
        {
          path: 'reportes',
          name: 'reportes',
          component: () => import('@/views/reportes/ReportesView.vue'),
          meta: { title: 'Reportes y tableros', permission: 'reporte.ver' },
        },
        /* Administración */
        {
          path: 'admin',
          component: () => import('@/views/admin/AdminLayout.vue'),
          meta: { title: 'Administración', permission: 'usuario.ver' },
          children: [
            { path: '', redirect: '/admin/usuarios' },
            {
              path: 'usuarios',
              name: 'admin-usuarios',
              component: () => import('@/views/admin/UsuariosView.vue'),
              meta: { title: 'Usuarios', permission: 'usuario.ver' },
            },
            {
              path: 'campanas',
              name: 'admin-campanas',
              component: () => import('@/views/admin/CampanasView.vue'),
              meta: { title: 'Campañas', permission: 'campana.ver' },
            },
            {
              path: 'candidatos',
              name: 'admin-candidatos',
              component: () => import('@/views/admin/CandidatosView.vue'),
              meta: { title: 'Candidatos', permission: 'campana.ver' },
            },
            {
              path: 'puestos',
              name: 'admin-puestos',
              component: () => import('@/views/admin/PuestosView.vue'),
              meta: { title: 'Puestos de votación', permission: 'catalogo.ver' },
            },
            {
              path: 'solicitudes-titular',
              name: 'admin-solicitudes',
              component: () => import('@/views/admin/SolicitudesTitularView.vue'),
              meta: { title: 'Solicitudes de titulares', permission: 'sistema.administrar' },
            },
          ],
        },
        /* Perfil */
        {
          path: 'perfil',
          name: 'perfil',
          component: () => import('@/views/perfil/PerfilView.vue'),
          meta: { title: 'Mi Perfil' },
        },
      ],
    },

    /* ── 404 ──────────────────────────────────────────────── */
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
    },
  ],
})

/* ── Guards ─────────────────────────────────────────────────── */
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Inicializar auth desde token guardado
  if (!auth.initialized) {
    await auth.init()
  }

  // Ruta protegida sin autenticación
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // Ruta de guest con sesión activa
  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'mapa' }
  }

  // Verificar permiso específico
  if (to.meta.permission && !auth.hasPermission(to.meta.permission)) {
    return { name: 'mapa' }
  }
})

export default router
