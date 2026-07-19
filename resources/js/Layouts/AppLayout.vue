<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Offcanvas } from 'bootstrap';
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import AppLogo from '@/Components/AppLogo.vue';

defineProps({
    title: { type: String, default: '' },
});

const busqueda = ref('');
const buscarGlobal = () => {
    if (busqueda.value.trim().length >= 2) {
        router.get(route('buscar'), { q: busqueda.value });
    }
};

const page = usePage();
const usuario = computed(() => page.props.auth?.usuario);
const itemsMenu = computed(() => page.props.itemsMenu ?? []);

// Solo mostramos ítems cuyo route ya existe (los módulos se agregan por CU).
const visibleMenu = computed(() =>
    itemsMenu.value.filter((i) => route().has(i.ruta_laravel))
);

// Navegación estilo "Menú 3C": ítems sueltos + grupos, ahora en barra lateral.
const GRUPOS = [
    { etiqueta: 'Inventario', icono: 'box-seam', rutas: ['productos.index', 'inventario.index', 'compras.index', 'proveedores.index'] },
    { etiqueta: 'Ventas', icono: 'receipt', rutas: ['pedidos.index', 'ventas.index', 'creditos.index'] },
    { etiqueta: 'Gestión', icono: 'gear', rutas: ['usuarios.index', 'reportes.index', 'bitacora.index', 'configuracion.index'] },
    { etiqueta: 'Mi cuenta', icono: 'person-circle', rutas: ['mis-pedidos.index', 'mis-creditos.index', 'mi-cuenta.index'] },
];

const nav = computed(() => {
    const items = visibleMenu.value;
    const byRoute = Object.fromEntries(items.map((i) => [i.ruta_laravel, i]));
    const usados = new Set();
    const grupos = [];
    for (const g of GRUPOS) {
        const hijos = g.rutas.filter((r) => byRoute[r]).map((r) => {
            usados.add(r);
            return byRoute[r];
        });
        if (hijos.length) grupos.push({ ...g, hijos });
    }
    const sueltos = items.filter((i) => !usados.has(i.ruta_laravel));
    return { grupos, sueltos };
});

const esActivo = (ruta) => route().current(ruta);
const grupoActivo = (g) => g.hijos.some((h) => esActivo(h.ruta_laravel));
const logout = () => router.post(route('logout'));

// En móvil, cerrar el offcanvas al navegar (sin data-bs-dismiss, que rompía la
// navegación de Inertia). Solo actúa si el sidebar está abierto como offcanvas.
const cerrarSidebarMovil = () => {
    const el = document.getElementById('sidebar');
    if (el?.classList.contains('show')) {
        Offcanvas.getInstance(el)?.hide();
    }
};
const buscarDesdeMenu = () => {
    cerrarSidebarMovil();
    buscarGlobal();
};

const notificaciones = computed(() => {
    const n = page.props.notificaciones;
    return {
        no_leidas: n?.no_leidas ?? 0,
        recientes: Array.isArray(n?.recientes) ? n.recientes : [],
    };
});
const iconoNotif = (t) => ({ STOCK_BAJO: 'exclamation-triangle', PEDIDO_POR_APROBAR: 'bag', VENTA_PAGADA: 'cash-stack', PEDIDO_APROBADO: 'check-circle', PEDIDO_RECHAZADO: 'x-circle', PEDIDO_DESPACHADO: 'truck', MORA: 'cash-coin' }[t] ?? 'bell');

// Al abrir una notificación: marcarla como leída (baja el contador) y luego ir al recurso.
const abrirNotif = (n) => {
    const ir = () => { if (n.recurso) router.visit(n.recurso); };
    if (n.leido) {
        ir();
        return;
    }
    router.post(route('notificaciones.leida', n.id), {}, {
        preserveScroll: true,
        preserveState: true,
        onFinish: ir,
    });
};
</script>

