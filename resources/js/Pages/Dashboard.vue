<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
    stats: { type: Object, default: null },
    ventasMes: { type: Array, default: () => [] },
    topProductos: { type: Array, default: () => [] },
    ordenesEstado: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const accesos = computed(() =>
    (page.props.menuItems ?? []).filter((i) => route().has(i.ruta_laravel) && i.ruta_laravel !== 'dashboard')
);

const fmt = (n) => `Bs. ${Number(n ?? 0).toFixed(2)}`;

// --- Ventas por mes (barras apiladas contado vs crédito) ---
const meses = computed(() => [...new Set(props.ventasMes.map((v) => v.mes))].sort());
const ventasChartData = computed(() => ({
    labels: meses.value,
    datasets: [
        { label: 'Contado', backgroundColor: '#0d6efd', data: meses.value.map((m) => Number(props.ventasMes.find((v) => v.mes === m && v.tipo_venta === 'CONTADO')?.total ?? 0)) },
        { label: 'Crédito', backgroundColor: '#ffc107', data: meses.value.map((m) => Number(props.ventasMes.find((v) => v.mes === m && v.tipo_venta === 'CREDITO')?.total ?? 0)) },
    ],
}));

const topChartData = computed(() => ({
    labels: props.topProductos.map((p) => p.nombre),
    datasets: [{ label: 'Unidades', backgroundColor: '#198754', data: props.topProductos.map((p) => p.total) }],
}));

const ordenesChartData = computed(() => ({
    labels: props.ordenesEstado.map((o) => o.estado),
    datasets: [{ backgroundColor: ['#6c757d', '#ffc107', '#0dcaf0', '#0d6efd', '#198754', '#dc3545'], data: props.ordenesEstado.map((o) => o.total) }],
}));

const chartOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };
const barStacked = { ...chartOpts, scales: { x: { stacked: true }, y: { stacked: true } } };
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout title="Dashboard">
        <h1 class="h4 mb-1">Hola, {{ user?.name }} 👋</h1>
        <p class="text-muted">
            Bienvenido a <strong>RAO MOTOS</strong> — tu rol es
            <span class="badge bg-primary text-uppercase">{{ user?.rol }}</span>
        </p>

        <!-- Tarjetas de estadísticas -->
        <div v-if="stats" class="row g-3 mb-2">
            <div class="col-6 col-lg-2"><div class="card border-0 shadow-sm text-bg-primary"><div class="card-body p-2"><div class="small">Ventas (total)</div><div class="fw-bold">{{ fmt(stats.ventas_total) }}</div></div></div></div>
            <div class="col-6 col-lg-2"><div class="card border-0 shadow-sm"><div class="card-body p-2"><div class="small text-muted">N° ventas</div><div class="fw-bold fs-5">{{ stats.ventas_count }}</div></div></div></div>
            <div class="col-6 col-lg-2"><div class="card border-0 shadow-sm text-bg-info"><div class="card-body p-2"><div class="small">Créditos vigentes</div><div class="fw-bold fs-5">{{ stats.creditos_vigentes }}</div></div></div></div>
            <div class="col-6 col-lg-2"><div class="card border-0 shadow-sm text-bg-danger"><div class="card-body p-2"><div class="small">Créditos morosos</div><div class="fw-bold fs-5">{{ stats.creditos_morosos }}</div></div></div></div>
            <div class="col-6 col-lg-2"><div class="card border-0 shadow-sm text-bg-warning"><div class="card-body p-2"><div class="small">Órdenes abiertas</div><div class="fw-bold fs-5">{{ stats.ordenes_abiertas }}</div></div></div></div>
            <div class="col-6 col-lg-2"><div class="card border-0 shadow-sm text-bg-dark"><div class="card-body p-2"><div class="small">Stock crítico</div><div class="fw-bold fs-5">{{ stats.inventario_critico }}</div></div></div></div>
        </div>

        <!-- Gráficas -->
        <div v-if="stats" class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100"><div class="card-header bg-white fw-semibold">Ventas por mes (contado vs crédito)</div>
                    <div class="card-body" style="height: 260px;"><Bar :data="ventasChartData" :options="barStacked" /></div></div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100"><div class="card-header bg-white fw-semibold">Top productos vendidos</div>
                    <div class="card-body" style="height: 260px;"><Bar :data="topChartData" :options="chartOpts" /></div></div>
            </div>
            <div v-if="ordenesEstado.length" class="col-lg-4">
                <div class="card border-0 shadow-sm h-100"><div class="card-header bg-white fw-semibold">Órdenes de taller por estado</div>
                    <div class="card-body" style="height: 260px;"><Doughnut :data="ordenesChartData" :options="chartOpts" /></div></div>
            </div>
        </div>

        <!-- Accesos rápidos -->
        <h6 class="text-muted mb-2">Accesos rápidos</h6>
        <div class="row g-3">
            <div v-for="item in accesos" :key="item.ruta_laravel" class="col-sm-6 col-lg-3">
                <Link :href="route(item.ruta_laravel)" class="text-decoration-none">
                    <div class="card shadow-sm h-100 border-0">
                        <div class="card-body d-flex align-items-center gap-3 py-3">
                            <i class="bi fs-4 text-primary" :class="`bi-${item.icono}`"></i>
                            <span class="fw-semibold text-dark">{{ item.etiqueta }}</span>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
