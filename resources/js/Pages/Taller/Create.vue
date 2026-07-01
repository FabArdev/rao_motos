<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ clientes: Array, motos: Array });

const form = useForm({
    cliente_id: '',
    moto_id: '',
    nueva_moto: { placa: '', marca: '', modelo: '', anio: null },
    descripcion_problema: '',
});

const motosCliente = computed(() => props.motos.filter((m) => m.cliente_id == form.cliente_id));
const usarNueva = computed(() => !form.moto_id);

const enviar = () => form.post(route('taller.store'));
</script>

<template>
    <Head title="Recibir moto" />
    <AppLayout title="Recibir moto — nueva orden">
        <form @submit.prevent="enviar" class="card shadow-sm border-0" style="max-width: 760px;">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Cliente</label>
                    <select v-model="form.cliente_id" class="form-select" :class="{ 'is-invalid': form.errors.cliente_id }" @change="form.moto_id = ''">
                        <option value="" disabled>Seleccione...</option>
                        <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                    </select>
                    <div class="invalid-feedback">{{ form.errors.cliente_id }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Moto registrada</label>
                    <select v-model="form.moto_id" class="form-select" :disabled="!form.cliente_id">
                        <option value="">— Registrar una moto nueva —</option>
                        <option v-for="m in motosCliente" :key="m.id" :value="m.id">{{ m.marca }} {{ m.modelo }} ({{ m.placa }})</option>
                    </select>
                </div>

                <template v-if="usarNueva">
                    <div class="col-12"><hr class="my-1" /><span class="small text-muted">Datos de la moto nueva</span></div>
                    <div class="col-md-3"><label class="form-label">Placa</label><input v-model="form.nueva_moto.placa" class="form-control" /></div>
                    <div class="col-md-3"><label class="form-label">Marca</label><input v-model="form.nueva_moto.marca" class="form-control" /></div>
                    <div class="col-md-3"><label class="form-label">Modelo</label><input v-model="form.nueva_moto.modelo" class="form-control" /></div>
                    <div class="col-md-3"><label class="form-label">Año</label><input v-model.number="form.nueva_moto.anio" type="number" class="form-control" /></div>
                </template>

                <div class="col-12">
                    <label class="form-label">Descripción del problema</label>
                    <textarea v-model="form.descripcion_problema" rows="3" class="form-control" :class="{ 'is-invalid': form.errors.descripcion_problema }"></textarea>
                    <div class="invalid-feedback">{{ form.errors.descripcion_problema }}</div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" :disabled="form.processing">Registrar orden</button>
                    <Link :href="route('taller.index')" class="btn btn-outline-secondary">Cancelar</Link>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
