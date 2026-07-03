<script setup>
/**
 * Logo de RAO MOTOS. La imagen vive en public/img/logo.png y se sirve como /img/logo.png
 * (Herd/Laravel sirven la carpeta public/ como raíz, por eso NO se pone "public" ni backslashes).
 * El emblema ya incluye el texto "RAO MOTOS", por eso no se agrega wordmark aparte.
 */
defineProps({
    height: { type: Number, default: 40 },
});
</script>

<template>
    <img
        src="/img/logo.png"
        alt="RAO MOTOS"
        :height="height"
        class="d-block rounded"
        style="width:auto; object-fit:contain;"
    />
</template>
