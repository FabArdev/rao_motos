<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ q: String, resultados: Object, esCliente: Boolean });
const termino = ref(props.q ?? '');

const buscar = () => router.get(route('buscar'), { q: termino.value }, { preserveState: true });

const totalStaff = () => props.resultados.productos.length + props.resultados.clientes.length + props.resultados.pedidos.length + props.resultados.ordenes.length;
</script>

<template>
    <Head title="Búsqueda" />
    <AppLayout title="Búsqueda global">
        <form class="d-flex gap-2 mb-4" @submit.prevent="buscar" style="max-width: 520px;">
            <input v-model="termino" class="form-control form-control-lg" placeholder="Buscar productos, clientes, pedidos, órdenes..." autofocus />
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
        </form>

        <p v-if="q && !totalStaff()" class="text-muted">Sin resultados para "<strong>{{ q }}</strong>".</p>

        <div v-if="resultados.productos.length" class="mb-4">
            <h6 class="text-muted"><i class="bi bi-box-seam me-1"></i>Productos</h6>
            <div class="list-group">
                <Link v-for="p in resultados.productos" :key="p.id" :href="esCliente ? route('catalogo.index', { q: p.codigo }) : route('productos.show', p.id)" class="list-group-item list-group-item-action">
                    <strong>{{ p.codigo }}</strong> — {{ p.nombre }} <span class="text-muted">{{ p.marca }}</span>
                </Link>
            </div>
        </div>

        <template v-if="!esCliente">
            <div v-if="resultados.clientes.length" class="mb-4">
                <h6 class="text-muted"><i class="bi bi-people me-1"></i>Clientes</h6>
                <div class="list-group">
                    <div v-for="c in resultados.clientes" :key="c.id" class="list-group-item">{{ c.nombre }} <span class="text-muted small">CI {{ c.ci }}</span></div>
                </div>
            </div>

            <div v-if="resultados.pedidos.length" class="mb-4">
                <h6 class="text-muted"><i class="bi bi-bag me-1"></i>Pedidos</h6>
                <div class="list-group">
                    <Link v-for="p in resultados.pedidos" :key="p.id" :href="route('pedidos.show', p.id)" class="list-group-item list-group-item-action d-flex justify-content-between">
                        <span>Pedido #{{ p.id }} · {{ p.cliente }}</span><span class="badge bg-secondary">{{ p.estado }}</span>
                    </Link>
                </div>
            </div>

            <div v-if="resultados.ordenes.length" class="mb-4">
                <h6 class="text-muted"><i class="bi bi-tools me-1"></i>Órdenes de taller</h6>
                <div class="list-group">
                    <Link v-for="o in resultados.ordenes" :key="o.id" :href="route('taller.show', o.id)" class="list-group-item list-group-item-action d-flex justify-content-between">
                        <span>Orden #{{ o.id }} · {{ o.cliente }} · {{ o.placa }}</span><span class="badge bg-secondary">{{ o.estado }}</span>
                    </Link>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
