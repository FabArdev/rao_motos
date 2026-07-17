<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ pedidos: Object, filtros: Object });
const page = usePage();
const estado = ref(props.filtros?.estado ?? '');

const filtrar = () => router.get(route('pedidos.index'), { estado: estado.value }, { preserveState: true, replace: true });

const badge = (e) => ({ SOLICITADO: 'bg-warning text-dark', APROBADO: 'bg-primary', RECHAZADO: 'bg-danger', EN_PROCESO: 'bg-info text-dark', DESPACHADO: 'bg-success', ANULADO: 'bg-secondary' }[e] ?? 'bg-secondary');

// Estado de pago/despacho derivado de la venta asociada.
const pago = (p) => {
    if (!p.venta) return { txt: '—', cls: 'bg-light text-muted border' };
    return {
        PENDIENTE: { txt: 'Por cobrar', cls: 'bg-warning text-dark' },
        PAGADA: { txt: 'Pagado', cls: 'bg-info text-dark' },
        COMPLETADA: { txt: 'Despachado', cls: 'bg-success' },
        ANULADA: { txt: 'Anulada', cls: 'bg-danger' },
    }[p.venta.estado] ?? { txt: p.venta.estado, cls: 'bg-secondary' };
};
</script>

<template>
    <Head title="Pedidos" />
    <AppLayout title="Pedidos">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="mb-3">
            <select v-model="estado" class="form-select" style="max-width: 240px;" @change="filtrar">
                <option value="">Todos los estados</option>
                <option value="SOLICITADO">Solicitados</option>
                <option value="APROBADO">Aprobados</option>
                <option value="DESPACHADO">Despachados</option>
                <option value="RECHAZADO">Rechazados</option>
            </select>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Cliente</th><th>Fecha</th><th>Ítems</th><th>Venta</th><th>Estado</th><th>Pago / Despacho</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="p in pedidos.data" :key="p.id">
                            <td>{{ p.id }}</td>
                            <td>{{ p.cliente?.usuario?.nombre_completo }}</td>
                            <td>{{ new Date(p.fecha).toLocaleDateString() }}</td>
                            <td>{{ p.detalles_count }}</td>
                            <td>{{ p.venta?.numero_venta || '—' }}</td>
                            <td><span class="badge" :class="badge(p.estado)">{{ p.estado }}</span></td>
                            <td><span class="badge" :class="pago(p).cls">{{ pago(p).txt }}</span></td>
                            <td class="text-end"><Link :href="route('pedidos.show', p.id)" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></Link></td>
                        </tr>
                        <tr v-if="!pedidos.data.length"><td colspan="8" class="text-center text-muted py-4">No hay pedidos.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav v-if="pedidos.links.length > 3" class="mt-3">
            <ul class="pagination pagination-sm">
                <li v-for="(link, i) in pedidos.links" :key="i" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                    <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" preserve-scroll />
                    <span v-else class="page-link" v-html="link.label" />
                </li>
            </ul>
        </nav>
    </AppLayout>
</template>
