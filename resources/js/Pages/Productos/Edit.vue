<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ producto: Object });

const form = useForm({
    codigo: props.producto.codigo,
    nombre: props.producto.nombre,
    marca: props.producto.marca ?? '',
    modelo: props.producto.modelo ?? '',
    descripcion: props.producto.descripcion ?? '',
    precio_venta_base: props.producto.precio_venta_base,
    precio_mayorista: props.producto.precio_mayorista,
    cantidad_minima_mayorista: props.producto.cantidad_minima_mayorista,
    stock_minimo: props.producto.inventario?.stock_minimo ?? 5,
    foto: null,
    imagenes: [],
    eliminar_imagenes: [],
    activo: !!props.producto.activo,
});

const preview = ref(props.producto.foto_completa ?? null);
const onFile = (e) => {
    const file = e.target.files[0];
    form.foto = file ?? null;
    if (file) preview.value = URL.createObjectURL(file);
};

const previewsGaleria = ref([]);
const onImagenes = (e) => {
    form.imagenes = Array.from(e.target.files);
    previewsGaleria.value = form.imagenes.map((f) => URL.createObjectURL(f));
};
const marcada = (id) => form.eliminar_imagenes.includes(id);
const toggleEliminar = (id) => {
    if (marcada(id)) form.eliminar_imagenes = form.eliminar_imagenes.filter((x) => x !== id);
    else form.eliminar_imagenes.push(id);
};

// Subida de archivo en PUT → spoof de método (PHP no parsea multipart en PUT).
const enviar = () => {
    form.transform((data) => ({ ...data, _method: 'put' }))
        .post(route('productos.update', props.producto.id));
};
</script>

<template>
    <Head title="Editar producto" />

    <AppLayout title="Editar producto">
        <div class="card shadow-sm border-0" style="max-width: 860px;">
            <div class="card-body">
                <form @submit.prevent="enviar" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Código</label>
                        <input v-model="form.codigo" class="form-control" :class="{ 'is-invalid': form.errors.codigo }" />
                        <div class="invalid-feedback">{{ form.errors.codigo }}</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Nombre</label>
                        <input v-model="form.nombre" class="form-control" :class="{ 'is-invalid': form.errors.nombre }" />
                        <div class="invalid-feedback">{{ form.errors.nombre }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Marca</label>
                        <input v-model="form.marca" class="form-control" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Modelo</label>
                        <input v-model="form.modelo" class="form-control" />
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <textarea v-model="form.descripcion" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Precio minorista (Bs.)</label>
                        <input v-model="form.precio_venta_base" type="number" step="0.01" min="0" class="form-control" :class="{ 'is-invalid': form.errors.precio_venta_base }" />
                        <div class="invalid-feedback">{{ form.errors.precio_venta_base }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Precio mayorista (Bs.)</label>
                        <input v-model="form.precio_mayorista" type="number" step="0.01" min="0" class="form-control" :class="{ 'is-invalid': form.errors.precio_mayorista }" />
                        <div class="invalid-feedback">{{ form.errors.precio_mayorista }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cantidad mín. mayoreo</label>
                        <input v-model="form.cantidad_minima_mayorista" type="number" min="1" class="form-control" :class="{ 'is-invalid': form.errors.cantidad_minima_mayorista }" />
                        <div class="invalid-feedback">{{ form.errors.cantidad_minima_mayorista }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stock mínimo (alerta)</label>
                        <input v-model="form.stock_minimo" type="number" min="0" class="form-control" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reemplazar portada</label>
                        <input type="file" accept="image/*" class="form-control" :class="{ 'is-invalid': form.errors.foto }" @change="onFile" />
                        <div class="invalid-feedback">{{ form.errors.foto }}</div>
                        <img v-if="preview" :src="preview" class="rounded mt-2" style="height:120px;object-fit:cover;" />
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Imágenes adicionales <small class="text-muted">(hasta 6, para el carrusel)</small></label>
                        <input type="file" accept="image/*" multiple class="form-control" :class="{ 'is-invalid': form.errors.imagenes || form.errors['imagenes.0'] }" @change="onImagenes" />
                        <div class="invalid-feedback">{{ form.errors.imagenes || form.errors['imagenes.0'] }}</div>

                        <div v-if="producto.imagenes?.length" class="mt-2">
                            <div class="small text-muted mb-1">Galería actual (marca las que quieras eliminar):</div>
                            <div class="d-flex flex-wrap gap-2">
                                <div v-for="img in producto.imagenes" :key="img.id" class="position-relative" style="cursor:pointer" @click="toggleEliminar(img.id)">
                                    <img :src="img.url" class="rounded border" :class="{ 'opacity-25': marcada(img.id) }" style="height:100px;width:100px;object-fit:cover;" />
                                    <span v-if="marcada(img.id)" class="position-absolute top-50 start-50 translate-middle badge bg-danger"><i class="bi bi-trash"></i></span>
                                </div>
                            </div>
                        </div>

                        <div v-if="previewsGaleria.length" class="mt-2">
                            <div class="small text-muted mb-1">Nuevas a subir:</div>
                            <div class="d-flex flex-wrap gap-2">
                                <img v-for="(src, i) in previewsGaleria" :key="i" :src="src" class="rounded border" style="height:100px;width:100px;object-fit:cover;" />
                            </div>
                        </div>
                    </div>
                    <div class="col-12 form-check ms-2">
                        <input v-model="form.activo" type="checkbox" class="form-check-input" id="activo" />
                        <label class="form-check-label" for="activo">Producto activo</label>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" :disabled="form.processing">Actualizar</button>
                        <Link :href="route('productos.index')" class="btn btn-outline-secondary">Cancelar</Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
