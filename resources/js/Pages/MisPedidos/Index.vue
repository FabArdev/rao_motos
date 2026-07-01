<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ pedidos: Object });
const page = usePage();

const badge = (e) => ({ SOLICITADO: 'bg-warning text-dark', APROBADO: 'bg-primary', RECHAZADO: 'bg-danger', EN_PROCESO: 'bg-info text-dark', DESPACHADO: 'bg-success', ANULADO: 'bg-secondary' }[e] ?? 'bg-secondary');
</script>

<template>
    <Head title="Mis pedidos" />
    <AppLayout title="Mis pedidos">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>

        <div class="mb-3"><Link :href="route('catalogo.index')" class="btn btn-primary"><i class="bi bi-shop me-1"></i>Ir al catálogo</Link></div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Fecha</th><th>Ítems</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="p in pedidos.data" :key="p.id">
                            <td>{{ p.id }}</td>
                            <td>{{ new Date(p.fecha).toLocaleDateString() }}</td>
                            <td>{{ p.detalles_count }}</td>
                            <td><span class="badge" :class="badge(p.estado)">{{ p.estado }}</span></td>
                            <td class="text-end"><Link :href="route('mis-pedidos.show', p.id)" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></Link></td>
                        </tr>
                        <tr v-if="!pedidos.data.length"><td colspan="5" class="text-center text-muted py-4">Aún no tienes pedidos.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
