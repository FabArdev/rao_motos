<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ proveedores: Array, productos: Array });

const form = useForm({
    proveedor_id: '',
    detalles: [{ producto_id: '', cantidad: 1, precio_unitario: '' }],
});

const agregar = () => form.detalles.push({ producto_id: '', cantidad: 1, precio_unitario: '' });
const quitar = (i) => form.detalles.splice(i, 1);

const total = computed(() =>
    form.detalles.reduce((s, d) => s + (Number(d.cantidad) || 0) * (Number(d.precio_unitario) || 0), 0)
);

const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
const enviar = () => form.post(route('compras.store'));
</script>

<template>
    <Head title="Nueva compra" />
    <AppLayout title="Nueva compra">
        <form @submit.prevent="enviar">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Proveedor</label>
                        <select v-model="form.proveedor_id" class="form-select" :class="{ 'is-invalid': form.errors.proveedor_id }">
                            <option value="" disabled>Seleccione...</option>
                            <option v-for="p in proveedores" :key="p.id" :value="p.id">{{ p.razon_social }}</option>
                        </select>
                        <div class="invalid-feedback">{{ form.errors.proveedor_id }}</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Detalle</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="agregar"><i class="bi bi-plus-lg"></i> Añadir línea</button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th style="width:45%">Producto</th><th>Cantidad</th><th>Precio unit.</th><th class="text-end">Subtotal</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="(d, i) in form.detalles" :key="i">
                                <td>
                                    <select v-model="d.producto_id" class="form-select form-select-sm">
                                        <option value="" disabled>Seleccione...</option>
                                        <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.codigo }} — {{ p.nombre }}</option>
                                    </select>
                                </td>
                                <td><input v-model.number="d.cantidad" type="number" min="1" class="form-control form-control-sm" style="width:90px" /></td>
                                <td><input v-model.number="d.precio_unitario" type="number" step="0.01" min="0" class="form-control form-control-sm" style="width:120px" /></td>
                                <td class="text-end">{{ fmt((d.cantidad || 0) * (d.precio_unitario || 0)) }}</td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-light text-danger" @click="quitar(i)" :disabled="form.detalles.length === 1"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-light"><th colspan="3" class="text-end">Total</th><th class="text-end">{{ fmt(total) }}</th><th></th></tr>
                        </tfoot>
                    </table>
                </div>
                <div v-if="form.errors.detalles" class="text-danger small px-3 pb-2">{{ form.errors.detalles }}</div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary" :disabled="form.processing">Registrar compra</button>
                <Link :href="route('compras.index')" class="btn btn-outline-secondary">Cancelar</Link>
            </div>
        </form>
    </AppLayout>
</template>
