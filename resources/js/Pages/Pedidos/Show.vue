<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ pedido: Object });
const page = usePage();
const motivo = ref('');

const badge = (e) => ({ SOLICITADO: 'bg-warning text-dark', APROBADO: 'bg-primary', RECHAZADO: 'bg-danger', EN_PROCESO: 'bg-info text-dark', DESPACHADO: 'bg-success', ANULADO: 'bg-secondary' }[e] ?? 'bg-secondary');

// Precio estimado por línea (mayorista según umbral) — solo informativo en la vista.
const precioLinea = (d) => {
    const p = d.producto;
    if (!p) return 0;
    return d.cantidad >= p.cantidad_minima_mayorista ? Number(p.precio_mayorista) : Number(p.precio_venta_base);
};
const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
const stockDe = (d) => d.producto?.inventario?.stock_actual ?? 0;

const ventaPendiente = computed(() => props.pedido.venta && props.pedido.venta.estado === 'PENDIENTE');
const ventaPagada = computed(() => ['PAGADA', 'COMPLETADA'].includes(props.pedido.venta?.estado));

const aprobar = () => {
    if (confirm('¿Aprobar el pedido? El cliente elegirá cómo pagar (QR o efectivo).')) {
        router.post(route('pedidos.aprobar', props.pedido.id));
    }
};
const rechazar = () => {
    if (!motivo.value) { alert('Indique el motivo del rechazo.'); return; }
    router.post(route('pedidos.rechazar', props.pedido.id), { motivo_rechazo: motivo.value });
};
const marcarPagado = () => {
    if (confirm('¿Confirmar que el cliente pagó en efectivo?')) {
        router.post(route('ventas.marcar-pagada', props.pedido.venta.id));
    }
};
</script>

<template>
    <Head title="Pedido" />
    <AppLayout title="Detalle del pedido">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1">Pedido #{{ pedido.id }} <span class="badge ms-2" :class="badge(pedido.estado)">{{ pedido.estado }}</span></h5>
                    <div class="text-muted">{{ pedido.cliente?.user?.name }} · {{ new Date(pedido.fecha).toLocaleString() }}</div>
                    <div v-if="pedido.venta" class="small">Venta generada: <Link :href="route('ventas.show', pedido.venta.id)">{{ pedido.venta.numero_venta }}</Link></div>
                    <div v-if="pedido.motivo_rechazo" class="small text-danger">Motivo rechazo: {{ pedido.motivo_rechazo }}</div>
                </div>
                <div class="d-flex flex-column gap-2" style="min-width:260px">
                    <template v-if="pedido.estado === 'SOLICITADO'">
                        <button class="btn btn-success" @click="aprobar"><i class="bi bi-check-lg me-1"></i>Aprobar</button>
                        <div class="input-group input-group-sm">
                            <input v-model="motivo" class="form-control" placeholder="Motivo de rechazo" />
                            <button class="btn btn-outline-danger" @click="rechazar">Rechazar</button>
                        </div>
                    </template>
                    <template v-else-if="pedido.estado === 'APROBADO'">
                        <div v-if="ventaPendiente" class="small text-muted">
                            <i class="bi bi-hourglass-split me-1"></i>El cliente elige pagar por QR o efectivo.
                        </div>
                        <button v-if="ventaPendiente" class="btn btn-success" @click="marcarPagado">
                            <i class="bi bi-cash-coin me-1"></i>Marcar pagado (efectivo)
                        </button>
                        <div v-else-if="ventaPagada" class="small text-success">
                            <i class="bi bi-check-circle me-1"></i>Pagado · en cola de despacho del almacén.
                        </div>
                    </template>
                    <Link :href="route('pedidos.index')" class="btn btn-outline-secondary">Volver</Link>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Stock</th><th class="text-end">Precio est.</th><th class="text-end">Subtotal est.</th></tr></thead>
                    <tbody>
                        <tr v-for="d in pedido.detalles" :key="d.id">
                            <td>{{ d.producto?.codigo }} — {{ d.producto?.nombre }}</td>
                            <td class="text-end">{{ d.cantidad }}</td>
                            <td class="text-end" :class="{ 'text-danger': stockDe(d) < d.cantidad }">{{ stockDe(d) }}</td>
                            <td class="text-end">{{ fmt(precioLinea(d)) }}</td>
                            <td class="text-end">{{ fmt(precioLinea(d) * d.cantidad) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
