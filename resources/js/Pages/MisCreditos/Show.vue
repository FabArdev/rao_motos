<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ credito: Object, cuotas: Array });
const page = usePage();

const badge = (e) => ({ VIGENTE: 'bg-info text-dark', PAGADO: 'bg-success', MOROSO: 'bg-danger', PENDIENTE: 'bg-warning text-dark', VENCIDO: 'bg-danger' }[e] ?? 'bg-secondary');
const fmt = (n) => `Bs. ${Number(n ?? 0).toFixed(2)}`;

const proximaCuota = props.cuotas.find((c) => c.estado !== 'PAGADO');

// Genera el QR de PagoFácil para la cuota (REQ10 / RN13).
const pagarQR = (cuota) => {
    router.post(route('pagofacil.generar-qr-cuota', cuota.id));
};
</script>

<template>
    <Head title="Mi crédito" />
    <AppLayout title="Detalle de mi crédito">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h5 class="fw-bold mb-1">Crédito #{{ credito.id }} <span class="badge ms-2" :class="badge(credito.estado)">{{ credito.estado }}</span></h5>
                <div class="text-muted">Venta {{ credito.venta?.numero_venta }} · {{ credito.numero_cuotas }} cuotas · Interés {{ credito.tasa_interes }}%</div>
                <div class="fs-5 fw-bold mt-1">Saldo pendiente: {{ fmt(credito.saldo_pendiente) }}</div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">Mis cuotas</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Cuota</th><th>Vencimiento</th><th class="text-end">Monto</th><th class="text-end">Mora</th><th class="text-end">Total</th><th>Estado</th><th class="text-end"></th></tr></thead>
                    <tbody>
                        <tr v-for="c in cuotas" :key="c.id">
                            <td>{{ c.numero_cuota }}</td>
                            <td>{{ c.fecha_vencimiento }}</td>
                            <td class="text-end">{{ fmt(c.monto_cuota) }}</td>
                            <td class="text-end" :class="{ 'text-danger fw-semibold': c.mora_actual > 0 }">{{ fmt(c.mora_actual) }}</td>
                            <td class="text-end">{{ fmt(Number(c.monto_cuota) + Number(c.mora_actual)) }}</td>
                            <td><span class="badge" :class="badge(c.estado)">{{ c.estado }}</span></td>
                            <td class="text-end">
                                <button v-if="c.estado !== 'PAGADO' && proximaCuota && c.id === proximaCuota.id"
                                    class="btn btn-sm btn-success" @click="pagarQR(c)">
                                    <i class="bi bi-qr-code me-1"></i>Pagar con QR
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <Link :href="route('mis-creditos.index')" class="btn btn-outline-secondary btn-sm mt-3">Volver</Link>
    </AppLayout>
</template>
