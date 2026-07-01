<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const desde = ref('');
const hasta = ref('');

const urlVentas = () => {
    const p = new URLSearchParams();
    if (desde.value) p.set('desde', desde.value);
    if (hasta.value) p.set('hasta', hasta.value);
    return route('reportes.ventas') + (p.toString() ? `?${p}` : '');
};
</script>

<template>
    <Head title="Reportes" />
    <AppLayout title="Reportes">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h6 class="fw-bold"><i class="bi bi-receipt me-1"></i>Ventas por fecha</h6>
                        <p class="text-muted small">Contado vs crédito en un rango de fechas.</p>
                        <div class="row g-2 mb-2">
                            <div class="col"><label class="form-label small">Desde</label><input v-model="desde" type="date" class="form-control form-control-sm" /></div>
                            <div class="col"><label class="form-label small">Hasta</label><input v-model="hasta" type="date" class="form-control form-control-sm" /></div>
                        </div>
                        <a :href="urlVentas()" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h6 class="fw-bold"><i class="bi bi-credit-card me-1"></i>Créditos por estado</h6>
                        <p class="text-muted small">Vigentes, morosos y pagados con saldo pendiente.</p>
                        <a :href="route('reportes.creditos')" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h6 class="fw-bold"><i class="bi bi-clipboard-data me-1"></i>Inventario crítico</h6>
                        <p class="text-muted small">Productos por debajo del stock mínimo.</p>
                        <a :href="route('reportes.inventario')" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h6 class="fw-bold"><i class="bi bi-trophy me-1"></i>Top productos vendidos</h6>
                        <p class="text-muted small">Ranking de repuestos por unidades y monto.</p>
                        <a :href="route('reportes.top-productos')" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF</a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
