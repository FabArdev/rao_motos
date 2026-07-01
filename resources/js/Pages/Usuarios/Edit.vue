<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ usuario: Object, roles: Array });

const form = useForm({
    nombre: props.usuario.nombre,
    apellidos: props.usuario.apellidos,
    ci: props.usuario.ci,
    telefono: props.usuario.telefono,
    direccion: props.usuario.direccion ?? '',
    email: props.usuario.email,
    password: '',
    password_confirmation: '',
    role_id: props.usuario.role_id,
    estado: !!props.usuario.estado,
    nit_ci: props.usuario.cliente?.nit_ci ?? '',
});

const esCliente = computed(() => props.roles.find((r) => r.id == form.role_id)?.nombre === 'cliente');

const enviar = () => form.put(route('usuarios.update', props.usuario.id));
</script>

<template>
    <Head title="Editar usuario" />

    <AppLayout title="Editar usuario">
        <div class="card shadow-sm border-0" style="max-width: 760px;">
            <div class="card-body">
                <form @submit.prevent="enviar" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre</label>
                        <input v-model="form.nombre" class="form-control" :class="{ 'is-invalid': form.errors.nombre }" />
                        <div class="invalid-feedback">{{ form.errors.nombre }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Apellidos</label>
                        <input v-model="form.apellidos" class="form-control" :class="{ 'is-invalid': form.errors.apellidos }" />
                        <div class="invalid-feedback">{{ form.errors.apellidos }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">CI</label>
                        <input v-model="form.ci" class="form-control" :class="{ 'is-invalid': form.errors.ci }" />
                        <div class="invalid-feedback">{{ form.errors.ci }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Teléfono</label>
                        <input v-model="form.telefono" class="form-control" :class="{ 'is-invalid': form.errors.telefono }" />
                        <div class="invalid-feedback">{{ form.errors.telefono }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rol</label>
                        <select v-model="form.role_id" class="form-select" :class="{ 'is-invalid': form.errors.role_id }">
                            <option v-for="r in roles" :key="r.id" :value="r.id" class="text-capitalize">{{ r.nombre }}</option>
                        </select>
                        <div class="invalid-feedback">{{ form.errors.role_id }}</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Dirección</label>
                        <input v-model="form.direccion" class="form-control" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': form.errors.email }" />
                        <div class="invalid-feedback">{{ form.errors.email }}</div>
                    </div>
                    <div v-if="esCliente" class="col-md-6">
                        <label class="form-label">NIT / CI de facturación</label>
                        <input v-model="form.nit_ci" class="form-control" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nueva contraseña <small class="text-muted">(dejar vacío para no cambiar)</small></label>
                        <input v-model="form.password" type="password" class="form-control" :class="{ 'is-invalid': form.errors.password }" />
                        <div class="invalid-feedback">{{ form.errors.password }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirmar contraseña</label>
                        <input v-model="form.password_confirmation" type="password" class="form-control" />
                    </div>
                    <div class="col-12 form-check ms-2">
                        <input v-model="form.estado" type="checkbox" class="form-check-input" id="estado" />
                        <label class="form-check-label" for="estado">Usuario activo</label>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" :disabled="form.processing">Actualizar</button>
                        <Link :href="route('usuarios.index')" class="btn btn-outline-secondary">Cancelar</Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
