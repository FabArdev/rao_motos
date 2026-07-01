<script setup>
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';

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

const esActivo = (ruta) => route().current(ruta);
const logout = () => router.post(route('logout'));

const notificaciones = computed(() => page.props.notificaciones ?? { no_leidas: 0, recientes: [] });
const iconoNotif = (t) => ({ STOCK_BAJO: 'exclamation-triangle', SOLICITUD_REPUESTO: 'wrench', PEDIDO_POR_APROBAR: 'bag', PRESUPUESTO: 'clipboard-check', MORA: 'cash-coin' }[t] ?? 'bell');
</script>

<template>
    <div class="d-flex min-vh-100">
        <!-- Sidebar -->
        <aside class="bg-dark text-white d-flex flex-column" style="width: 250px;">
            <div class="p-3 border-bottom border-secondary">
                <Link :href="route('dashboard')" class="text-white text-decoration-none fw-bold fs-5">
                    🏍️ RAO MOTOS
                </Link>
            </div>
            <nav class="nav flex-column p-2 flex-grow-1">
                <Link
                    v-for="item in visibleMenu"
                    :key="item.ruta_laravel"
                    :href="route(item.ruta_laravel)"
                    class="nav-link d-flex align-items-center gap-2 rounded mb-1"
                    :class="esActivo(item.ruta_laravel) ? 'active bg-primary text-white' : 'text-white-50'"
                >
                    <i class="bi" :class="`bi-${item.icono}`"></i>
                    <span>{{ item.etiqueta }}</span>
                </Link>
            </nav>
            <div class="p-3 border-top border-secondary small text-white-50">
                INF-513 · grupo02sa
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-grow-1 d-flex flex-column bg-light">
            <!-- Topbar -->
            <header class="navbar navbar-light bg-white border-bottom px-4 py-2">
                <span class="navbar-brand mb-0 h5">{{ title }}</span>

                <form class="d-none d-md-flex ms-4" style="max-width: 340px; flex: 1;" @submit.prevent="buscarGlobal">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input v-model="busqueda" class="form-control" placeholder="Buscar en el negocio..." />
                    </div>
                </form>

                <div class="ms-auto d-flex align-items-center gap-2">
                    <ThemeSwitcher />
                    <!-- Notificaciones -->
                    <div class="dropdown">
                        <button class="btn btn-light position-relative" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-5"></i>
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
                                <span class="dropdown-item small d-flex gap-2" :class="{ 'fw-semibold': !n.leido }">
                                    <i class="bi mt-1" :class="`bi-${iconoNotif(n.tipo)}`"></i>
                                    <span>{{ n.mensaje }}</span>
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span v-if="user">{{ user.name }}</span>
                        <span v-if="user" class="badge bg-secondary text-uppercase">{{ user.rol }}</span>
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

            <!-- Content -->
            <main class="p-4 flex-grow-1">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="border-top bg-white px-4 py-2 d-flex justify-content-between align-items-center small text-muted">
                <span>© {{ new Date().getFullYear() }} RAO MOTOS · INF-513 grupo02sa</span>
                <span><i class="bi bi-eye me-1"></i>Visitas: <strong>{{ page.props.visitas ?? 0 }}</strong></span>
            </footer>
        </div>
    </div>
</template>
