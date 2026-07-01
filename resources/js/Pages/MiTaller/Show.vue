<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ orden: Object });
const page = usePage();

const badge = (e) => ({ RECIBIDA: 'bg-secondary', DIAGNOSTICADA: 'bg-warning text-dark', EN_REPARACION: 'bg-info text-dark', TERMINADA: 'bg-primary', ENTREGADA: 'bg-success', CANCELADA: 'bg-danger' }[e] ?? 'bg-secondary');
const fmt = (n) => `Bs. ${Number(n ?? 0).toFixed(2)}`;

const aprobar = () => { if (confirm('¿Aprobar el presupuesto? Se iniciará la reparación.')) router.post(route('mi-taller.aprobar-presupuesto', props.orden.id), {}, { preserveScroll: true }); };
const rechazar = () => { if (confirm('¿Rechazar el presupuesto? La orden se cancelará.')) router.post(route('mi-taller.rechazar-presupuesto', props.orden.id), {}, { preserveScroll: true }); };
</script>

<template>
    <Head title="Mi orden de taller" />
    <AppLayout title="Estado de mi moto">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body">
                <h5 class="fw-bold mb-1">Orden #{{ orden.id }} <span class="badge ms-2" :class="badge(orden.estado)">{{ orden.estado }}</span></h5>
                <div class="text-muted">{{ orden.moto?.marca }} {{ orden.moto?.modelo }} ({{ orden.moto?.placa }})</div>
                <div class="mt-2"><strong>Problema reportado:</strong> {{ orden.descripcion_problema }}</div>
                <div v-if="orden.diagnostico" class="mt-1"><strong>Diagnóstico:</strong> {{ orden.diagnostico }}</div>
            </div>
        </div>

        <div v-if="orden.costo_estimado_mano_obra !== null" class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white fw-semibold">Presupuesto</div>
            <div class="card-body">
                <div class="d-flex justify-content-between"><span>Mano de obra estimada</span><span>{{ fmt(orden.costo_estimado_mano_obra) }}</span></div>
                <div class="d-flex justify-content-between"><span>Repuestos estimados</span><span>{{ fmt(orden.costo_estimado_repuestos) }}</span></div>
                <hr class="my-2" />
                <div class="d-flex justify-content-between fw-bold"><span>Total estimado</span><span>{{ fmt(Number(orden.costo_estimado_mano_obra) + Number(orden.costo_estimado_repuestos)) }}</span></div>

                <div v-if="orden.estado === 'DIAGNOSTICADA'" class="mt-3 d-flex gap-2">
                    <button class="btn btn-success" @click="aprobar"><i class="bi bi-check-lg me-1"></i>Aprobar presupuesto</button>
                    <button class="btn btn-outline-danger" @click="rechazar">Rechazar</button>
                </div>
                <div v-else-if="orden.presupuesto_aprobado" class="mt-2"><span class="badge bg-success">Aprobado</span></div>
            </div>
        </div>

        <div v-if="orden.venta" class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">Factura</div>
            <div class="card-body">
                <div>Venta {{ orden.venta.numero_venta }} · {{ fmt(orden.venta.monto_total) }} · {{ orden.venta.tipo_venta }}</div>
                <Link v-if="orden.venta.credito" :href="route('mis-creditos.show', orden.venta.credito.id)" class="btn btn-sm btn-outline-primary mt-2">Ver mis cuotas</Link>
            </div>
        </div>

        <Link :href="route('mi-taller.index')" class="btn btn-outline-secondary btn-sm mt-3">Volver</Link>
    </AppLayout>
</template>
