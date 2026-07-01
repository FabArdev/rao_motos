<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ usuario: Object });
</script>

<template>
    <Head title="Detalle de usuario" />

    <AppLayout title="Detalle de usuario">
        <div class="card shadow-sm border-0" style="max-width: 640px;">
            <div class="card-body">
                <h2 class="h5">{{ usuario.nombre }} {{ usuario.apellidos }}</h2>
                <span class="badge text-uppercase bg-primary mb-3">{{ usuario.role?.nombre }}</span>

                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">CI</dt><dd class="col-sm-8">{{ usuario.ci }}</dd>
                    <dt class="col-sm-4 text-muted">Email</dt><dd class="col-sm-8">{{ usuario.email }}</dd>
                    <dt class="col-sm-4 text-muted">Teléfono</dt><dd class="col-sm-8">{{ usuario.telefono }}</dd>
                    <dt class="col-sm-4 text-muted">Dirección</dt><dd class="col-sm-8">{{ usuario.direccion || '—' }}</dd>
                    <dt class="col-sm-4 text-muted">Estado</dt>
                    <dd class="col-sm-8">
                        <span class="badge" :class="usuario.estado ? 'bg-success' : 'bg-danger'">
                            {{ usuario.estado ? 'Activo' : 'Inactivo' }}
                        </span>
                    </dd>
                    <template v-if="usuario.cliente">
                        <dt class="col-sm-4 text-muted">NIT facturación</dt><dd class="col-sm-8">{{ usuario.cliente.nit_ci || '—' }}</dd>
                    </template>
                </dl>

                <div class="mt-4 d-flex gap-2">
                    <Link :href="route('usuarios.edit', usuario.id)" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Editar</Link>
                    <Link :href="route('usuarios.index')" class="btn btn-outline-secondary">Volver</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
