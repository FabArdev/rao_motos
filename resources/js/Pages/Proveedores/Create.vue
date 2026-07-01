<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    razon_social: '', contacto_principal: '', nit: '', telefono: '', activo: true,
});

const enviar = () => form.post(route('proveedores.store'));
</script>

<template>
    <Head title="Nuevo proveedor" />

    <AppLayout title="Nuevo proveedor">
        <div class="card shadow-sm border-0" style="max-width: 680px;">
            <div class="card-body">
                <form @submit.prevent="enviar" class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Razón social</label>
                        <input v-model="form.razon_social" class="form-control" :class="{ 'is-invalid': form.errors.razon_social }" />
                        <div class="invalid-feedback">{{ form.errors.razon_social }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contacto principal</label>
                        <input v-model="form.contacto_principal" class="form-control" :class="{ 'is-invalid': form.errors.contacto_principal }" />
                        <div class="invalid-feedback">{{ form.errors.contacto_principal }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">NIT</label>
                        <input v-model="form.nit" class="form-control" :class="{ 'is-invalid': form.errors.nit }" />
                        <div class="invalid-feedback">{{ form.errors.nit }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Teléfono</label>
                        <input v-model="form.telefono" class="form-control" :class="{ 'is-invalid': form.errors.telefono }" />
                        <div class="invalid-feedback">{{ form.errors.telefono }}</div>
                    </div>
                    <div class="col-12 form-check ms-2">
                        <input v-model="form.activo" type="checkbox" class="form-check-input" id="activo" />
                        <label class="form-check-label" for="activo">Proveedor activo</label>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" :disabled="form.processing">Guardar</button>
                        <Link :href="route('proveedores.index')" class="btn btn-outline-secondary">Cancelar</Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
