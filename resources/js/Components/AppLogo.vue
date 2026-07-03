<script setup>
import { usePage } from '@inertiajs/vue3';

defineProps({
    height: { type: Number, default: 40 },
});

const page = usePage();
</script>

<template>
    <img
        :src="page.props.asset_url ?? '/img/logo.png'"
        alt="RAO MOTOS"
        :height="height"
        class="d-block rounded"
        style="width:auto; object-fit:contain;"
    />
</template>
