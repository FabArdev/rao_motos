<script setup>
/**
 * Logo provisional de RAO MOTOS (marca de agua vectorial).
 * ── Para reemplazarlo por el logo real ──────────────────────────────
 *  Opción A: borra el <svg> de abajo y pon <img src="/img/logo.png" ...>
 *            (coloca el archivo en public/img/logo.png).
 *  Opción B: reemplaza el contenido del <svg> por el de tu logo.
 * El prop `light` pinta el texto en blanco (para fondos oscuros/rojos).
 */
defineProps({
    light: { type: Boolean, default: false },
    height: { type: Number, default: 34 },
});
</script>

<template>
    <span class="d-inline-flex align-items-center gap-2">
        <img src="/img/logo.png" alt="RAO MOTOS" :height="height" style="object-fit:contain;" />
        <span class="fw-bold lh-1" :class="light ? 'text-white' : 'text-danger'" style="letter-spacing:.5px;">
            RAO<span :class="light ? 'text-white-50' : 'text-dark'"> MOTOS</span>
        </span>
    </span>
</template>
