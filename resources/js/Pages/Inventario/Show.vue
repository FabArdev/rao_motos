<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ inventario: Object });
const page = usePage();

const form = useForm({ cantidad: '', motivo: '' });
const ajustar = () => form.post(route('inventario.ajuste', props.inventario.id), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
});
</script>

<template>
    <Head title="Inventario · producto" />
    <AppLayout title="Inventario del producto">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="alert alert-info d-flex gap-2 align-items-start">
            <i class="bi bi-info-circle fs-5 mt-1"></i>
            <div class="small">
                <strong>¿Para qué sirve esta pantalla?</strong> Aquí <strong>no se editan los datos ni el precio del producto</strong> (eso se hace en <em>Productos</em>).
                Esta vista es para <strong>ajustar el stock manualmente</strong> (correcciones de conteo, mermas, ingresos que no vienen de una compra)
                y para <strong>ver el historial de movimientos (kardex)</strong> del producto.
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-5">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <h5 class="fw-bold">{{ inventario.producto?.nombre }}</h5>
                        <div class="text-muted mb-3">{{ inventario.producto?.codigo }}</div>
                        <div class="d-flex justify-content-between"><span>Stock actual</span><span class="fw-bold fs-5">{{ inventario.stock_actual }}</span></div>
                        <div class="d-flex justify-content-between"><span>Stock mínimo</span><span>{{ inventario.stock_minimo }}</span></div>
                        <div class="mt-2">
                            <span v-if="inventario.stock_actual < inventario.stock_minimo" class="badge bg-danger">Bajo el mínimo</span>
                            <span v-else class="badge bg-success">Stock suficiente</span>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold">Ajuste manual de stock</div>
                    <div class="card-body">
                        <form @submit.prevent="ajustar" class="row g-2">
                            <div class="col-12">
                                <label class="form-label small">Cantidad (positiva = ingreso, negativa = egreso)</label>
                                <input v-model.number="form.cantidad" type="number" class="form-control" :class="{ 'is-invalid': form.errors.cantidad }" />
                                <div class="invalid-feedback">{{ form.errors.cantidad }}</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Motivo</label>
                                <input v-model="form.motivo" class="form-control" :class="{ 'is-invalid': form.errors.motivo }" />
                                <div class="invalid-feedback">{{ form.errors.motivo }}</div>
                            </div>
                            <div class="col-12"><button class="btn btn-primary btn-sm" :disabled="form.processing">Aplicar ajuste</button></div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold">Movimientos recientes</div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th>Fecha</th><th>Tipo</th><th class="text-end">Cantidad</th><th>Motivo</th></tr></thead>
                            <tbody>
                                <tr v-for="m in inventario.movimientos" :key="m.id">
                                    <td class="small">{{ new Date(m.fecha).toLocaleString() }}</td>
                                    <td><span class="badge" :class="m.tipo_movimiento === 'INGRESO' ? 'bg-success' : 'bg-danger'">{{ m.tipo_movimiento }}</span></td>
                                    <td class="text-end">{{ m.cantidad }}</td>
                                    <td class="small">{{ m.motivo }}</td>
                                </tr>
                                <tr v-if="!inventario.movimientos?.length"><td colspan="4" class="text-center text-muted py-3">Sin movimientos.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <Link :href="route('inventario.index')" class="btn btn-outline-secondary btn-sm mt-3">Volver al inventario</Link>
            </div>
        </div>
    </AppLayout>
</template>
