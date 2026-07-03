<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ pedido: Object });
const page = usePage();
const motivo = ref('');
const metodoPago = ref('EFECTIVO');

const badge = (e) => ({ SOLICITADO: 'bg-warning text-dark', APROBADO: 'bg-primary', RECHAZADO: 'bg-danger', EN_PROCESO: 'bg-info text-dark', DESPACHADO: 'bg-success', ANULADO: 'bg-secondary' }[e] ?? 'bg-secondary');

// Precio estimado por línea (mayorista según umbral) — solo informativo en la vista.
const precioLinea = (d) => {
    const p = d.producto;
    if (!p) return 0;
    return d.cantidad >= p.cantidad_minima_mayorista ? Number(p.precio_mayorista) : Number(p.precio_venta_base);
};
const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
const stockDe = (d) => d.producto?.inventario?.stock_actual ?? 0;

const aprobar = () => {
    if (confirm(`¿Aprobar el pedido? Método de pago: ${metodoPago.value === 'QR' ? 'QR (cobrar ahora)' : 'Efectivo'}`)) {
        router.post(route('pedidos.aprobar', props.pedido.id), { metodo_pago: metodoPago.value });
    }
};
const rechazar = () => {
    if (!motivo.value) { alert('Indique el motivo del rechazo.'); return; }
    router.post(route('pedidos.rechazar', props.pedido.id), { motivo_rechazo: motivo.value });
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
                        <label class="form-label small mb-0">Método de pago del cliente</label>
                        <select v-model="metodoPago" class="form-select form-select-sm">
                            <option value="EFECTIVO">Efectivo (cobra al recoger)</option>
                            <option value="QR">QR (cobrar ahora)</option>
                        </select>
                        <button class="btn btn-success" @click="aprobar"><i class="bi bi-check-lg me-1"></i>Aprobar</button>
                        <div class="input-group input-group-sm">
                            <input v-model="motivo" class="form-control" placeholder="Motivo de rechazo" />
                            <button class="btn btn-outline-danger" @click="rechazar">Rechazar</button>
                        </div>
                    </template>
                    <div v-else-if="pedido.estado === 'APROBADO'" class="small text-muted">
                        <i class="bi bi-info-circle me-1"></i>Cobro y despacho se gestionan desde la venta.
                    </div>
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
