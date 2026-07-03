<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    codigo: '', nombre: '', marca: '', modelo: '', descripcion: '',
    precio_venta_base: '', precio_mayorista: '', cantidad_minima_mayorista: 1,
    stock_minimo: 5, foto: null, imagenes: [], activo: true,
});

const preview = ref(null);
const onFile = (e) => {
    const file = e.target.files[0];
    form.foto = file ?? null;
    preview.value = file ? URL.createObjectURL(file) : null;
};

const previewsGaleria = ref([]);
const onImagenes = (e) => {
    form.imagenes = Array.from(e.target.files);
    previewsGaleria.value = form.imagenes.map((f) => URL.createObjectURL(f));
};

const enviar = () => form.post(route('productos.store'));
</script>

<template>
    <Head title="Nuevo producto" />

    <AppLayout title="Nuevo producto">
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
                        <small class="text-muted">Desde esta cantidad se aplica el precio mayorista.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stock mínimo (alerta)</label>
                        <input v-model="form.stock_minimo" type="number" min="0" class="form-control" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Foto principal (portada)</label>
                        <input type="file" accept="image/*" class="form-control" :class="{ 'is-invalid': form.errors.foto }" @change="onFile" />
                        <div class="invalid-feedback">{{ form.errors.foto }}</div>
                        <img v-if="preview" :src="preview" class="rounded mt-2" style="height:120px;object-fit:cover;" />
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Imágenes adicionales <small class="text-muted">(hasta 6, para el carrusel)</small></label>
                        <input type="file" accept="image/*" multiple class="form-control" :class="{ 'is-invalid': form.errors.imagenes || form.errors['imagenes.0'] }" @change="onImagenes" />
                        <div class="invalid-feedback">{{ form.errors.imagenes || form.errors['imagenes.0'] }}</div>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <img v-for="(src, i) in previewsGaleria" :key="i" :src="src" class="rounded border" style="height:100px;width:100px;object-fit:cover;" />
                        </div>
                    </div>
                    <div class="col-12 form-check ms-2">
                        <input v-model="form.activo" type="checkbox" class="form-check-input" id="activo" />
                        <label class="form-check-label" for="activo">Producto activo</label>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" :disabled="form.processing">Guardar</button>
                        <Link :href="route('productos.index')" class="btn btn-outline-secondary">Cancelar</Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
