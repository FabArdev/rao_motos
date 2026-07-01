<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ inventarios: Object, filtros: Object, totalAlertas: Number });
const page = usePage();
const q = ref(props.filtros?.q ?? '');

const buscar = () => router.get(route('inventario.index'), { q: q.value, alertas: props.filtros?.alertas ? 1 : undefined }, { preserveState: true, replace: true });
const toggleAlertas = () => router.get(route('inventario.index'), { q: q.value, alertas: props.filtros?.alertas ? undefined : 1 }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Inventario" />
    <AppLayout title="Inventario">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <form class="d-flex gap-2" @submit.prevent="buscar" style="max-width: 360px;">
                <input v-model="q" type="text" class="form-control" placeholder="Buscar producto por nombre o código..." />
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <button class="btn" :class="filtros?.alertas ? 'btn-warning' : 'btn-outline-warning'" @click="toggleAlertas">
                <i class="bi bi-exclamation-triangle me-1"></i>Stock bajo
                <span v-if="totalAlertas" class="badge bg-danger ms-1">{{ totalAlertas }}</span>
            </button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Código</th><th>Producto</th><th class="text-end">Stock actual</th><th class="text-end">Mínimo</th><th>Técnica</th><th>Estado</th><th class="text-end"></th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="inv in inventarios.data" :key="inv.id" :class="{ 'table-warning': inv.stock_actual < inv.stock_minimo }">
                            <td>{{ inv.producto?.codigo }}</td>
                            <td>{{ inv.producto?.nombre }}</td>
                            <td class="text-end fw-semibold">{{ inv.stock_actual }}</td>
                            <td class="text-end">{{ inv.stock_minimo }}</td>
                            <td class="small text-muted">{{ inv.tecnica_inventario }} / {{ inv.tecnica_costo }}</td>
                            <td>
                                <span v-if="inv.stock_actual < inv.stock_minimo" class="badge bg-danger">Bajo mínimo</span>
                                <span v-else class="badge bg-success">OK</span>
                            </td>
                            <td class="text-end"><Link :href="route('inventario.show', inv.id)" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></Link></td>
                        </tr>
                        <tr v-if="!inventarios.data.length"><td colspan="7" class="text-center text-muted py-4">Sin registros de inventario.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav v-if="inventarios.links.length > 3" class="mt-3">
            <ul class="pagination pagination-sm">
                <li v-for="(link, i) in inventarios.links" :key="i" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                    <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" preserve-scroll />
                    <span v-else class="page-link" v-html="link.label" />
                </li>
            </ul>
        </nav>
    </AppLayout>
</template>
