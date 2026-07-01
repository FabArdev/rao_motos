<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ ordenes: Object });
const page = usePage();

const badge = (e) => ({ RECIBIDA: 'bg-secondary', DIAGNOSTICADA: 'bg-warning text-dark', EN_REPARACION: 'bg-info text-dark', TERMINADA: 'bg-primary', ENTREGADA: 'bg-success', CANCELADA: 'bg-danger' }[e] ?? 'bg-secondary');
</script>

<template>
    <Head title="Mi taller" />
    <AppLayout title="Mi moto en el taller">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Moto</th><th>Ingreso</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="o in ordenes.data" :key="o.id">
                            <td>{{ o.id }}</td>
                            <td>{{ o.moto?.marca }} {{ o.moto?.modelo }} <span class="text-muted small">{{ o.moto?.placa }}</span></td>
                            <td>{{ new Date(o.fecha_ingreso).toLocaleDateString() }}</td>
                            <td><span class="badge" :class="badge(o.estado)">{{ o.estado }}</span></td>
                            <td class="text-end"><Link :href="route('mi-taller.show', o.id)" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></Link></td>
                        </tr>
                        <tr v-if="!ordenes.data.length"><td colspan="5" class="text-center text-muted py-4">No tienes órdenes de taller.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
