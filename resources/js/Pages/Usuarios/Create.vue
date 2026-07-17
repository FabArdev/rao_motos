<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ roles: Array });

const form = useForm({
    nombre: '', apellidos: '', ci: '', telefono: '', direccion: '',
    correo: '', password: '', password_confirmation: '',
    rol_id: '', estado: true, nit_ci: '',
});

const esCliente = computed(() => props.roles.find((r) => r.id == form.rol_id)?.nombre === 'cliente');

const enviar = () => form.post(route('usuarios.store'));
</script>

<template>
    <Head title="Nuevo usuario" />

    <AppLayout title="Nuevo usuario">
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
                        <input v-model="form.ci" inputmode="numeric" maxlength="20" class="form-control" :class="{ 'is-invalid': form.errors.ci }" />
                        <div class="invalid-feedback">{{ form.errors.ci }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Teléfono</label>
                        <input v-model="form.telefono" inputmode="tel" maxlength="15" class="form-control" :class="{ 'is-invalid': form.errors.telefono }" />
                        <div class="invalid-feedback">{{ form.errors.telefono }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Rol</label>
                        <select v-model="form.rol_id" class="form-select" :class="{ 'is-invalid': form.errors.rol_id }">
                            <option value="" disabled>Seleccione...</option>
                            <option v-for="r in roles" :key="r.id" :value="r.id" class="text-capitalize">{{ r.nombre }}</option>
                        </select>
                        <div class="invalid-feedback">{{ form.errors.rol_id }}</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Dirección</label>
                        <input v-model="form.direccion" maxlength="255" class="form-control" :class="{ 'is-invalid': form.errors.direccion }" />
                        <div class="invalid-feedback">{{ form.errors.direccion }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input v-model="form.correo" type="email" class="form-control" :class="{ 'is-invalid': form.errors.correo }" />
                        <div class="invalid-feedback">{{ form.errors.correo }}</div>
                    </div>
                    <div v-if="esCliente" class="col-md-6">
                        <label class="form-label">NIT / CI de facturación</label>
                        <input v-model="form.nit_ci" inputmode="numeric" maxlength="20" class="form-control" :class="{ 'is-invalid': form.errors.nit_ci }" />
                        <div class="invalid-feedback">{{ form.errors.nit_ci }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contraseña</label>
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
                        <button class="btn btn-primary" :disabled="form.processing">Guardar</button>
                        <Link :href="route('usuarios.index')" class="btn btn-outline-secondary">Cancelar</Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
