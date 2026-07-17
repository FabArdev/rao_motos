<template>
    <div
        class="search-results position-absolute top-100 start-0 w-100 mt-2 card shadow-lg"
        style="z-index: 1050; max-height: 500px; overflow-y: auto"
        role="dialog"
        aria-label="Resultados de búsqueda"
    >
        <div class="card-body p-0">
            <!-- Header con contador de resultados -->
            <div class="px-3 py-2 border-bottom bg-light">
                <small class="text-muted">
                    <i class="bi bi-search me-1"></i>
                    Se encontraron <strong>{{ productos.length + promociones.length + menus.length }}</strong> resultado(s)
                </small>
            </div>

            <!-- Menús / Navegación -->
            <div v-if="menus.length > 0" class="border-bottom">
                <h6 class="px-3 pt-3 pb-2 mb-0 text-muted small fw-bold">
                    <i class="bi bi-compass me-2"></i>Navegación ({{ menus.length }})
                </h6>
                <div class="list-group list-group-flush">
                    <button
                        v-for="menu in menus"
                        :key="menu.id"
                        class="list-group-item list-group-item-action d-flex align-items-center py-3"
                        @click="navigateTo('menus', menu.route)"
                        :title="`Ir a ${menu.label}`"
                    >
                        <div class="flex-shrink-0 me-3">
                            <div
                                class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                style="width: 40px; height: 40px"
                            >
                                <i
                                    :class="['bi', menu.icon, 'text-primary']"
                                    style="font-size: 1.2rem"
                                ></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">{{ menu.label }}</h6>
                            <small v-if="menu.padre" class="text-muted">
                                <i class="bi bi-arrow-return-right me-1"></i>{{ menu.padre }}
                            </small>
                            <small v-else-if="menu.submenus && menu.submenus.length > 0" class="text-muted">
                                {{ menu.submenus.length }} {{ menu.submenus.length === 1 ? 'opción' : 'opciones' }}
                            </small>
                        </div>
                        <div class="flex-shrink-0">
                            <i class="bi bi-chevron-right text-muted"></i>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Productos -->
            <div v-if="productos.length > 0" class="border-bottom">
                <h6 class="px-3 pt-3 pb-2 mb-0 text-muted small fw-bold">
                    <i class="bi bi-box-seam me-2"></i>Productos ({{ productos.length }})
                </h6>
                <div class="list-group list-group-flush">
                    <button
                        v-for="producto in productos"
                        :key="producto.id"
                        class="list-group-item list-group-item-action d-flex align-items-center py-3"
                        @click="navigateTo('productos', producto.id)"
                        :title="`Ver detalles de ${producto.nombre}`"
                    >
                        <div class="flex-shrink-0 me-3">
                            <div
                                class="rounded bg-light d-flex align-items-center justify-content-center"
                                style="width: 50px; height: 50px"
                            >
                                <i
                                    v-if="!producto.imagen"
                                    class="bi bi-image text-muted"
                                    style="font-size: 1.5rem"
                                ></i>
                                <img
                                    v-else
                                    :src="producto.imagen"
                                    :alt="producto.nombre"
                                    class="rounded"
                                    style="
                                        width: 50px;
                                        height: 50px;
                                        object-fit: cover;
                                    "
                                />
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div
                                class="d-flex justify-content-between align-items-start"
                            >
                                <div class="flex-grow-1 me-2">
                                    <h6 class="mb-1">{{ producto.nombre }}</h6>
                                    <div class="small text-muted">
                                        <i class="bi bi-tag me-1"></i>{{ producto.categoria }}
                                        <span class="mx-1">•</span>
                                        <i class="bi bi-upc me-1"></i>{{ producto.codigo }}
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <strong class="text-success d-block"
                                        >Bs. {{ formatMoney(producto.precio) }}</strong
                                    >
                                    <small 
                                        class="text-muted"
                                        :class="{ 'text-danger': producto.stock <= 5 }"
                                    >
                                        <i class="bi bi-box me-1"></i>{{ producto.stock }} 
                                        {{ producto.stock === 1 ? 'unidad' : 'unidades' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Promociones -->
            <div v-if="promociones.length > 0">
                <h6 class="px-3 pt-3 pb-2 mb-0 text-muted small fw-bold">
                    <i class="bi bi-tags me-2"></i>Promociones ({{ promociones.length }})
                </h6>
                <div class="list-group list-group-flush">
                    <button
                        v-for="promocion in promociones"
                        :key="promocion.id"
                        class="list-group-item list-group-item-action py-3"
                        @click="navigateTo('promociones', promocion.id)"
                        :title="`Ver detalles de ${promocion.nombre}`"
                    >
                        <div
                            class="d-flex justify-content-between align-items-start"
                        >
                            <div class="flex-grow-1 me-2">
                                <h6 class="mb-1">
                                    <i class="bi bi-gift me-2 text-warning"></i>{{ promocion.nombre }}
                                </h6>
                                <p class="mb-2 small text-muted">
                                    {{ promocion.descripcion }}
                                </p>
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    Válido hasta: {{ formatDate(promocion.fecha_fin) }}
                                </small>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="badge bg-danger fs-6">
                                    <span v-if="promocion.descuento_porcentaje">
                                        -{{ promocion.descuento_porcentaje }}%
                                    </span>
                                    <span v-else>
                                        -Bs. {{ formatMoney(promocion.descuento_monto) }}
                                    </span>
                                </span>
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Footer con hint de navegación -->
            <div class="px-3 py-2 bg-light border-top text-center">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Haz clic en un resultado para ver más detalles
                </small>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";

const props = defineProps({
    productos: Array,
    promociones: Array,
    menus: Array,
});

const emit = defineEmits(["close"]);

const navigateTo = (type, id) => {
    emit("close");

    // Navegación a los detalles del producto o promoción
    if (type === 'productos') {
        router.visit(route('productos.show', id));
    } else if (type === 'promociones') {
        router.visit(route('promociones.show', id));
    } else if (type === 'menus') {
        // Para menús, id es el nombre de la ruta
        router.visit(route(id));
    }
};

const formatMoney = (amount) => parseFloat(amount || 0).toFixed(2);
const formatDate = (date) => new Date(date).toLocaleDateString("es-ES");
</script>

<style scoped>
.search-results {
    background-color: var(--card-bg, #ffffff);
    color: var(--text-primary, #212121);
    border-color: var(--border-color, #dee2e6);
}

.list-group-item {
    background-color: var(--card-bg, #ffffff);
    color: var(--text-primary, #212121);
    border-color: var(--border-color, #dee2e6);
    transition: all 0.2s ease;
}

.list-group-item:hover {
    background-color: var(--bg-secondary, #f8f9fa);
    transform: translateX(4px);
}

.list-group-item:active {
    background-color: var(--bg-secondary, #e9ecef);
}

/* Mejora en scrollbar para resultados largos */
.search-results::-webkit-scrollbar {
    width: 8px;
}

.search-results::-webkit-scrollbar-track {
    background: var(--bg-secondary, #f8f9fa);
}

.search-results::-webkit-scrollbar-thumb {
    background: var(--border-color, #dee2e6);
    border-radius: 4px;
}

.search-results::-webkit-scrollbar-thumb:hover {
    background: var(--text-muted, #6c757d);
}

/* Responsive para móvil */
@media (max-width: 575.98px) {
    .search-results {
        max-height: 400px !important;
    }
}
</style>
