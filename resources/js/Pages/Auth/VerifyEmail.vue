<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AuthenticationCard from "@/Components/AuthenticationCard.vue";
import AuthenticationCardLogo from "@/Components/AuthenticationCardLogo.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route("verification.send"));
};

const verificationLinkSent = computed(
    () => props.status === "verification-link-sent"
);
</script>

<template>
    <Head title="Verificación de Correo" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-3 small text-muted">
            Antes de continuar, verifica tu dirección de correo electrónico haciendo clic en el enlace que te acabamos de enviar. Si no recibiste el correo, con gusto te enviaremos otro.
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-3 fw-medium small text-success"
        >
            Se ha enviado un nuevo enlace de verificación a la dirección de correo electrónico que proporcionaste en tu perfil.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-3 d-flex align-items-center justify-content-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Reenviar correo de verificación
                </PrimaryButton>

                <div>
                    <Link
                        :href="route('profile.show')"
                        class="text-decoration-underline small text-muted"
                    >
                        Editar Perfil</Link
                    >

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-decoration-underline small text-muted ms-2"
                    >
                        Cerrar sesión
                    </Link>
                </div>
            </div>
        </form>
    </AuthenticationCard>
</template>
