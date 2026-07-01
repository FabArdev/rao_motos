<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ compras: Object, filtros: Object });
const page = usePage();
const estado = ref(props.filtros?.estado ?? '');

const filtrar = () => router.get(route('compras.index'), { estado: estado.value }, { preserveState: true, replace: true });

const badge = (e) => ({ PENDIENTE: 'bg-warning text-dark', RECIBIDA: 'bg-success', ANULADA: 'bg-danger' }[e] ?? 'bg-secondary');
const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
</script>

<template>
    <Head title="Compras" />
    <AppLayout title="Compras a proveedores">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <select v-model="estado" class="form-select" style="max-width: 220px;" @change="filtrar">
                <option value="">Todos los estados</option>
                <option value="PENDIENTE">Pendientes</option>
                <option value="RECIBIDA">Recibidas</option>
                <option value="ANULADA">Anuladas</option>
            </select>
            <Link :href="route('compras.create')" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva compra</Link>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Proveedor</th><th>Fecha</th><th>Ítems</th><th class="text-end">Total</th><th>Estado</th><th class="text-end">Acciones</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in compras.data" :key="c.id">
                            <td>{{ c.id }}</td>
                            <td>{{ c.proveedor?.razon_social }}</td>
                            <td>{{ new Date(c.fecha).toLocaleDateString() }}</td>
                            <td>{{ c.detalles_count }}</td>
                            <td class="text-end">{{ fmt(c.total) }}</td>
                            <td><span class="badge" :class="badge(c.estado)">{{ c.estado }}</span></td>
                            <td class="text-end">
                                <Link :href="route('compras.show', c.id)" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></Link>
                            </td>
                        </tr>
                        <tr v-if="!compras.data.length"><td colspan="7" class="text-center text-muted py-4">No hay compras registradas.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav v-if="compras.links.length > 3" class="mt-3">
            <ul class="pagination pagination-sm">
                <li v-for="(link, i) in compras.links" :key="i" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                    <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" preserve-scroll />
                    <span v-else class="page-link" v-html="link.label" />
                </li>
            </ul>
        </nav>
    </AppLayout>
</template>
