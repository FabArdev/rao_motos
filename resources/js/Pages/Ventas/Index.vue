<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ ventas: Object, filtros: Object });
const page = usePage();
const q = ref(props.filtros?.q ?? '');

const buscar = () => router.get(route('ventas.index'), { q: q.value }, { preserveState: true, replace: true });

const badge = (e) => ({ COMPLETADA: 'bg-success', PENDIENTE: 'bg-warning text-dark', ANULADA: 'bg-danger' }[e] ?? 'bg-secondary');
const creditoBadge = (e) => ({ VIGENTE: 'bg-info text-dark', MOROSO: 'bg-danger', PAGADO: 'bg-success' }[e] ?? 'bg-secondary');
const creditoLabel = (e) => ({ VIGENTE: 'Crédito vigente', MOROSO: 'Moroso', PAGADO: 'Pagado' }[e] ?? e);
const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
</script>

<template>
    <Head title="Ventas" />
    <AppLayout title="Ventas">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <form class="d-flex gap-2" @submit.prevent="buscar" style="max-width: 320px;">
                <input v-model="q" type="text" class="form-control" placeholder="Buscar N° de venta..." />
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <Link :href="route('ventas.create')" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva venta</Link>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>N°</th><th>Cliente</th><th>Vendedor</th><th>Fecha</th><th>Tipo</th><th>Método</th><th class="text-end">Total</th><th>Estado</th><th></th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="v in ventas.data" :key="v.id">
                            <td class="fw-semibold">{{ v.numero_venta }}</td>
                            <td>{{ v.cliente?.user?.name }}</td>
                            <td>{{ v.vendedor?.name || '—' }}</td>
                            <td>{{ new Date(v.fecha).toLocaleDateString() }}</td>
                            <td><span class="badge" :class="v.tipo_venta === 'CREDITO' ? 'bg-info text-dark' : 'bg-secondary'">{{ v.tipo_venta }}</span></td>
                            <td>{{ v.metodo_pago }}</td>
                            <td class="text-end">{{ fmt(v.monto_total) }}</td>
                            <td>
                                <template v-if="v.tipo_venta === 'CREDITO' && v.credito">
                                    <span class="badge" :class="creditoBadge(v.credito.estado)">{{ creditoLabel(v.credito.estado) }}</span>
                                    <div class="small text-muted">{{ v.credito.cuotas_pagadas }}/{{ v.credito.cuotas_total }} cuotas</div>
                                </template>
                                <span v-else class="badge" :class="badge(v.estado)">{{ v.estado }}</span>
                            </td>
                            <td class="text-end"><Link :href="route('ventas.show', v.id)" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></Link></td>
                        </tr>
                        <tr v-if="!ventas.data.length"><td colspan="9" class="text-center text-muted py-4">No hay ventas registradas.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav v-if="ventas.links.length > 3" class="mt-3">
            <ul class="pagination pagination-sm">
                <li v-for="(link, i) in ventas.links" :key="i" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                    <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" preserve-scroll />
                    <span v-else class="page-link" v-html="link.label" />
                </li>
            </ul>
        </nav>
    </AppLayout>
</template>
