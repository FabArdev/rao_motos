<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ lista: Object });
const page = usePage();

const marcar = (n) => router.post(route('notificaciones.leida', n.id), {}, { preserveScroll: true });
const marcarTodas = () => router.post(route('notificaciones.todas'), {}, { preserveScroll: true });

const icono = (t) => ({ STOCK_BAJO: 'exclamation-triangle text-warning', PEDIDO_POR_APROBAR: 'bag text-primary', PEDIDO_APROBADO: 'check-circle text-success', PEDIDO_RECHAZADO: 'x-circle text-danger', PEDIDO_DESPACHADO: 'truck text-primary', MORA: 'cash-coin text-danger' }[t] ?? 'bell');
</script>

<template>
    <Head title="Notificaciones" />
    <AppLayout title="Notificaciones">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div class="d-flex justify-content-end mb-2"><button class="btn btn-sm btn-outline-secondary" @click="marcarTodas">Marcar todas como leídas</button></div>

        <div class="card shadow-sm border-0">
            <ul class="list-group list-group-flush">
                <li v-for="n in lista.data" :key="n.id" class="list-group-item d-flex justify-content-between align-items-start" :class="{ 'bg-light': !n.leido }">
                    <div class="d-flex gap-2">
                        <i class="bi mt-1" :class="`bi-${icono(n.tipo)}`"></i>
                        <div>
                            <Link v-if="n.recurso" :href="n.recurso" class="text-decoration-none d-block" :class="{ 'fw-semibold': !n.leido }">
                                {{ n.mensaje }} <i class="bi bi-box-arrow-up-right small"></i>
                            </Link>
                            <div v-else :class="{ 'fw-semibold': !n.leido }">{{ n.mensaje }}</div>
                            <div class="small text-muted">{{ new Date(n.fecha).toLocaleString() }}</div>
                        </div>
                    </div>
                    <button v-if="!n.leido" class="btn btn-sm btn-light" @click="marcar(n)"><i class="bi bi-check2"></i></button>
                </li>
                <li v-if="!lista.data.length" class="list-group-item text-center text-muted py-4">Sin notificaciones.</li>
            </ul>
        </div>
    </AppLayout>
</template>