<template>
    <div class="d-flex flex-column app-shell bg-light">
        <!-- ===== Topbar (pegajosa en móvil, fija en escritorio) ===== -->
        <header class="navbar bg-white border-bottom px-3 px-lg-4 py-2 shadow-sm flex-shrink-0" style="position: sticky; top: 0; z-index: 1030;">
            <div class="d-flex align-items-center gap-2">
                <!-- Hamburguesa (móvil) -->
                <button class="btn btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Menú">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <Link :href="route('dashboard')" class="d-flex align-items-center text-decoration-none">
                    <AppLogo :height="34" />
                </Link>
            </div>

            <div class="d-flex align-items-center gap-2 ms-auto">
                <form class="d-none d-md-flex" style="max-width: 260px;" @submit.prevent="buscarGlobal">
                    <div class="input-group input-group-sm">
                        <input v-model="busqueda" class="form-control" placeholder="Buscar..." />
                        <button class="btn btn-primary" type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
                    </div>
                </form>

                <ThemeSwitcher />

                <!-- Notificaciones -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light position-relative" data-bs-toggle="dropdown" aria-label="Notificaciones">
                        <i class="bi bi-bell fs-6"></i>
                        <span v-if="notificaciones.no_leidas" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ notificaciones.no_leidas }}
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width: 320px;">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            Notificaciones
                            <Link v-if="route().has('notificaciones.index')" :href="route('notificaciones.index')" class="small">Ver todas</Link>
                        </li>
                        <li v-if="!notificaciones.recientes.length" class="dropdown-item-text text-muted small">Sin notificaciones.</li>
                        <li v-for="n in notificaciones.recientes" :key="n.id">
                            <button type="button" class="dropdown-item small d-flex gap-2 text-wrap text-start" :class="{ 'fw-semibold': !n.leido }" @click="abrirNotif(n)">
                                <i class="bi mt-1" :class="`bi-${iconoNotif(n.tipo)}`"></i>
                                <span>{{ n.mensaje }}</span>
                                <span v-if="!n.leido" class="ms-auto badge rounded-pill bg-danger align-self-center">&nbsp;</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Usuario -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                        <img v-if="usuario?.profile_photo_url" :src="usuario.profile_photo_url" :alt="usuario.nombre_completo" style="width: 28px; height: 28px; min-width: 28px; border-radius: 50%; object-fit: cover; display: block; flex-shrink: 0;" />
                        <i v-else class="bi bi-person-circle fs-6"></i>
                        <span v-if="usuario" class="d-none d-sm-inline">{{ usuario.nombre_completo }}</span>
                        <span v-if="usuario" class="badge bg-secondary text-uppercase d-none d-md-inline">{{ usuario.rol }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <Link class="dropdown-item" :href="route('profile.show')">
                                <i class="bi bi-gear me-2"></i>Mi perfil
                            </Link>
                        </li>
                        <li><hr class="dropdown-divider" /></li>
                        <li>
                            <button class="dropdown-item text-danger" @click="logout">
                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- ===== Cuerpo: sidebar + contenido ===== -->
        <div class="d-flex flex-grow-1 app-body">
            <!-- Sidebar (estático en lg+, offcanvas en móvil) -->
            <div class="offcanvas-lg offcanvas-start text-bg-dark sidebar-3c" tabindex="-1" id="sidebar" style="--bs-offcanvas-width: 240px;">
                <div class="offcanvas-header border-bottom border-secondary d-lg-none">
                    <span class="offcanvas-title"><AppLogo :height="28" /></span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Cerrar"></button>
                </div>
                <div class="offcanvas-body flex-column p-2">
                    <!-- Buscador en móvil (en escritorio está en la topbar) -->
                    <form class="d-md-none mb-2" @submit.prevent="buscarDesdeMenu">
                        <div class="input-group input-group-sm">
                            <input v-model="busqueda" class="form-control" placeholder="Buscar..." />
                            <button class="btn btn-primary" type="submit" aria-label="Buscar"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                    <nav class="nav flex-column w-100">
                        <!-- Ítems sueltos (Dashboard / Catálogo) -->
                        <Link
                            v-for="item in nav.sueltos"
                            :key="item.ruta_laravel"
                            :href="route(item.ruta_laravel)"
                            @click="cerrarSidebarMovil"
                            class="nav-link d-flex align-items-center gap-2 rounded mb-1"
                            :class="esActivo(item.ruta_laravel) ? 'active bg-primary text-white' : 'text-white-50'"
                        >
                            <i class="bi" :class="`bi-${item.icono}`"></i><span>{{ item.etiqueta }}</span>
                        </Link>

                        <!-- Grupos: mini-menú flotante (dropend) que se cierra al hacer clic fuera -->
                        <div v-for="g in nav.grupos" :key="g.etiqueta" class="dropend">
                            <button
                                type="button"
                                class="nav-link dropdown-toggle d-flex align-items-center gap-2 rounded mb-1 w-100 border-0 bg-transparent text-start"
                                :class="grupoActivo(g) ? 'text-white' : 'text-white-50'"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <i class="bi" :class="`bi-${g.icono}`"></i>
                                <span>{{ g.etiqueta }}</span>
                            </button>
                            <ul class="dropdown-menu shadow">
                                <li v-for="h in g.hijos" :key="h.ruta_laravel">
                                    <Link
                                        :href="route(h.ruta_laravel)"
                                        @click="cerrarSidebarMovil"
                                        class="dropdown-item d-flex align-items-center gap-2"
                                        :class="{ active: esActivo(h.ruta_laravel) }"
                                    >
                                        <i class="bi" :class="`bi-${h.icono}`"></i>{{ h.etiqueta }}
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Contenido (scroll interno solo en escritorio; en móvil scrollea la página) -->
            <main class="flex-grow-1 app-main p-3 p-lg-4" style="min-width: 0;">
                <h1 v-if="title" class="h4 mb-3">{{ title }}</h1>
                <slot />
            </main>
        </div>

        <!-- ===== Footer fijo abajo (contador de visitas en cada página) ===== -->
        <footer class="border-top bg-white px-4 py-2 d-flex flex-wrap justify-content-between align-items-center small text-muted gap-2 flex-shrink-0">
            <span>© {{ new Date().getFullYear() }} RAO MOTOS · INF-513 grupo02sa</span>
            <span><i class="bi bi-eye me-1"></i>Visitas en esta página: <strong>{{ page.props.visitas ?? 0 }}</strong></span>
        </footer>
    </div>
</template>

<style scoped>
/* Móvil: la página crece y hace scroll normal (topbar pegajosa arriba). */
.app-shell {
    min-height: 100vh;
}
/* Escritorio: altura fija de pantalla; topbar/sidebar/footer fijos, solo el contenido scrollea. */
@media (min-width: 992px) {
    .app-shell {
        height: 100vh;
    }
    .app-body {
        min-height: 0;
    }
    .app-main {
        overflow-y: auto;
    }
    .sidebar-3c {
        width: 240px;
        flex-shrink: 0;
    }
}
/* Que el mini-menú flotante (dropend) no lo recorte el scroll del sidebar. */
.sidebar-3c .offcanvas-body {
    overflow: visible;
}
.sidebar-3c .nav-link:hover {
    color: #fff !important;
    background-color: rgba(255, 255, 255, .08);
}
</style>
