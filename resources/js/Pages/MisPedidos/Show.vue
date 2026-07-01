<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ pedido: Object });

const badge = (e) => ({ SOLICITADO: 'bg-warning text-dark', APROBADO: 'bg-primary', RECHAZADO: 'bg-danger', EN_PROCESO: 'bg-info text-dark', DESPACHADO: 'bg-success', ANULADO: 'bg-secondary' }[e] ?? 'bg-secondary');
</script>

<template>
    <Head title="Mi pedido" />
    <AppLayout title="Detalle de mi pedido">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h5 class="fw-bold mb-1">Pedido #{{ pedido.id }} <span class="badge ms-2" :class="badge(pedido.estado)">{{ pedido.estado }}</span></h5>
                <div class="text-muted">{{ new Date(pedido.fecha).toLocaleString() }}</div>
                <div v-if="pedido.motivo_rechazo" class="alert alert-danger mt-2 mb-0 py-2">Motivo del rechazo: {{ pedido.motivo_rechazo }}</div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Producto</th><th class="text-end">Cantidad</th></tr></thead>
                    <tbody>
                        <tr v-for="d in pedido.detalles" :key="d.id">
                            <td>{{ d.producto?.codigo }} — {{ d.producto?.nombre }}</td>
                            <td class="text-end">{{ d.cantidad }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <Link :href="route('mis-pedidos.index')" class="btn btn-outline-secondary btn-sm mt-3">Volver</Link>
    </AppLayout>
</template>
