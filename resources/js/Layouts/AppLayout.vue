<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
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
const user = computed(() => page.props.auth?.user);
const menuItems = computed(() => page.props.menuItems ?? []);

// Solo mostramos ítems cuyo route ya existe (los módulos se agregan por CU).
const visibleMenu = computed(() =>
    menuItems.value.filter((i) => route().has(i.ruta_laravel))
);

// Navegación estilo "Menú 3C": ítems sueltos + grupos con submenú (dropdown).
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

const notificaciones = computed(() => {
    const n = page.props.notificaciones;
    return {
        no_leidas: n?.no_leidas ?? 0,
        recientes: Array.isArray(n?.recientes) ? n.recientes : [],
    };
});
const iconoNotif = (t) => ({ STOCK_BAJO: 'exclamation-triangle', PEDIDO_POR_APROBAR: 'bag', MORA: 'cash-coin' }[t] ?? 'bell');
</script>

<template>
    <div class="d-flex flex-column min-vh-100 bg-light">
        <!-- ===== Navbar superior (Menú 3C) ===== -->
        <nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm" style="background: linear-gradient(45deg, #7a0000, #b30000);">
            <div class="container-fluid px-3 px-lg-4">
                <!-- Hamburguesa (móvil) -->
                <button class="navbar-toggler border-0 me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidenav" aria-label="Menú">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Marca / logo -->
                <Link :href="route('dashboard')" class="navbar-brand d-flex align-items-center bg-white rounded px-2 py-1">
                    <AppLogo :height="30" />
                </Link>

                <!-- Menú desktop (dropdowns agrupados) -->
                <ul class="navbar-nav ms-3 me-auto d-none d-lg-flex align-items-lg-center">
                    <li v-for="item in nav.sueltos" :key="item.ruta_laravel" class="nav-item">
                        <Link :href="route(item.ruta_laravel)" class="nav-link px-3" :class="{ active: esActivo(item.ruta_laravel) }">
                            <i class="bi me-1" :class="`bi-${item.icono}`"></i>{{ item.etiqueta }}
                        </Link>
                    </li>
                    <li v-for="g in nav.grupos" :key="g.etiqueta" class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle px-3" :class="{ active: grupoActivo(g) }" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi me-1" :class="`bi-${g.icono}`"></i>{{ g.etiqueta }}
                        </a>
                        <ul class="dropdown-menu shadow">
                            <li v-for="h in g.hijos" :key="h.ruta_laravel">
                                <Link class="dropdown-item" :class="{ active: esActivo(h.ruta_laravel) }" :href="route(h.ruta_laravel)">
                                    <i class="bi me-2" :class="`bi-${h.icono}`"></i>{{ h.etiqueta }}
                                </Link>
                            </li>
                        </ul>
                    </li>
                </ul>

                <!-- Zona derecha -->
                <div class="d-flex align-items-center gap-2 ms-auto ms-lg-0">
                    <form class="d-none d-md-flex" style="max-width: 260px;" @submit.prevent="buscarGlobal">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                            <input v-model="busqueda" class="form-control border-0" placeholder="Buscar..." />
                        </div>
                    </form>

                    <ThemeSwitcher />

                    <!-- Notificaciones -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light position-relative" data-bs-toggle="dropdown" aria-label="Notificaciones">
                            <i class="bi bi-bell fs-6"></i>
                            <span v-if="notificaciones.no_leidas" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
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
                                <span class="dropdown-item small d-flex gap-2 text-wrap" :class="{ 'fw-semibold': !n.leido }">
                                    <i class="bi mt-1" :class="`bi-${iconoNotif(n.tipo)}`"></i>
                                    <span>{{ n.mensaje }}</span>
                                </span>
                            </li>
                        </ul>
                    </div>

                    <!-- Usuario -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-6"></i>
                            <span v-if="user" class="d-none d-sm-inline">{{ user.name }}</span>
                            <span v-if="user" class="badge bg-secondary text-uppercase d-none d-md-inline">{{ user.rol }}</span>
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
            </div>
        </nav>

        <!-- ===== Sidenav offcanvas (móvil) ===== -->
        <div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="sidenav" aria-labelledby="sidenavLabel">
            <div class="offcanvas-header border-bottom border-secondary">
                <span class="offcanvas-title bg-white rounded px-2 py-1" id="sidenavLabel"><AppLogo :height="26" /></span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
            </div>
            <div class="offcanvas-body p-2">
                <form class="mb-3 d-md-none" @submit.prevent="buscarGlobal">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input v-model="busqueda" class="form-control" placeholder="Buscar..." />
                    </div>
                </form>
                <nav class="nav flex-column">
                    <Link
                        v-for="item in visibleMenu"
                        :key="item.ruta_laravel"
                        :href="route(item.ruta_laravel)"
                        data-bs-dismiss="offcanvas"
                        class="nav-link d-flex align-items-center gap-2 rounded mb-1"
                        :class="esActivo(item.ruta_laravel) ? 'active bg-primary text-white' : 'text-white-50'"
                    >
                        <i class="bi" :class="`bi-${item.icono}`"></i>
                        <span>{{ item.etiqueta }}</span>
                    </Link>
                </nav>
            </div>
        </div>

        <!-- ===== Contenido ===== -->
        <main class="flex-grow-1 p-3 p-lg-4">
            <h1 v-if="title" class="h4 mb-3">{{ title }}</h1>
            <slot />
        </main>

        <!-- ===== Footer (contador de visitas en cada página) ===== -->
        <footer class="border-top bg-white px-4 py-2 d-flex flex-wrap justify-content-between align-items-center small text-muted gap-2">
            <span>© {{ new Date().getFullYear() }} RAO MOTOS · INF-513 grupo02sa</span>
            <span><i class="bi bi-eye me-1"></i>Visitas: <strong>{{ page.props.visitas ?? 0 }}</strong></span>
        </footer>
    </div>
</template>

<style scoped>
.navbar-nav .nav-link.active {
    font-weight: 600;
    color: #fff !important;
}
.dropdown-item.active {
    background-color: #b30000;
}
</style>
