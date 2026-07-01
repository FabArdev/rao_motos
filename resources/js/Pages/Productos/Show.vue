<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ producto: Object });
const bs = (n) => 'Bs. ' + Number(n).toFixed(2);
const foto = props.producto.foto_completa ?? null;
</script>

<template>
    <Head title="Detalle de producto" />

    <AppLayout title="Detalle de producto">
        <div class="card shadow-sm border-0" style="max-width: 720px;">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4 text-center">
                        <img v-if="foto" :src="foto" class="img-fluid rounded" style="max-height:180px;object-fit:cover;" />
                        <div v-else class="d-flex align-items-center justify-content-center bg-light rounded text-muted" style="height:180px;">
                            <i class="bi bi-image fs-1"></i>
                        </div>
                    </div>
                    <div class="col-md-8">
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
