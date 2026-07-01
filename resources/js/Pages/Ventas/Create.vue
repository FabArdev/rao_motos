<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ clientes: Array, productos: Array });

const form = useForm({
    cliente_id: '',
    tipo_venta: 'CONTADO',
    metodo_pago: 'EFECTIVO',
    items: [{ producto_id: '', cantidad: 1 }],
    numero_cuotas: 2,
    tasa_interes: null,
});

const prodById = (id) => props.productos.find((p) => p.id == id);

// Precio por línea: mayorista si la cantidad alcanza el umbral del producto (RN3).
const precioLinea = (item) => {
    const p = prodById(item.producto_id);
    if (!p) return 0;
    return Number(item.cantidad) >= p.cantidad_minima_mayorista ? Number(p.precio_mayorista) : Number(p.precio_venta_base);
};
const esMayorista = (item) => {
    const p = prodById(item.producto_id);
    return p && Number(item.cantidad) >= p.cantidad_minima_mayorista;
};
const stockDe = (item) => prodById(item.producto_id)?.inventario?.stock_actual ?? 0;

const total = computed(() => form.items.reduce((s, it) => s + precioLinea(it) * (Number(it.cantidad) || 0), 0));

const agregar = () => form.items.push({ producto_id: '', cantidad: 1 });
const quitar = (i) => form.items.splice(i, 1);

const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
const enviar = () => form.post(route('ventas.store'));
</script>

<template>
    <Head title="Nueva venta" />
    <AppLayout title="Nueva venta">
        <form @submit.prevent="enviar">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Cliente</label>
                        <select v-model="form.cliente_id" class="form-select" :class="{ 'is-invalid': form.errors.cliente_id }">
                            <option value="" disabled>Seleccione...</option>
                            <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre }} ({{ c.nit_ci }})</option>
                        </select>
                        <div class="invalid-feedback">{{ form.errors.cliente_id }}</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipo</label>
                        <select v-model="form.tipo_venta" class="form-select">
                            <option value="CONTADO">Contado</option>
                            <option value="CREDITO">Crédito (cuotas)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Método de pago</label>
                        <select v-model="form.metodo_pago" class="form-select">
                            <option value="EFECTIVO">Efectivo</option>
                            <option value="QR">QR (PagoFácil)</option>
                        </select>
                    </div>

                    <template v-if="form.tipo_venta === 'CREDITO'">
                        <div class="col-md-3">
                            <label class="form-label">N° de cuotas</label>
                            <input v-model.number="form.numero_cuotas" type="number" min="2" class="form-control" :class="{ 'is-invalid': form.errors.numero_cuotas }" />
                            <div class="invalid-feedback">{{ form.errors.numero_cuotas }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tasa interés % <small class="text-muted">(opcional)</small></label>
                            <input v-model.number="form.tasa_interes" type="number" step="0.01" min="0" class="form-control" placeholder="default configurado" />
                        </div>
                    </template>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Productos</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" @click="agregar"><i class="bi bi-plus-lg"></i> Añadir</button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light"><tr><th style="width:40%">Producto</th><th>Cantidad</th><th>Precio</th><th class="text-end">Subtotal</th><th></th></tr></thead>
                        <tbody>
                            <tr v-for="(it, i) in form.items" :key="i">
                                <td>
                                    <select v-model="it.producto_id" class="form-select form-select-sm">
                                        <option value="" disabled>Seleccione...</option>
                                        <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.codigo }} — {{ p.nombre }}</option>
                                    </select>
                                    <small v-if="it.producto_id" class="text-muted">Stock: {{ stockDe(it) }}</small>
                                </td>
                                <td><input v-model.number="it.cantidad" type="number" min="1" class="form-control form-control-sm" style="width:90px" /></td>
                                <td>
                                    {{ fmt(precioLinea(it)) }}
                                    <span v-if="esMayorista(it)" class="badge bg-info text-dark ms-1">mayorista</span>
                                </td>
                                <td class="text-end">{{ fmt(precioLinea(it) * (it.cantidad || 0)) }}</td>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-light text-danger" @click="quitar(i)" :disabled="form.items.length === 1"><i class="bi bi-trash"></i></button></td>
                            </tr>
                        </tbody>
                        <tfoot><tr class="table-light"><th colspan="3" class="text-end">Total</th><th class="text-end">{{ fmt(total) }}</th><th></th></tr></tfoot>
                    </table>
                </div>
                <div v-if="form.errors.items" class="text-danger small px-3 pb-2">{{ form.errors.items }}</div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary" :disabled="form.processing">Registrar venta</button>
                <Link :href="route('ventas.index')" class="btn btn-outline-secondary">Cancelar</Link>
            </div>
        </form>
    </AppLayout>
</template>
