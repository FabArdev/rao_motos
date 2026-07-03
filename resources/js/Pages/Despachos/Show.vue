<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ venta: Object });
const page = usePage();

const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
const stockDe = (d) => d.producto?.inventario?.stock_actual ?? 0;
const hayFaltante = computed(() => (props.venta.detalles ?? []).some((d) => d.producto_id && stockDe(d) < d.cantidad));

const despachar = () => {
    if (confirm('¿Despachar esta venta? Se descontará el stock y quedará COMPLETADA.')) {
        router.post(route('despachos.despachar', props.venta.id));
    }
};
</script>

<template>
    <Head title="Preparar despacho" />
    <AppLayout title="Preparar despacho">
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1">{{ venta.numero_venta }} <span class="badge bg-info text-dark ms-2">PAGADA</span></h5>
                    <div class="fw-semibold">{{ venta.cliente?.user?.name }}</div>
                    <div class="small"><i class="bi bi-geo-alt me-1"></i><strong>Dirección:</strong> {{ venta.cliente?.user?.direccion || 'No registrada' }}</div>
                    <div class="small"><i class="bi bi-telephone me-1"></i>{{ venta.cliente?.user?.telefono || '—' }}</div>
                    <div class="small text-muted mt-1">{{ new Date(venta.fecha).toLocaleString() }} · Vendedor: {{ venta.vendedor?.name || '—' }} · Total {{ fmt(venta.monto_total) }}</div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" :disabled="hayFaltante" @click="despachar">
                        <i class="bi bi-box-arrow-right me-1"></i>Despachar
                    </button>
                    <Link :href="route('despachos.index')" class="btn btn-outline-secondary">Volver</Link>
                </div>
            </div>
        </div>

        <div v-if="hayFaltante" class="alert alert-warning py-2">
            <i class="bi bi-exclamation-triangle me-1"></i>Hay ítems sin stock suficiente. Reponé stock antes de despachar.
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">Ítems a preparar</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Stock actual</th></tr></thead>
                    <tbody>
                        <tr v-for="d in venta.detalles" :key="d.id">
                            <td>{{ d.producto ? `${d.producto.codigo} — ${d.producto.nombre}` : (d.descripcion || 'Servicio') }}</td>
                            <td class="text-end fw-semibold">{{ d.cantidad }}</td>
                            <td class="text-end" :class="{ 'text-danger fw-semibold': d.producto_id && stockDe(d) < d.cantidad }">
                                {{ d.producto_id ? stockDe(d) : '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
