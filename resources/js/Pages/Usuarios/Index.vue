<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    usuarios: Object,
    filtros: Object,
});

const page = usePage();
const q = ref(props.filtros?.q ?? '');

const buscar = () => router.get(route('usuarios.index'), { q: q.value }, { preserveState: true, replace: true });

const eliminar = (usuario) => {
    if (confirm(`¿Eliminar a ${usuario.nombre} ${usuario.apellidos}?`)) {
        router.delete(route('usuarios.destroy', usuario.id));
    }
};

const colorRol = (rol) => ({
    admin: 'bg-dark', vendedor: 'bg-primary', almacenero: 'bg-success',
    mecanico: 'bg-warning text-dark', cliente: 'bg-secondary',
}[rol] ?? 'bg-secondary');
</script>

<template>
    <Head title="Usuarios" />

    <AppLayout title="Gestión de usuarios">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <form class="d-flex gap-2" @submit.prevent="buscar" style="max-width: 360px;">
                <input v-model="q" type="text" class="form-control" placeholder="Buscar por nombre, email o CI..." />
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <Link :href="route('usuarios.create')" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nuevo usuario
            </Link>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th><th>Email</th><th>CI</th><th>Rol</th><th>Estado</th><th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in usuarios.data" :key="u.id">
                            <td>{{ u.nombre }} {{ u.apellidos }}</td>
                            <td>{{ u.email }}</td>
                            <td>{{ u.ci }}</td>
                            <td><span class="badge text-uppercase" :class="colorRol(u.role?.nombre)">{{ u.role?.nombre }}</span></td>
                            <td>
                                <span class="badge" :class="u.estado ? 'bg-success' : 'bg-danger'">
                                    {{ u.estado ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <Link :href="route('usuarios.show', u.id)" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></Link>
                                <Link :href="route('usuarios.edit', u.id)" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></Link>
                                <button class="btn btn-sm btn-light text-danger" @click="eliminar(u)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <tr v-if="!usuarios.data.length">
                            <td colspan="6" class="text-center text-muted py-4">No se encontraron usuarios.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav v-if="usuarios.links.length > 3" class="mt-3">
            <ul class="pagination pagination-sm">
                <li v-for="(link, i) in usuarios.links" :key="i" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                    <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" preserve-scroll />
                    <span v-else class="page-link" v-html="link.label" />
                </li>
            </ul>
        </nav>
    </AppLayout>
</template>
