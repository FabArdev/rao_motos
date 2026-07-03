<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ productos: Object, filtros: Object });
const page = usePage();
const q = ref(props.filtros?.q ?? '');

const buscar = () => router.get(route('productos.index'), { q: q.value }, { preserveState: true, replace: true });

const desactivar = (p) => {
    if (confirm(`¿Desactivar el producto "${p.nombre}"?`)) {
        router.delete(route('productos.destroy', p.id));
    }
};

const bs = (n) => 'Bs. ' + Number(n).toFixed(2);
const foto = (p) => p.foto_completa ?? null;
</script>

<template>
    <Head title="Productos" />

    <AppLayout title="Gestión de productos">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <form class="d-flex gap-2" @submit.prevent="buscar" style="max-width: 380px;">
                <input v-model="q" class="form-control" placeholder="Buscar por código, nombre, marca..." />
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <Link :href="route('productos.create')" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nuevo producto
            </Link>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th></th><th>Código</th><th>Producto</th><th class="text-end">Minorista</th>
                            <th class="text-end">Mayorista (≥)</th><th class="text-center">Stock</th>
                            <th class="text-center">Estado</th><th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in productos.data" :key="p.id" :class="{ 'opacity-50': !p.activo }">
                            <td>
                                <img v-if="foto(p)" :src="foto(p)" loading="lazy" class="rounded bg-light" style="width:42px;height:42px;object-fit:contain;" />
                                <span v-else class="d-inline-flex align-items-center justify-content-center bg-light rounded text-muted" style="width:42px;height:42px;"><i class="bi bi-image"></i></span>
                            </td>
                            <td><code>{{ p.codigo }}</code></td>
                            <td>
                                <div class="fw-semibold">{{ p.nombre }}</div>
                                <small class="text-muted">{{ [p.marca, p.modelo].filter(Boolean).join(' · ') }}</small>
                            </td>
                            <td class="text-end">{{ bs(p.precio_venta_base) }}</td>
                            <td class="text-end">{{ bs(p.precio_mayorista) }} <small class="text-muted">(≥{{ p.cantidad_minima_mayorista }})</small></td>
                            <td class="text-center">
                                <span class="badge" :class="(p.inventario?.stock_actual ?? 0) <= (p.inventario?.stock_minimo ?? 0) ? 'bg-danger' : 'bg-success'">
                                    {{ p.inventario?.stock_actual ?? 0 }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge" :class="p.activo ? 'bg-success' : 'bg-secondary'">{{ p.activo ? 'Activo' : 'Inactivo' }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <Link :href="route('productos.show', p.id)" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></Link>
                                <Link :href="route('productos.edit', p.id)" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></Link>
                                <button v-if="p.activo" class="btn btn-sm btn-light text-danger" @click="desactivar(p)"><i class="bi bi-slash-circle"></i></button>
                            </td>
                        </tr>
                        <tr v-if="!productos.data.length">
                            <td colspan="8" class="text-center text-muted py-4">No se encontraron productos.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav v-if="productos.links.length > 3" class="mt-3">
            <ul class="pagination pagination-sm">
                <li v-for="(link, i) in productos.links" :key="i" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                    <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" preserve-scroll />
                    <span v-else class="page-link" v-html="link.label" />
                </li>
            </ul>
        </nav>
    </AppLayout>
</template>
