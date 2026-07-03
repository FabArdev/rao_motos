<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ compras: Array, pedidos: Array, creditos: Array });

const fmt = (n) => `Bs. ${Number(n ?? 0).toFixed(2)}`;
const nombresProductos = (v) => (v.detalles ?? []).map((d) => d.producto?.nombre ?? d.descripcion).filter(Boolean).join(', ');
const badge = (e) => ({ VIGENTE: 'bg-info text-dark', PAGADO: 'bg-success', MOROSO: 'bg-danger', SOLICITADO: 'bg-warning text-dark', APROBADO: 'bg-primary', DESPACHADO: 'bg-success', RECHAZADO: 'bg-danger', RECIBIDA: 'bg-secondary', DIAGNOSTICADA: 'bg-warning text-dark', EN_REPARACION: 'bg-info text-dark', TERMINADA: 'bg-primary', ENTREGADA: 'bg-success', CANCELADA: 'bg-danger' }[e] ?? 'bg-secondary');
</script>

<template>
    <Head title="Mi cuenta" />
    <AppLayout title="Mi cuenta">
        <div class="row g-3">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold"><i class="bi bi-cart-check me-1"></i>Mis compras</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr><th>Venta</th><th>Fecha</th><th>Tipo</th><th>Productos</th><th class="text-end">Total</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="v in compras" :key="v.id">
                                    <td class="text-nowrap">{{ v.numero_venta }}</td>
                                    <td class="text-nowrap">{{ new Date(v.fecha).toLocaleDateString() }}</td>
                                    <td>
                                        <span class="badge" :class="v.tipo_venta === 'CREDITO' ? 'bg-info text-dark' : 'bg-success'">
                                            {{ v.tipo_venta === 'CREDITO' ? 'Crédito' : 'Contado' }}
                                        </span>
                                    </td>
                                    <td class="small text-muted">{{ nombresProductos(v) || '—' }}</td>
                                    <td class="text-end fw-semibold">{{ fmt(v.monto_total) }}</td>
                                </tr>
                                <tr v-if="!compras.length"><td colspan="5" class="text-center text-muted small py-3">Aún no tienes compras registradas.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

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
        </div>
    </AppLayout>
</template>
