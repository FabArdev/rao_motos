<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ ventas: Object, filtros: Object });
const page = usePage();
const q = ref(props.filtros?.q ?? '');

const buscar = () => router.get(route('despachos.index'), { q: q.value }, { preserveState: true, replace: true });
const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
</script>

<template>
    <Head title="Despachos" />
    <AppLayout title="Cola de despacho">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <p class="text-muted">Ventas <strong>pagadas</strong> listas para preparar y entregar.</p>

        <form class="d-flex gap-2 mb-3" @submit.prevent="buscar" style="max-width: 320px;">
            <input v-model="q" type="text" class="form-control" placeholder="Buscar N° de venta..." />
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
        </form>

        <div v-if="!ventas.data.length" class="text-center text-muted py-5">
            <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>No hay ventas pendientes de despacho.
        </div>

        <div v-else class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>N°</th><th>Cliente</th><th>Fecha</th><th class="text-end">Ítems</th><th class="text-end">Total</th><th></th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="v in ventas.data" :key="v.id">
                            <td class="fw-semibold">{{ v.numero_venta }}</td>
                            <td>{{ v.cliente?.user?.name }}</td>
                            <td>{{ new Date(v.fecha).toLocaleDateString() }}</td>
                            <td class="text-end">{{ v.detalles_count }}</td>
                            <td class="text-end">{{ fmt(v.monto_total) }}</td>
                            <td class="text-end">
                                <Link :href="route('despachos.show', v.id)" class="btn btn-sm btn-primary"><i class="bi bi-box-arrow-right me-1"></i>Preparar</Link>
                            </td>
                        </tr>
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
