<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    proveedores: Object,
    filtros: Object,
});

const page = usePage();
const q = ref(props.filtros?.q ?? '');

const buscar = () => router.get(route('proveedores.index'), { q: q.value }, { preserveState: true, replace: true });

const eliminar = (p) => {
    if (confirm(`¿Desactivar al proveedor "${p.razon_social}"?`)) {
        router.delete(route('proveedores.destroy', p.id));
    }
};
</script>

<template>
    <Head title="Proveedores" />

    <AppLayout title="Proveedores">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <form class="d-flex gap-2" @submit.prevent="buscar" style="max-width: 360px;">
                <input v-model="q" type="text" class="form-control" placeholder="Buscar razón social, contacto o NIT..." />
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <Link :href="route('proveedores.create')" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nuevo proveedor
            </Link>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Razón social</th><th>Contacto</th><th>NIT</th><th>Teléfono</th>
                            <th>Compras</th><th>Estado</th><th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in proveedores.data" :key="p.id">
                            <td class="fw-semibold">{{ p.razon_social }}</td>
                            <td>{{ p.contacto_principal || '—' }}</td>
                            <td>{{ p.nit || '—' }}</td>
                            <td>{{ p.telefono || '—' }}</td>
                            <td><span class="badge bg-info text-dark">{{ p.compras_count }}</span></td>
                            <td>
                                <span class="badge" :class="p.activo ? 'bg-success' : 'bg-danger'">
                                    {{ p.activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <Link :href="route('proveedores.show', p.id)" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></Link>
                                <Link :href="route('proveedores.edit', p.id)" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></Link>
                                <button class="btn btn-sm btn-light text-danger" @click="eliminar(p)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <tr v-if="!proveedores.data.length">
                            <td colspan="7" class="text-center text-muted py-4">No se encontraron proveedores.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav v-if="proveedores.links.length > 3" class="mt-3">
            <ul class="pagination pagination-sm">
                <li v-for="(link, i) in proveedores.links" :key="i" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                    <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" preserve-scroll />
                    <span v-else class="page-link" v-html="link.label" />
                </li>
            </ul>
        </nav>
    </AppLayout>
</template>
