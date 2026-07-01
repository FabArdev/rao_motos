<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ pedidos: Array, ordenes: Array, creditos: Array, motos: Array });

const fmt = (n) => `Bs. ${Number(n ?? 0).toFixed(2)}`;
const badge = (e) => ({ VIGENTE: 'bg-info text-dark', PAGADO: 'bg-success', MOROSO: 'bg-danger', SOLICITADO: 'bg-warning text-dark', APROBADO: 'bg-primary', DESPACHADO: 'bg-success', RECHAZADO: 'bg-danger', RECIBIDA: 'bg-secondary', DIAGNOSTICADA: 'bg-warning text-dark', EN_REPARACION: 'bg-info text-dark', TERMINADA: 'bg-primary', ENTREGADA: 'bg-success', CANCELADA: 'bg-danger' }[e] ?? 'bg-secondary');
</script>

<template>
    <Head title="Mi cuenta" />
    <AppLayout title="Mi cuenta">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold d-flex justify-content-between"><span><i class="bi bi-bag me-1"></i>Mis pedidos</span><Link :href="route('mis-pedidos.index')" class="small">Ver todos</Link></div>
                    <ul class="list-group list-group-flush">
                        <li v-for="p in pedidos" :key="p.id" class="list-group-item d-flex justify-content-between">
                            <span>Pedido #{{ p.id }} · {{ new Date(p.fecha).toLocaleDateString() }}</span>
                            <span class="badge" :class="badge(p.estado)">{{ p.estado }}</span>
                        </li>
                        <li v-if="!pedidos.length" class="list-group-item text-muted small text-center">Sin pedidos.</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold d-flex justify-content-between"><span><i class="bi bi-tools me-1"></i>Mi taller</span><Link :href="route('mi-taller.index')" class="small">Ver todas</Link></div>
                    <ul class="list-group list-group-flush">
                        <li v-for="o in ordenes" :key="o.id" class="list-group-item d-flex justify-content-between">
                            <span>{{ o.moto?.marca }} {{ o.moto?.modelo }} ({{ o.moto?.placa }})</span>
                            <span class="badge" :class="badge(o.estado)">{{ o.estado }}</span>
                        </li>
                        <li v-if="!ordenes.length" class="list-group-item text-muted small text-center">Sin órdenes de taller.</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold d-flex justify-content-between"><span><i class="bi bi-credit-card me-1"></i>Mis créditos</span><Link :href="route('mis-creditos.index')" class="small">Ver todos</Link></div>
                    <ul class="list-group list-group-flush">
                        <li v-for="c in creditos" :key="c.id" class="list-group-item d-flex justify-content-between">
                            <Link :href="route('mis-creditos.show', c.id)">Crédito #{{ c.id }} · {{ c.venta?.numero_venta }}</Link>
                            <span><span class="badge me-2" :class="badge(c.estado)">{{ c.estado }}</span>{{ fmt(c.saldo_pendiente) }}</span>
                        </li>
                        <li v-if="!creditos.length" class="list-group-item text-muted small text-center">Sin créditos.</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold"><i class="bi bi-bicycle me-1"></i>Mis motos</div>
                    <ul class="list-group list-group-flush">
                        <li v-for="m in motos" :key="m.id" class="list-group-item">{{ m.marca }} {{ m.modelo }} {{ m.anio }} · <span class="text-muted">{{ m.placa }}</span></li>
                        <li v-if="!motos.length" class="list-group-item text-muted small text-center">Sin motos registradas.</li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
