<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ creditos: Object, filtros: Object, resumen: Object });
const page = usePage();

const filtrar = (estado) => router.get(route('creditos.index'), { estado }, { preserveState: true, replace: true });

const badge = (e) => ({ VIGENTE: 'bg-info text-dark', PAGADO: 'bg-success', MOROSO: 'bg-danger' }[e] ?? 'bg-secondary');
const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
</script>

<template>
    <Head title="Créditos" />
    <AppLayout title="Créditos y cobranza">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="card border-0 shadow-sm text-bg-info"><div class="card-body"><div class="small">Vigentes</div><div class="fs-3 fw-bold">{{ resumen.vigentes }}</div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm text-bg-danger"><div class="card-body"><div class="small">Morosos</div><div class="fs-3 fw-bold">{{ resumen.morosos }}</div></div></div></div>
            <div class="col-md-4"><div class="card border-0 shadow-sm text-bg-success"><div class="card-body"><div class="small">Pagados</div><div class="fs-3 fw-bold">{{ resumen.pagados }}</div></div></div></div>
        </div>

        <div class="btn-group mb-3">
            <button class="btn btn-sm" :class="!filtros?.estado ? 'btn-dark' : 'btn-outline-dark'" @click="filtrar('')">Todos</button>
            <button class="btn btn-sm" :class="filtros?.estado === 'VIGENTE' ? 'btn-dark' : 'btn-outline-dark'" @click="filtrar('VIGENTE')">Vigentes</button>
            <button class="btn btn-sm" :class="filtros?.estado === 'MOROSO' ? 'btn-dark' : 'btn-outline-dark'" @click="filtrar('MOROSO')">Morosos</button>
            <button class="btn btn-sm" :class="filtros?.estado === 'PAGADO' ? 'btn-dark' : 'btn-outline-dark'" @click="filtrar('PAGADO')">Pagados</button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Venta</th><th>Cliente</th><th>Cuotas</th><th>Interés</th><th class="text-end">Saldo</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="c in creditos.data" :key="c.id">
                            <td>{{ c.id }}</td>
                            <td>{{ c.venta?.numero_venta }}</td>
                            <td>{{ c.venta?.cliente?.usuario?.nombre_completo }}</td>
                            <td>{{ c.numero_cuotas }}</td>
                            <td>{{ c.tasa_interes }}%</td>
                            <td class="text-end">{{ fmt(c.saldo_pendiente) }}</td>
                            <td><span class="badge" :class="badge(c.estado)">{{ c.estado }}</span></td>
                            <td class="text-end"><Link :href="route('creditos.show', c.id)" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></Link></td>
                        </tr>
                        <tr v-if="!creditos.data.length"><td colspan="8" class="text-center text-muted py-4">No hay créditos.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav v-if="creditos.links.length > 3" class="mt-3">
            <ul class="pagination pagination-sm">
                <li v-for="(link, i) in creditos.links" :key="i" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                    <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" preserve-scroll />
                    <span v-else class="page-link" v-html="link.label" />
                </li>
            </ul>
        </nav>
    </AppLayout>
</template>
