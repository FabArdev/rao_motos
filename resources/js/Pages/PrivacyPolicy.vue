<script setup>
import { Head } from "@inertiajs/vue3";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";

defineProps({
    policy: String,
});
</script>

<template>
    <Head title="Política de Privacidad" />

    <div>
        <div class="pt-4 bg-light">
            <div class="min-vh-100 d-flex flex-column align-items-center pt-3">
                <div>
                    <AuthenticationCardLogo />
                </div>

                <div
                    class="w-100 mt-4 p-4 bg-white shadow rounded"
                    style="max-width: 42rem"
                    v-html="policy"
                />
            </div>
        </div>
    </div>
</template>
