<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ credito: Object, cuotas: Array, metodosPago: Array });
const page = usePage();

const metodoSel = ref('');

const badge = (e) => ({ VIGENTE: 'bg-info text-dark', PAGADO: 'bg-success', MOROSO: 'bg-danger', PENDIENTE: 'bg-warning text-dark', VENCIDO: 'bg-danger' }[e] ?? 'bg-secondary');
const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;

const pagar = (cuota) => {
    if (confirm(`¿Registrar el pago de la cuota #${cuota.numero_cuota}?`)) {
        router.post(route('creditos.pagar-cuota', cuota.id), { metodo_pago_id: metodoSel.value || null }, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Crédito" />
    <AppLayout title="Detalle del crédito">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1">Crédito #{{ credito.id }} <span class="badge ms-2" :class="badge(credito.estado)">{{ credito.estado }}</span></h5>
                    <div class="text-muted">{{ credito.venta?.cliente?.user?.name }} · Venta {{ credito.venta?.numero_venta }}</div>
                    <div class="small text-muted">{{ credito.numero_cuotas }} cuotas · Interés {{ credito.tasa_interes }}% · Saldo {{ fmt(credito.saldo_pendiente) }}</div>
                </div>
                <div style="min-width:220px">
                    <label class="form-label small mb-0">Método de pago (cobranza)</label>
                    <select v-model="metodoSel" class="form-select form-select-sm">
                        <option value="">— sin especificar —</option>
                        <option v-for="m in metodosPago" :key="m.id" :value="m.id">{{ m.nombre }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">Calendario de cuotas</div>
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
                                <button v-if="c.estado !== 'PAGADO'" class="btn btn-sm btn-success" @click="pagar(c)">Registrar pago</button>
                                <span v-else class="text-muted small">{{ c.fecha_pago }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <Link :href="route('creditos.index')" class="btn btn-outline-secondary btn-sm mt-3">Volver</Link>
    </AppLayout>
</template>
