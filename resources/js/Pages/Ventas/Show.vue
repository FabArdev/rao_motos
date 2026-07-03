<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ venta: Object });
const page = usePage();

const badge = (e) => ({ COMPLETADA: 'bg-success', PENDIENTE: 'bg-warning text-dark', ANULADA: 'bg-danger', PAGADO: 'bg-success', VIGENTE: 'bg-info text-dark', MOROSO: 'bg-danger', VENCIDO: 'bg-danger' }[e] ?? 'bg-secondary');
const creditoLabel = (e) => ({ VIGENTE: 'Crédito vigente', MOROSO: 'Moroso', PAGADO: 'Pagado' }[e] ?? e);
const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;

// En crédito, el estado que importa es el del crédito (cuotas), no el de la venta.
const esCredito = computed(() => props.venta.tipo_venta === 'CREDITO' && props.venta.credito);
const cuotasPagadas = computed(() => (props.venta.credito?.cuotas ?? []).filter((c) => c.estado === 'PAGADO').length);
const cuotasTotal = computed(() => props.venta.credito?.cuotas?.length ?? props.venta.credito?.numero_cuotas ?? 0);

const anular = () => { if (confirm('¿Anular esta venta? Se revertirá el stock.')) router.post(route('ventas.anular', props.venta.id)); };
</script>

<template>
    <Head title="Venta" />
    <AppLayout title="Detalle de venta">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="fw-bold mb-1">
                        {{ venta.numero_venta }}
                        <span v-if="esCredito" class="badge ms-2" :class="badge(venta.credito.estado)">{{ creditoLabel(venta.credito.estado) }}</span>
                        <span v-else class="badge ms-2" :class="badge(venta.estado)">{{ venta.estado }}</span>
                        <span v-if="esCredito" class="text-muted small ms-2">({{ cuotasPagadas }}/{{ cuotasTotal }} cuotas pagadas)</span>
                    </h5>
                    <div class="text-muted">{{ venta.cliente?.user?.name }} · {{ new Date(venta.fecha).toLocaleString() }}</div>
                    <div class="small text-muted">{{ venta.tipo_venta }} · {{ venta.metodo_pago }} · Vendedor: {{ venta.vendedor?.name || '—' }}</div>
                </div>
                <div class="d-flex gap-2">
                    <Link v-if="venta.estado === 'PENDIENTE' && venta.metodo_pago === 'QR'" :href="route('pagofacil.generar-qr-venta', venta.id)" class="btn btn-primary">
                        <i class="bi bi-qr-code me-1"></i>Cobrar con QR
                    </Link>
                    <button v-if="venta.estado !== 'ANULADA' && !venta.credito" class="btn btn-outline-danger" @click="anular">Anular</button>
                    <Link :href="route('ventas.index')" class="btn btn-outline-secondary">Volver</Link>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Detalle</th><th class="text-end">Cantidad</th><th class="text-end">Precio unit.</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                        <tr v-for="d in venta.detalles" :key="d.id">
                            <td>{{ d.producto ? `${d.producto.codigo} — ${d.producto.nombre}` : (d.descripcion || 'Servicio') }}</td>
                            <td class="text-end">{{ d.cantidad }}</td>
                            <td class="text-end">{{ fmt(d.precio_unitario) }}</td>
                            <td class="text-end">{{ fmt(d.cantidad * d.precio_unitario) }}</td>
                        </tr>
                    </tbody>
                    <tfoot><tr class="table-light"><th colspan="3" class="text-end">Total</th><th class="text-end">{{ fmt(venta.monto_total) }}</th></tr></tfoot>
                </table>
            </div>
        </div>

        <div v-if="venta.credito" class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">
                Crédito · <span class="badge" :class="badge(venta.credito.estado)">{{ venta.credito.estado }}</span>
                · Interés {{ venta.credito.tasa_interes }}% · Saldo {{ fmt(venta.credito.saldo_pendiente) }}
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Cuota</th><th>Vencimiento</th><th class="text-end">Monto</th><th class="text-end">Mora</th><th>Estado</th></tr></thead>
                    <tbody>
                        <tr v-for="c in venta.credito.cuotas" :key="c.id">
                            <td>{{ c.numero_cuota }}</td>
                            <td>{{ c.fecha_vencimiento }}</td>
                            <td class="text-end">{{ fmt(c.monto_cuota) }}</td>
                            <td class="text-end">{{ fmt(c.mora) }}</td>
                            <td><span class="badge" :class="badge(c.estado)">{{ c.estado }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
