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
        <svg :height="height" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="1.5" y="1.5" width="41" height="41" rx="10" fill="#b30000" stroke="#7a0000" stroke-width="1.5" />
            <!-- rueda / engranaje estilizado -->
            <circle cx="22" cy="22" r="11" stroke="#fff" stroke-width="2.5" fill="none" />
            <circle cx="22" cy="22" r="3.5" fill="#fff" />
            <g stroke="#fff" stroke-width="2.5" stroke-linecap="round">
                <line x1="22" y1="8" x2="22" y2="12.5" />
                <line x1="22" y1="31.5" x2="22" y2="36" />
                <line x1="8" y1="22" x2="12.5" y2="22" />
                <line x1="31.5" y1="22" x2="36" y2="22" />
            </g>
        </svg>
        <span class="fw-bold lh-1" :class="light ? 'text-white' : 'text-danger'" style="letter-spacing:.5px;">
            RAO<span :class="light ? 'text-white-50' : 'text-dark'"> MOTOS</span>
        </span>
    </span>
</template>
