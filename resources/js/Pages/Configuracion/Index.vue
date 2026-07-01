<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ parametros: Array });
const page = usePage();

const form = useForm({
    parametros: props.parametros.map((p) => ({ id: p.id, valor: p.valor })),
});

const guardar = () => form.put(route('configuracion.update'));
</script>

<template>
    <Head title="Configuración" />
    <AppLayout title="Parámetros del sistema">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>

        <div class="card shadow-sm border-0" style="max-width: 820px;">
            <div class="card-body">
                <p class="text-muted small">Valores configurables del negocio (interés, mora, cuotas). Cada uno tiene un valor por defecto sembrado.</p>
                <form @submit.prevent="guardar">
                    <div v-for="(p, i) in parametros" :key="p.id" class="row g-2 align-items-center mb-3 pb-2 border-bottom">
                        <div class="col-md-4"><strong class="font-monospace">{{ p.clave }}</strong></div>
                        <div class="col-md-3">
                            <input v-model="form.parametros[i].valor" class="form-control" :class="{ 'is-invalid': form.errors[`parametros.${i}.valor`] }" />
                        </div>
                        <div class="col-md-5 small text-muted">{{ p.descripcion }}</div>
                    </div>
                    <button class="btn btn-primary" :disabled="form.processing">Guardar cambios</button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
