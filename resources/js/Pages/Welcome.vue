<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
    productos: { type: Array, default: () => [] },
});

const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
const img = (p) => p.foto_completa ?? p.foto_url ?? null;

// Scroll suave al catálogo — funciona siempre (no depende del hash de la URL).
const verRepuestos = () => {
    document.getElementById('catalogo')?.scrollIntoView({ behavior: 'smooth' });
};
</script>

<template>
    <Head title="RAO MOTOS — Repuestos de moto" />

    <div class="min-vh-100 d-flex flex-column bg-light">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: #b30000;">
            <div class="container">
                <span class="navbar-brand d-flex align-items-center bg-white rounded px-2 py-1">
                    <AppLogo :height="30" />
                </span>
                <div class="d-flex gap-2">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-light btn-sm fw-semibold">
                        Ir al panel
                    </Link>
                    <template v-else-if="canLogin">
                        <Link :href="route('login')" class="btn btn-light btn-sm fw-semibold border">Iniciar sesión</Link>
                        <Link v-if="canRegister" :href="route('register')" class="btn btn-warning btn-sm fw-bold text-dark">
                            <i class="bi bi-person-plus me-1"></i>Crear cuenta
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <header class="text-white py-5" style="background: linear-gradient(135deg, #b30000 0%, #7a0000 100%);">
            <div class="container py-4 text-center">
                <h1 class="display-4 fw-bold mb-3">Repuestos de moto para todo tipo de motocicleta</h1>
                <p class="lead mb-4 opacity-75">
                    Venta al por menor y mayor · Compra al contado o en cuotas
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <Link v-if="!$page.props.auth.user && canRegister" :href="route('register')" class="btn btn-light btn-lg fw-semibold">
                        <i class="bi bi-person-plus me-1"></i>Regístrate y haz tu pedido
                    </Link>
                    <a href="#catalogo" @click.prevent="verRepuestos" class="btn btn-outline-light btn-lg fw-semibold"><i class="bi bi-box-seam me-1"></i>Ver repuestos</a>
                </div>
            </div>
        </header>

        <!-- Servicios -->
        <section class="container py-5">
            <div class="row g-4 text-center justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-box-seam fs-1" style="color: #b30000;"></i>
                            <h5 class="mt-2">Repuestos</h5>
                            <p class="small text-muted mb-0">Catálogo con precio minorista y mayorista por volumen.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-credit-card fs-1" style="color: #b30000;"></i>
                            <h5 class="mt-2">Crédito / cuotas</h5>
                            <p class="small text-muted mb-0">Financia tu compra en cuotas.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-qr-code fs-1" style="color: #b30000;"></i>
                            <h5 class="mt-2">Pago con QR</h5>
                            <p class="small text-muted mb-0">Paga al contado o tus cuotas con PagoFácil.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Catálogo público -->
        <section id="catalogo" class="bg-white py-5 border-top">
            <div class="container">
                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h2 class="fw-bold mb-0">Nuestros repuestos</h2>
                        <p class="text-muted mb-0">Una muestra de nuestro inventario. Regístrate para hacer tu pedido.</p>
                    </div>
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-sm btn-outline-danger">Ver todo</Link>
                </div>

                <div class="row g-4">
                    <div v-for="p in productos" :key="p.id" class="col-6 col-md-4 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="ratio ratio-4x3 bg-light d-flex align-items-center justify-content-center">
                                <img v-if="img(p)" :src="img(p)" class="object-fit-contain" alt="" />
                                <i v-else class="bi bi-box-seam fs-1 text-muted"></i>
                            </div>
                            <div class="card-body">
                                <div class="small text-muted">{{ p.codigo }} · {{ p.marca || '—' }}</div>
                                <h6 class="fw-semibold mb-1">{{ p.nombre }}</h6>
                                <div class="fw-bold" style="color: #b30000;">{{ fmt(p.precio_venta_base) }}</div>
                                <div class="small text-muted">Mayorista {{ fmt(p.precio_mayorista) }} desde {{ p.cantidad_minima_mayorista }} u.</div>
                            </div>
                        </div>
                    </div>
                    <div v-if="!productos.length" class="col-12 text-center text-muted py-4">
                        Aún no hay productos publicados.
                    </div>
                </div>

                <div class="text-center mt-4">
                    <Link v-if="!$page.props.auth.user && canRegister" :href="route('register')" class="btn btn-danger btn-lg">
                        Crear una cuenta para comprar
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="text-white py-4 mt-auto" style="background-color: #1a1a1a;">
            <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <small>&copy; {{ new Date().getFullYear() }} RAO MOTOS · INF-513 Tecnología Web · grupo02sa</small>
                <small class="opacity-50">Laravel v{{ laravelVersion }} · PHP v{{ phpVersion }}</small>
            </div>
        </footer>
    </div>
</template>
