<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ proveedor: Object });

const estadoBadge = (estado) => ({
    PENDIENTE: 'bg-warning text-dark', RECIBIDA: 'bg-success', ANULADA: 'bg-danger',
}[estado] ?? 'bg-secondary');

const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
</script>

<template>
    <Head title="Proveedor" />

    <AppLayout title="Detalle del proveedor">
        <div class="row g-3">
            <div class="col-md-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">{{ proveedor.razon_social }}
                            <span class="badge ms-2" :class="proveedor.activo ? 'bg-success' : 'bg-danger'">
                                {{ proveedor.activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </h5>
                        <dl class="row mb-0">
                            <dt class="col-5 text-muted">Contacto</dt><dd class="col-7">{{ proveedor.contacto_principal || '—' }}</dd>
                            <dt class="col-5 text-muted">NIT</dt><dd class="col-7">{{ proveedor.nit || '—' }}</dd>
                            <dt class="col-5 text-muted">Teléfono</dt><dd class="col-7">{{ proveedor.telefono || '—' }}</dd>
                        </dl>
                        <div class="mt-3 d-flex gap-2">
                            <Link :href="route('proveedores.edit', proveedor.id)" class="btn btn-sm btn-primary">Editar</Link>
                            <Link :href="route('proveedores.index')" class="btn btn-sm btn-outline-secondary">Volver</Link>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold">Últimas compras</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th>#</th><th>Fecha</th><th>Estado</th><th class="text-end">Total</th></tr></thead>
                            <tbody>
                                <tr v-for="c in proveedor.compras" :key="c.id">
                                    <td>{{ c.id }}</td>
                                    <td>{{ new Date(c.fecha).toLocaleDateString() }}</td>
                                    <td><span class="badge" :class="estadoBadge(c.estado)">{{ c.estado }}</span></td>
                                    <td class="text-end">{{ fmt(c.total) }}</td>
                                </tr>
                                <tr v-if="!proveedor.compras?.length"><td colspan="4" class="text-center text-muted py-3">Sin compras registradas.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
