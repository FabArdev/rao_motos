<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ creditos: Object });

const badge = (e) => ({ VIGENTE: 'bg-info text-dark', PAGADO: 'bg-success', MOROSO: 'bg-danger' }[e] ?? 'bg-secondary');
const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
</script>

<template>
    <Head title="Mis créditos" />
    <AppLayout title="Mis créditos">
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Venta</th><th>Cuotas</th><th>Estado</th><th class="text-end">Saldo</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="c in creditos.data" :key="c.id">
                            <td>{{ c.id }}</td>
                            <td>{{ c.venta?.numero_venta }}</td>
                            <td>{{ c.numero_cuotas }}</td>
                            <td><span class="badge" :class="badge(c.estado)">{{ c.estado }}</span></td>
                            <td class="text-end">{{ fmt(c.saldo_pendiente) }}</td>
                            <td class="text-end"><Link :href="route('mis-creditos.show', c.id)" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></Link></td>
                        </tr>
                        <tr v-if="!creditos.data.length"><td colspan="6" class="text-center text-muted py-4">No tienes créditos.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
