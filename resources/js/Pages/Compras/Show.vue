<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ compra: Object });
const page = usePage();

const badge = (e) => ({ PENDIENTE: 'bg-warning text-dark', RECIBIDA: 'bg-success', ANULADA: 'bg-danger' }[e] ?? 'bg-secondary');
const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;

const recibir = () => { if (confirm('¿Recibir esta compra e ingresar el stock?')) router.post(route('compras.recibir', props.compra.id)); };
const anular = () => { if (confirm('¿Anular esta compra? Si estaba recibida se revertirá el inventario.')) router.post(route('compras.anular', props.compra.id)); };
</script>

<template>
    <Head title="Compra" />
    <AppLayout title="Detalle de compra">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="fw-bold mb-1">Compra #{{ compra.id }} <span class="badge ms-2" :class="badge(compra.estado)">{{ compra.estado }}</span></h5>
                    <div class="text-muted">{{ compra.proveedor?.razon_social }} · {{ new Date(compra.fecha).toLocaleString() }}</div>
                </div>
                <div class="d-flex gap-2">
                    <button v-if="compra.estado === 'PENDIENTE'" class="btn btn-success" @click="recibir"><i class="bi bi-box-arrow-in-down me-1"></i>Recibir</button>
                    <button v-if="compra.estado !== 'ANULADA'" class="btn btn-outline-danger" @click="anular">Anular</button>
                    <Link :href="route('compras.index')" class="btn btn-outline-secondary">Volver</Link>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light"><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Precio unit.</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                        <tr v-for="d in compra.detalles" :key="d.id">
                            <td>{{ d.producto?.codigo }} — {{ d.producto?.nombre }}</td>
                            <td class="text-end">{{ d.cantidad }}</td>
                            <td class="text-end">{{ fmt(d.precio_unitario) }}</td>
                            <td class="text-end">{{ fmt(d.cantidad * d.precio_unitario) }}</td>
                        </tr>
                    </tbody>
                    <tfoot><tr class="table-light"><th colspan="3" class="text-end">Total</th><th class="text-end">{{ fmt(compra.total) }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
