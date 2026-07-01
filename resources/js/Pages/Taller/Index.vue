<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ ordenes: Object, filtros: Object });
const page = usePage();
const estado = ref(props.filtros?.estado ?? '');
const rol = page.props.auth?.user?.rol;

const filtrar = () => router.get(route('taller.index'), { estado: estado.value }, { preserveState: true, replace: true });

const badge = (e) => ({ RECIBIDA: 'bg-secondary', DIAGNOSTICADA: 'bg-warning text-dark', EN_REPARACION: 'bg-info text-dark', TERMINADA: 'bg-primary', ENTREGADA: 'bg-success', CANCELADA: 'bg-danger' }[e] ?? 'bg-secondary');
</script>

<template>
    <Head title="Taller" />
    <AppLayout title="Órdenes de taller">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <select v-model="estado" class="form-select" style="max-width: 240px;" @change="filtrar">
                <option value="">Todos los estados</option>
                <option value="RECIBIDA">Recibidas</option>
                <option value="DIAGNOSTICADA">Diagnosticadas</option>
                <option value="EN_REPARACION">En reparación</option>
                <option value="TERMINADA">Terminadas</option>
                <option value="ENTREGADA">Entregadas</option>
                <option value="CANCELADA">Canceladas</option>
            </select>
            <Link v-if="rol === 'mecanico' || rol === 'admin'" :href="route('taller.create')" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Recibir moto</Link>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Cliente</th><th>Moto</th><th>Ingreso</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="o in ordenes.data" :key="o.id">
                            <td>{{ o.id }}</td>
                            <td>{{ o.cliente?.user?.name }}</td>
                            <td>{{ o.moto?.marca }} {{ o.moto?.modelo }} <span class="text-muted small">{{ o.moto?.placa }}</span></td>
                            <td>{{ new Date(o.fecha_ingreso).toLocaleDateString() }}</td>
                            <td><span class="badge" :class="badge(o.estado)">{{ o.estado }}</span></td>
                            <td class="text-end"><Link :href="route('taller.show', o.id)" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></Link></td>
                        </tr>
                        <tr v-if="!ordenes.data.length"><td colspan="6" class="text-center text-muted py-4">No hay órdenes de taller.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav v-if="ordenes.links.length > 3" class="mt-3">
            <ul class="pagination pagination-sm">
                <li v-for="(link, i) in ordenes.links" :key="i" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                    <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" preserve-scroll />
                    <span v-else class="page-link" v-html="link.label" />
                </li>
            </ul>
        </nav>
    </AppLayout>
</template>
