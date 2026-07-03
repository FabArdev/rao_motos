<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ producto: Object });
const bs = (n) => 'Bs. ' + Number(n).toFixed(2);
const galeria = [props.producto.foto_completa, ...(props.producto.imagenes ?? []).map((i) => i.url)].filter(Boolean);
</script>

<template>
    <Head title="Detalle de producto" />

    <AppLayout title="Detalle de producto">
        <div class="card shadow-sm border-0" style="max-width: 720px;">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-5 text-center">
                        <div v-if="galeria.length > 1" :id="`carousel-${producto.id}`" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner rounded">
                                <div v-for="(src, i) in galeria" :key="i" class="carousel-item" :class="{ active: i === 0 }">
                                    <img :src="src" class="d-block w-100 bg-light" style="height:220px;object-fit:contain;" />
                                </div>
                            </div>
                            <button class="carousel-control-prev" type="button" :data-bs-target="`#carousel-${producto.id}`" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                            </button>
                            <button class="carousel-control-next" type="button" :data-bs-target="`#carousel-${producto.id}`" data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                            </button>
                        </div>
                        <img v-else-if="galeria.length === 1" :src="galeria[0]" class="img-fluid rounded bg-light" style="max-height:220px;object-fit:contain;" />
                        <div v-else class="d-flex align-items-center justify-content-center bg-light rounded text-muted" style="height:220px;">
                            <i class="bi bi-image fs-1"></i>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h2 class="h5 mb-0">{{ producto.nombre }}</h2>
                            <span class="badge" :class="producto.activo ? 'bg-success' : 'bg-secondary'">{{ producto.activo ? 'Activo' : 'Inactivo' }}</span>
                        </div>
                        <p class="text-muted mb-3"><code>{{ producto.codigo }}</code> · {{ [producto.marca, producto.modelo].filter(Boolean).join(' · ') }}</p>

                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-muted">Precio minorista</dt><dd class="col-sm-7">{{ bs(producto.precio_venta_base) }}</dd>
                            <dt class="col-sm-5 text-muted">Precio mayorista</dt><dd class="col-sm-7">{{ bs(producto.precio_mayorista) }} <small class="text-muted">(desde {{ producto.cantidad_minima_mayorista }} u.)</small></dd>
                            <dt class="col-sm-5 text-muted">Stock actual</dt><dd class="col-sm-7">{{ producto.inventario?.stock_actual ?? 0 }}</dd>
                            <dt class="col-sm-5 text-muted">Stock mínimo</dt><dd class="col-sm-7">{{ producto.inventario?.stock_minimo ?? 0 }}</dd>
                        </dl>
                        <p v-if="producto.descripcion" class="mt-3 mb-0">{{ producto.descripcion }}</p>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <Link :href="route('productos.edit', producto.id)" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Editar</Link>
                    <Link :href="route('productos.index')" class="btn btn-outline-secondary">Volver</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
