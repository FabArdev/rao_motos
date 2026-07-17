<template>
    <li class="nav-item dropdown" :class="{ show: isOpen }">
        <a
            class="nav-link dropdown-toggle"
            :class="{ active: isActive }"
            href="#"
            role="button"
            @click.prevent="toggleDropdown"
            :aria-expanded="isOpen"
        >
            <i :class="['bi', item.icon, 'me-1']"></i>
            {{ item.label }}
        </a>

        <ul class="dropdown-menu" :class="{ show: isOpen }">
            <li v-for="child in item.hijos" :key="child.id">
                <Link
                    :href="route(child.route)"
                    class="dropdown-item"
                    :class="{ active: route().current(child.route) }"
                    @click="closeDropdown"
                >
                    <i :class="['bi', child.icon, 'me-2']"></i>
                    {{ child.label }}
                </Link>
            </li>
        </ul>
    </li>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});

const isOpen = ref(false);

const isActive = computed(() => {
    // Verificar si algún hijo está activo
    if (!props.item.hijos || props.item.hijos.length === 0) {
        return false;
    }
    return props.item.hijos.some((child) => route().current(child.route));
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
    isOpen.value = false;
};

// Cerrar dropdown al hacer clic fuera
const handleClickOutside = (event) => {
    const dropdown = event.target.closest(".dropdown");
    if (!dropdown) {
        isOpen.value = false;
    }
};

// Agregar listener al montar
onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

// Remover listener al desmontar
onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped>
.dropdown-menu {
    min-width: 200px;
}

.dropdown-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-item.active {
    background-color: #0d6efd;
    color: white;
}

.dropdown-item.active i {
    color: white;
}
</style>
