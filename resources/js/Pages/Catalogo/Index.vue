<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ productos: Object, filtros: Object });
const page = usePage();
const q = ref(props.filtros?.q ?? '');

const carrito = ref([]); // { producto, cantidad }

const buscar = () => router.get(route('catalogo.index'), { q: q.value }, { preserveState: true, replace: true });

const agregar = (p) => {
    const existe = carrito.value.find((i) => i.producto.id === p.id);
    if (existe) existe.cantidad++;
    else carrito.value.push({ producto: p, cantidad: 1 });
};
const quitar = (i) => carrito.value.splice(i, 1);

const totalItems = computed(() => carrito.value.reduce((s, i) => s + i.cantidad, 0));

const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;
const galeria = (p) => [p.foto_completa, ...(p.imagenes ?? []).map((i) => i.url)].filter(Boolean);

const form = useForm({ items: [] });
const enviar = () => {
    if (!carrito.value.length) return;
    form.items = carrito.value.map((i) => ({ producto_id: i.producto.id, cantidad: i.cantidad }));
    form.post(route('catalogo.pedido'));
};
</script>

<template>
    <Head title="Catálogo" />
    <AppLayout title="Catálogo de repuestos">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>

        <div class="row g-3">
            <div class="col-lg-8">
                <form class="d-flex gap-2 mb-3" @submit.prevent="buscar" style="max-width: 420px;">
                    <input v-model="q" type="text" class="form-control" placeholder="Buscar repuesto..." />
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </form>

                <div class="row g-3">
                    <div v-for="p in productos.data" :key="p.id" class="col-md-6 col-xl-4">
                        <div class="card h-100 shadow-sm border-0 producto-card">
                            <div class="prod-media bg-light">
                                <div v-if="galeria(p).length > 1" :id="`cat-carousel-${p.id}`" class="carousel slide h-100">
                                    <div class="carousel-inner h-100">
                                        <div v-for="(src, i) in galeria(p)" :key="i" class="carousel-item h-100" :class="{ active: i === 0 }">
                                            <img :src="src" class="d-block w-100 h-100" style="object-fit:cover;" alt="" />
                                        </div>
                                    </div>
                                    <button class="carousel-control-prev" type="button" :data-bs-target="`#cat-carousel-${p.id}`" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                                    <button class="carousel-control-next" type="button" :data-bs-target="`#cat-carousel-${p.id}`" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                                </div>
                                <img v-else-if="galeria(p).length === 1" :src="galeria(p)[0]" class="w-100 h-100" style="object-fit:cover;" alt="" />
                                <div v-else class="d-flex align-items-center justify-content-center h-100 text-muted"><i class="bi bi-box-seam fs-1"></i></div>
                            </div>
                            <div class="card-body">
                                <div class="small text-muted">{{ p.codigo }} · {{ p.marca || '—' }}</div>
                                <h6 class="fw-semibold">{{ p.nombre }}</h6>
                                <div class="fw-bold text-primary">{{ fmt(p.precio_venta_base) }}</div>
                                <div class="small text-muted">Mayorista {{ fmt(p.precio_mayorista) }} desde {{ p.cantidad_minima_mayorista }} u.</div>
                            </div>
                            <div class="card-footer bg-white border-0">
                                <button class="btn btn-sm btn-primary w-100" @click="agregar(p)"
                                    :disabled="(p.inventario?.stock_actual ?? 0) < 1">
                                    <i class="bi bi-cart-plus me-1"></i>
                                    {{ (p.inventario?.stock_actual ?? 0) < 1 ? 'Sin stock' : 'Agregar' }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-if="!productos.data.length" class="text-center text-muted py-4">No se encontraron productos.</div>
                </div>

                <nav v-if="productos.links.length > 3" class="mt-3">
                    <ul class="pagination pagination-sm">
                        <li v-for="(link, i) in productos.links" :key="i" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                            <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" preserve-scroll />
                            <span v-else class="page-link" v-html="link.label" />
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 sticky-top" style="top: 1rem;">
                    <div class="card-header bg-white fw-semibold"><i class="bi bi-cart me-1"></i>Mi pedido ({{ totalItems }})</div>
                    <ul class="list-group list-group-flush">
                        <li v-for="(i, idx) in carrito" :key="i.producto.id" class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small fw-semibold">{{ i.producto.nombre }}</div>
                                <input v-model.number="i.cantidad" type="number" min="1" class="form-control form-control-sm mt-1" style="width:80px" />
                            </div>
                            <button class="btn btn-sm btn-light text-danger" @click="quitar(idx)"><i class="bi bi-trash"></i></button>
                        </li>
                        <li v-if="!carrito.length" class="list-group-item text-muted text-center small py-3">Tu pedido está vacío.</li>
                    </ul>
                    <div class="card-body">
                        <button class="btn btn-success w-100" :disabled="!carrito.length || form.processing" @click="enviar">
                            Enviar pedido
                        </button>
                        <div v-if="form.errors.items" class="text-danger small mt-1">{{ form.errors.items }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.prod-media {
    height: 180px;
    overflow: hidden;
}
.prod-media img {
    transition: transform .3s ease;
}
/* Efecto hover sutil (CSS transform) en las tarjetas del catálogo */
.producto-card {
    transition: transform .18s ease, box-shadow .18s ease;
}
.producto-card:hover {
    transform: translateY(-5px) scale(1.01);
    box-shadow: 0 .6rem 1.2rem rgba(0, 0, 0, .15) !important;
}
.producto-card:hover .prod-media img {
    transform: scale(1.06);
}
</style>
